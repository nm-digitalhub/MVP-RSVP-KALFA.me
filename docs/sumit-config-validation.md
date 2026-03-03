# SUMIT Configuration Integrity Check

## Purpose

Ensure that when SUMIT is selected as the billing gateway in production, all required configuration is present. The application must fail fast at boot with a clear exception rather than failing later during checkout or webhook handling.

---

## config('billing.default_gateway')

- **Source:** `config/billing.php` → `env('BILLING_GATEWAY', 'stub')`.
- **Values:** `stub` (default) or `sumit`.
- **Usage:** In `AppServiceProvider::register()` the binding for `PaymentGatewayInterface` is chosen from this value. In `boot()`, when the environment is production and the value is `sumit`, required SUMIT config is validated.

---

## Required SUMIT env keys (production only)

When `APP_ENV=production` and `BILLING_GATEWAY=sumit`, the following are required. If any are missing or blank, the app throws at boot:

| Env key | Purpose |
|---------|--------|
| **OFFICEGUY_COMPANY_ID** | SUMIT company ID (from config `officeguy.company_id`). |
| **OFFICEGUY_PRIVATE_KEY** | SUMIT API private key (from config `officeguy.private_key`). |
| **BILLING_SUMIT_SUCCESS_URL** | Redirect URL after successful payment. |
| **BILLING_SUMIT_CANCEL_URL** | Redirect URL after cancelled payment. |

Validation runs in `AppServiceProvider::boot()` and calls an internal `validateSumitConfig()` method that checks each of the above via the corresponding config keys. No secrets are logged or written to repo/docs.

---

## Fail-fast behavior

- **When:** During application bootstrap, after service providers are booted (first request or artisan command in production).
- **Condition:** `app()->environment('production')` is true and `config('billing.default_gateway') === 'sumit'`.
- **Action:** If any required value is missing, `RuntimeException` is thrown with message listing the missing env key names (e.g. `OFFICEGUY_COMPANY_ID`, `BILLING_SUMIT_SUCCESS_URL`).
- **Result:** The request or command fails immediately; no checkout or webhook is processed with invalid or incomplete SUMIT config.

---

## Non-production

- In local/staging/testing, validation is **not** run. Missing SUMIT config in those environments does not cause a boot exception, so development with `BILLING_GATEWAY=stub` or with partial SUMIT config remains possible.

---

## Implementation

- **File:** `app/Providers/AppServiceProvider.php`.
- **Method:** `validateSumitConfig()` (private), called from `boot()` only when environment is production and default gateway is sumit.
- **Checks:** `config('officeguy.company_id')`, `config('officeguy.private_key')`, `config('billing.sumit.redirect_success_url')`, `config('billing.sumit.redirect_cancel_url')` — each must be non-blank (Laravel `blank()` helper).
