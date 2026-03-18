<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
final class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('??##-??##')),
            'description' => $this->faker->sentence(),
            'discount_type' => $this->faker->randomElement(CouponDiscountType::cases()),
            'discount_value' => $this->faker->numberBetween(5, 50),
            'target_type' => CouponTargetType::Global,
            'target_ids' => null,
            'max_uses' => null,
            'max_uses_per_account' => null,
            'first_time_only' => false,
            'is_active' => true,
            'expires_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function percentage(int $percent = 20): static
    {
        return $this->state([
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => $percent,
        ]);
    }

    public function fixed(int $nis = 50): static
    {
        return $this->state([
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => $nis,
        ]);
    }

    public function trialExtension(int $days = 30): static
    {
        return $this->state([
            'discount_type' => CouponDiscountType::TrialExtension,
            'discount_value' => $days,
        ]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function firstTimeOnly(): static
    {
        return $this->state(['first_time_only' => true]);
    }

    public function withMaxUses(int $max): static
    {
        return $this->state(['max_uses' => $max]);
    }
}
