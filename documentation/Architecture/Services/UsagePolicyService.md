---
date: 2026-03-16
tags: [architecture, service, product-engine, usage, limits, metering]
status: active
---

# UsagePolicyService

**File**: `app/Services/UsagePolicyService.php`

## Purpose

Enforces metered usage limits against subscription entitlements. Given an account, a metric key, and a quantity to consume, returns a `UsagePolicyDecision` indicating whether the operation is allowed, allowed with overage, or blocked.

---

## Decision Flow

```
UsagePolicyService::check(account, metricKey, quantity, subscription?)
        │
        ├── Resolve active subscription (if not passed)
        │
        ├── limitFeatureKey = plan.metadata.usage_policies.{metric}.limit_feature_key
        │                   ?? '{metricKey}_limit'  (default convention)
        │
        ├── limit = FeatureResolver::integer(account, limitFeatureKey)
        │   └── [null → unlimited] → Allowed ✓
        │
        ├── used = FeatureResolver::usage(account, metricKey, subscription)
        │
        ├── projected = used + quantity
        │   └── [projected ≤ limit] → Allowed ✓
        │
        ├── policyMode = plan.metadata.usage_policies.{metric}.mode
        │                ?? config('product-engine.usage.default_policy', 'hard')
        │
        ├── [mode = 'soft'] → AllowedWithOverage (operation proceeds, overage recorded)
        └── [mode = 'hard'] → Blocked ✗
                │
                └── ProductEngineEvent('limits.exceeded', severity: warning|info)
```

---

## Decision Enum

| Decision | Value | Meaning |
|----------|-------|---------|
| `Allowed` | `allowed` | Within limits — proceed |
| `AllowedWithOverage` | `allowed_with_overage` | Over limit, soft policy — proceed, flag for billing |
| `Blocked` | `blocked` | Over limit, hard policy — deny operation |

> **Source:** `App\Enums\UsagePolicyDecision`

---

## Policy Mode

| Mode | Behavior |
|------|---------|
| `hard` | Operations blocked when limit exceeded. Default. |
| `soft` | Operations allowed but excess usage is recorded (may trigger overage billing). |

Configured per metric key in `ProductPlan.metadata.usage_policies`:

```json
{
  "usage_policies": {
    "guests_invited": {
      "limit_feature_key": "max_guests_per_event",
      "mode": "hard"
    },
    "sms_sent": {
      "limit_feature_key": "sms_monthly_limit",
      "mode": "soft"
    }
  }
}
```

Global default: `config('product-engine.usage.default_policy', 'hard')` via `PRODUCT_ENGINE_USAGE_POLICY` env var.

---

## Limit Feature Key Convention

If not explicitly configured in plan metadata, the limit key defaults to `{metricKey}_limit`:

| Metric Key | Default Limit Key |
|-----------|-----------------|
| `guests_invited` | `guests_invited_limit` |
| `events_created` | `events_created_limit` |
| `sms_sent` | `sms_sent_limit` |

---

## Observability

When a limit is exceeded, `ProductEngineEvent` is dispatched regardless of policy mode:

```php
ProductEngineEvent::dispatch('limits.exceeded', account, product, subscription, [
    'metric_key'        => $metricKey,
    'limit_feature_key' => $limitFeatureKey,
    'quantity'          => $quantity,
    'used'              => $used,
    'projected'         => $projected,
    'limit'             => $limit,
    'decision'          => $decision->value,   // 'blocked' | 'allowed_with_overage'
], $decision === Blocked ? 'warning' : 'info');
```

This feeds into `LogProductEngineEvent` listener → structured log → Telescope/Pulse dashboards.

---

## Integration Pattern

```php
$decision = $usagePolicyService->check($account, 'guests_invited', quantity: 50);

match ($decision) {
    UsagePolicyDecision::Allowed => $this->proceedWithGuests(),
    UsagePolicyDecision::AllowedWithOverage => $this->proceedWithOverageFlag(),
    UsagePolicyDecision::Blocked => throw new UsageLimitExceededException(),
};
```

---

## Related

- [[Architecture/Services/FeatureResolver]] — Resolves limit values and usage counters
- [[Architecture/Services/SubscriptionService]] — Subscription state feeds into policy decisions
- [[Architecture/AsyncQueue]] — ProductEngineEvent dispatched on limit exceeded
- [[Architecture/Glossary]] — Entitlement, Limit, Metered, Usage Policy terms
