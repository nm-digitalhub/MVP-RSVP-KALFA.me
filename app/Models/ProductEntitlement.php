<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitlement granted by a product. feature_key is a free-form string.
 */
class ProductEntitlement extends Model
{
    protected $table = 'product_entitlements';

    protected $fillable = [
        'product_id',
        'feature_key',
        'value',
        'constraints',
    ];

    protected function casts(): array
    {
        return [
            'constraints' => 'array',
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
}
