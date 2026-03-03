# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

This is an **RSVP + Seating SaaS application** built with Laravel 12 and Livewire 4. It provides multi-tenant event management with guest invitations, seating assignments, and payment processing.

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire 4 + Alpine.js + Tailwind CSS v4
- **Build**: Vite 7
- **UI Components**: Flowbite 4
- **Authentication**: Laravel Sanctum
- **Payment**: `officeguy/laravel-sumit-gateway` (Israel-focused gateway) with stub fallback for local
- **Database**: PostgreSQL (production) or MySQL; SQLite for tests
- **Language**: Hebrew (RTL support) - `app.blade.php` sets `dir="rtl"`

---

## Common Development Commands

### Setup
```bash
composer install
cp .env.example .env
# Edit .env with your database credentials
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Development Server (Full Stack)
```bash
composer dev
```
Runs in parallel: PHP artisan serve, queue:listen, pail logs, and Vite dev server.

### Individual Services
```bash
php artisan serve                # Start Laravel server (port 8000)
php artisan queue:listen           # Process queue jobs
php artisan pail --timeout=0       # View real-time logs
npm run dev                     # Start Vite dev server (port 5173)
```

### Testing
```bash
php artisan test                 # Run all tests
php artisan test --filter=ClassName  # Run specific test class
php artisan test --stop-on-failure
```

### Code Quality
```bash
php artisan pint               # Code formatting (Laravel Pint)
```

### Database
```bash
php artisan migrate:fresh --seed    # Drop, recreate, and seed tables
php artisan migrate:rollback         # Rollback last migration
php artisan migrate:status          # Check migration status
```

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

---

## Architecture

### Multi-Tenancy via Organizations

The application uses **organization-based multi-tenancy**. Every resource (events, guests, payments, etc.) belongs to an organization.

**Key pattern**: `User → Organization → Resources`

- Users can belong to multiple organizations via `organization_users` pivot table with roles (Owner, Admin, Editor, Viewer)
- User's active organization is stored in `users.current_organization_id` (database is source of truth)
- `OrganizationContext` service manages organization switching — always call `OrganizationContext::current()` to get active org, never read from request directly
- `EnsureOrganizationSelected` middleware enforces that authenticated users have an active organization before accessing tenant routes

**Organization Context Flow**:
1. User logs in → redirected to organizations selection if no `current_organization_id`
2. User selects/creates organization → `OrganizationContext::set()` writes to DB + mirrors to session
3. All tenant controllers → read via `OrganizationContext::current()` which resolves from DB

### System Admin (Superuser)

- `users.is_system_admin` flag grants system-wide authority
- `users.is_disabled` flag prevents disabled users from logging in (checked in `LoginController`)
- System admin routes (`/system/*`) bypass tenant middleware entirely
- **Impersonation**: System admins can impersonate any organization (60-minute expiry via `ImpersonationExpiry` middleware)
  - Stores `impersonation.original_admin_id`, `impersonation.original_organization_id`, `impersonation.started_at` in session
  - Auto-restore via `ImpersonationExpiry` middleware or manual exit button in navbar
  - User's `currentOrganization()` method bypasses membership check when impersonating
- All admin actions logged via `SystemAuditLogger` service
- System dashboard: `/system/dashboard` with metrics (total orgs, users, events, MRR, churn, etc.)

### Payment Architecture

**Payment Gateway Interface** (`app/Contracts/PaymentGatewayInterface`):
- `createOneTimePayment()` — redirect flow (SUMIT gateway returns redirect_url)
- `chargeWithToken()` — token flow (PaymentsJS single-use tokens, no redirect)
- `handleWebhook()` — async webhook processing for payment status

**Gateways** (located in `app/Services/`):
- `StubPaymentGateway` — local development (always succeeds)
- `SumitPaymentGateway` — production gateway for Israel (officeguy/laravel-sumit-gateway adapter)

**System-Level Billing** (`app/Services/OfficeGuy/SystemBillingService.php`):
- Placeholder service for OfficeGuy subscription management
- Methods: `getOrganizationSubscription()`, `cancelSubscription()`, `extendTrial()`, `applyCredit()`, `retryPayment()`, `getMRR()`, `getChurnRate()`, `getActiveSubscriptions()`
- All methods currently return stub values until OfficeGuy integration is wired

**Billing Flow** (managed by `BillingService`):
1. Event created in `Draft` status
2. User initiates checkout → `BillingService::initiateEventPayment()` or `initiateEventPaymentWithToken()`
3. Creates `EventBilling` + `Payment` records, transitions event to `PendingPayment`
4. Gateway returns redirect_url (or processing status for token flow)
5. User pays → webhook posts to `/api/webhooks/{gateway}`
6. `WebhookController` delegates to gateway → `BillingService::markPaymentSucceeded/Failed()`
7. Payment succeeded → event transitions to `Active`

**PCI Compliance**: `InitiateCheckoutRequest` explicitly forbids card data in request payload. Only single-use tokens accepted.

### API Structure

**Routes** (`routes/api.php`):
- `/api/organizations/{organization}/events` — CRUD for events
- `/api/organizations/{organization}/events/{event}/guests` — guest management
- `/api/organizations/{organization}/events/{event}/event-tables` — table/seating layout
- `/api/organizations/{organization}/events/{event}/seat-assignments` — seat assignments
- `/api/organizations/{organization}/events/{event}/invitations` — send invitations
- `/api/organizations/{organization}/events/{event}/checkout` — initiate payment
- `/api/payments/{payment}` — payment status
- `/api/rsvp/{slug}` — public RSVP (no auth required)
- `/api/webhooks/{gateway}` — payment webhooks (POST, throttled)

All tenant routes require `auth:sanctum` and are scoped by `organization_id` in route parameters.

### Web Routes (`routes/web.php`)

- `/` → redirect to dashboard if authenticated, else login
- `/checkout/{organization}/{event}` — payment tokenization page
- `/dashboard` — main dashboard (Livewire)
- `/organizations/*` — org selection/creation/switch
- `/system/*` — admin panel (requires `system.admin` middleware)
- `/event/{slug}` — public event page
- `/rsvp/{slug}` — public RSVP page and form
- `/checkout/status/{payment}` — payment status page

### Authorization Policies

Policies (`app/Policies/`) enforce organization membership and role-based access:
- `EventPolicy` → user must belong to event's organization
- `OrganizationPolicy` → Owner/Admin can manage billing; others can view
  - `isOwnerOrAdmin()` checks pivot role against `OrganizationUserRole` enum
  - Roles: Owner, Admin, Editor, Viewer
- `GuestPolicy`, `InvitationPolicy` → scoped to organization

All policies check `user->organizations()->where('organizations.id', $resource->organization_id)->exists()`.

### Login Flow

`LoginController::store()` handles authentication with:
- Failed login → throws ValidationException with auth.failed message
- Disabled accounts → checks `user->is_disabled`, logs out if true
- Updates `last_login_at` timestamp
- Post-login redirect: System admins → `system.dashboard`, others → `dashboard` via `redirectPath()`
- Session regeneration after login

### Enum-Based Status Management

Enums (`app/Enums/`) define strict state transitions:
- `EventStatus`: Draft, PendingPayment, Active, Cancelled, Completed
- `EventBillingStatus`: Pending, Paid, Failed
- `PaymentStatus`: Pending, Processing, Succeeded, Failed
- `InvitationStatus`: Pending, Sent, Responded
- `OrganizationUserRole`: Owner, Admin, Editor, Viewer
- `RsvpResponseType`: Attending, Declining, Maybe

Models use enum casting: `protected function casts(): array { return ['status' => EventStatus::class]; }`

---

## Frontend Architecture

### Navigation Isolation

`dynamic-navbar.blade.php` provides context-aware navigation:
- Desktop: organization switcher dropdown, system admin links (when `is_system_admin`), impersonation exit button
- Mobile: drawer with same navigation, mobile menu toggle with overlay
- Shows "Exit impersonation" button when `impersonation.original_organization_id` session exists
- Separates tenant routes (dashboard, organizations, profile) from system routes

### Livewire Components
- `app/Livewire/` — interactive components
- Dashboard, Organization management (create/list/switch), Profile forms
- System admin components:
  - `System/Dashboard.php` — system-wide metrics (total orgs, users, events, MRR, churn, etc.)
  - `System/Users/Index.php` — user list with filters (admin, no-org, recent, suspended) + toggle admin role (password-protected)
  - `System/Organizations/Index.php` — organization list
  - `System/Organizations/Show.php` — org management with password-protected actions:
    - Transfer ownership (requires selecting new owner + password)
    - Suspend/activate organization
    - Force delete (catches referential integrity errors)
    - Reset data (placeholder)

### Views Structure
```
resources/views/
├── layouts/          # app.blade.php (auth), guest.blade.php (public)
├── pages/            # dashboard.blade.php, organizations pages
├── livewire/         # component views (subdirectories by feature)
├── components/        # Blade components (Flowbite-based)
├── auth/            # login, register, password reset
├── rsvp/            # public RSVP form
├── events/           # event detail views
├── dashboard/        # dashboard-specific views
└── system/           # admin panel views
```

### Styling
- Tailwind CSS v4 via `@tailwindcss/vite` plugin
- Flowbite 4 components for UI elements
- Alpine.js for client-side interactivity

### Vite Configuration
- Source alias: `@/` → `resources/js`
- Dev server: `0.0.0.0:5173` with HMR and polling
- Build output: `public/build/` with hashed assets
- CSS minification via LightningCSS, JS via esbuild

---

## Environment Configuration

### Key `.env` Variables

**Database**:
```
DB_CONNECTION=pgsql    # or mysql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kalfa_rsvp
DB_USERNAME=
DB_PASSWORD=
```

**Billing**:
```
BILLING_GATEWAY=stub   # stub for local, sumit for production
BILLING_SUMIT_SUCCESS_URL=https://your-frontend.example/checkout/success
BILLING_SUMIT_CANCEL_URL=https://your-frontend.example/checkout/cancel
BILLING_WEBHOOK_SECRET=   # HMAC signature verification
```

**SUMIT Credentials** (required when `BILLING_GATEWAY=sumit`):
```
OFFICEGUY_ENVIRONMENT=www
OFFICEGUY_COMPANY_ID=
OFFICEGUY_PRIVATE_KEY=
OFFICEGUY_PUBLIC_KEY=
```

---

## Models and Relationships

### Core Models
- `User` — authentication, multi-org membership, system admin flag
- `Organization` — tenant entity with settings, suspension status
- `OrganizationUser` — pivot with role
- `Event` — events belong to organizations (soft deletes)
- `Guest` — event guests
- `EventTable` — seating tables/areas
- `SeatAssignment` — guest → table assignment
- `Invitation` — RSVP invitations with slugs
- `RsvpResponse` — guest responses (attending/declining/maybe)
- `Plan` — pricing tiers
- `EventBilling` — payment billing records per event
- `Payment` — individual payment attempts (polymorphic to EventBilling)
- `BillingWebhookEvent` — webhook audit log
- `SystemAuditLog` — system admin action audit

### Key Relationships
```
Organization → hasMany Events, EventBilling, Payments
Organization → belongsToMany Users (withPivot role)
User → belongsToMany Organizations
Event → belongsTo Organization
Event → hasMany Guests, Invitations, EventTables
Event → hasOne EventBilling
Event → hasMany SeatAssignments
Guest → belongsTo Event
Invitation → belongsTo Event, Guest
EventBilling → belongsTo Event, Plan
EventBilling → hasMany Payments (morphTo payable)
```

---

## Important Implementation Details

### Organization Context is Mandatory
Never read `organization_id` from request parameters in controllers. Always use:
```php
$org = OrganizationContext::current();  // or
$org = auth()->user()->currentOrganization();
```

### Impersonation Safety
When checking organization membership in policies or services, allow system admins:
```php
if ($user->is_system_admin && session()->has('impersonation.original_organization_id')) {
    // Skip membership check for impersonation
    return true;
}
```

### Payment Webhook Idempotency
Webhook handler checks if payment already in terminal state (`Succeeded`/`Failed`) before processing to avoid duplicate state transitions.

### PCI Data Handling
Never log card data. `InitiateCheckoutRequest` rejects any request with forbidden keys (card_number, cvv, etc.) in `prepareForValidation()`.

### Route Scoping
All tenant routes use `scopeBindings()` to ensure route model binding respects organization context:
```php
Route::apiResource('organizations.events', EventController::class)
    ->scoped(['organization']);
```

---

## Testing

- PHPUnit 11.5+ with SQLite in-memory database
- Test structure: `tests/Feature/` and `tests/Unit/`
- `tests/Feature/SumitProductionValidationTest.php` validates SUMIT gateway configuration

---

## File Organization

```
app/
├── Contracts/              # PaymentGatewayInterface
├── Enums/                 # All status/role enums
├── Http/
│   ├── Controllers/
│   │   ├── Api/         # API controllers (tenant-scoped)
│   │   ├── Dashboard/    # Dashboard page controllers
│   │   ├── System/       # Admin panel controllers
│   │   └── Auth/        # Breeze authentication
│   ├── Middleware/        # EnsureOrganizationSelected, EnsureSystemAdmin, ImpersonationExpiry
│   ├── Requests/         # FormRequest validation classes
│   └── Controllers.php   # Base controller
├── Livewire/             # Interactive components
├── Models/               # Eloquent models
├── Policies/             # Authorization policies
├── Services/             # BillingService, OrganizationContext, SystemAuditLogger, PaymentGateways
└── View/Components/       # Blade components
```

---

## Notes

- This is a clean slate RSVP+Seating system — DO NOT use existing app database; create new `kalfa_rsvp` database
- The project is on branch `feature/4-business-areas` with main branch at origin/main
- Livewire 4 is installed (stable) after SUMIT v3 migration
- Use `declare(strict_types=1);` in all PHP files (enforced by project standards)
