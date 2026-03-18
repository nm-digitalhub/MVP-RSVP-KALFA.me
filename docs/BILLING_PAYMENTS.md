# Billing & Payments System — Comprehensive Documentation

> **Generated:** 2026-03-18  
> **Project:** Kalfa.me (Laravel)  
> **Currency:** ILS (Israeli New Shekel) — all amounts stored in **agorot** (1 ILS = 100 agorot)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Product / Plan / Pricing Model](#2-product--plan--pricing-model)
3. [Subscription Lifecycle](#3-subscription-lifecycle)
4. [Payment Gateway Integration (Sumit / OfficeGuy)](#4-payment-gateway-integration-sumit--officeguy)
5. [Entitlements & Feature Gating](#5-entitlements--feature-gating)
6. [Usage Tracking & Metering](#6-usage-tracking--metering)
7. [Credit Ledger System](#7-credit-ledger-system)
8. [Coupon System](#8-coupon-system)
9. [Event Billing (One-Time Payments)](#9-event-billing-one-time-payments)
10. [Webhook Handling](#10-webhook-handling)
11. [Admin & Tenant UI (Livewire)](#11-admin--tenant-ui-livewire)
12. [Operational Monitoring](#12-operational-monitoring)
13. [Configuration Reference](#13-configuration-reference)
14. [Known Gaps & Audit Findings](#14-known-gaps--audit-findings)
15. [Entity Relationship Diagram](#15-entity-relationship-diagram)

---

## 1. Architecture Overview

The billing system is built on two parallel payment abstractions:

| Concern | Interface | Implementation | Purpose |
|---------|-----------|----------------|---------|
| **One-time payments** (events) | `PaymentGatewayInterface` | `SumitPaymentGateway` / `StubPaymentGateway` | Redirect or token-based single charges |
| **Recurring subscriptions** | `BillingProvider` | `SumitBillingProvider` | Customer creation, recurring charges, usage reporting |

Both are resolved via the Laravel service container in `AppServiceProvider`:

```
PaymentGatewayInterface → SumitPaymentGateway (when BILLING_GATEWAY=sumit) or StubPaymentGateway
BillingProvider          → SumitBillingProvider (always)
```

### Key Service Classes

| Service | File | Role |
|---------|------|------|
| `BillingService` | `app/Services/BillingService.php` | Orchestrates one-time event payments |
| `SubscriptionService` | `app/Services/SubscriptionService.php` | Full subscription lifecycle (trial → active → cancel) |
| `SubscriptionManager` | `app/Services/SubscriptionManager.php` | Thin facade over SubscriptionService |
| `FeatureResolver` | `app/Services/FeatureResolver.php` | Resolves feature flags/limits for an account |
| `UsageMeter` | `app/Services/UsageMeter.php` | Records usage, calculates billing windows, bills overage |
| `UsagePolicyService` | `app/Services/UsagePolicyService.php` | Decides allow/block/overage for usage attempts |
| `CouponService` | `app/Services/CouponService.php` | Validates, calculates, and redeems coupons |
| `CreditService` | `app/Services/Billing/CreditService.php` | Append-only credit ledger management |
| `AccountPaymentMethodManager` | `app/Services/Sumit/AccountPaymentMethodManager.php` | CRUD for stored payment methods (OfficeGuyTokens) |
| `ProductIntegrityChecker` | `app/Services/ProductIntegrityChecker.php` | Validates product catalog consistency |
| `ProductEngineOperationsMonitor` | `app/Services/ProductEngineOperationsMonitor.php` | Scheduler heartbeat & task health checks |

---

## 2. Product / Plan / Pricing Model

### Entity Hierarchy

```
Product (catalog item)
├── ProductPlan (purchasable tier, e.g. "Basic", "Pro")
│   ├── ProductPrice (amount + billing_cycle per plan)
│   └── metadata.limits.{feature_key} → numeric limit values
├── ProductEntitlement (feature grants, e.g. "ai_voice_enabled")
├── ProductFeature (feature flags with is_enabled toggle)
└── ProductLimit (named limits with is_active toggle)
```

### Models

#### `Product` (`products`)

| Field | Type | Notes |
|-------|------|-------|
| `name` | string | Display name |
| `slug` | string | URL-safe identifier |
| `description` | text | |
| `category` | string | Grouping |
| `status` | enum: `draft`, `active`, `archived` | Only `active` products are sellable |
| `metadata` | JSON | Extensible |

#### `ProductPlan` (`product_plans`)

| Field | Type | Notes |
|-------|------|-------|
| `product_id` | FK → products | Parent product |
| `name` | string | e.g. "Pro Monthly" |
| `slug` | string | |
| `sku` | string | Auto-generated: `{PRODUCT_SLUG}_{PLAN_SLUG}` |
| `description` | text | |
| `is_active` | boolean | |
| `metadata` | JSON | Contains `limits`, `usage_policies`, `commercial` |
| `sort_order` | int | Display ordering |

**`metadata` structure:**
```json
{
  "limits": {
    "events_limit": 50,
    "guests_per_event_limit": 500
  },
  "usage_policies": {
    "events": {
      "mode": "hard|soft",
      "limit_feature_key": "events_limit"
    }
  },
  "commercial": {
    "overage_metric_key": "events",
    "overage_amount_minor": 500,
    "overage_unit": "event",
    "currency": "ILS"
  }
}
```

#### `ProductPrice` (`product_prices`)

| Field | Type | Notes |
|-------|------|-------|
| `product_plan_id` | FK | |
| `currency` | string | e.g. `ILS` |
| `amount` | int | In agorot (e.g. 9900 = ₪99) |
| `billing_cycle` | enum: `monthly`, `yearly`, `usage` | |
| `is_active` | boolean | |
| `metadata` | JSON | |

#### `ProductEntitlement` (`product_entitlements`)

Defines what features a product grants. Feature keys are **free-form strings** (no predefined enum).

| Field | Type | Notes |
|-------|------|-------|
| `product_id` | FK | |
| `feature_key` | string | e.g. `ai_voice_enabled`, `max_events` |
| `label` | string | Human-readable |
| `value` | string | The granted value |
| `type` | enum: `boolean`, `number`, `text`, `enum` | Determines casting |
| `is_active` | boolean | |
| `constraints` | JSON | Optional |

#### `ProductFeature` / `ProductLimit`

Simpler toggle/limit tables on the product level:
- `ProductFeature`: `feature_key`, `value`, `is_enabled`
- `ProductLimit`: `limit_key`, `value`, `is_active`

#### Legacy `Plan` (`plans`)

A simpler plan model used for **per-event billing** (not subscriptions):

| Field | Type | Notes |
|-------|------|-------|
| `product_id` | FK | |
| `name`, `slug`, `type` | string | e.g. type=`per_event` |
| `limits` | JSON | |
| `price_cents` | int | In agorot |
| `billing_interval` | string | |

---

## 3. Subscription Lifecycle

### State Machine

```
                 ┌──────────┐
      startTrial │  Trial   │
   ─────────────►│          │
                 └────┬─────┘
                      │ trial_ends_at expires
                      │ (processTrialExpirations)
                      ▼
           ┌─────────────────────┐
           │ Has active prices?  │
           └────┬────────────┬───┘
                │ YES        │ NO
                ▼            ▼
          ┌──────────┐  ┌───────────┐
          │  Active  │  │ Cancelled │
          │          │  │           │
          └──┬───┬───┘  └───────────┘
             │   │
    cancel() │   │ payment fails
             ▼   ▼
      ┌──────────┐  ┌──────────┐
      │Cancelled │  │ PastDue  │
      └──────────┘  └────┬─────┘
                         │ renew()
                         ▼
                   ┌──────────┐
                   │  Active  │
                   └──────────┘
```

### Status Enum (`AccountSubscriptionStatus`)

| Status | Value | Meaning |
|--------|-------|---------|
| `Trial` | `trial` | Free trial period, `trial_ends_at` set |
| `Active` | `active` | Paying/active subscription |
| `PastDue` | `past_due` | Payment failed, access may be suspended |
| `Cancelled` | `cancelled` | Terminated, `ends_at` set |

### Key Operations

#### `startTrial(account, plan, trialEndsAt?)`
- Creates `AccountSubscription` with status=`Trial`
- Dispatches `ProductEngineEvent('subscription.trial_started')`
- Does **not** grant product access (trial grants handled via feature resolution)

#### `activate(subscription, grantedBy?)`
- **Within DB transaction:**
  1. Loads plan, product, prices
  2. If plan has a price and no existing billing metadata:
     - Calls `BillingProvider::createCustomer()` → creates SUMIT customer
     - Calls `BillingProvider::createSubscription()` → charges first payment via stored token
     - On payment failure → sets status to `PastDue`, dispatches `subscription.payment_failed`
  3. Updates status to `Active`
  4. Calls `account->grantProduct()` → creates `AccountProduct` row
  5. Clears feature cache
  6. Dispatches `ProductEngineEvent('subscription.activated')`

#### `cancel(subscription)`
- **Within DB transaction:**
  1. Calls `BillingProvider::cancelSubscription()` → cancels on SUMIT side
  2. Sets status to `Cancelled`, sets `ends_at`
  3. Revokes `AccountProduct` (status → `Revoked`, expires_at → now)
  4. Expires related `AccountEntitlement` rows (if no other active subscription for same product)
  5. Clears feature cache
  6. Dispatches `ProductEngineEvent('subscription.cancelled')`

#### `suspend(subscription)`
- Sets status to `PastDue`
- Sets `AccountProduct` status to `Suspended`
- Dispatches `ProductEngineEvent('subscription.suspended')`

#### `renew(subscription)`
- Sets status back to `Active`
- Extends `ends_at` by one billing cycle
- Reactivates or creates `AccountProduct`
- Dispatches `ProductEngineEvent('subscription.renewed')`

#### `processTrialExpirations()` (Scheduled Task)
- Finds all subscriptions where status=`Trial` and `trial_ends_at <= now()`
- If plan has active prices → `activate()` (charges the card)
- If no prices → `cancel()`
- Configured frequency: `everyFiveMinutes` (see `config/product-engine.php`)

### Account-Level Models

#### `AccountSubscription` (`account_subscriptions`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK → accounts | |
| `product_plan_id` | FK → product_plans | |
| `status` | enum | Trial / Active / PastDue / Cancelled |
| `started_at` | datetime | |
| `trial_ends_at` | datetime | nullable |
| `ends_at` | datetime | nullable (null = indefinite) |
| `metadata` | JSON | Contains `billing` sub-key with provider details |

**metadata.billing:**
```json
{
  "provider": "sumit",
  "customer_reference": "12345",
  "subscription_reference": "67890",
  "price_id": 1,
  "currency": "ILS",
  "amount": 9900,
  "billing_cycle": "monthly"
}
```

#### `AccountProduct` (`account_products`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `product_id` | FK | |
| `status` | enum: `active`, `suspended`, `revoked` | |
| `granted_at` | datetime | |
| `expires_at` | datetime | nullable |
| `granted_by` | FK → users | nullable (null = system auto-grant) |
| `metadata` | JSON | Contains `source`, `subscription_id`, `product_plan_id` |

**Model hooks:** On `saved`/`deleted`, flushes the `FeatureResolver` cache for all related feature keys.

---

## 4. Payment Gateway Integration (Sumit / OfficeGuy)

### Overview

The system integrates with **SUMIT** (Israeli payment processor, formerly OfficeGuy) via the `officeguy/laravel-sumit-gateway` package.

Two distinct interfaces handle different payment flows:

### 4.1 `PaymentGatewayInterface` — One-Time Payments

Used for **per-event charges**. Implementations:

#### `SumitPaymentGateway`
- **Redirect flow** (`createOneTimePayment`): Creates payment via `PaymentService::processCharge()` with redirect URLs. Returns `redirect_url` for external checkout.
- **Token flow** (`chargeWithToken`): Charges using a single-use PaymentsJS token. No redirect. Returns sync success/failure.
- **Webhook** (`handleWebhook`): Finds payment by `gateway_transaction_id`, determines success via `ValidPayment`/`Status` fields, calls `BillingService::markPaymentSucceeded/Failed`.

#### `StubPaymentGateway`
- Development stub. Returns fake transaction IDs and redirect URLs.
- Token flow returns `success: false` ("Stub gateway does not support tokenization").

**Gateway selection:** `config('billing.default_gateway')` → env `BILLING_GATEWAY` (default: `stub`).

### 4.2 `BillingProvider` — Recurring Subscriptions

Used for **subscription billing**. Implementation: `SumitBillingProvider`.

#### `createCustomer(account)`
1. If `account->sumit_customer_id` exists → returns existing reference
2. Creates customer in SUMIT via `CreateCustomerRequest` with name, email, phone from account owner
3. Stores `sumit_customer_id` on the Account model

#### `createSubscription(account, price)`
1. Ensures customer exists via `createCustomer()`
2. For `usage` billing cycle → returns placeholder (no recurring charge)
3. For `monthly`/`yearly`:
   - Requires a stored `OfficeGuyToken` (default payment method)
   - Creates `OfficeGuySubscription` record
   - Processes first charge via `PaymentService::processCharge()` with stored token
   - On failure: marks subscription as failed, returns `failed: true`
   - On success: activates, records `RecurringID`

#### `cancelSubscription(subscription)`
- Looks up `metadata.billing.officeguy_subscription_id`
- Calls `OfficeGuySubscriptionService::cancel()`

#### `reportUsage(subscription, metric, quantity, context)`
- For overage billing (soft usage policy)
- Creates a `SumitUsageChargePayable` and charges via stored token
- Returns charge reference

### 4.3 Payment Method Management

`AccountPaymentMethodManager` handles stored cards:

#### `storeSingleUseToken(account, token)`
1. Ensures customer exists in SUMIT
2. Performs ₪1 authorization charge to validate and tokenize
3. Extracts permanent `CreditCard_Token` from response
4. Creates/updates `OfficeGuyToken` record (sets as default)

#### `setDefault(account, token)`
- Updates SUMIT customer's default payment method
- Sets local `is_default` flag

#### `delete(account, token)`
- If default: promotes next token or removes payment method from SUMIT
- Soft-deletes the token

### 4.4 Payable Adapters

The SUMIT package requires `Payable` interface implementations:

- **`EventBillingPayable`**: Wraps `EventBilling` for one-time event payments
- **`SumitUsageChargePayable`**: Wraps usage overage charges

Both provide: amount, currency, customer details, line items, VAT settings.

### 4.5 Customer Search

`OfficeGuyCustomerSearchService` provides multi-source customer lookup:
1. Local model search (Account with sumit_customer_id)
2. Local CRM cache (`CrmEntity` table)
3. Remote SUMIT CRM API (paginated entity search)

---

## 5. Entitlements & Feature Gating

### Resolution Priority

`FeatureResolver` resolves a feature key for an account using this cascade:

```
1. account_override      → AccountEntitlement where product_entitlement_id IS NULL
2. propagated_entitlement → AccountEntitlement linked to active ProductEntitlement
3. plan_limit            → ProductPlan.metadata.limits.{feature_key}
4. product_default       → ProductEntitlement from active AccountProduct
5. system_default        → config('product-engine.defaults')
6. null                  → Feature not found
```

### Key Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `has(account, key)` | bool | Feature exists in any source |
| `enabled(account, key)` | bool | Feature value is truthy |
| `allows(account, key)` | bool | Alias for `enabled()` |
| `value(account, key, default)` | mixed | Raw resolved value |
| `integer(account, key)` | ?int | Numeric value (for limits) |
| `usage(account, metricKey)` | int | Current billing period usage count |
| `remaining(account, limitKey, metricKey)` | ?int | limit - usage (null = unlimited) |
| `allowsUsage(account, limitKey, metricKey, qty)` | bool | Has enough remaining |

### Caching

- Cache key: `feature:{accountId}:{featureKey}`
- TTL: 300 seconds (configurable via `PRODUCT_ENGINE_FEATURE_CACHE_TTL`)
- **Invalidation:** Automatic on `AccountSubscription`, `AccountProduct`, or `AccountEntitlement` save/delete (via model `booted()` hooks)

### AccountEntitlement (`account_entitlements`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `feature_key` | string | Free-form key |
| `value` | string | |
| `type` | enum: `boolean`, `number`, `text`, `enum` | |
| `product_entitlement_id` | FK nullable | null = manual override |
| `expires_at` | datetime nullable | Expired entries are ignored |

---

## 6. Usage Tracking & Metering

### Recording Usage

`UsageMeter::record(account, product, metricKey, quantity, recordedAt?, metadata?)`

1. Creates `UsageRecord` row
2. Finds active subscription for the product
3. Checks if overage billing is required (`billOverageIfRequired`)
4. Dispatches `ProductEngineEvent('usage.recorded')`

### Overage Billing Logic

Triggered when:
- Usage policy mode = `soft` (from `productPlan.metadata.usage_policies.{metric}.mode`)
- The metric matches `commercial.overage_metric_key`
- `commercial.overage_amount_minor` > 0
- Total usage exceeds the feature limit

Calculates `newOverageQuantity` = new overage units since last record, then:
- Calls `BillingProvider::reportUsage()` → SUMIT charge
- Stores billing metadata on the usage record
- Dispatches `ProductEngineEvent('usage.overage_charged')`

### Usage Policy Service

`UsagePolicyService::check(account, metricKey, quantity?)` → returns:

| Decision | Value | Meaning |
|----------|-------|---------|
| `Allowed` | `allowed` | Under limit or no limit defined |
| `AllowedWithOverage` | `allowed_with_overage` | Over limit, but soft policy (will be charged) |
| `Blocked` | `blocked` | Over limit, hard policy |

Default policy: `hard` (configurable via `PRODUCT_ENGINE_USAGE_POLICY`)

### Billing Windows

`UsageMeter::billingWindow(subscription, asOf)` returns `[start, end]`:
- **Monthly**: Anchored to subscription start date, rolls monthly
- **Yearly**: Anchored to start, rolls yearly
- **Usage**: Calendar month (`startOfMonth` → `startOfMonth + 1`)

### Models

#### `UsageRecord` (`usage_records`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `product_id` | FK | |
| `metric_key` | string | e.g. `events`, `ai_calls` |
| `quantity` | int | |
| `recorded_at` | datetime | |
| `metadata` | JSON | May contain `billing` sub-key for overage charges |

#### `AccountFeatureUsage` (`account_feature_usage`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `feature_key` | string | |
| `period_key` | string | e.g. `2026-03` |
| `usage_count` | int | |
| `metadata` | JSON | |

---

## 7. Credit Ledger System

### Design Principles

- **Append-only ledger** — no updates or deletes on `AccountCreditTransaction`
- **Atomic mutations** — all operations use `DB::transaction()` + `SELECT FOR UPDATE` on accounts
- **Balance unit**: agorot (integer)
- **Balance tracking**: both per-row snapshot (`balance_after_agorot`) and live account field (`credit_balance_agorot`)

### `CreditService` Operations

#### `credit(account, amountAgorot, source, description, ...)`
- Adds funds. Creates `type=credit` ledger entry.
- Sources: `Manual`, `Coupon`, `Refund`, `Adjustment`, `Migration`, `SubscriptionCycle`

#### `debit(account, amountAgorot, source, description, ...)`
- Deducts funds. Throws `InsufficientCreditException` if insufficient balance.
- Sources: `CheckoutApplied`, `Expiry`

#### `applyToCheckout(account, chargeAgorot, reference?, currency?)`
- Auto-applies min(available, charge) at checkout
- Returns `CreditApplicationResult { applied, remainingCharge, transaction }`
- Used by `BillingService::initiateEventPayment()` to reduce SUMIT charge

#### `reverseDebit(debitTx, actorId?)`
- Creates compensating credit linked to original debit
- Idempotent: throws `AlreadyReversedException` if already reversed

#### `getAvailableBalance(account, currency)`
- Cached (60s TTL), expiry-aware
- Computes: sum(non-expired credits) - sum(all debits)

### `AccountCreditTransaction` (`account_credit_transactions`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `type` | string: `credit` / `debit` | Direction |
| `source` | enum (CreditSource) | Origin classification |
| `amount_agorot` | int | Always positive |
| `balance_after_agorot` | int | Snapshot at insert time |
| `currency` | string | Default `ILS` |
| `description` | string | |
| `reference_type` | string nullable | Polymorphic (Coupon, Payment, self) |
| `reference_id` | int nullable | |
| `expiry_at` | datetime nullable | Credit expiration |
| `actor_id` | FK → users nullable | Who performed the action |

---

## 8. Coupon System

### Coupon Model (`coupons`)

| Field | Type | Notes |
|-------|------|-------|
| `code` | string | Uppercase, unique |
| `description` | text | |
| `discount_type` | enum | `percentage`, `fixed`, `trial_extension` |
| `discount_value` | int | Percentage (0-100), NIS amount, or days |
| `discount_duration_months` | int nullable | How long discount lasts |
| `target_type` | enum | `global`, `subscription`, `plan`, `event_billing` |
| `target_ids` | JSON nullable | Specific plan IDs (when target_type=plan) |
| `max_uses` | int nullable | Total redemption cap |
| `max_uses_per_account` | int nullable | Per-account cap |
| `first_time_only` | boolean | Only for first-time purchasers |
| `is_active` | boolean | |
| `expires_at` | datetime nullable | |
| `created_by` | FK → users | |

### Discount Types

| Type | Behavior |
|------|----------|
| `Percentage` | Reduces charge by N% |
| `Fixed` | Reduces charge by N ILS (converted to agorot) |
| `TrialExtension` | Extends active trial by N days (no monetary discount) |

### CouponService Flow

1. **`validate(code, account, targetType, planId?)`**
   - Finds active, non-expired coupon by code
   - Checks target applicability
   - Checks global usage cap
   - Checks per-account cap
   - Checks first_time_only flag
   - Returns `Coupon` or throws `InvalidArgumentException` (Hebrew error messages)

2. **`calculateDiscount(coupon, amountMinor)`**
   - Returns discount in agorot (capped at original amount)

3. **`applyToCharge(coupon, originalAmountMinor)`**
   - Returns `{ amount, discount }`

4. **`redeem(coupon, account, redeemedBy, redeemable?, discountApplied)`**
   - Creates `CouponRedemption` record
   - For `TrialExtension`: extends active trial's `trial_ends_at`

### CouponRedemption (`coupon_redemptions`)

| Field | Type | Notes |
|-------|------|-------|
| `coupon_id` | FK | |
| `account_id` | FK | |
| `redeemed_by` | FK → users | |
| `redeemable_type` / `redeemable_id` | morph | What it was applied to |
| `discount_applied` | int | In agorot |
| `trial_days_added` | int | Days added (trial_extension type) |
| `metadata` | JSON | Snapshot of coupon terms at redemption |

### API Endpoints

- **`POST /api/billing/coupon/validate`** — `CouponValidationController`
  - Input: `code`, `plan_id?`, `amount_minor`
  - Returns: discount details for UI preview (no redemption)

- Coupons are redeemed within `SubscriptionPurchaseController` during checkout.

---

## 9. Event Billing (One-Time Payments)

### Flow

```
Event (Draft)
  │
  ├── POST /api/organizations/{org}/events/{event}/checkout
  │   ├── Token flow (PaymentsJS token provided):
  │   │   1. Event → PendingPayment
  │   │   2. Create EventBilling (Pending)
  │   │   3. Create Payment (Pending)
  │   │   4. SumitPaymentGateway::chargeWithToken()
  │   │   5. Payment → Processing (webhook is source of truth)
  │   │   └── Return { status: "processing", payment_id }
  │   │
  │   └── Redirect flow (no token):
  │       1. Event → PendingPayment
  │       2. Apply available credits (CreditService)
  │       3. Create EventBilling (Pending)
  │       4. Create Payment (Pending, reduced amount)
  │       5. SumitPaymentGateway::createOneTimePayment()
  │       └── Return { redirect_url }
  │
  └── Webhook arrives (POST /api/webhooks/{gateway})
      ├── markPaymentSucceeded():
      │   Payment → Succeeded
      │   EventBilling → Paid
      │   Event → Active
      └── markPaymentFailed():
          Payment → Failed
```

### EventBilling (`events_billing`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `organization_id` | FK | |
| `event_id` | FK | |
| `plan_id` | FK → plans (legacy) | |
| `amount_cents` | int | In agorot |
| `currency` | string | |
| `status` | enum: `pending`, `paid`, `cancelled` | |
| `paid_at` | datetime nullable | |

### Payment (`payments`)

| Field | Type | Notes |
|-------|------|-------|
| `account_id` | FK | |
| `organization_id` | FK | |
| `payable_type` / `payable_id` | morph | → EventBilling |
| `amount_cents` | int | In agorot (may be reduced by credits) |
| `currency` | string | |
| `status` | enum: `pending`, `processing`, `succeeded`, `failed`, `refunded`, `cancelled` | |
| `gateway` | string | e.g. `sumit`, `stub` |
| `gateway_transaction_id` | string nullable | SUMIT PaymentID |
| `gateway_response` | JSON | Raw gateway response |

---

## 10. Webhook Handling

### Endpoint

`POST /api/webhooks/{gateway}` → `WebhookController::handle()`

### Flow

1. **Signature verification** (for `sumit` gateway):
   - If `BILLING_WEBHOOK_SECRET` is set, verifies `X-Webhook-Signature` header
   - Uses `WebhookService::verifySignature()`

2. **Idempotency check**:
   - If payment with matching `gateway_transaction_id` already has `succeeded`/`failed` status → return 200

3. **Log webhook**:
   - Creates `BillingWebhookEvent` record with source, event_type, payload

4. **Process**:
   - Delegates to `PaymentGatewayInterface::handleWebhook()`
   - `SumitPaymentGateway::handleWebhook()`:
     - Extracts transaction ID from `PaymentID`/`TransactionID`/`ID`
     - Finds matching `Payment` record
     - Normalizes status from `ValidPayment`, `Status`, or nested `Payment.ValidPayment`
     - Calls `BillingService::markPaymentSucceeded()` or `markPaymentFailed()`

5. **Mark processed**:
   - Sets `processed_at` on the webhook event

### BillingWebhookEvent (`billing_webhook_events`)

| Field | Type | Notes |
|-------|------|-------|
| `source` | string | e.g. `sumit` |
| `event_type` | string nullable | |
| `payload` | JSON | Full webhook payload |
| `processed_at` | datetime nullable | |

### Webhook Configuration (`config/officeguy-webhooks.php`)

- **Async:** true (queued processing)
- **Queue:** `default`
- **Max retries:** 3
- **Timeout:** 30 seconds
- **Backoff:** Exponential (10s → 100s → 1000s)
- **SSL verification:** true

---

## 11. Admin & Tenant UI (Livewire)

### Tenant-Facing (Billing namespace)

| Component | Route | Purpose |
|-----------|-------|---------|
| `Billing\PlanSelection` | `/billing/plans` | Choose plan, start trial, or purchase |
| `Billing\AccountOverview` | `/billing/account` | Account overview, auto-creates account |
| `Billing\EntitlementsIndex` | `/billing/entitlements` | CRUD account entitlements |
| `Billing\UsageIndex` | `/billing/usage` | Read-only usage history |
| `Billing\BillingIntentsIndex` | `/billing/intents` | Billing intent history |

### Checkout Controllers

| Controller | Route | Purpose |
|------------|-------|---------|
| `BillingSubscriptionCheckoutController` | `GET /billing/checkout/{plan}` | Renders PaymentsJS checkout page for subscription |
| `CheckoutTokenizeController` | `GET /checkout/{org}/{event}` | Renders PaymentsJS page for event payment |
| `CheckoutStatusController` | `GET /checkout/{payment}/status` | Payment status polling page |

### System Admin (Livewire)

| Component | Purpose |
|-----------|---------|
| `System\Products\CreateProductWizard` | Product catalog management |
| `System\Products\ProductTree` | Hierarchical product view |
| `System\Products\Show` | Product detail with plans/prices/entitlements |
| `System\Coupons\Index` | Coupon list |
| `System\Coupons\CreateCouponWizard` | Create coupon |
| `System\Coupons\EditCoupon` | Edit coupon |
| `System\Accounts\Show` | Account detail (includes billing methods section) |
| `System\Organizations\Show` | Organization detail (includes billing actions) |
| `AccountPaymentMethodController` | CRUD for stored payment methods |

---

## 12. Operational Monitoring

### `ProductEngineOperationsMonitor`

Tracks health of scheduled billing tasks:

#### Scheduler Heartbeat
- Cache key: `product_engine:operations:scheduler_heartbeat_at`
- Max age: 120 seconds (configurable)
- Status: `healthy` or `stale`

#### Task Monitoring

| Task | Config Key | Default Frequency |
|------|-----------|-------------------|
| `trial_expirations` | `product-engine.operations.trial_expirations` | Every 5 minutes |
| `integrity_checks` | `product-engine.operations.integrity_checks` | Hourly |

Each task tracks: `last_started_at`, `last_finished_at`, `status`, `last_exit_code`

### `ProductIntegrityChecker`

Validates product catalog consistency:
- Duplicate entitlement feature keys within a product
- Inconsistent entitlement types for same feature key
- Active plans missing active prices
- Non-numeric limit values in plan metadata

Can be run on-demand (`reportAll()`) or as part of the scheduled integrity check.

### ProductEngineEvent

All billing/subscription events are dispatched as `ProductEngineEvent`:

| Event | Level | Trigger |
|-------|-------|---------|
| `subscription.trial_started` | info | Trial begins |
| `subscription.activated` | info | Subscription activated |
| `subscription.cancelled` | info | Subscription cancelled |
| `subscription.suspended` | warning | Payment failed |
| `subscription.renewed` | info | Subscription renewed |
| `subscription.payment_failed` | warning | First charge failed |
| `usage.recorded` | info | Usage metric recorded |
| `usage.overage_charged` | info | Overage billed |
| `limits.exceeded` | warning/info | Usage exceeds limit |

---

## 13. Configuration Reference

### `config/billing.php`

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `default_gateway` | `BILLING_GATEWAY` | `stub` | `sumit` or `stub` |
| `sumit.redirect_success_url` | `BILLING_SUMIT_SUCCESS_URL` | — | Post-payment redirect |
| `sumit.redirect_cancel_url` | `BILLING_SUMIT_CANCEL_URL` | — | Cancellation redirect |
| `webhook_secret` | `BILLING_WEBHOOK_SECRET` | — | HMAC-SHA256 secret |

### `config/product-engine.php`

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `feature_cache_ttl` | `PRODUCT_ENGINE_FEATURE_CACHE_TTL` | 300 | Feature cache seconds |
| `cache_store` | `PRODUCT_ENGINE_CACHE_STORE` | (default) | Cache driver |
| `usage.default_policy` | `PRODUCT_ENGINE_USAGE_POLICY` | `hard` | `hard` or `soft` |
| `operations.trial_expirations.enabled` | `PRODUCT_ENGINE_TRIAL_EXPIRATIONS_ENABLED` | true | |
| `operations.trial_expirations.frequency` | `PRODUCT_ENGINE_TRIAL_EXPIRATIONS_FREQUENCY` | `everyFiveMinutes` | |
| `operations.integrity_checks.enabled` | `PRODUCT_ENGINE_INTEGRITY_CHECKS_ENABLED` | true | |
| `operations.integrity_checks.frequency` | `PRODUCT_ENGINE_INTEGRITY_CHECKS_FREQUENCY` | `hourly` | |

### `config/officeguy.php`

| Key | Env | Description |
|-----|-----|-------------|
| `company_id` | `OFFICEGUY_COMPANY_ID` | SUMIT company ID |
| `private_key` | `OFFICEGUY_PRIVATE_KEY` | API private key |
| `public_key` | `OFFICEGUY_PUBLIC_KEY` | PaymentsJS public key |
| `environment` | `OFFICEGUY_ENVIRONMENT` | `www` (production) or `dev`/`test` |
| `pci` | `OFFICEGUY_PCI_MODE` | `no` (PaymentsJS) / `redirect` / `yes` (PCI) |
| `customer_model_class` | — | `App\Models\Account` (hardcoded) |
| `models.customer` | — | `App\Models\Account` |
| `models.order` | — | `App\Models\EventBilling` |

---

## 14. Known Gaps & Audit Findings

> Based on `BILLING_AUDIT_EXECUTIVE_SUMMARY.md`

### 🔴 Critical

1. **No billing middleware** — Organization-scoped routes (`/dashboard/*`) don't check if the account has active products or subscriptions. Users can access the dashboard without paying.

2. **Trial expiry not enforced at request time** — `FeatureResolver` includes trial subscriptions in `activeSubscriptions()` but never checks if `trial_ends_at < now()`. The `processTrialExpirations()` cron handles eventual cleanup, but there's a window where expired trials still have access.

3. **Missing `EnsureAccountActive` middleware** — The intended flow requires a middleware that checks:
   ```php
   $account->activeAccountProducts()->exists() || $account->activeSubscriptions()->exists()
   ```
   This file does not exist.

### ⚠️ High

4. **API routes lack billing enforcement** — `/api/*` routes only require `auth:sanctum`, not billing status.

5. **Account suspension not enforced** — `Organization::is_suspended` exists but is undocumented/unenforced.

### 🟡 Medium

6. **No `accounts.status` field** — Account active/suspended/trial_expired state is inferred from relations, making queries complex.

7. **Feature cache invalidation is eager** — Every subscription/product/entitlement save flushes all related feature keys, which could be optimized.

### ✅ Working Correctly

- Product/plan/pricing data model
- Permission sync via `AccountProduct` observer
- Event creation policy (checks Spatie permissions)
- Payment integration (SUMIT charges, webhooks)
- Credit ledger (atomic, append-only)
- Coupon validation and redemption
- Usage tracking and overage billing

---

## 15. Entity Relationship Diagram

```
┌──────────────┐     ┌──────────────────┐     ┌───────────────┐
│   Account    │────▶│ AccountSubscription│────▶│  ProductPlan  │
│              │     │                    │     │               │
│ credit_bal   │     │ status (enum)      │     │ metadata      │
│ sumit_cust_id│     │ trial_ends_at      │     │  .limits      │
└──────┬───────┘     │ ends_at            │     │  .usage_pol   │
       │             └────────────────────┘     │  .commercial  │
       │                                        └───────┬───────┘
       │                                                │
       │             ┌──────────────────┐               │
       ├────────────▶│  AccountProduct   │◀──── Product ◀┘
       │             │                   │     ┌───────────────┐
       │             │ status (enum)     │     │ entitlements   │
       │             │ granted_by        │     │ features       │
       │             │ expires_at        │     │ limits         │
       │             └───────────────────┘     │ plans          │
       │                                       └───────────────┘
       │
       ├─▶ AccountEntitlement ─── feature_key, value, type, expires_at
       │     └── ProductEntitlement (optional link)
       │
       ├─▶ AccountFeatureUsage ── feature_key, period_key, usage_count
       │
       ├─▶ AccountCreditTransaction ── type(cr/dr), source, amount_agorot
       │
       ├─▶ UsageRecord ── product_id, metric_key, quantity, recorded_at
       │
       ├─▶ Payment ── payable(morph), gateway, status, gateway_transaction_id
       │
       └─▶ CouponRedemption ── coupon_id, discount_applied, trial_days_added

┌──────────────┐     ┌──────────────────┐
│  EventBilling│────▶│    Payment       │
│              │     │  (morph payable) │
│ plan_id      │     └──────────────────┘
│ amount_cents │
│ status       │
└──────────────┘

┌──────────────┐
│    Coupon    │────▶ CouponRedemption
│ code, type   │
│ target_type  │
│ max_uses     │
└──────────────┘

┌─────────────────────┐
│ BillingWebhookEvent │ ── source, event_type, payload, processed_at
└─────────────────────┘

┌─────────────────────┐
│    BillingIntent    │ ── account_id, status, intent_type, payable(morph)
└─────────────────────┘
```

---

## Appendix: File Index

### Models
| File | Model |
|------|-------|
| `app/Models/Product.php` | Product |
| `app/Models/Plan.php` | Plan (legacy per-event) |
| `app/Models/ProductPlan.php` | ProductPlan |
| `app/Models/ProductPrice.php` | ProductPrice |
| `app/Models/ProductEntitlement.php` | ProductEntitlement |
| `app/Models/ProductFeature.php` | ProductFeature |
| `app/Models/ProductLimit.php` | ProductLimit |
| `app/Models/AccountSubscription.php` | AccountSubscription |
| `app/Models/AccountProduct.php` | AccountProduct |
| `app/Models/AccountEntitlement.php` | AccountEntitlement |
| `app/Models/AccountFeatureUsage.php` | AccountFeatureUsage |
| `app/Models/AccountCreditTransaction.php` | AccountCreditTransaction |
| `app/Models/UsageRecord.php` | UsageRecord |
| `app/Models/Payment.php` | Payment |
| `app/Models/EventBilling.php` | EventBilling |
| `app/Models/BillingIntent.php` | BillingIntent |
| `app/Models/BillingWebhookEvent.php` | BillingWebhookEvent |
| `app/Models/Coupon.php` | Coupon |
| `app/Models/CouponRedemption.php` | CouponRedemption |

### Services
| File | Class |
|------|-------|
| `app/Services/BillingService.php` | BillingService |
| `app/Services/SubscriptionService.php` | SubscriptionService |
| `app/Services/SubscriptionManager.php` | SubscriptionManager |
| `app/Services/FeatureResolver.php` | FeatureResolver |
| `app/Services/UsageMeter.php` | UsageMeter |
| `app/Services/UsagePolicyService.php` | UsagePolicyService |
| `app/Services/CouponService.php` | CouponService |
| `app/Services/ProductIntegrityChecker.php` | ProductIntegrityChecker |
| `app/Services/ProductEngineOperationsMonitor.php` | ProductEngineOperationsMonitor |
| `app/Services/SumitPaymentGateway.php` | SumitPaymentGateway |
| `app/Services/StubPaymentGateway.php` | StubPaymentGateway |
| `app/Services/Billing/CreditService.php` | CreditService |
| `app/Services/Billing/CreditApplicationResult.php` | CreditApplicationResult |
| `app/Services/Billing/SumitBillingProvider.php` | SumitBillingProvider |
| `app/Services/Sumit/AccountPaymentMethodManager.php` | AccountPaymentMethodManager |
| `app/Services/Sumit/EventBillingPayable.php` | EventBillingPayable |
| `app/Services/Sumit/SumitUsageChargePayable.php` | SumitUsageChargePayable |
| `app/Services/Sumit/OfficeGuyCustomerSearchService.php` | OfficeGuyCustomerSearchService |
| `app/Services/OfficeGuy/DocumentService.php` | DocumentService |
| `app/Services/OfficeGuy/SystemBillingService.php` | SystemBillingService |

### Contracts
| File | Interface |
|------|-----------|
| `app/Contracts/PaymentGatewayInterface.php` | PaymentGatewayInterface |
| `app/Contracts/BillingProvider.php` | BillingProvider |

### Enums
| File | Enum |
|------|------|
| `app/Enums/AccountSubscriptionStatus.php` | Trial, Active, PastDue, Cancelled |
| `app/Enums/AccountProductStatus.php` | Active, Suspended, Revoked |
| `app/Enums/PaymentStatus.php` | Pending, Processing, Succeeded, Failed, Refunded, Cancelled |
| `app/Enums/EventBillingStatus.php` | Pending, Paid, Cancelled |
| `app/Enums/ProductStatus.php` | Draft, Active, Archived |
| `app/Enums/ProductPriceBillingCycle.php` | Monthly, Yearly, Usage |
| `app/Enums/EntitlementType.php` | Boolean, Number, Text, Enum |
| `app/Enums/CouponDiscountType.php` | Percentage, Fixed, TrialExtension |
| `app/Enums/CouponTargetType.php` | Global, Subscription, Plan, EventBilling |
| `app/Enums/CreditSource.php` | Manual, Coupon, Refund, CheckoutApplied, SubscriptionCycle, Adjustment, Migration, Chargeback, Expiry |
| `app/Enums/UsagePolicyDecision.php` | Allowed, AllowedWithOverage, Blocked |

### Controllers
| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/CheckoutController.php` | One-time event payment initiation |
| `app/Http/Controllers/Api/SubscriptionPurchaseController.php` | Subscription checkout (token → subscribe → activate) |
| `app/Http/Controllers/Api/CouponValidationController.php` | Coupon preview (no redemption) |
| `app/Http/Controllers/Api/PaymentController.php` | Payment status polling |
| `app/Http/Controllers/Api/WebhookController.php` | Gateway webhook handler |
| `app/Http/Controllers/BillingSubscriptionCheckoutController.php` | Subscription checkout page (view) |
| `app/Http/Controllers/CheckoutTokenizeController.php` | Event checkout page (view) |
| `app/Http/Controllers/CheckoutStatusController.php` | Payment status page (view) |
| `app/Http/Controllers/System/AccountPaymentMethodController.php` | Admin: manage stored cards |

### Config
| File | Purpose |
|------|---------|
| `config/billing.php` | Gateway selection, SUMIT URLs, webhook secret |
| `config/product-engine.php` | Feature cache, usage policy, scheduled tasks |
| `config/officeguy.php` | SUMIT credentials, PCI mode, subscriptions, documents |
| `config/officeguy-webhooks.php` | Webhook queue, retries, backoff |
