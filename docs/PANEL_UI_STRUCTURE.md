# Panel UI Structure — Analysis

Evidence-based analysis of the application’s panel UI: layouts, navigation, tenant vs system panels, and how views map to routes and Livewire components.

---

## 1. High-level structure

| Layer | Purpose | Evidence |
|-------|---------|----------|
| **Layouts** | `layouts/app.blade.php` (authenticated), `layouts/guest.blade.php` (public/auth pages) | `resources/views/layouts/` |
| **Navigation** | Single shared navbar: `components/dynamic-navbar.blade.php` (desktop + mobile drawer) | Included in `layouts/app.blade.php:16` |
| **Tenant panel** | Authenticated user with optional current organization: Dashboard, Organizations, Profile, Events | Routes `dashboard`, `organizations.*`, `profile`, `dashboard.events.*` |
| **System admin panel** | Superuser-only: System Dashboard, System Organizations, System Users, Impersonation | Routes under `system.*`; middleware `system.admin` |
| **Public** | Event page, RSVP, checkout tokenize/status; auth routes (login, register, etc.) | Routes `event.show`, `rsvp.show`, `checkout.*`; `auth.php` |

---

## 2. Layouts

### 2.1 Authenticated layout (`layouts/app.blade.php`)

- **RTL:** `dir="rtl"` on `<html>` (Hebrew).
- **Head:** Vite entries `resources/css/app.css`, `resources/js/app.js`; CSRF; optional `paymentGatewayConfig` and Debugbar.
- **Body:** `bg-[#F9FAFB]`, `min-h-screen`, `bg-gray-50`.
- **Structure:**
  1. `<x-dynamic-navbar location="header" />` — full-width navbar.
  2. Optional `@yield('header')` — block below navbar (e.g. page title); wrapped in `max-w-7xl mx-auto px-4 py-6`, `bg-white border-b`.
  3. `<main class="@yield('containerWidth', 'max-w-7xl') mx-auto px-4 py-8">` — content; default width `max-w-7xl`, overridable per page.
  4. `@stack('scripts')`, `@livewireScripts`.

**Evidence:** `resources/views/layouts/app.blade.php` (full file).

### 2.2 Guest layout (`layouts/guest.blade.php`)

- No RTL on markup (locale still from app).
- No navbar; only `@yield('content')` or `$slot` inside `min-h-screen`.
- Used for: login, register, forgot/reset password, verify email, confirm password (and any other guest views that extend it).

**Evidence:** `resources/views/layouts/guest.blade.php`.

---

## 3. Navigation (`dynamic-navbar.blade.php`)

- **Desktop (`md:` and up):** Horizontal bar; app name; when auth: Dashboard, organization switcher (dropdown), “Manage Organizations”, impersonation exit (if active), Profile, system links (if `is_system_admin`), Logout.
- **Mobile:** Hamburger; overlay + drawer from the left; same links in a column (no org dropdown, only “Current: {name}” + “Manage Organizations”).
- **Organization switcher:** `<details>` dropdown; per-org form POST to `organizations.switch`; checkmark on current org; “Manage Organizations” to `organizations.index`.
- **System admin block:** Shown only when `auth()->user()->is_system_admin`. Links: System Dashboard, System Organizations, System Users. Impersonation exit when `session('impersonation.original_organization_id')`.
- **No** sidebar; no breadcrumbs; no secondary nav per section.

**Evidence:** `resources/views/components/dynamic-navbar.blade.php` (lines 1–138 referenced).

---

## 4. Tenant panel (authenticated, non-system)

### 4.1 Route → view mapping

| Route name | Path | View / component | Notes |
|------------|------|------------------|--------|
| `dashboard` | `/dashboard` | `pages.dashboard` → `<livewire:dashboard />` | Main tenant dashboard |
| `organizations.index` | `/organizations` | `pages.organizations.index` → `<livewire:organizations.index />` | List/switch orgs |
| `organizations.create` | `/organizations/create` | `pages.organizations.create` → `<livewire:organizations.create />` | Create org |
| `profile` | `/profile` | `profile.blade.php` → 3 Livewire profile forms | Update profile, password, delete user |
| `dashboard.events.index` | `dashboard/events` | `dashboard.index` | Controller-rendered; requires `ensure.organization` |
| `dashboard.events.show` | `dashboard/events/{event}` | `dashboard.events.show` | Controller-rendered; event detail |

### 4.2 Page pattern (tenant)

- **Wrapper:** Blade that `@extends('layouts.app')`, sets `@section('title')`, often `@section('containerWidth')` (e.g. `max-w-3xl` for org list/create, `max-w-7xl` for dashboard).
- **Header:** `@section('header')` with `<x-page-header :title="..." :subtitle="..." />` (title + optional subtitle + bottom border).
- **Content:** `@section('content')` containing one or more Livewire components or static markup.

**Evidence:**  
`pages.dashboard.blade.php`, `pages.organizations.index.blade.php`, `pages.organizations.create.blade.php`, `profile.blade.php`.

### 4.3 Tenant dashboard (two entry points)

- **Primary:** `GET /dashboard` → `pages.dashboard` → Livewire `Dashboard`.  
  - Redirects to `organizations.index` if no current organization.  
  - Otherwise: metric cards (total events, total guests, upcoming event, org status) + events table with link “View” → `dashboard.events.show`.
