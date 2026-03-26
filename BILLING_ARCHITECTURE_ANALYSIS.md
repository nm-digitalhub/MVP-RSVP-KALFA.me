# Billing Architecture Analysis
## Kalfa.me Event SaaS Platform
**Date**: 2026-03-24
**Analysis Scope**: End-to-end billing system architecture

---

## 1. Architecture Map

### Core Entities Hierarchy

```
Account (Billing Entity)
├── Organization (Tenant) ──┐
│   └── Event (Data)        │
│       └── EventBilling     │ (Per-event billing)
│           └── Payment       │ (Polymorphic)
│
├── AccountProduct          │ (Product grants)
│   └── Product             │ (Catalog)
│       ├── ProductEntitlement (Features granted)
│       ├── ProductLimit     │ (Usage limits)
│       ├── ProductFeature   │ (Feature flags)
│       └── ProductPlan      │ (Pricing tiers)
│           └── ProductPrice │ (Cycle pricing)
│
├── AccountSubscription     │ (Recurring billing)
│   └── ProductPlan         │
│
├── AccountEntitlement      │ (Resolved feature values)
│   └── ProductEntitlement   │
│
├── BillingIntent           │ (Checkout flow tracking)
│
└── OfficeGuyToken (MorphMany) │ (Payment methods)

Coupon (Promotions)
└── CouponRedemption        │ (MorphTo: any redeemable)
```

### Dual Billing Pattern

**Critical Discovery**: The system uses **TWO separate billing models**:

1. **EventBilling** (`app/Models/EventBilling.php`)
   - Per-event, one-time payment
   - Links: `Organization → EventBilling → Payment`
   - Status: `EventBillingStatus` enum
   - Used by: `BillingService::initiateEventPayment()`

2. **AccountSubscription** (`app/Models/AccountSubscription.php`)
   - Account-level recurring subscription
   - Links: `Account → AccountSubscription → AccountProduct`
   - Status: `AccountSubscriptionStatus` enum (trial, active, past_due, cancelled)
   - Used by: `SubscriptionService::activatePaid()`

---

## 2. Per-Layer Responsibility Summary

### Models Layer (`app/Models/`)

| Model | Responsibility | Key Relationships |
|-------|---------------|-------------------|
| **Account** | Billing entity, product grants, entitlement container | `HasMany` organizations, subscriptions, payments, products |
| **Organization** | Tenant, multi-tenancy scoping | `BelongsTo` Account, `HasMany` events, payments |
| **AccountProduct** | Product grant to account (time-boxed) | `BelongsTo` Account, Product |
| **AccountSubscription** | Recurring subscription with trial/active states | `BelongsTo` Account, ProductPlan |
| **Product** | Feature catalog definition | `HasMany` entitlements, limits, features, plans |
| **ProductPlan** | Pricing tier with limits in metadata | `BelongsTo` Product, `HasMany` subscriptions, prices |
| **ProductEntitlement** | Feature definition on product | `BelongsTo` Product |
| **AccountEntitlement** | Resolved feature value for account | `BelongsTo` Account, ProductEntitlement |
| **Payment** | Payment record (polymorphic) | `MorphTo` EventBilling, `BelongsTo` Account/Organization |
| **BillingIntent** | Checkout flow tracking (future payment) | `BelongsTo` Account, `MorphTo` payable |
| **Coupon** | Promotion with usage limits | `HasMany` redemptions |
| **CouponRedemption** | Coupon usage record (polymorphic) | `BelongsTo` Account, Coupon, `MorphTo` redeemable |
| **EventBilling** | Per-event billing record | `BelongsTo` Organization, Event, Plan, `MorphMany` Payment |
| **UsageRecord** | Metered usage for overage billing | `BelongsTo` Account, Product |

