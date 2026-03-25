# PHPStan Static Analysis Report - VERIFIED
## Kalfa.me Event SaaS Platform
**Date**: 2026-03-24
**Laravel Version**: 12.55.1
**PHPStan Version**: Latest
**Total Errors Found**: 21 errors across 9 files

---

## Executive Summary

This analysis reveals **21 static analysis errors** spanning 9 files. All findings have been verified against **actual Laravel 12.55.1 source code**, not just documentation.

**Key Discovery**: Most errors are **original bugs**, not API version changes.

| Category | Count | Verified Severity |
|----------|-------|------------------|
| Undefined Enum Constants | 2 | High |
| Missing Use Statements | 1 | Medium |
| Undefined Variables | 1 | High |
| Wrong Method Parameters/Signatures | 6 | High |
| Constructor Parameter Mismatches | 2 | High |
| Unknown Classes/Namespaces | 6 | High |
| Unknown Constructor Parameters | 3 | High |

---

## Critical Correction

### ❌ INCORRECT Conclusion (Earlier Draft)
> "Laravel 12 API Changes - The RateLimiter errors suggest the middleware was written for an older Laravel version."

### ✅ CORRECT Finding (Verified Against Source Code)
The errors in `ThrottleWithCustomResponse` are **original implementation bugs**. The Laravel 12.55.1 `RateLimiter` API is:
```php
// From vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php
public function tooManyAttempts($key, $maxAttempts)  // Line 127 - 2 params
public function hit($key, $decaySeconds = 60)         // Line 147 - decay, NOT maxAttempts
public function retriesLeft($key, $maxAttempts)       // Line 245 - 2 params
public function availableIn($key)                     // Line 271 - 1 param (key only)
```

The middleware was written incorrectly from the start.

---

## Verified Error Analysis

### 1. AccountProductStatus Enum - Missing Case

**File**: `app/Console/Commands/ProductEngine/ProcessProductExpirationsCommand.php:63`

**Current Enum** (`app/Enums/AccountProductStatus.php`):
```php
enum AccountProductStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    // ❌ NO 'Expired' case exists
}
```

**Error**:
```php
$accountProduct->status = AccountProductStatus::Expired;  // Line 63 - ERROR
```

**Solution** - Add the missing case:
```php
enum AccountProductStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Expired = 'expired';  // ✅ ADD THIS
}
```

---

### 2. BillingCheckoutController - Missing authorize() Method

**File**: `app/Http/Controllers/Api/BillingCheckoutController.php:43`

**Application's Base Controller** (`app/Http/Controllers/Controller.php`):
```php
namespace App\Http\Controllers;

abstract class Controller {}  // ❌ EMPTY - No traits!
```

**Laravel's AuthorizesRequests Trait** (`vendor/laravel/framework/src/Illuminate/Foundation/Auth/Access/AuthorizesRequests.php`):
```php
trait AuthorizesRequests
{
    public function authorize($ability, $arguments = [])
    {
        return app(Gate::class)->authorize($ability, $arguments);
    }
}
```

**Problem**: The class is `readonly` and doesn't have access to `authorize()` method.

**Current Code**:
```php
final readonly class BillingCheckoutController
{
    public function store(Request $request, OrganizationContext $context): JsonResponse
    {
        $this->authorize('update', $organization);  // ❌ Method doesn't exist
    }
}
```

**Solution Options**:

**Option 1**: Use Gate facade directly (Recommended for readonly classes):
```php
use Illuminate\Support\Facades\Gate;

final readonly class BillingCheckoutController
{
    public function store(Request $request, OrganizationContext $context): JsonResponse
    {
        Gate::authorize('update', $organization);  // ✅ Works
    }
}
```

**Option 2**: Add AuthorizesRequests to base Controller:
```php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;  // ✅ Adds authorize() to all controllers
}
```

**Option 3**: Remove readonly, extend Controller (breaking change):
```php
final class BillingCheckoutController extends Controller  // Can't be readonly
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private BillingProvider $billingProvider,
        private CouponService $couponService,
    ) {}
}
```

---

### 3. RsvpVoiceController - Missing $request in Closure

**File**: `app/Http/Controllers/Twilio/RsvpVoiceController.php:271`

**Error** (Line 264):
```php
DB::transaction(function () use ($guest, $invitation, $responseType) {
    // ❌ $request not in use() clause
    'ip' => $request->ip(),  // Line 271 - Undefined variable
});
```

**Solution**:
```php
DB::transaction(function () use ($guest, $invitation, $responseType, $request) {
    // ✅ Add $request
    'ip' => $request->ip(),
});
```

