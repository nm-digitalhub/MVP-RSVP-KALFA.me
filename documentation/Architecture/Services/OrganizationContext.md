---
date: 2026-03-16
tags: [architecture, service, multi-tenant]
status: active
---

# OrganizationContext Service

> Related: [[Architecture/Overview|Overview]] · [[Architecture/Permissions|Permissions]] · [[Architecture/Auth|Auth]]

`App\Services\OrganizationContext`

Manages the active organisation for the currently authenticated user in the multi-tenant dashboard. **DB (`users.current_organization_id`) is the single source of truth** — session is only a mirror for compatibility.

> **Rule:** Never read the organisation from the request directly. Always call `OrganizationContext::current()`.

---

## Session Key

```php
OrganizationContext::SESSION_KEY = 'active_organization_id'
```

---

## Methods

### `set(Organization $organization): void`

Sets the active organisation for the current user:
1. Validate user is authenticated
2. `validateMembership(user, org)` — checks `organization_users` pivot
3. If invalid: `clear()` and return
4. `user->update(['current_organization_id' => $org->id])`
5. `Session::put(SESSION_KEY, $org->id)`

---

### `setById(int $organizationId): bool`

Same as `set()` but accepts an ID. Returns `false` if:
- No authenticated user
- Organization not found
- User is not a member

---

### `current(): ?Organization`

Reads from `Auth::user()->currentOrganization` (Eloquent relation on User model — resolves `current_organization_id`). **Never reads session.** Returns `null` if unauthenticated or no org set.

---

### `clear(): void`

Removes the session mirror key only. Does **not** update `users.current_organization_id` in the DB.

---

### `validateMembership(?Authenticatable $user, Organization $org): bool`

Checks `user->organizations()->where('organizations.id', $org->id)->exists()`. Returns `false` for non-User authenticatables.

---

## Request Flow

```
User logs in
     │
     ▼
users.current_organization_id set?
     │
  No ├──► EnsureOrganizationSelected → redirect /organizations
     │
 Yes └──► SpatiePermissionTeam sets team scope
          └──► Controller resolves org via OrganizationContext::current()
```

---

## Organisation Switching

`OrganizationSwitchController` handles switching:
1. Validates the org belongs to the user's account
2. Calls `OrganizationContext::set($organization)`
3. Redirects to dashboard

Livewire `Organizations\Index` and `Organizations\Create` also call `set()` on creation/selection.

---

## Middleware Chain

```
auth → verified → EnsureOrganizationSelected → SpatiePermissionTeam → controller
```

| Middleware | Role |
|---|---|
| `EnsureOrganizationSelected` | Aborts 403 / redirects if `current()` returns null |
| `SpatiePermissionTeam` | Sets `PermissionRegistrar::setPermissionsTeamId(current()->id)` |

---

## Impersonation Override

When a system admin is impersonating, `User::currentOrganization` bypasses the membership check and returns any organisation (including those the impersonated user belongs to). See [[Architecture/Auth|Auth]] for impersonation details.

---

## Consumer Pattern

Livewire components and controllers inject `OrganizationContext` via the service container:

```php
// Livewire component
public function mount(OrganizationContext $context): void
{
    $this->org = $context->current() ?? abort(403);
}

// Controller
public function __construct(private OrganizationContext $context) {}
public function index(): View
{
    $org = $this->context->current();
    // ...
}
```

---

## Why DB is the Source of Truth

Using `users.current_organization_id` (DB) rather than session alone means:
- Survives session expiry (remembered login)
- Consistent across multiple browser tabs
- Works with queued jobs that impersonate users (session not available in queue)
- Admins can programmatically set org context for a user

Session is kept as a mirror for legacy compatibility only.
