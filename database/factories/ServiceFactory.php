<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            [
                'name' => 'General Consultation',
                'description' => 'A comprehensive consultation to discuss your needs and develop a personalized plan.',
                'duration_minutes' => 60,
                'price' => 150.00,
            ],
            [
                'name' => 'Follow-up Session',
                'description' => 'A shorter session to review progress and make necessary adjustments to your plan.',
                'duration_minutes' => 30,
                'price' => 75.00,
            ],
            [
                'name' => 'Initial Assessment',
                'description' => 'Detailed initial evaluation to understand your current situation and goals.',
                'duration_minutes' => 90,
                'price' => 200.00,
            ],
            [
                'name' => 'Group Workshop',
                'description' => 'Interactive group session covering important topics and strategies.',
                'duration_minutes' => 120,
                'price' => 100.00,
            ],
            [
                'name' => 'Quick Check-in',
                'description' => 'Brief session for quick questions or minor adjustments.',
                'duration_minutes' => 15,
                'price' => 40.00,
            ],
            [
                'name' => 'Comprehensive Review',
                'description' => 'In-depth review of your progress with detailed recommendations.',
                'duration_minutes' => 75,
                'price' => 175.00,
            ],
        ];

        return fake()->randomElement($services);
    }
}
