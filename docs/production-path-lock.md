# PRODUCTION PATH — AUTHORITATIVE LOCK

**Domain:** kalfa.me  
**Status:** LOCKED  
**ENV_OK (when using correct path):** YES

---

## 1. Environment Divergence — Root Cause

Two Laravel roots exist under the same vhost:

- `/var/www/vhosts/kalfa.me`
- `/var/www/vhosts/kalfa.me/httpdocs`

Only one serves production.

Running `php artisan` from different directories loads different `.env` files.

When executed from the wrong path:

- `billing.default_gateway` = `'stub'`
- Redirect URLs = NULL

This is an **execution path mismatch**. It is not:

- Not a SUMIT issue
- Not a config bug
- Not a routing problem
- Not a cache issue

---

## 2. Authoritative Production Context

**Production Laravel root:**

```
/var/www/vhosts/kalfa.me/httpdocs
```

**Document root:**

```
httpdocs/public
```

**Active .env:**

```
/var/www/vhosts/kalfa.me/httpdocs/.env
```

This is the only valid runtime context for kalfa.me.

---

## 3. Mandatory Execution Rule

Before any production command:

```bash
cd /var/www/vhosts/kalfa.me/httpdocs
```

Applies to:

- artisan
- tinker
- config:clear
- cache:clear
- migrate
- queue workers
- any operational command

**Running from** `/var/www/vhosts/kalfa.me` **is invalid.**

---

## 4. Safety Check (Required Before Any Validation)

```bash
php artisan tinker --execute="echo base_path();"
```

**Expected output:**

```
/var/www/vhosts/kalfa.me/httpdocs
```

If different → **STOP.**

---

## 5. Current State

| Item | State |
|------|--------|
| Environment | ACTIVE |
| Gateway | sumit |
| Redirect URLs | Defined |
| Runtime validation | Confirmed |
| Path mismatch | Resolved |

---

**Operational Status:**  
**READY FOR LIVE SANDBOX PAYMENT EXECUTION**
