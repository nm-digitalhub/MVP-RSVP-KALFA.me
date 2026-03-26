<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductPriceBillingCycle;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'product_plan_id' => null,
            'currency' => 'ILS',
            'amount' => $this->faker->numberBetween(1000, 100000),
            'billing_cycle' => $this->faker->randomElement(ProductPriceBillingCycle::class),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
