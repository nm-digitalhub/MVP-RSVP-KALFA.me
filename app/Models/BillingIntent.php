<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Purchase abstraction: links account to a future payment/checkout. No enforcement in this phase.
 */
class BillingIntent extends Model
{
    protected $fillable = [
        'account_id',
        'status',
        'intent_type',
        'payable_type',
        'payable_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
