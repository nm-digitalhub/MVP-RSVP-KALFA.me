<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int $account_id
 * @property int $redeemed_by
 * @property string|null $redeemable_type
 * @property int|null $redeemable_id
 * @property int $discount_applied
 * @property int $trial_days_added
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\Coupon $coupon
 * @property-read Model|\Eloquent|null $redeemable
 * @property-read \App\Models\User $redeemedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereDiscountApplied($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereRedeemableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereRedeemableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereRedeemedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereTrialDaysAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponRedemption whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperCouponRedemption
 */
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
