# Copilot Instructions

## Stack

- **Laravel 12** / PHP 8.4 · **Livewire 4** · **Alpine.js v3** · **Tailwind CSS v4**
- **Node.js** (`server.js`) — WebSocket relay: Twilio Media Stream ↔ Gemini Live API
- **PostgreSQL** (production) · **SQLite** (tests)
- **PHPUnit 11** (no Pest — convert any Pest tests to PHPUnit)
- Payment: `officeguy/laravel-sumit-gateway` (SUMIT only; CardCom/PayPal are legacy stubs)
- OTP/Voice: Twilio Verify API · Programmable Voice (Hebrew TTS via Google he-IL-Standard-A)

---

## Commands

```bash
# Full dev stack (PHP serve + queue + pail + Vite)
composer dev

# Individual services
php artisan serve
php artisan queue:listen --tries=1
npm run dev
node server.js           # WebSocket relay on port 4000

# Build
npm run build

# Tests
php artisan test --compact                                        # all tests
php artisan test --compact tests/Feature/SomeTest.php            # single file
php artisan test --compact --filter=testMethodName               # single test

# Code style (run after any PHP edit)
vendor/bin/pint --dirty --format agent
```

---

## Architecture

### Multi-Tenancy
Every resource belongs to an `Organization`. Users join orgs via `organization_users` pivot (roles: `Owner`, `Admin`, `Editor`, `Viewer` — `OrganizationUserRole` enum).

- **Active org is stored in `users.current_organization_id`** (DB is source of truth, not session).
- Always read the active org via `OrganizationContext::current()` or `auth()->user()->currentOrganization`. Never read `organization_id` from the request directly.
- `EnsureOrganizationSelected` middleware enforces this on all tenant routes.

### System Admin / Impersonation
- `users.is_system_admin` flag → grants access to `/system/*` routes.
- `users.is_disabled` → blocks login (checked in `LoginController`).
- Impersonation stores `impersonation.original_admin_id`, `impersonation.original_organization_id`, `impersonation.started_at` in session; expires via `ImpersonationExpiry` middleware.
- When impersonating, `User::$currentOrganization` bypasses membership check.

### Payment Flow
1. Event created in `Draft` status.
2. `BillingService::initiateEventPayment()` → creates `EventBilling` + `Payment`, transitions event to `PendingPayment`.
3. SUMIT returns a redirect URL → user completes payment.
4. Webhook hits `POST /api/webhooks/{gateway}` → `WebhookController` → `BillingService::markPaymentSucceeded/Failed()`.
5. On success → event transitions to `Active`.

**PCI**: `InitiateCheckoutRequest` rejects any payload containing card data keys. Only single-use tokens via PaymentsJS are accepted.

### Node.js Voice Bridge (`server.js`)
Listens on port 4000 (WebSocket). Twilio streams audio → Node relays to Gemini Live API (BidiGenerateContent) → responses TTS back through Twilio. On call end or no-answer, triggers WhatsApp fallback. Calls `save_rsvp` tool and POSTs to `POST /api/twilio/rsvp/process`.

### Middleware Registration (Laravel 12)
No `Kernel.php`. All middleware configured in `bootstrap/app.php` via `Application::configure()->withMiddleware()`. Named aliases: `ensure.organization`, `system.admin`.

---

## Key Conventions

- `declare(strict_types=1);` at the top of every PHP file.
- Model casts use the `casts(): array` **method**, not the `$casts` property.
- Always create **Form Request** classes for validation — never inline validation in controllers.
- Use `env()` only inside `config/` files. Elsewhere use `config('section.key')`.
- Prefer `Model::query()` over `DB::`. Eager-load to prevent N+1.
- Enum values are `TitleCase` (e.g. `EventStatus::Active`).
- Named routes + `route()` for all URL generation.
- PHPDoc blocks over inline comments; add array-shape types where helpful.
- PHP 8 constructor property promotion in `__construct`.
- Always use curly braces for control structures, even single-line bodies.
- New models should ship with a factory and seeder.
- After modifying PHP files, run Pint: `vendor/bin/pint --dirty --format agent`.

### Testing
- Tests are PHPUnit classes created with `php artisan make:test --phpunit {Name}`.
- Use factories (and existing factory states) to create test models.
- Cover happy paths, failure paths, and edge cases.
- Never delete existing tests without explicit approval.
- Run the minimal relevant filter before finalising, then offer to run the full suite.

