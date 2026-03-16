---
date: 2026-03-16
tags: [architecture, service, product-engine, entitlements, subscriptions]
status: active
---

# FeatureResolver — Product Engine

**File**: `app/Services/FeatureResolver.php`

## Purpose

Resolves per-account feature flags and limit values by walking a **priority chain** of entitlement sources. Results are cached per account+feature key (default: 5 min TTL).

---

## Resolution Priority Chain

```
1. account_override       ← AccountEntitlement with no product_entitlement_id
        │ (not found?)
        ▼
2. propagated_entitlement ← AccountEntitlement linked to an active AccountProduct
        │ (not found?)
        ▼
3. plan_limit             ← ProductPlan metadata limits (subscription-based)
        │ (not found?)
        ▼
4. product_default        ← ProductEntitlement default on active AccountProduct
        │ (not found?)
        ▼
5. system_default         ← config('product-engine.defaults')
        │ (not found?)
        ▼
   null / $default
```

---

## Key Methods

| Method | Description |
|--------|-------------|
| `has(account, key)` | Does account have this feature from any source? |
| `enabled(account, key)` | Is the feature boolean-true? |
| `value(account, key, default)` | Raw resolved value |
| `integer(account, key, default)` | Resolved value cast to int |
| `usage(account, metricKey)` | Current usage for a metric (from active subscription billing period) |
| `remaining(account, limitKey, metricKey)` | `limit - usage` (null = unlimited) |
| `allowsUsage(account, limitKey, metricKey, qty)` | Can account consume `qty` more units? |
| `clearAccount(account)` | Flush all cached feature values for account |
| `forget(account, key)` | Flush cache for one feature key |

---

## Caching

- Cache key: `feature:{accountId}:{featureKey}`
- TTL: `config('product-engine.feature_cache_ttl', 300)` seconds
- Store: `config('product-engine.cache_store')` (defaults to default cache)
- Uses `Cache::memo()` (in-process memoization layer on top of cache store)

**Cache invalidation** happens automatically on:
- `Account::grantProduct()` — flushes all product's feature keys
- `Account::overrideFeature()` — flushes that feature key
- `FeatureResolver::clearAccount()` — explicit full flush

---

## Product Engine Data Model

```
Product
  └── ProductEntitlement[]  (feature_key, value, type: Boolean|Number|Text)
  └── ProductPlan[]
        └── metadata.limits{}  (key → value)
        └── ProductPrice[]

Account
  └── AccountProduct[]      (status: Active/Inactive, expires_at)
        └── AccountEntitlement[] (propagated from product)
  └── AccountEntitlement[]  (manual overrides, no product_entitlement_id)
  └── AccountSubscription[] (plan → limits)
  └── UsageRecord[]
```

---

## Entitlement Types

`EntitlementType` enum:
- `Boolean` — true/false feature flags
- `Number` — integer/float limits (e.g. max_guests: 500)
- `Text` — free-form string values

---

## Config Reference

**File**: `config/product-engine.php`

```php
'feature_cache_ttl' => env('PRODUCT_ENGINE_FEATURE_CACHE_TTL', 300),
'cache_store'       => env('PRODUCT_ENGINE_CACHE_STORE'),
'defaults'          => [],          // system-level defaults

'usage.default_policy' => env('PRODUCT_ENGINE_USAGE_POLICY', 'hard'),

'operations.trial_expirations.frequency'   => 'everyFiveMinutes',
'operations.integrity_checks.frequency'    => 'hourly',
```

---

## Artisan Commands

| Command | Purpose |
|---------|---------|
| `product-engine:check-integrity` | Validates entitlement/subscription consistency |
| `product-engine:process-trial-expirations` | Expires trials past their `trial_ends_at` |
| `product-engine:health` | Reports ProductEngine operational health |

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/BillingService|BillingService]]
- [[Architecture/Permissions|Permissions System]]
- `app/Services/UsageMeter.php`
- `app/Services/UsagePolicyService.php`
- `app/Services/SubscriptionManager.php`
- `app/Services/SubscriptionService.php`
- `app/Models/Account.php` — `grantProduct()`, `overrideFeature()`, `subscribeToPlan()`
