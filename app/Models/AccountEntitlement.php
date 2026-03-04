<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grant: account has a feature_key (from product or manual). No enforcement in this phase.
 */
class AccountEntitlement extends Model
{
    protected $fillable = [
        'account_id',
        'feature_key',
        'value',
        'product_entitlement_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function productEntitlement(): BelongsTo
    {
        return $this->belongsTo(ProductEntitlement::class, 'product_entitlement_id');
    }
}
