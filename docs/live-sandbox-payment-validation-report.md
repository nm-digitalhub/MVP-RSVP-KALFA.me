# Live Sandbox Payment Execution — Validation Report

**Phase:** LIVE SANDBOX PAYMENT EXECUTION  
**Domain:** https://kalfa.me  
**Date:** Validation run after routing confirmed OK.

---

## Task 1: Confirm Environment — **STOP (FAIL)**

| Check | Result | Value |
|-------|--------|--------|
| `config('billing.default_gateway') === 'sumit'` | **FAIL** | Current: `stub` |
| `config('billing.sumit.redirect_success_url')` not null | **FAIL** | NULL |
| `config('billing.sumit.redirect_cancel_url')` not null | **FAIL** | NULL |

**ENV_OK: NO**

---

## Verdict: **BLOCKED**

**Exact failure:** Production (or target) environment is not configured for SUMIT. The application is still using the **stub** gateway, and SUMIT redirect URLs are not set.

**Required to proceed with Live Sandbox Payment Execution:**

1. **In `.env` (on the server used for the test):**
   - `BILLING_GATEWAY=sumit`
   - `BILLING_SUMIT_SUCCESS_URL=<absolute URL>` (e.g. `https://kalfa.me/checkout/success` or your frontend success page)
   - `BILLING_SUMIT_CANCEL_URL=<absolute URL>` (e.g. `https://kalfa.me/checkout/cancel` or your frontend cancel page)

2. **Optional but recommended for webhook verification:**
   - `BILLING_WEBHOOK_SECRET=<secret>` (must match SUMIT dashboard webhook signature secret)

3. **After changing `.env`:**
   - `php artisan config:clear`
   - Re-run this validation from Task 1.

---

## Tasks 2–6: Not executed

Tasks 2 (Create Draft Event), 3 (Initiate Checkout), 4 (Real Sandbox Payment), 5 (Webhook / DB verification), and 6 (Duplicate webhook test) were **not run** because Task 1 failed. No code was modified; no refactor; no architecture change.

Once the environment is set as above and Task 1 passes, the next steps are:

- **Task 2:** Create organization, draft event, ensure a valid plan exists (e.g. via tinker or API).
- **Task 3:** POST to `/api/organizations/{org}/events/{event}/checkout` with `plan_id`, expect `redirect_url`, `payment_id`, and optionally `gateway_transaction_id`.
- **Task 4:** Open `redirect_url` in browser and complete payment in SUMIT sandbox (manual).
- **Task 5:** After payment, verify in DB: `payments.status = succeeded`, `events_billing.status = paid`, `events.status = active`; one `billing_webhook_events` row with `processed_at` set; no duplicate payment processing.
- **Task 6:** Resend the same webhook payload; expect HTTP 200 with "Already processed" and no second DB mutation.

---

**Summary:** Routing and Laravel handling of `POST /api/webhooks/sumit` are confirmed. Live sandbox payment execution is **BLOCKED** until the environment is configured for SUMIT as above.
