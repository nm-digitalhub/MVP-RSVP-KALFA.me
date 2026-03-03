# Production Validation Checklist — Embedded Tokenization (Mode C)

Before go-live, validate the following.

---

## 1. Authoritative payment model

- [ ] **Webhook is the only source of truth** for `succeeded` / `failed`. No direct transition from `pending` → `succeeded` in token flow.
- [ ] Token flow: charge success → `payment.status = processing`, return `{ status: "processing", payment_id }`. Only webhook sets `succeeded` and activates event.
- [ ] State machine: `pending` → `processing` → `succeeded` or `failed`. Never `pending` → `succeeded` in token flow.

---

## 2. Idempotency

- [ ] DB: `payments.gateway_transaction_id` has unique constraint.
- [ ] Webhook: locate Payment by `gateway_transaction_id`; if status already terminal (`succeeded` / `failed`) → return 200 "Already processed", no side effects.
- [ ] All webhook state updates run inside a DB transaction.

---

## 3. PCI

- [ ] **DevTools:** Card data (card number, CVV, expiry) is NOT visible in the request body of the checkout API call. Only `plan_id` and `token` are sent.
- [ ] Request validation: if the request contains any card-like keys (e.g. `card_number`, `cvv`, `og-ccnum`), server responds with **400** and does not process.
- [ ] No request payload logging for the checkout route (no token or card data in logs).

---

## 4. Frontend tokenization

- [ ] No fixed delay (e.g. 150 ms). Token is obtained by waiting until `input[name="og-token"]` (or `input[data-og="token"]`) has a non-empty value, with a configurable timeout (e.g. 5 s).
- [ ] On API success with `status: "processing"`, UI shows “Payment received, confirming…” (or redirect to processing page). No “succeeded” until webhook confirms.

---

## 5. Dual mode (Mode C)

- [ ] If `token` present in request → token flow (charge with token, return processing).
- [ ] If `token` absent → redirect flow (legacy) unchanged; redirect endpoints remain operational.

---

## 6. Failure handling

- [ ] If `chargeWithToken` returns `success: false` → `payment.status = failed`, return **422** with normalized error. No automatic retry.

---

## 7. Simulate before go-live

- [ ] **Duplicate webhook:** Send same webhook payload twice; second response is 200 “Already processed”, no second DB mutation.
- [ ] **Slow webhook:** Token flow returns processing; after webhook arrives, payment → succeeded, event → active.
- [ ] **Webhook after already succeeded:** Same as duplicate; idempotent 200, no double activation.
- [ ] **Charge declined:** Token flow returns 422, payment marked failed; no event activation.

---

## 8. Gateway

- [ ] `chargeWithToken` uses `redirectMode = false`, endpoint `/billing/payments/charge/`, no `redirect_url` logic.
- [ ] Return structure: `success`, `transaction_id`, `raw`.

---

**References:** `docs/scope-approval-tokenization.md`, `docs/pci-saq-tokenization.md`
