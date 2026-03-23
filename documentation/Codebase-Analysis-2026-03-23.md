# 📊 Codebase Analysis Report

**Generated:** 2026-03-23
**Analysis Type:** Live Code Scan vs Documentation Review

---

## 📈 Database Schema

**Total Tables:** ~100 tables

### Core Domain Tables

| Domain | Tables | Key Models |
|--------|--------|------------|
| **Organizations** | 4 | `organizations`, `organization_users`, `organization_invitations` |
| **Events** | 6 | `events`, `guests`, `invitations`, `event_tables`, `seat_assignments`, `rsvp_responses` |
| **Billing** | 15+ | `payments`, `events_billing`, `plans`, `coupons`, `billing_intents`, `billing_webhook_events` |
| **Accounts** | 8 | `accounts`, `account_subscriptions`, `account_products`, `account_entitlements`, `account_feature_usage` |
| **Products** | 6 | `products`, `product_plans`, `product_prices`, `product_entitlements`, `product_features`, `product_limits` |
| **CRM** | 8 | `officeguy_crm_*` tables (entities, folders, activities) |
| **System** | 5 | `users`, `system_audit_logs`, `permissions`, `roles`, `sessions` |

---

## 🏗️ Application Structure

### Models (34 total)

```
Account models:          Account, AccountSubscription, AccountProduct, AccountEntitlement, AccountFeatureUsage, AccountCreditTransaction
Event models:            Event, Guest, Invitation, EventTable, SeatAssignment, RsvpResponse, EventBilling
Billing models:          Payment, Plan, Coupon, CouponRedemption, BillingWebhookEvent, BillingIntent
Product models:          Product, ProductPlan, ProductPrice, ProductEntitlement, ProductFeature, ProductLimit
System models:           User, Organization, OrganizationUser, OrganizationInvitation, SystemAuditLog
Other:                   UsageRecord
```

### Controllers (53 total)

```
Auth (8):                LoginController, RegisterController, LogoutController, PasswordController,
                          VerificationController, ConfirmPasswordController, VerifyEmailController,
                          WebAuthnLoginController, WebAuthnRegisterController

API (16):                 EventController, GuestController, InvitationController, EventTableController,
                          SeatAssignmentController, CheckoutController, PaymentController,
                          OrganizationController, PublicRsvpController, WebhookController,
                          CouponValidationController, BillingCheckoutController, BillingCouponController,
                          SubscriptionPurchaseController, GuestImportController, MobileBootstrapController,
                          MobileAuthController

Dashboard (6):            DashboardController, EventController, EventGuestsController,
                          EventInvitationsController, EventTablesController, EventSeatAssignmentsController,
                          OrganizationSettingsController

System (8):               SystemDashboard, SystemImpersonationController, SystemImpersonationExitController,
                          AccountPaymentMethodController, Organizations/Index, Organizations/Show,
                          Products/Index, Products/Show, Users/Index, Users/Show, Settings/Index,
                          Coupons/Index, Coupons/CreateCouponWizard, Coupons/EditCoupon

Other (15):               TwilioController, CallingController, RsvpVoiceController, PublicEventController,
                          PublicRsvpViewController, CheckoutStatusController, CheckoutTokenizeController,
                          OrganizationSwitchController, AcceptInvitation, BillingSubscriptionCheckoutController,
                          Mobile/MobileSecureTokenStore, Profile forms, etc.
```

### Services (42 total)

```
Core (10):                OrganizationContext, BillingService, SubscriptionService, SubscriptionSyncService,
                          SubscriptionManager, CouponService, SystemAuditLogger, UsageMeter, UsagePolicyService,
                          FeatureResolver, PermissionSyncService

Billing (7):              SumitPaymentGateway, StubPaymentGateway, SumitBillingProvider, StubBillingProvider,
                          CreditService, AccountPaymentMethodManager, DocumentService

OfficeGuy (6):            SystemBillingService, CustomerSearchService, EventBillingPayable,
                          SumitUsageChargePayable, ProductIntegrityChecker, ProductEngineOperationsMonitor

Communication (3):        CallingService, VerifyWhatsAppService, WhatsAppRsvpService

Database (2):             ReadReplicaHealthService, ReadWriteConnection

Other (14):               EventLinks, MjmlRenderer, TaggedCache, MobileSecureTokenStore, PanelScreenshotRunner,
                          OrganizationMemberService, etc.
```

### Livewire Components (50+ total)

```
Profile (4):              UpdateProfileInformationForm, UpdatePasswordForm, ManagePasskeys, DeleteUserForm

Organizations (3):        Index, Create, OrganizationMembers

Dashboard (5):             Dashboard, EventGuests, EventInvitations, EventTables, EventSeatAssignments

Billing (5):               AccountOverview, UsageIndex, BillingIntentsIndex, EntitlementsIndex, PlanSelection

System (16):               Dashboard, Accounts/Index, Accounts/Show, Products/Index, Products/Show, Products/CreateProductWizard,
                          Products/CreateProductModal, Products/ProductTree, Products/ProductStatusBadge, Products/EntitlementRow,
                          Users/Index, Users/Show, Organizations/Index, Organizations/Show, Coupons/Index,
                          Coupons/CreateCouponWizard, Coupons/EditCoupon, Settings/Index

Other (4):                AcceptInvitation, Billing/Checkout forms, Actions/Logout
```

