# Multi-Tenant Architecture - Kalfa.me Event SaaS

**Last Updated:** 2026-03-24
**Architecture Type:** Row-Level Tenancy
**Framework:** Laravel 12.x

---

## 🏗️ Architecture Pattern: Row-Level Tenancy

This application uses **row-level tenancy** where tenant isolation is achieved through foreign key columns rather than separate databases or schemas.

### Three-Layer Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                   ACCOUNT (Billing Entity)                      │
│  Purpose: Commercial billing, subscriptions, entitlements        │
│  Primary Key: id                                                   │
│  Relationships: HasMany organizations, subscriptions, products    │
└──────────────────────────┬──────────────────────────────────┘
                           │ account_id (FK, nullable)
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                 ORGANIZATION (Tenant)                           │
│  Purpose: Multi-tenant container for events                      │
│  Primary Key: id                                                   │
│  Columns: account_id (FK), name, slug, is_suspended              │
│  Relationships: BelongsTo Account, HasMany users, events          │
└──────────────────────────┬──────────────────────────────────┘
                           │ organization_id (FK, NOT NULL)
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                      EVENTS (Tenant Data)                        │
│  Purpose: Event management with guests, invitations, tables      │
│  Primary Key: id                                                   │
│  Columns: organization_id (FK), name, status, event_date          │
│  Relationships: All child data scoped via organization_id        │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Database Schema for Multi-Tenancy

### Core Foreign Key Relationships

| Table | Tenant Column | References | Isolation Type |
|-------|--------------|------------|----------------|
| organizations | account_id | accounts.id | Billing → Tenant |
| events | organization_id | organizations.id | Tenant → Data |
| guests | event_id | events.id | Data → Event |
| invitations | event_id | events.id | Data → Event |
| event_tables | event_id | events.id | Data → Event |
| seat_assignments | event_id | events.id | Data → Event |
| payments | organization_id | organizations.id | Tenant → Payment |
| payments | account_id | accounts.id | Billing → Payment |
| events_billing | organization_id | organizations.id | Tenant → Billing |
| events_billing | account_id | accounts.id | Billing → Billing |
| account_subscriptions | account_id | accounts.id | Billing → Subscription |
| account_products | account_id | accounts.id | Billing → Product |

---

## 🔒 Tenant Isolation Mechanisms

### 1. Database-Level (Foreign Keys)
```sql
-- PostgreSQL foreign keys enforce data integrity
ALTER TABLE events
ADD CONSTRAINT events_organization_id_foreign
FOREIGN KEY (organization_id) REFERENCES organizations(id);

ALTER TABLE guests
ADD CONSTRAINT guests_event_id_foreign
FOREIGN KEY (event_id) REFERENCES events(id);
```

### 2. Application-Level (Middleware)

**EnsureOrganizationSelected:**
```php
// Forces user to have active organization
Route::middleware('ensure.organization')->group(function () {
    // All tenant-scoped routes
});
```

**EnsureAccountActive:**
```php
// Checks billing access before allowing protected routes
Route::middleware('ensure.account_active')->group(function () {
    // Routes require active subscription/trial
});
```

### 3. Query-Level (Manual Scoping)

Controllers must explicitly scope queries:
```php
// DashboardController.php - Example
$events = Event::where('organization_id', $organization->id)->get();
```

**⚠️ NO Global Scopes Defined** - All tenant scoping is explicit in queries.

---

## 👥 User ↔ Organization Relationship

### Many-to-Many with Pivot Table

**Table:** `organization_users`
```php
user_id (FK → users.id)
organization_id (FK → organizations.id)
role (varchar: owner, admin, member)
created_at, updated_at
```

**Relationship Methods:**
```php
// In User model
public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class, 'organization_users')
        ->using(OrganizationUser::class)
        ->withPivot('role')
        ->withTimestamps();
}

// In Organization model
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'organization_users')
        ->using(OrganizationUser::class)
        ->withPivot('role')
        ->withTimestamps();
}
```

### Current Organization Tracking

**Table:** `users.current_organization_id`
```php
// In User model - Computed property with validation
public ?Organization $currentOrganization {
    get {
        return $this->organizations()->where('organizations.id', $this->current_organization_id)->first();
    }
}
```

**Context Service:**
```php
// OrganizationContext.php
public function current(): ?Organization
{
    return auth()->user()->currentOrganization;
}
```

---

## 💰 Billing Multi-Tenancy

### Account as Billing Entity

**Accounts can be:**
- `organization` type - Linked to one or more organizations
- `individual` type - Direct user billing

**Organization → Account Relationship:**
```
Organization (1) ──┐
Organization (2) ──┼──→ Account (shared billing)
Organization (3) ──┘
```

**Implication:**
- Multiple organizations can share one billing account
- Each organization has `account_id` FK (nullable)
- System management organizations may have `account_id = null`

### Billing Access Cache

```php
// In Account model
public function hasBillingAccess(): bool
{
    return Cache::remember("account:{$this->id}:billing_access", 60, function () {
        return $this->activeAccountProducts()->exists()
            || $this->activeSubscriptions()->exists()
            || $this->subscriptions()->where('status', 'trial')->where('trial_ends_at', '>', now())->exists();
    });
}
```

**Cache Invalidation:**
- After granting products
- After subscription changes
- After trial modifications

---

## 🔑 Feature-Based Entitlements (Product Engine)

### Hierarchical Resolution

**Priority Order:**
1. **Account Override** - Manual entitlement at account level
2. **Propagated** - From AccountProduct → Product → ProductEntitlement
3. **Plan Limits** - From AccountSubscription → ProductPlan → metadata.limits
4. **Product Defaults** - From Product → ProductEntitlement
5. **System Defaults** - From config/product-engine.php

