<?php

declare(strict_types=1);

namespace Tests\Feature\System\Products;

use App\Enums\ProductStatus;
use App\Livewire\System\Products\CreateProductWizard;
use App\Livewire\System\Products\EntitlementRow;
use App\Livewire\System\Products\Show;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductEntitlement;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Models\UsageRecord;
use App\Models\User;
use Database\Seeders\TwilioSmsProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateProductWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_open_the_product_wizard_route(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('system.products.create'));

        $response->assertStatus(200);
        $response->assertSeeText(__('Commercial Layer'));
    }

    public function test_system_admin_can_view_the_product_platform_index(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route('system.products.index'));

        $response->assertStatus(200);
        $response->assertSeeText(__('Product Platform'));
        $response->assertSeeText(__('Live Assignments'));
    }

    public function test_system_admin_can_view_the_product_engine_show_sections(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $product->limits()->create([
            'limit_key' => 'voice_minutes_limit',
            'label' => 'Voice Minutes Limit',
            'value' => '100',
            'is_active' => true,
        ]);

        $product->features()->create([
            'feature_key' => 'voice_routing',
            'label' => 'Voice Routing',
            'value' => 'regional',
            'is_enabled' => true,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $plan->prices()->create([
            'currency' => 'USD',
            'amount' => 4900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Kalfa Labs',
        ]);

        $account->grantProduct($product);

        UsageRecord::query()->create([
            'account_id' => $account->id,
            'product_id' => $product->id,
            'metric_key' => 'voice_minutes',
            'quantity' => 12,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('system.products.show', $product));

        $response->assertStatus(200);
        $response->assertSeeText(__('Plans & Pricing'));
        $response->assertSeeText(__('Assignments & Activation'));
        $response->assertSeeText(__('Recent Metered Activity'));
    }

    public function test_system_admin_can_view_commercial_pricing_basis_on_product_show(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'AI Voice Agent RSVP',
            'slug' => 'ai-voice-agent',
            'status' => ProductStatus::Active,
            'metadata' => [
                'commercial_model' => [
                    'pricing_basis' => [
                        'assumed_average_call_minutes' => 2,
                        'estimated_direct_cost_usd_per_minute_total' => 0.0911,
                        'estimated_direct_cost_usd_per_call' => 0.1822,
                        'target_margin_percent' => 18,
                        'estimated_direct_costs_usd_per_minute' => [
                            'twilio_voice_israel_mobile_from_israel' => 0.0646,
                        ],
                    ],
                    'sources' => [
                        'twilio_voice' => 'https://www.twilio.com/en-us/voice/pricing/il',
                    ],
                ],
            ],
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_rsvp_limit' => 400,
                ],
                'commercial' => [
                    'included_unit' => 'voice_rsvp_calls',
                    'included_quantity' => 400,
                    'overage_amount_minor' => 69,
                    'overage_unit' => 'call',
                    'target_margin_percent' => 18,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 29900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('system.products.show', $product));

        $response->assertStatus(200);
        $response->assertSeeText(__('Pricing Basis'));
        $response->assertSeeText(__('Target Margin'));
        $response->assertSeeText(__('Overage Rate'));
        $response->assertSeeText(__('Included Capacity'));
        $response->assertSeeText(__('Unit Economics Snapshot'));
        $response->assertSeeText(__('Revenue / Included Call'));
    }

    public function test_it_creates_a_draft_product_and_publishes_related_records(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CreateProductWizard::class)
            ->set('name', 'Premium Voice Suite')
            ->set('slug', 'premium voice suite')
            ->set('description', 'Draft product for voice automation.')
            ->set('category', 'Communications')
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->set('entitlementFeatureKey', 'voice_minutes')
            ->set('entitlementLabel', 'Voice Minutes')
            ->set('entitlementValue', '1000')
            ->set('entitlementType', 'number')
            ->set('entitlementDescription', 'Minutes included each month.')
            ->call('addEntitlement')
            ->assertHasNoErrors()
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('limitKey', 'concurrent_calls')
            ->set('limitLabel', 'Concurrent Calls')
            ->set('limitValue', '25')
            ->set('limitDescription', 'Maximum simultaneous outbound calls.')
            ->call('addLimit')
            ->assertHasNoErrors()
            ->set('featureKey', 'voice_routing')
            ->set('featureLabel', 'Voice Routing')
            ->set('featureValue', 'regional-failover')
            ->set('featureDescription', 'Use regional routing failover profile.')
            ->call('addFeature')
            ->assertHasNoErrors()
            ->call('nextStep')
            ->assertSet('step', 4);

        $product = Product::query()->firstOrFail();

        $this->assertSame('premium-voice-suite', $product->slug);
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'voice_minutes',
            'label' => 'Voice Minutes',
            'value' => '1000',
        ]);
        $this->assertDatabaseHas('product_limits', [
            'product_id' => $product->id,
            'limit_key' => 'concurrent_calls',
            'value' => '25',
        ]);
        $this->assertDatabaseHas('product_features', [
            'product_id' => $product->id,
            'feature_key' => 'voice_routing',
            'value' => 'regional-failover',
        ]);

        $component
            ->call('publish')
            ->assertRedirect(route('system.products.show', $product));

        $this->assertSame(ProductStatus::Active, $product->fresh()->status);
    }

    public function test_product_show_can_validate_and_save_without_livewire_validation_errors(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Starter plan',
            'category' => 'Bundles',
            'status' => ProductStatus::Draft,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['product' => $product])
            ->set('name', 'Starter Plus')
            ->set('slug', 'starter plus')
            ->set('description', 'Starter plan updated')
            ->set('category', 'Communications')
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->set('newFeatureKey', 'max_events')
            ->set('newLabel', 'Max Events')
            ->set('newValue', '15')
            ->set('newDescription', 'Allowed events for this product.')
            ->call('addEntitlement')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Starter Plus',
            'slug' => 'starter-plus',
            'category' => 'Communications',
        ]);
        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'max_events',
            'label' => 'Max Events',
            'value' => '15',
        ]);
    }

    public function test_product_show_can_manage_limits_features_plans_and_prices(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Managed Product',
            'slug' => 'managed-product',
            'status' => ProductStatus::Draft,
        ]);

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['product' => $product])
            ->call('openAddLimitForm')
            ->set('limitKey', 'voice_minutes_limit')
            ->set('limitLabel', 'Voice Minutes Limit')
            ->set('limitValue', '100')
            ->set('limitDescription', 'Minutes available per billing cycle.')
            ->call('saveLimit')
            ->assertHasNoErrors()
            ->call('openAddFeatureForm')
            ->set('featureKey', 'voice_routing')
            ->set('featureLabel', 'Voice Routing')
            ->set('featureValue', 'regional')
            ->set('featureDescription', 'Regional routing profile.')
            ->call('saveFeature')
            ->assertHasNoErrors()
            ->call('openAddPlanForm')
            ->set('planName', 'Pro')
            ->set('planSlug', 'pro')
            ->set('planDescription', 'Production plan.')
            ->set('planVoiceRsvpLimit', '100')
            ->set('planVoiceMinutesLimit', '200')
            ->set('planIncludedUnit', 'voice_rsvp_calls')
            ->set('planIncludedQuantity', '100')
            ->set('planOverageMetricKey', 'voice_rsvp_calls')
            ->set('planOverageUnit', 'call')
            ->set('planOverageAmountMinor', '79')
            ->set('planTargetMarginPercent', '18')
            ->call('savePlan')
            ->assertHasNoErrors();

        $plan = ProductPlan::query()->where('product_id', $product->id)->firstOrFail();

        $component
            ->call('openAddPriceForm', $plan->id)
            ->set('pricePlanId', $plan->id)
            ->set('priceCurrency', 'usd')
            ->set('priceAmount', '4900')
            ->set('priceBillingCycle', 'monthly')
            ->call('savePrice')
            ->assertHasNoErrors();

        $limitId = $product->limits()->value('id');
        $featureId = $product->features()->value('id');
        $priceId = ProductPrice::query()->where('product_plan_id', $plan->id)->value('id');

        $component
            ->call('startEditLimit', $limitId)
            ->set('limitValue', '250')
            ->call('saveLimit')
            ->assertHasNoErrors()
            ->call('startEditFeature', $featureId)
            ->set('featureValue', 'global')
            ->call('saveFeature')
            ->assertHasNoErrors()
            ->call('startEditPlan', $plan->id)
            ->set('planName', 'Pro Plus')
            ->set('planSlug', 'pro-plus')
            ->set('planVoiceRsvpLimit', '250')
            ->set('planOverageAmountMinor', '69')
            ->call('savePlan')
            ->assertHasNoErrors()
            ->call('startEditPrice', $priceId)
            ->set('priceAmount', '7900')
            ->call('savePrice')
            ->assertHasNoErrors()
            ->call('toggleLimit', $limitId)
            ->call('toggleFeature', $featureId)
            ->call('togglePlan', $plan->id)
            ->call('togglePrice', $priceId);

        $this->assertDatabaseHas('product_limits', [
            'id' => $limitId,
            'value' => '250',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('product_features', [
            'id' => $featureId,
            'value' => 'global',
            'is_enabled' => false,
        ]);
        $this->assertDatabaseHas('product_plans', [
            'id' => $plan->id,
            'name' => 'Pro Plus',
            'slug' => 'pro-plus',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'id' => $priceId,
            'amount' => 7900,
            'currency' => 'USD',
            'is_active' => false,
        ]);
        $this->assertSame(250, data_get($plan->fresh()->metadata, 'limits.voice_rsvp_limit'));
        $this->assertSame(69, data_get($plan->fresh()->metadata, 'commercial.overage_amount_minor'));

        $component
            ->call('deleteLimit', $limitId)
            ->call('deleteFeature', $featureId)
            ->call('deletePrice', $priceId)
            ->call('deletePlan', $plan->id);

        $this->assertDatabaseMissing('product_limits', [
            'id' => $limitId,
        ]);
        $this->assertDatabaseMissing('product_features', [
            'id' => $featureId,
        ]);
        $this->assertDatabaseMissing('product_prices', [
            'id' => $priceId,
        ]);
        $this->assertDatabaseMissing('product_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_product_entitlements_can_be_managed_from_the_visual_interface(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Entitled Product',
            'slug' => 'entitled-product',
            'status' => ProductStatus::Draft,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['product' => $product])
            ->call('openAddEntitlementForm')
            ->set('newFeatureKey', 'voice_rsvp_enabled')
            ->set('newLabel', 'Voice RSVP Enabled')
            ->set('newValue', 'true')
            ->set('newType', 'boolean')
            ->set('newDescription', 'Enables voice RSVP flows.')
            ->call('addEntitlement')
            ->assertHasNoErrors();

        $entitlement = ProductEntitlement::query()->where('product_id', $product->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(EntitlementRow::class, ['entitlement' => $entitlement])
            ->call('startEdit')
            ->set('editLabel', 'Voice RSVP Runtime')
            ->set('editValue', 'false')
            ->set('editDescription', 'Updated from the management interface.')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->call('toggleActive');

        $this->assertDatabaseHas('product_entitlements', [
            'id' => $entitlement->id,
            'label' => 'Voice RSVP Runtime',
            'value' => 'false',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(EntitlementRow::class, ['entitlement' => $entitlement->fresh() ?? $entitlement])
            ->call('delete');

        $this->assertDatabaseMissing('product_entitlements', [
            'id' => $entitlement->id,
        ]);
    }

    public function test_product_can_be_deleted_from_show_component(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'status' => ProductStatus::Draft,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['product' => $product])
            ->call('deleteProduct')
            ->assertRedirect(route('system.products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_twilio_sms_product_is_seeded_with_expected_entitlements(): void
    {
        Product::query()->create([
            'name' => 'SMS-twilio',
            'slug' => 'sms-twilio',
            'status' => ProductStatus::Draft,
        ]);

        $this->seed(TwilioSmsProductSeeder::class);

        $product = Product::query()->where('slug', 'twilio-sms')->firstOrFail();

        $this->assertSame('Twilio SMS', $product->name);
        $this->assertSame(ProductStatus::Active, $product->status);
        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'twilio_enabled',
            'value' => 'true',
        ]);
        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'sms_confirmation_enabled',
            'value' => 'true',
        ]);
        $this->assertDatabaseHas('product_entitlements', [
            'product_id' => $product->id,
            'feature_key' => 'sms_confirmation_limit',
            'value' => '500',
        ]);
    }

    public function test_it_blocks_publishing_products_with_integrity_issues(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Broken Product',
            'slug' => 'broken-product',
            'status' => ProductStatus::Draft,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => 'boolean',
            'is_active' => true,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled Duplicate',
            'value' => 'enabled',
            'type' => 'text',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CreateProductWizard::class)
            ->set('product', $product)
            ->call('publish')
            ->assertHasErrors(['product']);

        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }
}
