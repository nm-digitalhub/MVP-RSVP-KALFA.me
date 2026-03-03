# System Status Audit — Full Platform Snapshot

**Date:** 2025  
**Scope:** kalfa.me — RSVP + Seating MVP, Billing (SUMIT), multi-tenant.  
**Type:** State-mapping audit. No code changes.

---

## 1. Executive Summary (Non-Technical)

The platform is a **multi-tenant event and RSVP system** with **one-time payment per event** (no subscriptions). Payments are handled by SUMIT (Israeli gateway) in two ways: **redirect** (user sent to SUMIT’s page) and **embedded tokenization** (card entered on your site, only a token sent to the server). The **webhook is the single source of truth** for whether a payment succeeded or failed; the site does not treat a payment as final until the webhook confirms it.

**Stable today:** Core domains (Organizations, Events, Guests, Tables, Invitations, RSVP), API structure, auth (Sanctum), dual payment flows (redirect + token), webhook idempotency, and production path lock (single Laravel root and .env).

**Fragile / to watch:** Dependence on a single payment gateway (SUMIT), no queues (all work synchronous), possible second Laravel root under the same vhost (wrong .env if commands run from parent directory), and no structured correlation IDs or payment tracing in logs.

**Production-ready:** RSVP public flow, event CRUD, checkout (redirect and token), webhook handling, and production path documentation. **Still prototype-level in places:** No scheduled jobs, no Horizon/workers, no CORS config file, and observability is basic (single log channel, no dedicated error tracking).

---

## 2. Technical Summary

- **Stack:** Laravel (API + web), Sanctum (API tokens), SUMIT via `officeguy/laravel-sumit-gateway`, Livewire (some workflows), Filament (vendor).
- **Entry points:** Web (browser), API (JSON, auth:sanctum), Webhooks (POST, no CSRF), CLI (artisan only; no custom cron in `routes/console.php`).
- **Billing:** BillingService orchestrates; gateway adapter (Stub or SumitPaymentGateway) implements redirect and token flows. Webhook is the only authority for succeeded/failed; token flow sets payment to `processing` until webhook.
- **Data:** MySQL/sqlite (env-driven). Payments have unique `gateway_transaction_id` (nullable allowed). Events/organizations/guests use FKs and constraints; Event, Guest, EventTable use soft deletes.
- **Security:** PCI: server never receives card data in token flow; request validation rejects card-like keys. Rate limits: RSVP show 60/min, RSVP submit 10/min, webhooks 120/min. No CORS config file in app (Laravel defaults).
- **Operational:** Single production Laravel root: `httpdocs`. Document root: `httpdocs/public`. Commands must run from `httpdocs` to use correct .env. No Horizon/Supervisor; queue driver config exists (default `database`) but no jobs dispatched in app code.

---

## 3. Architecture Overview

### 3.1 High-Level (Textual)

```
                    ┌─────────────────────────────────────────────────────────┐
                    │                     kalfa.me                             │
                    │  (Laravel: httpdocs | Document root: httpdocs/public)   │
                    └─────────────────────────────────────────────────────────┘
                                              │
         ┌────────────────────────────────────┼────────────────────────────────────┐
         │                                    │                                    │
         ▼                                    ▼                                    ▼
   ┌───────────┐                      ┌──────────────┐                      ┌─────────────┐
   │   Web     │                      │  API (JSON)  │                      │  Webhooks   │
   │ (routes/  │                      │ auth:sanctum │                      │ POST only   │
   │  web.php) │                      │ routes/api  │                      │ throttle    │
   └─────┬─────┘                      └──────┬──────┘                      └──────┬──────┘
         │                                   │                                    │
         │  Hosting, ESIM, Cellular,         │  Organizations → Events →         │  POST
         │  Packages, RSVP (public),         │  Guests, Tables, Invitations,      │  /api/webhooks/{gateway}
         │  Dashboard, Checkout (tokenize)   │  SeatAssignments, Checkout         │  → WebhookController
         │                                   │                                    │
         └───────────────────────────────────┼────────────────────────────────────┘
                                            │
                    ┌───────────────────────┴───────────────────────┐
                    │              Service / Gateway Layer           │
                    │  BillingService ← PaymentGatewayInterface      │
                    │  (StubPaymentGateway | SumitPaymentGateway)    │
                    └───────────────────────┬───────────────────────┘
                                            │
                                            ▼
                    ┌───────────────────────────────────────────────┐
                    │  External: SUMIT (redirect + tokenization)    │
                    │  officeguy/laravel-sumit-gateway              │
                    └───────────────────────────────────────────────┘
```

