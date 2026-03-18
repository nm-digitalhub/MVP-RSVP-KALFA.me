---
date: 2026-03-16
tags: [architecture, caching, redis, performance, feature-flags]
status: active
---

# Caching Strategy

## Overview

KALFA uses multiple cache layers with different scopes, TTLs, and invalidation strategies. The primary cache store is **Redis** in production (database fallback in tests).

---

## Cache Layers Summary

| Layer | Key Pattern | TTL | Invalidated By |
|-------|-------------|-----|----------------|
| Feature flags | `feature:{accountId}:{featureKey}` | 5 min | `FeatureResolver::forget/forgetMany/clearAccount` |
| Subscription data | Per-org (SystemBillingService) | N/A (bust-on-write) | `SyncOrganizationSubscriptionsJob` |
| Org context | Session key `active_organization_id` | Session lifetime | `OrganizationContext::clear()`, logout |
| Spatie permissions | Internal Spatie cache | Per request | `PermissionRegistrar::forgetCachedPermissions()` |
| Laravel app cache | Config, routes, views | Persistent | `php artisan cache:clear` |

---

## Layer 1: Feature Flag Cache (FeatureResolver)

The most performance-critical cache. Every feature access check goes through this.

### Key format
```
feature:{accountId}:{featureKey}
```

Example: `feature:42:whatsapp_rsvp`, `feature:42:max_guests_per_event`

### Stack: `Cache::memo()` + Redis

```
FeatureResolver::resolve(account, featureKey)
    │
    ├── Cache::memo()   ← in-process memoization (same request, zero latency)
    │   └── hits → return immediately (no Redis round-trip)
    │
    └── [miss] → Cache::store(config('product-engine.cache_store'))
        └── [miss] → priority chain resolution (DB queries)
        └── [found] → cache for TTL seconds → return
```

`Cache::memo()` provides an in-process layer: within a single request, the same feature key is never resolved twice even if called from multiple services.

### TTL

```
config('product-engine.feature_cache_ttl', 300)  ← 5 minutes default
PRODUCT_ENGINE_FEATURE_CACHE_TTL=300              ← override via env
```

### Invalidation

| Trigger | Method | Scope |
|---------|--------|-------|
| Product granted to account | `Account::grantProduct()` | All feature keys for that product |
| Feature override applied | `Account::overrideFeature()` | Single feature key |
| Subscription activated/cancelled/renewed | `SubscriptionService::clearFeatureCache()` | All keys for product's entitlements + plan limits |
| Explicit flush | `FeatureResolver::clearAccount(account)` | All keys for account |
| Explicit single | `FeatureResolver::forget(account, key)` | One feature key |

---

## Layer 2: Subscription Data Cache (SystemBillingService)

`SystemBillingService::syncOrganizationSubscriptions()` fetches subscription state from SUMIT and caches it.

Invalidation is explicit (bust-on-write):

```
SyncOrganizationSubscriptionsJob dispatched
    └── SystemBillingService::syncOrganizationSubscriptions(org)
        └── SystemBillingService::forgetSubscriptionCache(org)
            └── Flushes cached subscription state for this org
```

---

## Layer 3: Organization Context (Session)

Active organization is stored in two places — DB is authoritative, session is a mirror for compatibility:

| Store | Key | Updated By |
|-------|-----|-----------|
| `users.current_organization_id` (DB) | — | `OrganizationContext::set()` |
| Session | `active_organization_id` | `OrganizationContext::set()` (mirrors DB write) |

Session is cleared on:
- Explicit `OrganizationContext::clear()` call
- User logout (`Auth::logout()`)
- Session expiry

---

## Layer 4: Spatie Permission Cache

Spatie caches resolved permissions per-team (org). The team ID is set on every request by `SpatiePermissionTeam` middleware.

```
SpatiePermissionTeam middleware
    └── PermissionRegistrar::setPermissionsTeamId(current_organization_id)
```

Permissions are synced (and cache invalidated) by `PermissionSyncService` when:
- Account product becomes Active with a Succeeded payment
- Subscription activates or is cancelled

---

## Layer 5: Laravel Application Cache

Standard Laravel cache for config, views, routes. Managed separately from domain caches.

```bash
php artisan config:clear    # Config cache
php artisan route:clear     # Route cache
php artisan view:clear      # Blade view cache
php artisan cache:clear     # Application cache (all tags)
php artisan queue:restart   # Restart queue workers (picks up code changes)
```

---

## Cache Architecture Diagram

```
Request
    │
    ├── Feature check → Cache::memo() [in-process, ~0ms]
    │                       └── miss → Redis [~1ms]
    │                                    └── miss → DB priority chain [~5-20ms]
    │
    ├── Org context → Session store [~1ms]
    │                   └── miss → users.current_organization_id (DB)
    │
    └── Permissions → Spatie cache [per-team, per-request setup]
```

---

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `CACHE_DRIVER` | `database` | Global cache store driver |
| `REDIS_HOST` | `127.0.0.1` | Redis connection |
| `PRODUCT_ENGINE_CACHE_STORE` | (default cache) | Cache store for feature flags |
| `PRODUCT_ENGINE_FEATURE_CACHE_TTL` | `300` | Feature flag TTL in seconds |

---

## Cache Warming Strategy

There is no explicit cache warming. Feature flags are resolved lazily on first access and cached automatically. For high-traffic scenarios, consider:
1. Pre-warming on deployment: call `FeatureResolver::value()` for key features on all active accounts
2. Increasing `PRODUCT_ENGINE_FEATURE_CACHE_TTL` (safe — invalidation is event-driven)

---

## Related

- [[Architecture/Services/FeatureResolver]] — Feature flag resolution and cache implementation
- [[Architecture/Services/SubscriptionService]] — Triggers cache invalidation on subscription changes
- [[Architecture/Services/OrganizationContext]] — Session-based org cache
- [[Architecture/Permissions]] — Spatie permission cache
- [[Architecture/Infrastructure]] — Redis setup and cache management commands
- [[Architecture/Diagrams/09-Caching-Architecture]] — Visual cache layer diagram
