<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementType;
use App\Services\FeatureResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grant: account has a feature_key (from product or manual). No enforcement in this phase.
 */
class AccountEntitlement extends Model
{
    protected $fillable = [
        'account_id',
        'feature_key',
        'value',
        'type',
        'product_entitlement_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EntitlementType::class,
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (AccountEntitlement $entitlement): void {
            app(FeatureResolver::class)->forgetByAccountId($entitlement->account_id, $entitlement->feature_key);
        });

        static::deleted(function (AccountEntitlement $entitlement): void {
            app(FeatureResolver::class)->forgetByAccountId($entitlement->account_id, $entitlement->feature_key);
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function productEntitlement(): BelongsTo
    {
        return $this->belongsTo(ProductEntitlement::class, 'product_entitlement_id');
    }
}
