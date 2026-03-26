<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\EventBillingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Models\BillingWebhookEvent;
use App\Models\Event;
use App\Models\EventBilling;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Webhook security tests — transport vs business validation.
 *
 * Validates:
 * - Gateway allowlist enforcement
 * - HMAC signature verification (mandatory in production)
 * - Business-layer trust: payment existence, gateway match, amount match, idempotency
 * - All responses return HTTP 200 (transport layer ACK)
 * - State changes only occur after full validation
 */
class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Gateway Allowlist ─────────────────────────────────────

    public function test_unknown_gateway_returns_200_and_does_not_process(): void
    {
        $response = $this->postJson('/api/webhooks/unknown-gateway', [
            'PaymentID' => '12345',
            'ValidPayment' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'OK']);

        // No webhook event should be logged for unknown gateways
        $this->assertDatabaseCount('billing_webhook_events', 0);
    }

    public function test_arbitrary_gateway_name_does_not_bypass_hmac(): void
    {
        $payment = $this->createPendingPayment('sumit');

        // Attacker tries /api/webhooks/custom to bypass HMAC check
        $response = $this->postJson('/api/webhooks/custom', [
            'PaymentID' => $payment->gateway_transaction_id,
            'ValidPayment' => true,
        ]);

        $response->assertOk();

        // Payment must NOT have changed state
        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
            'Payment state must not change for unrecognized gateway'
        );
    }

    public function test_allowed_gateway_is_accepted(): void
    {
        Config::set('billing.default_gateway', 'stub');

        $response = $this->postJson('/api/webhooks/stub', [
            'PaymentID' => 'non-existent',
        ]);

        $response->assertOk();

        // Webhook event IS logged for allowed gateways (even if payload is invalid)
        $this->assertDatabaseCount('billing_webhook_events', 1);
    }

    // ── HMAC Signature Enforcement ────────────────────────────

    public function test_sumit_webhook_without_secret_in_production_does_not_process(): void
    {
        Config::set('billing.webhook_secret', null);

        $this->app->detectEnvironment(fn () => 'production');

        $payment = $this->createPendingPayment('sumit');

        $response = $this->postJson('/api/webhooks/sumit', [
            'PaymentID' => $payment->gateway_transaction_id,
            'ValidPayment' => true,
        ]);

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
            'Payment must not be marked succeeded without HMAC in production'
        );
    }

    // ── Business Validation: Payment Existence ────────────────

    public function test_webhook_with_nonexistent_transaction_id_does_not_change_state(): void
    {
        Config::set('billing.default_gateway', 'stub');

        $response = $this->postJson('/api/webhooks/stub', [
            'PaymentID' => 'does-not-exist-99999',
            'ValidPayment' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('payments', 0);
    }

    // ── Business Validation: Gateway Mismatch ─────────────────

    public function test_webhook_gateway_mismatch_does_not_change_state(): void
    {
        Config::set('billing.default_gateway', 'stub');

        // Payment was created via 'sumit' but webhook comes through 'stub'
        $payment = $this->createPendingPayment('sumit');

        $response = $this->postJson('/api/webhooks/stub', [
            'PaymentID' => $payment->gateway_transaction_id,
            'ValidPayment' => true,
        ]);

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->fresh()->status,
            'Payment must not change when gateway does not match recorded gateway'
        );
    }

    // ── Business Validation: Idempotency ──────────────────────

    public function test_duplicate_webhook_for_succeeded_payment_is_idempotent(): void
    {
        Config::set('billing.default_gateway', 'stub');

        $payment = $this->createPendingPayment('stub');
        $payment->update(['status' => PaymentStatus::Succeeded]);

        $response = $this->postJson('/api/webhooks/stub', [
            'PaymentID' => $payment->gateway_transaction_id,
            'ValidPayment' => true,
        ]);

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Succeeded,
            $payment->fresh()->status,
        );
    }

    public function test_duplicate_webhook_for_failed_payment_is_idempotent(): void
    {
        Config::set('billing.default_gateway', 'stub');

        $payment = $this->createPendingPayment('stub');
        $payment->update(['status' => PaymentStatus::Failed]);

        $response = $this->postJson('/api/webhooks/stub', [
            'PaymentID' => $payment->gateway_transaction_id,
            'ValidPayment' => false,
        ]);

        $response->assertOk();

        $this->assertSame(
            PaymentStatus::Failed,
            $payment->fresh()->status,
        );
    }

    // ── Transport Layer: Always 200 ───────────────────────────

    public function test_all_webhook_responses_return_200(): void
    {
        // Unknown gateway
        $this->postJson('/api/webhooks/xyz', ['foo' => 'bar'])->assertOk();

        // Known gateway, missing transaction ID
        Config::set('billing.default_gateway', 'stub');
        $this->postJson('/api/webhooks/stub', ['foo' => 'bar'])->assertOk();

        // Known gateway, non-existent transaction
        $this->postJson('/api/webhooks/stub', ['PaymentID' => 'nope'])->assertOk();
    }

    // ── Audit Trail ───────────────────────────────────────────

    public function test_webhook_event_is_logged_with_processed_timestamp(): void
    {
        Config::set('billing.default_gateway', 'stub');

        $this->postJson('/api/webhooks/stub', [
            'PaymentID' => 'audit-test-123',
            'event_type' => 'payment.completed',
        ]);

        $this->assertDatabaseHas('billing_webhook_events', [
            'source' => 'stub',
            'event_type' => 'payment.completed',
        ]);

        $event = BillingWebhookEvent::latest('id')->first();
        $this->assertNotNull($event->processed_at);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function createPendingPayment(string $gateway): Payment
    {
        $organization = Organization::factory()->create();

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::PendingPayment,
        ]);

        $eventBilling = EventBilling::create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => EventBillingStatus::Pending,
        ]);

        return $eventBilling->payments()->create([
            'organization_id' => $organization->id,
            'amount_cents' => 9900,
            'currency' => 'ILS',
            'status' => PaymentStatus::Pending,
            'gateway' => $gateway,
            'gateway_transaction_id' => 'test-txn-'.uniqid(),
        ]);
    }
}
