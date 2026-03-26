<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductPriceBillingCycle;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_plan_id
 * @property string $currency
 * @property int $amount
 * @property ProductPriceBillingCycle $billing_cycle
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductPlan $productPlan
 * @method static Builder<static>|ProductPrice active()
 * @method static Builder<static>|ProductPrice newModelQuery()
 * @method static Builder<static>|ProductPrice newQuery()
 * @method static Builder<static>|ProductPrice query()
 * @method static Builder<static>|ProductPrice whereAmount($value)
 * @method static Builder<static>|ProductPrice whereBillingCycle($value)
 * @method static Builder<static>|ProductPrice whereCreatedAt($value)
 * @method static Builder<static>|ProductPrice whereCurrency($value)
 * @method static Builder<static>|ProductPrice whereId($value)
 * @method static Builder<static>|ProductPrice whereIsActive($value)
 * @method static Builder<static>|ProductPrice whereMetadata($value)
 * @method static Builder<static>|ProductPrice whereProductPlanId($value)
 * @method static Builder<static>|ProductPrice whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProductPrice
 */
class ProductPrice extends Model
{
    use HasFactory;
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