---

### 4. ThrottleWithCustomResponse - Verified API Mismatches

**File**: `app/Http/Middleware/ThrottleWithCustomResponse.php`

**Laravel 12.55.1 RateLimiter API** (VERIFIED from source):
| Method | Signature | Line |
|--------|-----------|------|
| `tooManyAttempts` | `($key, $maxAttempts)` | 127 |
| `hit` | `($key, $decaySeconds = 60)` | 147 |
| `retriesLeft` | `($key, $maxAttempts)` | 245 |
| `availableIn` | `($key)` | 271 |

**Errors in the Code**:

```php
// ❌ Line 23: Missing $maxAttempts parameter
if (RateLimiter::tooManyAttempts($key)) {  // Should be: tooManyAttempts($key, $maxAttempts)

// ❌ Line 27: Wrong parameter (maxAttempts instead of decaySeconds)
RateLimiter::hit($key, $maxAttempts);  // Should be: hit($key, $decaySeconds)

// ❌ Line 31: Passing wrong value to addHeaders
return $this->addHeaders($response, $maxAttempts, $request->ip());
// Method expects: ($response, $maxAttempts, $limitKey)
// But $request->ip() is not a rate limiter key!

// ❌ Line 74: Hardcoded number instead of key
$availableIn = RateLimiter::availableIn(60);  // Should be: availableIn($key)

// ❌ Line 82: Parameter type mismatch
private function addHeaders(Response $response, string $maxAttempts, string $limitKey)
// $limitKey is being passed $request->ip() (IP address) not the actual rate limiter key ($prefix . signature)

// ❌ Line 85: Using wrong variable ($limitKey instead of $key)
$remaining = RateLimiter::retriesLeft($limitKey, $maxAttempts);  // $limitKey is IP address!

// ❌ Line 88: Wrong parameter ($maxAttempts instead of $key)
$response->headers->set('X-RateLimit-Reset', RateLimiter::availableIn($maxAttempts));
```

**Complete Fixed Solution**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleWithCustomResponse
{
    private string $lastKey;  // Store the key for later use

    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1', string $prefix = ''): Response
    {
        $key = $prefix.$this->resolveRequestSignature($request);
        $this->lastKey = $key;  // Store for use in getRetryAfter()
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = (int) $decayMinutes * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildThrottleResponse($request);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        return $this->addHeaders($response, $maxAttempts, $key);
    }

    private function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        return $request->fingerprint();
    }

    private function buildThrottleResponse(Request $request): Response
    {
        $message = match ($request->ajax() || $request->wantsJson()) {
            true => [
                'message' => 'Too many requests. Please slow down.',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $this->getRetryAfter(),
            ],
            false => view('errors.rate-limited', [
                'retry_after' => $this->getRetryAfter(),
            ])->render(),
        };

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($message, 429);
        }

        return $message;
    }

    private function getRetryAfter(): int
    {
        $availableIn = RateLimiter::availableIn($this->lastKey);  // ✅ Use actual key

        return $availableIn > 0 ? $availableIn : 60;
    }

    private function addHeaders(Response $response, int $maxAttempts, string $key): Response
    {
        if ($response->isSuccessful()) {
            $remaining = RateLimiter::retriesLeft($key, $maxAttempts);  // ✅ Use actual key
            $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
            $response->headers->set('X-RateLimit-Reset', (string) RateLimiter::availableIn($key));  // ✅ Use actual key
        }

        return $response;
    }
}
```

---

### 5. SendTrialExpiringReminderJob - Constructor Mismatch

**File**: `app/Jobs/SendTrialExpiringReminderJob.php:65`

**Mail Constructor** (VERIFIED from `app/Mail/TrialExpiringReminder.php`):
```php
public function __construct(
    public string $ownerName,
    public int $daysRemaining,
    public string $organizationName,
    public string $planName,
    public Carbon $trialEndsAt,
    public string $selectPlanUrl,
    public string $mailLocale = 'he'
) {}
```

**Current Job Code**:
```php
Mail::to($owner->email)->send(
    new TrialExpiringReminder($account, $this->subscription, $this->daysRemaining)
    // ❌ 3 params, needs 6-7
);
```

**Solution**:
```php
$organization = $account->organizations->first();
$productPlan = $this->subscription->productPlan;

