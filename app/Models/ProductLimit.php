<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $limit_key
 * @property string $label
 * @property string $value
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @method static Builder<static>|ProductLimit active()
 * @method static \Database\Factories\ProductLimitFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductLimit newModelQuery()
 * @method static Builder<static>|ProductLimit newQuery()
 * @method static Builder<static>|ProductLimit query()
 * @method static Builder<static>|ProductLimit whereCreatedAt($value)
 * @method static Builder<static>|ProductLimit whereDescription($value)
 * @method static Builder<static>|ProductLimit whereId($value)
 * @method static Builder<static>|ProductLimit whereIsActive($value)
 * @method static Builder<static>|ProductLimit whereLabel($value)
 * @method static Builder<static>|ProductLimit whereLimitKey($value)
 * @method static Builder<static>|ProductLimit whereProductId($value)
 * @method static Builder<static>|ProductLimit whereUpdatedAt($value)
 * @method static Builder<static>|ProductLimit whereValue($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProductLimit
 */
class ProductLimit extends Model
{
    /** @use HasFactory<\Database\Factories\ProductLimitFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'limit_key',
        'label',
        'value',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