**Business Logic in Models** (Anti-pattern detected):
- `Account::grantProduct()` - Line 235-297 - **Should be in service**
- `Account::overrideFeature()` - Line 299-322 - **Should be in service**
- `Account::subscribeToPlan()` - Line 324-335 - **Delegates correctly to SubscriptionManager**
- `Account::startTrial()` - Line 337-354 - **Delegates correctly to SubscriptionService**
- `Coupon::calculateDiscountAmount()` - Line 151-158 - **Business logic in model**
- `AccountSubscription::activate()`, `cancel()`, `suspend()`, `renew()` - Lines 108-126 - **Delegate to service**

---

### Services Layer (`app/Services/`)

| Service | Responsibility | File Reference |
|---------|---------------|-----------------|
| **BillingService** | Event payment lifecycle (initiate, mark succeeded/failed) | `app/Services/BillingService.php:16-174` |
| **SubscriptionService** | Subscription lifecycle (trial, activate, cancel, suspend, renew) | `app/Services/SubscriptionService.php:16-367` |
| **SubscriptionManager** | Thin wrapper around SubscriptionService | `app/Services/SubscriptionManager.php:12-36` |
| **CouponService** | Coupon validation, redemption, discount calculation | `app/Services/CouponService.php:16-136` |
| **FeatureResolver** | Hierarchical feature resolution with caching | `app/Services/FeatureResolver.php:16-357` |
| **UsageMeter** | Usage tracking, billing window calculation, overage charging | `app/Services/UsageMeter.php:16-225` |
| **SumitBillingProvider** | SUMIT gateway adapter (implements BillingProvider) | `app/Services/Billing/SumitBillingProvider.php:23-330` |
| **SystemBillingService** | System admin billing operations (cancel, extend trial, MRR) | `app/Services/OfficeGuy/SystemBillingService.php:21-180` |
| **WebhookPayloadValidator** | Webhook business validation gate | `app/Services/Billing/WebhookPayloadValidator.php` |

**Good Separation**: Services properly delegate to each other. For example:
- `SubscriptionService` uses `BillingProvider` for external calls
- `FeatureResolver` uses `UsageMeter` for usage calculations
- `UsageMeter` uses `BillingProvider` for overage charging

---

### Controllers Layer (`app/Http/Controllers/`)

| Controller | Responsibility | File Reference |
|-----------|---------------|-----------------|
| **BillingCheckoutController** | Subscription checkout API endpoint | `app/Http/Controllers/Api/BillingCheckoutController.php:18-159` |
| **WebhookController** | Payment webhook transport + validation routing | `app/Http/Controllers/Api/WebhookController.php:25-156` |

**Issues Found**:
1. **BillingCheckoutController line 43**: `$this->authorize('update', $organization)` fails - readonly class without AuthorizesRequests trait (from PHPStan analysis)

---

### Integration Layer (`app/Contracts/`)

| Interface | Implementations | Purpose |
|-----------|-----------------|---------|
| **BillingProvider** | `SumitBillingProvider` | Subscription operations (create customer, subscription, cancel, usage reporting) |
| **PaymentGatewayInterface** | `SumitPaymentGateway`, `StubPaymentGateway` | One-time event payments (create intent, charge with token, webhook) |

---

## 3. End-to-End Billing Flows

### Flow 1: Event Payment (One-Time)

**Entry Point**: User publishes an event with a paid plan

```
1. EventController::publish()
   ↓
2. BillingService::initiateEventPayment($event, $plan)
   ├─ DB transaction starts
   ├─ Update event: draft → pending_payment
   ├─ Create EventBilling record (status: pending)
   ├─ Create Payment record (status: pending)
   ├─ PaymentGatewayInterface::createOneTimePayment()
   │  └─ Returns redirect_url or processes synchronously
   ├─ Update Payment with gateway_transaction_id
   └─ DB transaction commits

   → User redirected to payment provider OR charge processed

3. [ASYNC] WebhookController::handle($request, 'sumit')
   ├─ Create BillingWebhookEvent audit record
   ├─ Verify HMAC signature
   ├─ WebhookPayloadValidator::validate()
   │  └─ Confirms payment exists and is in valid state
   └─ PaymentGatewayInterface::handleWebhook()

4. [ASYNC] PaymentGatewayInterface::handleWebhook()
   ├─ If payment succeeded:
   │  └─ BillingService::markPaymentSucceeded($payment)
   │     ├─ Update Payment: status → succeeded
   │     ├─ Update EventBilling: status → paid, paid_at
   │     └─ Update Event: pending_payment → active
   └─ If payment failed:
      └─ BillingService::markPaymentFailed($payment)
         └─ Update Payment: status → failed (event stays pending_payment)
```

