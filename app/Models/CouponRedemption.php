<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class CouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id',
        'account_id',
        'redeemed_by',
        'redeemable_type',
        'redeemable_id',
        'discount_applied',
        'trial_days_added',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'integer',
            'trial_days_added' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function redeemable(): MorphTo
    {
        return $this->morphTo();
    }
}
