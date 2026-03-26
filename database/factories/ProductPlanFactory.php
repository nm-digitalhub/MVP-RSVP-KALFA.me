<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPlan>
 */
class ProductPlanFactory extends Factory
{
    protected $model = ProductPlan::class;

    public function definition(): array
    {
        return [
            'product_id' => null,
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug(),
            'sku' => strtoupper($this->faker->lexify('????-????-???')),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
