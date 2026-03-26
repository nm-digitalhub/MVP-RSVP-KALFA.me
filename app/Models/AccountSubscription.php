<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountSubscriptionStatus;
use App\Services\FeatureResolver;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $product_plan_id
 * @property AccountSubscriptionStatus $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\ProductPlan $productPlan
 * @method static Builder<static>|AccountSubscription active()
 * @method static Builder<static>|AccountSubscription newModelQuery()
 * @method static Builder<static>|AccountSubscription newQuery()
 * @method static Builder<static>|AccountSubscription query()
 * @method static Builder<static>|AccountSubscription whereAccountId($value)
 * @method static Builder<static>|AccountSubscription whereCreatedAt($value)
 * @method static Builder<static>|AccountSubscription whereEndsAt($value)
 * @method static Builder<static>|AccountSubscription whereId($value)
 * @method static Builder<static>|AccountSubscription whereMetadata($value)
 * @method static Builder<static>|AccountSubscription whereProductPlanId($value)
 * @method static Builder<static>|AccountSubscription whereStartedAt($value)
 * @method static Builder<static>|AccountSubscription whereStatus($value)
 * @method static Builder<static>|AccountSubscription whereTrialEndsAt($value)
 * @method static Builder<static>|AccountSubscription whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccountSubscription
 */
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

    #[Scope]
    protected function active(Builder $query): Builder
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