### 3.2 Entry Points

| Type    | Entry point(s) | Auth / Notes |
|---------|----------------|--------------|
| Web     | `/`, `/hosting/*`, `/esim/*`, `/cellular/*`, `/packages/*`, `/checkout/{org}/{event}`, `/dashboard`, `/profile`, `/orders`, `/appointments` | Session; checkout and dashboard use `auth` / `auth,verified` |
| API     | `/api/organizations/...`, `/api/.../events/...`, `/api/.../checkout`, `/api/rsvp/{slug}`, `/api/rsvp/{slug}/responses` | `auth:sanctum` except public RSVP (throttled) |
| Webhooks| `POST /api/webhooks/{gateway}` (e.g. `sumit`) | No CSRF; signature when `billing.webhook_secret` set; throttle 120/min |
| CLI     | `php artisan` (no custom cron in `console.php`) | N/A |

### 3.3 Core Domains

| Domain        | Responsibility | Source of truth / boundaries |
|---------------|----------------|------------------------------|
| Organizations | Multi-tenant root; name, slug, billing_email, settings | `organizations` table; ownership via `organization_users` |
| Events        | Event entity; status draft → pending_payment → active (via payment) | `events`; activation only via BillingService (webhook) |
| Guests        | Per-event guests | `guests`; FK to event |
| EventTables   | Tables for seating | `tables`; FK to event |
| Invitations   | RSVP links (slug) | `invitations`; FK to event + guest |
| SeatAssignments | Guest ↔ table | `seat_assignments` |
| Billing       | EventBilling + Payment; gateway calls; webhook handling | BillingService; webhook is only authority for succeeded/failed |
| Auth          | Laravel auth + Sanctum tokens | Session (web), Sanctum (API) |

### 3.4 Service Layer and Gateway Adapters

- **BillingService:** Orchestrates EventBilling + Payment creation, status transitions. Calls `PaymentGatewayInterface` for redirect URL or token charge. **Only** webhook calls `markPaymentSucceeded` / `markPaymentFailed` (token flow sets `processing` only).
- **PaymentGatewayInterface:** Implementations: `StubPaymentGateway`, `SumitPaymentGateway`. Methods: `createOneTimePayment` (redirect), `chargeWithToken` (token), `handleWebhook`.
- **SumitPaymentGateway:** Uses `officeguy/laravel-sumit-gateway` `PaymentService::processCharge` (redirect or single-use token). No redirect logic in token path; returns normalized `success`, `transaction_id`, `raw`.

### 3.5 External Integrations

- **SUMIT (OfficeGuy):** Redirect flow (Hosted Payment Page) and tokenization (PaymentsJS → single-use token → `/billing/payments/charge/`). Webhook URL: `https://kalfa.me/api/webhooks/sumit`.

### 3.6 Background Jobs / Queues

- **None in application code.** No `dispatch()`, no Horizon, no Supervisor. `config/queue.php` default driver: `env('QUEUE_CONNECTION', 'database')`. `routes/console.php` has no schedule. All flows are synchronous.

### 3.7 Transactional and Sync vs Async

- **BillingService:** `initiateEventPayment`, `initiateEventPaymentWithToken`, `markPaymentSucceeded`, `markPaymentFailed` run inside `DB::transaction(...)`.
- **WebhookController:** Creates `BillingWebhookEvent`, then calls gateway `handleWebhook` (which uses BillingService in a transaction). No queued webhook processing.
- **Sync:** Checkout (redirect and token), webhook handling, all API and web flows. **Async:** None.

### 3.8 Cross-Domain Dependencies

- Events depend on Organizations (FK, policy).
- EventBilling/Payment depend on Event, Organization, Plan.
- Event activation depends on Payment (webhook) and EventBilling (paid).
- Invitations/RSVP depend on Event, Guest.
- SeatAssignments depend on Event, Guest, EventTable.

---

## 4. Runtime & Environment

