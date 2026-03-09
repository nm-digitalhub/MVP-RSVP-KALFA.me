<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'type',
        'limits',
        'price_cents',
        'billing_interval',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'price_cents' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function eventsBilling(): HasMany
    {
        return $this->hasMany(EventBilling::class, 'plan_id');
    }
}
