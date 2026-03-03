<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = [
            ['name' => 'Dr. Sarah Johnson', 'specialty' => 'General Practice'],
            ['name' => 'Dr. Michael Chen', 'specialty' => 'Specialist Consultant'],
            ['name' => 'Dr. Emily Williams', 'specialty' => 'Senior Practitioner'],
            ['name' => 'Dr. James Anderson', 'specialty' => 'Lead Consultant'],
            ['name' => 'Dr. Lisa Martinez', 'specialty' => 'Expert Advisor'],
            ['name' => 'Dr. David Thompson', 'specialty' => 'Chief Consultant'],
        ];

        return array_merge(
            fake()->randomElement($providers),
            ['is_available' => true]
        );
    }

    /**
     * Indicate that the provider is not available.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
