# Backend Architecture — Kalfa.me

> Auto-generated: 2026-03-18  
> Laravel Event-Management SaaS with multi-tenant billing, RSVP, and Twilio integrations.

---

## Table of Contents

1. [Domain Overview](#domain-overview)
2. [Entity Relationship Map](#entity-relationship-map)
3. [Models Reference](#models-reference)
4. [Service Layer Overview](#service-layer-overview)
5. [Contracts & Interfaces](#contracts--interfaces)
6. [Enums Registry](#enums-registry)
7. [Event / Job / Listener / Observer Flow](#event--job--listener--observer-flow)
8. [Providers](#providers)
9. [Support Layer](#support-layer)
10. [Key Patterns & Conventions](#key-patterns--conventions)

---

## Domain Overview

Kalfa.me is a **multi-tenant event-management SaaS** built on Laravel. Core domains:

| Domain | Purpose |
|--------|---------|
| **Tenancy** | User → Organization → Account (billing entity) |
| **Events** | Event creation, guest management, tables, seating, invitations, RSVP |
| **Billing / Product Engine** | Products, plans, entitlements, subscriptions, usage metering, credits |
| **Payments** | SUMIT (OfficeGuy) gateway integration, one-time & recurring charges |
| **Communications** | Twilio voice calls, WhatsApp RSVP, SMS verification |
| **Coupons** | Discount codes with percentage, fixed, or trial-extension types |
| **System Admin** | Impersonation, audit logging, system settings |

---

## Entity Relationship Map

```
┌──────────┐         ┌───────────────┐
│   User   │────────▶│  Organization │──────▶ Account
│          │ M:N     │               │ N:1    │
│  (auth)  │ pivot:  │  (tenant)     │        │ (billing
│          │ Org-    │               │        │  entity)
└──────────┘ User    └───────┬───────┘        └────┬──────┘
   │  │                      │                     │
   │  │  owns                │ hasMany             │ hasMany
   │  └──────────────────────┼─────────────────────┤
   │                         ▼                     │
   │               ┌─────────────────┐             │
   │               │     Event       │             │
   │               │ (soft-deleted)  │             │
   │               │ + HasMedia      │             │
   │               └───┬──┬──┬──┬───┘             │
   │                   │  │  │  │                  │
   │          ┌────────┘  │  │  └────────┐         │
   │          ▼           ▼  ▼           ▼         │
   │    ┌─────────┐  ┌────────┐  ┌────────────┐   │
   │    │  Guest  │  │ Event  │  │ Invitation  │   │
   │    │(soft-del)│ │ Table  │  │             │   │
   │    └──┬──┬───┘  │(soft-del)│ └─────┬──────┘   │
   │       │  │      └────┬───┘        │          │
   │       │  │           │            │          │
   │       │  │    ┌──────┘    ┌───────┘          │
   │       │  │    ▼           ▼                  │
   │       │  │  ┌──────────────┐                 │
   │       │  └─▶│SeatAssignment│                 │
   │       │     └──────────────┘                 │
   │       ▼                                      │
   │  ┌────────────┐                              │
   │  │RsvpResponse│                              │
   │  └────────────┘                              │
   │                                              │
   │                                              │
   │     ┌────────────────────────────────────────┘
   │     │
   │     ▼
   │  ┌──────────────┐     ┌─────────────────┐     ┌─────────────┐
   │  │AccountProduct│────▶│    Product       │────▶│ ProductPlan │
   │  │              │ N:1 │                  │ 1:N │             │
   │  └──────────────┘     │  + Entitlements  │     │  + Prices   │
   │                       │  + Limits        │     │  + Subs     │
   │                       │  + Features      │     └──────┬──────┘
   │                       └──────────────────┘            │
   │                                                       │
   │  ┌──────────────────┐     ┌───────────────────┐       │
   │  │AccountEntitlement│     │AccountSubscription │◀──────┘
   │  │(feature grants)  │     │(trial/active/etc.) │
   │  └──────────────────┘     └───────────────────┘
   │
   │  ┌──────────────┐     ┌──────────┐     ┌──────────────────────┐
   │  │ EventBilling  │────▶│ Payment  │     │AccountCreditTx       │
   │  │(per-event $)  │ 1:N │(morph)   │     │(append-only ledger)  │
   │  └──────────────┘     └──────────┘     └──────────────────────┘
   │
   │  ┌──────────┐     ┌──────────────────┐     ┌────────────────┐
   │  │  Coupon   │────▶│CouponRedemption  │     │  UsageRecord   │
   │  └──────────┘ 1:N └──────────────────┘     └────────────────┘
   │
   │  ┌──────────────────┐     ┌──────────────┐
   │  │BillingIntent      │     │BillingWebhook│
   │  │(checkout abstrac.)│     │Event (log)   │
   │  └──────────────────┘     └──────────────┘
   │
   │  ┌────────────────────┐     ┌──────────────────────┐
   │  │OrganizationInvite  │     │  SystemAuditLog      │
   │  │(email team invite) │     │  (actor+target morph) │
   │  └────────────────────┘     └──────────────────────┘
```

---

## Models Reference

### User
- **Traits:** HasApiTokens, HasFactory, HasRoles (Spatie), Notifiable, WebAuthnAuthentication
- **Relationships:** `organizations()` BelongsToMany (pivot: OrganizationUser), `ownedOrganizations()` BelongsToMany (filtered pivot role=owner)
- **Virtual Property:** `currentOrganization` — resolved from `current_organization_id`, with impersonation bypass for system admins
- **Key Fields:** `is_system_admin`, `is_disabled`, `current_organization_id`, `last_login_at`

### Organization
- **Implements:** HasSumitCustomer
- **Traits:** HasFactory, HasSumitCustomerTrait
- **Relationships:** `account()` BelongsTo, `users()` BelongsToMany (pivot: OrganizationUser), `events()` HasMany, `eventsBilling()` HasMany, `payments()` HasMany, `invitations()` HasMany, `accountProducts()` HasManyThrough(Account), `activeAccountProducts()` HasManyThrough (scoped)
- **Key Methods:** `hasActivePlan()` (fast-path with eager-loaded relations or cached hasBillingAccess), `getBillingStatusAttribute()` → 'suspended' | 'active' | 'no_plan', `owner()` → first User with Owner role

### Account
- **Implements:** HasSumitCustomer
- **Traits:** HasFactory, HasSumitCustomerTrait
- **Relationships:** `owner()` BelongsTo(User), `organizations()` HasMany, `eventsBilling()` HasMany, `payments()` HasMany, `entitlements()` HasMany, `accountProducts()` HasMany, `activeAccountProducts()` HasMany (scoped), `featureUsage()` HasMany, `billingIntents()` HasMany, `subscriptions()` HasMany, `activeSubscriptions()` HasMany (scoped), `paymentMethods()` MorphMany(OfficeGuyToken)
- **Key Methods:** `hasBillingAccess()` (cached 60s, checks products+subscriptions+trials), `grantProduct()`, `overrideFeature()`, `subscribeToPlan()`, `startTrial()`, `invalidateBillingAccessCache()`

### Event
- **Implements:** HasMedia (Spatie)
- **Traits:** HasFactory, InteractsWithMedia, SoftDeletes
- **Relationships:** `organization()` BelongsTo, `guests()` HasMany, `invitations()` HasMany, `eventTables()` HasMany, `seatAssignments()` HasMany, `eventBilling()` HasOne
- **Casts:** `event_date` date, `settings` array, `status` EventStatus
- **Virtual Properties:** `imageUrl` (from media or settings), `customFields` (from settings)

### Guest
- **Traits:** SoftDeletes
- **Relationships:** `event()` BelongsTo, `invitation()` HasOne, `rsvpResponses()` HasMany, `seatAssignment()` HasOne

### Invitation
- **Relationships:** `event()` BelongsTo, `guest()` BelongsTo, `rsvpResponses()` HasMany
- **Casts:** `status` InvitationStatus, `expires_at` datetime

### RsvpResponse
- **Relationships:** `invitation()` BelongsTo, `guest()` BelongsTo
- **Casts:** `response` RsvpResponseType

### EventTable
- **Traits:** SoftDeletes
- **Relationships:** `event()` BelongsTo, `seatAssignments()` HasMany

### SeatAssignment
- **Relationships:** `event()` BelongsTo, `guest()` BelongsTo, `eventTable()` BelongsTo

### Product
- **Relationships:** `entitlements()` / `productEntitlements()` HasMany, `activeEntitlements()` HasMany (scoped), `limits()` HasMany, `activeLimits()` HasMany, `features()` HasMany, `enabledFeatures()` HasMany, `plans()` HasMany(Plan), `productPlans()` HasMany(ProductPlan), `accountProducts()` HasMany, `usageRecords()` HasMany
- **Scopes:** `active`, `draft`, `byCategory`

### ProductPlan
- **Relationships:** `product()` BelongsTo, `prices()` HasMany(ProductPrice), `activePrices()` HasMany, `subscriptions()` HasMany(AccountSubscription)
- **Auto-generates:** SKU on creating event
- **Key Methods:** `limit(featureKey)` reads from metadata JSON, `primaryPrice()`

### ProductPrice
- **Relationships:** `productPlan()` BelongsTo
- **Casts:** `billing_cycle` ProductPriceBillingCycle

### ProductEntitlement
- **Relationships:** `product()` BelongsTo, `accountEntitlements()` HasMany
- **Scopes:** `active`, `byType`

### ProductFeature / ProductLimit
- **Relationships:** `product()` BelongsTo
- **Scopes:** `enabled` / `active`

### AccountProduct
- **Relationships:** `account()` BelongsTo, `product()` BelongsTo, `grantedBy()` BelongsTo(User)
- **Scope:** `active`
- **Lifecycle:** `booted()` flushes FeatureResolver cache on save/delete
- **Casts:** `status` AccountProductStatus, `metadata` array

### AccountSubscription
- **Relationships:** `account()` BelongsTo, `productPlan()` BelongsTo
- **Scope:** `active`
- **Delegate Methods:** `activate()`, `cancel()`, `suspend()`, `renew()` → SubscriptionService
- **Lifecycle:** `booted()` flushes FeatureResolver cache on save/delete

### AccountEntitlement
- **Relationships:** `account()` BelongsTo, `productEntitlement()` BelongsTo
- **Lifecycle:** `booted()` flushes FeatureResolver on save/delete

### AccountCreditTransaction
- **Append-only ledger** (no `UPDATED_AT`)
- **Relationships:** `account()` BelongsTo, `actor()` BelongsTo(User), `reference()` MorphTo
- **Key:** `type` = 'credit' | 'debit', `source` = CreditSource enum

### AccountFeatureUsage
- **Relationships:** `account()` BelongsTo
- **Purpose:** Usage tracking per feature per period

### EventBilling
- **Relationships:** `account()` BelongsTo, `organization()` BelongsTo, `event()` BelongsTo, `plan()` BelongsTo, `payments()` MorphMany

### Payment
- **Relationships:** `account()` BelongsTo, `organization()` BelongsTo, `payable()` MorphTo
- **Casts:** `status` PaymentStatus

### BillingIntent
- **Relationships:** `account()` BelongsTo, `payable()` MorphTo

### BillingWebhookEvent
- Standalone log table for raw webhook payloads

### Plan (legacy)
- **Relationships:** `product()` BelongsTo, `eventsBilling()` HasMany

### Coupon
- **Relationships:** `creator()` BelongsTo(User), `redemptions()` HasMany
- **Scope:** `active`
- **Key Methods:** `hasUsesRemaining()`, `hasUsesRemainingFor(Account)`, `calculateDiscountAmount()`, `getTrialDaysToAdd()`, `appliesTo(targetType, planId)`

### CouponRedemption
- **Relationships:** `coupon()` BelongsTo, `account()` BelongsTo, `redeemedBy()` BelongsTo(User), `redeemable()` MorphTo

### UsageRecord
- **Append-only** (no `UPDATED_AT`)
- **Relationships:** `account()` BelongsTo, `product()` BelongsTo

### OrganizationUser (Pivot)
- **Relationships:** `organization()` BelongsTo, `user()` BelongsTo
- **Casts:** `role` OrganizationUserRole

### OrganizationInvitation
- **Relationships:** `organization()` BelongsTo
- **Key Methods:** `isExpired()`

### SystemAuditLog
- **Relationships:** `actor()` BelongsTo(User), `target()` MorphTo

---

## Service Layer Overview

### Core Billing & Product Engine

| Service | Responsibility |
|---------|---------------|
| **BillingService** | Orchestrates event payment flow: `initiateEventPayment()` (redirect), `initiateEventPaymentWithToken()` (PaymentsJS), `markPaymentSucceeded()`, `markPaymentFailed()`. Applies credits before charging. |
| **SubscriptionService** | Full subscription lifecycle: `startTrial()`, `activate()`, `cancel()`, `suspend()`, `renew()`, `processTrialExpirations()`. Integrates with BillingProvider for SUMIT recurring payments. Grants/revokes products and entitlements atomically. |
| **SubscriptionManager** | Thin facade over SubscriptionService for `subscribe()`, `activate()`, `cancel()`. |
| **FeatureResolver** | **Central feature gate resolver.** Cached resolution chain: account override → propagated entitlement → plan limit → product default → system default. Methods: `has()`, `allows()`, `enabled()`, `value()`, `integer()`, `usage()`, `remaining()`, `allowsUsage()`. |
| **UsageMeter** | Records usage (UsageRecord), computes billing windows, detects overage, triggers billing provider charges for soft-policy overages. |
| **UsagePolicyService** | Checks usage against limits: returns `Allowed`, `AllowedWithOverage`, or `Blocked`. Dispatches ProductEngineEvent on limits exceeded. |
| **CreditService** (`Billing/`) | Account credit ledger: `credit()`, `debit()`, `applyToCheckout()`, `reverseDebit()`, `getAvailableBalance()`, `getHistory()`. Atomic (SELECT FOR UPDATE), append-only, expiry-aware. |
| **CouponService** | Coupon validation, discount calculation, redemption recording, trial extension. |
| **PermissionSyncService** | Syncs Spatie team-scoped permissions when AccountProduct status changes. Grants/revokes tenant permissions to org Owner/Admin. |
| **ProductIntegrityChecker** | Validates product catalog: duplicate feature keys, inconsistent types, missing prices. Used at publish-time and post-migration. |
| **ProductEngineOperationsMonitor** | Health monitoring for scheduled tasks (trial expirations, integrity checks). Tracks heartbeats and task states in cache. |

### Payment Gateways

| Service | Responsibility |
|---------|---------------|
| **SumitPaymentGateway** | Implements `PaymentGatewayInterface` → SUMIT one-time payments (redirect + token modes), webhook handling. |
| **StubPaymentGateway** | Dev/test gateway. Returns stub transaction IDs, no real charges. |
| **SumitBillingProvider** (`Billing/`) | Implements `BillingProvider` → SUMIT customer creation, recurring subscription management, usage overage charging. |

### SUMIT Integration (`Sumit/` & `OfficeGuy/`)

| Service | Responsibility |
|---------|---------------|
| **AccountPaymentMethodManager** | Token-based payment method CRUD via SUMIT: `storeSingleUseToken()`, `setDefault()`, `delete()`. |
| **EventBillingPayable** | Payable adapter: EventBilling → SUMIT PaymentService interface. |
| **SumitUsageChargePayable** | Payable adapter: usage overage charges → SUMIT PaymentService interface. |
| **OfficeGuyCustomerSearchService** | Customer search across local DB, SUMIT CRM cache, and remote SUMIT CRM API. |
| **DocumentService** (`OfficeGuy/`) | SUMIT document/invoice generation and email delivery. |
| **SystemBillingService** (`OfficeGuy/`) | System-admin billing operations: `getOrganizationSubscription()`, `cancelSubscription()`, `extendTrial()`, `applyCredit()`, `retryPayment()`, `getMRR()`, `getChurnRate()`, `syncOrganizationSubscriptions()`. |

### Communications

| Service | Responsibility |
|---------|---------------|
| **CallingService** | Twilio voice RSVP: find guest by phone, normalize to E.164, ensure feature access, initiate outbound call. |
| **WhatsAppRsvpService** | Twilio WhatsApp: send RSVP invitation links, phone normalization, error translation (Hebrew). |
| **VerifyWhatsAppService** | Twilio Verify: send OTP via WhatsApp/SMS, check verification codes. |

### Tenant & Utility

| Service | Responsibility |
|---------|---------------|
| **OrganizationContext** | Multi-tenant organization context: set/get/clear active org. DB is source of truth; session mirrors. |
| **OrganizationMemberService** | Org member lifecycle: invite, accept invitation, add/remove member, update role. Syncs Spatie roles. |
| **EventLinks** | Google Calendar URLs, navigation links (Google Maps, Waze) from event data. |
| **SystemAuditLogger** | Static `log()` — creates SystemAuditLog entries with actor, target (morph), IP, user-agent. |

### DTOs

| Class | Purpose |
|-------|---------|
| **CreditApplicationResult** (`Billing/`) | Immutable result of `CreditService::applyToCheckout()`: applied amount, remaining charge, transaction reference. |

---

## Contracts & Interfaces

### `PaymentGatewayInterface`
```php
createOneTimePayment(int $organizationId, int $amount, array $metadata): array
chargeWithToken(int $organizationId, int $amount, array $metadata, string $token): array
handleWebhook(array $payload, string $signature): void
```
**Implementations:** `SumitPaymentGateway`, `StubPaymentGateway`  
**Bound in:** AppServiceProvider (based on `billing.default_gateway` config)

### `BillingProvider`
```php
createCustomer(Account $account): array
createSubscription(Account $account, ProductPrice $price): array
cancelSubscription(AccountSubscription $subscription): void
reportUsage(AccountSubscription $sub, string $metric, int $qty, array $context): array
```
**Implementation:** `SumitBillingProvider`  
**Bound in:** AppServiceProvider

---

## Enums Registry

| Enum | Values | Used In |
|------|--------|---------|
| `AccountProductStatus` | active, suspended, revoked | AccountProduct |
| `AccountSubscriptionStatus` | trial, active, past_due, cancelled | AccountSubscription |
| `CouponDiscountType` | percentage, fixed, trial_extension | Coupon |
| `CouponTargetType` | global, subscription, plan, event_billing | Coupon |
| `CreditSource` | manual, coupon, refund, checkout_applied, subscription_cycle, adjustment, migration, chargeback, expiry | AccountCreditTransaction |
| `EntitlementType` | boolean, number, text, enum | ProductEntitlement, AccountEntitlement |
| `EventBillingStatus` | pending, paid, cancelled | EventBilling |
| `EventStatus` | draft, pending_payment, active, locked, archived, cancelled | Event |
| `Feature` | twilio_enabled, voice_rsvp_calls, sms_confirmation_enabled, sms_confirmation_limit, sms_confirmation_messages, create_event, max_active_events, max_guests_per_event, guest_import, seating_management, invitation_sending | Feature gating (FeatureResolver, Gate) |
| `InvitationStatus` | pending, sent, opened, responded, expired | Invitation |
| `OrganizationUserRole` | owner, admin, member | OrganizationUser |
| `PaymentStatus` | pending, processing, succeeded, failed, refunded, cancelled | Payment |
| `ProductPriceBillingCycle` | monthly, yearly, usage | ProductPrice |
| `ProductStatus` | draft, active, archived | Product |
| `RsvpResponseType` | yes, no, maybe | RsvpResponse |
| `UsagePolicyDecision` | allowed, allowed_with_overage, blocked | UsagePolicyService |

---

## Event / Job / Listener / Observer Flow

### Events

```
ProductEngineEvent
├── action: product.granted | subscription.trial_started | subscription.activated
│           | subscription.cancelled | subscription.suspended | subscription.renewed
│           | subscription.payment_failed | usage.recorded | usage.overage_charged
│           | limits.exceeded
├── account, product, subscription, payload, level
└── Listener: LogProductEngineEvent → Log + SystemAuditLog

RsvpReceived (ShouldBroadcast)
├── Broadcasts on: PrivateChannel('event.{event_id}')
└── Triggered when a guest submits RSVP

Billing\SubscriptionCancelled
├── organization, actorId
└── Listener: Billing\AuditBillingEvent → SystemAuditLog

Billing\TrialExtended
├── organization, days, actorId
└── Listener: Billing\AuditBillingEvent → SystemAuditLog

Laragear\WebAuthn\Events\CredentialAsserted
└── Listener: StoreWebAuthnCredentialInSession → stores credential ID in session
```

### Jobs

```
ExpireAccountCreditsJob (ShouldQueue, tries=3)
├── Runs daily
├── Finds expired credit transactions without reversal debits
├── Creates compensating debit entries (source=Expiry)
└── Maintains ledger integrity: SUM(transactions) == credit_balance_agorot

SyncOrganizationSubscriptionsJob (ShouldQueue, tries=3, backoff=[30,120,300])
├── Syncs subscriptions from SUMIT API for a specific organization
├── Busts subscription cache after sync
└── Logs to SystemAuditLog
```

### Observer

```
AccountProductObserver
├── created  → if status=Active → PermissionSyncService::syncForAccount()
├── updated  → if status or expires_at changed → syncForAccount()
└── deleted  → syncForAccount()

Effect: Keeps Spatie team-scoped permissions in sync with billing state
```

### Event Registration (AppServiceProvider::boot)

```
ProductEngineEvent        → LogProductEngineEvent
CredentialAsserted        → StoreWebAuthnCredentialInSession
MigrationsEnded           → ProductIntegrityChecker::reportAll()
SubscriptionCancelled     → Billing\AuditBillingEvent
TrialExtended             → Billing\AuditBillingEvent
AccountProduct::observe   → AccountProductObserver
```

---

## Providers

### AppServiceProvider
**Registers (bindings):**
- `PaymentGatewayInterface` → `SumitPaymentGateway` or `StubPaymentGateway` (config-driven)
- `BillingProvider` → `SumitBillingProvider`
- Singletons: `AccountPaymentMethodManager`, `UsageMeter`, `FeatureResolver`, `SubscriptionService`, `SubscriptionManager`, `UsagePolicyService`, `ProductEngineOperationsMonitor`, `ProductIntegrityChecker`, `TwilioClient`

**Boots:**
- Scramble API docs (Sanctum bearer, API routes only)
- Event/Listener registration
- Observer registration
- Gate definitions: system admin bypass, `viewPulse`, `feature` gate (→ FeatureResolver)
- Rate limiters: `rsvp_show` (60/min), `rsvp_submit` (10/min), `webhooks` (120/min), `webauthn` (10/min per IP)
- Production SUMIT config validation
- Pail handler wrapper (avoids permission errors in production web requests)

### SystemSettingsServiceProvider
- Reads `SumitSettings`, `TwilioSettings`, `GeminiSettings` from DB `settings` table
- Overrides config values at runtime (OfficeGuy, Twilio, Gemini)

### TelescopeServiceProvider
- Filters: local=all, production=exceptions+failed requests+jobs+scheduled+monitored
- Gate: `viewTelescope` → `is_system_admin`

---

## Support Layer

### `Feature` (Facade)
- Proxies to `FeatureResolver`
- Static methods: `has()`, `allows()`, `enabled()`, `value()`, `integer()`, `usage()`, `remaining()`, `allowsUsage()`

### `UsagePolicy` (Facade)
- Proxies to `UsagePolicyService`
- Static method: `check(account, metricKey, quantity, subscription)`

### `helpers.php`
- `isRTL()` — checks current locale against `app.rtl_locales` (default: `['he']`)

### Exceptions
- `InsufficientCreditException` — thrown by CreditService when debit exceeds balance
- `AlreadyReversedException` — thrown by CreditService when debit is already reversed

---

## Key Patterns & Conventions

### 1. Multi-Tenancy Model
```
User ──M:N──▶ Organization ──N:1──▶ Account
```
- **Organization** = tenant boundary (team, org)
- **Account** = billing/entitlement entity (shared across org's organizations)
- `User.current_organization_id` is DB source of truth; `OrganizationContext` service manages switching
- System admins can impersonate organizations via session key

### 2. Feature Resolution Chain
```
Account Override → Propagated Entitlement → Plan Limit → Product Default → System Default → null
```
- All resolved values cached (configurable TTL, default 300s)
- Cache invalidated on any entitlement/product/subscription change via model `booted()` hooks
- `Feature::enabled($account, 'key')` or `Gate::allows('feature', 'key')` for access checks

### 3. Billing Access — Single Source of Truth
`Account::hasBillingAccess()` checks (in order):
1. Active AccountProduct (admin-granted or paid)
2. Active Subscription (status=active, not ended)
3. Active Trial (status=trial, trial_ends_at > now)

Result cached 60s; invalidated after any billing state change.

### 4. Append-Only Ledger (Credits)
- `AccountCreditTransaction` is never updated or deleted
- Balance = SUM(credits, non-expired) − SUM(debits)
- All mutations use `SELECT FOR UPDATE` on the Account row
- Reversals create compensating credits linked via `reference_type`/`reference_id`

### 5. Contract-Based Gateway Abstraction
- `PaymentGatewayInterface` for one-time charges (event billing)
- `BillingProvider` for subscription lifecycle + usage billing
- Implementations swappable via config (`billing.default_gateway`)

### 6. Event-Driven Architecture
- `ProductEngineEvent` is the universal domain event for all billing/product state changes
- Listeners log + audit every change
- Billing domain events (`SubscriptionCancelled`, `TrialExtended`) trigger audit entries
- `RsvpReceived` broadcasts via WebSocket for real-time dashboard updates

### 7. Observer-Driven Permission Sync
- `AccountProductObserver` watches create/update/delete on AccountProduct
- Triggers `PermissionSyncService` to grant/revoke Spatie team-scoped permissions
- Ensures billing status and tenant permissions stay in sync

### 8. DB Transactions for State Consistency
- All multi-step billing operations (payment, subscription activation, product granting) are wrapped in `DB::transaction()`
- Prevents partial state (e.g., payment succeeded but event not activated)

### 9. Strict PHP Typing
- All files use `declare(strict_types=1)`
- Backed enums for all status fields
- Method-based casts (`casts()` method, not `$casts` property)
- PHP 8.4 property hooks used in some models (`Event::$imageUrl`, `User::$currentOrganization`)

### 10. Hebrew / RTL Support
- Coupon labels, error messages, and UI strings in Hebrew
- `isRTL()` helper for locale detection
- Twilio error codes translated to Hebrew in WhatsAppRsvpService

### 11. SUMIT (OfficeGuy) Integration Pattern
- All SUMIT access goes through service classes — never direct SDK calls from controllers
- `HasSumitCustomer` interface on Account and Organization
- `OfficeGuyToken` (morphMany) for stored payment methods
- Customer sync is idempotent (check `sumit_customer_id` before creating)

### 12. Caching Strategy
- Feature resolution: per-account per-feature key (configurable store/TTL)
- Billing access: per-account (60s)
- Credit balance: per-account per-currency (60s)
- Subscription lookup: per-organization (60s)
- All caches explicitly invalidated on relevant mutations

---

*End of architecture document.*
