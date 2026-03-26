# Repository Inventory — Decision Document

> Generated: 2026-03-23 | Phase: project-analyzer | Status: **Base for restructure decisions**
>
> This is a **decision document**, not a description. Every section ends with a verdict.

---

## 1. Top-Level Directory Classification

| Directory | Classification | Size | Verdict |
|---|---|---|---|
| `app/` | **runtime** | 1.6 MB | Core application. Do not restructure without execution map. |
| `bootstrap/` | **runtime** | 56 KB | Laravel bootstrap. Touch only `app.php` and `providers.php`. |
| `config/` | **runtime** | 232 KB | 32 config files. Some are for packages not actively used. |
| `database/` | **runtime** | 872 KB | Migrations, factories, seeders. Active. |
| `routes/` | **runtime** | 40 KB | 5 route files. Active. |
| `resources/` | **runtime** | 2.7 MB | Views, JS, CSS. Active. |
| `tests/` | **runtime** | 384 KB | ~35 test files. Sparse but active. |
| `public/` | **runtime** | 27 MB | Static assets + build output. 90%+ is `public/build/`. |
| `storage/` | **generated** | 16 MB | Logs, cache, framework temp files. Gitignored content. |
| `packages/` | **runtime** (local) | 132 KB | `kalfa/secure-storage` — local Composer package. |
| `voice-bridge/` | **runtime** (separate) | 16 MB | Node.js WebSocket relay. Has own `Dockerfile`, `package.json`. **Separate runtime.** |
| `vendor/` | **build** | 344 MB | Composer dependencies. Reproducible. |
| `node_modules/` | **build** | 398 MB | NPM dependencies. Reproducible. |
| `docs/` | **docs** | 2.6 MB | ~90 markdown files. Architecture decisions + audit reports. |
| `scripts/` | **tooling** | 24 KB | Helper scripts. |
| `credentials/` | **sensitive** | 36 KB | iOS signing keys/certs. **SHOULD NOT BE IN REPO.** |
| `claude-os/` | **tooling** | 7.8 GB | AI orchestration framework. Not application code. |
| `hivemind-install/` | **tooling** | 1.3 GB | AI tooling installer. Not application code. |
| `hivemind/` | **tooling** | 20 KB | Pointer directory for hivemind. |
| `gemini-cli/` | **tooling** | 370 MB | Google Gemini CLI. Not application code. |
| `claude-knowledge-base-mcp/` | **tooling** | 287 MB | MCP knowledge base. Not application code. |
| `_bmad/` | **tooling** | 15 MB | BMAD agent config. Not application code. |
| `.claude/` | **tooling** | 11 MB | Claude Code skills/config. |
| `.cursor/` | **tooling** | 11 MB | Cursor IDE config. |
| `documentation/` | **archive** | 508 MB | Personal Obsidian vault. Not application docs. |
| `obsidian-hub/` | **archive** | 51 MB | Obsidian plugins. Not application code. |
| `obsidian-claude-pkm/` | **archive** | 516 KB | PKM tooling. Not application code. |
| `archive/` | **archive** | 396 MB | Old files. Not referenced by runtime. |
| `nativephp/` | **archive/experimental** | 38 MB | NativePHP mobile experiment. Not in active runtime. |
| `var/` | **unknown** | 32 KB | Contains `www/` — possibly deployment artifact. |

### Verdict: Directory Classification

```
RUNTIME (what runs):           app/ bootstrap/ config/ database/ routes/ resources/ tests/ public/ packages/
RUNTIME (separate process):    voice-bridge/
BUILD (reproducible):          vendor/ node_modules/
DOCS (reference):              docs/
TOOLING (AI/dev):              claude-os/ hivemind-install/ hivemind/ gemini-cli/ claude-knowledge-base-mcp/ _bmad/ .claude/ .cursor/ scripts/
ARCHIVE (not in runtime):      documentation/ obsidian-hub/ obsidian-claude-pkm/ archive/ nativephp/
SENSITIVE (should not be here): credentials/
UNKNOWN:                        var/
```

**Safe to exclude from GitIngest**: Everything except RUNTIME + docs/.
**Safe to move out of repo**: `documentation/`, `obsidian-hub/`, `obsidian-claude-pkm/`, `archive/`, `nativephp/`, `var/`.
**Must fix**: `credentials/` — move to secure storage, not git.

---

## 2. Bounded Contexts (from code, not just tables)

