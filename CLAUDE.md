# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Kalfa.me** is a Laravel-based Event SaaS platform with multi-tenant architecture. Users create organizations, manage events with guests/invitations, handle RSVP via web and phone (Twilio), and pay per-event or via subscription.

### Core Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 4, Flux UI v2.13, TallStackUI v2, Alpine.js, Tailwind CSS v4
- **Database**: MySQL (configurable)
- **Payment**: Sumit Gateway (officeguy/laravel-sumit-gateway)
- **Real-time**: Laravel Reverb (broadcasting)
- **Monitoring**: Laravel Pulse, Telescope (dev)
- **Testing**: PHPUnit 11
- **Mobile**: NativePHP Mobile (optional shell)

### Key Domain Models

- **Organization**: Multi-tenant container (belongs to Account)
- **Account**: Billing entity (product/subscription/trial)
- **Event**: Event with guests, invitations, tables, seat assignments
- **User**: Authenticatable via WebAuthn (passkeys), belongs to Organizations
- **Payment**: Per-event checkout payments

---

## Common Commands

### Development

```bash
# Start full dev stack (server, queue, logs, vite)
composer run dev

# Start individual services
php artisan serve
php artisan queue:listen --tries=1
php artisan pail --timeout=0
npm run dev

# Build assets for production
npm run build
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/EventTest.php

# Compact output (one line per test)
php artisan test --compact

# Create test
php artisan make:test EventTest
```

### Code Quality

```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Static analysis (Larastan)
./vendor/bin/phpstan analyse

# Generate IDE helpers
php artisan ide-helper:generate
php artisan ide-helper:models --write
```

### Database

```bash
# Create migration
php artisan make:migration create_events_table

# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Rollback
php artisan migrate:rollback
```

### Livewire Components

```bash
# Single-file component (default in v4)
php artisan make:livewire EventList

# Multi-file component
php artisan make:livewire EventList --mfc

# Class-based component (v3 style)
php artisan make:livewire EventList --class

# With namespace
php artisan make:livewire Dashboard/EventList
```

---

## Architecture Patterns

### Multi-Tenancy & Organization Scoping

**Critical**: Most routes require an active organization (`ensure.organization` middleware).

```php
// In middleware or controllers
Route::middleware('ensure.organization')->group(function () {
    // Routes here have $request->organization() available
});

// Get current organization
$organization = auth()->user()->currentOrganization;
```

### Billing Middleware Stack

Routes are protected by a billing check cascade:

```
auth → verified → ensure.organization → ensure.account_active → [route]
```

- `ensure.account_active`: Requires active product subscription or trial
- Some routes (billing pages) skip this to allow users to see/pay

### System Admin & Impersonation

System admins can impersonate organizations:

```php
// Middleware: require.impersonation (system admins MUST impersonate)
Route::middleware(['auth', 'verified', 'system.admin', 'require.impersonation'])
    ->prefix('system')
    ->group(function () {
        // System admin routes
    });

// Impersonation creates session key
// ImpersonationExpiry middleware auto-exits after duration
```

### Service Layer Architecture

Business logic lives in `app/Services/`:

- `Billing/` - Subscription, payment, entitlement logic
- `OfficeGuy/` - Sumit gateway integration
- `Sumit/` - Tokenization and checkout
- `Database/` - Database-specific operations

### Laravel Actions

Use `lorisleiva/laravel-actions` for reusable operations:

```php
// Create action
php artisan make:action ProcessRsvpAction

// Use as object, controller, job, listener
ProcessRsvpAction::make()->run($rsvp);
```

### Domain Enums

Domain concepts are PHP 8.4 enums in `app/Enums/`:

- `EventStatus`: draft, pending_payment, active, locked, archived, cancelled
- `PaymentStatus`, `InvitationStatus`, `OrganizationUserRole`, etc.

Models cast to enums:

```php
protected function casts(): array
{
    return [
        'status' => EventStatus::class,
    ];
}
```

---

## Frontend Conventions

### Component Libraries

1. **Flux UI** (`<flux:*>`) - Primary for Livewire components
2. **TallStackUI** (`<x-*>`) - Secondary UI components
3. **Blade Components** (`resources/views/components/`) - Custom shared components

