---
date: 2026-03-16
tags: [architecture, service, permissions, billing]
status: active
---

# PermissionSyncService

> Related: [[Architecture/Permissions|Permissions]] · [[Architecture/Services/SubscriptionService|SubscriptionService]] · [[Architecture/Services/FeatureResolver|FeatureResolver]]

Synchronises Spatie team-scoped permissions for all Owner/Admin users of every organisation linked to an account, based on billing status.

---

## Class

`App\Services\PermissionSyncService` _(final)_

---

## Responsibility

Grants or revokes a fixed set of **tenant permissions** whenever an account's billing status changes:

```
Account gets active paid/granted product  →  grant permissions to all org admins
Account loses all active products          →  revoke permissions from all org admins
```

---

## Permissions Managed

```php
private const TENANT_PERMISSIONS = [
    'view-event-details',
    'manage-event-guests',
    'manage-event-tables',
    'send-invitations',
];
```

These are Spatie permissions, scoped to the **team ID** = `organization_id`.

---

## API

### `syncForAccount(Account $account): void`

Main entry point. Determines if the account qualifies (`hasActivePaidOrGranted()`), then iterates all linked organisations and syncs each one.

```
syncForAccount($account)
    ├── hasActivePaidOrGranted($account) → bool
    ├── $account->organizations (loaded)
    └── foreach org: syncForOrganization($org, $shouldHaveAccess)
```

---

### `hasActivePaidOrGranted(Account $account): bool`

Returns `true` when:
1. Account has at least one **active** `AccountProduct`, AND
2. Either a **succeeded payment** exists (`payments.status = succeeded`), OR
3. At least one active product has `granted_by IS NOT NULL` (manually granted by system admin)

This allows manual grants without a real payment (e.g. trial or admin override).

---

### `syncForOrganization(Organization $org, bool $grant): void` _(private)_

1. Sets Spatie team scope: `PermissionRegistrar::setPermissionsTeamId($org->id)`
2. Loads `TENANT_PERMISSIONS` from DB
3. Iterates all org users with `role IN (Owner, Admin)`
4. For each user: clears cached permissions (`unsetRelation`), then `givePermissionTo` or `revokePermissionTo`

---

## Trigger Points

| Trigger | Where |
|---|---|
| Subscription activated | `SubscriptionService::activate()` |
| Subscription cancelled | `SubscriptionService::cancel()` |
| Product manually granted | System admin Livewire action |
| Subscription sync job | `SyncOrganizationSubscriptionsJob` |

---

## Role Scoping

Only **Owner** and **Admin** roles receive tenant permissions:

```php
$ownerRoles = [
    OrganizationUserRole::Owner->value,   // 'owner'
    OrganizationUserRole::Admin->value,   // 'admin'
];
```

Member/Editor roles do **not** receive `send-invitations` or other tenant permissions.

---

## Team Scoping Note

Spatie permissions are **team-scoped** — the same permission name can be granted independently for each organisation. Setting `setPermissionsTeamId($org->id)` before any Spatie call is mandatory. See [[Architecture/Permissions|Permissions]] for the full middleware + team setup.

---

## Cache Impact

Calling `user->unsetRelation('permissions')` clears the in-memory relation cache for that user object. However, the Spatie permission cache (Redis) is **not** explicitly cleared here — it is invalidated automatically by Spatie's `HasRoles` trait on `givePermissionTo` / `revokePermissionTo`.