### Context A: RSVP Core (the product)

| Layer | Components |
|---|---|
| **Models** | `Event`, `Guest`, `Invitation`, `RsvpResponse`, `EventTable`, `SeatAssignment` |
| **Controllers** | `EventController` (API+Dashboard), `GuestController`, `GuestImportController`, `InvitationController`, `SeatAssignmentController`, `EventTablesController`, `PublicRsvpController`, `PublicEventController`, `PublicRsvpViewController` |
| **Livewire** | `Dashboard/EventGuests`, `Dashboard/EventInvitations`, `Dashboard/EventSeatAssignments`, `Dashboard/EventTables`, `Dashboard.php` |
| **Services** | `EventLinks`, `WhatsAppRsvpService`, `VerifyWhatsAppService` |
| **DB Tables** | 6 tables |
| **Status** | **Active core product. Relatively clean boundaries.** |

### Context B: Multi-Tenancy & Auth

| Layer | Components |
|---|---|
| **Models** | `User`, `Organization`, `OrganizationUser`, `OrganizationInvitation` |
| **Controllers** | `LoginController`, `RegisterController`, `LogoutController`, `PasswordController`, `VerificationController`, `OrganizationSwitchController`, `WebAuthn/*` |
| **Livewire** | `Organizations/Index`, `Organizations/Create`, `Profile/*`, `AcceptInvitation`, `Dashboard/OrganizationMembers` |
| **Services** | `OrganizationContext` (14 usages — **highest-coupled service**), `OrganizationMemberService`, `PermissionSyncService`, `MobileSecureTokenStore` |
| **Middleware** | `EnsureOrganizationSelected`, `EnsureSystemAdmin`, `ImpersonationExpiry`, `RequireImpersonationForSystemAdmin`, `RequestId` |
| **DB Tables** | 8 tables + `sessions`, `password_reset_tokens`, `personal_access_tokens`, `webauthn_credentials` |
| **Status** | **Active. OrganizationContext is the #1 coupling hotspot.** |

### Context C: Product Engine

| Layer | Components |
|---|---|
| **Models** | `Product`, `ProductPlan`, `ProductPrice`, `ProductEntitlement`, `ProductFeature`, `ProductLimit`, `Account`, `AccountProduct`, `AccountSubscription`, `AccountEntitlement`, `AccountFeatureUsage`, `AccountCreditTransaction`, `UsageRecord` |
| **Controllers** | None dedicated (accessed via Livewire System panel) |
| **Livewire** | `System/Products/*` (7 components), `System/Accounts/*` (3 components), `Billing/*` (5 components) |
| **Services** | `FeatureResolver` (356 lines), `UsageMeter` (225 lines), `UsagePolicyService`, `ProductIntegrityChecker`, `ProductEngineOperationsMonitor` (221 lines), `SubscriptionManager`, `SubscriptionService` (367 lines), `SubscriptionSyncService`, `CreditService` (292 lines) |
| **Commands** | `CheckIntegrityCommand`, `ProcessTrialExpirationsCommand`, `ProcessProductExpirationsCommand`, `ProductEngineHealthCommand` |
| **Events** | `ProductEngineEvent`, `SubscriptionCancelled`, `TrialExtended` |
| **Enums** | `AccountProductStatus`, `AccountSubscriptionStatus`, `CreditSource`, `EntitlementType`, `ProductPriceBillingCycle`, `ProductStatus`, `UsagePolicyDecision`, `Feature` |
| **DB Tables** | 12 tables |
| **Status** | **Fully autonomous bounded context. 13 models, 9 services, 4 commands, 3 events, 8 enums. STRONGEST extraction candidate.** |

### Context D: OfficeGuy/SUMIT Integration