| Item | Current state |
|------|----------------|
| **Active production path** | `/var/www/vhosts/kalfa.me/httpdocs` (documented in `docs/production-path-lock.md`) |
| **Document root** | `httpdocs/public` |
| **Active .env** | `/var/www/vhosts/kalfa.me/httpdocs/.env` (must run artisan from `httpdocs`) |
| **Config cache** | Not assumed; `php artisan config:clear` from correct path if needed |
| **Queue driver** | `env('QUEUE_CONNECTION', 'database')`; no workers/jobs in use |
| **Session driver** | `env('SESSION_DRIVER', 'database')` |
| **Cache driver** | Laravel default (file/database/redis per env) |
| **Logging** | `env('LOG_CHANNEL', 'stack')`; stack uses `env('LOG_STACK', 'single')` |
| **Horizon / Supervisor** | Not present |
| **Cron** | No app-defined schedule in `routes/console.php` |
| **Webhook endpoints** | `POST /api/webhooks/{gateway}` (e.g. `sumit`); GET returns 405 JSON |
| **Environment isolation** | Single app; `APP_ENV` in .env. Second Laravel root at vhost root can load wrong .env if commands run there |

**Verified / documented:** No conflicting Laravel roots in code; two physical roots exist (vhost vs httpdocs). Only httpdocs is authoritative. Shadow .env at vhost root can be loaded if artisan is run from there.

---

## 5. Domain State Integrity

### 5.1 Billing

- **State machine:** Payment: `pending` → `processing` (token only) → `succeeded` | `failed`. EventBilling: `pending` → `paid` only via webhook. No direct `pending` → `succeeded` in token flow.
- **Idempotency:** WebhookController checks `Payment::where('gateway_transaction_id', $id)->whereIn('status', ['succeeded', 'failed'])->exists()`; if true returns 200 "Already processed". SumitPaymentGateway `handleWebhook` skips if payment status already succeeded/failed. `payments.gateway_transaction_id` unique (nullable).
- **Webhook authority:** Only webhook calls `markPaymentSucceeded` / `markPaymentFailed`; token flow only sets `processing` and stores `gateway_transaction_id`.
- **Failure:** Token charge failure sets payment to `failed` and returns 422; no auto-retry. Redirect failure does not create succeeded state.

### 5.2 Events

- **Activation:** Event becomes `active` only when `markPaymentSucceeded` runs (webhook). Draft → pending_payment on checkout start; pending_payment → active only after webhook.
- **Dependency on payment:** Event activation is fully dependent on payment success via webhook.
- **State transitions:** draft → pending_payment (checkout); pending_payment → active (webhook success); other statuses (locked, archived, cancelled) via API/usage.

### 5.3 Organizations

- **Ownership:** `organization_users` (pivot); policies (e.g. EventPolicy, OrganizationPolicy) enforce membership for view/create/update/delete and `initiateBilling`.

### 5.4 Auth

- **Token system:** Sanctum personal access tokens (e.g. checkout tokenize page creates short-lived token). Web uses session.
- **Enforcement:** API: `auth:sanctum`; web: `auth`, `auth,verified` where required. Policies used for event and org.
- **Expiration:** Session lifetime and token expiration per Sanctum/session config; no custom logic audited.

---

## 6. Data Integrity

| Aspect | State |
|--------|--------|
| **Unique constraints** | `payments.gateway_transaction_id` unique; nullable (multiple NULLs allowed). `events` unique `(organization_id, slug)`. `organizations.slug` unique. |
| **Foreign keys** | Used on organizations, events, guests, events_billing, payments, invitations, tables, seat_assignments, etc. Cascade/nullOnDelete per migration. |
| **Nullable vs non-null** | Critical fields (e.g. payment status, event status) non-null; gateway_transaction_id, paid_at, etc. nullable where appropriate. |
| **Transaction wrapping** | BillingService methods and webhook-driven updates run inside DB transactions. |
| **Orphan risk** | FKs with cascade reduce orphans; soft-deleted Event/Guest/EventTable can leave related data in place (design choice). |
| **Soft deletes** | Event, Guest, EventTable. |

**Tables that can affect consistency if written outside transactions or with bugs:** `payments`, `events_billing`, `events` (status and activation). Idempotency and single writer (webhook) reduce risk.

---

## 7. Security Posture

| Area | Finding |
|------|---------|
| **PCI** | Token flow: server receives only token + plan_id; card fields rejected (InitiateCheckoutRequest). No card data in logs; CheckoutController comment: do not log payload. SAQ-A-EP assumed (docs). |
| **Request validation** | Form requests for API (e.g. InitiateCheckoutRequest: plan_id, token; forbidden card keys). |
| **Mass assignment** | Models use `$fillable` (e.g. Payment, EventBilling, Event). |
| **Sensitive logging** | SumitPaymentGateway logs event_billing_id, message, payload_keys, transaction_id (no card/token). StepOneController logs request attributes (workflow). Checkout request body not logged. |
| **Rate limiting** | rsvp_show 60/min, rsvp_submit 10/min, webhooks 120/min (AppServiceProvider). |
| **CORS** | No `config/cors.php` in app; Laravel default behavior. |
| **CSRF** | Web routes protected; API stateless; webhooks explicitly `withoutMiddleware(VerifyCsrfToken::class)`. |
| **Authorization** | Policies (Event, Organization); CheckoutController and CheckoutTokenizeController authorize `initiatePayment` on Event. |

