---
date: 2026-03-16
tags: [architecture, service, billing, payments]
status: active
---

# BillingService

**File**: `app/Services/BillingService.php`

## Purpose

Orchestrates event payment lifecycle — creates billing records, delegates to the payment gateway, and processes webhook outcomes. **Webhooks are the only source of truth for payment success/failure.**

---

## Payment Flow

### Redirect Flow (SUMIT default)

```
User initiates checkout
        │
        ▼
BillingService::initiateEventPayment(event, plan)
        │
        ├── DB transaction:
        │   ├── Event: Draft → PendingPayment
        │   ├── Create EventBilling (Pending)
        │   └── Create Payment (Pending)
        │
        ▼
Gateway::createOneTimePayment()
        │
        ▼
Returns redirect_url → User goes to SUMIT payment page
        │
        ▼
User pays → SUMIT sends webhook to POST /api/webhooks/sumit
        │
        ▼
WebhookController → BillingService::markPaymentSucceeded()
        │
        ├── Payment: Pending → Succeeded
        ├── EventBilling: Pending → Paid
        └── Event: PendingPayment → Active
```

### Token Flow (PaymentsJS)

```
User enters card in PaymentsJS widget (no card data reaches server)
        │
        ▼
BillingService::initiateEventPaymentWithToken(event, plan, token)
        │
        ├── Gateway::chargeWithToken() called with single-use token
        │   ├── Success → Payment: Processing (not Succeeded yet!)
        │   └── Failure → Payment: Failed
        │
        ▼
Webhook arrives → BillingService::markPaymentSucceeded/Failed()
```

> **PCI Note**: `InitiateCheckoutRequest` explicitly forbids card data in the payload. Only single-use tokens accepted.

---

## State Machine

### EventStatus
```
Draft → PendingPayment → Active → Completed
                      └──────────→ Cancelled
```

### PaymentStatus
```
Pending → Processing → Succeeded
        └──────────→ Failed
```

### EventBillingStatus
```
Pending → Paid
        → Failed
```

---

## Gateway Contract

**Interface**: `app/Contracts/PaymentGatewayInterface.php`

| Method | Description |
|--------|-------------|
| `createOneTimePayment()` | Redirect flow — returns `redirect_url` |
| `chargeWithToken()` | Token flow — charges immediately, returns success/fail |
| `handleWebhook()` | Processes async payment gateway webhooks |

**Implementations**:
- `StubPaymentGateway` — always succeeds (local dev)
- `SumitPaymentGateway` — production SUMIT gateway

---

## System-Level Billing (OfficeGuy)

**File**: `app/Services/OfficeGuy/SystemBillingService.php`

Stub service for platform-level subscription management (not event payments). Methods are placeholders pending full OfficeGuy integration.

| Method | Status |
|--------|--------|
| `getOrganizationSubscription()` | Stub |
| `cancelSubscription()` | Stub |
| `extendTrial()` | Stub |
| `applyCredit()` | Stub |
| `retryPayment()` | Stub |
| `getMRR()` | Stub |
| `getChurnRate()` | Stub |

---

## Webhook Endpoint

```
POST /api/webhooks/{gateway}
```

- Throttled: `throttle:webhooks`
- CSRF exempt
- `{gateway}` is used to route to the correct `handleWebhook()` implementation

---

## Webhook Verification & Idempotency

**File**: `app/Http/Controllers/Api/WebhookController.php`

### Signature Verification

When `BILLING_WEBHOOK_SECRET` is configured, every SUMIT webhook is verified before processing:

```
POST /api/webhooks/sumit
    │
    ├─ [if BILLING_WEBHOOK_SECRET set]
    │   └─ WebhookService::verifySignature(X-Webhook-Signature, payload, secret)
    │       └─ HMAC mismatch → 403 Forbidden
    │
    ├─ Idempotency check:
    │   transactionId = payload.PaymentID | payload.id | payload.transaction_id
    │   Payment WHERE gateway_transaction_id = ? AND status IN (succeeded, failed)?
    │   └─ Already processed → 200 OK (no-op)
    │
    ├─ BillingWebhookEvent::create(source, event_type, payload)  ← raw log
    │
    ├─ gatewayInstance->handleWebhook(payload, signature)
    │   └─ Exception → 500 (raw event still stored)
    │
    └─ webhookEvent.update(processed_at: now())  ← mark done
```

### Idempotency Strategy

The system is idempotent at two levels:

| Level | Mechanism |
|-------|-----------|
| **Gateway transaction ID** | Before processing, checks `payments.gateway_transaction_id` for already-succeeded/failed status. Duplicate webhook → `200 OK` immediately. |
| **`BillingWebhookEvent` log** | Every inbound webhook is stored regardless of outcome. `processed_at` is set only after successful processing. Unprocessed events (null `processed_at`) can be replayed. |

### Webhook Failure & Retry

```
Webhook arrives
    │
    ├─ [Exception in handleWebhook()] → 500
    │   ├─ BillingWebhookEvent.processed_at stays NULL
    │   └─ SUMIT will retry (typically 3×, exponential backoff)
    │
    └─ [Success] → 200
        └─ BillingWebhookEvent.processed_at = now()
```

> SUMIT retries failed webhooks (non-2xx responses). The raw `BillingWebhookEvent` record with `processed_at = null` serves as a dead-letter queue for manual inspection.

### Environment Variables

| Variable | Purpose |
|----------|---------|
| `BILLING_WEBHOOK_SECRET` | HMAC secret for SUMIT signature verification (optional but recommended in production) |

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/FeatureResolver|Feature Resolver / Product Engine]]
- `app/Http/Controllers/Api/WebhookController.php`
- `app/Http/Controllers/Api/CheckoutController.php`
- `app/Services/Sumit/SumitUsageChargePayable.php`
- `app/Services/Sumit/EventBillingPayable.php`

---

## Related

- [[Architecture/EventLifecycle]] — Payment and EventBilling status machines
- [[Architecture/AsyncQueue]] — SyncOrganizationSubscriptionsJob queue job
- [[Architecture/Services/FeatureResolver]] — Feature gating based on subscription
- [[Architecture/Glossary]] — Billing term definitions
- [[Architecture/Diagrams/03-Billing-Payment-Flow]] — Visual payment flow