---

## 🛣️ Routes

| File | Lines | Purpose |
|------|-------|---------|
| `routes/api.php` | 105 | API endpoints for events, guests, billing, organizations |
| `routes/web.php` | 228 | Web routes, dashboard, auth, public pages |

---

## 📁 Documentation in `documentation/`

### Existing Structure

```
documentation/
├── Architecture/          # 9 files (AsyncQueue, Auth, Caching, EventLifecycle, Glossary, etc.)
├── Knowledge/             # 3 files (Excalidraw docs)
├── Projects/              # Project-specific docs
├── Tasks/                 # Task tracking
├── Daily/                 # Daily notes
├── Meetings/              # Meeting notes
├── Templates/             # Templates
├── Dashboards/            # Obsidian canvases
├── Goals/                 # Goal tracking
├── Archives/              # Archived content
├── CLAUDE.md              # Vault context (PKM system)
├── README.md              # Vault README
├── index.md               # Main index
└── AUDIT_REPORT_2026-03-16.md  # Dead code audit
```

---

## 🔍 Documentation Gaps Analysis

### ✅ Well Documented

| Area | Status | Notes |
|------|--------|-------|
| **Payment Architecture** | ✅ | CLAUDE.md has detailed SUMIT gateway, billing flow, PCI compliance |
| **Multi-Tenancy** | ✅ | Organization context, impersonation well documented |
| **System Admin** | ✅ | Superuser features, audit logging documented |
| **API Structure** | ✅ | Routes listed with descriptions |
| **Frontend Patterns** | ✅ | Livewire inline components, tree events, tone-based styling |

### ⚠️ Partially Documented

| Area | Gap | Recommendation |
|------|-----|----------------|
| **Twilio Voice Integration** | Missing Node.js relay, Gemini Live API details | Document `server.js` architecture |
| **Products/Plans System** | Architecture exists, but not in codebase docs | Sync to CLAUDE.md |
| **CRM Integration** | OfficeGuy CRM tables not documented | Add to Architecture/ |
| **Mobile Bootstrap** | API exists but not documented | Document mobile auth flow |

### ❌ Missing Documentation

| Area | Priority | Content Needed |
|------|----------|-----------------|
| **OfficeGuy Integration** | High | Document customer sync, webhooks, document generation |
| **Calling Service** | High | Document outbound voice calls, TTS, WhatsApp fallback |
| **Feature Entitlements** | Medium | Document feature flags, limits, usage metering |
| **WebAuthn/Passkeys** | Medium | Document passkey authentication flow |
| **MJML Email Templates** | Low | Document email rendering system |

---

## 📝 Recommended Updates to CLAUDE.md

### Add to "Tech Stack":

```markdown
- **OfficeGuy CRM**: Full CRM integration with entities, folders, activities, and document generation
- **Products Engine**: Feature entitlements, usage metering, plan management (product_plans, product_prices, etc.)
- **WebAuthn**: Passkey authentication via WebAuthnRegisterController/WebAuthnLoginController
```

### Add to "Services":

```markdown
**OfficeGuy Services**:
- `OfficeGuy/SystemBillingService` - Placeholder for subscription management
- `Sumit/OfficeGuyCustomerSearchService` - Customer lookup
- `Sumit/EventBillingPayable` - Event billing as payable
- `ProductIntegrityChecker` - Validates product configurations
```

### Add New Section "Twilio Voice Architecture":

```markdown
### Twilio Voice Integration (server.js)

**Flow**:
1. Twilio call → TwiML connect → Media Stream
2. Media Stream → Node.js WebSocket relay
3. Node.js → Gemini Live API (BidiGenerateContent)
4. Gemini calls `save_rsvp` tool
5. Node.js POSTs to Laravel `/api/twilio/rsvp/process`

**Environment**: `GEMINI_API_KEY`, `PHP_WEBHOOK`, `CALL_LOG_URL`, `CALL_LOG_SECRET`

**TTS**: Hebrew (Google.he-IL-Standard-A + SSML)
**Fallback**: WhatsApp on no-answer/short call
```

---

## 🎯 Next Steps

1. ✅ Code scan complete - all major components identified
2. 📝 Update `documentation/Architecture/` with missing areas
3. 📝 Update `CLAUDE.md` with OfficeGuy and Twilio Voice sections
4. 📝 Create `documentation/Architecture/TwilioVoice.md`
5. 📝 Create `documentation/Architecture/ProductsSystem.md`
6. 📝 Create `documentation/Architecture/CrmIntegration.md`

---

*Report generated by live code analysis - not reliant on potentially outdated documentation*
