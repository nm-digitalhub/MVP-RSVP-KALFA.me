# Final Routing Validation Report — kalfa.me

**Date:** 2026-03-01  
**Domain:** https://kalfa.me  
**Document root:** httpdocs/public  
**Scope:** Infrastructure / routing only (no code changes).

---

## 1) Rewrite active

| Test | Result |
|------|--------|
| `curl -X POST https://kalfa.me/api/webhooks/sumit` | **Does NOT return 404** |
| Response | **500** (Laravel/PHP error response) |
| Response headers | `HTTP/1.1 500`, `X-Powered-By: PHP/8.4.18`, `Content-Type: text/html; charset=utf-8`, `Cache-Control: no-cache, private` |
| Plesk/static 404 | **No** — response is from Laravel (PHP 500) |

**Conclusion:** The request reaches Laravel. Rewrite is active; no server-level 404 or static error page.

---

## 2) Laravel logs

- `storage/logs/laravel.log` exists and is writable.
- No dedicated “request reached WebhookController” line was required: Laravel does not log every HTTP request by default.
- Evidence that the request reaches the app: **500 response** with Laravel/PHP stack (e.g. `tempnam()` in the response body), i.e. the application is booted and handling the request.
- **No server-level 404** for `POST /api/webhooks/sumit`.

---

## 3) Route resolution

```text
php artisan route:list | grep webhooks
```

**Output:**

```text
POST  api/webhooks/{gateway}  webhooks.handle  › Api\WebhookController@handle
```

**Conclusion:** Route is registered; path and method match the webhook endpoint.

---

## 4) No rewrite regression (GET)

| Test | Result |
|------|--------|
| `curl https://kalfa.me/up` | **500** (Laravel response) |
| `curl https://kalfa.me/api/webhooks/sumit` (GET) | **500** (Laravel response) |

Both return a Laravel/PHP error page, not 404. So GET requests to `/up` and `/api/webhooks/sumit` are also rewritten to the front controller. No regression observed.

*(Note: There is no `api/health-check` route; Laravel health route is `health: '/up'` in `bootstrap/app.php`. `/up` was used for the GET check.)*

---

## 5) Front controller and .htaccess

| Check | Result |
|-------|--------|
| `index-laravel.php` in document root | **Exists** (`httpdocs/public/index-laravel.php`) |
| `.htaccess` rewrite target | **Points to `index-laravel.php`** (last rule: `RewriteRule ^ index-laravel.php [L]`) |
| Conflicting rewrite rules in repo | **None** — single `.htaccess` in document root; no Plesk-specific rules inspected in repo |

---

## Summary

| Item | Status |
|------|--------|
| POST /api/webhooks/sumit reaches Laravel | **Yes** (500 from Laravel, not 404) |
| HTTP status for webhook POST | **500** (application error; routing is correct) |
| Rewrite stable for POST and GET | **Yes** |
| Route registered | **Yes** (POST api/webhooks/{gateway}) |
| Front controller and .htaccess | **Correct** |

---

## Verdict

**Routing:** Production routing is **fully functional**. The webhook endpoint **reaches Laravel correctly** (evidenced by Laravel 500 response and headers; no 404 or static error page).

**READY FOR LIVE SANDBOX PAYMENT TEST** from an **infrastructure/routing** perspective:  
`POST https://kalfa.me/api/webhooks/sumit` is rewritten to the front controller and handled by Laravel.

**Note:** The current **500** response is an application/runtime issue (e.g. `tempnam()` or environment), not a routing or rewrite problem. Resolving that 500 is required for the webhook to process payloads successfully; it is outside this routing-only validation.

No code or routes were modified. Validation was limited to runtime routing and configuration.
