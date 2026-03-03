# SUMIT Integration (Headless / API-only)

## Overview

The app integrates **officeguy/laravel-sumit-gateway** in headless mode: we use only the package’s payment and API services. We do **not** use the package’s UI, Filament panels, public checkout routes, or migrations for our billing flow.

Our flow stays: `PaymentGatewayInterface` → `BillingService` → `WebhookController`. The SUMIT package is used only via a thin adapter.

---

## Package usage (no duplication)

### Classes we use from the package

| Purpose | Package class (namespace) |
|--------|---------------------------|
| Create one-time payment (redirect) | `OfficeGuy\LaravelSumitGateway\Services\PaymentService` |
| Payable contract for charge request | `OfficeGuy\LaravelSumitGateway\Contracts\Payable` |
| PayableType for our adapter | `OfficeGuy\LaravelSumitGateway\Enums\PayableType` |

We call `PaymentService::processCharge($payable, 1, false, true, null, $extra)` with `$extra['RedirectURL']` and `$extra['CancelRedirectURL']` to get a `redirect_url` and optional `transaction_id` from the response.

We do **not** use:

- Package routes (e.g. `officeguy.webhook.sumit`) or Filament
- Package `SumitWebhookController` or `SumitWebhook` model for our webhook handling
- Package `CheckoutController` / `PublicCheckoutController` or any checkout views
- Package migrations for our flow (we use our own `billing_webhook_events` and `payments`)

---

## Our code (allowed new code)

| File | Role |
|------|------|
| `app/Services/SumitPaymentGateway.php` | Implements `PaymentGatewayInterface`; calls `PaymentService::processCharge` and normalizes webhook payload; delegates to `BillingService` for DB updates |
| `app/Services/Sumit/EventBillingPayable.php` | Implements package `Payable`; wraps `EventBilling` (with event + organization) for one-time event payment |
| `config/billing.php` | Adds `sumit.redirect_success_url` and `sumit.redirect_cancel_url` (from env) |
| `app/Providers/AppServiceProvider.php` | Binds `PaymentGatewayInterface` to `SumitPaymentGateway` when `BILLING_GATEWAY=sumit` |

---

## Configuration

### Environment

- **`BILLING_GATEWAY`** — `stub` (default) or `sumit`. When `sumit`, the adapter and redirect URLs are used.
- **`BILLING_SUMIT_SUCCESS_URL`** — Absolute URL where the user is sent after successful payment (e.g. frontend or thank-you page).
- **`BILLING_SUMIT_CANCEL_URL`** — Absolute URL where the user is sent if they cancel.
- **SUMIT credentials** (from existing `config/officeguy.php`): `OFFICEGUY_COMPANY_ID`, `OFFICEGUY_PRIVATE_KEY`, `OFFICEGUY_PUBLIC_KEY`, `OFFICEGUY_ENVIRONMENT`. Do not put secrets in repo or docs.

### Config keys

- `config('billing.default_gateway')` — drives gateway binding (stub vs sumit).
- `config('billing.sumit.redirect_success_url')` and `config('billing.sumit.redirect_cancel_url')` — required when gateway is sumit; read from env.

---

## Checkout flow (one-time)

1. Client calls `POST /api/organizations/{org}/events/{event}/checkout` with `plan_id`.
2. `CheckoutController` → `BillingService::initiateEventPayment` → gateway `createOneTimePayment`.
3. `SumitPaymentGateway::createOneTimePayment` builds `EventBillingPayable`, calls `PaymentService::processCharge(..., redirectMode: true)` with redirect URLs from config, returns `redirect_url` and `transaction_id` (if present in response).
4. `BillingService` stores `gateway_transaction_id` and `gateway_response` on `Payment`, returns `redirect_url`, `payment_id`, etc. to the client.
5. Client redirects the user to `redirect_url` (SUMIT hosted page). After payment, SUMIT redirects the user to the success/cancel URL.

---

## Webhook flow (source of truth)

1. SUMIT sends payment result to **our** endpoint: `POST /api/webhooks/sumit` (we do not use the package’s webhook route).
2. `WebhookController` enforces idempotency (by `gateway_transaction_id`), stores payload in `billing_webhook_events`, then calls the bound gateway’s `handleWebhook`.
3. `SumitPaymentGateway::handleWebhook` normalizes payload (e.g. `PaymentID`, `ValidPayment`, `Status`), finds `Payment` by `gateway_transaction_id`, and calls `BillingService::markPaymentSucceeded` or `markPaymentFailed`. No direct DB writes in the adapter beyond what the architecture requires.
4. `WebhookController` sets `processed_at` on the webhook event after successful handling.

Configure SUMIT to send payment-completion webhooks to `https://your-domain/api/webhooks/sumit`.

---

## What we did not do

- No `vendor:publish` for package views/routes/Filament.
- No subscription or usage-tracking logic.
- No new UI or Blade/Filament pages.
- No changes to business rules (event activation only via payment succeeded, as before).
- No edits to old migrations; no new migrations for this integration.
- Stub gateway remains available when `BILLING_GATEWAY=stub` (default for local/dev).

---

## Summary of package classes used

- **`OfficeGuy\LaravelSumitGateway\Services\PaymentService`** — `processCharge()` for one-time redirect payment; `getCredentials()` and related helpers are used internally by the package.
- **`OfficeGuy\LaravelSumitGateway\Contracts\Payable`** — implemented by our `EventBillingPayable`.
- **`OfficeGuy\LaravelSumitGateway\Enums\PayableType`** — we use `PayableType::GENERIC` in `EventBillingPayable::getPayableType()`.

Config and routing for SUMIT (e.g. `config/officeguy.php`, package routes) remain as provided by the package; we do not register or use the package’s public checkout or webhook routes in our product.

---

## Production validation (audit and tests)

- **Package dependency audit:** [sumit-dependency-audit.md](sumit-dependency-audit.md) — No package DB tables required for redirect-only flow.
- **Webhook signature:** [sumit-webhook-validation.md](sumit-webhook-validation.md) — HMAC-SHA256 validation before any DB mutation; 403 on invalid.
- **Transaction safety:** [sumit-transaction-review.md](sumit-transaction-review.md) — BillingService transactions and HTTP-inside-transaction note.
- **Config integrity:** [sumit-config-validation.md](sumit-config-validation.md) — Production fail-fast when SUMIT is selected but credentials or redirect URLs are missing.
- **Runtime failure tests:** `tests/Feature/SumitProductionValidationTest.php` — Webhook before redirect, duplicate webhook, invalid signature, gateway timeout, missing redirect URLs (require PostgreSQL to run).

---

## Sandbox and production

- **Publish decision:** [sumit-package-publish-decision.md](sumit-package-publish-decision.md) — No package assets need to be published; config-only and our routes suffice.
- **Sandbox runbook:** [sumit-sandbox-runbook.md](sumit-sandbox-runbook.md) — Required env, checkout → redirect → webhook, DB state, curl examples for valid and invalid signature.
- **Production cutover:** [sumit-production-cutover-checklist.md](sumit-production-cutover-checklist.md) — Sandbox → production switch, credential rotation, webhook URL change, rollback to stub. Production never silently falls back to stub when SUMIT is selected; missing required env causes boot-time exception.
