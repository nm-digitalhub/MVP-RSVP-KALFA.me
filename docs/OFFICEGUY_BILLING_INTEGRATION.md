# OfficeGuy Billing Integration

> Package: `officeguy/laravel-sumit-gateway` v5.0.0-rc1  
> Status: **Active** — live data sourced from `SystemBillingService`

---

## Architecture

```
System Admin UI (Livewire)
        │
        ▼
SystemBillingService          ← single entry point for all billing operations
        │ dispatches ↘
        │        App\Events\Billing\*
        │                │
        │                ▼
        │        App\Listeners\Billing\AuditBillingEvent → SystemAuditLogger
        ▼
OfficeGuy\LaravelSumitGateway
  ├── Models\Subscription     (table: officeguy_subscriptions)
  └── Services\SubscriptionService
```

**Rule:** The system layer never calls the OfficeGuy SDK directly. All access is routed through `App\Services\OfficeGuy\SystemBillingService` (Adapter / Application Service pattern). This means:
- No coupling between UI and SDK
- Gateway can be swapped by changing the service only
- Livewire components remain thin
- Audit logging is decoupled via domain events

---

## Files

| File | Role |
|------|------|
| `app/Services/OfficeGuy/SystemBillingService.php` | Adapter — all subscription & billing operations |
| `app/Jobs/SyncOrganizationSubscriptionsJob.php` | Async SUMIT sync (queued, avoids HTTP timeout) |
| `app/Events/Billing/SubscriptionCancelled.php` | Domain event — fired on subscription cancellation |
| `app/Events/Billing/TrialExtended.php` | Domain event — fired on trial extension |
| `app/Listeners/Billing/AuditBillingEvent.php` | Billing audit listener — writes to SystemAuditLogger |
| `app/Policies/OrganizationPolicy.php` | `manageBilling()` gate for billing actions |
| `app/Livewire/System/Organizations/Show.php` | Admin UI component — Billing tab |
| `app/Livewire/System/Dashboard.php` | System dashboard — MRR / churn / active subscriptions |
| `resources/views/livewire/system/organizations/show.blade.php` | Billing tab view |
| `resources/views/livewire/system/dashboard.blade.php` | Billing metrics cards |
| `resources/lang/he.json` | Hebrew translations for all billing strings |

---

## SystemBillingService

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getOrganizationSubscription(Organization)` | `?Subscription` | Latest active subscription — cached 60s |
| `forgetSubscriptionCache(Organization)` | `void` | Invalidate subscription cache after mutation |
| `cancelSubscription(Organization, ?int $actorId)` | `bool` | Cancels via `SubscriptionService::cancel()` + dispatches event |
| `extendTrial(Organization, int $days, ?int $actorId)` | `bool` | Extends `trial_ends_at` + dispatches event |
| `applyCredit(Organization, int $amount)` | `bool` | Manual credit *(stub — awaiting Sumit API)* |
| `retryPayment(Organization)` | `bool` | Retries latest failed subscription charge |
| `syncOrganizationSubscriptions(Organization)` | `int` | Syncs from SUMIT API, returns count |
| `getMRR()` | `float` | Sum of `amount` on active + ILS subscriptions only |
| `getChurnRate()` | `float` (0–1) | Cancellations in last 30d ÷ active_at_start_of_period |
| `getActiveSubscriptions()` | `Collection<int, Subscription>` | All active subscriptions with subscriber loaded |

### Subscription Cache

`getOrganizationSubscription()` is cached for 60 seconds to avoid repeated DB hits on every Livewire render:

```php
private const SUBSCRIPTION_CACHE_TTL = 60;

return Cache::remember(
    "org:{$organization->id}:subscription",
    self::SUBSCRIPTION_CACHE_TTL,
    fn () => Subscription::where(...)->first()
);
```

After any mutation, call `forgetSubscriptionCache($organization)` or `unset($this->subscription)` in the Livewire component (busts the Computed cache).

### MRR Calculation

`getMRR()` filters `status = active` **AND** `currency = ILS` to avoid mixed-currency sum errors:

```php
Subscription::where('status', Subscription::STATUS_ACTIVE)
    ->where('currency', 'ILS')
    ->sum('amount');
