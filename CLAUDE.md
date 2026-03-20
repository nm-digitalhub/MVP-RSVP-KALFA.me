# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> For agent routing, domains, and skill activation details, see `AGENTS.md` in the project root.

---

## Project Overview

This is an **RSVP + Seating SaaS application** built with Laravel 12 and Livewire 4. It provides multi-tenant event management with guest invitations, seating assignments, and payment processing.

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.4.18)
- **Frontend**: Livewire 4 + Alpine.js + Tailwind CSS v4
- **Build**: Vite 7
- **UI Components**: Flowbite 4
- **Authentication**: Laravel Sanctum
- **Payment**: `officeguy/laravel-sumit-gateway` (SUMIT is the only active gateway; CardCom/PayPal are legacy)
- **Twilio**: Verify API for OTP (SMS/WhatsApp). SID: `VA5f1c126dd6b47bcd05492197c1c36f73`. **Programmable Voice**: outbound RSVP calls; TwiML connect → Stream to Node.js; Node relays to **Gemini Live API** for voice-to-voice; Hebrew TTS (Google.he-IL-Standard-A + SSML); WhatsApp fallback on no-answer/short call.
- **Node.js** (`server.js`): WebSocket relay Twilio Media Stream ↔ Gemini Live (BidiGenerateContent); receives guest/event/invitation params; calls `save_rsvp` tool and POSTs to Laravel `POST /api/twilio/rsvp/process`. Env: `GEMINI_API_KEY`, `PHP_WEBHOOK`, `CALL_LOG_URL`, `CALL_LOG_SECRET`.
- **Database**: PostgreSQL (production) or MySQL; SQLite for tests
- **Language**: Hebrew (RTL support) - `app.blade.php` sets `dir="rtl"`
- **Mail**: Laravel Mail (e.g. `App\Mail\WelcomeOrganizer`); views in `resources/views/emails/`

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

- Users can belong to multiple organizations via `organization_users` pivot table with roles (Owner, Admin, Member) — see `OrganizationUserRole` enum
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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.19
- laravel/framework (LARAVEL) - v12
- laravel/pint (PINT) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- laravel-echo (ECHO) - v2
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pulse-development` — Handles Laravel Pulse setup, configuration, and custom card development. Activates when installing Pulse; configuring the dashboard or authorization gate; setting up recorders and filtering; building custom Livewire cards; optimizing with Redis ingest or sampling; or when the user mentions /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
- `livewire-development` — Develops reactive Livewire 4 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `medialibrary-development` — Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths.
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.
- `ai-sdk-development` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `api-resource-patterns` — Best practices for Laravel API Resources including resource transformation, collection handling, conditional attributes, and relationship loading.
- `brainstorming-laravel` — Use when creating or developing Laravel features, before writing code or implementation plans - refines rough ideas into fully-formed Laravel designs through collaborative questioning, alternative exploration, and incremental validation.
- `eloquent-best-practices` — Best practices for Laravel Eloquent ORM including query optimization, relationship management, and avoiding common pitfalls like N+1 queries.
- `laravel-tdd` — Test-Driven Development specifically for Laravel applications using Pest PHP. Use when implementing any Laravel feature or bugfix - write the test first, watch it fail, write minimal code to pass.
- `systematic-debugging-laravel` — Systematic debugging process for Laravel applications - ensures root cause investigation before attempting fixes. Use for any Laravel issue (test failures, bugs, unexpected behavior, performance problems).
- `ui-ux-pro-max` — UI/UX design intelligence. 50 styles, 21 palettes, 50 font pairings, 20 charts, 9 stacks (React, Next.js, Vue, Svelte, SwiftUI, React Native, Flutter, Tailwind, shadcn/ui). Actions: plan, build, create, design, implement, review, fix, improve, optimize, enhance, refactor, check UI/UX code. Projects: website, landing page, dashboard, admin panel, e-commerce, SaaS, portfolio, blog, mobile app, .html, .tsx, .vue, .svelte. Elements: button, modal, navbar, sidebar, card, table, form, chart. Styles: glassmorphism, claymorphism, minimalism, brutalism, neumorphism, bento grid, dark mode, responsive, skeuomorphism, flat design. Topics: color palette, accessibility, animation, layout, typography, font pairing, spacing, hover, shadow, gradient. Integrations: shadcn/ui MCP for component search and examples.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use strict typing at the head of a `.php` file: `declare(strict_types=1);`.
- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP — no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- IMPORTANT: Activate `livewire-development` every time you're working with Livewire-related tasks.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== nativephp/mobile rules ===

## NativePHP Mobile

- NativePHP Mobile is a Laravel package that enables developers to build native iOS and Android applications using PHP and native UI components.
- NativePHP Mobile runs a full PHP runtime directly on the device with SQLite — no web server required.
- NativePHP Mobile supports **two frontend approaches**: Livewire/Blade (PHP) or JavaScript frameworks (Vue, React, Inertia, etc.).
- NativePHP Mobile documentation is hosted at `https://nativephp.com/docs/mobile/2/**`
- **Before implementing any features using NativePHP Mobile, use the `web-search` tool to get the latest docs for that specific feature. The docs listing is available in <available-docs>**

