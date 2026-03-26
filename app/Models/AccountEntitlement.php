<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementType;
use App\Services\FeatureResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grant: account has a feature_key (from product or manual). No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $feature_key
 * @property string|null $value
 * @property int|null $product_entitlement_id
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property EntitlementType|null $type
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\ProductEntitlement|null $productEntitlement
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereProductEntitlementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereValue($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccountEntitlement
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
