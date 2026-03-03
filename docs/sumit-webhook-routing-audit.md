# SUMIT Webhook Routing — Package vs Custom Endpoint (Audit)

## Context

- **Production webhook URL:** `https://kalfa.me/api/webhooks/sumit` (our endpoint).
- **Package:** `officeguy/laravel-sumit-gateway`.
- **Question:** Can we use only our custom endpoint, or must webhooks also pass through the package route?

---

## 1) Does the package register its own webhook route automatically?

**Yes.**  
In `OfficeGuyServiceProvider::boot()` (line 156):

```php
$this->loadRoutesFrom(__DIR__ . '/../routes/officeguy.php');
```

The file `routes/officeguy.php` registers the SUMIT webhook route inside a prefix group (lines 205–212):

```php
$sumitWebhookPath = RouteConfig::getSumitWebhookPath();
Route::post($sumitWebhookPath, [SumitWebhookController::class, 'handle'])
    ->name('officeguy.webhook.sumit');
```

So the package **always** registers its SUMIT webhook route when the provider boots. There is no flag to skip loading these routes.

---

## 2) Exact default webhook path, configurability, and disabling

### Default path

- **Prefix:** `RouteConfig::getPrefix()` → from DB (`officeguy_settings.routes_prefix`) or config `officeguy.routes.prefix` or **default `'officeguy'`**.
- **Path segment:** `RouteConfig::getSumitWebhookPath()` → from DB (`routes_sumit_webhook`) or config `officeguy.routes.sumit_webhook` or **default `'webhook/sumit'`**.

So the **default full path** is: **`{prefix}/webhook/sumit`** → e.g. **`officeguy/webhook/sumit`** (i.e. `POST /officeguy/webhook/sumit`).

### Configurability

- **Configurable:** Yes.  
  - Prefix: `officeguy.routes.prefix` ← `env('OFFICEGUY_ROUTE_PREFIX', 'officeguy')`.  
  - SUMIT webhook path: `officeguy.routes.sumit_webhook` ← `env('OFFICEGUY_SUMIT_WEBHOOK_PATH', 'webhook/sumit')`.  
- **DB override:** If `officeguy_settings` exists, `RouteConfig::getSetting()` reads from it first, then falls back to config.

### Disabling

- **No.** The package does not expose an option to disable the SUMIT webhook route (or the whole route file). `loadRoutesFrom()` is always executed; the route is always registered.

---

## 3) Does the package expect webhooks ONLY on its own route?

**No.**  
The package does not assume that SUMIT sends webhooks only to its route. SUMIT sends HTTP requests to whatever URL is configured in the SUMIT dashboard. Our app receives those requests on `POST /api/webhooks/sumit` (our `WebhookController`). The package has no logic that forces or assumes webhooks to hit `officeguy/webhook/sumit`; that route is simply one possible target.

---

## 4) Is there internal logic that requires the webhook to pass through the package controller?

**Not for our billing flow.**  
- **Package controller (`SumitWebhookController`):** Creates a `SumitWebhook` model, dispatches `ProcessSumitWebhookJob`, returns 200. The job fires `SumitWebhookReceived`, which package listeners use (e.g. TransactionSyncListener, CustomerSyncListener, RefundWebhookListener). That flow is for the package’s own features (card lifecycle, CRM, etc.).  
- **Our flow:** We do not use that. We use `POST /api/webhooks/sumit` → `App\Http\Controllers\Api\WebhookController` → `SumitPaymentGateway::handleWebhook()` → `BillingService::markPaymentSucceeded/Failed`. We do not use `SumitWebhook`, the job, or the package’s event. So **no** internal logic that we rely on requires the webhook to go through the package controller.

**Hardcoded reference:** The only `route(...)` usage in the package for webhooks is `route('officeguy.webhook.bit')` in `PublicCheckoutController` (Bit webhook). There is no reference to `officeguy.webhook.sumit` or the SUMIT webhook URL elsewhere in the package code we use (e.g. `PaymentService`). So nothing in our path depends on the package SUMIT webhook route.

---

## 5) Side-effects if the package route exists but is unused?

- **No conflict with our endpoint.** Our route is `POST /api/webhooks/sumit`; the package route is `POST /officeguy/webhook/sumit` (by default). Different paths; both can coexist.  
- **If SUMIT sends only to `https://kalfa.me/api/webhooks/sumit`:** The package route receives no requests. No side-effect.  
- **If someone pointed SUMIT at the package URL by mistake:** Requests would hit `SumitWebhookController`, creating `SumitWebhook` rows and firing package listeners. We do not use that for billing; our `Payment` and event activation are driven only by our `WebhookController`. So no impact on our billing logic.  
- **No shared state:** Our handler uses `billing_webhook_events` and `payments`; the package uses `officeguy_sumit_webhooks` and its own listeners. No overlap.

**Conclusion:** Safe to have the package route registered but unused. No need to disable it for correctness.

---

## Expected output (summary)

### 1) Hardcoded or configurable?

- **Path is configurable** via config (and optionally DB):  
  - `officeguy.routes.prefix` (default `officeguy`),  
  - `officeguy.routes.sumit_webhook` (default `webhook/sumit`).  
- The **fact that the route is registered** is not configurable; the package always loads its routes.

### 2) Can we safely ignore the package webhook route?

**Yes.** We can use only `https://kalfa.me/api/webhooks/sumit`. Our billing and event activation depend solely on our `WebhookController` and `SumitPaymentGateway`. The package route is for the package’s own features and is not used by our flow.

### 3) Is route disabling required?

**No.** Disabling is not required. The package does not offer a way to disable it, and leaving it registered causes no conflict or incorrect behavior for our custom endpoint.

### 4) If disabling were required — exact config change

Not applicable; disabling is not required. If in the future the package added a switch (e.g. `officeguy.routes.enable_sumit_webhook`), the change would be documented in the package; currently no such option exists.

### 5) Risk classification

**SAFE**

- Custom endpoint is independent of the package route.
- No dependency of our billing flow on the package webhook controller.
- Package route can remain registered with no impact.

---

## Conclusion

**Custom webhook endpoint is fully supported.**

You can configure SUMIT to send webhooks only to `https://kalfa.me/api/webhooks/sumit`. No need to use or override the package route; no code or config changes are required for this.
