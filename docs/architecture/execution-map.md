# Execution Map — Phase 2 Architecture Discovery

> Generated: 2026-03-23 | Purpose: **Validate Phase 1 hypotheses against actual execution flow**

---

## Q1: Is Account really a runtime hub between Product Engine and SUMIT?

### Execution trace

```
Account model (337 lines, 14 relationships)
├── Product Engine side:
│   ├── →  accountProducts()      → AccountProduct
│   ├── →  activeAccountProducts()
│   ├── →  entitlements()         → AccountEntitlement
│   ├── →  featureUsage()         → AccountFeatureUsage
│   ├── →  subscriptions()        → AccountSubscription
│   ├── →  activeSubscriptions()
│   ├── →  billingIntents()       → BillingIntent
│   ├── method: grantProduct()    → uses FeatureResolver, SubscriptionManager
│   ├── method: overrideFeature() → uses FeatureResolver
│   ├── method: subscribeToPlan() → uses SubscriptionManager
│   ├── method: startTrial()      → uses SubscriptionService
│   └── method: hasBillingAccess()→ checks products + subscriptions + trials
│
├── SUMIT side:
│   ├── implements HasSumitCustomer
│   ├── →  paymentMethods()       → OfficeGuyToken (morphMany — vendor model)
│   ├── →  payments()             → Payment
│   ├── →  eventsBilling()        → EventBilling
│   ├── method: getSumitCustomerEmail/Name/Phone() → delegates to owner User
│   └── trait: HasSumitCustomerTrait (vendor)
│
└── Tenancy side:
    ├── →  owner()                → User (owner_user_id)
    └── →  organizations()        → Organization (account_id FK on org)
```

### Runtime call paths into Account

```
EnsureAccountActive middleware
  → $org->account                    ← every request on protected routes
  → $account->hasBillingAccess()     ← checks Product Engine tables
  → $account->subscriptions()        ← checks Product Engine tables

System/Accounts/Show (Livewire)
  → $account->paymentMethods()       ← reads SUMIT vendor table
  → $account->entitlements()         ← reads Product Engine table
  → $account->grantProduct()         ← writes Product Engine tables

AccountPaymentMethodController
  → $account->paymentMethods()       ← reads/writes SUMIT vendor table
```

### Verdict: **CONFIRMED — Account IS a real runtime hub**

Phase 1 was not inflating this. Account is a *runtime coupling point* between Product Engine and SUMIT, not just a static relationship. The `EnsureAccountActive` middleware hits it on **every protected web request**. The `hasBillingAccess()` method touches Product Engine tables (subscriptions, account products, trials) and is cached for 60 seconds.

**Criticality**: Account is the **billing gate** for the entire tenant experience. If you refactor Account without understanding both sides, you break the middleware and every protected route stops working.

**Structural observation**: Account → Organization is `hasMany` (1 account → N organizations). This is the intended hierarchy: Account is the billing entity, Organization is the tenancy entity. The dual `HasSumitCustomer` on both is **legacy — Organization delegates to Account via `getSumitCustomerId()`**:

```php
// Organization.php line 33
public function getSumitCustomerId(): ?int
{
    return $this->sumit_customer_id ?? $this->account?->sumit_customer_id;
}
```

This means Organization's `HasSumitCustomer` is a **fallback proxy**, not a competing owner. The **Account IS the billing entity**. Organization only implements the interface as a convenience wrapper.

---

## Q2: Where does OrganizationContext enter the execution chain?

### Execution trace

```
OrganizationContext (85 lines, 5 methods)
├── set()       → writes User.current_organization_id + session mirror
├── setById()   → same, by ID
├── current()   → reads User->currentOrganization (DB relationship)
├── clear()     → clears session
└── validateMembership() → checks pivot table
```

### Where it's called from

