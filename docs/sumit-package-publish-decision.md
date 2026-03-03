# SUMIT Package Publish Decision

## Purpose

Determine whether any package resources (migrations, config, views, routes) must be **published** for our headless SUMIT integration to work at runtime. Publish only if technically required.

---

## STEP 1 — Runtime dependency verification

### A) PaymentService::processCharge() requirements

| Dependency | Required in our flow? | Evidence |
|------------|------------------------|----------|
| **officeguy_settings table** | **No** | We use redirect mode only. `getOrderCustomer()` calls `SettingsService::get('merge_customers', false)`. When the table does not exist, SettingsService returns `config('officeguy.merge_customers', false)` — config-only. See [sumit-dependency-audit.md](sumit-dependency-audit.md). |
| **officeguy_transactions table** | **No** | `OfficeGuyTransaction::create()` runs only when `redirectMode === false` and payment succeeds. We always call with `redirectMode = true` and return after receiving `RedirectURL`; we never reach the DB write block. |
| **Any DB writes during redirect flow** | **No** | Redirect path exits at lines 945–958 with `redirect_url`; no package table is read or written. |

**Conclusion:** processCharge() in redirect mode does **not** require any package DB tables. Config-only is sufficient.

---

### B) Webhook validation requirements

| Dependency | Required? | Evidence |
|-------------|----------|----------|
| **Package DB tables** | **No** | We use our own `WebhookController` and `billing_webhook_events` table. We call `WebhookService::verifySignature($signature, $payload, $secret)` with the secret from `config('billing.webhook_secret')` — no package DB. |
| **Package webhook controllers** | **No** | We do not use the package’s `SumitWebhookController` or package webhook routes. Our endpoint is `POST /api/webhooks/sumit`. |
| **Published configuration** | **No** | Webhook secret is read from our `config/billing.php` → `env('BILLING_WEBHOOK_SECRET')`. Package config is not required for webhook validation. |

**Conclusion:** Webhook validation does not require package DB tables, package controllers, or published package config.

---

### C) Where credentials are read from

| Source | Used by our flow? |
|--------|--------------------|
| **config/officeguy.php** | **Yes.** PaymentService uses `config('officeguy.company_id')`, `config('officeguy.private_key')` (and optionally `officeguy.environment`, `officeguy.merge_customers`). Our project already contains `config/officeguy.php` (created/copied independently or in a prior setup). |
| **DB-backed settings (officeguy_settings)** | **No.** When the table is missing, SettingsService falls back to config. We do not rely on the table. |

**Conclusion:** Credentials are read from **config only** (config files that read from `.env`). DB-backed settings are **not** mandatory for our headless redirect flow.

---

## Package assets (what the package can publish)

From `OfficeGuyServiceProvider::boot()`:

| Asset | Tag | Purpose |
|-------|-----|---------|
| **Config** | `officeguy-config` | `officeguy.php`, `officeguy-webhooks.php` → `config_path()`. |
| **Migrations** | — | Loaded via `loadMigrationsFrom()` (not publish-only; they run with `php artisan migrate` unless excluded). |
| **Migrations (copy)** | `officeguy-migrations` | Copy package migrations to `database/migrations`. |
| **Views** | `officeguy-views` | Blade views → `resources/views/vendor/officeguy`. |
| **Lang** | `officeguy-lang` | Translations → `lang/vendor/officeguy`. |
| **Public assets** | `officeguy-assets` | `public` → `public_path('vendor/officeguy')`. |
| **Routes** | — | Loaded via `loadRoutesFrom()` (package routes registered automatically; we do not use them for checkout or webhooks). |

---

## Which are safe to ignore (for our product)

- **Views** — We do not use package checkout or Filament UI. Safe to ignore; do **not** publish.
- **Filament resources** — Not a publish tag; package registers them. We do not use Filament for billing. Safe to ignore.
- **Public checkout controllers / routes** — We do not expose or use them. Our checkout is API-only; our webhook is `POST /api/webhooks/sumit`. Safe to ignore.
- **Lang** — Not required for our API-only flow. Safe to ignore.
- **Public assets** — Not required for redirect or webhook. Safe to ignore.
- **officeguy-webhooks.php** — Optional for our flow; we use `billing.webhook_secret` and our own webhook endpoint. Safe to ignore unless we later align with package webhook config.

---

## Which are required (if any)

- **Config (officeguy.php):** The **file** is required so that `config('officeguy.company_id')` and `config('officeguy.private_key')` exist. The project **already has** `config/officeguy.php` (values from `.env`). No **publish** step is required for runtime; the file is already present.
- **Migrations:** **Not required** for our flow. We do not use `officeguy_settings` or `officeguy_transactions` in the redirect or webhook path. We must **not** run or publish package migrations that would create those tables for this app unless we change architecture. See [sumit-dependency-audit.md](sumit-dependency-audit.md).

---

## Final decision: publish or not

| Action | Decision | Reason |
|--------|----------|--------|
| **Publish config** | **Do not publish** | `config/officeguy.php` already exists in the project and reads from `.env`. No need to run `php artisan vendor:publish --tag=officeguy-config`. |
| **Publish migrations** | **Do not publish** | Our runtime does not require package tables. Publishing would add migrations that create `officeguy_*` tables we do not use and could conflict with our billing tables or migration order. |
| **Publish views / lang / assets** | **Do not publish** | Not used in headless flow; forbidden by project rules. |
| **Use package routes for checkout/webhook** | **Do not use** | We use our own API routes only. |

**Summary:** No package assets need to be **published** for sandbox or production. Config is already in place; credentials and redirect URLs come from `.env`. No silent fallback to stub in production: when `BILLING_GATEWAY=sumit` and required env keys are missing, the app throws at boot (production only). See [sumit-config-validation.md](sumit-config-validation.md) and [sumit-production-cutover-checklist.md](sumit-production-cutover-checklist.md).
