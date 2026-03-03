<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Seeder;

class AppointmentWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Services
        Service::create([
            'name' => 'General Consultation',
            'description' => 'A comprehensive consultation to discuss your needs and develop a personalized plan.',
            'duration_minutes' => 60,
            'price' => 150.00,
        ]);

        Service::create([
            'name' => 'Follow-up Session',
            'description' => 'A shorter session to review progress and make necessary adjustments to your plan.',
            'duration_minutes' => 30,
            'price' => 75.00,
        ]);

        Service::create([
            'name' => 'Initial Assessment',
            'description' => 'Detailed initial evaluation to understand your current situation and goals.',
            'duration_minutes' => 90,
            'price' => 200.00,
        ]);

        Service::create([
            'name' => 'Group Workshop',
            'description' => 'Interactive group session covering important topics and strategies.',
            'duration_minutes' => 120,
            'price' => 100.00,
        ]);

        Service::create([
            'name' => 'Quick Check-in',
            'description' => 'Brief session for quick questions or minor adjustments.',
            'duration_minutes' => 15,
            'price' => 40.00,
        ]);

        Service::create([
            'name' => 'Comprehensive Review',
            'description' => 'In-depth review of your progress with detailed recommendations.',
            'duration_minutes' => 75,
            'price' => 175.00,
        ]);

        // Create Providers
        Provider::create([
            'name' => 'Dr. Sarah Johnson',
            'specialty' => 'General Practice',
            'is_available' => true,
        ]);

        Provider::create([
            'name' => 'Dr. Michael Chen',
            'specialty' => 'Specialist Consultant',
            'is_available' => true,
        ]);

        Provider::create([
            'name' => 'Dr. Emily Williams',
            'specialty' => 'Senior Practitioner',
            'is_available' => true,
        ]);

        Provider::create([
            'name' => 'Dr. James Anderson',
            'specialty' => 'Lead Consultant',
            'is_available' => true,
        ]);

        Provider::create([
            'name' => 'Dr. Lisa Martinez',
            'specialty' => 'Expert Advisor',
            'is_available' => true,
        ]);

        Provider::create([
            'name' => 'Dr. David Thompson',
            'specialty' => 'Chief Consultant',
            'is_available' => false, // One unavailable provider for testing
        ]);
    }
}