```
NOT middleware — OrganizationContext is NOT in the middleware chain.

Middleware uses User->currentOrganization directly:
  EnsureOrganizationSelected → $user->currentOrganization  (line 38)
  EnsureAccountActive        → $user->currentOrganization  (line 54, fallback)

OrganizationContext is called from:
  Controllers:
    OrganizationSwitchController   → OrgContext::set()        (user switches org)
    DashboardController            → OrgContext::current()    (reads active org)
    EventController (Dashboard)    → OrgContext::current()    (scopes events)
    EventGuestsController          → OrgContext::current()
    EventTablesController          → OrgContext::current()
    EventInvitationsController     → OrgContext::current()
    EventSeatAssignmentsController → OrgContext::current()
    OrganizationSettingsController → OrgContext::current()

  Livewire:
    Dashboard.php                  → OrgContext::current()
    OrganizationMembers            → OrgContext::current()
    System/Dashboard               → OrgContext (for impersonation display)
    System/Organizations/Show      → OrgContext::set() (impersonation)
    Billing/AccountOverview        → OrgContext::current()
    Billing/UsageIndex             → OrgContext::current()
```

### Verdict: **CLARIFIED — OrganizationContext is a service-layer concern, not middleware**

Phase 1 said "14 direct usages" which is correct, but the characterization needs nuance:
- It's a **read helper** in 12/14 cases (just `current()` to get the org)
- It's a **write action** in 2/14 cases (switching org, impersonation)
- The middleware (`EnsureOrganizationSelected`) does NOT use `OrganizationContext` — it reads `User->currentOrganization` directly

**Risk assessment revised**: OrganizationContext is safe to refactor/rename. The dangerous coupling is actually `User->currentOrganization` (the Eloquent relationship) + `EnsureOrganizationSelected` middleware. OrganizationContext is just a convenience service around it.

---

## Q3: Payment flow execution path

```
INITIATION (two paths):

Path A — Redirect flow:
  POST /api/organizations/{org}/events/{event}/checkout
  → auth:sanctum middleware
  → CheckoutController::initiate()
  → Gate::authorize('initiatePayment', $event)     ← EventPolicy
  → $event->requiresPerEventPayment()              ← business rule
  → BillingService::initiateEventPayment()
    → DB::transaction
    → Event.status = PendingPayment
    → creates EventBilling (org_id, event_id, plan_id, amount)
    → creates Payment (org_id, amount, gateway='stub'|'sumit')
    → PaymentGatewayInterface::createOneTimePayment()
      ├── StubPaymentGateway: returns fake redirect_url
      └── SumitPaymentGateway: → EventBillingPayable → PaymentService::processCharge()
          → returns {redirect_url, transaction_id}
  → response: {redirect_url, event_billing_id, payment_id}

Path B — Token flow:
  Same route, with `token` field in request
  → BillingService::initiateEventPaymentWithToken()
    → Same DB setup
    → PaymentGatewayInterface::chargeWithToken()
      └── SumitPaymentGateway: → PaymentService::processCharge(token=...)
    → Payment.status = Processing (if success) or Failed
  → response: {status: 'processing', payment_id}

COMPLETION (webhook):
  POST /api/webhooks/{gateway}
  → throttle middleware (no auth)
  → WebhookController::handle()
  → HMAC signature verification (if BILLING_WEBHOOK_SECRET set)
  → Idempotency check: skip if Payment already succeeded/failed
  → creates BillingWebhookEvent (audit log)
  → PaymentGatewayInterface::handleWebhook()
    └── SumitPaymentGateway::handleWebhook()
      → finds Payment by gateway_transaction_id
      → normalizeWebhookStatus() → bool
      → BillingService::markPaymentSucceeded()
        → Payment.status = Succeeded
        → EventBilling.status = Paid, paid_at = now
        → Event.status = Active                         ← THE STATE TRANSITION
      OR → BillingService::markPaymentFailed()
        → Payment.status = Failed

STATUS CHECK:
  GET /api/payments/{payment}
  → auth:sanctum
  → PaymentController::show()
  → returns Payment with status
```

### Key finding: Clean adapter pattern already exists

