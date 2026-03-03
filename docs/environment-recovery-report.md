# Environment Recovery — Active Path & .env

**Issue:** Runtime reported `billing.default_gateway = 'stub'` and NULL redirect URLs (environment “reverted”).

**Cause:** More than one Laravel application (and more than one `.env`) exist under the vhost. The one used at runtime depends on **where** `artisan` / PHP is run and **which** document root serves the domain.

---

## Step 1 — Active application path

| Check | Result |
|-------|--------|
| **Working directory used for this run** | `/var/www/vhosts/kalfa.me/httpdocs` |
| **Laravel `base_path()`** (when run from httpdocs) | `/var/www/vhosts/kalfa.me/httpdocs` |
| **Document root for https://kalfa.me** | `httpdocs/public` (confirmed earlier) |

So the application **actually serving** https://kalfa.me is the one whose root is:

**`/var/www/vhosts/kalfa.me/httpdocs`**

That is the **active** Laravel root for this domain. Any `php artisan` or tinker run that must reflect production must be executed from this directory.

---

## Step 2 — Two Laravel roots, two .env files

| Location | .env | BILLING_* | OFFICEGUY_* |
|----------|------|-----------|-------------|
| **httpdocs** (`/var/www/vhosts/kalfa.me/httpdocs`) | Present, 1286 bytes | `BILLING_GATEWAY=sumit`, redirect URLs set | All set (company ID, keys, etc.) |
| **Vhost root** (`/var/www/vhosts/kalfa.me`) | Present, 825 bytes | **None** | Keys present but **empty** (no values) |

- **Correct .env for SUMIT / kalfa.me** is: **`httpdocs/.env`**. It already contains the right SUMIT/billing values.
- If you run `php artisan` or `php artisan tinker` from **vhost root** (`/var/www/vhosts/kalfa.me`), Laravel uses **vhost root `.env`**, which has no `BILLING_*` and empty `OFFICEGUY_*` → you see `stub` and NULL URLs. That is an **environment mismatch**, not a code or routing bug.

---

## Step 3 — Configuration in the active .env (no change needed)

The **active** `.env` (inside the active application path) is:

**`/var/www/vhosts/kalfa.me/httpdocs/.env`**

Current content for billing/SUMIT (already set):

```env
BILLING_GATEWAY=sumit
BILLING_SUMIT_SUCCESS_URL=https://kalfa.me/checkout/success
BILLING_SUMIT_CANCEL_URL=https://kalfa.me/checkout/cancel
```

OFFICEGUY_* credentials are also set in this file. No edit was required; values were already correct.

If you ever need to “fix” again:

1. Edit **only** `httpdocs/.env` (not the vhost root `.env`).
2. Then from **httpdocs** run:
   - `php artisan config:clear`
   - `php artisan cache:clear`

---

## Step 4 — Re-validation (from active path)

Run from the **active** Laravel root:

```bash
cd /var/www/vhosts/kalfa.me/httpdocs
php artisan config:clear
php artisan cache:clear
php artisan tinker --execute="
echo 'gateway: ' . config('billing.default_gateway') . PHP_EOL;
echo 'success_url: ' . (config('billing.sumit.redirect_success_url') ?? 'NULL') . PHP_EOL;
echo 'cancel_url: ' . (config('billing.sumit.redirect_cancel_url') ?? 'NULL') . PHP_EOL;
"
```

**Result (when run from httpdocs):**

- `gateway: sumit`
- `success_url: https://kalfa.me/checkout/success`
- `cancel_url: https://kalfa.me/checkout/cancel`

No cached config file was present under `bootstrap/cache/config.php`; config is read from `.env` via `config/*.php`.

---

## Summary

- **Active application path for https://kalfa.me:**  
  **`/var/www/vhosts/kalfa.me/httpdocs`**
- **Correct .env:**  
  **`/var/www/vhosts/kalfa.me/httpdocs/.env`**  
  It already contains SUMIT activation (gateway, redirect URLs, OFFICEGUY_*).
- **Why “stub” was seen:**  
  Commands were likely run from **vhost root** (`/var/www/vhosts/kalfa.me`), which has a **different** Laravel and a **different** `.env` (no BILLING_*, empty OFFICEGUY_*).
- **Rule:** For kalfa.me / SUMIT, always run artisan and tinker from:
  ```bash
  cd /var/www/vhosts/kalfa.me/httpdocs
  ```
  so that `base_path()` and `.env` match the app that serves the domain.

**ENV_OK:** YES (when using `httpdocs` as the working directory and `httpdocs/.env`).
