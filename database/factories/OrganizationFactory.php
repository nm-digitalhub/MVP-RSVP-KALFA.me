<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'billing_email' => fake()->optional(0.3)->companyEmail(),
            'settings' => null,
            'is_suspended' => false,
        ];
    }

    /**
     * Configure the model factory: attach one user as owner.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Organization $organization): void {
            if ($organization->users()->count() > 0) {
                return;
            }
            $user = User::factory()->create();
            $organization->users()->attach($user->id, ['role' => OrganizationUserRole::Owner->value]);
        });
    }
}
