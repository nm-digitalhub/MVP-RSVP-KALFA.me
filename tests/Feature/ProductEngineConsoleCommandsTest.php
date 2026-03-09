<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ProductEngine\CheckIntegrityCommand;
use App\Console\Commands\ProductEngine\ProcessTrialExpirationsCommand;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Account;
use App\Models\Product;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEngineConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_trial_expirations_command_transitions_expired_trials(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $billableProduct = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $billablePlan = $billableProduct->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $billablePlan->prices()->create([
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $freeProduct = Product::query()->create([
            'name' => 'Callback Addon',
            'slug' => 'callback-addon',
            'status' => ProductStatus::Active,
        ]);

        $freePlan = $freeProduct->productPlans()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_active' => true,
        ]);

        $activates = $account->startTrial($billablePlan, now()->subMinute());
        $cancels = $account->startTrial($freePlan, now()->subMinute());

        $this->artisan(ProcessTrialExpirationsCommand::class)
            ->expectsOutput('Processed 2 expired trial subscription(s): 1 activated, 1 cancelled.')
            ->assertSuccessful();

        $this->assertSame('active', $activates->fresh()->status->value);
        $this->assertSame('cancelled', $cancels->fresh()->status->value);
    }

    public function test_process_trial_expirations_command_supports_dry_run(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $subscription = $account->startTrial($plan, now()->subMinute());

        $this->artisan(ProcessTrialExpirationsCommand::class, ['--dry-run' => true])
            ->expectsTable(
                ['Subscription', 'Account', 'Product', 'Plan', 'Trial Ended', 'Next Action'],
                [[
                    $subscription->id,
                    $account->id,
                    'Voice RSVP',
                    'Pro',
                    (string) $subscription->trial_ends_at,
                    'activate',
                ]]
            )
            ->expectsOutput('Dry run complete. 1 expired trial subscription(s) would be processed.')
            ->assertSuccessful();

        $this->assertSame('trial', $subscription->fresh()->status->value);
    }

    public function test_check_integrity_command_reports_failures(): void
    {
        $product = Product::query()->create([
            'name' => 'Broken Product',
            'slug' => 'broken-product',
            'status' => ProductStatus::Draft,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Duplicate',
            'value' => 'enabled',
            'type' => EntitlementType::Text,
            'is_active' => true,
        ]);

        $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $this->artisan(CheckIntegrityCommand::class, ['--fail-on-issues' => true])
            ->expectsTable(
                ['Product ID', 'Slug', 'Issue'],
                [
                    [$product->id, 'broken-product', 'Duplicate entitlement feature key detected: voice_rsvp_enabled.'],
                    [$product->id, 'broken-product', 'Inconsistent entitlement types detected for feature key: voice_rsvp_enabled.'],
                    [$product->id, 'broken-product', 'Active product plan [pro] is missing an active price.'],
                ]
            )
            ->expectsOutput('Integrity issues detected: 3 issue(s) across 1 product(s).')
            ->assertExitCode(1);
    }

    public function test_product_engine_commands_are_registered_in_scheduler(): void
    {
        $events = collect(app(Schedule::class)->events());

        $trialExpirationEvent = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'app:process-trial-expirations-command')
        );
        $integrityEvent = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'app:check-integrity-command --fail-on-issues')
        );

        $this->assertNotNull($trialExpirationEvent);
        $this->assertSame('*/5 * * * *', $trialExpirationEvent->getExpression());

        $this->assertNotNull($integrityEvent);
        $this->assertSame('0 * * * *', $integrityEvent->getExpression());
    }
}
