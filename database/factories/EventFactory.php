<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->sentence(3, false);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'event_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'venue_name' => fake()->optional(0.7)->city(),
            'settings' => [],
            'status' => EventStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Active,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Draft,
        ]);
    }
}
