<?php

declare(strict_types=1);

namespace App\Models;

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
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function productEntitlements(): HasMany
    {
        return $this->hasMany(ProductEntitlement::class, 'product_id');
    }
}
