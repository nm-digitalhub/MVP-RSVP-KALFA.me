# Kalfa Domain – Billing & Checkout

> Scope: Billing flows, checkout, payment gateways (SUMIT, stub), webhooks, billing-related models & services.

_This document aggregates findings about the billing domain: payment flows, gateway integration, data models, and failure handling._

## Domain overview & invariants (initial)

- Billing is event-centric:
  - Events start in `Draft` status and must be `Draft` to initiate payment.
  - Initiating payment transitions the event to `PendingPayment`.
  - Successful payment transitions the event to `Active`.
- Each event payment flow creates:
  - An `EventBilling` record (per event + plan).
  - One or more `Payment` records attached to the billing (polymorphic `payable`).
- Organization context is preserved through all billing records:
  - `EventBilling` and `Payment` both store `organization_id` alongside the event.

## BillingService – responsibilities (from code)

Located at `app/Services/BillingService.php`.

### initiateEventPayment(Event $event, Plan $plan): array

- Preconditions:
  - `event->status` **must** be `EventStatus::Draft`.
  - Otherwise throws `InvalidArgumentException` (hard guard).
- Transactional behavior (wrapped in `DB::transaction`):
  1. Update event status → `PendingPayment`.
  2. Create `EventBilling` with:
     - `organization_id`, `event_id`, `plan_id`.
     - `amount_cents` from `Plan::price_cents`.
     - `currency` currently hard-coded to `ILS`.
     - `status` = `EventBillingStatus::Pending`.
  3. Create `Payment` via `$eventBilling->payments()->create([...])` with:
     - `organization_id` (mirrored),
     - `amount_cents`, `currency`,
     - `status` = `PaymentStatus::Pending`,
     - `gateway` from `config('billing.default_gateway', 'stub')`.
  4. Delegate to gateway:
     - `$this->gateway->createOneTimePayment(...)` with org id, amount, and metadata (`event_id`, `event_billing_id`, `payment_id`).
  5. Update `Payment` with `gateway_transaction_id` and `gateway_response` from the gateway result.
  6. Return gateway result merged with `event_billing_id` and `payment_id`.

### initiateEventPaymentWithToken(Event $event, Plan $plan, string $token): array

- Same precondition: event must be `Draft`.
- Same DB transaction pattern: update event → create `EventBilling` → create `Payment`.
- Delegates to `$this->gateway->chargeWithToken(...)` with org id, amount, context payload, and token.
- Interprets gateway result as **pre-webhook** indicator:
  - On success:
    - Updates `Payment` with transaction id, minimal `gateway_response`, and sets `status` to `Processing`.
    - Returns `['status' => 'processing', 'payment_id' => ...]`.
  - On failure:
    - Updates `Payment` with failure info and sets `status` to `Failed`.
    - Returns `['status' => 'failed', 'payment_id' => ..., 'message' => ...]`.
- Important invariant: **webhook is the only source of truth** for final success/failure state.

### markPaymentSucceeded(Payment $payment): void

- Wrapped in `DB::transaction` to keep payment + billing + event in sync.
- Behavior:
  1. Update `Payment` → `PaymentStatus::Succeeded`.
  2. Resolve `$payment->payable`:
     - If it's an `EventBilling`:
       - Update billing `status` → `EventBillingStatus::Paid` and set `paid_at = now()`.
       - Update associated event `status` → `EventStatus::Active`.

> TODO: Document `markPaymentFailed` / webhook controllers and how they coordinate with this service.

## Models & enums to document next

- `Event`, `EventBilling`, `Payment`, `Plan`, `Account`, `BillingWebhookEvent`.
- Enums:
  - `EventStatus`, `EventBillingStatus`, `PaymentStatus`.

## Early risks / smells (billing)

- Currency is currently hard-coded to `ILS` in `BillingService`; multi-currency support would require centralizing currency handling.
- Event lifecycle is tightly coupled to billing inside `BillingService` (status transitions happen here); as the domain grows, consider explicit domain events or a dedicated event lifecycle service.
- Gateway response is stored verbatim in `gateway_response`; this is good for audit, but we should ensure no sensitive card data can ever enter this structure (enforced by request validation + gateway contracts).

## Next analysis steps

- Review billing-related models to capture their fields/relationships here.
- Inspect webhook handling (controllers + `BillingWebhookEvent`) to document idempotency and edge cases.
- Cross-check with `config/billing.php` (if present) for gateway configuration and defaults.