```

### Churn Calculation

Uses the period-based formula: `cancelled_in_last_30d / active_at_start_of_period`  
where `active_at_start = current_active + cancelled_in_last_30d`:

```php
$cancelledLast30Days = Subscription::where('status', STATUS_CANCELLED)
    ->where('cancelled_at', '>=', now()->subDays(30))->count();
$activeAtStart = $activeNow + $cancelledLast30Days;
return $activeAtStart > 0 ? $cancelledLast30Days / $activeAtStart : 1.0;
```

### Subscription Model

Table: `officeguy_subscriptions`  
Class: `OfficeGuy\LaravelSumitGateway\Models\Subscription`

**Status constants:** `STATUS_PENDING`, `STATUS_ACTIVE`, `STATUS_PAUSED`, `STATUS_CANCELLED`, `STATUS_EXPIRED`, `STATUS_FAILED`

**Key fields:** `name`, `amount`, `currency`, `interval_months`, `total_cycles`, `completed_cycles`, `status`, `trial_ends_at`, `next_charge_at`, `last_charged_at`, `cancelled_at`, `payment_method_token`

**Subscriber relation:** polymorphic `subscriber` → `Organization` (via `HasSumitCustomerTrait`)

---

## Organization Model

`Organization` implements `HasSumitCustomer` and uses `HasSumitCustomerTrait`. The Sumit customer ID resolves from the org directly or from its linked account:

```php
return $this->sumit_customer_id ?? $this->account?->sumit_customer_id;
```

---

## Domain Events

Billing mutations dispatch domain events instead of calling `SystemAuditLogger` directly. This decouples the service from audit infrastructure.

### Events

| Event | Fired when |
|-------|-----------|
| `App\Events\Billing\SubscriptionCancelled` | Subscription is cancelled via admin |
| `App\Events\Billing\TrialExtended` | Trial period is extended via admin |

### Listener

`App\Listeners\Billing\AuditBillingEvent` handles both events and writes to `SystemAuditLogger`:

```php
public function handle(SubscriptionCancelled|TrialExtended $event): void
{
    match (true) {
        $event instanceof SubscriptionCancelled => SystemAuditLogger::log(...),
        $event instanceof TrialExtended => SystemAuditLogger::log(...),
    };
}
```

Registered in `AppServiceProvider::boot()`:
```php
Event::listen(\App\Events\Billing\SubscriptionCancelled::class, \App\Listeners\Billing\AuditBillingEvent::class);
Event::listen(\App\Events\Billing\TrialExtended::class, \App\Listeners\Billing\AuditBillingEvent::class);
```

---

## Authorization — `manageBilling` Policy

Defined in `OrganizationPolicy::manageBilling()`:

```php
public function manageBilling(User $user, Organization $organization): bool
{
    if ($user->is_system_admin) {
        return true;
    }
    return $this->isOwnerOrAdmin($user, $organization);
}
```

Used in all billing actions via `$this->authorize('manageBilling', $this->organization)`.  
This is preferred over `abort_unless(is_system_admin)` because:
- Authorization is centralized and testable
- Can be reused from API controllers

---

## Livewire Component — Organizations/Show

### DI via `boot()`

```php
protected SystemBillingService $billingService;

public function boot(OrganizationMemberService $memberService, SystemBillingService $billingService): void
{
    $this->memberService = $memberService;
    $this->billingService = $billingService;
}
```

`boot()` is used (not `mount()`) so DI is re-resolved on every Livewire request cycle.

### Computed Subscription

```php
#[Computed]
public function subscription(): ?Subscription
{
    return $this->billingService->getOrganizationSubscription($this->organization);
}
```

- Fresh data on every render via service cache (60s)
- After mutations: `unset($this->subscription)` busts the per-request Livewire Computed cache

### Password-Protected Billing Actions

All billing mutations go through the `pendingAction` / `confirmAndExecute` flow:

```
requestAction('cancelSubscription' | 'extendTrial')
    → Security overlay with password input
    → confirmAndExecute()
    → executeCancelSubscription() | executeExtendTrial()
