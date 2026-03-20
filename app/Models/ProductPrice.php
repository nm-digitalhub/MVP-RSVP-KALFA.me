<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use App\Enums\ProductPriceBillingCycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_plan_id',
        'currency',
        'amount',
        'billing_cycle',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle' => ProductPriceBillingCycle::class,
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
