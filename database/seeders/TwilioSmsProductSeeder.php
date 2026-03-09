<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductIntegrityChecker;
use Illuminate\Database\Seeder;

class TwilioSmsProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()
            ->whereIn('slug', ['twilio-sms', 'sms-twilio'])
            ->orWhereIn('name', ['Twilio SMS', 'SMS-twilio'])
            ->first();

        if ($product === null) {
            $product = new Product;
        }

        $product->fill([
            'name' => 'Twilio SMS',
            'slug' => 'twilio-sms',
            'description' => 'Transactional SMS confirmations and messaging powered by Twilio.',
            'category' => 'Twilio',
            'status' => ProductStatus::Active,
        ])->save();

        $entitlements = [
            'twilio_enabled' => [
                'label' => 'Twilio Integration Enabled',
                'value' => 'true',
                'type' => EntitlementType::Boolean,
                'description' => 'Enables Twilio-backed communication features for the account.',
            ],
            'sms_confirmation_enabled' => [
                'label' => 'SMS Confirmations Enabled',
                'value' => 'true',
                'type' => EntitlementType::Boolean,
                'description' => 'Allows RSVP confirmation SMS messages to be sent through Twilio.',
            ],
            'sms_confirmation_limit' => [
                'label' => 'SMS Confirmations Per Month',
                'value' => '500',
                'type' => EntitlementType::Number,
                'description' => 'Monthly quota for transactional RSVP confirmation SMS messages.',
            ],
        ];

        $product->entitlements()
            ->whereNotIn('feature_key', array_keys($entitlements))
            ->delete();

        foreach ($entitlements as $featureKey => $attributes) {
            $product->entitlements()->updateOrCreate(
                ['feature_key' => $featureKey],
                [
                    'label' => $attributes['label'],
                    'value' => $attributes['value'],
                    'type' => $attributes['type'],
                    'description' => $attributes['description'],
                    'is_active' => true,
                ]
            );
        }

        app(ProductIntegrityChecker::class)->assertProductSeedable($product->fresh(['entitlements', 'productPlans.activePrices']));
    }
}