```

The component passes `actorId = auth()->id()` to the service; the service dispatches the event; the listener writes to the audit log. The Livewire component no longer calls `SystemAuditLogger` directly for billing actions.

### Sync Action (Async)

```php
public function syncSubscriptions(): void
{
    $this->authorize('manageBilling', $this->organization);
    SyncOrganizationSubscriptionsJob::dispatch($this->organization, auth()->id());
    session()->flash('success', __('Subscription sync queued. Data will update shortly.'));
}
```

Dispatched as a queued Job — SUMIT API calls can be slow and must not block the HTTP response.

---

## SyncOrganizationSubscriptionsJob

```php
public int $tries = 3;
public array $backoff = [30, 120, 300]; // seconds between retries

public function __construct(
    public readonly Organization $organization,
    public readonly ?int $actorId = null,
) {}

public function handle(SystemBillingService $billingService): void
{
    $count = $billingService->syncOrganizationSubscriptions($this->organization);
    $billingService->forgetSubscriptionCache($this->organization); // bust cache post-sync
    $actor = $this->actorId ? User::find($this->actorId) : null;
    SystemAuditLogger::log($actor, 'organization.subscriptions_synced', $this->organization, ['synced' => $count]);
}
```

- `SystemBillingService` injected via `handle()`, not constructor — avoids serialization issues
- `actorId` stored as `int` (not `User`) for the same reason
- Retries up to 3 times on transient API failures with exponential backoff

---

## System Dashboard

`app/Livewire/System/Dashboard.php` uses `boot()` DI:

```php
protected SystemBillingService $billing;

public function boot(SystemBillingService $billing): void
{
    $this->billing = $billing;
}
```

In `render()`:
```php
$mrr              = $this->billing->getMRR();            // ILS only
$activeSubscriptions = $this->billing->getActiveSubscriptions();  // Collection
$churn            = $this->billing->getChurnRate();      // period-based
```

---

## Audit Log Events

Billing domain events are handled by `AuditBillingEvent` listener:

| Event key | Trigger |
|-----------|---------|
| `organization.subscriptions_synced` | Sync job completes (direct call in job) |
| `organization.subscription_cancelled` | `SubscriptionCancelled` event → listener |
| `organization.trial_extended` | `TrialExtended` event → listener |
| `organization.subscription_payment_retried` | *(future)* |
| `organization.subscription_credit_applied` | *(future)* |

---

## Queue Setup

The sync job requires a queue worker:

```bash
# Development (via composer dev)
php artisan queue:listen --tries=1

# Production (recommended)
php artisan queue:work --daemon
# or via Supervisor
```

Default queue connection is used (`QUEUE_CONNECTION` in `.env`).

---

## Hebrew Translations

All billing-related strings are translated in `resources/lang/he.json`:

```
Active Subscription, Billing & Subscription, Billing cycle,
Cancel Subscription, Every :n month(s), Extend Trial,
Last charged, Next charge, No active subscription found.,
Subscription cancelled., Sync from SUMIT, Trial ends,
Trial extended by :days day(s)., :count subscription(s) synced from SUMIT.,
Subscription sync queued. Data will update shortly.
```

---

## Future Work

- `applyCredit()` — awaiting Sumit API support for direct credit adjustments
- Subscription creation UI — currently subscriptions are created in Sumit dashboard and synced in
- Cache dashboard metrics (`Cache::remember('system.mrr', 300, ...)`) for high-traffic admin panels
- Webhook event listeners for package-dispatched `OfficeGuy\Events\SubscriptionCancelled` / `SubscriptionCharged` to auto-update local status when SUMIT pushes

