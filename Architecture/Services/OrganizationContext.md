---
date: 2026-03-16
tags: [architecture, service, multi-tenant]
status: active
---

# OrganizationContext Service

**File**: `app/Services/OrganizationContext.php`

## Purpose

Manages the active organization for the currently authenticated user in the multi-tenant dashboard. **DB (`users.current_organization_id`) is the single source of truth** — session is only a mirror for compatibility.

## Key Rule

> **Never read the organization from the request directly.** Always call `OrganizationContext::current()`.

---

## Flow

```
User logs in
     │
     ▼
Is current_organization_id set in DB?
     │
 No  ├──► Redirect to /organizations (selection screen)
     │
Yes  └──► EnsureOrganizationSelected middleware passes
          └──► OrganizationContext::current() resolves from DB
```

---

## Methods

| Method | Description |
|--------|-------------|
| `set(Organization)` | Sets active org: writes to DB + mirrors to session. Validates membership. |
| `setById(int)` | Same as `set()` but by ID. Returns bool. |
| `current()` | Reads from `Auth::user()->currentOrganization` (DB). Returns `?Organization`. |
| `clear()` | Removes session mirror only (does not touch DB). |
| `validateMembership(user, org)` | Checks the pivot `organization_users` table for membership. |

---

## Impersonation Override

When a system admin is impersonating (`session()->has('impersonation.original_organization_id')`), `User::currentOrganization` **bypasses the membership check** and returns any organization.

---

## Session Key

```php
OrganizationContext::SESSION_KEY = 'active_organization_id'
```

---

## Middleware Chain

```
auth → verified → EnsureOrganizationSelected → SpatiePermissionTeam → controller
```

- `EnsureOrganizationSelected` — aborts if no current org
- `SpatiePermissionTeam` — scopes Spatie permissions to `current_organization_id`

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Permissions|Permissions System]]
- `app/Http/Middleware/EnsureOrganizationSelected.php`
- `app/Http/Middleware/SpatiePermissionTeam.php`
