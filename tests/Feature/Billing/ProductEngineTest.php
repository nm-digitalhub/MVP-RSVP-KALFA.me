<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\AccountProductStatus;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Account;
use App\Models\Product;
use App\Support\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_granting_a_product_creates_an_account_assignment_and_propagates_entitlements(): void
    {
        $product = Product::query()->create([
            'name' => 'Voice RSVP Pro',
            'slug' => 'voice-rsvp-pro',
            'status' => ProductStatus::Active,
        ]);

        $feature = $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $limit = $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_limit',
            'label' => 'Voice RSVP Limit',
            'value' => '100',
            'type' => EntitlementType::Number,
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $assignment = $account->grantProduct($product);

        $this->assertDatabaseHas('account_products', [
            'id' => $assignment->id,
            'account_id' => $account->id,
            'product_id' => $product->id,
            'status' => AccountProductStatus::Active->value,
        ]);
        $this->assertDatabaseHas('account_entitlements', [
            'account_id' => $account->id,
            'feature_key' => 'voice_rsvp_enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean->value,
            'product_entitlement_id' => $feature->id,
        ]);
        $this->assertDatabaseHas('account_entitlements', [
            'account_id' => $account->id,
            'feature_key' => 'voice_rsvp_limit',
            'value' => '100',
            'type' => EntitlementType::Number->value,
            'product_entitlement_id' => $limit->id,
        ]);
    }

    public function test_account_override_takes_priority_and_invalidates_cached_values(): void
    {
        $product = Product::query()->create([
            'name' => 'Voice RSVP Pro',
            'slug' => 'voice-rsvp-pro',
            'status' => ProductStatus::Active,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $this->assertFalse(Feature::enabled($account, 'voice_rsvp_enabled'));

        $account->grantProduct($product);

        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));

        $override = $account->overrideFeature('voice_rsvp_enabled', false);

        $this->assertSame(EntitlementType::Boolean, $override->type);
        $this->assertNull($override->product_entitlement_id);
        $this->assertFalse(Feature::enabled($account, 'voice_rsvp_enabled'));
    }

    public function test_feature_resolver_falls_back_to_product_defaults_when_propagated_entitlements_are_missing(): void
    {
        $product = Product::query()->create([
            'name' => 'Twilio SMS',
            'slug' => 'twilio-sms',
            'status' => ProductStatus::Active,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'sms_confirmation_limit',
            'label' => 'SMS Confirmation Limit',
            'value' => '500',
            'type' => EntitlementType::Number,
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $account->grantProduct($product);
        $account->entitlements()->delete();

        $this->assertSame(500, Feature::integer($account, 'sms_confirmation_limit'));
    }

    public function test_expired_override_is_ignored_while_active_propagated_entitlement_is_used(): void
    {
        $product = Product::query()->create([
            'name' => 'Voice RSVP Pro',
            'slug' => 'voice-rsvp-pro',
            'status' => ProductStatus::Active,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $account->grantProduct($product);
        $account->overrideFeature('voice_rsvp_enabled', false, expiresAt: now()->subMinute());

        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));
    }

    public function test_system_default_is_used_when_no_assignment_or_entitlement_exists(): void
    {
        config()->set('product-engine.defaults.voice_rsvp_enabled', true);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));
        $this->assertFalse(Feature::has($account, 'voice_rsvp_enabled'));
    }
}
