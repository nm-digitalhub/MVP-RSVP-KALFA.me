# Kalfa RSVP + Seating — System Architecture

**Version**: 1.0  
**Last Updated**: March 2026  
**Status**: Current  
**Stack**: Laravel 12 · PHP 8.4 · Livewire 4 · Alpine.js · Tailwind CSS v4 · Node.js · PostgreSQL

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Context](#system-context)
3. [Architecture Overview](#architecture-overview)
4. [Multi-Tenancy Model](#multi-tenancy-model)
5. [Billing & Access Gate](#billing--access-gate)
6. [Core Domain Models](#core-domain-models)
7. [Route & Middleware Architecture](#route--middleware-architecture)
8. [Service Layer](#service-layer)
9. [Frontend Architecture](#frontend-architecture)
10. [Voice Bridge (Node.js)](#voice-bridge-nodejs)
11. [Payment Architecture](#payment-architecture)
12. [Security Architecture](#security-architecture)
13. [Data Architecture](#data-architecture)
14. [System Admin Panel](#system-admin-panel)
15. [Feature Flag System](#feature-flag-system)
16. [Key Architectural Decisions](#key-architectural-decisions)
17. [Future Considerations](#future-considerations)

---

## Executive Summary

### What This System Does

Kalfa is a **multi-tenant SaaS** platform for event RSVP and seating management, targeting Hebrew-speaking event organizers in Israel. It provides:

- **Event Management** — create and manage events with guest lists
- **RSVP System** — invitation sending, response tracking, WhatsApp/SMS/Voice
- **Seating** — drag-and-drop table assignment
- **Voice RSVP** — outbound AI voice calls via Twilio + Gemini Live (Hebrew TTS)
- **Payment Processing** — event billing via SUMIT gateway (Israeli market)
- **Multi-Tenant Admin** — system admin panel with org management, impersonation, billing control

### Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 / PHP 8.4 |
| Frontend | Livewire 4 + Alpine.js v3 + Tailwind CSS v4 |
| Build | Vite 7 |
| UI Components | Flowbite 4 |
| Authentication | Laravel Breeze + Laravel Sanctum + WebAuthn (Passkeys) |
| Authorization | Spatie Permission (team-scoped) + Laravel Gate |
| Payment | SUMIT (officeguy/laravel-sumit-gateway) |
| Voice | Twilio Programmable Voice + Google Gemini Live API |
| Messaging | Twilio Verify (OTP) + WhatsApp |
| Database | PostgreSQL (production) / SQLite (tests) |
| Cache | Redis (Laravel Cache) |
| Queue | Laravel Queue (database driver) |
| Monitoring | Laravel Telescope + Laravel Pulse |
| Voice Bridge | Node.js (server.js) WebSocket relay on port 4000 |

---

## System Context

```
┌─────────────────────────────────────────────────────────────────────┐
│                     Kalfa RSVP + Seating SaaS                      │
│                                                                     │
│  ┌────────────┐   ┌─────────────────┐   ┌──────────────────────┐  │
│  │  Organizer │   │   Guest (RSVP)  │   │   System Admin       │  │
│  │  Dashboard │   │   Public Pages  │   │   /system/* panel    │  │
│  └────────────┘   └─────────────────┘   └──────────────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │              Laravel 12 Application                          │  │
│  │   Controllers · Livewire · Policies · Services · Models      │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │        Node.js Voice Bridge (server.js, port 4000)           │  │
│  │   Twilio Media Stream ↔ Gemini Live API (WebSocket relay)    │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │  PostgreSQL  │  │    Redis     │  │  Laravel Queue (DB)      │  │
│  └─────────────┘  └──────────────┘  └──────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
           │               │                │              │
    ┌──────────┐   ┌──────────────┐  ┌──────────┐  ┌──────────────┐
    │  SUMIT   │   │   Twilio     │  │ Gemini   │  │  WhatsApp    │
    │ Payment  │   │ Voice / SMS  │  │ Live API │  │  (Twilio)    │
    └──────────┘   └──────────────┘  └──────────┘  └──────────────┘
```

### External Integrations

| Service | Purpose | Fallback |
|---------|---------|----------|
| SUMIT (OfficeGuy) | Israeli payment gateway | Stub gateway (dev) |
| Twilio Voice | Outbound RSVP calls | WhatsApp fallback |
| Twilio Verify | OTP via SMS/WhatsApp | — |
| Twilio WhatsApp | RSVP fallback messages | — |
| Google Gemini Live | Voice AI (Hebrew TTS) | — |

---

## Architecture Overview

### Architectural Style

**Modular Monolith** — all application logic lives in a single Laravel application, with clear domain boundaries via namespacing (`App\Services`, `App\Livewire`, `App\Models`). The Node.js voice bridge is the only external service process.

### High-Level Request Flow

```
Browser / API Client
        │
        ▼
   Nginx / Apache
        │
        ▼
   Laravel FPM
        │
   ┌────┴─────┐
   │Middleware│  auth → verified → ensure.organization
   │ Pipeline │           → ensure.account_active
   │          │                  → ensure.feature:*
   └────┬─────┘
        │
   ┌────┴────────────────────────┐
   │   Router Decision           │
   ├─────────────────────────────┤
   │ Livewire Component          │  ← dashboard, billing, system
   │ API Controller              │  ← REST JSON API
   │ Web Controller              │  ← form submissions, public pages
   └────┬────────────────────────┘
        │
   ┌────┴────────────────────────┐
   │   Service Layer             │
   │  BillingService             │
   │  SubscriptionService        │
   │  CallingService             │
   │  OrganizationContext        │
   └────┬────────────────────────┘
        │
   PostgreSQL / Redis
```

---

## Multi-Tenancy Model

### Hierarchy

```
User
 └─ belongsToMany Organizations (via organization_users, pivot: role)
        └─ belongsTo Account
                └─ hasMany AccountProducts
                └─ hasMany AccountSubscriptions
                └─ hasMany AccountEntitlements
        └─ hasMany Events
        └─ hasMany EventBillings
```

### Organization Context

- **`users.current_organization_id`** — DB is the source of truth for active org (not session)
- **`OrganizationContext::current()`** — service reads from DB, never from request payload
- **`EnsureOrganizationSelected`** middleware — enforces org context on all tenant routes
- Users can belong to multiple organizations; switching via `POST /organizations/switch/{org}`

### Roles (OrganizationUserRole enum)

| Role | Description |
|------|-------------|
| `Owner` | Full control incl. billing |
| `Admin` | Manage events and members |
| `Editor` | Manage event content |
| `Viewer` | Read-only |

Roles are stored in `organization_users.role` and mapped to **Spatie Permission** roles scoped by `organization_id` (team-scoped permissions).

---

## Billing & Access Gate

> Full details: [`docs/BILLING_ACCESS_GATE.md`](./BILLING_ACCESS_GATE.md)

### Account → Billing State

Each `Organization` belongs to one `Account`. Access to feature routes is gated on the account having one of:

| Condition | Table | Check |
|-----------|-------|-------|
| Active product | `account_products` | `status=active AND (expires_at IS NULL OR expires_at > now)` |
| Active subscription | `account_subscriptions` | `status=active AND (ends_at IS NULL OR ends_at > now)` |
| Active trial | `account_subscriptions` | `status=trial AND trial_ends_at > now` |

### Single Source of Truth

```php
// Account::hasBillingAccess() — cached 60s per account
Cache::remember("account:{$id}:billing_access", 60, fn() =>
    $this->activeAccountProducts()->exists()
    || $this->activeSubscriptions()->exists()
    || $this->subscriptions()->trial()->active()->exists()
);
```

### Middleware Stack (tenant routes)

```
web
 └─ auth
     └─ verified
         └─ ensure.organization      ← sets OrganizationContext from users.current_organization_id
             └─ ensure.account_active ← calls Account::hasBillingAccess() [cached]
                 └─ controller / Livewire
```

### Billing Status (3 states)

```
Organization::getBillingStatusAttribute()
  ├─ is_suspended == true  →  'suspended'   🔴
  ├─ hasBillingAccess()    →  'active'      🟢
  └─ (else)                →  'no_plan'    🟡
```

### User Onboarding Flow

```
Register → Create Org → /dashboard (blocked by ensure.account_active)
   → redirect /billing
   → /billing/plans   (PlanSelection Livewire component)
   → Start Trial or Purchase
   → SubscriptionService::startTrial()
   → cache invalidated
   → /dashboard unlocked
```

### Feature-Level Gate

```
ensure.feature:twilio_enabled   →  Gate::allows('feature', 'twilio_enabled')
                                →  FeatureResolver::allows($account, $key)
                                →  checks account_entitlements → product_entitlements
```

---

## Core Domain Models

### Entity Relationship (simplified)

```
users ──────┐
            │ organization_users (role)
organizations ──────────────────────────── events
    │                                          │
    └─ account_id → accounts                  ├─ guests
                         │                    ├─ invitations → rsvp_responses
                         ├─ account_products  ├─ event_tables
                         ├─ account_subscriptions ├─ seat_assignments
                         └─ account_entitlements  └─ events_billing → payments
```

### Key Models

| Model | Table | Key Relations |
|-------|-------|---------------|
| `User` | `users` | `belongsToMany Organizations`, `belongsTo currentOrganization` |
| `Organization` | `organizations` | `belongsTo Account`, `hasMany Events`, `belongsToMany Users` |
| `Account` | `accounts` | `hasMany AccountProducts`, `hasMany AccountSubscriptions`, `hasMany AccountEntitlements` |
| `AccountProduct` | `account_products` | `belongsTo Account`, `belongsTo Product`; scope: `active()` |
| `AccountSubscription` | `account_subscriptions` | `belongsTo Account`, `belongsTo ProductPlan` |
| `Event` | `events` | `belongsTo Organization`, `hasMany Guests`, `hasMany EventTables`, `hasOne EventBilling` |
| `Guest` | `guests` | `belongsTo Event`, `hasMany Invitations`, `hasMany SeatAssignments` |
| `Invitation` | `invitations` | `belongsTo Event`, `belongsTo Guest`; unique token/slug for RSVP |
| `Product` | `products` | `hasMany ProductPlans`, `hasMany ProductEntitlements`, `hasMany ProductFeatures` |
| `ProductPlan` | `product_plans` | `belongsTo Product`, `hasMany ProductPrices` |

### Enums

| Enum | Values |
|------|--------|
| `EventStatus` | Draft, PendingPayment, Active, Cancelled, Completed |
| `EventBillingStatus` | Pending, Paid, Failed |
| `PaymentStatus` | Pending, Processing, Succeeded, Failed |
| `AccountProductStatus` | Active, Revoked, Expired |
| `AccountSubscriptionStatus` | Trial, Active, Cancelled, Expired, Suspended |
| `InvitationStatus` | Pending, Sent, Responded |
| `OrganizationUserRole` | Owner, Admin, Editor, Viewer |
| `RsvpResponseType` | Attending, Declining, Maybe |
| `Feature` | twilio_enabled, voice_rsvp_calls, sms_confirmation_enabled, create_event, max_active_events, max_guests_per_event, guest_import, seating_management, invitation_sending, … |

---

## Route & Middleware Architecture

### Web Route Groups

```
/                           → redirect (home)
/login, /register, ...      → auth.php (Breeze, no middleware)

webauthn/...                → throttle:webauthn (no CSRF)

/dashboard                  → auth + verified
/organizations/*            → auth + verified
/profile                    → auth + verified

/organization/settings      → auth + verified + ensure.organization
/billing/*                  → auth + verified + ensure.organization  (NO billing gate)
/billing/plans              → auth + verified + ensure.organization  (PlanSelection Livewire)

/dashboard/events/*         → auth + verified + ensure.organization + ensure.account_active
/team                       → auth + verified + ensure.organization + ensure.account_active

/twilio/calling/*           → auth + verified + ensure.organization + ensure.feature:twilio_enabled
/twilio/rsvp/*              → public (Twilio callbacks)

/system/*                   → auth + verified + system.admin
/checkout/*                 → auth (no billing gate)
/event/{slug}               → public
/rsvp/{slug}                → public
/invitations/{token}        → public (Livewire)
```

### API Route Groups

```
GET  /api/rsvp/{slug}          → public (throttle:rsvp_show)
POST /api/rsvp/{slug}/responses → public (throttle:rsvp_submit)
POST /api/webhooks/{gateway}   → public + throttle (SUMIT webhooks)

auth:sanctum
├─ GET/PATCH /api/organizations/{org}       → no billing gate
├─ ensure.account_active:
│   ├─ /api/organizations/{org}/events
│   ├─ /api/organizations/{org}/events/{event}/guests
│   ├─ /api/organizations/{org}/events/{event}/event-tables
│   ├─ /api/organizations/{org}/events/{event}/seat-assignments
│   └─ /api/organizations/{org}/events/{event}/invitations
└─ POST /api/organizations/{org}/events/{event}/checkout  → no billing gate

/api/twilio/*                  → secret-key secured (Node.js callbacks)
```

### Registered Middleware Aliases

| Alias | Class | Purpose |
|-------|-------|---------|
| `ensure.organization` | `EnsureOrganizationSelected` | Sets org context from `users.current_organization_id` |
| `ensure.account_active` | `EnsureAccountActive` | Billing gate — blocks if no product/subscription/trial |
| `ensure.feature` | `EnsureFeatureAccess` | Feature-key gate via Gate + FeatureResolver |
| `system.admin` | `EnsureSystemAdmin` | Blocks non-system-admin users |
| `require.impersonation` | `RequireImpersonationForSystemAdmin` | Forces impersonation context for system admins |

### Web-level appended middleware (every request)

| Middleware | Purpose |
|-----------|---------|
| `RequestId` | Injects X-Request-ID header |
| `ImpersonationExpiry` | Auto-exits impersonation after 60 min |
| `SpatiePermissionTeam` | Scopes Spatie permissions to active org |

---

## Service Layer

| Service | Responsibilities |
|---------|----------------|
| `OrganizationContext` | Get/set/switch active organization; read from `users.current_organization_id` |
| `BillingService` | Initiate event payment, handle webhook, mark payment succeeded/failed |
| `SubscriptionService` | startTrial, activate, cancel, suspend, renew; clears billing cache |
| `SubscriptionManager` | Thin wrapper over SubscriptionService for callers |
| `FeatureResolver` | Checks `account_entitlements` + `product_entitlements` for a feature key |
| `CallingService` | Initiate Twilio outbound calls with guest/event params to Node.js bridge |
| `WhatsAppRsvpService` | Send WhatsApp RSVP fallback messages via Twilio |
| `PermissionSyncService` | Syncs Spatie permissions when account products are granted/revoked |
| `SystemAuditLogger` | Logs all system admin actions to `system_audit_logs` |
| `UsageMeter` | Track and enforce feature usage limits |
| `SumitPaymentGateway` | Adapts `PaymentGatewayInterface` for SUMIT |
| `StubPaymentGateway` | Always-succeed gateway for local development |
| `UsagePolicyService` | Evaluates usage against entitlement limits |

---

## Frontend Architecture

### Component Hierarchy

```
layouts/app.blade.php
  ├─ dynamic-navbar.blade.php     ← org switcher, impersonation banner, system admin links
  │
  ├─ Dashboard.php (Livewire)
  ├─ Billing/
  │   ├─ AccountOverview.php      ← billing status + CTA banner
  │   ├─ PlanSelection.php        ← trial/purchase plan cards
  │   ├─ EntitlementsIndex.php
  │   └─ UsageIndex.php
  ├─ Dashboard/
  │   ├─ EventGuests.php
  │   ├─ EventInvitations.php
  │   ├─ EventSeatAssignments.php
  │   ├─ EventTables.php
  │   └─ OrganizationMembers.php
  ├─ Organizations/Create.php
  ├─ Profile/
  │   ├─ UpdateProfileInformationForm.php
  │   ├─ UpdatePasswordForm.php
  │   ├─ ManagePasskeys.php       ← WebAuthn credential management
  │   └─ DeleteUserForm.php
  └─ System/                      ← admin-only Livewire components
      ├─ Dashboard.php            ← MRR, total orgs, users, events
      ├─ Organizations/Index.php  ← withExists billing filter
      ├─ Organizations/Show.php   ← org actions (suspend, transfer, delete)
      ├─ Users/Index.php
      ├─ Users/Show.php
      ├─ Accounts/Index.php
      ├─ Accounts/Show.php
      ├─ Products/Index.php
      └─ Settings/Index.php
```

### Livewire 4 Patterns Used

- `#[Layout('layouts.app')]` and `#[Title(...)]` attributes
- `#[Computed]` for cached computed properties
- `wire:navigate` for SPA-style navigation
- `wire:loading` for loading states
- Lazy-loading via `wire:init`

### Styling

- **Tailwind CSS v4** via `@tailwindcss/vite` plugin
- **Flowbite 4** components (modals, dropdowns, badges)
- **RTL support** — `<html dir="rtl">` in `app.blade.php`
- Design tokens: `text-content`, `text-content-muted`, `bg-surface`, `bg-card`, `border-stroke`, `text-brand`

---

## Voice Bridge (Node.js)

### Architecture

```
Organizer dashboard
    │  POST /twilio/calling/initiate
    ▼
CallingService (Laravel)
    │  Twilio REST API: outbound call
    ▼
Twilio PSTN → Guest phone
    │  TwiML <Connect><Stream url="wss://kalfa.me/media?...params">
    ▼
server.js (port 4000, WebSocket)
    │  Opens Gemini Live WebSocket
    ▼
Gemini Live API (BidiGenerateContent)
    │  Hebrew TTS audio chunks
    ▼
server.js relays μlaw audio back to Twilio
    │  Guest hears AI voice (Hebrew)
    ▼
Call ends → server.js POSTs to:
    POST /api/twilio/rsvp/process   ← saves RSVP response
    POST /api/twilio/calling/log    ← appends call log
    WhatsApp fallback (if no answer)
```

### Key Configuration

| Variable | Purpose |
|----------|---------|
| `GEMINI_API_KEY` | Google Gemini Live API access |
| `PHP_WEBHOOK` | `https://kalfa.me/api/twilio/rsvp/process` |
| `CALL_LOG_URL` | `https://kalfa.me/api/twilio/calling/log` |
| `CALL_LOG_SECRET` | HMAC secret for webhook verification |
| `TWILIO_ACCOUNT_SID` | Twilio account |
| `TWILIO_API_KEY` / `TWILIO_API_SECRET` | API credentials |
| `TWILIO_AUTH_TOKEN_LIVE` | Auth token for live calls |
| `TWILIO_NUMBER` | Outbound caller ID |
| `TWILIO_MESSAGING_SERVICE_SID` | SMS messaging service |
| `TWILIO_WHATSAPP_FROM` | WhatsApp sender number |
| `TWILIO_VERIFY_SID` | Verify service SID (OTP) |

### Model Used

- `gemini-2.0-flash-exp` via `wss://generativelanguage.googleapis.com/ws/...BidiGenerateContent`
- Hebrew voice: Google he-IL-Standard-A (SSML)
- Context passed via URL params: guest name, event name/date/venue, seating, custom questions

---

## Payment Architecture

### Event Payment Flow

```
1. Event created (Draft status)
2. BillingService::initiateEventPayment()
   → creates EventBilling + Payment record
   → event transitions to PendingPayment
3. SUMIT gateway returns redirect_url
4. User pays via SUMIT hosted page
5. Webhook: POST /api/webhooks/sumit
   → WebhookController → BillingService
   → markPaymentSucceeded() or markPaymentFailed()
6. Success → event transitions to Active
```

### Gateway Interface

```php
interface PaymentGatewayInterface {
    public function createOneTimePayment(...): array;  // returns redirect_url
    public function chargeWithToken(...): array;       // single-use PaymentsJS token
    public function handleWebhook(...): void;
}
```

| Gateway | Used When |
|---------|-----------|
| `SumitPaymentGateway` | `BILLING_GATEWAY=sumit` (production) |
| `StubPaymentGateway` | `BILLING_GATEWAY=stub` (development) |

### PCI Compliance

`InitiateCheckoutRequest` rejects any payload containing raw card data keys (`card_number`, `cvv`, etc.) in `prepareForValidation()`. Only single-use tokens from PaymentsJS are accepted.

---

## Security Architecture

### Defense Layers

| Layer | Mechanism |
|-------|-----------|
| Network | Nginx/Apache, TLS |
| Authentication | Laravel Breeze (email/password) + WebAuthn (Passkeys) + Sanctum (API) |
| Session | CSRF via `VerifyCsrfToken`, session regeneration on login |
| Authorization | Spatie RBAC (team-scoped) + Laravel Gate (feature flags) |
| Billing gate | `EnsureAccountActive` middleware + `Account::hasBillingAccess()` |
| Feature gate | `EnsureFeatureAccess` + `FeatureResolver` |
| Impersonation | 60-min expiry, stored in session, auto-exit via `ImpersonationExpiry` middleware |
| PCI | No card data in server-side code; PaymentsJS tokens only |
| Admin actions | `system_audit_logs` via `SystemAuditLogger` |
| Webhooks | HMAC signature verification + idempotency check |

### System Admin Bypass

All billing/feature gates check for system admin authority:

```php
// In EnsureAccountActive + EnsureFeatureAccess
if ($user->is_system_admin) { return $next($request); }
if (session()->has('impersonation.original_organization_id')) { return $next($request); }
```

---

## Data Architecture

### Database Summary (PostgreSQL)

**Core tenant tables:**
`organizations`, `organization_users`, `organization_invitations`, `events`, `guests`, `invitations`, `rsvp_responses`, `event_tables`, `seat_assignments`, `events_billing`, `payments`

**Billing/product engine tables:**
`accounts`, `account_products`, `account_subscriptions`, `account_entitlements`, `account_feature_usage`, `products`, `product_plans`, `product_prices`, `product_entitlements`, `product_features`, `product_limits`, `plans`, `billing_intents`, `usage_records`

**OfficeGuy/SUMIT tables:**
`officeguy_documents`, `officeguy_transactions`, `officeguy_subscriptions`, `officeguy_tokens`, `officeguy_sumit_webhooks`, `officeguy_vendor_credentials`, `officeguy_crm_*`

**System tables:**
`users`, `system_audit_logs`, `personal_access_tokens`, `sessions`, `notifications`, `settings`

**Spatie Permission tables:**
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

### Caching Strategy

| Key Pattern | TTL | Content |
|------------|-----|---------|
| `account:{id}:billing_access` | 60s | `hasBillingAccess()` result |
| `feature:{account_id}:{key}` | 60s | Feature entitlement value |
| Invalidated by | | `grantProduct()`, `SubscriptionService::clearFeatureCache()` |

### N+1 Prevention in Admin Lists

```php
// System organizations index — O(1) subquery, no eager load
Organization::withExists('activeAccountProducts')  // hasManyThrough + active() scope
    ->with('account')
    ->withCount(['users', 'events'])
```

`Organization::activeAccountProducts()` is a `hasManyThrough(AccountProduct, Account, ...)` with `->active()` scope — enables `withExists`, `whereHas`, `withCount` directly.

---

## System Admin Panel

All routes under `/system/*` require `users.is_system_admin = true`.

### Capabilities

| Page | Features |
|------|---------|
| `/system/dashboard` | MRR, total orgs/users/events, churn rate |
| `/system/organizations` | List with billing badge (active/no_plan/suspended), filters |
| `/system/organizations/{org}` | Suspend/activate, transfer ownership, force delete, grant products |
| `/system/users` | List with filters (admin/disabled/no-org), toggle admin role |
| `/system/accounts` | Account management, payment methods |
| `/system/products` | Product + plan + entitlement management |
| `/system/settings` | Global settings |

### Impersonation

```
System admin → POST /system/impersonate/{organization}
  → stores in session:
      impersonation.original_admin_id
      impersonation.original_organization_id
      impersonation.started_at
  → switches current_organization_id to target org
  → ImpersonationExpiry middleware: auto-exit after 60 min
  → Exit: POST /system/impersonation/exit
```

---

## Feature Flag System

### Architecture

```
Route middleware: ensure.feature:twilio_enabled
         │
         ▼
Gate::allows('feature', 'twilio_enabled')
         │
         ▼
Gate::define('feature', fn(User, string $key) =>
    FeatureResolver::allows($account, $key)
)
         │
         ▼
FeatureResolver: checks account_entitlements → product_entitlements
```

### Feature Keys (Feature enum)

| Key | Description |
|-----|-------------|
| `twilio_enabled` | Master switch for all Twilio |
| `voice_rsvp_calls` | Outbound AI voice RSVP calls |
| `sms_confirmation_enabled` | SMS confirmations |
| `sms_confirmation_limit` | SMS quota |
| `create_event` | Event creation |
| `max_active_events` | Active event limit |
| `max_guests_per_event` | Guest count limit |
| `guest_import` | CSV guest import |
| `seating_management` | Table seating |
| `invitation_sending` | Invitation dispatch |

---

## Key Architectural Decisions

### ADR-001: Billing Gate via Middleware (not Policy)

**Decision:** Use `EnsureAccountActive` middleware rather than per-action policy checks.

**Rationale:** All tenant feature routes share the same billing precondition. Middleware enforces it uniformly at the route level, without requiring every controller/Livewire component to check billing. Policies remain for authorization (who can do what within an org).

**Trade-off:** All-or-nothing per route group — cannot partially allow routes for unpaid accounts without adding more route groups.

---

### ADR-002: `hasBillingAccess()` as single source of truth

**Decision:** `Account::hasBillingAccess()` is the canonical check used by both the middleware and `Organization::getBillingStatusAttribute()`.

**Rationale:** Previously `billing_status` used `hasActivePlan()` which read `accountProducts` without the `active()` scope, causing false positives for expired products. Centralizing in one cached method eliminates divergence.

**Trade-off:** Requires explicit cache invalidation on any billing state change (`grantProduct()`, `SubscriptionService::clearFeatureCache()`).

---

### ADR-003: `hasManyThrough` for `Organization::activeAccountProducts`

**Decision:** Define `Organization::activeAccountProducts()` as `hasManyThrough(AccountProduct, Account)` with `->active()` scope.

**Rationale:** Enables `withExists('activeAccountProducts')` directly on the Organization query — single subquery per paginated list, no eager load of collections. Also enables `whereHas`, `withCount` for admin filtering.

**Trade-off:** Organization normally `belongsTo` Account; the `hasManyThrough` goes in the "reverse" direction which requires explicit FK arguments.

---

### ADR-004: Node.js for Gemini Live Voice Bridge

**Decision:** Separate Node.js process (`server.js`) handles Twilio media stream ↔ Gemini Live WebSocket relay.

**Rationale:** Gemini Live uses persistent bidirectional WebSocket with binary audio frames (μlaw). PHP/Laravel is not suited for long-lived WebSocket connections with binary streaming. Node.js handles this natively with low memory overhead.

**Trade-off:** Operational complexity of a second process. Managed via PM2 (`ecosystem.config.js`).

---

## Future Considerations

| Area | Recommendation |
|------|----------------|
| **Caching** | Add Redis tag-based invalidation for billing cache to avoid manual key tracking |
| **API 402 messaging** | Add `?reason=no_active_plan` to billing redirect for better UX messaging |
| **Usage enforcement** | Wire `UsageMeter` + `UsagePolicyService` to enforce `max_active_events`, `max_guests_per_event` at creation time |
| **Subscriptions** | Connect `officeguy_subscriptions` to `account_subscriptions` for live renewal/expiry |
| **Multi-region** | Currently single-region; Redis session sharing needed for horizontal scaling |
| **WebAuthn** | Passkeys implemented — consider removing password login for security |
| **Billing UX** | `/billing/plans` has Trial button wired; Purchase button still routes to `/billing` (SUMIT flow not yet wired per plan) |
| **Admin withExists O(1)** | Currently 2-query pattern (organizations + withExists subquery); scalable to 10k orgs |

---

## Appendix — Development Commands

```bash
composer dev          # Full dev stack (serve + queue + pail + vite)
php artisan test --compact            # All tests
npm run build                         # Production assets
vendor/bin/pint --dirty --format agent  # Code style (after PHP edits)
node server.js                        # Voice bridge (port 4000)
php artisan queue:listen --tries=1    # Queue worker
```

---

**Document Status**: Current  
**Maintained By**: Engineering  
**Related Docs**: [`BILLING_ACCESS_GATE.md`](./BILLING_ACCESS_GATE.md) · [`BILLING_PERMISSION_GATE.md`](./BILLING_PERMISSION_GATE.md) · [`CALLING_SYSTEM_TECHNICAL.md`](./CALLING_SYSTEM_TECHNICAL.md)
