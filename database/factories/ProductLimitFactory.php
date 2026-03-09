<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductLimit>
 */
class ProductLimitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::query()->create([
                'name' => fake()->unique()->words(2, true),
                'slug' => Str::slug(fake()->unique()->words(3, true)),
                'status' => ProductStatus::Draft,
            ])->id,
            'limit_key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'value' => (string) fake()->numberBetween(1, 5000),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
