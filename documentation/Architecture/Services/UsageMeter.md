---
date: 2026-03-16
tags: [architecture, service, usage, billing, product-engine]
status: active
---

# UsageMeter

> Related: [[Architecture/Services/UsagePolicyService|UsagePolicyService]] · [[Architecture/Services/SubscriptionService|SubscriptionService]] · [[Architecture/Services/FeatureResolver|FeatureResolver]] · [[Architecture/Services/BillingService|BillingService]]

Records usage events, calculates billing windows, and triggers real-time overage charges when soft-limit plans are exceeded.

---

## Class

`App\Services\UsageMeter` _(final)_

---

## Constructor

```php
public function __construct(private readonly BillingProvider $billingProvider)
```

---

## Core Method: `record()`

```php
public function record(
    Account          $account,
    Product          $product,
    string           $metricKey,
    int              $quantity   = 1,
    ?CarbonInterface $recordedAt = null,
    array            $metadata   = [],
): UsageRecord
```

**Flow:**

```
1. INSERT UsageRecord (account_id, product_id, metric_key, quantity, recorded_at, metadata)
2. Find active subscription for this account + product (latest started_at)
3. If subscription found → billOverageIfRequired()
4. If billing metadata returned → update UsageRecord.metadata with ['billing' => ...]
5. Dispatch ProductEngineEvent('usage.recorded', ...)
```

---

## Billing Window Calculation

### `billingWindow(AccountSubscription $sub, CarbonInterface $asOf): [start, end]`

Computes the current billing period based on the subscription's `started_at` anchor and the plan's billing cycle:

| Billing Cycle | Logic |
|---|---|
| `Monthly` | Roll anchor forward by 1 month until next month > asOf |
| `Yearly` | Roll anchor forward by 1 year until next year > asOf |
| `Usage` | Always `startOfMonth()` → `startOfMonth() + 1 month` |

### `sumForCurrentBillingPeriod(AccountSubscription $sub, string $metricKey): int`

Convenience method: calls `billingWindow(sub, now())` then `sumForPeriod()`.

### `sumForPeriod(Account, metricKey, start, end, ?Product): int`

SQL `SUM(quantity)` on `usage_records` scoped to account, metric, time window, optionally product.

---

## Overage Billing: `billOverageIfRequired()` _(private)_

Only runs when all conditions are met:

| Condition | Source |
|---|---|
| `policyMode === 'soft'` | `plan.metadata.usage_policies.{metricKey}.mode` |
| `overageMetricKey === metricKey` | `plan.metadata.commercial.overage_metric_key` |
| `unitAmountMinor > 0` | `plan.metadata.commercial.overage_amount_minor` |
| `limit !== null` | `FeatureResolver::integer(account, limitFeatureKey)` |

**Overage calculation:**
```
used          = sumForCurrentBillingPeriod (includes current record)
previouslyUsed = max(0, used - quantity)
newOverage    = max(0, used - limit) - max(0, previouslyUsed - limit)
```
Only the **incremental** overage is charged — not cumulative.

**Charge:**
```php
$billingProvider->reportUsage($subscription, $metricKey, $newOverageQuantity, [...]);
```

**Events dispatched:**
- `ProductEngineEvent('usage.overage_charged', ...)` with charge reference, amount, currency

---

## ProductEngine Events

| Event | When |
|---|---|
| `usage.recorded` | Every `record()` call |
| `usage.overage_charged` | When soft-limit overage is billed |

---

## Plan Metadata Shape (for overage)

```json
{
  "usage_policies": {
    "voice_rsvp_calls": { "mode": "soft", "limit_feature_key": "voice_rsvp_limit" }
  },
  "commercial": {
    "overage_metric_key": "voice_rsvp_calls",
    "overage_amount_minor": 100,
    "overage_unit": "call",
    "currency": "ILS"
  }
}
```

---

## Integration Points

- **`FeatureResolver`** — resolves the limit for the metric key
- **`BillingProvider`** — `reportUsage()` calls the payment gateway for overage
- **`UsagePolicyService`** — uses `sumForPeriod()` to evaluate `Allowed` / `Blocked` decisions

See [[Architecture/Services/UsagePolicyService|UsagePolicyService]] for enforcement, [[Architecture/Services/BillingService|BillingService]] for payment gateway abstraction.