```
CheckoutController → BillingService → PaymentGatewayInterface
                                              ↓
                              ┌───────────────┴───────────────┐
                              │                               │
                     StubPaymentGateway              SumitPaymentGateway
                     (dev/test)                      (production)
                                                          ↓
                                              EventBillingPayable (adapter)
                                                          ↓
                                              vendor PaymentService::processCharge()
```

### Verdict: **Payment flow is well-isolated**

BillingService depends ONLY on `PaymentGatewayInterface` (contract). It never touches SUMIT internals directly. The `SumitPaymentGateway` is a thin adapter (184 lines). This is already a proper seam.

**What Phase 1 called "integration blob"**: The blob is NOT in the payment flow. It's in the CRM side (OfficeGuyCustomerSearchService, DocumentService, etc.) which is **not in the payment execution path at all**.

---

## Q4: RSVP Core execution paths

### Path 1: API RSVP (mobile/external)

```
GET /api/rsvp/{slug}           ← no auth, throttled
  → PublicRsvpController::showBySlug()
  → Invitation::where('slug', $slug)->with(['event', 'guest'])
  → checks Event.status === Active
  → returns: {slug, event_name, event_date, venue_name, guest_name}

POST /api/rsvp/{slug}/responses  ← no auth, throttled
  → StoreRsvpResponseRequest (validates response, attendees_count)
  → PublicRsvpController::storeResponse()
  → DB::transaction
    → RsvpResponse::updateOrCreate (idempotent per invitation+guest)
    → Invitation.status = Responded, responded_at = now
  → returns: {success, response}
```

### Path 2: Web RSVP (public page)

```
GET /rsvp/{slug}               ← no auth
  → PublicRsvpViewController::show()
  → renders rsvp view

POST /rsvp/{slug}/responses    ← no auth
  → PublicRsvpViewController::store()
  → same logic as API path
```

### Path 3: Dashboard management

```
GET /dashboard/events/{event}/guests        ← auth + ensure.org + ensure.account_active
  → EventGuestsController::index()
  → OrgContext::current() → scope to org
  → renders Livewire EventGuests component

GET /dashboard/events/{event}/invitations   ← same middleware
  → EventInvitationsController::index()
  → renders Livewire EventInvitations component

GET /dashboard/events/{event}/tables        ← same middleware
  → EventTablesController::index()
  → renders Livewire EventTables component
```

### Path 4: Voice RSVP (Twilio)

```
POST /twilio/calling/initiate              ← auth + ensure.org
  → CallingController::call()
  → CallingService → TwilioClient → initiates outbound call
  → call TwiML URL points to:

GET|POST /twilio/rsvp/connect              ← no auth (Twilio webhook)
  → RsvpVoiceController::connect()
  → builds TwiML with <Connect><Stream> pointing to voice-bridge
  → voice-bridge (Node.js) connects to Gemini Live API
  → Gemini calls save_rsvp tool → POSTs to:

POST /api/twilio/rsvp/process              ← no auth (internal webhook)
  → RsvpVoiceController::process()
  → creates/updates RsvpResponse (same model as API/Web)

POST /twilio/calling/status                ← no auth (Twilio webhook)
  → CallingController::statusCallback()
  → on no-answer/short-call → WhatsAppRsvpService fallback
```

### Verdict: **RSVP Core is clean but has 4 entry points converging on 1 model**

All paths write to `RsvpResponse` and update `Invitation.status`. The model is simple and the convergence is intentional. No boundary issues.

**Note**: The voice path adds the only coupling between RSVP Core and voice-bridge (via HTTP webhook). This is a clean protocol-level boundary.

---

## Q5: OfficeGuy/SUMIT — execution surface vs DB surface

### DB surface: 18 `officeguy_*` tables + ~10 app billing tables = 28 tables

### Execution surface (what actually runs):

