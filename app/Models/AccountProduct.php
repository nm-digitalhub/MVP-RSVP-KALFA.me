<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountProductStatus;
use App\Services\FeatureResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountProduct extends Model
{
    protected $fillable = [
        'account_id',
        'product_id',
        'status',
        'granted_at',
        'expires_at',
        'granted_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountProductStatus::class,
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $flushResolvedFeatures = function (AccountProduct $accountProduct): void {
            $featureKeys = $accountProduct->product()
                ->with('productEntitlements')
                ->first()?->productEntitlements
                ->pluck('feature_key')
                ->all() ?? [];

            app(FeatureResolver::class)->forgetMany(
                $accountProduct->account()->firstOrFail(),
                $featureKeys,
            );
        };

        static::saved($flushResolvedFeatures);
        static::deleted($flushResolvedFeatures);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', AccountProductStatus::Active->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
