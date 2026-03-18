---
date: 2026-03-16
tags: [architecture, service, subscription, billing]
status: active
---

# SubscriptionManager

> Related: [[Architecture/Services/SubscriptionService|SubscriptionService]] · [[Architecture/Services/BillingService|BillingService]]

Thin orchestration facade over `SubscriptionService`. Provides a stable public API for subscription lifecycle operations, decoupling callers from the implementation details of `SubscriptionService`.

---

## Class

`App\Services\SubscriptionManager` _(final)_

---

## Constructor

```php
public function __construct(private readonly SubscriptionService $subscriptionService)
```

---

## Methods

All methods delegate directly to `SubscriptionService`. See [[Architecture/Services/SubscriptionService|SubscriptionService]] for full documentation of side effects.

### `subscribe(Account, ProductPlan, ?startedAt, array $metadata): AccountSubscription`

Starts a **trial** subscription for an account on the given plan.

Delegates to: `SubscriptionService::startTrial()`

```php
$manager->subscribe($account, $plan);
// → AccountSubscription{status: Trial}
```

---

### `activate(AccountSubscription, ?int $grantedBy): AccountSubscription`

Activates a subscription (Trial → Active, or PastDue → Active on retry).

Delegates to: `SubscriptionService::activate()`

```php
$manager->activate($subscription, grantedBy: auth()->id());
// → AccountSubscription{status: Active}
// → AccountProduct{status: Active}
// → Feature cache busted
// → ProductEngineEvent dispatched
```

---

### `cancel(AccountSubscription): AccountSubscription`

Cancels a subscription.

Delegates to: `SubscriptionService::cancel()`

```php
$manager->cancel($subscription);
// → AccountSubscription{status: Cancelled}
// → AccountEntitlement.expires_at = now()
// → Feature cache busted
```

---

## Why Does This Exist?

`SubscriptionManager` acts as the **application-layer** entry point for subscription changes:
- Controllers and Livewire components depend on `SubscriptionManager`, not on `SubscriptionService`
- Keeps the public API surface minimal (`subscribe`, `activate`, `cancel`)
- Allows future orchestration logic (e.g. pre-activation checks, webhook triggers) to be added here without touching `SubscriptionService`

For the full state machine (Trial → Active → PastDue → Cancelled) and all side effects, see [[Architecture/Services/SubscriptionService|SubscriptionService]].

---

## State Machine (delegated)

```
Trial ──activate()──► Active ──suspend()──► PastDue ──activate()──► Active
  │                     │                                │
  └──cancel()──► Cancelled ◄──cancel()────────────────┘
```