### Blade Layouts

- `layouts.enterprise-app` - Main dashboard layout
- `layouts.guest` - Unauthenticated pages

### View Organization

```
resources/views/
├── components/       # Reusable Blade components
├── dashboard/        # Dashboard pages (events, guests, etc.)
├── system/           # System admin pages
├── pages/            # Route-based views (organizations, billing)
├── livewire/         # Livewire component views
└── layouts/          # Layout templates
```

### Language & Localization

- `resources/lang/en/messages.php` - English
- `resources/lang/he/messages.php` - Hebrew
- Use `__('messages.key')` for translatable strings

---

## Important File Locations

### Configuration

- `config/billing.php` - Billing and subscription settings
- `config/officeguy.php` - Sumit gateway configuration
- `config/livewire.php` - Livewire component settings
- `config/tallstackui.php` - TallStackUI configuration

### Models

- `app/Models/Event.php` - Event domain model
- `app/Models/Organization.php` - Multi-tenant organization
- `app/Models/Account.php` - Billing account
- `app/Models/User.php` - User with WebAuthn/passkeys

### Controllers

- `app/Http/Controllers/Dashboard/` - Event management
- `app/Http/Controllers/System/` - System admin
- `app/Http/Controllers/Billing*` - Checkout/subscriptions

### Livewire Components

- `app/Livewire/Dashboard/` - Dashboard components
- `app/Livewire/System/` - System admin components
- `app/Livewire/Billing/` - Billing-related components

---

## Key Patterns

### Event Status Workflow

Events progress through states:

```
draft → pending_payment → active → locked → archived
                                         ↓
                                    cancelled
```

Use `EventStatus` enum and `$event->status` transitions.

### Public RSVP Flow

1. Public event page: `/event/{slug}`
2. RSVP form: `/rsvp/{slug}` (POST to `/rsvp/{slug}/responses`)
3. Invitation link: `/invitations/{token}` (Livewire component `AcceptInvitation`)

### Phone RSVP (Twilio)

1. Incoming call webhook: `/twilio/rsvp/connect`
2. Digit response: POST `/twilio/rsvp/digit_response`
3. Voice controller in `app/Http/Controllers/Twilio/`

### Checkout Flow

1. Tokenize: `/checkout/{organization}/{event}`
2. Payment processing via Sumit gateway
3. Status page: `/checkout/status/{payment}`

---

## Testing Notes

- Tests use SQLite `:memory:` by default
- Use `RefreshDatabase` trait for database isolation
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`

---

## Debugging

### Logs

```bash
# View real-time logs
php artisan pail --timeout=0

# Check recent logs
tail -f storage/logs/laravel.log
```

### Browser Console

Use Boost MCP `browser-logs` tool to check frontend errors.

### Database Inspection

Use Boost MCP `database-query` for read-only queries during debugging.

---

## Skills & Triggers

This project uses domain-specific skills. The following skills auto-activate:

- **livewire-development** - When working with Livewire components, wire:* directives
- **fluxui-development** - When using <flux:*> components
- **tailwindcss-development** - When writing Tailwind CSS
- **pulse-development** - When configuring Laravel Pulse
- **laravel-actions** - When creating/refactoring Laravel Actions
- **medialibrary-development** - When using Spatie Media Library
- **laravel-permission-development** - When using Spatie Permissions

---

## Deployment Notes

- Frontend assets built via Vite to `public/build/`
- Use `npm run build` for production assets
- Ensure storage is linked: `php artisan storage:link`
- Clear caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear`

---

## Git Workflow

