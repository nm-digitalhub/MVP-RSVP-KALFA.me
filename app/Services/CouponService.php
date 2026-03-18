<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class CouponService
{
    /**
     * Validate a coupon code for a given account and context.
     *
     * @throws InvalidArgumentException when the coupon is not applicable
     */
    public function validate(
        string $code,
        Account $account,
        CouponTargetType $targetType,
        ?int $planId = null,
    ): Coupon {
        $coupon = Coupon::active()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if ($coupon === null) {
            throw new InvalidArgumentException('קוד הקופון אינו תקין או שפג תוקפו.');
        }

        if (! $coupon->appliesTo($targetType, $planId)) {
            throw new InvalidArgumentException('קוד הקופון אינו חל על רכישה זו.');
        }

        if (! $coupon->hasUsesRemaining()) {
            throw new InvalidArgumentException('קוד הקופון מוצה — כל השימושים נוצלו.');
        }

        if (! $coupon->hasUsesRemainingFor($account)) {
            throw new InvalidArgumentException('השגת את מגבלת השימוש בקוד קופון זה.');
        }

        if ($coupon->first_time_only && ! $coupon->hasNotBeenRedeemedBy($account)) {
            throw new InvalidArgumentException('קוד קופון זה מיועד לרכישה ראשונה בלבד.');
        }

        return $coupon;
    }

    /**
     * Calculate the discount amount in agorot for a given charge.
     *
     * @param  int  $amountMinor  Charge amount in agorot
     * @return int Discount in agorot (never exceeds original amount)
     */
    public function calculateDiscount(Coupon $coupon, int $amountMinor): int
    {
        $discount = $coupon->calculateDiscountAmount($amountMinor);

        return min($discount, $amountMinor);
    }

    /**
     * Redeem a coupon for an account, recording the redemption.
     *
     * @param  Model|null  $redeemable  The entity the coupon is applied against
     * @param  int  $discountApplied  Actual discount in agorot (0 for trial extensions)
     */
    public function redeem(
        Coupon $coupon,
        Account $account,
        User $redeemedBy,
        ?Model $redeemable = null,
        int $discountApplied = 0,
    ): CouponRedemption {
        $trialDays = $coupon->getTrialDaysToAdd();

        /** @var CouponRedemption $redemption */
        $redemption = $coupon->redemptions()->create([
            'account_id' => $account->id,
            'redeemed_by' => $redeemedBy->id,
            'redeemable_type' => $redeemable ? get_class($redeemable) : null,
            'redeemable_id' => $redeemable?->getKey(),
            'discount_applied' => $discountApplied,
            'trial_days_added' => $trialDays,
            'metadata' => [
                'discount_type' => $coupon->discount_type->value,
                'discount_value' => $coupon->discount_value,
                'discount_duration_months' => $coupon->discount_duration_months,
                'target_type' => $coupon->target_type->value,
            ],
        ]);

        // Extend the account's active trial when discount type is TrialExtension
        if ($coupon->discount_type === CouponDiscountType::TrialExtension && $trialDays > 0) {
            $this->extendTrial($account, $trialDays);
        }

        return $redemption;
    }

    /**
     * Apply a validated coupon to a final charge amount, returning the adjusted amount.
     *
     * @param  int  $originalAmountMinor  Original amount in agorot
     * @return array{amount: int, discount: int}
     */
    public function applyToCharge(Coupon $coupon, int $originalAmountMinor): array
    {
        $discount = $this->calculateDiscount($coupon, $originalAmountMinor);

        return [
            'amount' => max(0, $originalAmountMinor - $discount),
            'discount' => $discount,
        ];
    }

    private function extendTrial(Account $account, int $days): void
    {
        $activeTrial = $account->subscriptions()
            ->where('status', 'trial')
            ->where('trial_ends_at', '>', now())
            ->first();

        if ($activeTrial !== null) {
            $activeTrial->trial_ends_at = $activeTrial->trial_ends_at->addDays($days);
            $activeTrial->save();
        }
    }
}
