<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductFeature>
 */
class ProductFeatureFactory extends Factory
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
            'feature_key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'value' => fake()->boolean() ? fake()->word() : null,
            'description' => fake()->sentence(),
            'is_enabled' => true,
            'metadata' => null,
        ];
    }
}
