# Frontend, Routes & Livewire Documentation

> Auto-generated: 2026-03-18 | Project: kalfa.me (Event SaaS)

---

## Table of Contents

1. [Frontend Stack](#frontend-stack)
2. [Route Map — Web](#route-map--web)
3. [Route Map — API](#route-map--api)
4. [Route Map — Auth](#route-map--auth)
5. [Middleware Reference](#middleware-reference)
6. [Livewire Component Inventory](#livewire-component-inventory)
7. [Blade Template Structure](#blade-template-structure)
8. [System Admin Panel](#system-admin-panel)
9. [Public vs Authenticated Pages](#public-vs-authenticated-pages)
10. [Controllers Reference](#controllers-reference)

---

## Frontend Stack

| Technology | Version | Purpose |
|---|---|---|
| **Tailwind CSS** | v4.2+ | Utility-first styling (via `@tailwindcss/vite` plugin) |
| **Alpine.js** | v3.15+ | Reactive UI interactions |
| **Livewire** | v3/v4 | Server-driven reactive components |
| **Vite** | v7.0+ | Build tool & HMR |
| **Flowbite** | v4.0+ | UI component library (Tailwind-based) |
| **Laravel Echo + Reverb** | v2.3+ | WebSocket real-time broadcasting |
| **Axios** | v1.11+ | HTTP client |
| **Chart.js** | v4.5+ | Data visualizations |
| **SortableJS** | v1.15+ | Drag-and-drop sorting |
| **CropperJS** | v2.1+ | Image cropping |
| **Floating UI** | v1.7+ | Tooltip/popover positioning |
| **jQuery** | v4.0+ | Legacy compat (exposed globally) |
| **@laragear/webpass** | v2.1+ | WebAuthn/Passkey authentication |
| **Alpine Clipboard** | v2.3+ | Copy-to-clipboard plugin |
| **Alpine Intersect** | v3.15+ | Intersection observer plugin |
| **Alpine Collapse** | v3.15+ | Accordion/collapse plugin |

### Vite Configuration

- **Entry points:** `resources/css/app.css`, `resources/js/app.js`, `resources/js/passkey-login.js`
- **CSS:** Tailwind v4 via `@tailwindcss/vite`, LightningCSS transformer, custom media drafts enabled
- **Output:** `public/build/` with CSS in `assets/css/`, JS in `assets/js/`
- **Alias:** `@` → `resources/js/`
- **Dev server:** `0.0.0.0:5173`, polling watcher enabled
- **Optimized deps:** flowbite, alpinejs, axios, @laragear/webpass

### CSS Design System (`resources/css/app.css`)

Custom `@theme` with brand palette:
- **Brand:** `#6C4CF1` (purple), hover `#5A3DE0`
- **Surfaces:** `#FAFAFC` (surface), `#FFFFFF` (card), `#E4E4E7` (stroke)
- **Typography:** `#18181B` (content), `#71717A` (muted)
- **Semantic:** success `#22C55E`, warning `#F59E0B`, danger `#EF4444`

Component classes: `.btn-primary`, `.btn-secondary`, `.btn-ghost`, `.btn-danger`, `.card`, `.card-elevated`, `.badge-*`, `.input-base`, `.glass-navbar`, `.mobile-drawer`, `.nav-link`

Full dark mode support with CSS custom property overrides.

### JavaScript Setup (`resources/js/app.js`)

- Imports `bootstrap.js` (Axios + CSRF + Laravel Echo/Reverb WebSocket)
- Loads Flowbite, jQuery (global), CropperJS, SortableJS, Chart.js, Floating UI
- Alpine plugins registered on `alpine:init`: Clipboard, Intersect, Collapse
- Webpass exposed globally for passkey auth

### Passkey Login (`resources/js/passkey-login.js`)

Standalone entry for the login page — handles WebAuthn assertion flow with silent cancel detection, CSRF 419 recovery, and redirect on success.

---

## Route Map — Web

### Public Routes (No Auth)

| Method | URI | Name | Handler | Notes |
|---|---|---|---|---|
| GET | `/` | `home` | Closure | Redirects: auth→dashboard, guest→login |
| GET | `/event/{slug}` | `event.show` | `PublicEventController@show` | Public event page |
| GET | `/rsvp/{slug}` | `rsvp.show` | `PublicRsvpViewController@show` | Public RSVP form |
| POST | `/rsvp/{slug}/responses` | `rsvp.responses.store` | `PublicRsvpViewController@store` | Submit RSVP response |
| GET,POST | `/invitations/{token}` | `invitations.accept` | `Livewire\AcceptInvitation` | Accept org invitation |
| GET | `/terms` | `terms` | Closure → `legal.terms` | Terms of service |
| GET | `/privacy` | `privacy` | Closure → `legal.privacy` | Privacy policy |
| GET | `/refund-policy` | `refund.policy` | Closure → `legal.refund-policy` | Refund policy |

### WebAuthn / Passkey Routes

| Method | URI | Name | Middleware | Handler |
|---|---|---|---|---|
| POST | `/webauthn/login/options` | `webauthn.login.options` | `throttle:webauthn` (no CSRF) | `WebAuthnLoginController@options` |
| POST | `/webauthn/login` | `webauthn.login` | `throttle:webauthn` (no CSRF) | `WebAuthnLoginController@login` |
| POST | `/webauthn/register/options` | `webauthn.register.options` | `auth`, `throttle:webauthn` (no CSRF) | `WebAuthnRegisterController@options` |
| POST | `/webauthn/register` | `webauthn.register` | `auth`, `throttle:webauthn` (no CSRF) | `WebAuthnRegisterController@register` |

### Twilio Routes

| Method | URI | Name | Middleware | Handler |
|---|---|---|---|---|
| GET | `/twilio/calling` | `twilio.calling.index` | `auth`, `verified`, `ensure.organization`, `ensure.feature:twilio_enabled` | `CallingController@index` |
| POST | `/twilio/calling/initiate` | `twilio.calling.initiate` | (same) | `CallingController@call` |
| GET | `/twilio/calling/logs` | `twilio.calling.logs` | (same) | `CallingController@getLogs` |
| POST | `/twilio/calling/status` | `twilio.calling.status` | (public webhook) | `CallingController@statusCallback` |
| GET,POST | `/twilio/rsvp/connect` | `twilio.rsvp.connect` | (public webhook) | `RsvpVoiceController@connect` |
| POST | `/twilio/rsvp/response` | `twilio.rsvp.digit_response` | (public webhook) | `RsvpVoiceController@digitResponse` |
| GET,POST | `/mvp-rsvp/webhook/callcomes` | — | (public webhook) | `TwilioController@callComes` |

### Authenticated Routes (`auth`, `verified`)

| Method | URI | Name | Extra Middleware | Handler |
|---|---|---|---|---|
| GET | `/dashboard` | `dashboard` | — | Closure → `pages.dashboard` |
| GET | `/organizations` | `organizations.index` | — | Closure → `pages.organizations.index` |
| GET | `/organizations/create` | `organizations.create` | — | Closure → `pages.organizations.create` |
| POST | `/organizations/switch/{organization}` | `organizations.switch` | — | `OrganizationSwitchController` |
| GET | `/profile` | `profile` | — | Closure → `profile` |
| GET | `/checkout/{organization}/{event}` | `checkout.tokenize` | `auth` only | `CheckoutTokenizeController` |
| GET | `/checkout/status/{payment}` | `checkout.status` | `auth` only | `CheckoutStatusController@show` |

### Organization-Scoped Routes (`auth`, `verified`, `ensure.organization`)

| Method | URI | Name | Extra Middleware | Handler |
|---|---|---|---|---|
| GET | `/organization/settings` | `dashboard.organization-settings.edit` | — | `OrganizationSettingsController@edit` |
| PUT | `/organization/settings` | `dashboard.organization-settings.update` | — | `OrganizationSettingsController@update` |

### Billing Routes (`auth`, `verified`, `ensure.organization`)

| Method | URI | Name | Handler |
|---|---|---|---|
| GET | `/billing` | `billing.account` | Closure → `pages.billing.account` |
| GET | `/billing/plans` | `billing.plans` | `Livewire\Billing\PlanSelection` |
| GET | `/billing/checkout/{plan}` | `billing.checkout` | `BillingSubscriptionCheckoutController` |
| GET | `/billing/entitlements` | `billing.entitlements` | Closure → `pages.billing.entitlements` |
| GET | `/billing/usage` | `billing.usage` | Closure → `pages.billing.usage` |
| GET | `/billing/intents` | `billing.intents` | Closure → `pages.billing.intents` |

### Feature Routes (`auth`, `verified`, `ensure.organization`, `ensure.account_active`)

| Method | URI | Name | Handler |
|---|---|---|---|
| GET,POST | `/team` | `dashboard.team` | `Livewire\Dashboard\OrganizationMembers` |
| GET | `/dashboard/events` | `dashboard.events.index` | `DashboardController@index` |
| GET | `/dashboard/events/create` | `dashboard.events.create` | `EventController@create` |
| POST | `/dashboard/events` | `dashboard.events.store` | `EventController@store` |
| GET | `/dashboard/events/{event}` | `dashboard.events.show` | `EventController@show` |
| GET | `/dashboard/events/{event}/edit` | `dashboard.events.edit` | `EventController@edit` |
| PUT | `/dashboard/events/{event}` | `dashboard.events.update` | `EventController@update` |
| DELETE | `/dashboard/events/{event}` | `dashboard.events.destroy` | `EventController@destroy` |
| GET | `/dashboard/events/{event}/guests` | `dashboard.events.guests.index` | `EventGuestsController@index` |
| GET | `/dashboard/events/{event}/tables` | `dashboard.events.tables.index` | `EventTablesController@index` |
| GET | `/dashboard/events/{event}/invitations` | `dashboard.events.invitations.index` | `EventInvitationsController@index` |
| GET | `/dashboard/events/{event}/seat-assignments` | `dashboard.events.seat-assignments.index` | `EventSeatAssignmentsController@index` |

### System Admin Routes (`auth`, `verified`, `system.admin`)

See [System Admin Panel](#system-admin-panel) section below.

---

## Route Map — API

All API routes are prefixed with `/api/` (standard Laravel).

### Authenticated API (`auth:sanctum`)

| Method | URI | Name | Extra Middleware | Controller |
|---|---|---|---|---|
| GET | `/organizations/{organization}` | `organizations.show` | — | `OrganizationController@show` |
| PATCH | `/organizations/{organization}` | `organizations.update` | — | `OrganizationController@update` |
| POST | `/billing/checkout` | `billing.checkout.purchase` | `ensure.organization` | `SubscriptionPurchaseController` |
| POST | `/billing/coupon/validate` | `billing.coupon.validate` | `ensure.organization` | `CouponValidationController` |

### Tenant Feature API (`auth:sanctum`, `ensure.account_active`)

| Method | URI | Controller | Notes |
|---|---|---|---|
| GET/POST | `/organizations/{org}/events` | `EventController` | List / create events |
| GET/PUT/PATCH/DELETE | `/organizations/{org}/events/{event}` | `EventController` | CRUD single event |
| GET/POST | `/organizations/{org}/events/{event}/guests` | `GuestController` | List / create guests |
| POST | `/organizations/{org}/events/{event}/guests/import` | `GuestImportController` | CSV/Excel import |
| GET/PUT/PATCH/DELETE | `/organizations/{org}/events/{event}/guests/{guest}` | `GuestController` | CRUD single guest |
| GET/POST | `/organizations/{org}/events/{event}/event-tables` | `EventTableController` | List / create tables |
| GET/PUT/PATCH/DELETE | `/organizations/{org}/events/{event}/event-tables/{eventTable}` | `EventTableController` | CRUD single table |
| GET/PUT | `/organizations/{org}/events/{event}/seat-assignments` | `SeatAssignmentController` | View / update seating |
| GET/POST | `/organizations/{org}/events/{event}/invitations` | `InvitationController` | List / create invitations |
| POST | `/organizations/{org}/events/{event}/invitations/{invitation}/send` | `InvitationController@send` | Send invitation |
| POST | `/organizations/{org}/events/{event}/checkout` | `CheckoutController@initiate` | Event payment checkout |
| GET | `/payments/{payment}` | `PaymentController@show` | Payment status |

### Twilio Integration API (Secret-key secured)

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/api/twilio/rsvp/process` | `api.twilio.rsvp.process` | `RsvpVoiceController@process` |
| POST | `/api/twilio/calling/log` | `api.twilio.calling.log.append` | `CallingController@appendLog` |

### Public API

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/api/rsvp/{slug}` | `api.rsvp.show` | `throttle:rsvp_show` | `PublicRsvpController@showBySlug` |
| POST | `/api/rsvp/{slug}/responses` | `api.rsvp.responses.store` | `throttle:rsvp_submit` | `PublicRsvpController@storeResponse` |
| POST | `/api/webhooks/{gateway}` | `webhooks.handle` | `throttle:webhooks` (no CSRF) | `WebhookController@handle` |
| GET | `/api/webhooks/{gateway}` | `webhooks.get` | — | 405 response |

---

## Route Map — Auth

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/register` | `register` | `guest` | `RegisterController@create` |
| POST | `/register` | — | `guest` | `RegisterController@store` |
| GET | `/login` | `login` | `guest` | `LoginController@create` |
| POST | `/login` | — | `guest` | `LoginController@store` |
| POST | `/logout` | `logout` | `auth` | `LogoutController` |
| GET | `/forgot-password` | `password.request` | `guest` | `PasswordController@create` |
| POST | `/forgot-password` | `password.email` | `guest` | `PasswordController@sendResetLink` |
| GET | `/reset-password/{token}` | `password.reset` | `guest` | `PasswordController@edit` |
| POST | `/reset-password` | `password.store` | `guest` | `PasswordController@update` |
| GET | `/verify-email` | `verification.notice` | `auth` | `VerificationController@notice` |
| POST | `/verify-email` | `verification.send` | `auth` | `VerificationController@send` |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | `auth`, `signed`, `throttle:6,1` | `VerifyEmailController` |
| GET | `/confirm-password` | `password.confirm` | `auth` | `ConfirmPasswordController@create` |
| POST | `/confirm-password` | — | `auth` | `ConfirmPasswordController@store` |

---

## Middleware Reference

| Alias / Class | Purpose |
|---|---|
| `auth` | Standard Laravel authentication |
| `auth:sanctum` | API token authentication (Sanctum) |
| `guest` | Guests only (redirect if authenticated) |
| `verified` | Email must be verified |
| `signed` | URL signature validation |
| `throttle:*` | Rate limiting |
| `ensure.organization` | `EnsureOrganizationSelected` — user must have an active organization selected |
| `ensure.account_active` | `EnsureAccountActive` — organization must have an active subscription/plan/trial |
| `ensure.feature:*` | `EnsureFeatureAccess` — checks specific feature entitlements (e.g., `twilio_enabled`) |
| `system.admin` | `EnsureSystemAdmin` — restricts to system administrators |
| `throttle:webauthn` | Rate limit for WebAuthn endpoints |
| `throttle:rsvp_show` | Rate limit for public RSVP page views |
| `throttle:rsvp_submit` | Rate limit for RSVP form submissions |
| `throttle:webhooks` | Rate limit for incoming webhooks |
| `ImpersonationExpiry` | Auto-expire admin impersonation sessions |
| `RequireImpersonationForSystemAdmin` | Force system admins to impersonate before tenant actions |
| `SpatiePermissionTeam` | Spatie permission team scope |
| `RequestId` | Attach unique request ID for tracing |

---

## Livewire Component Inventory

### Root-Level Components

| Component | Class | Purpose |
|---|---|---|
| `AcceptInvitation` | `App\Livewire\AcceptInvitation` | Guest layout. Accept organization invitation via token URL. Redirects to register if unauthenticated. |
| `Dashboard` | `App\Livewire\Dashboard` | Main dashboard hub. Shows org stats (events count, guests count, upcoming event). Redirects to org selection if no org set. |

### Dashboard/ Namespace (Event Management)

| Component | Class | Purpose | Real-Time |
|---|---|---|---|
| `EventGuests` | `Dashboard\EventGuests` | CRUD guests for an event. Add, edit, delete, CSV import. Supports file uploads. | ✅ `RsvpReceived` via Echo |
| `EventInvitations` | `Dashboard\EventInvitations` | Create invitations, link to guests, mark as sent. | ✅ `RsvpReceived` via Echo |
| `EventTables` | `Dashboard\EventTables` | CRUD event tables with capacity. List view + seating chart mode. Drag-sort support. | — |
| `EventSeatAssignments` | `Dashboard\EventSeatAssignments` | Assign guests to tables. Dropdown-per-guest interface. | — |
| `OrganizationMembers` | `Dashboard\OrganizationMembers` | Team management: invite members by email, set role (admin/member), cancel invitations, remove members. Full-page Livewire component. | — |

### Organizations/ Namespace

| Component | Class | Purpose |
|---|---|---|
| `Index` | `Organizations\Index` | List user's organizations. Auto-redirect: if none → create, if one → auto-select & go to dashboard. |
| `Create` | `Organizations\Create` | Create new organization form. Auto-assigns owner role, sends welcome email. |

### Profile/ Namespace

| Component | Class | Purpose |
|---|---|---|
| `UpdateProfileInformationForm` | `Profile\UpdateProfileInformationForm` | Edit name & email. Re-verifies email on change. |
| `UpdatePasswordForm` | `Profile\UpdatePasswordForm` | Change password with current password confirmation. |
| `DeleteUserForm` | `Profile\DeleteUserForm` | Account deletion with password confirmation. Logs out on delete. |
| `ManagePasskeys` | `Profile\ManagePasskeys` | WebAuthn credential management. Register, rename, delete passkeys. Max 10. |

### Billing/ Namespace

| Component | Class | Purpose |
|---|---|---|
| `AccountOverview` | `Billing\AccountOverview` | Shows account billing status. Auto-creates Account record if org has none. |
| `PlanSelection` | `Billing\PlanSelection` | Full-page plan picker. Shows available plans, trial start confirmation. Redirects if already subscribed. |
| `EntitlementsIndex` | `Billing\EntitlementsIndex` | CRUD account entitlements (feature flags/limits). |
| `UsageIndex` | `Billing\UsageIndex` | Read-only feature usage table with filters. |
| `BillingIntentsIndex` | `Billing\BillingIntentsIndex` | Read-only billing intents (payment history/pending). |

### System/ Namespace (Admin Panel)

See [System Admin Panel](#system-admin-panel) below.

### Actions/

| Component | Class | Purpose |
|---|---|---|
| `Logout` | `Actions\Logout` | Shared logout action (invalidates session, regenerates token). Used by DeleteUserForm. |

---

## Blade Template Structure

### Layouts

| Path | Purpose |
|---|---|
| `layouts/app.blade.php` | Authenticated app shell. RTL/LTR support, `<x-dynamic-navbar>`, Vite assets, PWA head, skip-to-content, passkey upgrade banner. |
| `layouts/guest.blade.php` | Guest/unauthenticated shell. Minimal — just slot, Livewire styles/scripts. |
| `components/layouts/app.blade.php` | Component-based app layout (alternative). |
| `components/layouts/guest.blade.php` | Component-based guest layout (alternative). |

### View Components (`components/`)

**UI Primitives:**
- `action-message`, `danger-button`, `primary-button`, `secondary-button`
- `text-input`, `textarea`, `input-error`, `input-label`
- `modal`, `empty-state`, `loading-skeleton`, `page-header`
- `file-upload-modern`, `dark-mode-toggle`

**Navigation:**
- `dynamic-navbar` — Premium glass navbar with mobile drawer
- `auth-logo`, `auth-session-status`

**Tree Components:**
- `tree/tree.blade.php`, `tree/⚡tree-toolbar.blade.php`

**Heroicons:** ~30+ inline SVG icon components (`heroicon-o-*`)

### Page Views

| Directory | Views | Purpose |
|---|---|---|
| `pages/dashboard.blade.php` | Dashboard wrapper (embeds Livewire Dashboard) |
| `pages/organizations/index.blade.php` | Organization list wrapper |
| `pages/organizations/create.blade.php` | Organization create wrapper |
| `pages/billing/account.blade.php` | Billing account wrapper |
| `pages/billing/entitlements.blade.php` | Entitlements wrapper |
| `pages/billing/usage.blade.php` | Usage wrapper |
| `pages/billing/intents.blade.php` | Billing intents wrapper |

### Dashboard Views

| View | Purpose |
|---|---|
| `dashboard/index.blade.php` | Events dashboard (list/grid) |
| `dashboard/events/create.blade.php` | Create event form |
| `dashboard/events/edit.blade.php` | Edit event form |
| `dashboard/events/show.blade.php` | Event detail view |
| `dashboard/events/guests.blade.php` | Guest management (wraps Livewire) |
| `dashboard/events/tables.blade.php` | Table management (wraps Livewire) |
| `dashboard/events/invitations.blade.php` | Invitation management (wraps Livewire) |
| `dashboard/events/seat-assignments.blade.php` | Seating chart (wraps Livewire) |
| `dashboard/organizations/edit.blade.php` | Organization settings |

### Auth Views

| View | Purpose |
|---|---|
| `auth/login.blade.php` | Login (email + passkey support) |
| `auth/register.blade.php` | Registration |
| `auth/forgot-password.blade.php` | Password reset request |
| `auth/reset-password.blade.php` | Password reset form |
| `auth/verify-email.blade.php` | Email verification |
| `auth/confirm-password.blade.php` | Password confirmation |
| `auth/change-password.blade.php` | Password change |

### Livewire Views (`livewire/`)

Mirror the Livewire component hierarchy:
- `livewire/dashboard.blade.php`
- `livewire/accept-invitation.blade.php`
- `livewire/dashboard/event-guests.blade.php`, `event-invitations.blade.php`, `event-tables.blade.php`, `event-seat-assignments.blade.php`, `organization-members.blade.php`
- `livewire/organizations/index.blade.php`, `create.blade.php`
- `livewire/profile/update-profile-information-form.blade.php`, `update-password-form.blade.php`, `delete-user-form.blade.php`, `manage-passkeys.blade.php`
- `livewire/billing/account-overview.blade.php`, `plan-selection.blade.php`, `entitlements-index.blade.php`, `usage-index.blade.php`, `billing-intents-index.blade.php`
- `livewire/system/` — full admin panel views (see below)
- `livewire/tree-branch.blade.php`, `tree-node.blade.php`

### Other Views

| Directory | Views | Purpose |
|---|---|---|
| `events/show.blade.php` | Public event page |
| `rsvp/show.blade.php`, `event-not-available.blade.php` | Public RSVP page + unavailable state |
| `checkout/tokenize.blade.php`, `status.blade.php` | Payment checkout flow |
| `billing/subscription-checkout.blade.php` | Subscription payment page |
| `twilio/calling.blade.php`, `twiml/connect.blade.php` | Twilio calling UI + TwiML |
| `emails/organization-invitation.blade.php`, `welcome-organizer.blade.php` | Email templates |
| `errors/403.blade.php`, `404.blade.php`, `429-payment.blade.php`, `500.blade.php` | Error pages |
| `legal/terms.blade.php`, `privacy.blade.php`, `refund-policy.blade.php` | Legal pages |
| `profile.blade.php` | Profile page (embeds profile Livewire components) |
| `welcome.blade.php` | Default Laravel welcome (likely unused) |

### Vendor Views

| Directory | Purpose |
|---|---|
| `vendor/livewire/` | Pagination templates (tailwind, bootstrap) |
| `vendor/media-library/` | Spatie Media Library image rendering |
| `vendor/pagination/` | Custom pagination views |
| `vendor/pulse/` | Laravel Pulse dashboard |
| `vendor/scramble/` | API docs (Scramble) |

---

## System Admin Panel

Accessible at `/system/*`, protected by `auth`, `verified`, `system.admin` middleware.

### System Routes

| Method | URI | Name | Component/Controller |
|---|---|---|---|
| GET | `/system/dashboard` | `system.dashboard` | `System\Dashboard` (Livewire) |
| GET | `/system/settings` | `system.settings.index` | `System\Settings\Index` (Livewire) |
| GET | `/system/organizations` | `system.organizations.index` | `System\Organizations\Index` (Livewire) |
| GET | `/system/organizations/{organization}` | `system.organizations.show` | `System\Organizations\Show` (Livewire) |
| GET | `/system/users` | `system.users.index` | `System\Users\Index` (Livewire) |
| GET | `/system/users/{user}` | `system.users.show` | `System\Users\Show` (Livewire) |
| GET | `/system/accounts` | `system.accounts.index` | `System\Accounts\Index` (Livewire) |
| GET | `/system/accounts/create` | `system.accounts.create` | `System\Accounts\CreateAccountWizard` (Livewire) |
| GET | `/system/accounts/{account}` | `system.accounts.show` | `System\Accounts\Show` (Livewire) |
| POST | `/system/accounts/{account}/payment-methods` | `system.accounts.payment-methods.store` | `AccountPaymentMethodController@store` |
| POST | `/system/accounts/{account}/payment-methods/{pm}/default` | `system.accounts.payment-methods.default` | `AccountPaymentMethodController@setDefault` |
| DELETE | `/system/accounts/{account}/payment-methods/{pm}` | `system.accounts.payment-methods.destroy` | `AccountPaymentMethodController@destroy` |
| GET | `/system/products` | `system.products.index` | `System\Products\Index` (Livewire) |
| GET | `/system/products/create` | `system.products.create` | `System\Products\CreateProductWizard` (Livewire) |
| GET | `/system/products/{product}` | `system.products.show` | `System\Products\Show` (Livewire) |
| GET | `/system/coupons` | `system.coupons.index` | `System\Coupons\Index` (Livewire) |
| GET | `/system/coupons/create` | `system.coupons.create` | `System\Coupons\CreateCouponWizard` (Livewire) |
| GET | `/system/coupons/{coupon}/edit` | `system.coupons.edit` | `System\Coupons\EditCoupon` (Livewire) |
| POST | `/system/impersonate/{organization}` | `system.impersonate` | `SystemImpersonationController` |
| POST | `/system/impersonation/exit` | `system.impersonation.exit` | `SystemImpersonationExitController` |

### System Livewire Components

| Component | Purpose |
|---|---|
| **System\Dashboard** | Global KPIs: orgs, users, events, guests counts. Health signals (orphan users, orgs without owners). MRR, active subscriptions, churn rate. 15s auto-poll refresh. |
| **System\Settings\Index** | Tabbed settings: Sumit (payment gateway), Twilio (calling/SMS), Gemini (AI). Toggle active states, manage API keys. |
| **System\Organizations\Index** | Paginated org list with filters (suspended, no plan, no events, no users). Search by name/owner email. |
| **System\Organizations\Show** | Org detail: team tab (add/remove users), events tab, subscription tab. Impersonation, trial extension, suspension controls. Password-confirmed destructive actions. |
| **System\Users\Index** | Paginated user list with filters (admin, no org, recent, suspended). Toggle system admin role. Search. |
| **System\Users\Show** | User detail: organizations, subscriptions per org. Sync subscriptions, password-confirmed actions (disable, reset password, delete). |
| **System\Accounts\Index** | Paginated account list. Search by ID, type, owner, Sumit customer ID, name. Stats overview. |
| **System\Accounts\Show** | Account detail: tabbed (overview, organizations, entitlements, usage, billing intents). Attach/detach orgs. Edit name/owner/Sumit customer. Sumit customer search integration. Entitlement CRUD. |
| **System\Accounts\CreateAccountWizard** | 3-step wizard: Type+Name → Owner → Attach org + Preview. |
| **System\Products\Index** | Product catalog with search, status filter, category filter. Paginated. |
| **System\Products\Show** | Product detail: edit metadata, manage plans/prices/features/limits/entitlements. Integrity checker. |
| **System\Products\CreateProductWizard** | 5-step wizard for new products with entitlements. |
| **System\Products\ProductTree** | Hierarchical tree view of product plans, prices, subscriptions. Searchable. |
| **System\Products\ProductCard** | Reusable product display card. |
| **System\Products\ProductStatusBadge** | Status badge (Draft/Active/Archived) with colors. |
| **System\Products\EntitlementRow** | Inline-editable entitlement row. |
| **System\Products\CreateProductModal** | Quick-create product modal. |
| **System\Coupons\Index** | Paginated coupon list with search, active/type filters. Toggle active. |
| **System\Coupons\CreateCouponWizard** | 5-step wizard: Basic → Scope → Discount → Limits → Review. |
| **System\Coupons\EditCoupon** | Edit existing coupon (code, discount, scope, limits). |

---

## Public vs Authenticated Pages

### Public (No Auth Required)

- `/` — Home redirect
- `/event/{slug}` — Public event page
- `/rsvp/{slug}` — Public RSVP form
- `/invitations/{token}` — Accept invitation (guest layout)
- `/terms`, `/privacy`, `/refund-policy` — Legal pages
- `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` — Auth pages
- `/webauthn/login/*` — Passkey login endpoints
- `/twilio/calling/status`, `/twilio/rsvp/*` — Twilio webhooks
- `/api/rsvp/{slug}`, `/api/webhooks/{gateway}` — Public API

### Authenticated (Login Required)

- `/dashboard` — Main dashboard
- `/organizations/*` — Organization management
- `/profile` — User profile
- `/checkout/*` — Payment flow
- `/billing/*` — Subscription & billing
- `/team` — Team management
- `/dashboard/events/*` — Full event CRUD + guests/tables/invitations/seating
- `/twilio/calling` — Calling feature (requires `twilio_enabled` entitlement)

### System Admin (Login + `is_system_admin`)

- `/system/*` — Full admin panel (dashboard, users, orgs, accounts, products, coupons, settings, impersonation)

---

## Controllers Reference

### Dashboard Controllers (`App\Http\Controllers\Dashboard\`)

| Controller | Purpose |
|---|---|
| `DashboardController` | Events dashboard index |
| `EventController` | Full CRUD for events (create, show, edit, update, destroy) |
| `EventGuestsController` | Guest management index (renders Blade + Livewire) |
| `EventTablesController` | Table management index |
| `EventInvitationsController` | Invitation management index |
| `EventSeatAssignmentsController` | Seat assignment index |
| `OrganizationSettingsController` | Organization settings (edit/update) |

### API Controllers (`App\Http\Controllers\Api\`)

| Controller | Purpose |
|---|---|
| `EventController` | REST API for events |
| `GuestController` | REST API for guests |
| `GuestImportController` | CSV/Excel guest import |
| `EventTableController` | REST API for event tables |
| `SeatAssignmentController` | REST API for seat assignments |
| `InvitationController` | REST API for invitations |
| `OrganizationController` | REST API for org show/update |
| `CheckoutController` | Initiate event payment checkout |
| `PaymentController` | Payment status |
| `PublicRsvpController` | Public RSVP API (no auth) |
| `SubscriptionPurchaseController` | Subscription billing purchase |
| `CouponValidationController` | Validate coupon codes |
| `WebhookController` | Gateway webhook handler |

### System Controllers (`App\Http\Controllers\System\`)

| Controller | Purpose |
|---|---|
| `AccountPaymentMethodController` | Manage account payment methods (store, set default, delete) |
| `SystemImpersonationController` | Start impersonating an organization |
| `SystemImpersonationExitController` | Exit impersonation |

### Other Controllers

| Controller | Purpose |
|---|---|
| `OrganizationSwitchController` | Switch active organization |
| `PublicEventController` | Public event page |
| `PublicRsvpViewController` | Public RSVP web view |
| `CheckoutTokenizeController` | Event checkout tokenization |
| `CheckoutStatusController` | Payment status page |
| `BillingSubscriptionCheckoutController` | Subscription checkout page |
| `TwilioController` | Legacy Twilio webhook |

### Twilio Controllers (`App\Http\Controllers\Twilio\`)

| Controller | Purpose |
|---|---|
| `CallingController` | Calling feature: initiate calls, status callbacks, logs |
| `RsvpVoiceController` | Voice RSVP: TwiML connect, digit response, process |

### View Components (`App\View\Components\`)

| Component | Renders |
|---|---|
| `AppLayout` | `layouts.app` — authenticated shell |
| `GuestLayout` | `layouts.guest` — unauthenticated shell |

---

## Architecture Summary

```
┌─────────────────────────────────────────────────────┐
│                    PUBLIC LAYER                       │
│  /event/{slug}  /rsvp/{slug}  /invitations/{token}  │
│  /api/rsvp/*    /api/webhooks/*                      │
└──────────────────────┬──────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────┐
│                  AUTH LAYER                           │
│  /login  /register  /webauthn/*  /verify-email       │
└──────────────────────┬──────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────┐
│              TENANT LAYER (auth+verified)             │
│  /dashboard  /organizations  /profile  /billing      │
│                                                       │
│  ┌─ ensure.organization ──────────────────────────┐  │
│  │  /billing/*  /organization/settings             │  │
│  │                                                  │  │
│  │  ┌─ ensure.account_active ──────────────────┐   │  │
│  │  │  /dashboard/events/*  /team               │   │  │
│  │  │  /twilio/calling (+ ensure.feature)       │   │  │
│  │  └──────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────┐
│              SYSTEM ADMIN LAYER                       │
│  /system/dashboard  /system/users  /system/orgs      │
│  /system/accounts   /system/products  /system/coupons │
│  /system/settings   /system/impersonate               │
└──────────────────────────────────────────────────────┘
```

**Multi-tenancy model:** Organization-scoped. Users belong to multiple orgs, switch via `OrganizationSwitchController`. Each org has an Account for billing. System admins can impersonate any org.

**Real-time:** Laravel Reverb (WebSocket) via Echo. Event-scoped private channels (`event.{id}`) broadcast `RsvpReceived` events to live-update guest lists and invitations.

**Payment:** Sumit (OfficeGuy) gateway integration for both event payments (checkout tokenization) and subscriptions. Coupon system with global/plan-scoped targeting.
