<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product catalog for entitlements. No predefined feature keys.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property ProductStatus $status
 * @property string|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $accountProducts
 * @property-read int|null $account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $activeEntitlements
 * @property-read int|null $active_entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductLimit> $activeLimits
 * @property-read int|null $active_limits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductFeature> $enabledFeatures
 * @property-read int|null $enabled_features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $entitlements
 * @property-read int|null $entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductFeature> $features
 * @property-read int|null $features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductLimit> $limits
 * @property-read int|null $limits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Plan> $plans
 * @property-read int|null $plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $productEntitlements
 * @property-read int|null $product_entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPlan> $productPlans
 * @property-read int|null $product_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UsageRecord> $usageRecords
 * @property-read int|null $usage_records_count
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product byCategory(?string $category)
 * @method static Builder<static>|Product draft()
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product whereCategory($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereMetadata($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereSlug($value)
 * @method static Builder<static>|Product whereStatus($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasFactory;
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
