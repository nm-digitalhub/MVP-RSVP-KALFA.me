<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventBillingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\EventBilling;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

final class BillingService
{
    public function __construct(
        private \App\Contracts\PaymentGatewayInterface $gateway
    ) {}

    /**
     * Initiate payment for an event. Transitions event draft → pending_payment.
     * Creates EventBilling and Payment records; delegates to gateway for redirect/checkout URL.
     * Wrapped in DB transaction to avoid partial state (e.g. payment created but event not updated).
     */
    public function initiateEventPayment(Event $event, Plan $plan): array
    {
        return DB::transaction(function () use ($event, $plan) {
            if ($event->status !== EventStatus::Draft) {
                throw new \InvalidArgumentException('Event must be in draft status to initiate payment.');
            }

            $event->update(['status' => EventStatus::PendingPayment]);

            $eventBilling = EventBilling::create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'plan_id' => $plan->id,
                'amount_cents' => $plan->price_cents,
                'currency' => 'ILS',
                'status' => EventBillingStatus::Pending,
            ]);

            $payment = $eventBilling->payments()->create([
                'organization_id' => $event->organization_id,
                'amount_cents' => $plan->price_cents,
                'currency' => 'ILS',
                'status' => PaymentStatus::Pending,
                'gateway' => config('billing.default_gateway', 'stub'),
            ]);

            $result = $this->gateway->createOneTimePayment(
                $event->organization_id,
                $plan->price_cents,
                [
                    'event_id' => $event->id,
                    'event_billing_id' => $eventBilling->id,
                    'payment_id' => $payment->id,
                ]
            );

            $payment->update([
                'gateway_transaction_id' => $result['transaction_id'] ?? null,
                'gateway_response' => $result,
            ]);

            return array_merge($result, [
                'event_billing_id' => $eventBilling->id,
                'payment_id' => $payment->id,
            ]);
        });
    }

    /**
     * Initiate payment using single-use token (PaymentsJS). No redirect.
     * Webhook is the ONLY source of truth for succeeded/failed.
     * On charge success: set payment to processing, store transaction_id, return processing.
     * Only the webhook may set succeeded, mark billing paid, or activate event.
     */
    public function initiateEventPaymentWithToken(Event $event, Plan $plan, string $token): array
    {
        return DB::transaction(function () use ($event, $plan, $token) {
            if ($event->status !== EventStatus::Draft) {
                throw new \InvalidArgumentException('Event must be in draft status to initiate payment.');
            }

            $event->update(['status' => EventStatus::PendingPayment]);

            $eventBilling = EventBilling::create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'plan_id' => $plan->id,
                'amount_cents' => $plan->price_cents,
                'currency' => 'ILS',
                'status' => EventBillingStatus::Pending,
            ]);

            $payment = $eventBilling->payments()->create([
                'organization_id' => $event->organization_id,
                'amount_cents' => $plan->price_cents,
                'currency' => 'ILS',
                'status' => PaymentStatus::Pending,
                'gateway' => config('billing.default_gateway', 'stub'),
            ]);

            $result = $this->gateway->chargeWithToken(
                $event->organization_id,
                $plan->price_cents,
                [
                    'event_id' => $event->id,
                    'event_billing_id' => $eventBilling->id,
                    'payment_id' => $payment->id,
                ],
                $token
            );

            if ($result['success'] ?? false) {
                $payment->update([
                    'gateway_transaction_id' => $result['transaction_id'] ?? null,
                    'gateway_response' => ['success' => true, 'transaction_id' => $result['transaction_id'] ?? null],
                    'status' => PaymentStatus::Processing,
                ]);

                return [
                    'status' => 'processing',
                    'payment_id' => $payment->id,
                ];
            }

            $payment->update([
                'gateway_transaction_id' => $result['transaction_id'] ?? null,
                'gateway_response' => ['success' => false, 'message' => $result['message'] ?? null],
                'status' => PaymentStatus::Failed,
            ]);

            return [
                'status' => 'failed',
                'payment_id' => $payment->id,
                'message' => $result['message'] ?? 'Payment failed.',
            ];
        });
    }

    /**
     * Mark payment as succeeded. Source of truth: webhook. Transitions event pending_payment → active.
     * Wrapped in DB transaction to avoid partial state (payment succeeded but event not active).
     */
    public function markPaymentSucceeded(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->update(['status' => PaymentStatus::Succeeded]);

            $payable = $payment->payable;
            if ($payable instanceof EventBilling) {
                $payable->update([
                    'status' => EventBillingStatus::Paid,
                    'paid_at' => now(),
                ]);
                $payable->event->update(['status' => EventStatus::Active]);
            }
        });
    }

    /**
     * Mark payment as failed. Event remains pending_payment.
     */
    public function markPaymentFailed(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->update(['status' => PaymentStatus::Failed]);
        });
    }
}
