# SUMIT Sandbox End-to-End Validation Runbook

## Purpose

Runbook for real SUMIT **sandbox** testing: required env, checkout → redirect → payment simulation, webhook handling, DB state, and curl examples.

---

## 1) Required .env values for sandbox

Names only (no secrets):

- **BILLING_GATEWAY** — set to `sumit`.
- **BILLING_SUMIT_SUCCESS_URL** — full URL where the user is sent after successful payment (e.g. your frontend or a thank-you page).
- **BILLING_SUMIT_CANCEL_URL** — full URL where the user is sent after cancel.
- **OFFICEGUY_ENVIRONMENT** — sandbox/test environment value per SUMIT docs (e.g. `dev` or `test` if applicable).
- **OFFICEGUY_COMPANY_ID** — SUMIT CompanyID (מזהה עסק).
- **OFFICEGUY_PRIVATE_KEY** — SUMIT APIKey / מפתחות פרטיים.
- **OFFICEGUY_PUBLIC_KEY** — SUMIT APIPublicKey / מפתחות ציבוריים (optional for redirect-only).
- **BILLING_WEBHOOK_SECRET** — (optional but recommended) shared secret for webhook signature verification; SUMIT or your middleware must send `X-Webhook-Signature: HMAC-SHA256(json_encode(payload), secret)`.

Ensure `config/officeguy.php` exists and reads these from env (no hardcoded credentials).

---

## 2) How to: initiate checkout, receive redirect, simulate sandbox payment

### Initiate checkout (API)

- **Method:** `POST`
- **URL:** `https://your-domain/api/organizations/{organizationId}/events/{eventId}/checkout`
- **Headers:** `Authorization: Bearer {Sanctum token}`, `Accept: application/json`, `Content-Type: application/json`
- **Body:** `{ "plan_id": <plan_id> }`

**Response (success):** JSON with at least:

- `redirect_url` — SUMIT hosted payment page URL.
- `payment_id` — our internal payment ID.
- `event_billing_id` — our internal event billing ID.

**Next step:** Redirect the user (or test client) to `redirect_url`.

### Receive redirect

- User completes or cancels payment on SUMIT’s page.
- SUMIT redirects the browser to:
  - **Success:** `BILLING_SUMIT_SUCCESS_URL` (e.g. with query params per SUMIT).
  - **Cancel:** `BILLING_SUMIT_CANCEL_URL`.

We do **not** activate the event or update payment on redirect; activation is done only when a **webhook** indicates success (see below).

### Simulate sandbox payment

1. Use sandbox credentials in `.env`.
2. Call the checkout API as above and get `redirect_url`.
3. Open `redirect_url` in a browser and complete the sandbox payment flow (use SUMIT sandbox test cards if documented).
4. After payment, SUMIT will send a webhook to your webhook URL (configure in SUMIT dashboard). Locally, use a tunnel (e.g. ngrok) and set the webhook URL to `https://your-tunnel.ngrok.io/api/webhooks/sumit`.
5. Alternatively, **simulate the webhook** with curl (see section 8) using a known `PaymentID` / `gateway_transaction_id` that matches a pending payment in your DB.

---

## 3) Expected webhook payload example (sanitized)

SUMIT may send different payload shapes. Our adapter normalizes using:

- `PaymentID` or `TransactionID` or `ID` → `gateway_transaction_id`
- `ValidPayment` or `Status` or `status` / `Payment.ValidPayment` → success/failure

**Example (sanitized) success payload:**

```json
{
  "PaymentID": "12345678",
  "ValidPayment": true,
  "Status": 0,
  "Amount": 99.00,
  "Currency": 0
}
```

**Example (sanitized) failure payload:**

```json
{
  "PaymentID": "12345678",
  "ValidPayment": false,
  "Status": 1
}
```

Exact field names may vary; our `SumitPaymentGateway::handleWebhook()` and `normalizeWebhookStatus()` handle the variants listed in code.

---

## 4) DB state before payment

- **events:** status = `pending_payment` for the event that entered checkout.
- **events_billing:** one row for that event, status = `pending`, `paid_at` = null.
- **payments:** one row, status = `pending`, `gateway` = `sumit`, `gateway_transaction_id` = value from checkout response if returned by SUMIT, else null.

---

## 5) DB state after successful webhook

- **payments:** status = `succeeded` for the payment matched by `gateway_transaction_id`.
- **events_billing:** status = `paid`, `paid_at` set.
- **events:** status = `active` for the event linked to that event_billing.
- **billing_webhook_events:** one row with the payload, `processed_at` set.

---

## 6) DB state after failed payment (webhook indicates failure)

- **payments:** status = `failed` for the payment matched by `gateway_transaction_id`.
- **events_billing:** unchanged (still `pending`).
- **events:** unchanged (still `pending_payment`).
- **billing_webhook_events:** one row with the payload, `processed_at` set.

---

## 7) Duplicate webhook expected behavior

- **First request:** Processed normally; payment and event state updated; response `200` with `{"message":"OK"}`.
- **Second request (same payload / same gateway_transaction_id):** Idempotency check finds an existing payment with that `gateway_transaction_id` and status `succeeded` or `failed`. Response `200` with `{"message":"Already processed"}`. No second DB mutation; no second row in `billing_webhook_events` for that request (we return before creating the event).

---

## 8) Invalid signature expected behavior

- When `BILLING_WEBHOOK_SECRET` is set and the request has a wrong or missing `X-Webhook-Signature`:
  - Response: **403**.
  - Body: `{"error":"Invalid signature"}`.
  - No DB write: no row in `billing_webhook_events`, no change to payments or events.

---

## 9) Curl examples

**Base URL:** assume `https://your-domain` or `https://your-tunnel.ngrok.io`.

### Valid webhook (success)

Compute signature (example with secret `my-secret`). The signature must be HMAC-SHA256 of the **exact** JSON body (same as `json_encode($payload)` in PHP):

```bash
# Body must match exactly what you send in -d (no extra spaces)
BODY='{"PaymentID":"sumit-txn-123","ValidPayment":true,"Status":0}'
SIG=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "my-secret" | awk '{print $2}')
```

Then:

```bash
curl -s -X POST "https://your-domain/api/webhooks/sumit" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $SIG" \
  -d "$BODY"
```

Expected: `200` and `{"message":"OK"}` if a payment with `gateway_transaction_id` = `sumit-txn-123` exists and was pending.

### Invalid signature webhook

```bash
curl -s -X POST "https://your-domain/api/webhooks/sumit" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: invalid-signature" \
  -d '{"PaymentID":"sumit-txn-123","ValidPayment":true,"Status":0}'
```

Expected: **403** and `{"error":"Invalid signature"}` when `BILLING_WEBHOOK_SECRET` is set. No DB change.

---

## 10) Checklist for sandbox run

1. Set all required `.env` keys (names in section 1); no secrets in repo.
2. Ensure `config/officeguy.php` exists and uses env.
3. Create organization, event (draft), and plan; get Sanctum token.
4. Call `POST .../checkout` with `plan_id`; receive `redirect_url`.
5. Redirect to `redirect_url` and complete sandbox payment (or simulate webhook with curl).
6. Configure SUMIT sandbox to send webhooks to `https://your-domain/api/webhooks/sumit` (or tunnel URL).
7. Verify DB state before payment (section 4), after success (section 5), and after failure (section 6).
8. Send duplicate webhook; expect 200 and "Already processed" (section 7).
9. Send webhook with invalid signature; expect 403 and no DB write (section 8).
