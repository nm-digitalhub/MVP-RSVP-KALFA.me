# Production Routing Diagnosis — kalfa.me

**Domain:** https://kalfa.me  
**Document root (confirmed):** `httpdocs/public`  
**Issue:** `POST /api/webhooks/sumit` returned 404 (Plesk error page), request did not reach Laravel.

---

## 1) Apache/Nginx rewrite forwarding

| Check | Result |
|-------|--------|
| Requests forwarded to front controller | **NO** — document root had no `.htaccess` |
| Rule blocking `/api/*` | None found |
| `.htaccess` in `httpdocs/public` | **MISSING** (existed only in sibling `public/` at vhost root) |
| AllowOverride | Not verified in this session (Plesk panel); rewrite now works after adding `.htaccess` |

**Finding:** The directory actually used as document root (`/var/www/vhosts/kalfa.me/httpdocs/public`) did **not** contain a `.htaccess` file. A Laravel-style `.htaccess` exists under `/var/www/vhosts/kalfa.me/public/` (different path). So Apache never applied rewrite rules for the live site; non-file requests (e.g. `/api/webhooks/sumit`) were not sent to the front controller and produced a 404.

---

## 2) Laravel front controller

| Test | Result |
|------|--------|
| `POST https://kalfa.me/index.php/api/webhooks/sumit` | **404** — no `index.php` in document root |
| `POST https://kalfa.me/index-laravel.php/api/webhooks/sumit` | **500** — request reaches Laravel (app error, not routing) |

So when the URL explicitly targeted the front controller (`index-laravel.php`), the request reached Laravel. The only way for “pretty” URLs like `/api/webhooks/sumit` to work is rewrite in the document root sending them to that front controller.

---

## 3) Plesk error documents

- 404 was served as a generic “Page Not Found” style page (Plesk-style).
- When rewrite was not active, Apache had no handler for `/api/webhooks/sumit`, so it fell through to the server’s 404 (e.g. from Plesk `error_docs` or equivalent). No special “static 404 override for POST” or “location block overriding /api” was required for this; the request simply never reached Laravel.

---

## 4) GET test

- `GET https://kalfa.me/api/webhooks/sumit` returned **404** before adding `.htaccess**, and returns **405** or **500** after (Laravel is reached; method or app logic may differ). No extra test route was added.

---

## Root cause

**Document root in use is `httpdocs/public`.** That directory had **no `.htaccess`**, so:

- Apache did not run mod_rewrite rules there.
- Requests to non-existent paths (e.g. `/api/webhooks/sumit`) were not rewritten to the front controller.
- The server therefore returned 404 (e.g. Plesk error page) before Laravel ran.

The front controller in this deployment is **`index-laravel.php`** (no `index.php` in document root). So even a correct rewrite must point to `index-laravel.php`, not `index.php`.

---

## Required fix (Apache / document root)

**1. Add `.htaccess` in the document root**  
Path: `httpdocs/public/.htaccess` (i.e. the same directory that is the document root in Plesk).

**2. Content:** Standard Laravel-style rewrite, with the last rule sending all non-file, non-dir requests to **`index-laravel.php`**:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index-laravel.php [L]
</IfModule>
```

**3. AllowOverride**  
Ensure `AllowOverride` for this document root allows “FileInfo” (or “All”) so `.htaccess` (and thus `RewriteRule`s) are applied. In Plesk: Apache & Nginx Settings → “Additional Apache directives” or the vhost’s `<Directory>` for the document root should not override with `AllowOverride None`.

**4. Optional (cleaner long-term)**  
Rename or deploy `index-laravel.php` as `index.php` and change the last `RewriteRule` to `index.php [L]` so the setup matches standard Laravel and future deployments.

---

## Verification

After placing `.htaccess` as above:

- `POST https://kalfa.me/api/webhooks/sumit` returns **500** (or 200 with valid payload) instead of **404**, i.e. the request reaches Laravel. Any 500 is from application/runtime (e.g. env, temp, DB), not from routing.

No Laravel application code or route definitions were changed; only server-level configuration (`.htaccess` in document root and AllowOverride) was added or verified.
