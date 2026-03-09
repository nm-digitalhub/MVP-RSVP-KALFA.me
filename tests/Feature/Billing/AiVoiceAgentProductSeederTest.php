<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\EntitlementType;
use App\Enums\ProductPriceBillingCycle;
use App\Enums\ProductStatus;
use App\Models\Account;
use App\Models\Product;
use App\Support\Feature;
use Database\Seeders\AiVoiceAgentProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiVoiceAgentProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_voice_agent_product_seeder_builds_commercial_plans_from_current_cost_model(): void
    {
        Product::query()->create([
            'name' => 'AI Voice Agent RSVP',
            'slug' => 'ai-voice-agent',
            'description' => 'Legacy pricing shape',
            'status' => ProductStatus::Draft,
        ])->entitlements()->createMany([
            [
                'feature_key' => 'voice_rsvp_enabled',
                'label' => 'Voice RSVP Enabled',
                'value' => 'true',
                'type' => EntitlementType::Text,
                'is_active' => true,
            ],
            [
                'feature_key' => 'voice_rsvp_limit',
                'label' => 'Legacy Limit',
                'value' => '100',
                'type' => EntitlementType::Text,
                'is_active' => true,
            ],
        ]);

        $this->seed(AiVoiceAgentProductSeeder::class);

        $product = Product::query()
            ->with(['entitlements', 'limits', 'features', 'productPlans.prices'])
            ->where('slug', 'ai-voice-agent')
            ->firstOrFail();

        $this->assertSame(ProductStatus::Active, $product->status);
        $this->assertSame('AI Voice Agent RSVP', $product->name);
        $this->assertSame('Voice AI', $product->category);

        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'voice_rsvp_enabled',
            'type' => EntitlementType::Boolean->value,
            'value' => 'true',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'voice_rsvp_limit',
        ]);

        $starterPlan = $product->productPlans->firstWhere('slug', 'starter');
        $growthPlan = $product->productPlans->firstWhere('slug', 'growth');
        $scalePlan = $product->productPlans->firstWhere('slug', 'scale');

        $this->assertNotNull($starterPlan);
        $this->assertNotNull($growthPlan);
        $this->assertNotNull($scalePlan);

        $this->assertSame(120, data_get($starterPlan->metadata, 'limits.voice_rsvp_limit'));
        $this->assertSame(240, data_get($starterPlan->metadata, 'limits.voice_minutes_limit'));
        $this->assertSame(400, data_get($growthPlan->metadata, 'limits.voice_rsvp_limit'));
        $this->assertSame(800, data_get($growthPlan->metadata, 'limits.voice_minutes_limit'));
        $this->assertSame(1200, data_get($scalePlan->metadata, 'limits.voice_rsvp_limit'));
        $this->assertSame(2400, data_get($scalePlan->metadata, 'limits.voice_minutes_limit'));

        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $starterPlan->id,
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => ProductPriceBillingCycle::Monthly->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $starterPlan->id,
            'currency' => 'ILS',
            'amount' => 75,
            'billing_cycle' => ProductPriceBillingCycle::Usage->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $growthPlan->id,
            'currency' => 'ILS',
            'amount' => 29900,
            'billing_cycle' => ProductPriceBillingCycle::Monthly->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $growthPlan->id,
            'currency' => 'ILS',
            'amount' => 69,
            'billing_cycle' => ProductPriceBillingCycle::Usage->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $scalePlan->id,
            'currency' => 'ILS',
            'amount' => 84900,
            'billing_cycle' => ProductPriceBillingCycle::Monthly->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_plan_id' => $scalePlan->id,
            'currency' => 'ILS',
            'amount' => 65,
            'billing_cycle' => ProductPriceBillingCycle::Usage->value,
            'is_active' => true,
        ]);
    }

    public function test_seeded_ai_voice_agent_plans_drive_runtime_limits_instead_of_legacy_product_defaults(): void
    {
        $this->seed(AiVoiceAgentProductSeeder::class);

        $product = Product::query()->where('slug', 'ai-voice-agent')->firstOrFail();
        $plan = $product->productPlans()->where('slug', 'growth')->firstOrFail();
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Kalfa Voice Account',
        ]);

        $subscription = $account->subscribeToPlan($plan);
        $subscription->activate();

        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));
        $this->assertSame(400, Feature::integer($account, 'voice_rsvp_limit'));
        $this->assertSame(800, Feature::integer($account, 'voice_minutes_limit'));
    }
}
