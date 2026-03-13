# Step 1 — Full Repository Analysis
## Kalfa.me → Laravel + Inertia.js + React + TypeScript Migration

> Generated: 2026-03-13  
> Purpose: Pre-migration architectural analysis. Stop after Step 1 — awaiting further instructions.

---

## System Overview

| | |
|---|---|
| **Framework** | Laravel 12 (PHP 8.4.18) |
| **Frontend** | Livewire 4.1 + Alpine.js 3 + Tailwind CSS v4 + Flowbite 4 |
| **Build** | Vite 7, LightningCSS, esbuild |
| **Database** | PostgreSQL (prod) / SQLite (tests) |
| **Auth** | Breeze-style (custom controllers) + WebAuthn/Passkeys |
| **Realtime** | Laravel Reverb (WebSocket server) + Node.js AI Voice relay |

---

## 1. Backend

### 1.1 Service Providers
- `AppServiceProvider` — RateLimiter for WebAuthn, macro setup
- `AuthServiceProvider` — policy registration
- Spatie Permission, Reverb, Media Library, Settings, Sanctum — via package auto-discovery

### 1.2 Middleware

| Middleware | Purpose |
|---|---|
| `RequestId` | Attaches UUID to every request (first in `web` stack) |
| `EnsureOrganizationSelected` | Blocks tenant routes if no active org |
| `EnsureSystemAdmin` | Guards `/system/*` routes (`is_system_admin` flag) |
| `ImpersonationExpiry` | Auto-restores session after 60-min impersonation timeout |
| `throttle:webauthn` | Rate-limits WebAuthn endpoints (10 req/min per IP) |
| `auth:sanctum` | Protects all API endpoints |
| Standard Laravel | `auth`, `guest`, `verified`, `signed` |

### 1.3 Route Structure — 93 routes total

| Group | Prefix | Auth | Count |
|---|---|---|---|
| Public | `/`, `/event/{slug}`, `/rsvp/{slug}` | none | 5 |
| Auth | `/login`, `/register`, `/forgot-password` etc. | guest | 12 |
| WebAuthn | `/webauthn/login`, `/webauthn/register` | split | 4 |
| Dashboard (Blade) | `/dashboard/*`, `/organization/*`, `/profile` | auth+org | 18 |
| Billing (Blade) | `/billing/*` | auth+org | 4 |
| Checkout | `/checkout/*` | mixed | 3 |
| System Admin | `/system/*` | system.admin | 8 |
| API (Sanctum) | `/api/organizations/{org}/events/*` | auth:sanctum | 27 |
| API Public | `/api/rsvp/*`, `/api/payments/*` | none | 4 |
| API Webhooks | `/api/webhooks/{gateway}` | none (HMAC) | 2 |
| Twilio | `/twilio/*`, `/api/twilio/*`, `/mvp-rsvp/*` | none | 7 |

### 1.4 Authentication & Guards

```
Guards:     web (eloquent-webauthn driver, password_fallback: true)
            api (Sanctum token)
Providers:  users → App\Models\User
WebAuthn:   laragear/webauthn v4.1, WEBAUTHN_ID=kalfa.me
Passkeys:   WebAuthnLoginController + WebAuthnRegisterController
            PasskeyAuditContext trait (event_version, request_id, ua_hash)
            RateLimiter: 10/min per IP
```

### 1.5 Policies (4)

| Policy | Model | Key checks |
|---|---|---|
| `EventPolicy` | Event | Org membership, role |
| `GuestPolicy` | Guest | Event org membership |
| `OrganizationPolicy` | Organization | Owner/Admin role for billing |
| `PaymentPolicy` | Payment | Org membership |

### 1.6 Events & Listeners

| Event | Usage |
|---|---|
| `RsvpReceived` | Fired when RSVP response stored |
| `ProductEngineEvent` | Product engine state changes |

No separate Listeners directory — handled inline or via observers.

### 1.7 Jobs & Queues
- **No `app/Jobs/` directory** — queuing happens via Spatie Media Library's `MEDIA_QUEUE`
- Queue driver: `database` (table: `jobs`)
- Failed jobs: `failed_jobs` (uuid driver)
- Job batching table: `job_batches`

### 1.8 Scheduled Commands (routes/console.php)

