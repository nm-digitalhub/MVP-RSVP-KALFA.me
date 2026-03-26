# Kalfa.me Event SaaS - Complete Architecture Documentation

**Last Updated:** 2026-03-24
**Laravel Version:** 12.x
**PHP Version:** 8.4
**Database:** PostgreSQL

---

## 🏗️ Architecture Overview

Multi-tenant Event SaaS platform with:
- **Row-Level Tenancy** via `organization_id` FK
- **Billing Layer** via `accounts` entity
- **Product Engine** with hierarchical feature resolution
- **Twilio Integration** for voice RSVP
- **Sumit Payment Gateway** (officeguy/laravel-sumit-gateway)
- **WebAuthn/Passkey** authentication (laragear/webauthn)
- **Spatie Permissions** with team/organization scoping

---

## 📊 Complete Relationship Map (91 Explicit Relationships)

### Core Tenant & Billing Models

#### Account (10 relationships)
```php
owner() → User (owner_user_id)
organizations() → Organization (account_id)
eventsBilling() → EventBilling (account_id)
payments() → Payment (account_id)
entitlements() → AccountEntitlement (account_id)
accountProducts() → AccountProduct (account_id)
featureUsage() → AccountFeatureUsage (account_id)
billingIntents() → BillingIntent (account_id)
subscriptions() → AccountSubscription (account_id)
paymentMethods() → OfficeGuyToken (morph: owner)
```

#### Organization (6 relationships)
```php
account() → Account (account_id)
users() → User (pivot: organization_users)
events() → Event (organization_id)
eventsBilling() → EventBilling (organization_id)
payments() → Payment (organization_id)
invitations() → OrganizationInvitation (organization_id)
```

#### Event (6 relationships)
```php
organization() → Organization (organization_id)
guests() → Guest (event_id)
invitations() → Invitation (event_id)
eventTables() → EventTable (event_id)
seatAssignments() → SeatAssignment (event_id)
eventBilling() → EventBilling (hasOne, event_id)
```

---

### Event Data Models

#### Guest (4 relationships)
```php
event() → Event (event_id)
invitation() → Invitation (hasOne, guest_id)
rsvpResponses() → RsvpResponse (guest_id)
seatAssignment() → SeatAssignment (hasOne, guest_id)
```

#### Invitation (3 relationships)
```php
event() → Event (event_id)
guest() → Guest (guest_id)
rsvpResponses() → RsvpResponse (invitation_id)
```

#### SeatAssignment (3 relationships)
```php
event() → Event (event_id)
guest() → Guest (guest_id)
eventTable() → EventTable (event_table_id)
```

#### EventTable (2 relationships)
```php
event() → Event (event_id)
seatAssignments() → SeatAssignment (event_table_id)
```

#### RsvpResponse (2 relationships)
```php
invitation() → Invitation (invitation_id)
guest() → Guest (guest_id)
```

---

### Billing & Payment Models

#### Payment (3 relationships)
```php
account() → Account (account_id)
organization() → Organization (organization_id)
payable() → MorphTo (payable_type, payable_id)
```

#### EventBilling (5 relationships)
```php
account() → Account (account_id)
organization() → Organization (organization_id)
event() → Event (event_id)
plan() → Plan (plan_id)
payments() → Payment (morphMany: payable)
```

#### AccountSubscription (2 relationships)
```php
account() → Account (account_id)
productPlan() → ProductPlan (product_plan_id)
```

#### AccountProduct (3 relationships)
```php
account() → Account (account_id)
product() → Product (product_id)
grantedBy() → User (granted_by)
```

#### BillingIntent (2 relationships)
```php
account() → Account (account_id)
payable() → MorphTo (payable_type, payable_id)
```

---

### Product Engine Models

#### Product (9 relationships)
```php
entitlements() → ProductEntitlement (product_id)
productEntitlements() → ProductEntitlement (alias)
limits() → ProductLimit (product_id)
features() → ProductFeature (product_id)
plans() → Plan (product_id)
productPlans() → ProductPlan (product_id)
accountProducts() → AccountProduct (product_id)
usageRecords() → UsageRecord (product_id)
```

#### ProductPlan (4 relationships)
```php
product() → Product (product_id)
prices() → ProductPrice (product_plan_id)
subscriptions() → AccountSubscription (product_plan_id)
activePrices() → scoped: prices()->where('is_active', true)
```

