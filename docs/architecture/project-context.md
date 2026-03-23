# Project Context — Kalfa RSVP + Seating SaaS

> Canonical architecture contract for agents and refactoring decisions.
> Based on validated Phase 1 (inventory) and Phase 2 (execution map).
> Last validated: 2026-03-23.

---

## 1. System Shape

**Primary runtime**: Laravel 12 monolith (PHP 8.4, PostgreSQL, Livewire 4, Tailwind v4).
**Secondary runtime**: `voice-bridge/` — Node.js WebSocket relay (Twilio Media Stream ↔ Gemini Live API). Separate process, own Dockerfile, protocol-only coupling.

```
Internet
  ├── Laravel (PHP) ─── PostgreSQL
  │     ├── Web UI (Livewire + Alpine.js)
  │     ├── REST API (Sanctum auth)
  │     ├── Public RSVP (no auth)
  │     ├── Twilio webhooks
  │     └── SUMIT webhooks
  │
  └── voice-bridge (Node.js) ─── Gemini Live API
        ├── Twilio Media Stream (WebSocket in)
        └── HTTP callbacks to Laravel (out)
```

---

## 2. Runtime-Critical Surface

### Middleware chain (request order)

```
SecurityHeaders → RequestId → ImpersonationExpiry → SpatiePermissionTeam
  → auth → verified → EnsureOrganizationSelected → EnsureAccountActive
```

`EnsureAccountActive` is the **billing gate**. It resolves organization from 3 sources (route param → event relation → session) and checks `Account.hasBillingAccess()`. Every protected tenant route passes through it.

### Critical controllers

| Controller | Path | Why critical |
|---|---|---|
| `Api\CheckoutController` | `POST /api/.../checkout` | Payment initiation |
| `Api\WebhookController` | `POST /api/webhooks/{gateway}` | Payment completion (state transitions) |
| `Api\PublicRsvpController` | `GET/POST /api/rsvp/{slug}` | Public-facing RSVP (no auth) |
| `Twilio\RsvpVoiceController` | `POST /twilio/rsvp/connect` | TwiML for voice RSVP |
| `Twilio\CallingController` | `POST /twilio/calling/initiate` | Outbound call initiation |
| `Dashboard\EventController` | `/dashboard/events/*` | Core tenant CRUD |

### Critical services

| Service | Why |
|---|---|
| `BillingService` | Orchestrates payment lifecycle (initiate → succeed/fail) |
| `OrganizationContext` | Resolves active tenant (convenience layer over `User->currentOrganization`) |
| `FeatureResolver` | Resolves feature availability per account (cached) |
| `CallingService` | Twilio call initiation |

### Critical models

| Model | Role |
|---|---|
| `Account` | Billing entity. Hub between Product Engine and SUMIT. |
| `Organization` | Tenancy scope. Delegates billing to Account. |
| `User` | Auth + `current_organization_id` (tenancy source of truth). |
| `Event` | Core product entity. Status machine: Draft → PendingPayment → Active → Completed. |
| `Payment` | Payment state machine: Pending → Processing → Succeeded/Failed. |

---

## 3. Bounded Contexts

### RSVP Core
- **Responsibility**: Event management, guest lists, invitations, RSVP responses, seating.
- **Models**: Event, Guest, Invitation, RsvpResponse, EventTable, SeatAssignment.
- **Controllers**: EventController (API+Dashboard), GuestController, InvitationController, SeatAssignmentController, PublicRsvpController.
- **Verdict**: Clean. 4 entry points converge correctly on RsvpResponse.

### Multi-Tenancy & Auth
- **Responsibility**: User auth, organization membership, role-based access, impersonation.
- **Models**: User, Organization, OrganizationUser, OrganizationInvitation.
- **Services**: OrganizationContext, OrganizationMemberService, PermissionSyncService.
- **Middleware**: EnsureOrganizationSelected, EnsureSystemAdmin, ImpersonationExpiry.
- **Verdict**: Stable. OrganizationContext is a convenience wrapper, not a risk.