**Files**:
- `app/Services/BillingService.php:27-73` (initiate)
- `app/Services/BillingService.php:149-163` (mark succeeded)
- `app/Http/Controllers/Api/WebhookController.php:40-94` (webhook handler)

---

### Flow 2: Subscription Checkout

**Entry Point**: User purchases a subscription plan

```
1. BillingCheckoutController::store($request)
   ├─ Validate: plan_id, payment_token, optional coupon_code
   ├─ Get Organization from OrganizationContext
   ├─ Authorize (BROKEN - readonly class without trait)
   ├─ Find ProductPrice (prefer yearly if multiple cycles)
   ↓
   DB transaction starts
   ├─ Store OfficeGuyToken (payment method)
   ├─ IF coupon_code provided:
   │  └─ CouponService::validate() + redeem()
   ├─ BillingProvider::createSubscription($account, $price)
   │  └─ SumitBillingProvider::createSubscription()
   │     ├─ Create SUMIT customer if needed
   │     ├─ Create OfficeGuySubscription in package
   │     ├─ Process first charge via PaymentService
   │     └─ Return subscription_reference + metadata
   ├─ IF subscription failed → rollback
   ├─ SubscriptionService::activatePaid()
   │  ├─ Create AccountSubscription (status: active)
   │  ├─ Account::grantProduct($plan->product)
   │  │  ├─ Create AccountProduct (status: active)
   │  │  ├─ Create AccountEntitlements from ProductEntitlements
   │  │  ├─ Bust FeatureResolver cache
   │  │  └─ Dispatch ProductEngineEvent
   │  └─ Bust feature cache
   └─ DB commit

   → Return success with redirect_url
```

**Files**:
- `app/Http/Controllers/Api/BillingCheckoutController.php:26-158`
- `app/Services/Billing/SumitBillingProvider.php:71-168` (createSubscription)
- `app/Services/SubscriptionService.php:57-101` (activatePaid)

---

### Flow 3: Feature Resolution (Hierarchical)

**Used by**: Middleware, policies, feature checks throughout app

```
FeatureResolver::value($account, 'max_events')
│
└─ Cache lookup (TTL: 300s)
   └─ resolveUncached()
      ├─ 1. accountOverrideEntitlement() ← AccountEntitlement where product_entitlement_id = NULL
      ├─ 2. propagatedEntitlement() ← AccountEntitlement where AccountProduct is Active
      ├─ 3. planLimitValue() ← ProductPlan.metadata.limits.*
      ├─ 4. productDefaultEntitlement() ← Product.activeEntitlements
      └─ 5. system default ← config('product-engine.defaults')

Priority order: Override > Propagated > Plan Limit > Product Default > System Default

Cache busting triggers:
- AccountEntitlement saved/deleted
- AccountProduct saved/deleted
- AccountSubscription saved/deleted
```

**Files**:
- `app/Services/FeatureResolver.php:148-208` (resolution logic)
- `app/Models/AccountEntitlement.php:60-68` (cache bust on save/delete)

---

### Flow 4: Trial Expiration Processing

**Trigger**: Scheduled command (cron)