| Command | Frequency | Description |
|---|---|---|
| `ProductEngineSchedulerHeartbeat` | hourly | Keeps scheduler alive |
| `ProcessTrialExpiration` | hourly | Checks trial expirations |
| `CheckIntegrityCommand` | hourly | Product engine integrity (`--fail-on-issues` flag) |
| SUMIT debt auto-check | daily 02:00 | Checks outstanding debts |
| SUMIT documents sync | daily 03:00 | Syncs OfficeGuy documents |
| CRM folders sync | daily 02:09 | Syncs OfficeGuy CRM |

### 1.9 Models (27)

**Core RSVP domain:**
`User`, `Organization`, `OrganizationUser`, `Event`, `Guest`, `EventTable`, `SeatAssignment`, `Invitation`, `RsvpResponse`

**Billing/Payment:**
`Plan`, `EventBilling`, `Payment`, `BillingWebhookEvent`, `BillingIntent`

**Product Engine (SaaS):**
`Account`, `AccountEntitlement`, `AccountFeatureUsage`, `AccountProduct`, `AccountSubscription`, `Product`, `ProductEntitlement`, `ProductFeature`, `ProductLimit`, `ProductPlan`, `ProductPrice`, `UsageRecord`

**System:**
`SystemAuditLog`, `OrganizationInvitation`

### 1.10 Database Tables

| Era | Tables |
|---|---|
| Core (2026-03) | `users`, `organizations`, `organization_users`, `events`, `guests`, `invitations`, `rsvp_responses`, `event_tables`, `seat_assignments`, `plans`, `events_billing`, `payments`, `billing_webhook_events` |
| System | `personal_access_tokens`, `cache`, `jobs`, `failed_jobs`, `settings` |
| OfficeGuy/SUMIT | `officeguy_transactions`, `officeguy_tokens`, `officeguy_documents`, `officeguy_settings`, `vendor_credentials`, `subscriptions`, `officeguy_crm_*` (6 tables), `pending_checkouts`, `order_success_tokens` |
| Product Engine | `accounts`, `products`, `product_entitlements`, `account_entitlements`, `account_feature_usage` |
| WebAuthn | `webauthn_credentials` |
| Spatie | `roles`, `permissions`, `model_has_*`, `media` |

### 1.11 Form Requests
Located in `app/Http/Requests/` — validation for all API endpoints.  
`InitiateCheckoutRequest` explicitly blocks card data (PCI compliance).

### 1.12 Services (app/Services/)

| Service | Purpose |
|---|---|
| `BillingService` | Orchestrates payment flow (initiate, webhook, status) |
| `SumitPaymentGateway` | SUMIT/OfficeGuy gateway adapter |
| `StubPaymentGateway` | Dev/test stub |
| `OrganizationContext` | Reads/sets active org (DB-backed) |
| `CallingService` | Twilio outbound RSVP call orchestration |
| `WhatsAppRsvpService` | WhatsApp fallback after unanswered calls |
| `SystemAuditLogger` | Logs all system admin actions |
| `FeatureResolver` | Resolves product entitlements |
| `UsageMeter` | Tracks feature usage |
| `SubscriptionManager` | SaaS subscription lifecycle |
| `ProductIntegrityChecker` | Validates product engine state |

---

## 2. Frontend

### 2.1 Blade Layouts

| Layout | Purpose |
|---|---|
| `layouts/app.blade.php` | Authenticated shell — navbar, Livewire scripts, Alpine, Passkey banner |
| `layouts/guest.blade.php` | Unauthenticated shell |
| `components/layouts/app.blade.php` | Component-based app layout (Livewire pattern) |
| `components/layouts/guest.blade.php` | Component-based guest layout |
| `components/dynamic-navbar.blade.php` | Context-aware nav — org switcher, impersonation exit, system admin links |

### 2.2 Blade Views Summary

| Area | Views | Notes |
|---|---|---|
| Auth | 7 | login, register, forgot/reset, verify, confirm, change-password |
| Dashboard | 8 | events CRUD + guests/invitations/tables/seats |
| Public | 3 | event show, RSVP show, RSVP not-available |
| Billing | 4 page + 4 livewire | account, entitlements, intents, usage |
| System Admin | 3 pages + 9 livewire | users, orgs, dashboard, accounts, products, settings |
| Checkout | 2 | tokenize (PaymentsJS), status |
| Twilio | 2 | calling UI, TwiML connect template |
| Emails | 2 | welcome-organizer, organization-invitation |
| Components | ~55 | heroicons (inline SVG ×30), form inputs, modal, empty-state, navbar |
| Livewire views | ~35 | mirrors `app/Livewire/` structure |