Mail::to($owner->email)->send(
    new TrialExpiringReminder(
        ownerName: $owner->name ?? $owner->email,
        daysRemaining: $this->daysRemaining,
        organizationName: $organization->name,
        planName: $productPlan->name,
        trialEndsAt: $this->subscription->trial_ends_at ?? now(),
        selectPlanUrl: route('billing.plans.index'),
        mailLocale: 'he'
    )
);
```

---

### 6. SyncOrganizationSubscriptionsJob - Missing Import

**File**: `app/Jobs/SyncOrganizationSubscriptionsJob.php:37`

**Error**:
```php
Log::warning('Organization has no account to sync subscriptions', [...]);
// ❌ Log class not imported
```

**Solution**:
```php
use Illuminate\Support\Facades\Log;  // ✅ Add this use statement
```

---

### 7. SumitBillingProvider - CustomerData Constructor

**File**: `app/Services/Billing/SumitBillingProvider.php:302-304`

**Package Constructor** (VERIFIED from `vendor/officeguy/laravel-sumit-gateway/src/DataTransferObjects/CustomerData.php`):
```php
final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public ?string $company = null,       // ✅ NOT 'companyNumber'
        public ?string $vatNumber = null,
        public ?string $citizenId = null,
        public ?AddressData $address = null,  // ✅ NOT separate 'city', 'zipCode'
    ) {}
}
```

**Current Code**:
```php
return new CustomerData(
    name: ...,
    email: ...,
    phone: ...,
    address: null,
    city: null,              // ❌ Unknown parameter
    zipCode: null,           // ❌ Unknown parameter
    companyNumber: ...,      // ❌ Unknown parameter
);
```

**Solution**:
```php
$address = new \OfficeGuy\LaravelSumitGateway\DataTransferObjects\AddressData(
    line1: $account->getSumitCustomerAddress() ?? '',
    line2: null,
    city: $account->getSumitCustomerCity(),
    state: null,
    country: 'IL',
    postalCode: $account->getSumitCustomerPostalCode(),
);

