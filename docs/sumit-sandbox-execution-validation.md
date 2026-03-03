# SUMIT Sandbox Execution Validation Report

**Date:** Runtime validation (no code changes).  
**Domain:** https://kalfa.me  
**Custom webhook:** `POST /api/webhooks/sumit`

---

## 1) Runtime configuration

| Check | Result | Notes |
|-------|--------|------|
| `config('billing.default_gateway') === 'sumit'` | **FAIL** (current env) | Current value: `'stub'`. For SUMIT sandbox/live set `BILLING_GATEWAY=sumit` in `.env`. |
| `config('officeguy.company_id')` not null | **PASS** | Value is set (from `.env`). |
| `config('officeguy.private_key')` not null | **PASS** | Value is set (from `.env`). |
| `config('billing.sumit.redirect_success_url')` not null | **FAIL** (current env) | Currently NULL/empty. Set `BILLING_SUMIT_SUCCESS_URL` when using SUMIT. |
| `config('billing.sumit.redirect_cancel_url')` not null | **FAIL** (current env) | Currently NULL/empty. Set `BILLING_SUMIT_CANCEL_URL` when using SUMIT. |

**Missing for SUMIT mode (when `BILLING_GATEWAY=sumit`):**

- `BILLING_GATEWAY` must be `sumit`.
- `BILLING_SUMIT_SUCCESS_URL` must be set (absolute URL).
- `BILLING_SUMIT_CANCEL_URL` must be set (absolute URL).

**Fallback:** There is no fallback to stub when SUMIT is selected. If `BILLING_GATEWAY=sumit` and required keys are missing, `SumitPaymentGateway::createOneTimePayment()` throws at runtime (redirect URLs), and in **production** `AppServiceProvider::validateSumitConfig()` throws at boot. Code was not modified.

---

## 2) Webhook route availability

| Check | Result | Notes |
|-------|--------|------|
| Route `POST /api/webhooks/sumit` registered | **PASS** | `php artisan route:list --path=api/webhooks` shows `POST api/webhooks/{gateway}` → `WebhookController@handle`. |
| Valid signature → 200 | **Code path verified** | Controller returns 200 after idempotency check, `handleWebhook`, and `processed_at` update. |
| Invalid signature → 403, no DB write | **PASS** | `WebhookService::verifySignature('invalid', payload, secret)` returns `false` (verified in tinker). Controller returns `response()->json(['error' => 'Invalid signature'], 403)` before any `BillingWebhookEvent::create()`. |
| Live `https://kalfa.me/api/webhooks/sumit` | **404** | Curl to production returned 404 with server-level error page (e.g. Plesk `error_docs`). Request does not reach Laravel in the tested environment; likely document root or routing (e.g. API under different path/subdomain). Route exists in application; deployment/routing must send `POST /api/webhooks/sumit` to Laravel. |

Package webhook route (`officeguy/webhook/sumit`) was not used or tested; only the custom endpoint was validated.

---

## 3) End-to-end sandbox flow (logic and tests)

| Step | Result | Notes |
|------|--------|------|
| Create draft event | **N/A** (no run) | Implemented in app; no change. |
| Initiate checkout | **N/A** (no run) | With gateway=sumit and redirect URLs set, `SumitPaymentGateway::createOneTimePayment()` calls package `PaymentService::processCharge(..., redirectMode: true)` and returns `redirect_url`; `BillingService` stores `gateway_transaction_id` from result. |
| redirect_url returned | **By design** | Documented and implemented. |
| gateway_transaction_id stored | **By design** | `BillingService` updates `Payment` with `$result['transaction_id'] ?? null` and `gateway_response`. |
| Webhook (success) → payments/events_billing/events updated | **By design** | `SumitPaymentGateway::handleWebhook()` finds `Payment` by `gateway_transaction_id`, calls `BillingService::markPaymentSucceeded` → payment succeeded, event_billing paid, event active. |
| Duplicate webhook → idempotent, no second mutation | **By design** | `WebhookController` checks `Payment::where('gateway_transaction_id', $transactionId)->whereIn('status', ['succeeded', 'failed'])->exists()` and returns 200 `Already processed` before creating `BillingWebhookEvent`. |

**Automated E2E:** Feature tests in `tests/Feature/SumitProductionValidationTest.php` cover unknown transaction, duplicate webhook, invalid signature (403, no DB write), gateway timeout rollback, and missing redirect URLs. They **skip on SQLite** (require PostgreSQL). To run: set `DB_CONNECTION=pgsql` and `DB_DATABASE` for testing, then `php artisan test tests/Feature/SumitProductionValidationTest.php`. No E2E was run in this validation (no DB execution); logic and code paths were verified by inspection and tinker.

---

## 4) Safety assertions

| Check | Result | Notes |
|-------|--------|------|
| No subscription logic triggered | **PASS** | `BillingService` and `SumitPaymentGateway` contain no subscription/usage logic; grep found no matches. |
| No package DB tables (`officeguy_*`) in our path | **PASS** | No `officeguy_` or `Schema::hasTable` in `app/`. Our flow uses `payments`, `events_billing`, `events`, `billing_webhook_events` only. Package `PaymentService::processCharge(..., redirectMode: true)` does not write to DB in redirect path; package SettingsService falls back to config when table missing. |
| No unintended route collisions | **PASS** | Custom route is `POST api/webhooks/{gateway}`; package route is `POST officeguy/webhook/sumit`. Different URIs; no collision. |
| No silent fallback to stub | **PASS** | `AppServiceProvider::register()` binds `SumitPaymentGateway` only when `config('billing.default_gateway') === 'sumit'`; otherwise `StubPaymentGateway`. No fallback when sumit is selected. In production, `validateSumitConfig()` throws if required SUMIT keys are missing. |

---

## Summary

- **Config (current env):** Gateway is `stub`; redirect URLs are empty. For SUMIT sandbox/live, set `BILLING_GATEWAY=sumit`, `BILLING_SUMIT_SUCCESS_URL`, and `BILLING_SUMIT_CANCEL_URL`.
- **Route:** Registered in app; live curl to `https://kalfa.me/api/webhooks/sumit` returned 404 (server/routing; not Laravel route registration).
- **Webhook logic:** Invalid signature → 403 and no DB write (verified via package `WebhookService` and controller code).
- **E2E:** Implemented and covered by feature tests (run with PostgreSQL); not executed in this run.
- **Safety:** No subscription logic, no use of `officeguy_*` tables in our code, no route collision, no silent fallback to stub.

---

## Verdict

**NOT READY FOR LIVE SANDBOX PAYMENT TEST** until:

1. **Environment:** Set in `.env`: `BILLING_GATEWAY=sumit`, `BILLING_SUMIT_SUCCESS_URL`, `BILLING_SUMIT_CANCEL_URL` (and ensure `OFFICEGUY_COMPANY_ID`, `OFFICEGUY_PRIVATE_KEY` remain set).
2. **Deployment/routing:** Ensure `POST https://kalfa.me/api/webhooks/sumit` reaches the Laravel application (document root / API prefix / reverse proxy). Current 404 indicates the request is not hitting the app.

After (1) and (2), re-run this validation (config check + live webhook curl). Then the system is **READY FOR LIVE SANDBOX PAYMENT TEST**.

No code was modified; no refactor; no package assets published.