### 2.3 Livewire Components (35)

| Area | Components |
|---|---|
| Dashboard | `Dashboard`, `EventGuests`, `EventInvitations`, `EventSeatAssignments`, `EventTables`, `OrganizationMembers` |
| Profile | `ManagePasskeys`, `UpdateProfileInformationForm`, `UpdatePasswordForm`, `DeleteUserForm` |
| Billing | `AccountOverview`, `BillingIntentsIndex`, `EntitlementsIndex`, `UsageIndex` |
| Organizations | `Create`, `Index` |
| System/Users | `Index`, `Show` |
| System/Orgs | `Index`, `Show` |
| System/Accounts | `CreateAccountWizard`, `Index`, `Show` |
| System/Products | `Index`, `Show`, `CreateProductModal`, `CreateProductWizard`, `EntitlementRow`, `ProductCard`, `ProductStatusBadge`, `ProductTree` |
| System/Settings | `Index` |
| System | `Dashboard` |
| Public | `AcceptInvitation` |
| Misc | `Actions/Logout`, `TreeBranch`, `TreeNode` |

### 2.4 Alpine.js Usage

**495 total directive instances** across Blade files. Key patterns:

| Pattern | Used for |
|---|---|
| `x-data="{ open: false }"` | Dropdowns, modals, collapsible sections |
| `x-data="{ mobileMenuOpen: false }"` | Mobile navigation drawer |
| `@entangle('activeTab')` | Tab state sync between Alpine and Livewire |
| `x-data="modernFileUpload({...})"` | File upload UI component |
| `x-data="{ shown: false }"` | Flash messages auto-dismiss |
| `@click.away` | Close-on-click-outside for dropdowns |
| Collapse plugin | Tree node expand/collapse |
| Intersect plugin | Lazy-load triggers |

### 2.5 Dynamic UI Behaviors

| Feature | Technology |
|---|---|
| Drag & drop seat assignments | `SortableJS v1.15.7` — in `EventTables`, `ProductTree`, `CreateProductWizard` |
| Drag & drop file upload | Alpine + custom `modernFileUpload` component |
| Tree view (product hierarchy) | Custom Livewire `TreeBranch`/`TreeNode` components with Alpine collapse |
| Dark mode toggle | Alpine x-data in `dark-mode-toggle.blade.php` |
| Charts (system dashboard) | Flowbite/basic — no heavy chart library |
| Modals | Alpine-based `components/modal.blade.php` |
| Tabs | `@entangle` pattern (Alpine ↔ Livewire) |

### 2.6 Vite Configuration

```js
Entry points:  resources/css/app.css
               resources/js/app.js
               resources/js/passkey-login.js   // separate entry
Alias:         @ → resources/js
Plugins:       @tailwindcss/vite, laravel-vite-plugin
CSS:           LightningCSS (not PostCSS)
Optimize:      flowbite, alpinejs, axios, @laragear/webpass pre-bundled
Build:         public/build/, ES2020, esbuild minify
```

### 2.7 JS Dependencies

```json
alpinejs:            ^3.15.4
@alpinejs/collapse:  ^3.15.8
@alpinejs/intersect: ^3.15.8
flowbite:            ^4.0.1
sortablejs:          ^1.15.7
@laragear/webpass:   ^2.1.2
pusher-js:           installed (for Echo)
laravel-echo:        installed
axios:               standard
```

---

## 3. Realtime & Integrations

### 3.1 Laravel Reverb (WebSocket server)

- **Status:** ✅ Installed (`laravel/reverb ^1.0`)
- Port: `8080`, host: `0.0.0.0`
- Redis scaling: supported via `REVERB_SCALING_ENABLED`
- Origins: `['*']` (open — production concern)
- `echo.js` currently configured for **Pusher driver** (needs updating to Reverb)

### 3.2 Node.js Voice Bridge (`server.js`)

- Port `4000` (WebSocket), PM2 process: `kalfa-ai-voice`
- **Flow:** Twilio Media Stream → Node.js WS → Gemini Live API (BidiGenerateContent) → TTS back via Twilio
- Hebrew TTS: `Google he-IL-Standard-A` + SSML
- WhatsApp fallback on no-answer
- POSTs to `POST /api/twilio/rsvp/process` on completion
- Env deps: `GEMINI_API_KEY`, `PHP_WEBHOOK`, `CALL_LOG_SECRET`, `TWILIO_*`