### Identifying the Development Environment

**IMPORTANT:** Before running commands or giving platform-specific advice, determine:

1. **Operating System** (check with system info or ask):
   - **macOS**: Can build and run for **both iOS and Android**
   - **Windows/Linux**: Can **only build for Android** — iOS requires macOS with Xcode
   - When on Windows/Linux, never suggest `php artisan native:run ios`, `native:install ios`, or `native:open ios`
   - **Note:** WSL (Windows Subsystem for Linux) is NOT supported — must run directly on Windows

2. **Frontend Stack** (examine the codebase):
   - **Livewire/Blade**: Look for `.blade.php` files with `wire:` directives, Livewire components in `app/Livewire/`
   - **JavaScript (Vue/React/Inertia)**: Look for `.vue`, `.jsx`, `.tsx` files, `resources/js/` with framework code, `inertiajs` in `package.json`

### Required Environment Variables

**CRITICAL:** Before running `php artisan native:install`, ensure these are set in `.env`:

```dotenv
NATIVEPHP_APP_ID=com.yourcompany.yourapp
NATIVEPHP_APP_VERSION="DEBUG"
NATIVEPHP_APP_VERSION_CODE="1"
```

- `NATIVEPHP_APP_ID`: Reverse-domain app identifier (e.g., `com.acme.myapp`) — **required**
- `NATIVEPHP_APP_VERSION`: Use `"DEBUG"` for development, semantic version (e.g., `"1.0.0"`) for production
- `NATIVEPHP_APP_VERSION_CODE`: Integer build number for Play Store (increment with each release)