```
PAYMENT PATH (active, well-structured):
  SumitPaymentGateway          → vendor PaymentService     (thin adapter)
  EventBillingPayable          → vendor Payable contract   (data mapper)
  StubPaymentGateway           → no vendor dependency      (dev stub)
  BillingService               → PaymentGatewayInterface   (contract only)

ACCOUNT MANAGEMENT (active, admin-only):
  AccountPaymentMethodManager  → vendor OfficeGuyToken     (CRUD tokens)
  AccountPaymentMethodController → above                    (3 routes)

CRM/CUSTOMER SEARCH (active, complex):
  OfficeGuyCustomerSearchService (543 lines, 39 functions)
    → queries local officeguy_crm_entities table
    → queries local officeguy_tokens table
    → matches customers across multiple criteria
    → USED BY: System/Accounts/Show (admin panel)

DOCUMENTS (stub):
  DocumentService              → 5 functions, called from admin panel
  SystemBillingService         → 11 functions, ALL return stub values

SUBSCRIPTION BILLING (active but internal):
  SumitBillingProvider         → vendor PaymentService
  SumitUsageChargePayable      → vendor Payable contract
  SubscriptionService          → manages local subscription lifecycle
  SubscriptionSyncService      → syncs vendor ↔ local state
  CreditService                → manages account credits (agorot)
  CouponService                → coupon validation + redemption
```

### Execution surface ratio

| Category | Tables | Runtime files | Active? |
|---|---|---|---|
| Payment (redirect+token) | 3 (`payments`, `events_billing`, `billing_webhook_events`) | 4 services | **Active — production** |
| Token management | 1 (`officeguy_tokens`) | 1 service | **Active — admin** |
| CRM/Customer | 7 (`crm_*` tables) | 1 service (543 lines) | **Active — admin search** |
| Documents | 3 (`officeguy_documents`, `document_subscription`, `officeguy_settings`) | 1 service (stub) | **Stub — no real execution** |
| Subscriptions | 1 (`officeguy_subscriptions`) | 3 services | **Active — internal** |
| Transactions | 1 (`officeguy_transactions`) | 0 services (vendor-managed) | **Vendor-only** |
| Webhooks/debt | 3 (`officeguy_sumit_webhooks`, `webhook_events`, `debt_attempts`) | 0 services | **Vendor-only** |
| Vendor credentials | 1 (`officeguy_vendor_credentials`) | 0 services | **Config — rarely touched** |

### Verdict: **CONFIRMED — execution surface is much smaller than DB surface**

Of 18 officeguy tables:
- **4 are actively used by app code** (tokens, CRM entities, subscriptions, documents-stub)
- **14 are vendor-managed** (transactions, webhooks, debt, credentials, settings, CRM fields/relations/views/activities/folders)

The "blob" is real but it's mostly **vendor table sprawl**, not app code sprawl. The app's SUMIT surface is actually only 8 service files totaling ~2,200 lines. The payment path itself is clean (adapter pattern).

**Revised characterization**: OfficeGuy/SUMIT is not one blob — it's **3 distinct integration concerns**:
1. **Payment adapter** (clean, well-isolated via contract)
2. **CRM customer search** (complex but self-contained, 1 file)
3. **Vendor table management** (vendor-owned, no app code)

---

## Q6: Is voice-bridge truly isolated?

### Coupling points found

```
PHP → voice-bridge:
  1. config/services.php line 48:
     'rsvp_node_ws_url' => env('RSVP_NODE_WS_URL', 'wss://voice-bridge.kalfa.me/media')
     Used by RsvpVoiceController::connect() to generate TwiML <Stream> URL

voice-bridge → PHP:
  2. Node.js POSTs to: POST /api/twilio/rsvp/process (RsvpVoiceController::process)
     Sends: guest_id, event_id, invitation_id, response, attendees_count
  3. Node.js POSTs to: POST /api/twilio/calling/log (CallingController::appendLog)
     Sends: call logs

Config coupling:
  4. .env: RSVP_NODE_WS_URL (PHP reads)
  5. voice-bridge/.env: PHP_WEBHOOK, CALL_LOG_URL (Node reads)

Deploy coupling:
  6. voice-bridge/Dockerfile exists → separate container
  7. No shared deploy script found — deployed independently

Filesystem coupling:
  8. NONE — voice-bridge has its own node_modules, package.json, src/
```

