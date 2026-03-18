<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'discount_duration_months',
        'target_type',
        'target_ids',
        'max_uses',
        'max_uses_per_account',
        'first_time_only',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'target_type' => CouponTargetType::class,
            'target_ids' => 'array',
            'max_uses' => 'integer',
            'max_uses_per_account' => 'integer',
            'discount_value' => 'integer',
            'discount_duration_months' => 'integer',
            'first_time_only' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /** Scope to only active, non-expired coupons. */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /** Returns true when total usage has not hit the cap. */
    public function hasUsesRemaining(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }

        return $this->redemptions()->count() < $this->max_uses;
    }

    /** Returns true when this account has not hit the per-account cap. */
    public function hasUsesRemainingFor(Account $account): bool
    {
        if ($this->max_uses_per_account === null) {
            return true;
        }

        return $this->redemptions()->where('account_id', $account->id)->count() < $this->max_uses_per_account;
    }

    /** Returns true when this account has never redeemed this coupon (for first_time_only gate). */
    public function hasNotBeenRedeemedBy(Account $account): bool
    {
        return ! $this->redemptions()->where('account_id', $account->id)->exists();
    }

    /**
     * Calculate the discount amount in agorot for a given charge.
     * Returns 0 for trial_extension coupons (days are stored in discount_value).
     *
     * @param  int  $amountMinor  Amount in smallest currency unit (agorot)
     * @return int Discount in agorot
     */
    public function calculateDiscountAmount(int $amountMinor): int
    {
        return match ($this->discount_type) {
            CouponDiscountType::Percentage => (int) round($amountMinor * ($this->discount_value / 100)),
            CouponDiscountType::Fixed => min($amountMinor, $this->discount_value * 100), // discount_value in NIS → agorot
            CouponDiscountType::TrialExtension => 0,
        };
    }

    /**
     * Returns trial days to add (only for TrialExtension type).
     */
    public function getTrialDaysToAdd(): int
    {
        if ($this->discount_type !== CouponDiscountType::TrialExtension) {
            return 0;
        }

        return $this->discount_value;
    }

    /**
     * Whether this coupon applies to the given target type and optional plan ID.
     */
    public function appliesTo(CouponTargetType $targetType, ?int $planId = null): bool
    {
        if ($this->target_type === CouponTargetType::Global) {
            return true;
        }

        if ($this->target_type !== $targetType) {
            return false;
        }

        if ($this->target_type === CouponTargetType::Plan && $planId !== null) {
            $ids = $this->target_ids ?? [];

            return in_array($planId, $ids, strict: true);
        }

        return true;
    }
}