**Optional but recommended for iOS:**
```dotenv
NATIVEPHP_DEVELOPMENT_TEAM=XXXXXXXXXX
```
Find your Team ID in your [Apple Developer account](https://developer.apple.com/account) under 'Membership details'.

### PHP Usage (Livewire/Blade Projects)

Use PHP Facades in the `Native\Mobile\Facades` namespace:
- `Camera::getPhoto()`, `Dialog::toast()`, `Biometrics::prompt()`, etc.
- All Facades: `Camera`, `Dialog`, `Biometrics`, `Network`, `SecureStorage`, `File`, `Share`, `Haptics`, `System`, `Device`
- Note: `Browser`, `Scanner`, `Microphone`, `Geolocation`, and `PushNotifications` are available as separate NativePHP plugins (nativephp/browser, nativephp/scanner, nativephp/microphone, nativephp/geolocation, nativephp/mobile-firebase).
- Listen for events with `#[OnNative(EventClass::class)]` attribute on Livewire component methods
- Use EDGE components in Blade templates for native UI (`native:bottom-nav`, `native:top-bar`, `native:side-nav`)

### JavaScript Usage (Vue/React/Inertia Projects)

Import from the NativePHP JavaScript bridge library:
```javascript
import { camera, dialog, scanner, biometric, on, Events } from '#nativephp';
// or individual imports
import { getPhoto, alert, scanQR } from '#nativephp';
```

The JS API mirrors the PHP Facades with fluent builders:
- `await camera.getPhoto()` / `await dialog.alert('Title', 'Message')` / `await scanner.scan()`
- `await biometric.prompt().id('auth-check')` — fluent builder pattern
- `await scanner.scan().prompt('Scan ticket').formats(['qr', 'ean13'])`

Listen for events with `on()` and `off()`:
```javascript
import { on, off, Events } from '#nativephp';
on(Events.Camera.PhotoTaken, (payload) => { /* handle photo */ });
// Cleanup in unmount: off(Events.Camera.PhotoTaken, handler);
```

### Event Handling (Both Stacks)

Asynchronous operations dispatch events to both JavaScript and PHP simultaneously.

**Livewire/Blade:**
```php
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\PhotoTaken;

#[OnNative(PhotoTaken::class)]
public function handlePhoto(string $path) { /* ... */ }
```

**JavaScript (Vue/React):**
```javascript
import { on, Events } from '#nativephp';
on(Events.Camera.PhotoTaken, ({ path }) => { /* ... */ });
```

Custom events can extend built-in events and be passed via `->event(CustomEvent::class)` (PHP) or `.event('App\Events\Custom')` (JS).

### EDGE Components (Native UI)

- EDGE (Element Definition and Generation Engine) renders Blade components as truly native UI elements.
- Components use `native:` prefix: `native:bottom-nav`, `native:top-bar`, `native:side-nav`.
- Child items require unique `id` attributes for lifecycle management.
- Add `nativephp-safe-area` class to body for proper handling of notches and navigation areas.
- **Note:** EDGE components are defined in Blade templates and work with both Livewire and Inertia apps (the layout is still Blade).

<available-docs>

## Getting Started

- [https://nativephp.com/docs/mobile/2/getting-started/introduction] Use these docs for comprehensive introduction to NativePHP Mobile, overview of how PHP runs natively on device, the embedded runtime architecture, and core philosophy behind the package
- [https://nativephp.com/docs/mobile/2/getting-started/quick-start] Use these docs for rapid setup guide to get your first mobile app running in minutes
- [https://nativephp.com/docs/mobile/2/getting-started/environment-setup] Use these docs for setting up your development environment including Xcode, Android Studio, simulators, and required dependencies
- [https://nativephp.com/docs/mobile/2/getting-started/installation] Use these docs for step-by-step installation via Composer, running `php artisan native:install`, platform-specific setup, and ICU support options
- [https://nativephp.com/docs/mobile/2/getting-started/configuration] Use these docs for detailed configuration guide including NATIVEPHP_APP_ID, NATIVEPHP_APP_VERSION, permissions setup, and config/nativephp.php options
- [https://nativephp.com/docs/mobile/2/getting-started/development] Use these docs for development workflow including `php artisan native:run`, `native:watch` for hot reload, `native:tail` for logs, and debugging techniques
- [https://nativephp.com/docs/mobile/2/getting-started/deployment] Use these docs for packaging and deploying apps to App Store and Play Store using `php artisan native:package`
- [https://nativephp.com/docs/mobile/2/getting-started/versioning] Use these docs for version management, semantic versioning, and `php artisan native:release` command
- [https://nativephp.com/docs/mobile/2/getting-started/changelog] Use these docs for version history and release notes
- [https://nativephp.com/docs/mobile/2/getting-started/roadmap] Use these docs for upcoming features and planned improvements
- [https://nativephp.com/docs/mobile/2/getting-started/support-policy] Use these docs for support policy and compatibility information

## The Basics

- [https://nativephp.com/docs/mobile/2/the-basics/overview] Use these docs for understanding how NativePHP Mobile works, the bridge between PHP and native code, and the overall architecture
- [https://nativephp.com/docs/mobile/2/the-basics/events] Use these docs for the complete event system guide including async vs sync operations, event handling in Livewire with `#[OnNative()]`, JavaScript event handling with `Native.on()`, custom events, and the dual dispatch pattern
- [https://nativephp.com/docs/mobile/2/the-basics/native-functions] Use these docs for understanding the `nativephp_call()` function, the bridge function registry, and how to extend native functionality
- [https://nativephp.com/docs/mobile/2/the-basics/native-components] Use these docs for overview of native UI components and how they integrate with your app
- [https://nativephp.com/docs/mobile/2/the-basics/web-view] Use these docs for understanding the web view rendering, JavaScript bridge, and how PHP content is displayed
- [https://nativephp.com/docs/mobile/2/the-basics/splash-screens] Use these docs for configuring splash screens on iOS and Android
- [https://nativephp.com/docs/mobile/2/the-basics/app-icon] Use these docs for setting up app icons for both platforms
- [https://nativephp.com/docs/mobile/2/the-basics/assets] Use these docs for managing static assets, images, and files in your mobile app

## EDGE Components (Native UI)

- [https://nativephp.com/docs/mobile/2/edge-components/introduction] Use these docs for understanding EDGE (Element Definition and Generation Engine), how Blade components become native UI, server-driven UI approach, and the JSON compilation process
- [https://nativephp.com/docs/mobile/2/edge-components/bottom-nav] Use these docs for implementing bottom navigation bars with `native:bottom-nav` and `native:bottom-nav-item`, including icons, labels, URLs, and styling
- [https://nativephp.com/docs/mobile/2/edge-components/top-bar] Use these docs for implementing top app bars with `native:top-bar` and `native:top-bar-action`, including titles, navigation icons, and action buttons
- [https://nativephp.com/docs/mobile/2/edge-components/side-nav] Use these docs for implementing slide-out navigation drawers with `native:side-nav`, `native:side-nav-item`, `native:side-nav-header`, and `native:side-nav-group`
- [https://nativephp.com/docs/mobile/2/edge-components/icons] Use these docs for available icon names and how to use icons in EDGE components

## APIs (Device Features)

- [https://nativephp.com/docs/mobile/2/apis/camera] Use these docs for camera operations including `Camera::getPhoto()`, `Camera::recordVideo()`, `Camera::pickImages()`, PhotoTaken and VideoRecorded events
- [https://nativephp.com/docs/mobile/2/apis/microphone] Use these docs for audio recording with `Microphone::record()`, `->start()`, `->stop()`, `->pause()`, `->resume()`, `->getStatus()`, and MicrophoneRecorded events
- [https://nativephp.com/docs/mobile/2/apis/scanner] Use these docs for QR code and barcode scanning with `Scanner::scan()`, fluent configuration, CodeScanned events, and supported formats
- [https://nativephp.com/docs/mobile/2/apis/dialog] Use these docs for native dialogs with `Dialog::alert()`, `Dialog::toast()`, button configuration, and ButtonPressed events
- [https://nativephp.com/docs/mobile/2/apis/biometrics] Use these docs for Face ID/Touch ID authentication with `Biometrics::prompt()`, fluent API, and Completed events
- [https://nativephp.com/docs/mobile/2/apis/push-notifications] Use these docs for push notification enrollment with `PushNotifications::enroll()`, `->getToken()`, and TokenGenerated events (requires nativephp/mobile-firebase plugin)
- [https://nativephp.com/docs/mobile/2/apis/geolocation] Use these docs for location services with `Geolocation::getCurrentPosition()`, `->checkPermissions()`, `->requestPermissions()`, and LocationReceived events
- [https://nativephp.com/docs/mobile/2/apis/browser] Use these docs for opening URLs with `Browser::open()`, `Browser::inApp()`, `Browser::auth()` for OAuth flows (requires nativephp/browser plugin)
- [https://nativephp.com/docs/mobile/2/apis/secure-storage] Use these docs for secure credential storage with `SecureStorage::get()`, `->set()`, `->delete()` using device Keychain/KeyStore
- [https://nativephp.com/docs/mobile/2/apis/share] Use these docs for native share sheet with `Share::url()` and `Share::file()`
- [https://nativephp.com/docs/mobile/2/apis/file] Use these docs for file operations with `File::move()` and `File::copy()`
- [https://nativephp.com/docs/mobile/2/apis/network] Use these docs for network status checking with `Network::status()`
- [https://nativephp.com/docs/mobile/2/apis/haptics] Use these docs for haptic feedback with `Haptics::vibrate()` (prefer `Device::vibrate()`)
- [https://nativephp.com/docs/mobile/2/apis/device] Use these docs for device information with `Device::getId()`, `->getInfo()`, `->getBatteryInfo()`, `->vibrate()`, `->toggleFlashlight()`
- [https://nativephp.com/docs/mobile/2/apis/system] Use these docs for platform detection with `System::isIos()`, `System::isAndroid()`, `System::isMobile()`, `System::flashlight()`

## Concepts

- [https://nativephp.com/docs/mobile/2/concepts/databases] Use these docs for SQLite database usage, local data storage, and when to use local vs API storage
- [https://nativephp.com/docs/mobile/2/concepts/deep-links] Use these docs for configuring deep links, URL schemes, and universal links
- [https://nativephp.com/docs/mobile/2/concepts/push-notifications] Use these docs for comprehensive push notification setup including Firebase, APNs, and server-side integration
- [https://nativephp.com/docs/mobile/2/concepts/security] Use these docs for security best practices, secure storage, and protecting sensitive data
- [https://nativephp.com/docs/mobile/2/concepts/ci-cd] Use these docs for continuous integration and deployment pipelines for mobile apps
</available-docs>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