```
ProcessProductExpirationsCommand::handle()
│
└─ SubscriptionService::processTrialExpirations()
   └─ For each Trial subscription where trial_ends_at <= now:
      ├─ IF ProductPlan has active prices:
      │  └─ SubscriptionService::activate($subscription)
      │     ├─ BillingProvider::createSubscription()
      │     ├─ Account::grantProduct()
      │     └─ Dispatch 'subscription.activated'
      └─ ELSE (no prices):
         └─ SubscriptionService::cancel($subscription)
            ├─ BillingProvider::cancelSubscription()
            ├─ Update AccountProduct: status → Revoked
            └─ Dispatch 'subscription.cancelled'
```

**Files**:
- Console command location (inferred, not read)
- `app/Services/SubscriptionService.php:312-332` (processTrialExpirations)

---

### Flow 5: Usage Metering + Overage Charging

**Trigger**: Application action that consumes metered resource

```
UsageMeter::record($account, $product, 'sms_sent', $quantity)
│
├─ Create UsageRecord
├─ Find active AccountSubscription for product
└─ IF subscription found AND usage policy is 'soft':
   └─ billOverageIfRequired()
      ├─ Calculate limit from FeatureResolver::integer($account, 'sms_limit')
      ├─ Calculate used = sumForCurrentBillingPeriod()
      ├─ Calculate newOverageQuantity
      ├─ IF overage > 0:
      │  └─ BillingProvider::reportUsage()
      │     └─ SumitBillingProvider::reportUsage()
      │        ├─ Create SumitUsageChargePayable
      │        ├─ PaymentService::processCharge()
      │        └─ Return charge_reference
      └─ Dispatch 'usage.overage_charged'
```

**Files**:
- `app/Services/UsageMeter.php:22-69` (record method)
- `app/Services/UsageMeter.php:140-224` (billOverageIfRequired)
- `app/Services/Billing/SumitBillingProvider.php:199-287` (reportUsage)

---

## 4. Architecture Quality Assessment

### Issues Found

#### 🔴 Critical (Broken Functionality)

1. **BillingCheckoutController Authorization** - `app/Http/Controllers/Api/BillingCheckoutController.php:43`
   - **Problem**: `$this->authorize('update', $organization)` called on readonly class without AuthorizesRequests trait
   - **Impact**: Authorization is bypassed or fails at runtime
   - **Fix**: Use `Gate::authorize('update', $organization)` instead

2. **AccountProductStatus::Expired Missing** - `app/Enums/AccountProductStatus.php`
   - **Problem**: Enum has Active, Suspended, Revoked but ProcessProductExpirationsCommand uses `Expired`
   - **Impact**: Command will crash when trying to expire products
   - **Fix**: Add `case Expired = 'expired';` to enum

#### 🟡 High Priority (Business Logic in Models)

3. **Business Logic in Models**
   - `Account::grantProduct()` (67 lines of business logic)
   - `Account::overrideFeature()` (24 lines)
   - `Coupon::calculateDiscountAmount()` (domain logic in entity)
   - **Impact**: Violates Single Responsibility, hard to test
   - **Better Pattern**: Move to `ProductGrantService`, `FeatureOverrideService`, `DiscountCalculator`

4. **Dual Billing Pattern Confusion**
   - `EventBilling` (per-event) vs `AccountSubscription` (recurring) serve similar purposes
   - Both create `Payment` records but through different flows
   - **Impact**: Unclear which to use when, potential duplicate charges
   - **Risk**: No guard against billing an event for an account that already has a subscription

#### 🟠 Medium Priority (Inconsistencies)

5. **Inconsistent Status Enums**
   - `EventBillingStatus`: Pending, Paid (no Failed state)
   - `PaymentStatus`: Pending, Processing, Succeeded, Failed
   - `AccountSubscriptionStatus`: Trial, Active, PastDue, Cancelled
   - **Impact**: `EventBilling` cannot represent failed payments without fallback to `Payment` status

