<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EntitlementType;
use App\Enums\ProductPriceBillingCycle;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductIntegrityChecker;
use Illuminate\Database\Seeder;

class AiVoiceAgentProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()
            ->where('slug', 'ai-voice-agent')
            ->orWhere('name', 'AI Voice Agent RSVP')
            ->first();

        if ($product === null) {
            $product = new Product;
        }

        $product->fill([
            'name' => 'AI Voice Agent RSVP',
            'slug' => 'ai-voice-agent',
            'description' => 'Automatic RSVP confirmation calls using Gemini Live AI over Twilio voice and media streams.',
            'category' => 'Voice AI',
            'status' => ProductStatus::Active,
            'metadata' => [
                'commercial_model' => [
                    'pricing_basis' => [
                        'assumed_average_call_minutes' => 2,
                        'estimated_direct_costs_usd_per_minute' => [
                            'twilio_voice_israel_mobile_from_israel' => 0.0646,
                            'twilio_media_streams' => 0.0040,
                            'gemini_live_audio' => 0.0225,
                        ],
                        'estimated_direct_cost_usd_per_minute_total' => 0.0911,
                        'estimated_direct_cost_usd_per_call' => 0.1822,
                        'target_margin_percent' => 18,
                    ],
                    'sources' => [
                        'twilio_voice' => 'https://www.twilio.com/en-us/voice/pricing/il',
                        'google_live_api' => 'https://cloud.google.com/vertex-ai/generative-ai/pricing',
                    ],
                ],
            ],
        ])->save();

        $product->entitlements()->updateOrCreate(
            ['feature_key' => 'voice_rsvp_enabled'],
            [
                'label' => 'AI Voice RSVP Enabled',
                'value' => 'true',
                'type' => EntitlementType::Boolean,
                'description' => 'Enables automated RSVP voice calls for the assigned account.',
                'is_active' => true,
            ]
        );

        $product->entitlements()
            ->where('feature_key', 'voice_rsvp_limit')
            ->delete();

        $product->limits()->updateOrCreate(
            ['limit_key' => 'average_call_minutes_assumption'],
            [
                'label' => 'Average Call Minutes Assumption',
                'value' => '2',
                'description' => 'Commercial pricing assumes an average AI RSVP call duration of two minutes.',
                'is_active' => true,
            ]
        );

        $product->limits()->updateOrCreate(
            ['limit_key' => 'direct_cost_usd_per_minute'],
            [
                'label' => 'Estimated Direct Cost Per Minute (USD)',
                'value' => '0.0911',
                'description' => 'Twilio Israel mobile from Israel + Twilio Media Streams + Gemini Live audio processing.',
                'is_active' => true,
            ]
        );

        $product->features()->updateOrCreate(
            ['feature_key' => 'ai_provider'],
            [
                'label' => 'AI Provider',
                'value' => 'gemini-live',
                'description' => 'Live voice reasoning and turn management are powered by Gemini Live.',
                'is_enabled' => true,
            ]
        );

        $product->features()->updateOrCreate(
            ['feature_key' => 'voice_transport'],
            [
                'label' => 'Voice Transport',
                'value' => 'twilio-media-streams',
                'description' => 'Calls are delivered over Twilio Programmable Voice and Media Streams.',
                'is_enabled' => true,
            ]
        );

        $plans = [
            'starter' => [
                'name' => 'Starter',
                'description' => 'For low-volume RSVP campaigns that need a predictable monthly bundle.',
                'limits' => [
                    'voice_rsvp_limit' => 120,
                    'voice_minutes_limit' => 240,
                ],
                'monthly_amount' => 9900,
                'usage_amount' => 75,
            ],
            'growth' => [
                'name' => 'Growth',
                'description' => 'For active event teams that run recurring RSVP campaigns each month.',
                'limits' => [
                    'voice_rsvp_limit' => 400,
                    'voice_minutes_limit' => 800,
                ],
                'monthly_amount' => 29900,
                'usage_amount' => 69,
            ],
            'scale' => [
                'name' => 'Scale',
                'description' => 'For high-volume voice RSVP operations with a lower blended overage rate.',
                'limits' => [
                    'voice_rsvp_limit' => 1200,
                    'voice_minutes_limit' => 2400,
                ],
                'monthly_amount' => 84900,
                'usage_amount' => 65,
            ],
        ];

        foreach ($plans as $slug => $planDefinition) {
            $plan = $product->productPlans()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $planDefinition['name'],
                    'description' => $planDefinition['description'],
                    'is_active' => true,
                    'metadata' => [
                        'limits' => $planDefinition['limits'],
                        'usage_policies' => [
                            'voice_rsvp_calls' => ['mode' => 'hard'],
                            'voice_minutes' => ['mode' => 'hard'],
                        ],
                        'commercial' => [
                            'included_unit' => 'voice_rsvp_calls',
                            'included_quantity' => $planDefinition['limits']['voice_rsvp_limit'],
                            'overage_metric_key' => 'voice_rsvp_calls',
                            'overage_unit' => 'call',
                            'overage_amount_minor' => $planDefinition['usage_amount'],
                            'currency' => 'ILS',
                            'assumed_average_call_minutes' => 2,
                            'target_margin_percent' => 18,
                        ],
                    ],
                ]
            );

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => ProductPriceBillingCycle::Monthly],
                [
                    'currency' => 'ILS',
                    'amount' => $planDefinition['monthly_amount'],
                    'is_active' => true,
                    'metadata' => [
                        'price_type' => 'subscription',
                        'included_voice_rsvp_calls' => $planDefinition['limits']['voice_rsvp_limit'],
                        'included_voice_minutes' => $planDefinition['limits']['voice_minutes_limit'],
                    ],
                ]
            );

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => ProductPriceBillingCycle::Usage],
                [
                    'currency' => 'ILS',
                    'amount' => $planDefinition['usage_amount'],
                    'is_active' => true,
                    'metadata' => [
                        'price_type' => 'overage',
                        'metric_key' => 'voice_rsvp_calls',
                        'unit' => 'call',
                        'assumed_average_call_minutes' => 2,
                    ],
                ]
            );
        }

        app(ProductIntegrityChecker::class)->assertProductSeedable(
            $product->fresh(['entitlements', 'productPlans.activePrices'])
        );
    }
}
