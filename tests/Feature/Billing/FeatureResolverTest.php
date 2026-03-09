<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Account;
use App\Models\AccountEntitlement;
use App\Models\Product;
use App\Support\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_catalog_entitlements_through_the_feature_api(): void
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

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_limit',
            'label' => 'Voice RSVP Limit',
            'value' => '250',
            'type' => EntitlementType::Number,
            'is_active' => true,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'routing_profile',
            'label' => 'Routing Profile',
            'value' => 'regional-failover',
            'type' => EntitlementType::Text,
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $account->grantProduct($product);

        $this->assertTrue(Feature::has($account, 'voice_rsvp_enabled'));
        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));
        $this->assertTrue(Feature::allows($account, 'voice_rsvp_enabled'));
        $this->assertSame(250, Feature::integer($account, 'voice_rsvp_limit'));
        $this->assertSame('regional-failover', Feature::value($account, 'routing_profile'));
    }

    public function test_latest_account_level_override_wins_over_catalog_value(): void
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

        $account->overrideFeature('voice_rsvp_enabled', false);

        $this->assertFalse(Feature::enabled($account, 'voice_rsvp_enabled'));
    }

    public function test_it_ignores_expired_entitlements_and_returns_defaults(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        AccountEntitlement::query()->create([
            'account_id' => $account->id,
            'feature_key' => 'sms_confirmation_enabled',
            'value' => 'true',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse(Feature::has($account, 'sms_confirmation_enabled'));
        $this->assertFalse(Feature::enabled($account, 'sms_confirmation_enabled'));
        $this->assertSame(10, Feature::integer($account, 'sms_confirmation_limit', 10));
        $this->assertNull(Feature::value($account, 'sms_confirmation_enabled'));
    }
}
