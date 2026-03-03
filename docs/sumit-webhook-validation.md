# SUMIT Webhook Signature Verification

## Purpose

Ensure incoming SUMIT webhooks are authenticated before any DB mutation. Reject forged or tampered payloads with a clear HTTP response.

---

## A) Does the package provide signature validation?

**Yes.**  
`OfficeGuy\LaravelSumitGateway\Services\WebhookService::verifySignature(string $signature, array $payload, string $secret): bool`  
- Computes `hash_hmac('sha256', json_encode($payload), $secret)` and compares with `hash_equals()` to the provided signature.  
- Returns false if secret or signature is empty or '0'.

---

## B) Are we currently using it?

**Yes (after this phase).**  
- For `POST /api/webhooks/sumit`, when `config('billing.webhook_secret')` is set, we call `WebhookService::verifySignature()` **before** any DB write (before idempotency check and before creating `BillingWebhookEvent`).  
- If verification fails we return **403** and do not persist the payload or update payment state.

---

## C) Mechanism that validates authenticity

- **Header:** `X-Webhook-Signature` (fallback: `Stripe-Signature` for compatibility).  
- **Algorithm:** HMAC-SHA256 over the **parsed JSON body** (same as package):  
  `expected = hash_hmac('sha256', json_encode($payload), secret)`.  
- **Comparison:** Constant-time `hash_equals(expected, signature)` via package.  
- **Secret source:** Env key **`BILLING_WEBHOOK_SECRET`** (exposed in app as `config('billing.webhook_secret')`). Not stored in repo or docs; set in production only.

---

## Enforcement order

1. Parse payload (`$request->all()`).  
2. **If gateway === 'sumit' and webhook_secret is set:** verify signature; on failure → **403**, stop (no DB change).  
3. Idempotency check (read-only).  
4. Create `BillingWebhookEvent`.  
5. Call gateway `handleWebhook()`.  
6. Update `processed_at`.

Signature validation is always **before** any DB mutation; idempotency logic is unchanged.

---

## Failure response behavior

| Condition | HTTP code | Body | DB mutated? |
|-----------|-----------|------|-------------|
| Invalid or missing signature (secret set) | 403 | `{"error":"Invalid signature"}` | No |
| Valid signature or secret not set | 200 (after normal handling) | As per handler | Yes (if processing runs) |

---

## Headers expected

| Header | Use |
|--------|-----|
| `X-Webhook-Signature` | HMAC-SHA256 of `json_encode($payload)` with `BILLING_WEBHOOK_SECRET`. |
| `Stripe-Signature` | Fallback if `X-Webhook-Signature` is missing (same value). |

---

## Hash method

- **HMAC-SHA256.**  
- Input: UTF-8 string `json_encode($payload)` (body as parsed by Laravel).  
- Secret: value of `BILLING_WEBHOOK_SECRET`.

---

## Secret source (env key name only)

**`BILLING_WEBHOOK_SECRET`**