#### ProductPrice (1 relationship)
```php
productPlan() → ProductPlan (product_plan_id)
```

#### ProductEntitlement (2 relationships)
```php
product() → Product (product_id)
accountEntitlements() → AccountEntitlement (product_entitlement_id)
```

#### AccountEntitlement (2 relationships)
```php
account() → Account (account_id)
productEntitlement() → ProductEntitlement (product_entitlement_id)
```

---

### Usage & Tracking Models

#### UsageRecord (2 relationships)
```php
account() → Account (account_id)
product() → Product (product_id)
```

#### AccountFeatureUsage (1 relationship)
```php
account() → Account (account_id)
```

#### CouponRedemption (4 relationships)
```php
coupon() → Coupon (coupon_id)
account() → Account (account_id)
redeemedBy() → User (redeemed_by)
redeemable() → MorphTo (redeemable_type, redeemable_id)
```

#### Coupon (2 relationships)
```php
creator() → User (created_by)
redemptions() → CouponRedemption (coupon_id)
```

#### AccountCreditTransaction (3 relationships)
```php
account() → Account (account_id)
actor() → User (actor_id)
reference() → MorphTo (reference_type, reference_id)
```

---

### Organization Management Models

#### User (2 BelongsToMany relationships)
```php
organizations() → Organization (pivot: organization_users)
ownedOrganizations() → Organization (pivot: organization_users, wherePivot role=Owner)
```

#### OrganizationInvitation (1 relationship)
```php
organization() → Organization (organization_id)
```

#### OrganizationUser (Pivot model - 2 relationships)
```php
organization() → Organization (organization_id)
user() → User (user_id)
```

---

### System Models

#### SystemAuditLog (2 relationships)
```php
actor() → User (actor_id)
target() → MorphTo (target_type, target_id)
```

#### BillingWebhookEvent
**NO RELATIONSHIPS** - log-only model

---

## 🔑 Relationship Type Summary

| Type | Count | Models Using It |
|------|-------|----------------|
| BelongsTo | 42 | All models |
| HasMany | 35 | Most models |
| BelongsToMany | 3 | User (2), Organization |
| HasOne | 3 | Guest (invitation), Guest (seatAssignment), Event (eventBilling) |
| MorphTo | 5 | Payment, BillingIntent, CouponRedemption, AccountCreditTransaction, SystemAuditLog |
| MorphMany | 3 | Account (paymentMethods), EventBilling (payments), Payment (inverse) |
| **TOTAL** | **91** | **31 models** |

---

## 🏢 Multi-Tenant Architecture

### Three-Layer Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                      ACCOUNT (Billing)                         │
│  - Subscriptions (AccountSubscription)                             │
│  - Products (AccountProduct)                                        │
│  - Entitlements (AccountEntitlement)                                │
│  - Usage Records (UsageRecord)                                      │
│  - Payment Methods (MorphMany: OfficeGuyToken)                      │
└──────────────────────────┬──────────────────────────────────┘
                           │ 1:N (nullable)
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   ORGANIZATION (Tenant)                       │
│  - account_id → Account (nullable)                                  │
│  - users (BelongsToMany) via organization_users pivot                │
│  - events, payments, billing (all scoped via organization_id)       │
└──────────────────────────┬──────────────────────────────────┘
                           │ 1:N
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                        EVENT (Data)                           │
│  - organization_id FK (tenant isolation)                            │
│  - guests, invitations, tables, seats (all tenant-scoped)         │
│  - eventBilling (hasOne per event)                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 Middleware Stack (Request Flow)

```
Request → auth → verified → ensure.organization → ensure.account_active → Route
                                        ↓
                            $user->currentOrganization
```

### Middleware Responsibilities

1. **auth** - Authenticate user (session/passkey)
2. **verified** - Email verification required
3. **ensure.organization** - User must have active organization selected
4. **ensure.account_active** - Account must have active product/subscription/trial
5. **system.admin** - System admin routes (require impersonation)
6. **require.impersonation** - System admins MUST impersonate orgs
7. **SpatiePermissionTeam** - Set team_id = organization_id for permissions

---

## 💳 Billing & Payment Flow

### Per-Event Payment (for accounts without active subscription)
```
Event (draft) → initiateEventPayment() → EventBilling + Payment created
                              ↓
                         Tokenization / Redirect
                              ↓
                    Webhook marks Payment succeeded
                              ↓
                    Event status: draft → pending_payment → active
```

