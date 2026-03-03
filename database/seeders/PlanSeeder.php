<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed at least one per_event plan for checkout/billing flow.
     */
    public function run(): void
    {
        Plan::firstOrCreate(
            ['slug' => 'per-event-basic'],
            [
                'name' => 'תוכנית אירוע בודד',
                'type' => 'per_event',
                'limits' => null,
                'price_cents' => 9900, // 99.00 ILS
                'billing_interval' => null,
            ]
        );
    }
}