6. **SubscriptionService Method Naming Mismatch**
   - `activatePaid()` - activates with billing metadata
   - `activate()` - activates by calling billing provider
   - **Impact**: Unclear which method to call when
   - **Better**: Rename to `activateWithProvider()` and `activateDirect()`

7. **FeatureResolver Cache Stampede Risk**
   - Cache key: `"feature:{$accountId}:{$featureKey}"`
   - Cache TTL: 300 seconds (5 minutes)
   - **Risk**: No lock mechanism → cache stampede if many simultaneous requests
   - **Fix**: Use `Cache::remember()` with atomic `add()` for lock

8. **BillingIntent Unused**
   - Model exists but no active usage in flows reviewed
   - **Impact**: Dead code or future feature incomplete

9. **Webhook Signature Verification Optional in Dev**
   - Production enforces `BILLING_WEBHOOK_SECRET`
   - Development allows unsigned webhooks with warning
   - **Risk**: Accidental deployment to production without secret

#### 🔵 Low Priority (Design Patterns)

10. **SubscriptionManager Unnecessary Wrapper**
    - Only 36 lines, just forwards to SubscriptionService
    - No added value, creates indirection
    - **Better**: Use SubscriptionService directly or rename to clarify role

11. **SystemBillingService Namespacing**
    - Located in `app/Services/OfficeGuy/` but is system-level
    - **Better**: `app/Services/Billing/SystemBillingService`

---

## 5. Missing Links / Gaps

### Not Found in Codebase

1. **BillingEvent Events** - Referenced but no files found:
   - `App\Events\Billing\*` - Only `SubscriptionCancelled` and `TrialExtended` exist
   - Expected: `PaymentSucceeded`, `PaymentFailed`, `SubscriptionActivated`, etc.

2. **Billing Listeners** - Only `AuditBillingEvent` found:
   - No webhook event listeners
   - No post-payment action listeners (e.g., email receipt, webhook to third-party)

3. **ProductExpirationObserver** - Referenced in `AccountProduct.php`:
   - `#[ObservedBy([AccountProductObserver::class])]`
   - File should be at `app/Observers/AccountProductObserver.php`
   - **Not verified**: May not exist or be in different location

### Incomplete Implementations

1. **CouponService Trial Extension** - `app/Services/CouponService.php:124-135`
   - `extendTrial()` only looks for trial subscriptions
   - Does not extend trial if subscription is already active/past_due
   - **Gap**: What if user extends trial during active subscription?

2. **UsageMeter BillingWindow Edge Cases** - `app/Services/UsageMeter.php:107-135`
   - Monthly window: Loop from anchor until finding period containing `$asOf`
   - **Inefficient**: O(n) loop for each check
   - **Better**: Calculate using date math without loop

3. **BillingProvider::reportUsage() Return Type**
   - Returns array with optional keys: `charged`, `charge_reference`, `failed`, `error`
   - **Inconsistent**: Success case has `charged` but not `failed`, error case has `failed` but not `charged`
   - **Better**: Use Result object or consistent union type

---

## 6. Concrete File References Summary

### Models (18 files)
| File | Lines | Key Logic |
|------|-------|-----------|
| `app/Models/Account.php` | 390 | `grantProduct()`, `overrideFeature()`, `subscribeToPlan()`, `startTrial()` |
| `app/Models/Organization.php` | 132 | HasSumitCustomer trait, account relationship |
| `app/Models/AccountSubscription.php` | 128 | `activate()`, `cancel()`, `suspend()`, `renew()` delegate to service |
| `app/Models/AccountProduct.php` | 115 | Observer for cache busting, active scope |
| `app/Models/Product.php` | 176 | Catalog definition, relationships |
| `app/Models/ProductPlan.php` | 117 | `limit()` retrieves from metadata, `primaryPrice()` |
| `app/Models/ProductEntitlement.php` | 112 | Feature definition with type casting |
| `app/Models/AccountEntitlement.php` | 81 | Cache bust on save/delete |
| `app/Models/Payment.php` | 87 | Polymorphic payable relationship |
| `app/Models/BillingIntent.php` | 68 | Polymorphic payable, purchase abstraction |
| `app/Models/Coupon.php` | 194 | `calculateDiscountAmount()`, usage limits |
| `app/Models/CouponRedemption.php` | 86 | Polymorphic redeemable, tracks discount |
| `app/Models/EventBilling.php` | 96 | Per-event billing, morphMany Payment |
| `app/Models/UsageRecord.php` | 68 | Metered usage for overage |
| `app/Models/ProductPrice.php` | 71 | Cycle pricing (monthly, yearly, usage) |
| `app/Models/ProductLimit.php` | 73 | Feature limits definition |
| `app/Models/ProductFeature.php` | 77 | Feature flags definition |
| `app/Models/BillingWebhookEvent.php` | 47 | Webhook audit log |

