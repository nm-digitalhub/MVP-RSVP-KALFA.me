<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $sku
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $activePrices
 * @property-read int|null $active_prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $prices
 * @property-read int|null $prices_count
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static Builder<static>|ProductPlan active()
 * @method static Builder<static>|ProductPlan newModelQuery()
 * @method static Builder<static>|ProductPlan newQuery()
 * @method static Builder<static>|ProductPlan query()
 * @method static Builder<static>|ProductPlan whereCreatedAt($value)
 * @method static Builder<static>|ProductPlan whereDescription($value)
 * @method static Builder<static>|ProductPlan whereId($value)
 * @method static Builder<static>|ProductPlan whereIsActive($value)
 * @method static Builder<static>|ProductPlan whereMetadata($value)
 * @method static Builder<static>|ProductPlan whereName($value)
 * @method static Builder<static>|ProductPlan whereProductId($value)
 * @method static Builder<static>|ProductPlan whereSku($value)
 * @method static Builder<static>|ProductPlan whereSlug($value)
 * @method static Builder<static>|ProductPlan whereSortOrder($value)
 * @method static Builder<static>|ProductPlan whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProductPlan
 */
class ProductPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'sku',
        'description',
        'is_active',
        'metadata',
        'sumit_entity_id',

    ];

    protected static function booted(): void
    {
        static::creating(function (ProductPlan $plan) {
            if (empty($plan->sku)) {
                $productSlug = $plan->product?->slug ?? 'PRODUCT';
                $planSlug = $plan->slug;
                $plan->sku = strtoupper("{$productSlug}_{$planSlug}");
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_plan_id');
    }

    public function activePrices(): HasMany
    {
        return $this->prices()->where('is_active', true);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AccountSubscription::class, 'product_plan_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function limit(string $featureKey): mixed
    {
        return data_get($this->metadata, "limits.{$featureKey}");
    }

    public function primaryPrice(): ?ProductPrice
    {
        return $this->activePrices()
            ->orderByRaw("CASE billing_cycle WHEN 'monthly' THEN 0 WHEN 'yearly' THEN 1 WHEN 'usage' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->first();
    }
}
