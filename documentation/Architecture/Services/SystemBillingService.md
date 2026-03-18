---
date: 2026-03-16
tags: [architecture, service, billing, sumit, officeguy]
status: active
---

# SystemBillingService + DocumentService

> Related: [[Architecture/Services/BillingService|BillingService]] · [[Architecture/Services/SubscriptionService|SubscriptionService]]

Two services handle the **OfficeGuy / SUMIT** legacy billing layer — organisation-level subscription management and document (invoice) generation.

---

## SystemBillingService

`App\Services\OfficeGuy\SystemBillingService`

System-level adapter for OfficeGuy/SUMIT subscriptions. All access to the OfficeGuy SDK goes through this service — controllers and Livewire components must never call the SDK directly.

### Cache

Subscription lookups are cached for **60 seconds** per organisation:

```
Key: org:{organization_id}:subscription
TTL: 60s
```

Call `forgetSubscriptionCache($org)` after any mutation.

---

### Methods

#### `getOrganizationSubscription(Organization $org): ?Subscription`

Returns the active OfficeGuy `Subscription` for the organisation (status = `active`, latest). Cached 60s.

---

#### `cancelSubscription(Organization $org, ?int $actorId): bool`

1. Fetch active subscription (cached)
2. `SubscriptionService::cancel($subscription, 'Cancelled via System Admin')`
3. `forgetSubscriptionCache($org)`
4. Dispatch `SubscriptionCancelledEvent($org, $actorId)`

Returns `false` if no active subscription found or if SDK throws.

---

#### `extendTrial(Organization $org, int $days, ?int $actorId): bool`

1. Fetch active subscription
2. Extend `trial_ends_at` by `$days`
3. Save + `forgetSubscriptionCache`
4. Dispatch `TrialExtendedEvent($org, $days, $actorId)`

---

#### `retryPayment(Organization $org): bool`

Fetches the latest **failed** subscription and retries payment via SDK.

---

#### `applyCredit(Organization $org, int $amount): bool`

Placeholder for manual credit adjustments (local record or SUMIT API). Currently returns `true`.

---

### Events Dispatched

| Event | When |
|---|---|
| `Billing\SubscriptionCancelled` | `cancelSubscription()` succeeds |
| `Billing\TrialExtended` | `extendTrial()` succeeds |

These events are consumed by `SystemAuditLogger` listeners.

---

### Architecture Note

`SystemBillingService` wraps the **OfficeGuy** legacy billing path, which is separate from the **Product Engine** subscription path (`SubscriptionService`). The system has two subscription layers:

| Layer | Class | Scope |
|---|---|---|
| Product Engine | `SubscriptionService` | Account-level entitlements, feature access |
| Legacy Billing | `SystemBillingService` | OfficeGuy/SUMIT payment processing |

See [[Architecture/Services/BillingService|BillingService]] for the payment gateway abstraction.

---

## DocumentService

`App\Services\OfficeGuy\DocumentService`

Host-level wrapper for SUMIT document (invoice/receipt) generation and delivery. Enforces host-specific rules on top of the vendor `DocumentService`.

### Methods

#### `getPdfUrl(int $documentId): ?string`

Returns the PDF download URL for a SUMIT document.

```
VendorDocumentService::getDocumentPDF($documentId)
    → success: returns $result['pdf_url']
    → failure: Log::warning + return null
```

---

#### `createInvoice(EventBilling $eventBilling): ?OfficeGuyDocument`

Creates an order document (invoice) for an event billing record in SUMIT.

**Flow:**
1. Wrap `EventBilling` in `EventBillingPayable` (implements `Payable` interface)
2. `PaymentService::getOrderCustomer($payable)` — fetch/create SUMIT customer
3. `VendorDocumentService::createOrderDocument($payable, $customer)`
4. On error: `Log::error` + return `null`
5. On success: return `OfficeGuyDocument` model (queried by `order_id` + `order_type`)

---

#### `sendByEmail(OfficeGuyDocument $doc, ?string $email): bool`

Sends a document PDF via SUMIT's email delivery API.

```
VendorDocumentService::sendByEmail($document, $email)
    → success: true
    → failure: Log::warning + false
```

If `$email` is `null`, SUMIT uses the customer's email on file.

---

### Payable Interface

`EventBillingPayable` wraps `EventBilling` to implement the `Payable` contract required by the OfficeGuy SDK. Similar wrappers exist for usage charges (`SumitUsageChargePayable`).

---

### Integration Flow

```
EventBilling created
    └─► DocumentService::createInvoice(eventBilling)
            ├─► SUMIT API: create order document
            ├─► OfficeGuyDocument saved to DB
            └─► DocumentService::sendByEmail(document, email)
                    └─► SUMIT API: send PDF by email
```
