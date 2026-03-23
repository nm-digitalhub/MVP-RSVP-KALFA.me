# BILLING/SUBSCRIPTION AUDIT - EXECUTIVE SUMMARY

## The Problem

**Account #2 (Kalfa-test) can access the system without paying, and the trial system never actually expires.**

---

## Root Causes Identified

### 1. **NO MIDDLEWARE ENFORCING ACCOUNT ACTIVE STATUS** 🔴 CRITICAL

The application requires `ensure.organization` middleware but **never checks if the account has an active subscription or product**.

**Files involved:**
- `bootstrap/app.php` (lines 16-30) - middleware registration
- `routes/web.php` (lines 89-124) - organization routes without billing check
- Missing: `app/Http/Middleware/EnsureAccountActive.php` - **DOES NOT EXIST**

**What happens:**
```
Route: GET /dashboard/events
├─ ensure.organization middleware: ✅ passes (user in org)
├─ User tries to create event
├─ EventPolicy.create() called
│  ├─ Check: User in organization? ✅ YES
│  └─ Check: User has 'manage-event-guests' permission? ❌ NO
└─ Result: Redirect to billing page (CORRECT)

BUT: User can still VIEW organization pages without any product!
```

---

### 2. **TRIAL SYSTEM NOT ENFORCED** 🔴 CRITICAL

Trial subscriptions are created with `trial_ends_at` field but nowhere is this checked.

**Files:**
- `app/Services/SubscriptionService.php` (lines 24-51) - `startTrial()` creates trials
- `app/Services/FeatureResolver.php` (lines 148-208) - resolves features
- Missing enforcement: No check if `trial_ends_at < now()`

**What happens:**
```
1. Trial created: status='trial', trial_ends_at = now() + 14 days
2. Features work because activeSubscriptions() includes trials
3. Trial expires: trial_ends_at < now()
4. No enforcement: Features STILL WORK (not checked)
5. User can use system forever on free trial
```

---

### 3. **ACCOUNT #2 SPECIFIC ISSUE**

Account #2 has:
- `account_subscriptions.count()` = 0
- `account_products.count()` = 0  
- `account_entitlements.count()` = 0
- `payments.count()` = 0

**No products = PermissionSyncService never called.**

```
Observer flow:
  AccountProduct created/updated → Observer fires → syncForAccount()

Account #2:
  No AccountProduct rows → Observer never fires → Permissions never synced
  Result: User has NO Spatie permissions → Can't create events → CORRECT behavior
  
BUT: Dashboard access is NOT blocked (middleware doesn't check)
```

---

## What's Working ✅

| Component | Status | Why |
|-----------|--------|-----|
| **Account/Product Models** | ✅ | activeAccountProducts() and activeSubscriptions() methods work correctly |
| **Permission Sync Observer** | ✅ | Automatically syncs Spatie permissions when products change |
| **Event Creation Policy** | ✅ | EventPolicy correctly checks Spatie permission 'manage-event-guests' |
| **Product Granting** | ✅ | System admins can manually grant products with granted_by=admin_id |
| **Payment Integration** | ✅ | PermissionSyncService checks for succeeded payments |

---

## What's Missing or Broken ❌

| Gap | Impact | Files |
|-----|--------|-------|
| **No billing middleware** | Users can access dashboard without product | bootstrap/app.php, routes/web.php |
| **No trial expiry check** | Trials run forever | FeatureResolver.php, missing middleware |
| **Trial auto-expire not wired** | Trials don't automatically transition to expired state | No cron/job to handle expirations |
| **No account suspension wall** | Can't force-block an account (except by deleting products) | Missing middleware |
| **Dashboard not protected** | Viewing org/dashboard allowed without payment | EnsureOrganizationSelected.php |

---

## Database Reality

**Account #1 (Eventra):**
```
activeAccountProducts: 1
  - Product: AI Voice Agent RSVP
  - Granted by: NULL (system admin grant)
Permissions: Granted (system admin granted product manually)
Can create events: YES (has manage-event-guests)
```

