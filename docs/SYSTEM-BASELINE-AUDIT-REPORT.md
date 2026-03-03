# MVP-RSVP — System Baseline Alignment Audit Report (PART 1)

**Date:** 2025  
**Scope:** Full structure discovery, runtime verification, drift detection, tenant state analysis  
**Rule:** READ-ONLY. No code changes, no file deletions, no refactors.

---

## 1. Architecture Map (Route → View → Layout → Navbar → Component)

| Route | Handler | Returned View | Layout | Navbar | Livewire Component | Component View |
|-------|---------|---------------|--------|--------|--------------------|----------------|
| **GET /dashboard** | Closure | `pages.dashboard` | `layouts.app` | `x-dynamic-navbar` | `App\Livewire\Dashboard` | `livewire/dashboard.blade.php` |
| **GET /organizations** | Closure | `pages.organizations.index` | `layouts.app` | `x-dynamic-navbar` | `App\Livewire\Organizations\Index` | `livewire/organizations/index.blade.php` |
| **GET /organizations/create** | Closure | `pages.organizations.create` | `layouts.app` | `x-dynamic-navbar` | `App\Livewire\Organizations\Create` | `livewire/organizations/create.blade.php` |
| **GET /login** | Closure | `auth.login` | `layouts.guest` | None | None | — |
| **GET /register** | Closure | `auth.register` | `layouts.guest` | None | None | — |
| **GET /dashboard/events** | `DashboardController::index` | `dashboard.index` | `layouts.app` | `x-dynamic-navbar` | None | — |
| **GET /dashboard/events/{event}** | `EventController::show` | `dashboard.events.show` | `layouts.app` | `x-dynamic-navbar` | None | — |

**Confirmed at runtime:**
- **Layout rendered for dashboard/organizations:** `resources/views/layouts/app.blade.php`
- **Navbar rendered:** `resources/views/components/dynamic-navbar.blade.php` (included only in `layouts.app`)
- **Livewire components executed:** Class-based only (`Dashboard`, `Organizations\Index`, `Organizations\Create`). No Volt components are mounted by any of the above routes.
- **Legacy Volt:** Volt components exist in `resources/views/components/` (⚡dashboard, ⚡index, ⚡create) and `resources/views/livewire/pages/auth/*` (Volt + LoginForm) but **are not referenced by any route**. `/login` and `/register` serve Blade views `auth.login` and `auth.register`, not Livewire.

---

## 2. File Status Table

### Active (referenced and used at runtime)

