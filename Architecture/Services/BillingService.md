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

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/FeatureResolver|Feature Resolver / Product Engine]]
- `app/Http/Controllers/Api/WebhookController.php`
- `app/Http/Controllers/Api/CheckoutController.php`
- `app/Services/Sumit/SumitUsageChargePayable.php`
- `app/Services/Sumit/EventBillingPayable.php`
