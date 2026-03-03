# MVP-RSVP — Architectural Normalization Execution Report

**Date:** 2025  
**Scope:** Dead infrastructure removal, tenant source-of-truth unification, single architecture verification  
**Stop condition:** System Admin layer NOT started; awaiting approval.

---

## 1. Files Deleted (PHASE 1)

| Category | File(s) |
|----------|--------|
| **Volt stubs** | `resources/views/components/⚡dashboard.blade.php`, `components/organizations/⚡index.blade.php`, `components/organizations/⚡create.blade.php` |
| **Unused Volt auth** | `resources/views/livewire/pages/auth/login.blade.php`, `register.blade.php`, `verify-email.blade.php`, `forgot-password.blade.php`, `confirm-password.blade.php`, `reset-password.blade.php` |
| **LoginForm (only ref was deleted auth)** | `app/Livewire/Forms/LoginForm.php` |
| **Dead navigation** | `resources/views/livewire/layout/navigation.blade.php` |
| **Unused layouts** | `resources/views/layouts/client.blade.php`, `resources/views/layouts/admin.blade.php` |
| **Dead organization flow** | `app/Http/Controllers/Dashboard/OrganizationContextController.php`, `resources/views/dashboard/organizations/index.blade.php`, `resources/views/dashboard/organizations/create.blade.php` |
| **Unused root dashboard** | `resources/views/dashboard.blade.php` |
| **Unused route file** | `routes/workflows.php` |

**Total:** 19 files deleted. No speculative deletions.

---

## 2. Tenant Logic Updated (PHASE 3)

### OrganizationContext (`app/Services/OrganizationContext.php`)

- **current():** Resolves **only** from `auth()->user()->currentOrganization()`. No session read. Returns `null` when not authenticated.
- **set() / setById():** Update **both** DB (`User::current_organization_id`) and session (mirror). Session never overrides DB.
- **clear():** Still clears session only (for mirror consistency).

### EnsureOrganizationSelected (`app/Http/Middleware/EnsureOrganizationSelected.php`)

- Replaced `$user->currentOrganization() ?? $this->context->current()` with **`$user->currentOrganization()`** only. No fallback to session.
- Removed `OrganizationContext` dependency from the middleware.

### Call sites

- **Dashboard (Livewire):** Uses only `auth()->user()->currentOrganization()` in `mount()` and `render()`; removed `OrganizationContext` usage for reading.
- **dynamic-navbar:** Uses only `auth()->user()->currentOrganization()` (desktop and mobile).
- **OrganizationSwitchController:** Unchanged; still updates `current_organization_id` and calls `OrganizationContext::set()` (which now updates DB + session).
- **Organizations\Create:** Unchanged; updates user and calls `OrganizationContext::set()`.
- **Organizations\Index:** When auto-selecting single org, now uses `OrganizationContext::set()` so DB and session stay in sync.
- **DashboardController:** Still uses `OrganizationContext::current()` for `dashboard/events`; `current()` now delegates to `User::currentOrganization()`.

**Single source of truth:** DB (`User::current_organization_id`). Session is an optional mirror only.

---

## 3. route:list — Clean

- `php artisan route:list` runs successfully.
- No references to deleted routes or files. `dashboard`, `organizations.index`, `organizations.create`, `organizations.switch`, `login`, `register`, `dashboard/events`, etc. are present and point to existing handlers.
- `workflows.php` was not loaded in `bootstrap/app.php`; removal does not affect routing.

---

## 4. No Broken References

- No `view()`, `@extends`, or Livewire mounts reference deleted views or controllers.
- `layouts.app` and `layouts.guest` are the only layouts in use for app and auth/errors.
- `dynamic-navbar` is the only navbar included (in `layouts.app`).

**Note:** Some tests still reference deleted Volt components (`pages.auth.login`, `pages.auth.register`, `layout.navigation`, etc.) and `assertSeeVolt`. Those tests will fail until updated or removed; not changed in this phase.

---

## 5. Updated Tenant Flow Diagram

1. **Resolve current organization (read path)**  
   - Always: `auth()->user()->currentOrganization()`.  
   - Implemented in: middleware, Dashboard Livewire, dynamic-navbar, DashboardController (via `OrganizationContext::current()` which delegates to that).

2. **Set current organization (write path)**  
   - Update DB: `$user->update(['current_organization_id' => $id])`.  
   - Mirror (optional): `OrganizationContext::set($organization)` which updates both DB and session.  
   - Used on: switch (OrganizationSwitchController), create org (Organizations\Create), auto-select single org (Organizations\Index).

3. **Middleware**  
   - Skips for `organizations.*`.  
   - 0 orgs → redirect to `organizations.create`.  
   - ≥1 orgs and `$user->currentOrganization() === null` → redirect to `organizations.index`.  
   - Otherwise → allow (e.g. dashboard, dashboard/events).

4. **No redirect loops:** Redirects only to `organizations.create` or `organizations.index` when no current org; those pages do not redirect back without setting an org.

5. **Single source of truth:** DB. Session may mirror; it never overrides.

---

## 6. PHASE 2 — Profile Decision (Pending)

Audit showed:

- `resources/views/profile.blade.php` exists and uses `<x-app-layout>` and Livewire profile components.
- Livewire profile components exist: `livewire/profile/update-profile-information-form.blade.php`, `update-password-form.blade.php`, `delete-user-form.blade.php` (they use `Livewire\Volt\Component`).
- There is **no** `/profile` route in `web.php` or `auth.php`.

**Decision required (no assumption made):**

- **Option A — Keep Profile:** Add a proper `/profile` route (e.g. `view('profile')` or equivalent) using `layouts.app`, and ensure profile is reachable.
- **Option B — Remove Profile Layer:** Delete `profile.blade.php` and the Livewire profile components (and optionally the `Logout` action if only used there).

Agent has **not** applied Option A or B. Awaiting your choice before any profile changes.

---

## 7. Final Architecture Map (Post–Cleanup)

| Route | Handler | View | Layout | Navbar | Livewire |
|-------|---------|------|--------|--------|----------|
| GET /dashboard | Closure | pages.dashboard | layouts.app | dynamic-navbar | Dashboard |
| GET /organizations | Closure | pages.organizations.index | layouts.app | dynamic-navbar | Organizations\Index |
| GET /organizations/create | Closure | pages.organizations.create | layouts.app | dynamic-navbar | Organizations\Create |
| GET /login | Closure | auth.login | layouts.guest | — | — |
| GET /register | Closure | auth.register | layouts.guest | — | — |
| GET /dashboard/events | DashboardController::index | dashboard.index | layouts.app | dynamic-navbar | — |
| GET /dashboard/events/{event} | EventController::show | dashboard.events.show | layouts.app | dynamic-navbar | — |

**Verified:**

- Only **layouts/app.blade.php** is used for authenticated app (dashboard, organizations, dashboard/events).
- Only **layouts.guest** is used for auth and errors.
- Only **dynamic-navbar** is used (included in `layouts.app`).
- Only **class-based** Livewire components are mounted for app flows: `Dashboard`, `Organizations\Index`, `Organizations\Create`.
- No Volt components are mounted by any route (remaining Volt usage is only in profile components, which have no route).
- No duplicate organization flow; only `/organizations`, Livewire Index/Create, and OrganizationSwitchController.
- **Single tenant source:** DB (`User::current_organization_id`). Session is mirror only.

---

**Report complete. System Admin layer not started. Awaiting approval and Profile decision.**
