---
date: 2026-03-16
tags: [architecture, service, subscription, billing, product-engine]
status: active
---

# SubscriptionService & SubscriptionManager

**Files**:
- `app/Services/SubscriptionService.php` — core state machine logic
- `app/Services/SubscriptionManager.php` — thin facade / entry point

## Purpose

Manages the full lifecycle of account subscriptions: trial start, activation, renewal, suspension (past-due), and cancellation. Integrates with `BillingProvider`, `FeatureResolver`, and the Product Engine data model. Every transition dispatches a `ProductEngineEvent` for observability.

---

## Subscription Status Machine

```
          startTrial()
               │
               ▼
            Trial ──[trial_ends_at reached, plan has price]──► activate()
               │                                                    │
               │  [trial_ends_at reached, no price]             Active ──[renew()]──► Active
               └──────────────────► cancel()                       │
                                                         [payment fails]
                                                                    ▼
                                                                PastDue (suspend())
                                                                    │
                                                          [manual cancel / expiry]
                                                                    ▼
                                                                Cancelled
```

| Status | Enum | Description |
|--------|------|-------------|
| `Trial` | `AccountSubscriptionStatus::Trial` | Free trial period, no payment required yet |
| `Active` | `AccountSubscriptionStatus::Active` | Paid and running |
| `PastDue` | `AccountSubscriptionStatus::PastDue` | Payment failed — product suspended |
| `Cancelled` | `AccountSubscriptionStatus::Cancelled` | Terminated, entitlements revoked |

> **Source:** `App\Enums\AccountSubscriptionStatus`

---

## SubscriptionManager (Facade)

`SubscriptionManager` is a thin delegation layer over `SubscriptionService`. Use it as the public API to avoid depending directly on the service:

| Manager Method | Delegates To |
|---------------|--------------|
| `subscribe(account, plan, ...)` | `SubscriptionService::startTrial()` |
| `activate(subscription)` | `SubscriptionService::activate()` |
| `cancel(subscription)` | `SubscriptionService::cancel()` |

---

## Methods

### `startTrial(account, plan, trialEndsAt, startedAt, metadata)`

Creates an `AccountSubscription` in `Trial` status.

```
AccountSubscription::create(status: Trial, trial_ends_at: ?)
    └── ProductEngineEvent::dispatch('subscription.trial_started', ...)
```

---

### `activate(subscription, grantedBy)`

Transitions `Trial → Active`. Involves billing provider and entitlement grant.

```
DB::transaction:
    └── BillingProvider::createCustomer(account)
    └── BillingProvider::createSubscription(account, price)
        ├── [payment OK]  → subscription.status = Active, ends_at = next billing date
        │   └── Account::grantProduct(product, expiresAt: ends_at)
        │       └── AccountEntitlement[] propagated
        │   └── FeatureResolver::forgetMany(account, featureKeys)  ← cache bust
        │   └── ProductEngineEvent('subscription.activated')
        └── [payment FAIL] → subscription.status = PastDue
            └── ProductEngineEvent('subscription.payment_failed', severity: 'warning')
```

---

### `suspend(subscription)`

Transitions `Active → PastDue` on payment failure.

```
DB::transaction:
    └── subscription.status = PastDue
    └── AccountProduct.status = Suspended (preserves record)
    └── FeatureResolver::forgetMany(...)  ← cache bust
    └── ProductEngineEvent('subscription.suspended', severity: 'warning')
```

> Features become unavailable while product is `Suspended`. FeatureResolver won't find active entitlements.

---

### `cancel(subscription)`

Terminates subscription and revokes entitlements.

```
DB::transaction:
    └── BillingProvider::cancelSubscription(subscription)
    └── subscription.status = Cancelled, ends_at = now (if not set)
    └── AccountProduct.status = Revoked, expires_at = now
    └── [no other active subscription for same product?]
    │   └── AccountEntitlement.expires_at = now  ← features gone
    └── FeatureResolver::forgetMany(...)  ← cache bust
    └── ProductEngineEvent('subscription.cancelled')
    └── Billing\SubscriptionCancelled event → AuditBillingEvent listener
```

---

### `renew(subscription)`

Extends an active subscription for the next billing period.

```
DB::transaction:
    └── subscription.status = Active, ends_at = nextEndDate()
    └── AccountProduct.status = Active, expires_at = ends_at
        └── [no existing AccountProduct?] → Account::grantProduct(...)
    └── FeatureResolver::forgetMany(...)  ← cache bust
    └── ProductEngineEvent('subscription.renewed')
```

---

### `processTrialExpirations()`

Called by scheduled `ProcessTrialExpirationsCommand` every **5 minutes**.

```
AccountSubscription WHERE status=Trial AND trial_ends_at <= now()
    │
    ├── [plan has active price] → activate(subscription)
    └── [no price] → cancel(subscription)
```

---

## Cache Invalidation on Transitions

Every state transition calls `clearFeatureCache()`:

```php
featureKeys = product.activeEntitlements.pluck('feature_key')
           + productPlan.metadata.limits.keys()
featureResolver->forgetMany(account, featureKeys)
```

This ensures stale feature flags are never served after a subscription status change.

---

## BillingProvider Contract

| Method | Description |
|--------|-------------|
| `createCustomer(account)` | Registers account with billing provider |
| `createSubscription(account, price)` | Initiates recurring billing. Returns `['failed' => bool, 'error' => string]` |
| `cancelSubscription(subscription)` | Cancels with provider |

---

## Related

- [[Architecture/Services/FeatureResolver]] — Entitlement resolution, cache invalidation
- [[Architecture/Services/BillingService]] — Event-level one-time payments
- [[Architecture/EventLifecycle]] — PaymentStatus state machine
- [[Architecture/AsyncQueue]] — `SyncOrganizationSubscriptionsJob`, `ProductEngineEvent`
- [[Architecture/Glossary]] — AccountSubscription, AccountProduct, Trial definitions
- [[Architecture/Diagrams/08-Subscription-Lifecycle]] — Visual state machine
