<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property CouponDiscountType $discount_type
 * @property int $discount_value
 * @property CouponTargetType $target_type
 * @property array<array-key, mixed>|null $target_ids
 * @property int|null $max_uses
 * @property int|null $max_uses_per_account
 * @property bool $first_time_only
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $discount_duration_months
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CouponRedemption> $redemptions
 * @property-read int|null $redemptions_count
 * @method static Builder<static>|Coupon active()
 * @method static \Database\Factories\CouponFactory factory($count = null, $state = [])
 * @method static Builder<static>|Coupon newModelQuery()
 * @method static Builder<static>|Coupon newQuery()
 * @method static Builder<static>|Coupon query()
 * @method static Builder<static>|Coupon whereCode($value)
 * @method static Builder<static>|Coupon whereCreatedAt($value)
 * @method static Builder<static>|Coupon whereCreatedBy($value)
 * @method static Builder<static>|Coupon whereDescription($value)
 * @method static Builder<static>|Coupon whereDiscountDurationMonths($value)
 * @method static Builder<static>|Coupon whereDiscountType($value)
 * @method static Builder<static>|Coupon whereDiscountValue($value)
 * @method static Builder<static>|Coupon whereExpiresAt($value)
 * @method static Builder<static>|Coupon whereFirstTimeOnly($value)
 * @method static Builder<static>|Coupon whereId($value)
 * @method static Builder<static>|Coupon whereIsActive($value)
 * @method static Builder<static>|Coupon whereMaxUses($value)
 * @method static Builder<static>|Coupon whereMaxUsesPerAccount($value)
 * @method static Builder<static>|Coupon whereTargetIds($value)
 * @method static Builder<static>|Coupon whereTargetType($value)
 * @method static Builder<static>|Coupon whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperCoupon
 */
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
    #[Scope]
    protected function active(Builder $query): void
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