---

## 8. Operational Risk Map

| Risk | Description | Mitigation / note |
|------|-------------|-------------------|
| **Wrong .env** | Artisan run from `/var/www/vhosts/kalfa.me` loads parent .env (e.g. stub gateway) | Lock doc: always run from `httpdocs`; check `base_path()` |
| **Webhook delay** | User sees "processing" until webhook arrives; long delay may confuse | Acceptable; webhook is source of truth; no timeout-based auto-activation |
| **Duplicate webhook** | Same payload delivered twice | Idempotency: terminal status check + 200 "Already processed"; gateway handler skips if already succeeded/failed |
| **SUMIT outage** | Charge or webhook unavailable | Single gateway; no fallback; operational dependency |
| **No retry** | Token charge failure returns 422; no automatic retry | By design; user can retry; no server-side retry |
| **Synchronous webhook** | Webhook handled in request; slow SUMIT or DB blocks response | No queue; 120/min throttle; risk of timeouts under load |
| **Race (double checkout)** | Two token submissions for same event | Event status draft → pending_payment on first; second would need same event; idempotency on payment side, not double activation |
| **gateway_transaction_id NULL** | Multiple payments with NULL until set | Allowed by DB; idempotency uses non-null transaction_id from webhook |

---

## 9. Observability

| Item | State |
|------|--------|
| **Structured logs** | Standard Laravel/Monolog; no standardized JSON structure or correlation ID in app code. |
| **Correlation IDs** | Not implemented. |
| **Error tracking** | No Sentry/Bugsnag etc. in app code. |
| **Payment tracing** | Possible via `gateway_transaction_id` and `billing_webhook_events`; no dedicated trace ID. |
| **Audit logs** | No dedicated audit table; webhook events stored in `billing_webhook_events`. |
| **Alerting** | Not implemented in codebase. |

---

## 10. Performance & Scaling Readiness

| Item | State |
|------|--------|
| **N+1** | EventController::show uses `$event->load([...])`. PublicRsvpController and InvitationController use `with()`. EventController::index returns paginated list without relations; no N+1 there. |
| **DB indexing** | Indexes on organization_id, status, event_id, payable, slug, etc. (see migrations). |
| **Query complexity** | Simple queries; no heavy joins audited. |
| **Transaction scope** | Billing transactions are short (create/update payment and billing, no long external calls inside transaction in token flow; gateway call before final update). |
| **Queue offload** | None; all synchronous. |
| **Blocking external calls** | SUMIT charge and redirect URL creation are blocking HTTP; webhook handling is blocking. |

---

## 11. Deployment Model

| Item | State |
|------|--------|
| **Deployment method** | Not defined in repo (likely manual or Plesk). |
| **Migration strategy** | Standard Laravel migrations; no custom rollback in code. |
| **Rollback** | Application rollback via deployment; DB rollback would be manual. |
| **Cache warmup** | Not implemented; config/cache clear documented. |
| **Feature flags** | None in code. |

---

## 12. System Maturity Classification

| Dimension | Classification | Justification |
|-----------|----------------|---------------|
| **Overall architecture** | **Production-grade** | Clear domains, single source of truth for payments (webhook), transactions, dual payment flows, documented production path. |
| **Payment** | **Production-grade** | Webhook-only success/failure, idempotency, token flow hardening, PCI rules (no card on server), validation. |
| **Operational** | **Beta** | No queues, no cron, no Horizon; single gateway; observability and alerting minimal. |
| **Security** | **Production-grade** | PCI-conscious, validation, rate limits, policies; CORS and error tracking not custom. |
| **Risk level** | **Moderate** | Dependency on one gateway and sync webhook handling; path/env discipline required. |

---

## 13. Risk Map Table

| ID | Risk | Likelihood | Impact | Area |
|----|------|------------|--------|------|
| R1 | Commands run from wrong directory (stub .env) | Low (if procedures followed) | High | Env |
| R2 | SUMIT prolonged outage | Low | High | Billing |
| R3 | Webhook timeout under load | Low–Moderate | Medium | Billing |
| R4 | No payment trace in logs | Medium | Low | Observability |
| R5 | Duplicate webhook delivery | Low | Low (idempotent) | Billing |
| R6 | No retry on token charge failure | By design | Low | Billing |

