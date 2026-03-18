---
name: sumit-integration-expert
description: "Deep expertise on SUMIT/Officeguy payment integration in this Laravel SaaS. Activates when implementing payment flows, debugging SUMIT API errors, working with PaymentsJS tokenization, managing OfficeGuyToken/recurring tokens, handling billing webhooks, configuring SUMIT credentials, or working with the officeguy/laravel-sumit-gateway package."
license: MIT
metadata:
  author: kalfa
---

# SUMIT Integration Expert

## When to Apply

Activate this skill when:

- Implementing or debugging checkout/payment flows
- Working with PaymentsJS single-use token flow
- Debugging `storeSingleUseToken()` failures
- Handling SUMIT webhook processing
- Configuring `config/officeguy.php` or `OFFICEGUY_*` env vars
- Working with `OfficeGuyToken`, `AccountSubscription`, `SumitBillingProvider`
- Billing webhook idempotency issues

## Stack Overview

| Component | Class/Package | Purpose |
|---|---|---|
| Gateway package | `officeguy/laravel-sumit-gateway` | SUMIT API client |
| Provider | `SumitBillingProvider` | Adapter implementing `BillingProvider` |
| Token manager | `AccountPaymentMethodManager` | Single-use → stored token |
| Subscription | `SubscriptionService` | Create + activate subscriptions |
| Webhook handler | `WebhookController` + `BillingService` | Process async payment results |

## PaymentsJS Tokenization Flow

```
1. Frontend loads PaymentsJS script:
   https://app.sumit.co.il/scripts/payments.js

2. Form has data-og="form" attribute

3. JS calls:
   OfficeGuy.Payments.BindFormSubmit({ CompanyID, APIPublicKey })

4. On submit: PaymentsJS injects input[name="og-token"] (async ~50ms)

5. JS polls for token (must wait before submitting to API)

6. POST /api/billing/checkout with:
   { plan_id: X, payment_token: "<og-token value>" }
```

### Critical: Token Appears Asynchronously

```javascript
// Poll for token — do NOT submit immediately on form submit
function waitForToken(maxWait = 3000) {
    return new Promise((resolve, reject) => {
        const start = Date.now();
        const check = () => {
            const input = document.querySelector('input[name="og-token"]');
            if (input?.value) return resolve(input.value);
            if (Date.now() - start > maxWait) return reject('Token timeout');
            setTimeout(check, 50);
        };
        check();
    });
}
```

## storeSingleUseToken Flow

```php
// AccountPaymentMethodManager::storeSingleUseToken(Account $account, string $token)
// 1. billingProvider->createCustomer($account)   → gets/creates SUMIT customer ID
// 2. ChargePaymentRequest (₪1 auth charge)        → authorizes card + extracts recurring token
// 3. Creates/updates OfficeGuyToken               → is_default = true on account
```

**Why ₪1 auth charge?** SUMIT requires a real transaction to extract a recurring token. The ₪1 is typically reversed.

## SubscriptionService::activate()

```php
// 1. SumitBillingProvider::createSubscription($account, $plan)
//    → uses defaultTokenFor($account) to get OfficeGuyToken
//    → creates SUMIT recurring subscription
// 2. PaymentService::processCharge() with token
//    → if fails → status = PastDue
//    → if succeeds → status = Active + grantProduct()
// 3. account->invalidateBillingAccessCache()
```

## Configuration

### Required ENV vars

```env
OFFICEGUY_ENVIRONMENT=www           # or 'sandbox'
OFFICEGUY_COMPANY_ID=               # SUMIT company ID
OFFICEGUY_PRIVATE_KEY=              # HMAC signing key
OFFICEGUY_PUBLIC_KEY=               # PaymentsJS public key
```

### config/officeguy.php model bindings

```php
'models' => [
    'customer' => Organization::class,  // or Account::class if Account implements HasSumitCustomer
    'order'    => EventBilling::class,
],
```

**Important:** `Account` must implement `HasSumitCustomer` trait for `sumit_customer_id` to be managed.

## Webhook Processing

```
POST /api/webhooks/sumit
  → WebhookController::handle()
  → Verify HMAC signature (BILLING_WEBHOOK_SECRET)
  → Idempotency check: if payment already Succeeded/Failed → skip
  → BillingService::markPaymentSucceeded() or markPaymentFailed()
  → On success: event → Active (for event billing)
               subscription → Active + grantProduct() (for subscriptions)
```

### Idempotency Pattern

```php
// Always check before processing:
if (in_array($payment->status, [PaymentStatus::Succeeded, PaymentStatus::Failed])) {
    return; // already processed
}
```

## Duplicate Subscription Guard

```php
// SubscriptionPurchaseController — before creating:
if ($account->activeSubscriptions()->exists()) {
    return response()->json(['success' => false, 'message' => '...'], 409);
}
```

## Common Errors

| Error | Cause | Fix |
|---|---|---|
| `og-token` input not found | Token not yet injected by PaymentsJS | Poll with setTimeout |
| 401 from SUMIT API | Wrong `OFFICEGUY_PRIVATE_KEY` | Verify key in SUMIT dashboard |
| `sumit_customer_id` null | `createCustomer()` not called | Ensure `storeSingleUseToken()` runs first |
| `OfficeGuyToken` not found | `storeSingleUseToken()` failed silently | Check logs for ChargePaymentRequest error |
| PastDue after activate | First charge failed | Card declined or wrong credentials |
| Webhook 403 | HMAC mismatch | Verify `BILLING_WEBHOOK_SECRET` matches SUMIT config |

## Key Files

| File | Purpose |
|---|---|
| `app/Services/Sumit/AccountPaymentMethodManager.php` | Single-use → stored token |
| `app/Services/Billing/SumitBillingProvider.php` | SUMIT API adapter |
| `app/Services/SubscriptionService.php` | Subscription lifecycle |
| `app/Http/Controllers/Api/SubscriptionPurchaseController.php` | Checkout API endpoint |
| `app/Http/Controllers/BillingSubscriptionCheckoutController.php` | Checkout page |
| `resources/views/billing/subscription-checkout.blade.php` | PaymentsJS form |
| `config/officeguy.php` | SUMIT package configuration |
| `app/Models/OfficeGuyToken.php` | Stored recurring token |
| `routes/api.php` | `/api/billing/checkout`, `/api/webhooks/sumit` |

## Testing Without Real Credentials

```env
BILLING_GATEWAY=stub   # Uses StubPaymentGateway (always succeeds)
```

The `StubPaymentGateway` bypasses all SUMIT API calls for local development.