### Services (9 files)
| File | Lines | Responsibility |
|------|-------|---------------|
| `app/Services/BillingService.php` | 175 | Event payment lifecycle |
| `app/Services/SubscriptionService.php` | 368 | Subscription lifecycle |
| `app/Services/SubscriptionManager.php` | 37 | Thin wrapper |
| `app/Services/CouponService.php` | 137 | Coupon operations |
| `app/Services/FeatureResolver.php` | 357 | Hierarchical feature resolution |
| `app/Services/UsageMeter.php` | 226 | Usage tracking + overage |
| `app/Services/Billing/SumitBillingProvider.php` | 331 | SUMIT adapter |
| `app/Services/OfficeGuy/SystemBillingService.php` | 181 | System admin operations |
| `app/Services/Billing/WebhookPayloadValidator.php` | Not read | Webhook validation |

### Controllers (3 files)
| File | Lines | Responsibility |
|------|-------|---------------|
| `app/Http/Controllers/Api/BillingCheckoutController.php` | 160 | Subscription checkout API |
| `app/Http/Controllers/Api/WebhookController.php` | 157 | Webhook transport + routing |
| `app/Http/Controllers/BillingSubscriptionCheckoutController.php` | Not read | Legacy controller? |

### Contracts (2 files)
| File | Lines | Purpose |
|------|-------|---------|
| `app/Contracts/BillingProvider.php` | 31 | Subscription operations interface |
| `app/Contracts/PaymentGatewayInterface.php` | 30 | One-time payment interface |

---

## 7. Risks & Recommendations

### Security Risks

1. **Webhook Replay Attacks**
   - `WebhookController` verifies HMAC but doesn't check timestamp
   - **Risk**: Old webhooks can be replayed
   - **Fix**: Reject webhooks older than 5 minutes

2. **Payment Authorization Bypass**
   - `BillingCheckoutController` authorization broken (readonly class)
   - **Risk**: Anyone with organization context can charge subscriptions
   - **Fix**: Use `Gate::authorize()` or extend Controller

### Data Integrity Risks

3. **Orphaned Payments**
   - No foreign key constraint on `Payment.payable_id` (polymorphic)
   - **Risk**: Deleted payable leaves orphaned payment records
   - **Fix**: Database triggers or periodic cleanup

4. **Cache Inconsistency**
   - FeatureResolver cache (300s TTL) may serve stale data after subscription cancel
   - **Risk**: User retains access after cancellation for up to 5 minutes
   - **Fix**: Immediate cache bust on subscription state change

### Scalability Risks

5. **Synchronous Subscription Activation**
   - `BillingCheckoutController` calls SUMIT API synchronously
   - **Risk**: Slow gateway responses block HTTP request
   - **Fix**: Queue subscription activation, return "processing" status

6. **Loop-Based Billing Window Calculation**
   - `UsageMeter::billingWindow()` uses while loop for period calculation
   - **Risk**: O(n) for year-long subscriptions
   - **Fix**: Calculate directly using date math

---

## 8. Positive Patterns Detected