### Verdict: **CONFIRMED — voice-bridge is isolated**

Coupling is **protocol-only** (HTTP webhooks + WebSocket URL in config). No shared code, no shared state, no shared filesystem. Extraction to a separate repo would require only:
1. Move `voice-bridge/` to its own repo
2. Keep the `RSVP_NODE_WS_URL` env var pointing to the deployed URL
3. Keep the webhook endpoints in `routes/api.php`

**Risk**: Zero. This is already a microservice in practice.

---

## Q7: System/Products/Show.php — god component or orchestration hub?

### Analysis

```
1,037 lines | 48 public methods | 35 public properties

Models directly used:
  Product, ProductPlan, ProductPrice, ProductEntitlement, ProductFeature, ProductLimit, UsageRecord

Services used:
  ProductIntegrityChecker (validation only)

What it does:
  - CRUD for Product (edit name/slug/description/category/status)
  - CRUD for ProductEntitlement (add/edit/toggle/delete + constraint management)
  - CRUD for ProductLimit (add/edit/toggle/delete)
  - CRUD for ProductFeature (add/edit/toggle/delete)
  - CRUD for ProductPlan (add/edit/toggle/delete/reorder + SKU/voice limits/overage config)
  - CRUD for ProductPrice (add/edit/toggle/delete per plan)
  - Integrity check display
  - Tree event handling (#[On('tree:*')] dispatches)
```

### Verdict: **God component — 6 CRUD forms in 1 component**

