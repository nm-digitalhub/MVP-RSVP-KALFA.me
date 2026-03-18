---
date: 2026-03-16
tags: [architecture, service, audit, security]
status: active
---

# SystemAuditLogger

> Related: [[Architecture/Overview|Overview]] · [[Architecture/Auth|Auth]] · [[Architecture/Permissions|Permissions]]

Provides a static, centralised audit trail for all privileged system-admin and account-management operations.

---

## Class

`App\Services\SystemAuditLogger`

Stateless — all methods are static. Writes to `system_audit_logs` table via `SystemAuditLog` model.

---

## API

```php
SystemAuditLogger::log(
    ?Authenticatable $actor,
    string           $action,
    Model|int|null   $target  = null,
    array            $metadata = [],
): SystemAuditLog
```

| Parameter | Description |
|---|---|
| `$actor` | The authenticated user performing the action (null for system-initiated) |
| `$action` | Dot-namespaced action string (e.g. `account.payment_method_added`) |
| `$target` | Eloquent model → resolves `target_type` + `target_id` via morph. Raw int → sets `target_id` only |
| `$metadata` | Arbitrary key-value context stored as JSON |

Every log entry automatically captures:
- `ip_address` — from `Request::ip()`
- `user_agent` — from `Request::userAgent()`

---

## Database Schema

Table: `system_audit_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment |
| `actor_id` | int nullable | FK → `users.id` |
| `target_type` | string nullable | Morph class name |
| `target_id` | int nullable | Morph target PK |
| `action` | string | Dot-namespaced action |
| `metadata` | json nullable | Arbitrary context |
| `ip_address` | string | Actor IP |
| `user_agent` | string | Actor UA |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Action Catalogue

### Account Actions

| Action | Triggered by | Metadata |
|---|---|---|
| `account.updated` | System admin updates account | changed fields |
| `account.product_granted` | Admin manually grants product | `product_id`, `plan_slug` |
| `account.product_subscription_activated` | Subscription activated | `plan_id`, `granted_by` |
| `account.entitlement_deleted` | Admin removes entitlement | `feature_key` |
| `account.payment_method_added` | Payment method stored | `payment_method_id` |
| `account.payment_method_default_updated` | Default PM changed | `payment_method_id` |
| `account.payment_method_deleted` | PM removed | `payment_method_id` |
| `account.sumit_customer_connected` | SUMIT customer ID linked | `sumit_customer_id` |
| `account.sumit_customer_disconnected` | SUMIT customer ID unlinked | — |

### Organisation Actions

| Action | Triggered by | Metadata |
|---|---|---|
| `organization.activated` | Admin activates org | — |
| `organization.suspended` | Admin suspends org | — |
| `organization.force_deleted` | Admin force-deletes org | `organization_id`, `name` |
| `organization.force_delete_blocked` | Delete blocked by referential integrity | `reason` |
| `organization.reset_data_requested` | Admin requests data reset | — |
| `organization.subscriptions_synced` | Subscription sync job completes | `synced` (count) |

### System Admin Actions

| Action | Triggered by | Metadata |
|---|---|---|
| `system_admin.promoted` | Admin promotes user | `user_id`, `email` |
| `system_admin.demoted` | Admin demotes user | `user_id`, `email` |

### Billing Events (via Listeners)

| Action | Triggered by |
|---|---|
| `subscription.cancelled` | `SubscriptionCancelled` event |
| `trial.extended` | `TrialExtended` event |

---

## Model Relationships

```php
// SystemAuditLog
actor(): BelongsTo → User
target(): MorphTo   → any Model (Account, Organization, User, ...)
```

---

## Usage Pattern

```php
// In a Livewire action or controller:
SystemAuditLogger::log(
    auth()->user(),
    'account.product_granted',
    $account,
    ['product_id' => $product->id, 'plan_slug' => $plan->slug]
);

// System-initiated (no actor):
SystemAuditLogger::log(null, 'organization.subscriptions_synced', $org, ['synced' => 3]);
```

---

## Querying Audit Logs

```php
SystemAuditLog::query()
    ->where('actor_id', $userId)
    ->where('action', 'like', 'account.%')
    ->latest()
    ->get();
```

---

## Notes

- Logging is **synchronous** — no queue. If log fails, the calling action is not rolled back.
- `metadata` is stored as JSON null when empty (not `{}`).
- IP and UA are captured per-request — not available in queued jobs (will capture queue worker IP).
