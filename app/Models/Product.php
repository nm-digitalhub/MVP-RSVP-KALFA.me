<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product catalog for entitlements. No predefined feature keys.
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'metadata' => 'array',
        ];
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(ProductEntitlement::class, 'product_id');
    }

    public function productEntitlements(): HasMany
    {
        return $this->entitlements();
    }

    public function activeEntitlements(): HasMany
    {
        return $this->entitlements()->where('is_active', true);
    }

    public function limits(): HasMany
    {
        return $this->hasMany(ProductLimit::class, 'product_id');
    }

    public function activeLimits(): HasMany
    {
        return $this->limits()->where('is_active', true);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class, 'product_id');
    }

    public function enabledFeatures(): HasMany
    {
        return $this->features()->where('is_enabled', true);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'product_id');
    }

    public function productPlans(): HasMany
    {
        return $this->hasMany(ProductPlan::class, 'product_id')->orderBy('sort_order');
    }

    public function accountProducts(): HasMany
    {
        return $this->hasMany(AccountProduct::class, 'product_id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class, 'product_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    #[Scope]
    protected function draft(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Draft);
    }

    #[Scope]
    protected function byCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === ProductStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === ProductStatus::Archived;
    }
}