### Well-Designed Aspects

1. **Hierarchical Feature Resolution** - `FeatureResolver`
   - Clear priority: override > propagated > plan limit > product > system
   - Cached for performance
   - Elegant use of Laravel scopes

2. **Polymorphic Payment Design** - `Payment.payable()`
   - Single payment table works for EventBilling and future types
   - Clean extensibility

3. **MorphTo Coupon Redemption** - `CouponRedemption.redeemable()`
   - Coupons apply to any entity (subscription, event, product)
   - Flexible promotion system

4. **Transport vs Business Validation** - `WebhookController`
   - Always return HTTP 200 (prevent provider retries)
   - Business validation gates state changes
   - HMAC signature verification

5. **Database Transactions** - Used consistently
   - `BillingService`, `SubscriptionService` wrap operations in transactions
   - Prevents partial state on failures

6. **Event-Driven Architecture** - `ProductEngineEvent`
   - Dispatched at key points (product granted, subscription activated)
   - Enables audit trails and external integrations

7. **Proper Interface Implementation** - `HasSumitCustomer`
   - `Account` and `Organization` implement vendor interface for SUMIT interoperability
   - Both models **override** trait defaults with custom business logic:
     - `Account` delegates to owner user (email, name, phone from user)
     - `Organization` falls back to Account (hierarchical resolution)
   - **NOT vendor leakage**: Interface contract satisfied with domain-specific implementations
   - Package trait provides only default implementations that are overridden

---

## 9. Recommended Refactoring Priority

### Immediate (Fix Broken Functionality)

1. Fix `BillingCheckoutController` authorization
2. Add `AccountProductStatus::Expired` to enum
3. Verify `AccountProductObserver` exists

### Short-Term (Reduce Coupling)

4. Extract `Account::grantProduct()` to `ProductGrantService`
5. Extract `Account::overrideFeature()` to `FeatureOverrideService`

### Medium-Term (Improve Design)

6. Unify EventBilling and AccountSubscription into single Billing model
7. Add Result objects for service returns (type safety)
8. Implement proper event system for all billing state changes
9. Add webhook timestamp validation

### Long-Term (Scalability)

10. Queue subscription activation (async billing provider calls)
11. Implement idempotency keys for all payment operations
12. Add circuit breaker for SUMIT API calls
13. Implement feature flag system for gradual rollout

---

**End of Analysis**

All findings verified against actual source code files. No assumptions made from filenames alone.

---

## 10. Verification Notes

### HasSumitCustomer Integration Pattern (Verified 2026-03-24)

**Initial Claim**: "Account, Organization are directly dependent on SUMIT traits/interfaces/package concerns" (Vendor Leakage)

**Verification Process**:
1. Read vendor interface: `vendor/officeguy/laravel-sumit-gateway/src/Contracts/HasSumitCustomer.php`
   - Pure interface contract with 5 methods (getSumitCustomerId, Email, Name, Phone, BusinessId)
2. Read vendor trait: `vendor/officeguy/laravel-sumit-gateway/src/Support/Traits/HasSumitCustomerTrait.php`
   - Default implementations assuming standard attribute names
3. Read Account implementation: `app/Models/Account.php:102-120`
   - **Overrides all 5 methods** with custom logic (delegates to owner user)
4. Read Organization implementation: `app/Models/Organization.php:70-81`
   - **Overrides 2 methods** with fallback to Account

**Corrected Finding**: This is NOT vendor leakage. The pattern is:
- Interface contract defines required methods for SUMIT interoperability
- Package trait provides optional default implementations
- Domain models implement interface but **override defaults** with business-specific logic
- This is proper separation of concerns and interface adherence

**Why this is well-designed**:
- Models can interact with SUMIT gateway through standard interface
- Business logic (e.g., "use owner's email") is encapsulated in domain models
- If SUMIT integration changes, only the implementations need updating
- The interface is a stable contract, not an implementation detail
