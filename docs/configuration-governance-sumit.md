# Configuration Governance — SUMIT Integration

**Status:** Final confirmation. Configuration model locked.  
**Environment status:** ACTIVE — READY FOR LIVE SANDBOX PAYMENT TEST.

---

## 1) Source of truth

**Source of truth: `.env`**

All SUMIT and billing activation values **MUST** exist only in `.env`.

**Required keys:**

| Key | Purpose |
|-----|--------|
| `BILLING_GATEWAY` | Gateway selector (`stub` \| `sumit`) |
| `BILLING_SUMIT_SUCCESS_URL` | Redirect after successful payment |
| `BILLING_SUMIT_CANCEL_URL` | Redirect after cancelled payment |
| `BILLING_WEBHOOK_SECRET` | Optional; webhook signature validation |
| `OFFICEGUY_COMPANY_ID` | SUMIT company ID |
| `OFFICEGUY_PRIVATE_KEY` | SUMIT API (private) key |
| `OFFICEGUY_PUBLIC_KEY` | SUMIT API public key |
| `OFFICEGUY_ENVIRONMENT` | SUMIT environment (`www` \| `dev` \| `test`) |

**Rule:** No production values may be hardcoded anywhere in application code.

---

## 2) Config files role

`config/billing.php` and `config/officeguy.php` are **mapping layers only**.

They **MUST:**

- Read values via `env('KEY_NAME')`
- Contain no hardcoded credentials
- Contain no hardcoded redirect URLs
- Contain no hardcoded gateway overrides

**Allowed:**

- `env('KEY', 'safe_default')` — e.g. `env('BILLING_GATEWAY', 'stub')`

**Forbidden:**

- Direct string values for production configuration (credentials, URLs, gateway overrides)

---

## 3) Laravel resolution model

| Mode | Behavior |
|------|----------|
| **Without config cache** | `.env` is loaded → config files call `env()` → runtime reads `config()`. |
| **With `php artisan config:cache`** | `env()` is evaluated once → compiled config cache is generated → runtime reads from cached config only. `.env` changes require `config:clear` (or cache clear) and optional cache rebuild. |

**Summary:**

- **`.env`** = configuration authority  
- **Config files** = mapping definition  
- **Config cache** = runtime snapshot  

---

## 4) Validation method

Correct runtime validation **must** use:

```php
config('billing.default_gateway')
config('billing.sumit.redirect_success_url')
config('billing.sumit.redirect_cancel_url')
```

If these return the expected values, configuration is valid.

---

## 5) Current state (at lock)

| Item | State |
|------|--------|
| Gateway | `sumit` |
| Redirect URLs | Defined in `.env` |
| Config cache | Cleared and validated |
| Runtime verification | Passed |

**ENVIRONMENT STATUS:** ACTIVE  
**READY FOR LIVE SANDBOX PAYMENT TEST**

---

## 6) Governance rules (from this point forward)

- **No configuration changes without cache clear** — after editing `.env`, run `php artisan config:clear` (and `cache:clear` if needed).
- **No secrets committed to repository** — `.env` and secrets stay out of version control; use `.env.example` with placeholders only.
- **No hardcoded overrides in config files** — `config/billing.php` and `config/officeguy.php` must only read via `env()`.
- **All runtime checks must use `config()`** — never read `getenv()` or `$_ENV` directly for these values; use `config('billing.*')` / `config('officeguy.*')`.

---

*Document: Configuration Governance — SUMIT. Final.*
