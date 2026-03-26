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
 * @property string $feature_key
 * @property string $label
 * @property string|null $value
 * @property string|null $description
 * @property bool $is_enabled
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @method static Builder<static>|ProductFeature enabled()
 * @method static \Database\Factories\ProductFeatureFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductFeature newModelQuery()
 * @method static Builder<static>|ProductFeature newQuery()
 * @method static Builder<static>|ProductFeature query()
 * @method static Builder<static>|ProductFeature whereCreatedAt($value)
 * @method static Builder<static>|ProductFeature whereDescription($value)
 * @method static Builder<static>|ProductFeature whereFeatureKey($value)
 * @method static Builder<static>|ProductFeature whereId($value)
 * @method static Builder<static>|ProductFeature whereIsEnabled($value)
 * @method static Builder<static>|ProductFeature whereLabel($value)
 * @method static Builder<static>|ProductFeature whereMetadata($value)
 * @method static Builder<static>|ProductFeature whereProductId($value)
 * @method static Builder<static>|ProductFeature whereUpdatedAt($value)
 * @method static Builder<static>|ProductFeature whereValue($value)
 * @mixin \Eloquent
 * @mixin IdeHelperProductFeature
 */
class ProductFeature extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'feature_key',
        'label',
        'value',
        'description',
        'is_enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