### Frontend
- `@/` alias resolves to `resources/js/`.
- Tailwind CSS v4 — check existing patterns before adding utilities.
- If a UI change isn't visible, the user needs to run `npm run build` or `composer dev`.
- Flowbite 4 components are available; check for existing components before writing new ones.
- Spatie Permissions uses team context (`SpatiePermissionTeam` middleware scopes permissions to the active organization).

### Organization Policies
Policies live in `app/Policies/` and verify org membership:
```php
$user->organizations()->where('organizations.id', $resource->organization_id)->exists();
```
System admins impersonating an org must bypass this check (see `User::$currentOrganization`).

/var/www/vhosts/kalfa.me/httpdocs
├── AGENTS.md
├── Admin-Layer.md
├── CLAUDE.md
├── CONFIG_ALIGNMENT_REPORT.md
├── Call.wav
├── DOCUMENT_ROOT.md
├── GEMINI.md
├── HEARTBEAT.md
├── IDENTITY.md
├── MODEL_ROLE_SPEC.md
├── MVP_RSVP_SYSTEM_ADMIN_E2E_VERIFICATION_DIRECTIVE.md
├── PHASE_KALFA_C_PACKAGE_FEATURE_CAPABILITY_AUDIT.md
├── PHASE_KALFA_VENDOR_CONTRACT_FORENSICS.md
├── REPO_MIGRATION_REPORT.md
├── SOUL.md
├── SYSTEM_ADMIN_MATURITY_EXPANSION_DIRECTIVE.md
├── TOOLS.md
├── Tocheck.md
├── USER.md
├── app.js
├── artisan
├── boost.json
├── bootstrap
│   ├── app.php
│   └── providers.php
├── claude-os
│   ├── CLAUDE.md
│   ├── README.md
│   ├── WHAT_IS_CLAUDE_OS.pdf
│   ├── brochure.html
│   ├── claude-os-config.json
│   ├── install.sh
│   ├── presentation.html
│   ├── pytest.ini
│   ├── requirements.txt
│   ├── restart_services.sh
│   ├── setup-claude-os.sh
│   ├── setup.sh
│   ├── start.sh
│   ├── start_all_services.sh
│   ├── stop_all_services.sh
│   └── uninstall.sh
├── composer.json
├── composer.lock
├── config
│   ├── app.php
│   ├── auth.php
│   ├── billing.php
│   ├── blade-iconsax.php
│   ├── broadcasting.php
│   ├── cache.php
│   ├── database.php
│   ├── events.php
│   ├── filesystems.php
│   ├── livewire.php
│   ├── logging.php
│   ├── mail.php
│   ├── media-library.php
│   ├── officeguy-webhooks.php
│   ├── officeguy.php
│   ├── permission.php
│   ├── product-engine.php
│   ├── pulse.php
│   ├── pwa.php
│   ├── queue.php
│   ├── reverb.php
│   ├── robotstxt.php
│   ├── sanctum.php
│   ├── services.php
│   ├── session.php
│   └── telescope.php
├── copilot-chat.vsix
├── database
│   └── database.sqlite
├── docs
│   ├── ACCOUNT_ENTITLEMENTS_README.md
│   ├── ACCOUNT_INSERTION_MAP.md
│   ├── ARCHITECTURAL-NORMALIZATION-REPORT.md
│   ├── AUTH_UI_LOGIN_LOGOUT_MAP.md
│   ├── CALLING_SYSTEM_TECHNICAL.md
│   ├── CALLING_SYSTEM_USER_GUIDE.md
│   ├── CLAUDE_VERIFICATION_REPORT.md
│   ├── CLIENT_ID_COUPLING_ANALYSIS.md
│   ├── COMPATIBILITY_CHECKLIST_ACCOUNT_PHASE.md
│   ├── Checklist+Fix.md
│   ├── DB_SCHEMA_AUDIT.md
│   ├── DEPENDENCY-STABILIZATION-AUDIT.md
│   ├── DESIGN_SYSTEM_TAILWIND.md
│   ├── EMAIL_INFRASTRUCTURE_REPORT.md
│   ├── EMAIL_OPERATIONAL_GUIDE.md
│   ├── EVENTS_CALENDAR_AND_MAPS.md
│   ├── LIVEWIRE_V4_AUDIT.md
│   ├── Livewire-Page-izard.md
│   ├── MODELS_ORGANIZATION_ACCOUNT_USER_ANALYSIS.md
│   ├── MVP-RSVP-Seating-Phase1.md
│   ├── PANEL_READONLY_TO_FULL_MANAGEMENT_GAP_ANALYSIS.md
│   ├── PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md
│   ├── PANEL_UI_STRUCTURE.md
│   ├── PDF_TOOLS_RULES.md
│   ├── PERMISSION_MANAGEMENT_GUIDELINES.md
│   ├── PHASE-8-DOMAIN-INTEGRITY-AUDIT.md
│   ├── PRODUCT_CREATION_GUIDE.md
│   ├── Public_Customer_ID_for_Account.md
│   ├── ROUTES_NAVBAR_MAPPING.md
│   ├── STORAGE_403_APACHE_PLESK.md
│   ├── SYSTEM-BASELINE-AUDIT-REPORT.md
│   ├── SYSTEM-STATUS-AUDIT.md
│   ├── SYSTEM_ORGANIZATIONS_ANALYSIS.md
│   ├── TABLES_SORT_AND_DIAGRAM_DESIGN.md
│   ├── TWILIO_SMS_PRODUCT_SETUP_GUIDE.md
│   ├── UI_UX_DESIGN_ANALYSIS_AND_MAPPING.md
│   ├── UX_AUDIT_REPORT.md
│   ├── VENDOR_CONTRACT_REQUIREMENTS.md
│   ├── VOICE_RSVP_RESEARCH.md
│   ├── auth-authorization-phase1.md
│   ├── checkout-mode-tokenization-scope.md
│   ├── configuration-governance-sumit.md
│   ├── env-database.md
│   ├── environment-recovery-report.md
│   ├── filesystem-permissions-fix.md
│   ├── final-routing-validation.md
│   ├── hardening-and-production-readiness.md
│   ├── hardening-phase-report.md
│   ├── legacy-cleanup-audit.md
│   ├── live-sandbox-payment-execution-report.md
│   ├── live-sandbox-payment-validation-report.md
│   ├── pci-saq-tokenization.md
│   ├── product-scope-out-of-scope-models.md
│   ├── production-path-lock.md
│   ├── production-routing-diagnosis.md
│   ├── production-validation-tokenization.md
│   ├── rsvp-voice-gemini-live-analysis.md
│   ├── scope-approval-tokenization.md
│   ├── sumit-config-validation.md
│   ├── sumit-dependency-audit.md
│   ├── sumit-integration.md
│   ├── sumit-package-publish-decision.md
│   ├── sumit-production-cutover-checklist.md
│   ├── sumit-sandbox-execution-validation.md
│   ├── sumit-sandbox-runbook.md
│   ├── sumit-transaction-review.md
│   ├── sumit-webhook-routing-audit.md
│   ├── sumit-webhook-validation.md
│   ├── supplemental-constraints-admin-phase1.md
│   ├── tree-app.txt
│   ├── tree-full.txt
│   ├── twilio-cli-verify-whatsapp.md
│   ├── twilio-stream-nginx-websocket.conf
│   └── verify-whatsapp-setup.md
├── ecosystem.config.cjs
├── ecosystem.config.js
├── gemini-cli
│   ├── CONTRIBUTING.md
│   ├── Dockerfile
│   ├── GEMINI.md
│   ├── LICENSE
│   ├── Makefile
│   ├── README.md
│   ├── esbuild.config.js
│   ├── eslint.config.js
│   ├── package-lock.json
│   ├── package.json
│   └── tsconfig.json
├── index.html
├── js.md
├── landing-backup
│   ├── app.js
│   ├── index.html
│   ├── public-index.html
│   └── robots.txt
├── package-lock.json
├── package.json
├── phpunit.xml
├── project-analysis-app-only.json
├── project-analysis.json
├── public
│   ├── app.js
│   ├── ed10cadfe97c4073ec25bf8b0e6d45e3.html
│   ├── favicon.ico
│   ├── index-laravel.php
│   ├── index.html
│   ├── logo.png
│   ├── manifest.json
│   ├── offline.html
│   ├── robots.txt
│   ├── sw.js
│   ├── test_glm.php
│   └── test_glm_models.php
├── robots.txt
├── routes
│   ├── api.php
│   ├── auth.php
│   ├── channels.php
│   ├── console.php
│   └── web.php
├── scripts
│   └── install-local-voice-stack.sh
├── server.js
├── tests
│   └── TestCase.php
├── tinker.md
├── vite.config.js
└── voice.wav

12 directories, 192 files
