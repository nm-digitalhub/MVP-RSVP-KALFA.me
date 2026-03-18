---
name: billing-gate-auditor
description: "Audits billing access gate layers in this Laravel SaaS. Activates when investigating why a user/org has unexpected access or is blocked, debugging billing_status inconsistencies, reviewing EnsureAccountActive middleware behavior, verifying hasBillingAccess() logic, checking account product/subscription/trial state, or auditing billing middleware stack order."
license: MIT
metadata:
  author: kalfa
---

# Billing Gate Auditor

## When to Apply

Activate this skill when:

- A user/org should be blocked but has access (false positive)
- A user/org should have access but is blocked (false negative)
- `billing_status` doesn't match what `hasBillingAccess()` returns
- Debugging `ensure.account_active` middleware behavior
- Verifying a plan purchase unlocked access correctly
- Auditing the middleware stack order for billing routes

## Architecture

### Middleware Stack (correct order)

```
web
 → auth
 → verified
 → ensure.organization        ← sets OrganizationContext
 → ensure.account_active      ← checks billing (NOT before org!)
 → controller
```

### Billing Access Sources (single source of truth)

`hasBillingAccess()` on `Account` model is the canonical check:

```php
// app/Models/Account.php
public function hasBillingAccess(): bool
{
    if ($this->is_suspended) return false;
    if ($this->activeSubscriptions()->exists()) return true;
    if ($this->isOnActiveTrial()) return true;
    if ($this->activeAccountProducts()->exists()) return true;
    return false;
}
```

### billing_status Derivation

```php
// Correct derivation on Organization model:
if ($org->account->is_suspended) → 'suspended'
elseif ($org->account->hasBillingAccess()) → 'active'
else → 'no_plan'
```

### Route Separation (critical)

| Route group | Middleware | Why |
|---|---|---|
| `/billing/*` | `ensure.organization` only | User must reach billing to pay |
| `/api/billing/checkout` | `auth:sanctum` + `ensure.organization` | Checkout API needs no billing gate |
| All feature routes | + `ensure.account_active` | Gate after org context exists |

## Audit Checklist

### 1. Tinker Diagnostic

```php
$org = Organization::find($id);
$acc = $org->account;

// All three must be consistent:
echo $org->billing_status;                      // 'active' | 'no_plan' | 'suspended'
echo $acc->hasBillingAccess() ? 'yes' : 'no';  // must match billing_status
echo $acc->activeAccountProducts()->count();    // products
echo $acc->activeSubscriptions()->count();      // subscriptions
echo $acc->isOnActiveTrial() ? 'yes' : 'no';   // trial
```

### 2. Common Inconsistency Causes

| Symptom | Root Cause | Fix |
|---|---|---|
| `billing_status=active` but middleware blocks | `billing_status` uses stale eager-load | Ensure `billing_status` uses `hasBillingAccess()` |
| `hasBillingAccess()=true` but UI shows no_plan | Cache not invalidated after purchase | Call `$account->invalidateBillingAccessCache()` |
| Access granted without payment | `hasActivePlan()` loading unscoped relation | Use `activeAccountProducts()` with `active()` scope |
| Loop redirect to `/billing` | Route has `ensure.account_active` before payment | Remove middleware from billing routes |

### 3. Cache Key

```php
// Cache key for billing access (Redis):
"account:{$account->id}:billing_access"

// Invalidation (called after purchase/cancel):
$account->invalidateBillingAccessCache();
```

### 4. EnsureAccountActive Middleware Logic

```php
// app/Http/Middleware/EnsureAccountActive.php
// Bypass: system admins + impersonation
// Check: $org->account->hasBillingAccess()
// Redirect: /billing (with ?reason= if set)
```

## Key Files

| File | Purpose |
|---|---|
| `app/Http/Middleware/EnsureAccountActive.php` | The gate |
| `app/Models/Account.php` | `hasBillingAccess()`, `activeSubscriptions()`, `activeAccountProducts()` |
| `app/Models/Organization.php` | `billing_status` computed attribute |
| `app/Services/SubscriptionService.php` | `activate()` → triggers cache invalidation |
| `app/Http/Controllers/Api/SubscriptionPurchaseController.php` | Duplicate subscription guard |
| `bootstrap/app.php` | Middleware aliases: `ensure.account_active` |

## Verification Flow

```
User blocked unexpectedly?
  → tinker: $org->account->hasBillingAccess()
  → if true: check middleware stack order / route group
  → if false: check activeSubscriptions / activeAccountProducts / trial
  → if stale: invalidateBillingAccessCache() then re-check
```
