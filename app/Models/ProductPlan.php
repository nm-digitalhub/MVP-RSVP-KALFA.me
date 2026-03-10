<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPlan extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'sku',
        'description',
        'is_active',
        'metadata',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function limit(string $featureKey): mixed
    {
        return data_get($this->metadata, "limits.{$featureKey}");
    }

    public function primaryPrice(): ?ProductPrice
    {
        return $this->activePrices()->orderBy('id')->first();
    }
}