### Product Engine
- **Responsibility**: Product catalog, plans, pricing, entitlements, feature flags, usage metering, trials, subscriptions, credits.
- **Models**: Product, ProductPlan, ProductPrice, ProductEntitlement, ProductFeature, ProductLimit, Account, AccountProduct, AccountSubscription, AccountEntitlement, AccountFeatureUsage, AccountCreditTransaction, UsageRecord.
- **Services**: FeatureResolver, UsageMeter, UsagePolicyService, SubscriptionManager, SubscriptionService, SubscriptionSyncService, CreditService, ProductIntegrityChecker, ProductEngineOperationsMonitor.
- **Commands**: CheckIntegrityCommand, ProcessTrialExpirationsCommand, ProcessProductExpirationsCommand, ProductEngineHealthCommand.
- **Events**: ProductEngineEvent, SubscriptionCancelled, TrialExtended.
- **Verdict**: Strongest bounded context. Single coupling point: Account. Extraction candidate.

### Payment Adapter (SUMIT)
- **Responsibility**: Translate app billing intent into SUMIT gateway calls.
- **Contract**: `PaymentGatewayInterface` (createOneTimePayment, chargeWithToken, handleWebhook).
- **Implementations**: SumitPaymentGateway (production), StubPaymentGateway (dev).
- **Adapters**: EventBillingPayable, SumitUsageChargePayable.
- **Verdict**: Clean. Proper adapter pattern. Do not refactor.

