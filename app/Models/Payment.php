<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'payable_type',
        'payable_id',
        'amount_cents',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'gateway_response' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