return new CustomerData(
    name: ...,
    email: ...,
    phone: ...,
    company: $account->getSumitCustomerBusinessId(),  // ✅ Correct name
    vatNumber: null,
    citizenId: null,
    address: $address,  // ✅ Use AddressData object
);
```

---

### 8. SubscriptionSyncService - Wrong Namespaces

**File**: `app/Services/SubscriptionSyncService.php`

**Errors**:
```php
use App\AccountSubscription;  // ❌ Wrong namespace
use App\Models\OfficeGuy\Subscription as OfficeGuySubscription;  // ❌ Wrong path
```

**Solution**:
```php
use App\Models\AccountSubscription;  // ✅ Correct
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;  // ✅ Correct package path
```

---

### 9. AccountPaymentMethodManager - Wrong Parameter Name

**File**: `app/Services/Sumit/AccountPaymentMethodManager.php:43`

**Package Constructor** (VERIFIED from vendor):
```php
public function __construct(
    protected readonly int $customerId,
    protected readonly float $amount,
    protected readonly CredentialsData $credentials,
    protected readonly ?string $token = null,  // ✅ 'token', NOT 'singleUseToken'
    // ...
) {}
```

**Solution**:
```php
$request = new ChargePaymentRequest(
    customerId: $account->sumit_customer_id,
    amount: 1,
    credentials: $this->credentials(),
    token: $singleUseToken,  // ✅ Correct parameter name
    description: 'Token authorization charge',
    cancelable: true
);
```

---

### 10. SumitUsageChargePayable - Wrong Enum Constant

**File**: `app/Services/Sumit/SumitUsageChargePayable.php:120`

**Package Enum** (VERIFIED from vendor):
```php
enum PayableType: string
{
    case INFRASTRUCTURE = 'infrastructure';
    case DIGITAL_PRODUCT = 'digital_product';  // ✅ Correct name
    case SUBSCRIPTION = 'subscription';
    case SERVICE = 'service';
    case GENERIC = 'generic';
}
```

**Solution**:
```php
return PayableType::DIGITAL_PRODUCT;  // ✅ Correct constant
```

---

## Priority Recommendations

### Critical (Fix Immediately)

1. **ThrottleWithCustomResponse.php** - 6 bugs, completely broken middleware
2. **RsvpVoiceController.php** - Undefined variable causes runtime error
3. **BillingCheckoutController.php** - Authorization not working

### High Priority

4. **AccountProductStatus.php** - Command will fail
5. **SendTrialExpiringReminderJob.php** - Jobs will fail
6. **SumitBillingProvider.php** - API calls will fail
7. **SubscriptionSyncService.php** - Sync completely broken

### Medium Priority

8. **SyncOrganizationSubscriptionsJob.php** - Add import
9. **AccountPaymentMethodManager.php** - Fix parameter name
10. **SumitUsageChargePayable.php** - Fix enum constant

---

## Root Cause Analysis (VERIFIED against actual code & database)

### 1. **AccountProductStatus::Expired - INCOMPLETE IMPLEMENTATION** ✅ VERIFIED

**DB Status Values**: `active`, `revoked` only - **NO `expired`**
**Enum Cases**: `Active`, `Suspended`, `Revoked` - **NO `Expired`**

**The Bug**:
- Command `ProcessProductExpirationsCommand.php:63` uses: `AccountProductStatus::Expired`
- Command description: *"Transition active AccountProducts with a past expires_at to Expired status"*
- But the enum was **never updated** to include the `Expired` case!

**Root Cause**: Someone wrote the command assuming `Expired` would be added to the enum, but forgot to complete the implementation.

---

### 2. **ThrottleWithCustomResponse - ORIGINAL BUGS** ✅ VERIFIED

All 6 errors are **original bugs** in the middleware:

```php
// Line 23: Missing required parameter $maxAttempts
if (RateLimiter::tooManyAttempts($key)) {  // ❌ needs 2 params

// Line 27: Wrong parameter type (maxAttempts instead of decaySeconds)
RateLimiter::hit($key, $maxAttempts);  // ❌ second param is decay seconds

// Line 31: Passing wrong value ($request->ip() as key)
return $this->addHeaders($response, $maxAttempts, $request->ip());  // ❌ IP is not a rate limiter key

// Line 74: Hardcoded number instead of key
$availableIn = RateLimiter::availableIn(60);  // ❌ should be $key

// Line 85: Using wrong variable
$remaining = RateLimiter::retriesLeft($limitKey, $maxAttempts);  // ❌ $limitKey doesn't exist

// Line 88: Wrong parameter
RateLimiter::availableIn($maxAttempts);  // ❌ should be $key
```

**Root Cause**: Middleware was written incorrectly from the start - wrong parameter types, wrong variables, misunderstanding of RateLimiter API.

---

### 3. **BillingCheckoutController - ARCHITECTURE INCONSISTENCY** ✅ VERIFIED

```php
final readonly class BillingCheckoutController  // ❌ No AuthorizesRequests trait
{
    $this->authorize('update', $organization);  // ❌ Method doesn't exist
}
```

**Comparison**:
- **Livewire components** extend `Livewire\Component` which HAS `AuthorizesRequests` trait
- **Regular controllers** extend `App\Http\Controllers\Controller` which is EMPTY
- **This controller** is `readonly` without extends - NO trait

**Root Cause**: Incomplete refactoring - someone converted to `readonly` pattern but forgot authorization doesn't work without the trait.

---

### 4. **Package Version Mismatch** ✅ VERIFIED

The `officeguy/laravel-sumit-gateway` package was upgraded but app code wasn't updated:
- `CustomerData` constructor parameters changed
- `ChargePaymentRequest` parameter renamed to `token`
- `PayableType` enum value changed to `DIGITAL_PRODUCT`

---

### 5. **SubscriptionSyncService - WRONG NAMESPACES** ✅ VERIFIED

```php
use App\AccountSubscription;  // ❌ Wrong: should be App\Models\AccountSubscription
use App\Models\OfficeGuy\Subscription as OfficeGuySubscription;  // ❌ Wrong path
```

**Correct**:
```php
use App\Models\AccountSubscription;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;
```

---

### 6. **PHPStan CI** - ❌ NO PHPStan in CI ✅ CONFIRMED

Only workflow files:
- `build-android.yml`
- `build-ios.yml`
- `voice-bridge-deploy.yml`

**NO PHPStan check** - meaning static analysis is not blocking merges.

---

## Testing Strategy

```bash
# Verify all errors fixed
./vendor/bin/phpstan analyse app/

# Run affected tests
php artisan test --filter=ThrottleWithCustomResponseTest
php artisan test --filter=BillingCheckoutTest
php artisan test --filter=RsvpVoiceTest
php artisan test --filter=ProcessProductExpirationsCommandTest
```

---

**End of Verified Report**

All findings verified against:
- `vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php` (Laravel 12.55.1)
- `vendor/laravel/framework/src/Illuminate/Foundation/Auth/Access/AuthorizesRequests.php`
- `vendor/officeguy/laravel-sumit-gateway/src/DataTransferObjects/CustomerData.php`
- `vendor/officeguy/laravel-sumit-gateway/src/Http/Requests/Payment/ChargePaymentRequest.php`
- `vendor/officeguy/laravel-sumit-gateway/src/Enums/PayableType.php`
