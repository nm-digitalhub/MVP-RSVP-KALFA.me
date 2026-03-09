<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountSubscriptionStatus;
use App\Services\FeatureResolver;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSubscription extends Model
{
    protected $fillable = [
        'account_id',
        'product_plan_id',
        'status',
        'started_at',
        'trial_ends_at',
        'ends_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountSubscriptionStatus::class,
            'started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $flushAccountFeatures = function (AccountSubscription $subscription): void {
            $featureKeys = array_unique(array_filter([
                ...($subscription->productPlan?->product?->activeEntitlements()->pluck('feature_key')->all() ?? []),
                ...array_keys((array) data_get($subscription->productPlan?->metadata, 'limits', [])),
            ]));

            if ($featureKeys === []) {
                return;
            }

            app(FeatureResolver::class)->forgetMany($subscription->account, $featureKeys);
        };

        static::saved($flushAccountFeatures);
        static::deleted($flushAccountFeatures);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', AccountSubscriptionStatus::Active->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function activate(?int $grantedBy = null): self
    {
        return app(SubscriptionService::class)->activate($this, $grantedBy);
    }

    public function cancel(): self
    {
        return app(SubscriptionService::class)->cancel($this);
    }

    public function suspend(): self
    {
        return app(SubscriptionService::class)->suspend($this);
    }

    public function renew(): self
    {
        return app(SubscriptionService::class)->renew($this);
    }
}
