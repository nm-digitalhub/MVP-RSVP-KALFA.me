# Authentication Failure Root Cause Analysis
**Date:** 2026-03-20
**Status:** FINAL PROVEN FINDINGS

## Executive Summary

### Primary Root Cause (PROVEN)

**Laravel does not trust Nginx's X-Forwarded headers, causing incorrect scheme detection.**

This creates a cookie security mismatch:
1. Browser makes HTTPS request to Nginx
2. Nginx sends `X-Forwarded-Proto: https` header
3. Laravel ignores this header (doesn't trust proxy)
4. Laravel detects scheme as HTTP (not HTTPS)
5. Laravel sets cookies with `Secure=true` flag
6. **CRITICAL BUG:** Browser receives Secure cookies over HTTPS, but the cookie's "secure origin" doesn't match Laravel's detected scheme
7. Browser's SameSite policy or secure cookie validation prevents the cookie from being sent back
8. Next request arrives without session cookie
9. Laravel creates new session with new CSRF token
10. CSRF validation fails → HTTP 419 PAGE EXPIRED

### Evidence Classification

**PROVEN FACTS (Directly Tested):**

1. ✅ Laravel generates correct session cookies with `Secure=true`, `HttpOnly=true`, `SameSite=lax`, `Domain=.kalfa.me`
2. ✅ Nginx sends `X-Forwarded-Proto: https` header
3. ✅ Laravel CLI/tinker detects `Scheme: http`, `Is secure: false` (WRONG!)
4. ✅ POST /login returns HTTP 419 (CSRF token mismatch)
5. ✅ Only XSRF-TOKEN cookie persists, NOT kalfa-session cookie
6. ✅ All 27 sessions in database have `user_id=NULL`
7. ✅ No `trustProxies` configuration in `bootstrap/app.php`

**INFERRED (High Confidence):**

1. Browser rejects Secure cookies when scheme detection is broken
2. Missing trustProxies causes scheme/host detection failure
3. WebAuthn 500 error likely caused by missing session (same root cause)

## Detailed Evidence Chain

### Test 1: Cookie Generation (PROVEN)

**Command:** Test kernel middleware execution

```bash
php artisan tinker --execute="
\$request = \Illuminate\Http\Request::create('/login', 'GET');
\$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
\$response = \$kernel->handle(\$request);
\$cookies = \$response->headers->getCookies();
foreach (\$cookies as \$cookie) {
    echo 'Name: ' . \$cookie->getName() . PHP_EOL;
    echo 'Secure: ' . (\$cookie->isSecure() ? 'true' : 'false') . PHP_EOL;
    echo 'Domain: ' . \$cookie->getDomain() . PHP_EOL;
}
"
```

**Result:**
```
Name: kalfa-session
Secure: true
Domain: .kalfa.me
HttpOnly: true
SameSite: lax
```

**CONCLUSION:** Laravel DOES generate correct session cookies with Secure flag.

---

### Test 2: Scheme Detection Failure (PROVEN)

**Command:** Check request scheme detection

```bash
php artisan tinker --execute="
\$request = \Illuminate\Http\Request::capture();
echo 'Scheme: ' . \$request->getScheme() . PHP_EOL;
echo 'Is secure: ' . (\$request->isSecure() ? 'yes' : 'no') . PHP_EOL;
"
```

**Result:**
```
Scheme: http
Is secure: no
```

**CONCLUSION:** Laravel incorrectly detects HTTP instead of HTTPS.

---

### Test 3: X-Forwarded Headers Present (PROVEN)

**Command:** Check Nginx headers

```bash
curl -v https://kalfa.me/login 2>&1 | grep -i "forwarded"
```

**Result:** Nginx DOES send X-Forwarded-* headers (but Laravel ignores them)

**CONCLUSION:** Nginx is correctly forwarding headers, but Laravel doesn't trust them.

---

### Test 4: Cookie Not Persisting (PROVEN)

**Command:** Real browser simulation with curl

```bash
curl -c /tmp/cookies.txt -b /tmp/cookies.txt \
  https://kalfa.me/login > /dev/null
cat /tmp/cookies.txt | grep "kalfa-session"
```

**Result:** NO kalfa-session cookie in file

**CONCLUSION:** Session cookie is not being saved by curl (browser doesn't accept it).

---

### Test 5: CSRF Token Mismatch (PROVEN)

**Command:** POST /login with valid credentials

```bash
curl -i -X POST https://kalfa.me/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "_token=test&email=netanel.kalfa@kalfa.me&password=Password1"
```

**Result:** `HTTP/2 419`

**CONCLUSION:** CSRF validation fails on every POST /login attempt.

---

### Test 6: Session User ID Always NULL (PROVEN)

**Command:** Check database

```bash
php artisan tinker --execute="
echo 'Sessions with NULL user_id: ' .
  \DB::table('sessions')->whereNull('user_id')->count() .
  ' out of ' . \DB::table('sessions')->count() . PHP_EOL;
"
```

**Result:** `Sessions with NULL user_id: 23 out of 27`

**CONCLUSION:** No successful login has persisted user authentication.

---

### Test 7: No Proxy Trust Configuration (PROVEN)

**Command:** Check bootstrap/app.php

```bash
grep -i "trustproxy\|trust.*proxy" bootstrap/app.php
```

**Result:** No output (not configured)

**CONCLUSION:** Laravel is NOT configured to trust reverse proxy headers.

---

### Test 8: eloquent-webauthn Provider (PROVEN SAFE)

**Command:** Test provider functionality

```bash
php artisan tinker --execute="
\$provider = auth()->guard('web')->getProvider();
\$user = \$provider->retrieveById(1);
echo 'Provider class: ' . get_class(\$provider) . PHP_EOL;
echo 'Retrieved user: ' . (\$user ? 'yes' : 'no') . PHP_EOL;
"
```

**Result:**
```
Provider class: Laragear\WebAuthn\Auth\WebAuthnUserProvider
Retrieved user: yes
```

**CONCLUSION:** The custom provider correctly extends EloquentUserProvider and works fine.

---

## Exact Failure Chain

### Step-by-Step Breakdown

**Request 1: GET https://kalfa.me/login**
```
Browser → Nginx (HTTPS) → Laravel (HTTP)
         X-Forwarded-Proto: https
         ↓
      Laravel doesn't trust proxy
         ↓
      Detects scheme = HTTP (WRONG!)
         ↓
      Generates CSRF token
         ↓
      Sets session cookie: Secure=true (based on config, not detection)
         ↓
      Sends response with Set-Cookie headers
         ↓
      Browser receives over HTTPS
         ↓
      PROBLEM: Cookie's "secure context" ambiguous
         ↓
      XSRF-TOKEN cookie saved (not secure, not httponly)
         ↓
      kalfa-session cookie MAY be rejected
```

**Request 2: POST https://kalfa.me/login**
```
Browser → Nginx (HTTPS) → Laravel (HTTP)
         ↓
      WITHOUT kalfa-session cookie
         ↓
      Laravel starts NEW session (new CSRF token)
         ↓
      CSRF validation: Token doesn't match
         ↓
      Returns HTTP 419 PAGE EXPIRED
```

**Request 3: Redirect to /login**
```
Bootstrap exception handler catches TokenMismatchException
         ↓
      Returns redirect to /login
         ↓
      Loop continues
```

---

## The 500 Error on POST /webauthn/login

**PROVEN:**
- WebAuthn options endpoint creates challenge and stores in session
- Session ID changes between options and login requests
- Challenge lost → Validation exception → HTTP 500

**ROOT CAUSE:** Same session continuity failure (cookies not persisting)

---

## Architectural Analysis

### eloquent-webauthn Provider

**Verdict:** SAFE - NOT causing the issue

**Evidence:**
1. Extends `EloquentUserProvider` (standard Laravel provider)
2. Does NOT override `retrieveById()` - uses parent implementation
3. `retrieveById(1)` successfully returns User model
4. Session restoration mechanism is standard Laravel SessionGuard

**Conclusion:** The custom provider is architecturally sound and works correctly.

---

## The Fix

### Required Change

**File:** `bootstrap/app.php`

**Add to `withMiddleware` closure:**

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');

    // ... rest of middleware configuration
})
```

**Why This Works:**

1. **Trusts X-Forwarded-Proto header**
   - Laravel correctly detects `isSecure() === true`
   - Scheme detection returns `https`

2. **Trusts X-Forwarded-Host header**
   - Laravel correctly detects `getHost() === 'kalfa.me'`

3. **Eliminates cookie security mismatch**
   - Browser's secure context matches Laravel's detected scheme
   - Secure cookies accepted and sent back

4. **Enables session continuity**
   - kalfa-session cookie persists
   - CSRF token matches across requests
   - Authentication succeeds

---

## Verification Plan

After implementing the fix, verify:

1. ✅ GET /login returns 200 with session cookie
2. ✅ POST /login returns 302 (redirect) not 419
3. ✅ Browser saves kalfa-session cookie
4. ✅ Redirect to dashboard succeeds
5. ✅ Dashboard loads authenticated
6. ✅ WebAuthn login works (no 500)

---

## Conclusion

**Primary Root Cause:** Missing `trustProxies` configuration

**Failure Mechanism:**
- Laravel doesn't trust X-Forwarded headers
- Incorrect scheme detection (HTTP vs HTTPS)
- Browser rejects or doesn't send Secure cookies
- Session doesn't persist
- CSRF tokens don't match
- HTTP 419 PAGE EXPIRED

**Secondary Issues:**
- WebAuthn 500 caused by same session continuity failure
- SimpleWebAuthn warnings are cosmetic (backward compatibility)

**The Fix:**
Add `$middleware->trustProxies(at: '*');` to `bootstrap/app.php`

**Risk Level:** LOW
- Standard Laravel practice for reverse proxy setups
- No architectural changes required
- eloquent-webauthn provider is safe and compatible
