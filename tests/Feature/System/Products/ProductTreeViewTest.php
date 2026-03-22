<?php

declare(strict_types=1);

namespace Tests\Feature\System\Products;

use App\Enums\ProductStatus;
use App\Livewire\System\Products\Show;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTreeViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_view_the_responsive_product_tree_workspace(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'AI Voice Agent RSVP',
            'slug' => 'ai-voice-agent-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'is_active' => true,
            'metadata' => [
                'commercial' => [
                    'included_quantity' => 400,
                    'included_unit' => 'voice_rsvp_calls',
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'USD',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $product->limits()->create([
            'limit_key' => 'voice_rsvp_calls',
            'label' => 'Voice RSVP Calls',
            'value' => '400',
            'is_active' => true,
        ]);

        $product->features()->create([
            'feature_key' => 'voice_ai',
            'label' => 'Voice AI',
            'value' => 'enabled',
            'is_enabled' => true,
        ]);

        $product->productEntitlements()->create([
            'feature_key' => 'voice_minutes',
            'label' => 'Voice Minutes',
            'value' => '1200',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('system.products.show', $product));

        $response->assertOk();
        $response->assertSeeText(__('Manage product structure'));
        $response->assertSeeText(__('Show status badges'));
        $response->assertSeeText(__('Plans & Pricing'));
        $response->assertSeeText('Growth');
        $response->assertSeeText(__('Commercial'));
        $response->assertSeeText(__('Constraint'));
        $response->assertSeeText(__('Capability'));
        $response->assertSeeText(__('Grant'));
        $response->assertSeeText(__('Price'));
        $response->assertSeeText(__('Threshold'));
        $response->assertSeeText(__('Entitlements'));
    }

    public function test_plan_edit_event_populates_the_show_component_form(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'AI Voice Agent RSVP',
            'slug' => 'ai-voice-agent-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'sku' => 'growth-monthly',
            'description' => 'Growth plan description',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_rsvp_limit' => 400,
                    'voice_minutes_limit' => 1200,
                ],
                'commercial' => [
                    'included_unit' => 'voice_rsvp_calls',
                    'included_quantity' => 400,
                    'overage_metric_key' => 'voice_minutes',
                    'overage_unit' => 'minutes',
                    'overage_amount_minor' => 25,
                    'target_margin_percent' => 72,
                ],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['product' => $product])
            ->dispatch('tree:open-edit-plan', planId: $plan->id)
            ->assertSet('editingPlanId', $plan->id)
            ->assertSet('showAddPlanForm', true)
            ->assertSet('planName', 'Growth')
            ->assertSet('planSlug', 'growth')
            ->assertSet('planSku', 'growth-monthly')
            ->assertSet('planVoiceRsvpLimit', '400')
            ->assertSet('planIncludedUnit', 'voice_rsvp_calls')
            ->assertSeeText(__('Edit Plan'))
            ->assertSeeText(__('Save Plan'));
    }
}
