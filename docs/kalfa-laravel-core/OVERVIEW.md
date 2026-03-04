# Kalfa Laravel Core – Analysis

> Scope: Controllers, Requests, Models, Services, Policies, Routes.

_This document aggregates findings about the core Laravel backend: architecture, patterns, risks, and recommendations._

## High-level architecture (initial)

- Laravel 12 monolith with a clear separation between:
  - **Tenant-facing app** (dashboard, organizations, events, billing) under standard `web` routes.
  - **System admin area** under `/system/*` with its own middleware (`system.admin`) and dedicated Livewire screens.
  - **Public surfaces**: event page, RSVP page, and checkout status routes without auth.
- Multi-tenancy is organization-centric:
  - `Organization` / `OrganizationUser` models define tenants and membership.
  - `ensure.organization` middleware wraps tenant routes to enforce an active organization context.
- Events + RSVP domain is modeled explicitly via:
  - `Event`, `Guest`, `EventTable`, `SeatAssignment`, `Invitation`, `RsvpResponse` models.
- Billing is first-class in the core domain:
  - `Account`, `AccountEntitlement`, `AccountFeatureUsage`, `BillingIntent`, `BillingWebhookEvent`, `EventBilling`, `Payment`, `Plan`, `Product`, `ProductEntitlement`.
  - Dedicated tenant billing pages (`billing.account`, `billing.entitlements`, `billing.usage`, `billing.intents`).

## Key Models & Services (snapshot)

**Primary domain models (PHP in `app/Models`):**

- `User` – authentication, multi-org membership, system admin flag.
- `Organization`, `OrganizationUser` – tenant entity and pivot for roles/membership.
- Event & RSVP:
  - `Event`, `Guest`, `EventTable`, `SeatAssignment`, `Invitation`, `RsvpResponse`.
- Billing & accounts:
  - `Account`, `AccountEntitlement`, `AccountFeatureUsage`.
  - `BillingIntent`, `BillingWebhookEvent`, `EventBilling`, `Payment`, `Plan`, `Product`, `ProductEntitlement`.
- System:
  - `SystemAuditLog` – records sensitive/system-level actions.

> TODO: as we inspect each model, document relationships, enums, and invariants here.

## Routing & HTTP layer (initial observations)

- `routes/web.php` is cleanly structured with clear comments and grouped middlewares:
  - Home route redirects based on `Auth::check()` to login vs dashboard.
  - Checkout tokenization route is behind `auth` and uses `scopeBindings()` for correct tenant binding.
  - Authenticated user group (`auth`, `verified`) splits into:
    - general dashboard/organizations/profile routes,
    - `ensure.organization`-wrapped tenant routes for events & billing.
  - System admin routes are all under `Route::prefix('system')->middleware(['auth', 'verified', 'system.admin'])` and use Livewire route helpers.
  - Public routes for event view + RSVP (`event/{slug}`, `rsvp/{slug}`) are clearly separated.
- The use of `scopeBindings()` on organization + event routes signals consistent use of scoped route model binding for multi-tenancy.

## Policies & authorization patterns

> TODO: inspect `app/Policies/**` and fill in the policy mapping, especially for Event, Organization, Billing, and System admin actions.

## Testing & TDD coverage

> TODO: review `tests/Feature` and `tests/Unit` to understand coverage for core models/services and routes.

## Risks / smells (early)

- The number of models in the core domain is relatively high; maintaining clear boundaries between:
  - tenant flows vs system-admin flows,
  - event/RSVP vs billing/accounting,
  may require disciplined use of services and policies.
- Route file is well-structured but dense; as the app grows, consider extracting route groups per domain if it starts to sprawl.

## Recommendations (initial)

- Keep enforcing `ensure.organization` + `scopeBindings()` for any new tenant routes.
- As we review each model/service, consider whether some responsibilities should move into dedicated domain services (especially around Billing and Event lifecycle).
- Document policy decisions in `docs/kalfa-domain-organizations-admin/OVERVIEW.md` as we analyze policies to keep multi-tenancy and admin behavior explicit.