### Feature Resolution Service

```php
// FeatureResolver service
public function get(Account $account, string $featureKey): mixed
{
    // 1. Check account overrides
    // 2. Check propagated entitlements
    // 3. Check plan limits
    // 4. Check product defaults
    // 5. Return system default
}
```

### Feature Cache

**Cache Key:** `"account:{$account->id}:feature:{$featureKey}"`
**Cache TTL:** 300 seconds (configurable)
**Invalidation:** Automatic on subscription/product changes

---

## 🎭 Multi-Tenant Permission Scoping

### Spatie Laravel Permission with Teams

**Team ID = Organization ID**

**Middleware:** `SpatiePermissionTeam`
```php
// Sets team context for permissions
app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
```

**Role Definitions (OrganizationMemberService):**
```php
const ROLE_PERMISSIONS = [
    'Organization Admin' => [
        'view-event-details',
        'manage-event-guests',
        'manage-event-tables',
        'send-invitations',
    ],
    'Organization Editor' => [
        'view-event-details',
        'manage-event-guests',
    ],
];
```

---

## 🎯 Tenant Context Resolution

### Request Lifecycle

```
1. Request arrives
2. Authentication (WebAuthn passkey)
3. Email verification check
4. Organization selection check (ensure.organization)
5. Resolve current organization:
   - From session OR
   - From $user->current_organization_id
6. Account active check (ensure.account_active)
7. Route execution with tenant context
```

### System Admin Impersonation

**Session Keys:**
- `impersonation.original_organization_id` - Admin's original org
- `impersonation.impersonated_organization_id` - Target org

**Middleware:** `RequireImpersonationForSystemAdmin`
```php
// System admins MUST impersonate to access tenant routes
if ($user->is_system_admin && !session()->has('impersonation.original_organization_id')) {
    abort(403, 'System admin must impersonate an organization');
}
```

---

## 📱 Tenant-Specific Features

### Twilio Subaccounts (Optional)
```php
// Account model
protected $fillable = [..., 'twilio_subaccount_sid'];
```

Each Account can have its own Twilio subaccount for voice RSVP.

### SUMIT Customer Mapping
```php
// Organization can inherit SUMIT customer from Account
public function getSumitCustomerId(): ?int
{
    return $this->sumit_customer_id ?? $this->account?->sumit_customer_id;
}
```

### Media Library Isolation
- Event images scoped via `event_id`
- Organization can share SUMIT customer ID

---

## 🔍 Data Access Patterns

### Reading Tenant Data

**Controller Pattern:**
```php
public function index(Request $request): View
{
    $organization = $this->context->current(); // From OrganizationContext service

    $events = Event::where('organization_id', $organization->id)
        ->withCount('guests')
        ->orderByDesc('event_date')
        ->get();

    return view('dashboard.index', compact('events'));
}
```

### Writing Tenant Data

**All writes must include organization_id:**
```php
Event::create([
    'organization_id' => $organization->id,
    'name' => $request->name,
    // ...
]);
```

**Route Model Binding automatically scopes:**
```php
Route::get('events/{event}')
// → Event::where('organization_id', $org->id)->where('id', $event)->firstOrFail()
```

---

## ⚙️ Configuration

### Product Engine Config
```php
// config/product-engine.php
'feature_cache_ttl' => 300, // seconds
'usage' => [
    'default_policy' => 'hard', // or 'soft' for overage billing
],
'operations' => [
    'trial_expirations' => [
        'enabled' => true,
        'frequency' => 'everyFiveMinutes',
    ],
],
```

### Billing Config
```php
// config/billing.php
'default_gateway' => 'stub', // or 'sumit'
'allowed_gateways' => ['sumit', 'stub'],
'webhook_secret' => env('BILLING_WEBHOOK_SECRET'),
```

---

## 📋 Best Practices Observed

### ✅ Implemented Patterns

1. **Explicit Tenant Scoping** - All queries include `organization_id`
2. **Billing Isolation** - Account layer separate from Organization
3. **Feature Caching** - Hierarchical resolution with 300s TTL
4. **Permission Team Scoping** - Spatie teams aligned with organizations
5. **Impersonation Safety** - System admins must impersonate orgs
6. **Soft Deletes** - Event, Guest use SoftDeletes trait
7. **Pivot Model** - OrganizationUser extends Pivot for extra attributes

### ⚠️ Areas Requiring Manual Attention

1. **No Global Scopes** - Every query must explicitly scope tenant
2. **Cache Invalidation** - Must call `invalidateBillingAccessCache()` after billing changes
3. **Current Org Validation** - Property accessor checks membership on every access
4. **Nullable account_id** - Some orgs may lack billing account (system orgs)

---

## 🎓 Key Takeaways for Multi-Tenant SaaS

1. **Row-level tenancy is simplest** - Single database, FK-based isolation
2. **Separate billing layer** - Account entity enables shared billing across orgs
3. **Explicit scoping required** - No magic global scopes, must be intentional
4. **Cache carefully** - Feature resolution cached, billing access cached
5. **Polymorphic relations** - Payment, BillingIntent use morphTo for flexibility
6. **Pivot models for extra data** - organization_users pivot with role column

---

## 📚 Related Documentation Files

- `RELATIONSHIPS_COMPLETE.md` - Full relationship map of all 91 explicit relationships
- `CLAUDE.md` - Project-level documentation
- `routes/web.php` - Route definitions showing middleware stack