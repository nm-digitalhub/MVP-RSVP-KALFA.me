<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventBillingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Models\BillingWebhookEvent;
use App\Models\Event;
use App\Models\EventBilling;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Runtime failure simulation for SUMIT production validation.
 * Requires PostgreSQL for test DB (package migrations are not SQLite-compatible).
 * Run with: DB_CONNECTION=pgsql DB_DATABASE=kalfa_rsvp_test php artisan test tests/Feature/SumitProductionValidationTest.php
 */
class SumitProductionValidationTest extends TestCase
{
    use RefreshDatabase;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (config('database.default') === 'sqlite') {
            self::markTestSkipped('SumitProductionValidationTest requires PostgreSQL (set DB_CONNECTION=pgsql and DB_DATABASE for testing).');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlan();
    }

    private function seedPlan(): void
    {
        Plan::firstOrCreate(
            ['slug' => 'per-event-basic'],
            [
                'name' => 'Test Plan',
                'type' => 'per_event',
                'limits' => null,
                'price_cents' => 9900,
                'billing_interval' => null,
            ]
        );
    }

    private function createOrganization(array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'Test Org',
            'slug' => 'test-org-' . uniqid(),
            'billing_email' => null,
        ], $overrides));
    }

    private function createEvent(Organization $org, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'organization_id' => $org->id,
            'name' => 'Test Event',
            'slug' => 'test-event-' . uniqid(),
            'event_date' => null,
            'venue_name' => null,
            'settings' => null,
            'status' => EventStatus::Draft,
        ], $overrides));
    }

    /**
     * 1) Webhook arrives before redirect completes: unknown PaymentID.
     * No Payment with that gateway_transaction_id → no event activation, no partial state.
     */
    public function test_webhook_with_unknown_transaction_id_does_not_activate_event(): void
    {
        $org = $this->createOrganization();
        $event = $this->createEvent($org, ['status' => EventStatus::PendingPayment]);
        $eventBilling = EventBilling::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'plan_id' => Plan::first()->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => EventBillingStatus::Pending,
        ]);
        $payment = $eventBilling->payments()->create([
            'organization_id' => $org->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => PaymentStatus::Pending,
            'gateway' => 'sumit',
            'gateway_transaction_id' => null,
        ]);

        $payload = [
            'PaymentID' => 'unknown-sumit-id-12345',
            'ValidPayment' => true,
            'Status' => 0,
        ];

        $response = $this->postJson('/api/webhooks/sumit', $payload);

        $response->assertStatus(200);
        $event->refresh();
        $payment->refresh();
        $this->assertSame(EventStatus::PendingPayment, $event->status);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertDatabaseCount('billing_webhook_events', 1);
    }

    /**
     * 2) Duplicate webhook: same gateway_transaction_id processed twice.
     * Second request returns 200 "Already processed", no second DB mutation.
     */
    public function test_duplicate_webhook_returns_200_and_does_not_double_process(): void
    {
        $org = $this->createOrganization();
        $event = $this->createEvent($org, ['status' => EventStatus::PendingPayment]);
        $eventBilling = EventBilling::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'plan_id' => Plan::first()->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => EventBillingStatus::Pending,
        ]);
        $payment = $eventBilling->payments()->create([
            'organization_id' => $org->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => PaymentStatus::Pending,
            'gateway' => 'sumit',
            'gateway_transaction_id' => 'sumit-txn-dup',
        ]);

        $payload = [
            'PaymentID' => 'sumit-txn-dup',
            'ValidPayment' => true,
            'Status' => 0,
        ];

        $first = $this->postJson('/api/webhooks/sumit', $payload);
        $first->assertStatus(200);

        $second = $this->postJson('/api/webhooks/sumit', $payload);
        $second->assertStatus(200);
        $second->assertJson(['message' => 'Already processed']);

        $this->assertDatabaseCount('billing_webhook_events', 1);
        $event->refresh();
        $this->assertSame(EventStatus::Active, $event->status);
    }

    /**
     * 3) Invalid signature: when secret is set, wrong signature → 403, no DB write.
     */
    public function test_invalid_webhook_signature_returns_403_and_no_db_write(): void
    {
        Config::set('billing.webhook_secret', 'test-secret');
        $payload = ['PaymentID' => 'any', 'ValidPayment' => true];
        $wrongSignature = 'invalid';

        $response = $this->postJson('/api/webhooks/sumit', $payload, [
            'X-Webhook-Signature' => $wrongSignature,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid signature']);
        $this->assertDatabaseCount('billing_webhook_events', 0);
    }

    /**
     * 4) Gateway timeout during initiateEventPayment: gateway throws → transaction rolls back.
     * Event remains draft, no EventBilling/Payment created.
     */
    public function test_gateway_timeout_rolls_back_transaction_event_stays_draft(): void
    {
        $gateway = new class implements \App\Contracts\PaymentGatewayInterface {
            public function createOneTimePayment(int $organizationId, int $amount, array $metadata = []): array
            {
                throw new \RuntimeException('Gateway timeout');
            }

            public function handleWebhook(array $payload, string $signature): void
            {
            }
        };
        $this->app->instance(\App\Contracts\PaymentGatewayInterface::class, $gateway);

        $user = User::factory()->create();
        $org = $this->createOrganization();
        $org->users()->attach($user->id, ['role' => 'owner']);
        $event = $this->createEvent($org, ['status' => EventStatus::Draft]);
        $plan = Plan::first();

        $response = $this->actingAs($user)->postJson(
            "/api/organizations/{$org->id}/events/{$event->id}/checkout",
            ['plan_id' => $plan->id]
        );

        $response->assertStatus(500);
        $event->refresh();
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertDatabaseMissing('events_billing', ['event_id' => $event->id]);
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * 5) Missing redirect URLs in config: SumitPaymentGateway throws before returning.
     * Transaction in BillingService rolls back → no partial state.
     */
    public function test_missing_redirect_urls_throws_and_rolls_back(): void
    {
        Config::set('billing.default_gateway', 'sumit');
        Config::set('billing.sumit.redirect_success_url', null);
        Config::set('billing.sumit.redirect_cancel_url', null);
        $this->app->bind(\App\Contracts\PaymentGatewayInterface::class, \App\Services\SumitPaymentGateway::class);

        $user = User::factory()->create();
        $org = $this->createOrganization();
        $org->users()->attach($user->id, ['role' => 'owner']);
        $event = $this->createEvent($org, ['status' => EventStatus::Draft]);
        $plan = Plan::first();

        $response = $this->actingAs($user)->postJson(
            "/api/organizations/{$org->id}/events/{$event->id}/checkout",
            ['plan_id' => $plan->id]
        );

        $response->assertStatus(500);
        $event->refresh();
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertDatabaseMissing('events_billing', ['event_id' => $event->id]);
    }
}