### Subscription-Based (for accounts with active subscription)
```
Account has active AccountSubscription
         ↓
Event.created_at status → auto-activates (no payment needed)
```

### Product Engine Entitlement Resolution

**Hierarchy (FeatureResolver service):**
```
1. account_overrides (AccountEntitlement where product_entitlement_id = null)
2. propagated_entitlements (from AccountProduct → Product → ProductEntitlement)
3. plan_limits (from AccountSubscription → ProductPlan → metadata.limits)
4. product_defaults (from Product → ProductEntitlement)
5. system_defaults (config/product-engine.php defaults)
```

---

## 📱 User Authentication

### WebAuthn/Passkey Only (no password auth)
- Package: `laragear/webauthn`
- Models: `webauthn_credentials` table
- Login: assertion flow (unauthenticated endpoint)
- Register: attestation flow (authenticated endpoint)

### User Properties
- `current_organization_id` - Active org for dashboard
- `is_system_admin` - Global admin flag
- `is_disabled` - Account disabled flag
- `last_login_at` - Tracking

---

## 🔌 External Integrations

### Twilio (Voice RSVP)
- **Service:** `CallingService`
- **Normalization:** Israeli phone to E.164
- **Controllers:** `Twilio/RsvpVoiceController`
- **Routes:** `/twilio/rsvp/connect`, `/twilio/rsvp/response`

### Sumit Payment Gateway
- **Package:** `officeguy/laravel-sumit-gateway`
- **Service:** `SumitBillingProvider`
- **Tables:** `officeguy_tokens`, `officeguy_subscriptions`, `officeguy_transactions`
- **Webhooks:** `/api/webhooks/{gateway}` (HMAC verified)

### Spatie Packages
- **laravel-permission** - Role-based permissions with team scoping
- **laravel-medialibrary** - Media management (Event images)

---

## 🎯 Domain Enums

### EventStatus
```php
Draft, PendingPayment, Active, Locked, Archived, Cancelled
```

### AccountSubscriptionStatus
```php
Trial, Active, PastDue, Cancelled
```

### OrganizationUserRole
```php
Owner, Admin, Member
```

### RsvpResponseType
```php
Yes, No, Maybe
```

### ProductStatus
```php
Draft, Active, Archived (from enum, check actual)
```

### ProductPriceBillingCycle
```php
Monthly, Yearly, Usage
```

---

## 🗂️ Database Schema Summary

### Key Tables with Foreign Keys

**Tenant Isolation:**
- `events.organization_id` → `organizations.id`
- `guests.event_id` → `events.id`
- `invitations.event_id` → `events.id`
- `event_tables.event_id` → `events.id`
- `seat_assignments.event_id` → `events.id`

**Billing Layer:**
- `organizations.account_id` → `accounts.id`
- `events_billing.account_id` → `accounts.id`
- `payments.account_id` → `accounts.id`
- `account_subscriptions.account_id` → `accounts.id`

**Product Engine:**
- `account_subscriptions.product_plan_id` → `product_plans.id`
- `product_plans.product_id` → `products.id`
- `account_products.product_id` → `products.id`
- `product_entitlements.product_id` → `products.id`

---

## 📋 Key Services

### FeatureResolver
- Hierarchical feature flag resolution
- Cache TTL: 300 seconds
- Methods: `boolean()`, `integer()`, `get()`, `forget()`, `forgetMany()`

### BillingService
- Event payment lifecycle
- Methods: `initiateEventPayment()`, `markPaymentSucceeded()`, `markPaymentFailed()`

### SubscriptionService
- Trial and subscription management
- Methods: `startTrial()`, `activate()`, `cancel()`, `suspend()`, `renew()`

### UsageMeter
- Usage tracking with overage billing
- Methods: `record()`, `sumForPeriod()`, `billOverageIfRequired()`

### OrganizationContext
- Current organization management
- Methods: `set()`, `setById()`, `current()`, `clear()`, `validateMembership()`

### OrganizationMemberService
- Member invitations and role management
- Methods: `invite()`, `addMember()`, `acceptInvitation()`, `removeMember()`, `updateRole()`

### CallingService
- Twilio voice integration
- Methods: `normalizePhoneNumber()` (Israeli E.164), `initiateCall()`