| Layer | Components |
|---|---|
| **Models** | None in `app/Models/` — uses vendor package models + 18 DB tables |
| **Controllers** | `WebhookController`, `AccountPaymentMethodController`, `CheckoutController`, `CheckoutTokenizeController`, `CheckoutStatusController`, `BillingCheckoutController`, `BillingCouponController`, `BillingSubscriptionCheckoutController` |
| **Livewire** | `Billing/BillingIntentsIndex`, `Billing/PlanSelection`, `Billing/AccountOverview` |
| **Services** | `BillingService`, `SumitPaymentGateway`, `SumitBillingProvider`, `StubPaymentGateway`, `StubBillingProvider`, `AccountPaymentMethodManager`, `OfficeGuyCustomerSearchService` (543 lines — **2nd largest service**), `EventBillingPayable`, `SumitUsageChargePayable`, `DocumentService`, `SystemBillingService`, `CouponService` |
| **Config** | `officeguy.php`, `officeguy-webhooks.php`, `billing.php` |
| **DB Tables** | 18 tables (`officeguy_*` prefix) + `payments`, `events_billing`, `billing_intents`, `billing_webhook_events`, `coupons`, `coupon_redemptions`, `pending_checkouts`, `order_success_*`, `payable_field_mappings` |
| **Status** | **Integration blob, not a clean context. Mixes CRM, payments, documents, subscriptions, webhooks. 18 vendor tables + ~10 app tables = 28 tables total. Largest domain surface by far.** |

### Context E: Voice/Calling (Twilio + Gemini)

| Layer | Components |
|---|---|
| **Controllers** | `CallingController` (291 lines), `RsvpVoiceController` (361 lines), `TwilioController` |
| **Services** | `CallingService` |
| **Config** | `services.php` (Twilio keys) |
| **Settings** | `TwilioSettings`, `GeminiSettings` |
| **Separate Runtime** | `voice-bridge/` (Node.js, 16 MB, own Dockerfile) |
| **DB Tables** | None (stateless relay) |
| **Status** | **Split runtime: PHP controllers initiate calls, Node.js handles WebSocket media stream. Clean boundary. voice-bridge/ is already structurally isolated.** |

### Context F: System Admin Panel

| Layer | Components |
|---|---|
| **Livewire** | `System/Dashboard`, `System/Users/*`, `System/Organizations/*`, `System/Products/*`, `System/Accounts/*`, `System/Coupons/*`, `System/Settings/*` |
| **Controllers** | `SystemImpersonationController`, `SystemImpersonationExitController` |
| **Services** | `SystemAuditLogger` (6 usages), `SystemBillingService` |
| **Middleware** | `EnsureSystemAdmin`, `ImpersonationExpiry`, `RequireImpersonationForSystemAdmin` |
| **Status** | **Cross-cutting — consumes Product Engine, OfficeGuy, and Multi-Tenancy contexts. Not a candidate for extraction; it's a UI aggregate.** |

---

## 3. Cross-Domain Dependencies

```
OrganizationContext ──────────── 14 usages ──── Controllers (8) + Livewire (6)
                                                 ↓ touches ALL domains

Account model ────────────────── 13 usages ──── Services layer
                                                 ↓ hub between Product Engine + SUMIT

Event model ──────────────────── 17 usages ──── Controllers
                                                 ↓ hub between RSVP Core + Billing

HasSumitCustomer interface ────── Account + Organization
                                                 ↓ SUMIT couples to both tenancy AND accounts
```

### Dependency Hotspots

1. **`OrganizationContext`** — 14 direct usages. Every controller and most Livewire components depend on it. This is the tenancy backbone; refactoring it ripples everywhere.

2. **`Account` model** — 13 usages in services. It's the join point between Product Engine (subscriptions, credits, usage) and SUMIT (payment methods, customer IDs). Both `Account` and `Organization` implement `HasSumitCustomer` — this is the **boundary mismatch**: is billing owned by the org or the account?

3. **`Event` model** — 17 usages in controllers. Makes sense for the core product, but it also has `EventBilling` linkage that couples RSVP Core to OfficeGuy/SUMIT.

---

## 4. Boundary Mismatches

### Mismatch 1: Account vs Organization for Billing

Both `Account` and `Organization` implement `HasSumitCustomer`. Tables have both `organization_id` AND `account_id` columns (`events_billing`, `payments`). This means billing can be attributed to either entity — unclear ownership.

**Risk**: If you restructure tenancy, billing breaks in unpredictable ways.
**Decision needed**: Is `Account` the billing entity or is `Organization`? Pick one.

### Mismatch 2: OfficeGuy Tables Without Models

18 `officeguy_*` tables exist in the database but have **no corresponding models in `app/Models/`**. They're managed by the `officeguy/laravel-sumit-gateway` vendor package. This means:
- No Eloquent relationships visible in your codebase
- No policy/authorization coverage
- Schema changes happen in vendor migrations

**Risk**: Shadow domain — code that runs but isn't visible in your architecture.
**Decision needed**: Are these tables part of your domain or purely delegated to the vendor?