**Account #2 (Kalfa-test):**
```
activeAccountProducts: 0
activeSubscriptions: 0
payments: 0
Permissions: NOT granted (no active products)
Can create events: NO (no manage-event-guests)
```

---

## The Intended Flow (From Code Design)

```
┌─────────────────┐
│  User creates   │
│   org/account   │
└────────┬────────┘
         ▼
┌─────────────────────────────────────┐
│  Start Trial OR Pay for Product     │
│  → Create AccountSubscription       │
│     (status='trial' or 'active')    │
└────────┬────────────────────────────┘
         ▼
┌─────────────────────────────────────┐
│  Activate Subscription              │
│  → Call SubscriptionService::       │
│     activate($subscription)         │
│  → Creates AccountProduct           │
│  → Observer fires                   │
└────────┬────────────────────────────┘
         ▼
┌─────────────────────────────────────┐
│  PermissionSyncService checks:      │
│  1. Has active product? ✅          │
│  2. Has payment OR granted_by? ✅   │
│  → Syncs Spatie permissions         │
└────────┬────────────────────────────┘
         ▼
┌─────────────────────────────────────┐
│  User can now:                      │
│  - Create events (has permission)   │
│  - Manage guests (has permission)   │
└─────────────────────────────────────┘
```

## The Actual Flow (What Happens)

```
Routes: middleware('ensure.organization')
  ├─ EnsureOrganizationSelected
  │  └─ Checks: User in org? ✅ (no billing check)
  └─ User can access pages ✅ (dashboard, billing page)

Event Creation:
  ├─ EventPolicy.create()
  │  ├─ User in org? ✅
  │  └─ Has manage-event-guests? ❌ (permission never synced)
  └─ Redirect to billing ✅ (works if product not granted)

⚠️ But: User can SEE dashboard without product (middleware doesn't block)
```

---

## Why Account #2 Doesn't Have Access

**The current system actually DOES block Account #2 from event creation:**

1. Admin never granted any products to Account #2
2. No AccountProduct rows exist
3. Observer never fires (no products to observe)
4. PermissionSyncService never runs for Account #2
5. User never gets 'manage-event-guests' Spatie permission
6. EventPolicy.create() check fails
7. User redirected to billing page with warning

**This is correct behavior!** But it's only correct because:
- The policy happens to check the right permission
- The permission system was properly set up
- System admins didn't manually grant a product

If a system admin had accidentally done:
```php
$account2->grantProduct($someProduct, auth()->id());
```

Then Account #2 would have full access (because granted_by is set and PermissionSyncService would grant all permissions).

---

## Summary of Gaps

### Missing Components:
1. **EnsureAccountActive middleware** - Should check `account->activeAccountProducts()->exists()`
2. **Trial expiry enforcement** - Should check `trial_ends_at < now()` 
3. **Cron job for trial expiry** - Auto-expire old trials
4. **Account status field** - Optional but helpful (accounts.status enum)

### Lines of Code Missing:
- ❌ `app/Http/Middleware/EnsureAccountActive.php` - **DOES NOT EXIST** (0 lines)
- ❌ Trial expiry cron job - **DOES NOT EXIST**
- ❌ Trial check in FeatureResolver - **DOES NOT EXIST**

### What's Currently There But Incomplete:
- ✅ PermissionSyncService.hasActivePaidOrGranted() - **Works, but only called when product changes**
- ✅ Account.activeAccountProducts() - **Works, but never checked in middleware**
- ✅ AccountSubscription.trial_ends_at - **Column exists, never validated**

---

## Specific File Locations - Full List

### Models (Working Correctly)
- `app/Models/Account.php` - No is_active field, relies on relations
- `app/Models/AccountSubscription.php` - Lines 16-24 (fillable), line 31 (trial_ends_at cast)
- `app/Models/AccountProduct.php` - Lines 15-32 (model setup)

### Enums (Complete)
- `app/Enums/AccountSubscriptionStatus.php` - Trial, Active, PastDue, Cancelled
- `app/Enums/AccountProductStatus.php` - Active, Suspended, Revoked
- `app/Enums/EntitlementType.php` - Boolean, Number, Text, Enum

