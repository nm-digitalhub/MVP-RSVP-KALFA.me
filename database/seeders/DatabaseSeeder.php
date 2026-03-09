<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First: ensure initial admin exists when no users (e.g. fresh install)
        $this->call(InitialAdminSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // RSVP: at least one per_event plan for billing
        $this->call(PlanSeeder::class);

        // Workflow seeders require models that are not in this codebase (CartItem, Provider, Service, Appointment).
        // Uncomment when those domains are implemented:
        // $this->call([AppointmentWorkflowSeeder::class, CheckoutWorkflowSeeder::class]);
    }
}