### Mismatch 3: Product Engine Services Without Controllers

Product Engine has 9 services, 4 commands, 3 events — but **zero dedicated API controllers**. Everything is accessed through Livewire System panel. This means:
- No REST API for product management
- No way for external systems to manage products
- Admin panel is the only interface

**Risk**: If you need API access later, you'll build controllers that duplicate Livewire logic.
**Decision needed**: Is this intentional (admin-only feature) or a gap?

---

## 5. The Five Questions

### Q1: What is the runtime-critical surface?

```
MUST NOT BREAK:
├── app/Http/Controllers/Api/          → 17 controllers (REST API)
├── app/Http/Controllers/Auth/         →  7 controllers (login/register)
├── app/Http/Controllers/Dashboard/    →  7 controllers (tenant UI)
├── app/Livewire/                      → 30 components (interactive UI)
├── app/Services/OrganizationContext   → tenancy backbone
├── app/Services/BillingService        → payment flow
├── app/Models/ (31 models)            → all data access
├── routes/api.php                     → public API surface
├── routes/web.php                     → web UI routes
├── bootstrap/app.php                  → middleware + routing config
├── voice-bridge/                      → Twilio WebSocket relay (separate Node.js process)
└── config/billing.php, officeguy.php  → payment gateway config
```

**Total runtime-critical files: ~150 PHP files + 1 Node.js app.**

### Q2: Which areas are NOT part of the active product?

| Directory | Size | Reason it's not active product |
|---|---|---|
| `claude-os/` | 7.8 GB | AI development tooling |
| `hivemind-install/` | 1.3 GB | AI tooling installer |
| `documentation/` | 508 MB | Personal Obsidian vault |
| `archive/` | 396 MB | Archived files |
| `gemini-cli/` | 370 MB | Google CLI tool |
| `claude-knowledge-base-mcp/` | 287 MB | MCP knowledge base |
| `obsidian-hub/` | 51 MB | Obsidian plugins |
| `nativephp/` | 38 MB | Mobile experiment (no routes, no integration) |
| `_bmad/` | 15 MB | Agent config |
| `.claude/` + `.cursor/` | 22 MB | IDE/agent config |
| **Total non-product** | **~10.9 GB** | **99.94% of repo disk** |

### Q3: Is OfficeGuy/SUMIT active, legacy, or integration blob?

**Verdict: Active integration blob.**

- **Not legacy** — `OfficeGuyCustomerSearchService` (543 lines, 39 functions) is actively maintained, has complex search logic
- **Not a clean domain** — mixes CRM (entities, folders, fields, views, activities, relations), payments (transactions, tokens), documents, subscriptions, debt collection, and webhooks into one namespace
- **Integration, not domain** — 18 tables are vendor-managed, no local models, accessed through vendor package contracts
- **Coupling vector** — Both `Account` and `Organization` implement `HasSumitCustomer`, creating a dual-ownership ambiguity

**Recommendation**: Treat as **integration layer**, not domain. Don't try to extract it as a bounded context — instead, define clear seams (billing adapter, CRM adapter) that isolate the rest of the app from SUMIT internals.

### Q4: Is Product Engine a standalone bounded context?

**Verdict: Yes — the strongest candidate for extraction.**

Evidence:
- 13 models (own namespace potential)
- 9 dedicated services
- 4 artisan commands
- 3 domain events
- 8 domain enums
- 12 dedicated DB tables
- Zero direct API controllers (self-contained via Livewire admin)
- Clear single coupling point: `Account` model

**If you extract one thing, extract this.** It could be a Laravel package under `packages/kalfa/product-engine/` with a clean contract boundary.

### Q5: What can be moved without breaking build/deploy/runtime?

#### Safe to move (zero runtime references):

| Item | Action | Risk |
|---|---|---|
| `documentation/` | Move to separate repo or external storage | None |
| `obsidian-hub/` | Remove from repo | None |
| `obsidian-claude-pkm/` | Remove from repo | None |
| `archive/` | Move to separate repo/branch | None |
| `nativephp/` | Move to separate repo | None (no imports found) |
| `var/` | Delete (deployment artifact) | None |
| `credentials/` | Move to secure vault (Vault, AWS Secrets Manager) | **Critical security improvement** |
| Root-level audit/analysis `.md` files | Move to `docs/archive/` | None |

#### Safe to reorganize (runtime, but isolated):

