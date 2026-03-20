<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitlement granted by a product. feature_key is a free-form string.
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, EntitlementType $type): Builder
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