- Main branch: `main`
- Feature branches: `feature/`, `chore/`, `fix/`
- Use conventional commit messages
- Always include tests for new features

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- laravel-echo (ECHO) - v2
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `pulse-development` — Handles Laravel Pulse setup, configuration, and custom card development. Activates when installing Pulse; configuring the dashboard or authorization gate; setting up recorders and filtering; building custom Livewire cards; optimizing with Redis ingest or sampling; or when the user mentions /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `laravel-actions` — Build, refactor, and troubleshoot Laravel Actions using lorisleiva/laravel-actions. Use when implementing reusable action classes (object/controller/job/listener/command), converting service classes/controllers/jobs into actions, orchestrating workflows via faked actions, or debugging action entrypoints and wiring.
- `nativephp-mobile` — Builds native iOS and Android apps with PHP & Larvel. Activate when using native device APIs (camera, dialog, biometrics, scanner, geolocation, push notifications), EDGE components (bottom-nav, top-bar, side-nav), `#nativephp` JavaScript imports, native mobile events, NativePHP Artisan commands (native:run, native:install, native:watch), deep links, secure storage, or mobile app deployment.
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

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

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

=== kalfa/secure-storage rules ===

## kalfa/secure-storage

Secure key/value storage for NativePHP Mobile apps — backed by Keychain on iOS and EncryptedSharedPreferences on Android.

### Installation

```bash
composer require kalfa/secure-storage
```

### PHP Usage (Livewire / Blade)

The plugin exposes its bridge via `Native\Mobile\Facades\SecureStorage`. The `Kalfa\SecureStorage\Facades\SecureStorage` facade delegates to the same binding.

<code-snippet name="SecureStorage — PHP facade" lang="php">
use Kalfa\SecureStorage\Facades\SecureStorage;

// Store a value
SecureStorage::set('auth_token', 'abc123');

// Retrieve a value (returns '' when the key does not exist)
$token = SecureStorage::get('auth_token');

// Delete a value (idempotent)
SecureStorage::delete('auth_token');
</code-snippet>

### Available Methods

| Method | Description |
|---|---|
| `SecureStorage::get(string $key): string` | Return the stored value, or `''` when the key does not exist |
| `SecureStorage::set(string $key, ?string $value): bool` | Store a value; passing `null` deletes the entry |
| `SecureStorage::delete(string $key): bool` | Delete a value (idempotent) |

### Events

`Kalfa\SecureStorage\Events\SecureStorageCompleted` is dispatched after a bridge call completes.

<code-snippet name="SecureStorage — listening for events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Kalfa\SecureStorage\Events\SecureStorageCompleted;

#[OnNative(SecureStorageCompleted::class)]
public function handleCompleted(string $result, ?string $id = null): void
{
    // Handle the completed event
}
</code-snippet>

### JavaScript Usage (Vue / React / Inertia)

<code-snippet name="SecureStorage — JavaScript" lang="javascript">
import { get, set, del } from '@kalfa/secure-storage/secureStorage';

// Store a value
await set('auth_token', 'abc123');

// Retrieve a value ({ value: '' } when the key does not exist)
const { value } = await get('auth_token');

// Delete a value (idempotent)
await del('auth_token');
</code-snippet>

=== nativephp/mobile rules ===

## NativePHP Mobile

- NativePHP Mobile is a Laravel package for building native iOS and Android apps using PHP and native UI components. It runs a full PHP runtime directly on the device with SQLite — no web server required.
- Documentation: `https://nativephp.com/docs/mobile/3/**`
- IMPORTANT: Always activate the `nativephp-mobile` skill every time you work on any NativePHP functionality.

### Build Commands — Tell the User, Never Run

**CRITICAL: Never execute any of these commands yourself. Always instruct the user to run them manually in their terminal.**

| Command | Purpose |
|---|---|
| `npm run build -- --mode=ios` | Build frontend assets for iOS |
| `npm run build -- --mode=android` | Build frontend assets for Android |
| `php artisan native:run ios` | Compile and run on iOS simulator/device |
| `php artisan native:run android` | Compile and run on Android emulator/device |
| `php artisan native:run ios --watch` | Build, deploy, then start hot reload — all in one |
| `php artisan native:watch` | Hot reload (watch for file changes) |
| `php artisan native:open` | Open project in Xcode or Android Studio |

**Always ask which platform before giving any build or run command.** If the user hasn't specified iOS or Android, ask: "Which platform do you want to build/test on — iOS or Android?" Never assume a platform.

When the platform is confirmed, give the relevant command(s) above and tell the user to run it in their terminal. Do not run it yourself.
</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
