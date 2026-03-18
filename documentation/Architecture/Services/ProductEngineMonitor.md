---
date: 2026-03-16
tags: [architecture, service, product-engine, monitoring, integrity]
status: active
---

# ProductEngineOperationsMonitor + ProductIntegrityChecker

> Related: [[Architecture/Overview|Overview]] · [[Architecture/Services/SubscriptionService|SubscriptionService]] · [[Architecture/Services/UsagePolicyService|UsagePolicyService]]

Two observability services for the Product Engine: one monitors scheduled task health, the other validates product configuration consistency.

---

## ProductEngineOperationsMonitor

`App\Services\ProductEngineOperationsMonitor` _(final)_

Tracks the liveness of the Laravel scheduler and the Product Engine's scheduled tasks. All state is stored in the **cache** (Redis).

### Tracked Components

| Component | Cache Key | Max Age (default) |
|---|---|---|
| Scheduler heartbeat | `product_engine:operations:scheduler_heartbeat_at` | 120s |
| `trial_expirations` task | `product_engine:task:trial_expirations` | config |
| `integrity_checks` task | `product_engine:task:integrity_checks` | config |

Config path for tasks: `product-engine.operations.{task}` (e.g. `product-engine.operations.trial_expirations`).

---

### Methods

#### `recordSchedulerHeartbeat(?CarbonInterface $at): void`

Records that the scheduler ran. Called by a scheduled command (e.g. every minute). Stores ISO-8601 timestamp in cache with TTL from `stateTtlSeconds()`.

---

#### `recordTaskStarted(string $task, ?CarbonInterface $at): void`

Marks a task as `running` in cache. Called at the start of `ProcessTrialExpirationsCommand` and `ProductEngineHealthCommand`.

---

#### `recordTaskFinished(string $task, bool $successful, ?CarbonInterface $at, ?int $exitCode): void`

Updates task state to `ok` or `failed`. Records:
- `last_finished_at`
- `last_success_at` or `last_failure_at`
- `last_exit_code`

---

#### `schedulerStatus(?CarbonInterface $now): array`

Returns health status of the Laravel scheduler:

```php
[
    'component'     => 'scheduler',
    'status'        => 'healthy' | 'stale',
    'healthy'       => bool,
    'last_seen_at'  => ?CarbonImmutable,
    'age_seconds'   => ?int,
    'max_age_seconds' => int,
    'details'       => string,
]
```

`healthy = true` when heartbeat exists AND `age_seconds <= max_age_seconds`.

---

#### `taskStatuses(?CarbonInterface $now): array`

Returns status array for all tracked tasks (same shape as `schedulerStatus`).

---

#### `taskStatus(string $task, ?CarbonInterface $now): array`

Status for a single task. `max_age_seconds` is loaded from task config.

---

### Health Dashboard

`ProductEngineHealthCommand` (`php artisan product-engine:health`) uses this service to display a health table:
- Scheduler heartbeat age
- `trial_expirations` last run + status
- `integrity_checks` last run + status

---

## ProductIntegrityChecker

`App\Services\ProductIntegrityChecker` _(final)_

Validates that `Product` configuration is internally consistent before publishing or seeding. Used to catch configuration mistakes early.

---

### Checks Performed

For each product, `issuesForProduct(Product $product): string[]` returns a list of issue strings:

| Check | Issue |
|---|---|
| Duplicate entitlement feature keys | `"Duplicate entitlement feature key detected: {key}"` |
| Inconsistent entitlement types for same key | `"Inconsistent entitlement types detected for: {key}"` |
| Active plan with no active prices | `"Active product plan [{slug}] is missing an active price"` |
| Non-numeric plan limit value | `"Plan [{slug}] has a non-numeric limit for [{key}]"` |

---

### Methods

#### `issuesForProduct(Product $product): string[]`

Returns all integrity issues for one product. Returns `[]` if clean.

Loads: `product.entitlements`, `product.productPlans.activePrices`

---

#### `assertProductCanPublish(Product $product): void`

Throws `ValidationException` with all issues if any exist. Used in admin UI before publishing a product.

---

#### `assertProductSeedable(Product $product): void`

Throws `RuntimeException` with concatenated issues. Used in database seeders to prevent seeding broken product configs.

---

#### `reportAll(): void`

Scans all products in the DB and logs warnings for any with issues:

```php
Log::warning('Product integrity issues detected', [
    'product_id' => $product->id,
    'slug'       => $product->slug,
    'issues'     => $issues,
]);
```

Called by `CheckIntegrityCommand` (`php artisan product-engine:check-integrity`).

---

### Usage

```php
// Before publishing in admin UI
$checker->assertProductCanPublish($product);

// In a seeder
$checker->assertProductSeedable($product);

// Scheduled integrity scan
$checker->reportAll();  // logs warnings, does not throw
```