- **Alternative:** `GET dashboard/events` → `DashboardController::index` → `dashboard.index` (server-rendered table, same idea).  
  - Protected by `ensure.organization`; 404 if no current org.

**Evidence:**  
`app/Livewire/Dashboard.php`, `resources/views/livewire/dashboard.blade.php`, `app/Http/Controllers/Dashboard/DashboardController.php`, `resources/views/dashboard/index.blade.php`, `routes/web.php:28,37-40`.

---

## 5. System admin panel

### 5.1 Access and routing

- **Prefix:** `system` (e.g. `/system/dashboard`).
- **Middleware:** `auth`, `verified`, `system.admin`.
- **No** `ensure.organization` — system panel is tenant-agnostic.

### 5.2 System routes and UI

| Route name | Path | Backing | View |
|------------|------|---------|------|
| `system.dashboard` | `/system/dashboard` | Livewire `System\Dashboard` | `system/dashboard.blade.php` → `@livewire('system.dashboard')` |
| `system.organizations.index` | `/system/organizations` | Livewire `System\Organizations\Index` | `system/organizations/index.blade.php` |
| `system.organizations.show` | `/system/organizations/{organization}` | Livewire `System\Organizations\Show` | `system/organizations/show.blade.php` |
| `system.users.index` | `/system/users` | Livewire `System\Users\Index` | `system/users/index.blade.php` |
| `system.users.show` | `/system/users/{user}` | Livewire `System\Users\Show` | `system/users/show.blade.php` |
| `system.impersonate` | POST `system/impersonate/{organization}` | Controller | — |
| `system.impersonation.exit` | POST `system/impersonation/exit` | Controller | — |

### 5.3 System page pattern

- Same layout as tenant: `@extends('layouts.app')`, optional `@section('header')` with `<x-page-header>`, `@section('content')` with a single `@livewire('system.*')` component.
- **System Dashboard:** Livewire `System\Dashboard` uses `#[Layout('layouts.app')]`, `#[Title('System Dashboard')]`; renders `livewire.system.dashboard` with KPIs (users, orgs, events, guests, health, billing placeholders), recent orgs/users.
- **System Organizations / Users:** List and detail Livewire components; list views under `resources/views/system/` and Livewire views under `resources/views/livewire/system/`.

**Evidence:**  
`routes/web.php:45-59`, `app/Livewire/System/Dashboard.php`, `resources/views/system/dashboard.blade.php`, `resources/views/livewire/system/dashboard.blade.php`.

---

## 6. Shared components

| Component | Use |
|-----------|-----|
| `x-dynamic-navbar` | Single navbar (tenant + system) in app layout |
| `x-page-header` | Title + optional subtitle + border; used in tenant and system pages |
| `x-modal` | Reusable modal (e.g. confirmations) |
| `x-primary-button`, `x-secondary-button`, `x-danger-button` | Buttons |
| `x-text-input`, `x-textarea`, `x-input-label`, `x-input-error` | Forms |
| `x-action-message`, `x-auth-session-status` | Feedback / session status |

**Evidence:**  
`resources/views/components/` (files listed in glob); usage in `profile.blade.php`, `layouts/app.blade.php`.

---

## 7. Public and auth UI

- **Public:** `event.show`, `rsvp.show` — controller-rendered; layout may be app or guest depending on view.
- **Checkout:** `checkout.tokenize` (auth), `checkout.status` (auth) — dedicated checkout views; app layout can inject `paymentGatewayConfig`.
- **Auth:** Login, register, forgot/reset password, verify email, confirm password — use `layouts.guest` (or equivalent); Blade views under `resources/views/auth/`.

---

## 8. Summary diagram

```
layouts.app (RTL, Tailwind, Vite)
├── dynamic-navbar (tenant: Dashboard, Org switcher, Profile; system: System Dashboard, Orgs, Users; impersonation exit)
├── header (optional) → page-header
└── main
    ├── Tenant: pages.dashboard | pages.organizations.* | profile → Livewire components
    ├── Tenant (org-scoped): dashboard.index | dashboard.events.show → Controller views
    └── System: system/dashboard | system/organizations/* | system/users/* → @livewire('system.*')

layouts.guest
└── content → auth pages, etc.
```

---

## 9. File locations (reference)

- **Layouts:** `resources/views/layouts/app.blade.php`, `guest.blade.php`
- **Navbar:** `resources/views/components/dynamic-navbar.blade.php`
- **Page wrapper + Livewire:** `resources/views/pages/*.blade.php`, `resources/views/profile.blade.php`, `resources/views/system/*.blade.php`
- **Livewire views:** `resources/views/livewire/*.blade.php`, `resources/views/livewire/system/*.blade.php`, `resources/views/livewire/profile/*.blade.php`, `resources/views/livewire/organizations/*.blade.php`
- **Controller views:** `resources/views/dashboard/index.blade.php`, `resources/views/dashboard/events/show.blade.php`
- **Livewire components:** `app/Livewire/Dashboard.php`, `app/Livewire/Organizations/*.php`, `app/Livewire/Profile/*.php`, `app/Livewire/System/*.php`
