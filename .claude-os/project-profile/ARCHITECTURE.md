# Architecture — Kalfa RSVP (Panel UI + App)

## Panel UI Structure (primary focus)

### High-level structure

| Layer | Purpose |
|-------|---------|
| **Layouts** | `layouts/app.blade.php` (authenticated), `layouts/guest.blade.php` (public/auth) |
| **Navigation** | Single shared navbar: `components/dynamic-navbar.blade.php` (desktop + mobile drawer) |
| **Tenant panel** | Dashboard, Organizations, Profile, Events (auth + optional current org) |
| **System admin panel** | System Dashboard, System Organizations, System Users, Impersonation (`/system/*`, `system.admin`) |
| **Public** | Event page, RSVP, checkout; auth routes use guest layout |

### Layouts

- **Authenticated (`layouts/app`):** RTL (`dir="rtl"`), Vite (css/app.css, js/app.js). Structure: `<x-dynamic-navbar />` → optional `@yield('header')` (e.g. `<x-page-header>`) → `<main class="@yield('containerWidth', 'max-w-7xl') mx-auto px-4 py-8">` → `@livewireScripts`.
- **Guest (`layouts/guest`):** No navbar; `@yield('content')` or `$slot` only. Used for login, register, password, verify, confirm password.

### Navigation

- **Desktop:** App name, Dashboard, organization switcher (`<details>` dropdown, POST to `organizations.switch`), Manage Organizations, impersonation exit (if active), Profile, system links (if `is_system_admin`), Logout.
- **Mobile:** Hamburger → overlay + left drawer; same links in column; current org name + Manage Organizations.
- **System admin block:** System Dashboard, System Organizations, System Users; shown only when `auth()->user()->is_system_admin`. No sidebar; no breadcrumbs.

### Tenant panel

| Route | Path | UI |
|-------|------|-----|
| `dashboard` | `/dashboard` | `pages.dashboard` → `<livewire:dashboard />` (redirect to orgs if no current org; else metric cards + events table → View → event detail) |
| `organizations.index` | `/organizations` | `pages.organizations.index` → `<livewire:organizations.index />` |
| `organizations.create` | `/organizations/create` | `pages.organizations.create` → `<livewire:organizations.create />` |
| `profile` | `/profile` | `profile.blade.php` → 3 Livewire profile forms |
| `dashboard.events.index` | `dashboard/events` | Controller → `dashboard.index` (requires `ensure.organization`) |
| `dashboard.events.show` | `dashboard/events/{event}` | Controller → `dashboard.events.show` |

**Page pattern:** Blade extends `layouts.app`, `@section('title')`, optional `@section('containerWidth')` (e.g. `max-w-3xl`), `@section('header')` with `<x-page-header>`, `@section('content')` with Livewire or static markup.

### System admin panel

- **Prefix:** `system`; middleware: `auth`, `verified`, `system.admin` (no tenant).
- **Routes:** `system.dashboard`, `system.organizations.index` / `show`, `system.users.index` / `show`, POST `system.impersonate`, `system.impersonation.exit`.
- **UI:** Same layout as tenant; each system page = wrapper view + single `@livewire('system.dashboard')` or `system.organizations.index`, etc. Livewire views under `resources/views/livewire/system/`.

### Shared components

- `x-dynamic-navbar`, `x-page-header`, `x-modal`, `x-primary-button`, `x-secondary-button`, `x-danger-button`, `x-text-input`, `x-textarea`, `x-input-label`, `x-input-error`, `x-action-message`, `x-auth-session-status`.

### Summary diagram

```
layouts.app (RTL, Tailwind, Vite)
├── dynamic-navbar (tenant: Dashboard, Org switcher, Profile; system: System Dashboard, Orgs, Users; impersonation exit)
├── header (optional) → page-header
└── main
    ├── Tenant: pages.dashboard | pages.organizations.* | profile → Livewire
    ├── Tenant (org-scoped): dashboard.index | dashboard.events.show → Controller views
    └── System: system/dashboard | system/organizations/* | system/users/* → @livewire('system.*')

layouts.guest → auth pages
```

### Styling (CSS)

- **Pipeline:** Vite 7 → `resources/css/app.css` (`@import "tailwindcss"`) → `@tailwindcss/vite` → LightningCSS → `public/build/` (hashed CSS).
- **No** `tailwind.config.js` in app root; Tailwind v4; all UI styling via Tailwind utilities in Blade/Livewire. RTL via `dir="rtl"` on `<html>`.
- **Reference:** `.claude-os/project-profile/CSS_AND_STYLING.md`.

### File locations

- Layouts: `resources/views/layouts/app.blade.php`, `guest.blade.php`
- Navbar: `resources/views/components/dynamic-navbar.blade.php`
- Page wrappers: `resources/views/pages/*.blade.php`, `profile.blade.php`, `resources/views/system/*.blade.php`
- Livewire views: `resources/views/livewire/**/*.blade.php`
- Controller views: `resources/views/dashboard/index.blade.php`, `dashboard/events/show.blade.php`
- Livewire components: `app/Livewire/Dashboard.php`, `Organizations/*`, `Profile/*`, `System/*`

---

## App architecture (summary)

- **Stack:** Laravel 12, Livewire 4, Alpine.js, Tailwind v4, Vite 7, Flowbite 4. Auth: Laravel Sanctum. Payment: officeguy/laravel-sumit-gateway (SUMIT) + stub.
- **Multi-tenancy:** Organization-based. `User → Organization` (pivot `organization_users` with role). Active org: `users.current_organization_id`; `OrganizationContext::current()` is source of truth; `EnsureOrganizationSelected` middleware for tenant routes.
- **System admin:** `users.is_system_admin`; routes `/system/*`; impersonation with session keys and exit action in navbar.
- **API:** `routes/api.php` — tenant-scoped CRUD for organizations, events, guests, event-tables, seat-assignments, invitations, checkout, payments; public RSVP; webhooks.
- **Policies:** EventPolicy, OrganizationPolicy, GuestPolicy, InvitationPolicy — org membership and role checks. Enums for status (EventStatus, PaymentStatus, etc.).

---

## Panel ↔ Account & Entitlements (connection)

- **Billable subject in UI:** All current panel flows use **Organization** (and User, Event, Payment). No UI displays or edits `account_id`, Account, entitlements, or billing intents. Checkout and billing unchanged (see compatibility checklist).
- **Account layer:** Additive only: `accounts` table; nullable `account_id` on organizations, events_billing, payments. Organization remains customer for SUMIT; Account can later hold `sumit_customer_id` or entitlements. No enforcement or gating in panel.
- **Future UI insertion points:** Tenant (organizations list/create, dashboard, profile) and system (organizations show, system dashboard) are natural places to add Account/entitlement UI when needed; reuse existing page pattern and layout.
- **Full connection map:** See `docs/PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md`, which links panel structure to `docs/ACCOUNT_INSERTION_MAP.md`, `docs/ACCOUNT_ENTITLEMENTS_README.md`, `docs/DB_SCHEMA_AUDIT.md`, `docs/VENDOR_CONTRACT_REQUIREMENTS.md`, and `docs/COMPATIBILITY_CHECKLIST_ACCOUNT_PHASE.md`.