This is not an orchestration hub (it doesn't coordinate other components). It's a **monolithic form component** that handles CRUD for 6 different Product Engine sub-entities. Each sub-entity (entitlements, limits, features, plans, prices) has its own add/edit/delete/toggle cycle with separate form state.

**Should be**: 6 smaller Livewire components, one per entity, composed in the view.

**However**: This is an admin-only component behind `system.admin` middleware. It has zero runtime impact on tenant users. Refactoring it is a **quality improvement**, not a **risk reduction**.

---

## Validated / Disputed / New Findings

### VALIDATED (Phase 1 confirmed)

| # | Phase 1 Hypothesis | Evidence |
|---|---|---|
| 1 | Account is runtime hub between Product Engine and SUMIT | `EnsureAccountActive` middleware hits Account on every protected request. Account has 14 relationships spanning both domains. |
| 2 | Product Engine is a standalone bounded context | 13 models, 9 services, 4 commands, clean internal cohesion. Only coupling point is Account. |
| 3 | voice-bridge is isolated | Protocol-only coupling (HTTP + WebSocket URL). Own Dockerfile, own package.json. Zero shared code. |
| 4 | OfficeGuy/SUMIT has large DB surface vs small execution surface | 18 vendor tables, only 4 actively used by app code. 14 are vendor-managed with no app service layer. |
| 5 | System/Products/Show.php is oversized | 1,037 lines, 48 methods, 6 CRUD forms. God component pattern. |

### DISPUTED (Phase 1 needs correction)

| # | Phase 1 Claim | Correction |
|---|---|---|
| 1 | "Account ↔ Organization dual HasSumitCustomer = boundary mismatch" | **Not a mismatch — it's a delegation chain.** Organization.getSumitCustomerId() falls back to Account. Account IS the billing entity. Organization is a proxy. The dual implementation is intentional hierarchy, not ambiguity. |
| 2 | "OrganizationContext is the #1 coupling hotspot (14 usages)" | **Overstated.** OrganizationContext is a read-only convenience service in 12/14 cases. The real coupling is `User->currentOrganization` (Eloquent). OrganizationContext is safe to refactor. |
| 3 | "OfficeGuy/SUMIT is an integration blob" | **Partially correct but needs decomposition.** It's actually 3 distinct concerns: (a) payment adapter (clean), (b) CRM customer search (complex but contained), (c) vendor table sprawl (not our code). The "blob" perception comes from table count, not code coupling. |

### NEW FINDINGS

| # | Finding | Impact |
|---|---|---|
| 1 | **`EnsureAccountActive` is the real billing gate** — middleware that checks Account.hasBillingAccess() on every protected route. This is the most critical runtime path between Product Engine and tenancy. | Any change to Account, subscriptions, or billing access logic must be tested through this middleware. |
| 2 | **Account → Organization is 1:N** (Account hasMany Organizations). This settles the ownership question: Account is the billing entity, Organization is the tenancy entity. Account sits above Organization in the hierarchy. | The restructure question is NOT "which one owns billing" — it's already decided. The question is whether this hierarchy is visible enough in code. |
| 3 | **Payment flow already has proper adapter pattern** — `PaymentGatewayInterface` → `SumitPaymentGateway` → vendor. No refactoring needed here. | Safe zone — don't touch. |
| 4 | **4 RSVP entry points converge correctly** — API, Web, Dashboard, and Voice all write to the same `RsvpResponse` model with the same semantics. | Clean convergence. No action needed. |
| 5 | **`EnsureAccountActive` resolves org from 3 sources** (route param, event relation, session) via match expression. This is well-structured but non-obvious. | Document this resolution order explicitly. |
| 6 | **Routes have clear middleware layering**: `auth → verified → ensure.organization → ensure.account_active`. Each layer adds a gate. System admin routes bypass all tenant middleware entirely. | Clean architecture. Preserve this layering in any restructure. |

---

## Decision-Ready Answers

### What can be moved NOW?

| Item | Action | Confidence |
|---|---|---|
| `voice-bridge/` | Extract to separate repo | **HIGH** — protocol-only coupling |
| `documentation/`, `obsidian-*`, `archive/`, `nativephp/`, `var/` | Remove from repo | **HIGH** — zero runtime references |
| `credentials/` | Move to secure vault | **CRITICAL** — security remediation |
| Root `.md` audit files | Move to `docs/archive/` | **HIGH** — no runtime refs |

### What MUST NOT be moved without deeper analysis?

| Item | Why |
|---|---|
| `Account` model | Runtime billing gate — every protected request flows through it |
| `EnsureAccountActive` middleware | Billing gate — resolves org from 3 sources |
| `BillingService` + `PaymentGatewayInterface` | Payment flow adapter — clean but critical |

### What needs conceptual decision FIRST?

| Decision | Context |
|---|---|
| Should Product Engine become a Laravel package? | It's ready structurally. Only coupling is Account. But: do you want `packages/kalfa/product-engine/` or just better namespace organization within `app/`? Package extraction adds dependency management overhead. |
| Should OfficeGuy CRM tables have local models? | Currently vendor-managed. Adding models gives you Eloquent relationships and policies. But: do you need them, or is the vendor service layer sufficient? |

### What justifies extraction?

| Candidate | Type | Justification |
|---|---|---|
| Product Engine | **Extraction** (package) | 13 models, 9 services, clean boundary, single coupling point |
| voice-bridge | **Already extracted** (separate runtime) | Just needs repo separation |

### What justifies isolation only (not extraction)?

| Candidate | Type | Justification |
|---|---|---|
| SUMIT payment adapter | **Isolation** (adapter seam) | Already isolated via contract. Keep seam clean. |
| CRM customer search | **Isolation** (service boundary) | 1 large file, self-contained. Could become `App\Services\Crm\` namespace. |
| System admin panel | **Isolation** (namespace only) | Cross-cutting aggregate — can't extract, but can namespace under `App\Livewire\System\` (already done). |

---

## Gate Status

- [x] Inventory (Phase 1) ✓
- [x] Execution map (Phase 2) ✓ — this document
- [ ] Project context (Phase 3 — `bmad-document-project`)
- [ ] Architecture review (Phase 4)

**Phase 2 success criteria check:**
- [x] What can be moved now — answered
- [x] What must not be moved — answered
- [x] What needs conceptual decision first — answered
- [x] What justifies extraction — answered
- [x] What justifies only isolation — answered
