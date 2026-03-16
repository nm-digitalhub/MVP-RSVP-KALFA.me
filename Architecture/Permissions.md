---
date: 2026-03-16
tags: [architecture, permissions, spatie, roles, authorization]
status: active
---

# Permissions System

## Overview

KALFA uses two complementary authorization systems:

1. **Laravel Policies** — resource-level authorization (can this user act on this resource?)
2. **Spatie Permission (team-scoped)** — feature gates tied to billing status (does this org have access to this feature?)

---

## Organization Roles

Defined in `OrganizationUserRole` enum:

| Role | Level | Notes |
|------|-------|-------|
| `Owner` | Full control | One per org, set at creation |
| `Admin` | Full control | Same as Owner for most operations |
| `Editor` | Edit resources | Can manage events/guests |
| `Viewer` | Read-only | Can only view |

Stored in `organization_users.role` pivot column.

---

## Laravel Policies

| Policy | Governs | Key Rule |
|--------|---------|----------|
| `EventPolicy` | Events | User must belong to event's organization |
| `OrganizationPolicy` | Organizations | Owner/Admin can manage billing; all can view |
| `GuestPolicy` | Guests | Scoped to organization |
| `PaymentPolicy` | Payments | Scoped to organization |

All policies check:
```php
$user->organizations()->where('organizations.id', $resource->organization_id)->exists()
```

`OrganizationPolicy::isOwnerOrAdmin()` checks pivot role against `OrganizationUserRole::Owner` and `Admin`.

---

## Spatie Permissions (Team-Scoped)

### Team Context

`SpatiePermissionTeam` middleware sets the team ID to the current organization:

```php
app(PermissionRegistrar::class)->setPermissionsTeamId($user->current_organization_id);
```

This means permissions are scoped per organization — a user can have `view-event-details` in Org A but not Org B.

### Tenant Permissions

`PermissionSyncService::TENANT_PERMISSIONS`:

| Permission | Description |
|-----------|-------------|
| `view-event-details` | Access event details page |
| `manage-event-guests` | Add/edit/delete guests |
| `manage-event-tables` | Configure seating tables |
| `send-invitations` | Send WhatsApp/SMS invitations |

These are granted to **Owner** and **Admin** roles only.

### Sync Trigger

Permissions are synced when:
1. An `AccountProduct` becomes `Active` **AND**
   - A `Payment` with status `Succeeded` exists, **OR**
   - Product was manually granted by system admin (`granted_by` IS NOT NULL)

Revoked when: Account has no more active products satisfying the above.

**Sync entry point**: `PermissionSyncService::syncForAccount(account)`

### Grant/Revoke Flow

```
AccountProduct activated (billing event or manual grant)
        │
        ▼
PermissionSyncService::syncForAccount($account)
        │
        ├── hasActivePaidOrGranted()? → YES
        │       └── givePermissionTo([...TENANT_PERMISSIONS]) for Owner+Admin users
        └── hasActivePaidOrGranted()? → NO
                └── revokePermissionTo([...TENANT_PERMISSIONS]) for Owner+Admin users
```

---

## System Admin

- `users.is_system_admin = true` grants access to `/system/*` routes
- `EnsureSystemAdmin` middleware enforces this
- `RequireImpersonationForSystemAdmin` middleware: system admins can only view tenant data while impersonating

---

## Impersonation

| Session Key | Purpose |
|-------------|---------|
| `impersonation.original_admin_id` | Admin user ID before impersonation |
| `impersonation.original_organization_id` | Original org context |
| `impersonation.started_at` | Timestamp — `ImpersonationExpiry` checks this |

Impersonation expires after **60 minutes** via `ImpersonationExpiry` middleware.

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/OrganizationContext|OrganizationContext Service]]
- [[Architecture/Services/FeatureResolver|Feature Resolver / Product Engine]]
- `app/Services/PermissionSyncService.php`
- `app/Http/Middleware/SpatiePermissionTeam.php`
- `app/Http/Middleware/EnsureSystemAdmin.php`
- `app/Http/Middleware/ImpersonationExpiry.php`