| File | Role |
|------|------|
| **Layouts** | |
| `resources/views/layouts/app.blade.php` | Main app layout; includes `dynamic-navbar`, `@yield('header')`, `@yield('containerWidth')`, `@yield('content')` |
| `resources/views/layouts/guest.blade.php` | Auth/guest layout; no navbar; used by auth/* and errors/* |
| **Pages (wrappers)** | |
| `resources/views/pages/dashboard.blade.php` | Wrapper for `/dashboard`; extends app, yields header + content, mounts `<livewire:dashboard />` |
| `resources/views/pages/organizations/index.blade.php` | Wrapper for `/organizations`; mounts `<livewire:organizations.index />` |
| `resources/views/pages/organizations/create.blade.php` | Wrapper for `/organizations/create`; mounts `<livewire:organizations.create />` |
| **Auth (Blade)** | |
| `resources/views/auth/login.blade.php` | Served by route `login` |
| `resources/views/auth/register.blade.php` | Served by route `register` |
| `resources/views/auth/forgot-password.blade.php` | password.request |
| `resources/views/auth/reset-password.blade.php` | password.reset |
| `resources/views/auth/verify-email.blade.php` | verification.notice |
| `resources/views/auth/confirm-password.blade.php` | password.confirm |
| **Dashboard (controller-driven)** | |
| `resources/views/dashboard/index.blade.php` | Returned by `DashboardController::index` for route `dashboard/events` |
| `resources/views/dashboard/events/show.blade.php` | Returned by `EventController::show` |
| **Components** | |
| `resources/views/components/dynamic-navbar.blade.php` | Only navbar in use (in `layouts.app`) |
| `resources/views/components/page-header.blade.php` | Used by dashboard + organizations index pages |
| **Livewire (class-based)** | |
| `app/Livewire/Dashboard.php` | Mounted on `/dashboard` |
| `app/Livewire/Organizations/Index.php` | Mounted on `/organizations` |
| `app/Livewire/Organizations/Create.php` | Mounted on `/organizations/create` |
| `resources/views/livewire/dashboard.blade.php` | View for Dashboard component |
| `resources/views/livewire/organizations/index.blade.php` | View for Organizations\Index |
| `resources/views/livewire/organizations/create.blade.php` | View for Organizations\Create |
| **Controllers** | |
| `app/Http/Controllers/OrganizationSwitchController.php` | organizations.switch |
| `app/Http/Controllers/Dashboard/DashboardController.php` | dashboard.events.index |
| `app/Http/Controllers/Dashboard/EventController.php` | dashboard.events.show |
| **Middleware / Services** | |
| `app/Http/Middleware/EnsureOrganizationSelected.php` | Applied to dashboard/events/* |
| `app/Services/OrganizationContext.php` | Session-based org context; used by middleware and switch flow |
| **Models** | `User` (currentOrganization, current_organization_id), `Organization`, etc. |

### Redundant (replaced by current design; no route points to them)

| File | Reason |
|------|--------|
| `app/Http/Controllers/Dashboard/OrganizationContextController.php` | No route in `web.php` for `dashboard.organizations.*`; app uses `organizations.index`, `organizations.create`, `OrganizationSwitchController` |
| `resources/views/dashboard/organizations/index.blade.php` | Only referenced by OrganizationContextController (dead) |
| `resources/views/dashboard/organizations/create.blade.php` | Only referenced by OrganizationContextController (dead) |
| `resources/views/dashboard.blade.php` (root) | Uses `<x-app-layout>`; no route returns `view('dashboard')` |
| `resources/views/profile.blade.php` | Uses `<x-app-layout>` and Livewire profile components; **no route named `profile`** in `web.php` or `auth.php` |

### Conflicting / Duplicate (multiple systems for same concern)

| File / Pattern | Conflict |
|---------------|----------|
| **Navigation** | `resources/views/components/dynamic-navbar.blade.php` = **active**. `resources/views/livewire/layout/navigation.blade.php` = **Volt nav** with `wire:navigate`, routes to `appointments.index`, `orders.index`, `profile`; **not included in any layout**; legacy. |
| **Layouts** | **app** = single main app layout (with header + containerWidth). **guest** = auth/errors. **client** and **admin** = **never referenced** by any `view()` or `@extends` in codebase. |
| **Organization flow** | **Active:** `/organizations`, `/organizations/create`, `OrganizationSwitchController`, Livewire `Organizations\Index`, `Organizations\Create`. **Dead:** `OrganizationContextController`, `dashboard.organizations.*` views, routes `dashboard.organizations.switch` / `store` (do not exist). |
| **Auth UI** | **Active:** Blade `auth.login`, `auth.register` (plain forms). **Unused:** `livewire/pages/auth/login.blade.php` (Volt + LoginForm), `livewire/pages/auth/register.blade.php`, and other `livewire/pages/auth/*` (Volt). |

### Unused (never referenced)

| File | Notes |
|------|--------|
| **Volt components** | |
| `resources/views/components/⚡dashboard.blade.php` | Stub; no route or blade mounts it |
| `resources/views/components/organizations/⚡index.blade.php` | Stub; no route or blade mounts it |
| `resources/views/components/organizations/⚡create.blade.php` | Stub; no route or blade mounts it |
| **Livewire auth (Volt)** | |
| `resources/views/livewire/pages/auth/login.blade.php` | Volt + LoginForm; `/login` serves `auth.login` instead |
| `resources/views/livewire/pages/auth/register.blade.php` | Not served by `/register` |
| `resources/views/livewire/pages/auth/verify-email.blade.php` | Auth route uses `auth.verify-email` (Blade) |
| `resources/views/livewire/pages/auth/forgot-password.blade.php` | Same pattern |
| `resources/views/livewire/pages/auth/confirm-password.blade.php` | Same pattern |
| `resources/views/livewire/pages/auth/reset-password.blade.php` | Same pattern |
| **Nav** | |
| `resources/views/livewire/layout/navigation.blade.php` | Volt nav; not included in app or guest layout |
| **Layouts** | |
| `resources/views/layouts/client.blade.php` | No `view()` or `@extends` references it |
| `resources/views/layouts/admin.blade.php` | No `view()` or `@extends` references it |
| **Profile** | |
| `resources/views/profile.blade.php` | No route `profile` in routes; ProfileTest expects `/profile` but route missing |
| **Livewire profile** | Used only by profile.blade.php (which is not routed) |
| **Forms** | |
| `app/Livewire/Forms/LoginForm.php` | Used only by unused `livewire/pages/auth/login.blade.php` |
| **Routes** | |
| `routes/workflows.php` | Empty; **not loaded** in `bootstrap/app.php` (only `web`, `api`, `console`, health) |

### Safe to delete (after approval)

- Volt stubs: `components/⚡dashboard.blade.php`, `components/organizations/⚡index.blade.php`, `components/organizations/⚡create.blade.php`
- Dead controller and its views: `OrganizationContextController`, `dashboard/organizations/index.blade.php`, `dashboard/organizations/create.blade.php`
- Unused layouts: `layouts/client.blade.php`, `layouts/admin.blade.php` (if confirmed no external/package use)
- Unused Livewire auth Volt views: `livewire/pages/auth/*.blade.php` (and optionally `LoginForm.php` if auth stays Blade)
- Unused nav: `livewire/layout/navigation.blade.php`
- Root `dashboard.blade.php` (no route returns it)
- `routes/workflows.php` (empty and not loaded), or add it to bootstrap if workflows are reintended later
- Profile: either add a `profile` route and keep profile views, or remove `profile.blade.php` and Livewire profile components if profile is out of scope

---

## 3. Tenant State Diagram

- **User** has `current_organization_id` (DB) and `organizations()` (many-to-many).
- **currentOrganization()** (on User): reads `current_organization_id`, validates membership, returns `Organization` or null. **Source of truth for DB.**
- **OrganizationContext** (service): uses session key `active_organization_id`. **Source of truth for session.**
- **EnsureOrganizationSelected** (middleware):  
  - Skips check for `organizations.*` routes.  
  - If user has 0 organizations → redirect to `organizations.create`.  
  - Else gets current org by **`$user->currentOrganization() ?? $this->context->current()`** (DB first, then session).  
  - If null → redirect to `organizations.index`.  
- **OrganizationSwitchController:** On switch, sets `auth()->user()->update(['current_organization_id' => $organization->id])` and `OrganizationContext::set($organization)` (session). So **both DB and session are updated** on switch.
- **Organizations\Create (Livewire):** On create, sets `current_organization_id` and `OrganizationContext::set()`.
- **Organizations\Index (Livewire):** If one org, sets `current_organization_id` and context, then redirects to dashboard.

**Flow (bullet form):**

1. User logs in → no tenant in session/DB yet.
2. Request to `/dashboard` → middleware runs (not `organizations.*`) → 0 orgs → redirect `organizations.create`; ≥1 org but no current → redirect `organizations.index`.
3. User selects or creates org → `current_organization_id` and session are set.
4. Next request → `currentOrganization()` or `context->current()` returns org → dashboard/events allowed.
5. User switches org via navbar or organizations index → POST to `organizations.switch` → controller updates DB + session.

**Drift:** Two sources (DB + session) are kept in sync by switch/create and index (single-org). Middleware uses DB first, then session. If session were changed without updating DB (e.g. old code path), `currentOrganization()` could disagree with session; current code paths that set org always set both, so deterministic for current flows.

---

## 4. Drift Risk List

| Risk | Severity | Description |
|------|----------|-------------|
| Two tenant sources | Medium | DB (`current_organization_id`) and session (`OrganizationContext`); both updated together in current code; any future code that only sets session could cause drift. |
| Dead OrganizationContextController | Low | References non-existent routes `dashboard.organizations.*`; 404 if ever linked. |
| Volt vs Blade auth | Low | Login/register are Blade; Livewire Volt auth views and LoginForm unused; confusion for future changes. |
| Duplicate nav systems | Low | `dynamic-navbar` vs `livewire/layout/navigation`; only dynamic-navbar used; navigation.blade has wrong routes (appointments, orders). |
| Unused layouts | Low | `client`, `admin` never extended or used in code. |
| Profile route missing | Medium | ProfileTest expects `/profile`; no such route; profile.blade and Livewire profile components orphaned. |
| workflows.php not loaded | Low | File exists and is empty; not required in bootstrap; harmless. |

---

## 5. Confirmation

- **No code changes were made.**
- **No file deletions were performed.**
- **No refactors were applied.**
- This document is the result of **read-only analysis** as required by PART 1.

**Next step:** Await approval to proceed to **PART 2 — Architectural Normalization** (single layout/nav/tenant source, remove dead and duplicate code, then PART 3 — System Admin Layer).