| Item | Action | Risk |
|---|---|---|
| `voice-bridge/` | Extract to separate repo + Docker service | Low — already has own Dockerfile, own package.json. Only connected via HTTP webhook. |
| `docs/` | Reorganize into `docs/{architecture,audits,ops,research}` | None — already partially done |

#### High risk (do NOT move without execution map):

| Item | Why |
|---|---|
| `app/Services/OrganizationContext.php` | 14 direct dependents |
| `app/Models/Account.php` | 13 service usages, dual HasSumitCustomer |
| `app/Services/Billing*` + `app/Services/Sumit*` | Intertwined with payments runtime |
| `config/officeguy.php` | Used by vendor package internals |
| `routes/api.php` | Public API surface |

---

## 6. Restructure Risk Map

```
                    LOW RISK                          HIGH RISK
              (safe to move now)                 (need execution map first)
                     │                                    │
  ┌──────────────────┤                    ┌───────────────┤
  │                  │                    │               │
  │  documentation/  │                    │  Account ←→   │
  │  obsidian-*/     │                    │  Organization │
  │  archive/        │                    │  HasSumitCust │
  │  nativephp/      │                    │               │
  │  var/            │                    │  BillingServ  │
  │  credentials/    │                    │  ← SUMIT →    │
  │                  │                    │               │
  │  voice-bridge/   │                    │  OrgContext   │
  │  (already iso-   │                    │  (14 deps)    │
  │   lated)         │                    │               │
  │                  │                    │  routes/      │
  │  Product Engine  │                    │  api.php      │
  │  (clean bound-   │                    │               │
  │   ary, single    │                    │               │
  │   coupling pt)   │                    │               │
  └──────────────────┘                    └───────────────┘
```

---

## 7. Largest Files (Complexity Hotspots)

| File | Lines | Domain | Note |
|---|---|---|---|
| `Livewire/System/Products/Show.php` | 1,037 | Product Engine | **Over 1K lines.** God component — manages plans, entitlements, features, limits in one view. |
| `Livewire/System/Products/CreateProductWizard.php` | 577 | Product Engine | Multi-step wizard. |
| `Services/Sumit/OfficeGuyCustomerSearchService.php` | 543 | SUMIT | 39 functions. Complex search/matching logic. |
| `Livewire/System/Accounts/Show.php` | 480 | Product Engine | Account management UI. |
| `Services/SubscriptionService.php` | 367 | Product Engine | Subscription lifecycle. |
| `Controllers/Twilio/RsvpVoiceController.php` | 361 | Voice | TwiML generation + voice flow. |
| `Services/FeatureResolver.php` | 356 | Product Engine | Feature flag resolution. |
| `Models/Account.php` | 337 | Product Engine | Largest model. Many relationships. |
| `Services/Billing/SumitBillingProvider.php` | 330 | SUMIT | SUMIT gateway adapter. |
| `Providers/AppServiceProvider.php` | 310 | Infrastructure | Large boot method — may contain too many bindings. |

---

## 8. Technical Debt Score

| Category | Score (0-10) | Detail |
|---|---|---|
| Test coverage | 3/10 | ~35 test files for ~150 runtime files. Key domains undertested. |
| Boundary clarity | 5/10 | Product Engine is clean; SUMIT is a blob; Account/Org dual-ownership is ambiguous. |
| File sizes | 6/10 | 1 file over 1K lines. 3 files over 500 lines. Mostly reasonable. |
| Dependency health | 7/10 | All packages up to date per Boost. |
| Security | 4/10 | `credentials/` in repo is a critical finding. |
| Architecture clarity | 6/10 | Clear layering (Controllers → Services → Models) but cross-domain coupling via Account/OrgContext. |
| Documentation | 7/10 | Extensive `docs/` directory. CLAUDE.md is comprehensive. |
| **Overall** | **5.4/10** | Moderate debt. Focus on: credentials security, test coverage, SUMIT boundary, Account/Org clarity. |

---

## 9. Gate Checklist (must have before restructuring)

- [x] **Inventory** — this document
- [ ] **Context boundaries** — defined above, need validation via `architecture-discovery` (execution flow)
- [ ] **Dependency hotspots** — OrganizationContext (14), Account (13), Event (17) identified, need call-graph validation
- [ ] **Restructure risk map** — defined above, needs execution map confirmation

**Next step**: `architecture-discovery` to validate these boundaries against actual execution flow (routes → controllers → services → models → views).
