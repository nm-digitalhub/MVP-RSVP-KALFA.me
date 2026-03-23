# 🏗️ Laravel 12 RSVP & Seating Platform - Complete Documentation

**Generated:** 2026-03-23
**Version:** 1.0
**Language:** English

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Models & Relationships](#models--relationships)
4. [Controllers & Routes](#controllers--routes)
5. [Services Architecture](#services-architecture)
6. [Integrations](#integrations)
7. [Livewire Components](#livewire-components)

---

## Overview

**Project Type:** Multi-tenant SaaS Platform
**Framework:** Laravel 12 (PHP 8.4.18)
**Frontend:** Livewire 4 + Alpine.js + Tailwind CSS v4
**Database:** PostgreSQL (production), MySQL (alternative), SQLite (tests)

### Core Features

- **Multi-Tenancy**: Organization-based isolation with role-based access
- **Event Management**: Create events, manage guests, seating assignments, RSVPs
- **Payment Processing**: SUMIT gateway integration, subscription management
- **CRM Integration**: OfficeGuy CRM for customer management
- **Products Engine**: Feature entitlements, usage metering, plan management
- **Voice Integration**: Twilio Programmable Voice with Gemini Live AI
- **Authentication**: Sanctum + WebAuthn (passkeys)

---

## Database Schema

### Tables by Domain (100 total)

#### Core Tables

| Domain | Tables | Description |
|--------|--------|-------------|
| **Organizations** | 3 | `organizations`, `organization_users`, `organization_invitations` |
| **Users** | 1 | `users` (with `is_system_admin`, `is_disabled`, `current_organization_id`) |
| **Events** | 6 | `events`, `guests`, `invitations`, `event_tables`, `seat_assignments`, `rsvp_responses` |
| **Billing** | 6 | `payments`, `events_billing`, `billing_intents`, `billing_webhook_events`, `plans`, `coupons`, `coupon_redemptions` |
| **Accounts** | 8 | `accounts`, `account_subscriptions`, `account_products`, `account_entitlements`, `account_feature_usage`, `account_credit_transactions` |
| **Products** | 6 | `products`, `product_plans`, `product_prices`, `product_entitlements`, `product_features`, `product_limits` |
| **CRM** | 8 | `officeguy_crm_*` (entities, folders, fields, relations, views, activities) |
| **Documents** | 2 | `officeguy_documents`, `document_subscription` |
| **Transactions** | 3 | `officeguy_transactions`, `officeguy_tokens`, `officeguy_sumit_webhooks` |
| **Subscriptions** | 2 | `officeguy_subscriptions`, `usage_records` |
| **System** | 5 | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `system_audit_logs` |

#### Key Relationships

```
Organization 1:N Events
Organization 1:N Users (via organization_users)
Event 1:N Guests
Event 1:N Invitations
Event 1:N EventTables
Event 1:1 EventBilling
Guest N:1 Invitation
Guest N:1 SeatAssignment
```

---

## Models & Relationships

### Core Domain Models (34 total)

#### Organization & User Models

```php
// app/Models/Organization.php
class Organization extends Model
{
    public function users() { return $this->belongsToMany(User::class); }
    public function events() { return $this->hasMany(Event::class); }
    public function account() { return $this->hasOne(Account::class); }
}

// app/Models/User.php
class User extends Model
{
    public function organizations() { return $this->belongsToMany(Organization::class); }
    public function currentOrganization() { return $this->belongsTo(Organization::class, 'current_organization_id'); }
    public function isSystemAdmin(): bool { return $this->is_system_admin; }
}
```

#### Event Models

```php
// app/Models/Event.php
class Event extends Model
{
    protected $casts = ['status' => EventStatus::class, 'settings' => 'json'];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function guests() { return $this->hasMany(Guest::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }
    public function tables() { return $this->hasMany(EventTable::class); }
    public function billing() { return $this->hasOne(EventBilling::class); }
    public function seatAssignments() { return $this->hasMany(SeatAssignment::class); }
}

// app/Models/Guest.php
// app/Models/Invitation.php
// app/Models/EventTable.php
// app/Models/SeatAssignment.php
// app/Models/RsvpResponse.php
// app/Models/EventBilling.php
```

#### Billing Models

```php
// app/Models/Payment.php
class Payment extends Model implements Payable
{
    protected $casts = ['status' => PaymentStatus::class, 'gateway_response' => 'json'];

    public function payable() { return $this->morphTo(); }
    public function organization() { return $this->belongsTo(Organization::class); }
}

// app/Models/Plan.php
// app/Models/Coupon.php
// app/Models/CouponRedemption.php
```

#### Account & Product Models

```php
// app/Models/Account.php
// app/Models/AccountSubscription.php
// app/Models/AccountProduct.php
// app/Models/AccountEntitlement.php
// app/Models/AccountFeatureUsage.php
// app/Models/UsageRecord.php

// app/Models/Product.php
// app/Models/ProductPlan.php
// app/Models/ProductPrice.php
// app/Models/ProductEntitlement.php
// app/Models/ProductFeature.php
// app/Models/ProductLimit.php
```

---

## Controllers & Routes

### API Controllers (16 total)

| Controller | Routes | Purpose |
|------------|--------|---------|
| `EventController` | `/api/organizations/{org}/events` | Event CRUD |
| `GuestController` | `/api/organizations/{org}/events/{event}/guests` | Guest management |
| `InvitationController` | `/api/organizations/{org}/events/{event}/invitations` | Invitation CRUD |
| `EventTableController` | `/api/organizations/{org}/events/{event}/event-tables` | Seating layout |
| `SeatAssignmentController` | `/api/organizations/{org}/events/{event}/seat-assignments` | Seat assignments |
| `CheckoutController` | `/api/organizations/{org}/events/{event}/checkout` | Initiate payment |
| `PaymentController` | `/api/payments/{payment}` | Payment status |
| `PublicRsvpController` | `/api/rsvp/{slug}` | Public RSVP (no auth) |
| `WebhookController` | `/api/webhooks/{gateway}` | Payment webhooks |
| `OrganizationController` | `/api/organizations/{org}` | Organization management |
| `BillingCheckoutController` | `/api/organizations/{org}/billing/checkout` | Subscription checkout |
| `BillingCouponController` | `/api/billing/coupons/*` | Coupon validation |
| `CouponValidationController` | `/api/coupons/validate` | Coupon validation |
| `SubscriptionPurchaseController` | `/api/subscriptions/purchase` | Purchase subscription |
| `GuestImportController` | `/api/organizations/{org}/events/{event}/guests/import` | CSV import |
| `MobileBootstrapController` | `/api/mobile/bootstrap` | Mobile bootstrap |
| `MobileAuthController` | `/api/mobile/auth` | Mobile auth |

### Dashboard Controllers (6 total)

| Controller | Purpose |
|------------|---------|
| `DashboardController` | Main dashboard |
| `EventController` | Event management UI |
| `EventGuestsController` | Guest list and management |
| `EventInvitationsController` | Invitation management |
| `EventTablesController` | Seating layout |
| `EventSeatAssignmentsController` | Seat assignment UI |
| `OrganizationSettingsController` | Organization settings |

### System Controllers (8 total)

| Controller | Purpose |
|------------|---------|
| `SystemDashboard` | System-wide metrics |
| `SystemImpersonationController` | Impersonate organizations |
| `SystemImpersonationExitController` | Exit impersonation |
| `Accounts/Index`, `Accounts/Show` | Account management |
| `Products/Index`, `Products/Show` | Product/plan management |
| `Users/Index`, `Users/Show` | User management |
| `Organizations/Index`, `Organizations/Show` | Organization management |
| `Coupons/Index`, `Coupons/CreateCouponWizard`, `Coupons/EditCoupon` | Coupon management |

### Auth Controllers (9 total)

| Controller | Purpose |
|------------|---------|
| `LoginController` | Authentication |
| `RegisterController` | Registration |
| `LogoutController` | Logout |
| `PasswordController` | Password reset |
| `VerificationController` | Email/OTP verification |
| `VerifyEmailController` | Email verification |
| `ConfirmPasswordController` | Confirm password for sensitive actions |
| `WebAuthnRegisterController` | Passkey registration |
| `WebAuthnLoginController` | Passkey login |

### Routes Summary

| File | Lines | Endpoints |
|------|-------|-----------|
| `routes/api.php` | 105 | ~25 API routes |
| `routes/web.php` | 228 | ~40 web routes |

---

## Services Architecture

### Core Services (10 total)

| Service | Purpose |
|---------|---------|
| `OrganizationContext` | Active organization management |
| `BillingService` | Billing workflow orchestration |
| `SubscriptionService` | Subscription lifecycle |
| `SubscriptionSyncService` | Sync with OfficeGuy |
| `SubscriptionManager` | High-level subscription operations |
| `CouponService` | Coupon validation and redemption |
| `SystemAuditLogger` | Admin action audit logging |
| `UsageMeter` | Feature usage tracking |
| `UsagePolicyService` | Usage policy enforcement |
| `FeatureResolver` | Feature availability resolution |
| `PermissionSyncService` | Permission synchronization |

### Billing Services (7 total)

| Service | Purpose |
|---------|---------|
| `SumitPaymentGateway` | SUMIT gateway integration |
| `StubPaymentGateway` | Development/stub gateway |
| `SumitBillingProvider` | SUMIT billing provider |
| `StubBillingProvider` | Development billing provider |
| `CreditService` | Account credit management |
| `AccountPaymentMethodManager` | Payment token management |
| `DocumentService` | Document generation |

### OfficeGuy Services (6 total)

| Service | Purpose |
|---------|---------|
| `SystemBillingService` | Placeholder for OfficeGuy subscription management |
| `OfficeGuyCustomerSearchService` | Customer lookup/search |
| `EventBillingPayable` | Links event billing to payable system |
| `SumitUsageChargePayable` | Usage-based billing integration |
| `AccountPaymentMethodManager` | Payment method management |
| `ProductIntegrityChecker` | Product configuration validation |
| `ProductEngineOperationsMonitor` | Product operations monitoring |

### Communication Services (3 total)

| Service | Purpose |
|---------|---------|
| `CallingService` | Outbound voice call orchestration |
| `VerifyWhatsAppService` | WhatsApp OTP verification |
| `WhatsAppRsvpService` | WhatsApp RSVP fallback |

### Database Services (2 total)

| Service | Purpose |
|---------|---------|
| `ReadReplicaHealthService` | Read replica health monitoring |
| `ReadWriteConnection` | Database connection management |

### Other Services (14+)

| Service | Purpose |
|---------|---------|
| `EventLinks` | Event URL generation |
| `MjmlRenderer` | MJML email rendering |
| `TaggedCache` | Tagged cache operations |
| `MobileSecureTokenStore` | Mobile token storage |
| `PanelScreenshotRunner` | Screenshot automation |
| `OrganizationMemberService` | Organization member management |

---

## Integrations

### OfficeGuy CRM Integration

**Tables**: 8 tables (crm_entities, crm_folders, crm_fields, crm_views, etc.)

**Key Services**:
- `OfficeGuyCustomerSearchService` - Customer lookup
- `DocumentService` - Document generation
- System webhooks processing

**Features**:
- Customer entity management
- Custom fields per folder
- Activity tracking
- Document generation (invoices, receipts)
- Webhook processing

### Twilio Voice Integration

**Architecture**:
```
Twilio Call → TwiML Connect → Media Stream
    ↓
Node.js WebSocket (server.js)
    ↓
Gemini Live API (BidiGenerateContent)
    ↓
Tool: save_rsvp → POST to Laravel /api/twilio/rsvp/process
```

**Environment Variables**:
- `GEMINI_API_KEY` - Gemini API key
- `PHP_WEBHOOK` - Laravel webhook URL
- `CALL_LOG_URL` - Call logging endpoint
- `CALL_LOG_SECRET` - Webhook secret

**Features**:
- Hebrew TTS (Google.he-IL-Standard-A + SSML)
- Voice-to-voice AI conversation
- RSVP capture via voice
- WhatsApp fallback on no-answer

### Products Engine

**Components**:
- `Product` - Base product catalog
- `ProductPlan` - Plans within products
- `ProductPrice` - Pricing per plan
- `ProductEntitlement` - Feature grants
- `ProductFeature` - Feature definitions
- `ProductLimit` - Usage limits

**Account-Level**:
- `AccountProduct` - Product subscriptions
- `AccountEntitlement` - Granted features
- `AccountFeatureUsage` - Usage tracking
- `UsageMeter` - Metering service

### SUMIT Payment Gateway

**Package**: `officeguy/laravel-sumit-gateway`

**Flow**:
1. User initiates checkout → `BillingService::initiateEventPayment()`
2. Creates `EventBilling` + `Payment` records
3. Gateway returns `redirect_url`
4. User pays → webhook to `/api/webhooks/sumit`
5. `BillingService::markPaymentSucceeded()` → Event becomes `Active`

---

## Livewire Components

### Dashboard Components (5 total)

| Component | Purpose |
|-----------|---------|
| `Dashboard` | Main dashboard view |
| `EventGuests` | Guest management UI |
| `EventInvitations` | Invitation management UI |
| `EventTables` | Seating layout UI |
| `EventSeatAssignments` | Seat assignment UI |

### System Components (16 total)

| Component | Purpose |
|-----------|---------|
| `SystemDashboard` | System metrics dashboard |
| `Accounts/Index`, `Show` | Account management |
| `Products/Index`, `Show` | Product management with tree view |
| `Users/Index`, `Show` | User management |
| `Organizations/Index`, `Show` | Organization management |
| `Coupons/Index`, `CreateCouponWizard`, `EditCoupon` | Coupon management |

### Billing Components (5 total)

| Component | Purpose |
|-----------|---------|
| `AccountOverview` | Account billing overview |
| `UsageIndex` | Usage metrics display |
| `BillingIntentsIndex` | Billing intents history |
| `EntitlementsIndex` | Feature entitlements |
| `PlanSelection` | Plan selection UI |

### Organization Components (3 total)

| Component | Purpose |
|-----------|---------|
| `Organizations/Index` | Organization list |
| `Organizations/Create` | Create organization |
| `OrganizationMembers` | Member management |

### Profile Components (4 total)

| Component | Purpose |
|-----------|---------|
| `UpdateProfileInformationForm` | Profile update |
| `UpdatePasswordForm` | Password change |
| `ManagePasskeys` | Passkey management |
| `DeleteUserForm` | Account deletion |

### Other Components (8 total)

| Component | Purpose |
|-----------|---------|
| `AcceptInvitation` | Invitation acceptance |
| `Actions/Logout` | Logout action |
| Mobile/... | Mobile-specific components |

---

## File Organization

```
app/
├── Models/               # 34 Eloquent models
├── Http/
│   ├── Controllers/      # 53 controllers
│   │   ├── Api/          # 16 API controllers
│   │   ├── Dashboard/    # 6 dashboard controllers
│   │   ├── System/       # 8 system controllers
│   │   ├── Auth/         # 9 auth controllers
│   │   └── Twilio/       # 2 Twilio controllers
│   ├── Middleware/        # Auth, organization, impersonation
│   └── Requests/         # Form request validation
├── Services/              # 42 services
│   ├── Billing/          # Billing services
│   ├── OfficeGuy/        # OfficeGuy integration
│   ├── Sumit/            # SUMIT integration
│   └── Database/         # Database services
├── Livewire/              # 50+ Livewire components
│   ├── Dashboard/
│   ├── System/
│   ├── Billing/
│   ├── Organizations/
│   ├── Profile/
│   └── Actions/
└── Enums/                 # Status and role enums
```

---

## Key Patterns

### Multi-Tenancy

- Organization-based isolation
- `OrganizationContext::current()` for active org
- `current_organization_id` on users table
- Route scoping with `->scoped(['organization'])`

### Enum-Based Status Management

- `EventStatus`: Draft, PendingPayment, Active, Cancelled, Completed
- `PaymentStatus`: Pending, Processing, Succeeded, Failed
- `OrganizationUserRole`: Owner, Admin, Editor, Viewer
- `RsvpResponseType`: Attending, Declining, Maybe

### Payment Flow

1. Event created in `Draft`
2. `BillingService::initiateEventPayment()` → `PendingPayment`
3. User pays via SUMIT
4. Webhook confirms → `markPaymentSucceeded()` → `Active`

### System Admin Impersonation

- `impersonation.original_admin_id` in session
- `impersonation.original_organization_id` for restoration
- `ImpersonationExpiry` middleware (60 minutes)

---

*This documentation is generated from live code analysis - always current, never stale.*