---

## 14. Immediate Red Flags

1. **Two Laravel roots:** Vhost root and `httpdocs`. If scripts or cron run artisan from vhost root, wrong .env (e.g. stub) is used. **Mitigation:** Strict rule and docs: run only from `httpdocs`; verify `base_path()`.  
2. **No background processing:** Webhook and checkout are synchronous. Under high load or slow SUMIT, timeouts and queue backlog possible if webhooks are later queued. **Not a code defect;** architectural choice.  
3. **Code-analyzer:** Referenced skill (`.claude/tools/analysis/project-analyzer/analyzer.mjs`) is not present in this repo; audit is manual.

No critical code flaws identified; red flags are environment/discipline and operational design.

---

## 15. What Is Stable vs Fragile vs Production-Ready

- **Stable:** Multi-tenant model, event/guest/table/invitation/RSVP model, BillingService and gateway abstraction, webhook idempotency, payment state machine, auth and policies, production path lock, rate limits, PCI request rules.  
- **Fragile:** Reliance on single gateway, sync webhook handling, correct working directory for artisan, no correlation IDs.  
- **Production-ready:** RSVP public flow, event CRUD, checkout (redirect + token), webhook handling, token flow hardening (webhook-only success), docs (path lock, validation checklist, PCI/SAQ).  
- **Prototype-level:** No scheduled jobs, no queue usage, no CORS file, no structured observability or alerting.

---

## 16. Full directory tree

Full project tree (Laravel root: `httpdocs`). Excludes: `node_modules`, `vendor`, `.git`, `storage/logs`, `storage/framework/views`, `bootstrap/cache`. Generated with:

```bash
tree -a -I 'node_modules|vendor|.git|storage/logs|storage/framework/views|bootstrap/cache' --dirsfirst -L 4
```

Same content is saved in **`docs/tree-full.txt`** (812 lines). Inline copy below.

<details>
<summary>Expand full tree (210 directories, 600 files)</summary>

```
.
├── app
│   ├── Contracts
│   │   └── PaymentGatewayInterface.php
│   ├── Enums
│   │   ├── EventBillingStatus.php
│   │   ├── EventStatus.php
│   │   ├── InvitationStatus.php
│   │   ├── OrganizationUserRole.php
│   │   ├── PaymentStatus.php
│   │   └── RsvpResponseType.php
│   ├── Guards
│   │   ├── Appointments
│   │   ├── Checkout
│   │   ├── Login
│   │   └── Registration
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Api
│   │   │   ├── Auth
│   │   │   ├── Workflows
│   │   │   ├── CheckoutTokenizeController.php
│   │   │   └── Controller.php
│   │   └── Requests
│   │       └── Api
│   ├── Livewire
│   │   ├── Actions
│   │   ├── Appointments
│   │   ├── Checkout
│   │   ├── Forms
│   │   ├── Login
│   │   ├── Registration
│   │   └── Traits
│   ├── Models
│   ├── Policies
│   ├── Providers
│   ├── Services
│   │   ├── Sumit
│   │   ├── BillingService.php
│   │   ├── StubPaymentGateway.php
│   │   └── SumitPaymentGateway.php
│   └── View
│       └── Components
├── bootstrap
├── config
├── database
│   ├── factories
│   ├── migrations
│   ├── seeders
│   └── database.sqlite
├── docs
├── git
├── landing-backup
├── public
│   ├── build
│   ├── css
│   ├── fonts
│   ├── images
│   ├── js
│   ├── .htaccess
│   ├── index-laravel.php
│   └── ...
├── resources
│   ├── css
│   ├── filament-icons
│   ├── js
│   ├── lang
│   ├── svg
│   └── views
│       ├── appointments
│       ├── auth
│       ├── checkout
│       │   └── tokenize.blade.php
│       ├── components
│       ├── errors
│       ├── layouts
│       ├── livewire
│       ├── public
│       └── ...
├── routes
│   ├── api.php
│   ├── auth.php
│   ├── console.php
│   ├── web.php
│   └── workflows.php
├── storage
│   ├── app
│   ├── framework
│   └── logs
├── tests
│   ├── Feature
│   ├── Unit
│   └── TestCase.php
├── .env
├── .env.example
├── artisan
├── composer.json
├── composer.lock
└── phpunit.xml
```

</details>

**Full listing (all 812 lines):** see **`docs/tree-full.txt`**.

---

**End of audit. No code or config was changed.**