### CRM Customer Search
- **Responsibility**: Match and search customers across SUMIT CRM entities.
- **Service**: OfficeGuyCustomerSearchService (543 lines, 39 functions).
- **Tables**: 7 `officeguy_crm_*` tables (vendor-managed).
- **Used by**: System admin panel only.
- **Verdict**: Complex but self-contained. Acceptable isolation. Could move to `App\Services\Crm\` namespace.

### Voice Integration
- **Responsibility**: Automated voice RSVP calls via Twilio + Gemini Live API.
- **PHP side**: RsvpVoiceController (TwiML generation), CallingController (call initiation), CallingService.
- **Node side**: `voice-bridge/` (WebSocket relay, Gemini connection, save_rsvp tool).
- **Coupling**: HTTP webhooks + WebSocket URL in config. No shared code.
- **Verdict**: Protocol-isolated. Already a microservice in practice.

### System Admin Panel
- **Responsibility**: System-wide management (users, organizations, accounts, products, coupons, settings, impersonation).
- **Livewire**: System/Dashboard, System/Users/*, System/Organizations/*, System/Products/*, System/Accounts/*, System/Coupons/*, System/Settings/*.
- **Middleware**: `system.admin` (bypasses all tenant middleware).
- **Verdict**: Cross-cutting aggregate. Cannot extract. Namespace isolation already in place.

---

## 4. Critical Architectural Truths

1. **Account is the billing entity. Organization is the tenancy scope.** Account → Organization is 1:N. Organization delegates SUMIT customer ID to Account. This is settled hierarchy, not ambiguity.

2. **`EnsureAccountActive` is the billing gate.** It checks `Account.hasBillingAccess()` on every protected tenant route. Breaking this middleware breaks the entire tenant experience.

3. **Payment flow is isolated via `PaymentGatewayInterface`.** BillingService never touches SUMIT internals. SumitPaymentGateway is a thin adapter. This is the cleanest architectural seam in the codebase.

4. **Product Engine is a bounded context with a single coupling point.** 13 models, 9 services, 4 commands, 8 enums. The only external dependency is Account. Everything else is internal.

5. **Voice system is protocol-isolated.** PHP generates TwiML. Node.js handles WebSocket relay. Communication is HTTP webhooks only. Zero shared code or state.

6. **OfficeGuy/SUMIT is 3 distinct concerns, not 1 blob.** Payment adapter (clean), CRM search (complex but contained), vendor table sprawl (not app code). Treat each separately.

7. **RSVP Core has 4 entry points (API, Web, Dashboard, Voice) converging on 1 model.** All paths write to RsvpResponse and update Invitation.status. Convergence is intentional and correct.

8. **Middleware layering is clean and ordered.** `auth → verified → ensure.organization → ensure.account_active`. System admin routes bypass tenant middleware entirely. Preserve this in any restructure.

---

## 5. Integration Seams

| Boundary | Mechanism | Status |
|---|---|---|
| App ↔ SUMIT Payments | `PaymentGatewayInterface` contract | **clean** |
| App ↔ voice-bridge | HTTP webhooks + WebSocket URL config | **clean** |
| App ↔ Twilio | TwiML responses + status webhooks | **clean** |
| App ↔ SUMIT CRM tables | Vendor package models (no local models) | **acceptable** — vendor owns schema |
| App ↔ SUMIT Tokens | `OfficeGuyToken` morphMany via Account | **acceptable** — vendor model, app uses relationship |
| Account ↔ Product Engine | Direct Eloquent relationships + service calls | **needs isolation** — candidate for package boundary contract |
| Products/Show.php ↔ 6 sub-entities | All CRUD in 1 component | **needs isolation** — decompose into child components |

---

## 6. Refactor Policy

### Safe to move NOW

| Item | Action |
|---|---|
| `documentation/` (508 MB) | Separate repo or external storage |
| `obsidian-hub/` (51 MB) | Remove from repo |
| `obsidian-claude-pkm/` (516 KB) | Remove from repo |
| `archive/` (396 MB) | Separate repo or branch |
| `nativephp/` (38 MB) | Separate repo |
| `var/` (32 KB) | Delete |
| Root audit `.md` files | Move to `docs/archive/` |

### Safe to isolate

| Item | Action |
|---|---|
| `OfficeGuyCustomerSearchService` | Move to `App\Services\Crm\` namespace |
| System admin Livewire components | Already namespaced under `App\Livewire\System\` — no action needed |
| `voice-bridge/` | Separate git repo (protocol coupling only) |

### Extraction candidates

| Item | Justification | Coupling point |
|---|---|---|
| Product Engine | 13 models, 9 services, 4 commands, 8 enums. Clean internal cohesion. | Account model (single point) |

### High-risk — DO NOT TOUCH without execution map review

| Item | Why |
|---|---|
| `Account` model | Billing gate runtime dependency. 14 relationships, hasBillingAccess() cached. |
| `EnsureAccountActive` middleware | Resolves org from 3 sources. Every protected route depends on it. |
| `BillingService` + `PaymentGatewayInterface` | Payment state machine. Clean but critical. |
| `routes/api.php` | Public API contract. Changes break mobile + external integrations. |
| `User->currentOrganization` relationship | Tenancy source of truth. Middleware depends on it. |

### Immediate fixes required

| Item | Priority | Type |
|---|---|---|
| `credentials/` directory in repo | **P0** | Security — iOS signing keys, certs, private keys exposed |

---

## 7. Known Technical Debt

| Issue | Scope | Impact |
|---|---|---|
| `credentials/` in git | Security | P8/p12/key files committed. Must move to secure vault. |
| `System/Products/Show.php` | Quality | 1,037 lines, 48 methods, 6 CRUD forms in 1 component. Admin-only — no user-facing impact. |
| 10.9 GB repo noise | DX | 99.94% of disk is non-application code. Slows clones, confuses analysis. |
| Test coverage gaps | Risk | ~35 test files for ~150 runtime files. `EnsureAccountActive`, `BillingService`, and payment webhook path are under-tested relative to criticality. |
| `DocumentService` + `SystemBillingService` | Dead code | All methods return stub values. Either implement or remove. |

---

## 8. Open Decisions

| Decision | Tradeoff |
|---|---|
| **Product Engine: extract to `packages/kalfa/product-engine/` vs keep in `app/` with better namespacing** | Package extraction gives clean boundary + independent testing. But: adds Composer autoload complexity, cross-package migrations, and release coordination overhead for a single-team project. |
| **OfficeGuy CRM: add local Eloquent models vs keep vendor-only** | Local models give relationships, policies, and query scopes. But: creates maintenance burden — must stay in sync with vendor schema changes. Only worth doing if CRM features expand beyond admin search. |
| **voice-bridge: separate repo vs keep in monorepo** | Separate repo clarifies ownership and deploy pipeline. But: currently only 16 MB and changes rarely. Monorepo is simpler if team is small. |
| **Repo noise: aggressive cleanup vs gradual gitignore** | Aggressive cleanup (remove dirs, force push) reclaims 10+ GB immediately. But: may lose reference material. Gradual approach (gitignore + archive branch) is safer but slower. |
| **Stub services: implement or remove `DocumentService` + `SystemBillingService`** | They exist as placeholders. If OfficeGuy document generation is planned, keep. If not, remove to reduce confusion. |
