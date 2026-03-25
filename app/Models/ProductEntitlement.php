<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitlement granted by a product. feature_key is a free-form string.
 *
 * @property int $id
 * @property int $product_id
 * @property string $feature_key
 * @property string|null $value
 * @property array<array-key, mixed>|null $constraints
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $label
 * @property EntitlementType $type
 * @property bool $is_active
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountEntitlement> $accountEntitlements
 * @property-read int|null $account_entitlements_count
 * @property-read \App\Models\Product $product
 * @method static Builder<static>|ProductEntitlement active()
 * @method static Builder<static>|ProductEntitlement byType(\App\Enums\EntitlementType $type)
 * @method static Builder<static>|ProductEntitlement newModelQuery()
 * @method static Builder<static>|ProductEntitlement newQuery()
 * @method static Builder<static>|ProductEntitlement query()
 * @method static Builder<static>|ProductEntitlement whereConstraints($value)
 * @method static Builder<static>|ProductEntitlement whereCreatedAt($value)
 * @method static Builder<static>|ProductEntitlement whereDescription($value)
 * @method static Builder<static>|ProductEntitlement whereFeatureKey($value)
 * @method static Builder<static>|ProductEntitlement whereId($value)
 * @method static Builder<static>|ProductEntitlement whereIsActive($value)
 * @method static Builder<static>|ProductEntitlement whereLabel($value)
 * @method static Builder<static>|ProductEntitlement whereProductId($value)
 * @method static Builder<static>|ProductEntitlement whereType($value)
 * @method static Builder<static>|ProductEntitlement whereUpdatedAt($value)
 * @method static Builder<static>|ProductEntitlement whereValue($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProductEntitlement
 */
class ProductEntitlement extends Model
{
    protected $fillable = [
        'product_id',
        'feature_key',
        'label',
        'value',
        'type',
        'is_active',
        'description',
        'constraints',
    ];

    protected function casts(): array
    {
        return [
            'type' => EntitlementType::class,
            'is_active' => 'boolean',
            'constraints' => 'array',
        ];
    }

    protected function attributes(): array
    {
        return [
            'label' => $this->label ?? $this->feature_key,
            'type' => $this->type ?? EntitlementType::Text,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function accountEntitlements(): HasMany
    {
        return $this->hasMany(AccountEntitlement::class, 'product_entitlement_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function byType(Builder $query, EntitlementType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isBoolean(): bool
    {
        return $this->type === EntitlementType::Boolean;
    }

    public function isNumber(): bool
    {
        return $this->type === EntitlementType::Number;
    }
}