### 3.3 Twilio

- Voice: Outbound RSVP calls via `CallingService`
- Verify API: OTP (SMS + WhatsApp), SID: `VA5f1c126dd6b47bcd05492197c1c36f73`
- TwiML: `resources/views/twilio/twiml/connect.blade.php`
- Webhooks: `/mvp-rsvp/webhook/callcomes`, `/twilio/calling/status`, `/twilio/rsvp/*`

### 3.4 Payment — SUMIT/OfficeGuy

- Package: `officeguy/laravel-sumit-gateway v5.0.0-rc1`
- Live gateway: `SumitPaymentGateway`, dev: `StubPaymentGateway`
- Webhooks: `/api/webhooks/sumit`, `/api/webhooks/bit` + OfficeGuy auto-registered routes
- HMAC-SHA256 signature validation via `BILLING_WEBHOOK_SECRET`
- PCI: `InitiateCheckoutRequest` blocks card fields, only PaymentsJS tokens accepted

### 3.5 Mail

- 2 Mailables: `WelcomeOrganizer`, `OrganizationInvitationMail`
- Driver: `log` (dev), SMTP port 2525 (prod)

### 3.6 Notifications
- `app/Notifications/` — **empty** (no notification classes exist)

### 3.7 Spatie Packages

| Package | Version | Usage |
|---|---|---|
| `spatie/laravel-permission` | ^7.2 | Team-based roles scoped to Organization (Owner/Admin/Editor/Viewer) |
| `spatie/laravel-medialibrary` | ^11.21 | File/image attachments, queued responsive images |
| `spatie/laravel-settings` | ^3.7 | System-level settings storage |
| `spatie/calendar-links` | ^1.11 | Event calendar links (Google, iCal, Outlook) |

### 3.8 Webhooks Received (external)

| Endpoint | Source | Auth |
|---|---|---|
| `POST /api/webhooks/sumit` | SUMIT payment | HMAC-SHA256 |
| `POST /api/webhooks/bit` | Bit payment | HMAC-SHA256 |
| `officeguy/webhook/*` | OfficeGuy package auto-routes | package-handled |
| `POST /api/twilio/rsvp/process` | Node.js voice bridge | `CALL_LOG_SECRET` |
| `POST /api/twilio/calling/log` | Node.js call log | `CALL_LOG_SECRET` |
| `POST /mvp-rsvp/webhook/callcomes` | Twilio inbound call | Twilio signature |
| `POST /twilio/calling/status` | Twilio status callback | Twilio signature |

---

## 4. Migration Complexity Map

| Concern | Complexity | Notes |
|---|---|---|
| Auth pages (login/register/reset) | 🟢 Low | Standard forms → React |
| Dashboard CRUD (events/guests/tables) | 🟡 Medium | Livewire → Inertia pages |
| SortableJS drag & drop | 🟡 Medium | React DnD or `@dnd-kit/core` |
| Tree component (products) | 🟠 High | Recursive Livewire → React recursive component |
| Alpine dropdowns/modals | 🟢 Low | React state or Radix/Headless UI |
| WebAuthn/Passkey | 🟡 Medium | `passkey-login.js` → React component, `window.Webpass` already global |
| Checkout/PaymentsJS tokenization | 🟠 High | External JS integration — wrap carefully or keep Blade |
| Twilio TwiML views | 🟢 None | Stay as Blade (server-rendered XML, not UI) |
| Node.js voice bridge | 🟢 None | Fully independent — not touched |
| API layer | 🟢 None | Already RESTful — zero changes needed |
| Realtime (Reverb/Echo) | 🟡 Medium | `echo.js` → React hooks, `useEcho` pattern |
| System Admin panel | 🟠 High | ~15 complex Livewire components |
| Multi-tenant org context | 🟡 Medium | Pass via Inertia shared props (`HandleInertiaRequests`) |
| Impersonation UI | 🟡 Medium | Pass impersonation state via Inertia shared props |
| Public RSVP pages | 🟢 Low | Simple forms — React straightforward |
| Dark mode | 🟢 Low | Tailwind `dark:` class + `localStorage` state |
| Spatie Media Library uploads | 🟡 Medium | `modernFileUpload` Alpine → React dropzone |