### Services (Partially Wired)
- `app/Services/PermissionSyncService.php` - Lines 35-69 (hasActivePaidOrGranted)
- `app/Services/SubscriptionService.php` - Lines 24-51 (startTrial)
- `app/Services/FeatureResolver.php` - Lines 148-208 (resolveUncached, includes trials)

### Access Control (Missing Middleware)
- `bootstrap/app.php` - Lines 16-30 (no billing middleware)
- `routes/web.php` - Lines 89-124 (ensure.organization only)
- `routes/api.php` - Lines 23-65 (auth:sanctum only)
- `app/Http/Middleware/EnsureOrganizationSelected.php` - **No billing check**
- **MISSING:** `app/Http/Middleware/EnsureAccountActive.php`

### Observers (Working Correctly)
- `app/Observers/AccountProductObserver.php` - Calls syncForAccount() on product changes
- `app/Providers/AppServiceProvider.php` - Line 127 (registers observer)

### Policies (Correct But Incomplete)
- `app/Policies/EventPolicy.php` - Lines 23-27 (checks permission, not billing)
- `app/Http/Controllers/Dashboard/EventController.php` - Lines 34-39 (catches auth exception)

---

## Recommendations by Priority

### 🔴 CRITICAL (Implement First)

1. **Create EnsureAccountActive Middleware**
   - Check: `$account->activeAccountProducts()->exists() || $account->activeSubscriptions()->exists()`
   - Apply to: All organization-scoped routes

2. **Enforce Trial Expiry**
   - Add check in FeatureResolver: if trial_ends_at < now(), don't grant feature
   - OR: Add middleware to block trial-expired subscriptions

3. **Create Trial Expiry Cron**
   - Mark trials with trial_ends_at < now() as status='expired'
   - Or revoke associated products

### ⚠️ HIGH (Implement Soon)

4. **Apply Billing Gate to API**
   - Same EnsureAccountActive check for /api/* routes
   - Currently only 'auth:sanctum'

5. **Add Account Suspension**
   - Organization::is_suspended exists but undocumented
   - Ensure it's properly enforced in middleware

### 🟡 MEDIUM (Nice to Have)

6. **Add Account Status Field**
   - `accounts.status` enum (active|suspended|trial_expired)
   - Simplifies queries and debugging

7. **Audit Logging**
   - Log permission grants/revokes
   - Help debug future access issues

---

## Database Schema - Account Billing State

```sql
-- Accounts table (NO status field!)
accounts:
  id
  type (organization|individual)
  name
  owner_user_id
  sumit_customer_id
  created_at, updated_at

-- Subscription table (has trial_ends_at)
account_subscriptions:
  id
  account_id (FK)
  product_plan_id (FK)
  status: enum('trial', 'active', 'past_due', 'cancelled')
  started_at: timestamp
  trial_ends_at: timestamp (⚠️ NOT ENFORCED)
  ends_at: timestamp
  metadata: json
  created_at, updated_at

-- Product grant table (has granted_by)
account_products:
  id
  account_id (FK)
  product_id (FK)
  status: enum('active', 'suspended', 'revoked')
  granted_at: timestamp
  expires_at: timestamp
  granted_by: int (FK to users, NULL=auto-grant)
  metadata: json
  created_at, updated_at
```

---

## Bottom Line

**The system is 80% built but 20% incomplete:**

✅ **Data model is sound** - Correctly tracks subscriptions, products, payments
✅ **Permission sync works** - Observer pattern correctly grants/revokes Spatie permissions
✅ **Policies check permissions** - EventPolicy correctly validates 'manage-event-guests'
✅ **Admin grants work** - Admins can manually grant products

❌ **Middleware doesn't enforce** - Routes don't check if account has active products
❌ **Trial never expires** - trial_ends_at field exists but is never validated
❌ **No suspension wall** - Can't force-block without deleting products

**To fix:** Add 2-3 middleware files and a cron job to enforce trial expiry. The architecture is already correct; just needs the enforcement layer.
