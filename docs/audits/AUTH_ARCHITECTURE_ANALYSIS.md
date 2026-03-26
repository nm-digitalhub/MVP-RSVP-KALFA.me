# Laravel Authentication Architecture Analysis
**Date:** 2026-03-20
**Environment:** Production (https://kalfa.me)

## Executive Summary

### Primary Root Cause
**Laravel is not configured to trust the reverse proxy (Nginx), causing session cookie delivery failures.**

**Evidence:**
1. Site runs behind Nginx (confirmed via `curl -I https://kalfa.me`)
2. Nginx terminates SSL and passes HTTP to Laravel
3. Laravel has NO proxy trust configuration in `bootstrap/app.php`
4. Laravel sets cookies with `secure=true` but may not correctly detect HTTPS
5. When Laravel thinks it's HTTP but cookie is Secure, browser won't send it back
6. Result: Session not persisted → CSRF token mismatch → HTTP 419 PAGE EXPIRED

### Secondary Issues
1. **WebAuthn response format warning**: Browser console warnings about SimpleWebAuthn API usage (cosmetic, doesn't break functionality)
2. **Session regeneration redundancy**: LoginController calls `session()->regenerate()` after Auth::attempt() already regenerated the session (minor performance issue, not causing failures)

### Architectural Risks
- **Current**: `eloquent-webauthn` provider used globally for all authentication
- **Risk**: Low - WebAuthnUserProvider extends EloquentUserProvider and doesn't override `retrieveById()`, so session restoration works correctly
- **Finding**: The custom provider is ARCHITECTURALLY SOUND and not causing the authentication failures

## Architecture Map

### Guards
```php
'guards' => [
    'web' => [
        'driver' => 'session',      // SessionGuard
        'provider' => 'users',      // WebAuthnUserProvider
    ],
    'sanctum' => [
        'driver' => 'sanctum',      // Token-based
        'provider' => null,
    ],
]
```

### Providers
```php
'providers' => [
    'users' => [
        'driver' => 'eloquent-webauthn',  // Custom WebAuthnUserProvider
        'model' => App\Models\User::class,
        'password_fallback' => true,      // Falls back to password validation
    ],
]
```

### Middleware Stack
**Web Middleware Group:**
1. `EncryptCookies` (built-in)
2. `AddQueuedCookiesToResponse` (built-in)
3. `StartSession` (built-in) → Starts database session
4. `AuthenticateSession` (built-in)
5. `ShareErrorsFromSession` (built-in)
6. `ValidateCsrfToken` → REPLACED by `App\Http\Middleware\VerifyCsrfToken`
7. **Appended**:
   - `RequestId`
   - `ImpersonationExpiry`
   - `SpatiePermissionTeam`

**Dashboard Routes:**
- `Route::middleware(['auth', 'verified'])`
- Does NOT use `ensure.organization` (organization selection is separate)

### Controllers
1. **LoginController** (`app/Http/Controllers/Auth/LoginController.php`)
   - `POST /login` → Validates credentials, calls `Auth::attempt()`, regenerates session, redirects

2. **WebAuthnLoginController** (`app/Http/Controllers/WebAuthn/WebAuthnLoginController.php`)
   - `POST /webauthn/login/options` → Returns challenge for WebAuthn assertion
   - `POST /webauthn/login` → Verifies WebAuthn assertion, authenticates user
   - Uses `WebAuthnUserProvider` for credential validation

### Package Extensions
**laragear/webauthn:**
- Registers `eloquent-webauthn` user provider
- `WebAuthnUserProvider` extends `EloquentUserProvider`
- Overrides `retrieveByCredentials()` and `validateCredentials()`
- Does NOT override `retrieveById()` - uses parent EloquentUserProvider method
- **Critical:** Session authentication still works because `retrieveById()` is inherited

### Session & WebAuthn State
- **Session Driver:** Database (`sessions` table)
- **Session Cookie:** `kalfa-session`
- **Domain:** `.kalfa.me` (wildcard subdomain)
- **Secure:** `true` (HTTPS only)
- **SameSite:** `lax`
- **WebAuthn Challenge:** Stored in session via `SessionChallengeRepository`

## End-to-End Flow Analysis

### Standard Login Flow

#### 1. GET /login (LoginController::create)
```
Request → web middleware → guest middleware → LoginController::create()
         ↓
      Returns login view
```

#### 2. POST /login (LoginController::store)
```
Request → web middleware → guest middleware → LoginController::store()
         ↓
      Validate credentials
         ↓
      Auth::attempt($credentials)
         ↓
      SessionGuard::attempt()
         ↓
      [WebAuthnUserProvider::retrieveByCredentials($credentials)]
         ↓
      [WebAuthnUserProvider::validateCredentials($user, $credentials)]
         → Checks if WebAuthn assertion OR password
         → Falls back to password hash check (password_fallback=true)
         ↓
      SessionGuard::login($user)
         ↓
      SessionGuard::updateSession($user->getAuthIdentifier())
         → Stores user ID in session: 'login_web_59ba3...' => 1
         → session()->regenerate(true)
         ↓
      [LoginController: $request->session()->regenerate()] ← REDUNDANT
         ↓
      redirect()->intended(dashboard)
```

#### 3. POST /login → Redirect to /dashboard
```
Request → web middleware → auth middleware → verified middleware
         ↓
      [PROBLEM: Session cookie not sent by browser]
         ↓
      ValidateCsrfToken → Token mismatch!
         ↓
      bootstrap/app.php exception handler
         → Returns 419 JSON for XHR requests
         → Returns redirect to /login for web requests
```

#### 4. Session Restoration (Theoretical - Should Work)
```
Request → StartSession middleware
         ↓
      Reads session cookie from browser
         ↓
      Loads session from database by ID
         ↓
      SessionGuard::user()
         ↓
      SessionGuard::resolve()
         ↓
      Reads user ID from session: session()->get('login_web_59ba3...')
         → Returns: 1
         ↓
      [WebAuthnUserProvider::retrieveById(1)]
         → Inherits from EloquentUserProvider
         → User::find(1)
         → Returns User model
         ↓
      SessionGuard::setUser($user)
         ↓
      Auth::check() === true
```

**Finding:** Session restoration ARCHITECTURE is CORRECT. The provider CAN retrieve users by ID.

### WebAuthn Login Flow

#### 1. POST /webauthn/login/options
```
Request → web middleware → throttle:webauthn → WITHOUT CSRF
         ↓
      WebAuthnLoginController::options(AssertionRequest)
         ↓
      AssertionRequest::toVerify($credentials)
         ↓
      AssertionCreator::send(AssertionCreation)
         → CreateAssertionChallenge pipe
         → Stores challenge in session
         ↓
      Returns JSON:
      {
        "challenge": "...",
        "timeout": 60000,
        "rpId": "kalfa.me",
        "allowCredentials": [...]
      }
```

#### 2. POST /webauthn/login (Assertion)
```
Request → web middleware → throttle:webauthn → WITHOUT CSRF
         ↓
      WebAuthnLoginController::login(AssertedRequest)
         ↓
      AssertedRequest::login()
         ↓
      AssertionValidator::send($assertion, $user)
         → Retrieves challenge from session
         → Verifies assertion signature
         → Validates challenge matches
         ↓
      [CRITICAL: Challenge must exist in session]
         ↓
      SessionGuard::login($user)
         → Stores user ID in session
         → Regenerates session
         ↓
      Returns 204 on success / 422 on failure
```

**Potential 500 Cause:**
- If challenge was lost from session → AssertionValidator throws exception
- If user retrieval fails → Exception
- If database connection fails → Exception

## Verification Test Results

### Test 1: Can WebAuthnUserProvider retrieve by ID?
```php
$provider = auth()->guard('web')->getProvider();
$user = $provider->retrieveById(1);
// Result: SUCCESS - User model returned correctly
```

### Test 2: Does session persist after login?
```php
$guard->login($user);
session()->save();
$savedSession = DB::table('sessions')->where('id', session()->getId())->first();
// Result: SUCCESS - user_id column = 1, payload contains login key
```

### Test 3: Does session restoration work?
```php
$payload = unserialize(base64_decode($savedSession->payload));
// Result: SUCCESS - Payload contains 'login_web_59ba3...' => 1
$retrievedUser = $provider->retrieveById($payload['login_web_59ba3...']);
// Result: SUCCESS - User retrieved correctly
```

### Test 4: URL/Proxy Detection
```php
$request = Illuminate\Http\Request::capture();
echo $request->getScheme();  // Returns: http (WRONG - should be https)
echo $request->isSecure();   // Returns: false (WRONG - should be true)
```

**CRITICAL FINDING:** Laravel thinks it's running on HTTP, not HTTPS!

## Root Cause Analysis

### Why Auth Works in Tinker But Fails in Browser

**In Tinker (CLI):**
- No browser cookies involved
- Direct database access
- Session storage works perfectly
- User authentication works perfectly

**In Production (Browser):**
1. Browser makes HTTPS request to https://kalfa.me
2. Nginx terminates SSL, passes HTTP to Laravel
3. Laravel doesn't trust proxy, thinks it's HTTP
4. Laravel generates CSRF token
5. Laravel sets session cookie with `secure=true`
6. Browser receives response, but:
   - **BUG:** Cookie's Secure flag conflicts with what browser sees
   - OR: Domain mismatch prevents cookie from being sent
7. User clicks submit / is redirected
8. Browser DOESN'T send session cookie back
9. Laravel creates NEW session (no CSRF token match)
10. ValidateCsrfToken throws 419
11. Exception handler redirects to /login

### The Missing Configuration

**File:** `bootstrap/app.php`

**Missing:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*'); // Or specific IP
})
```

**OR in Laravel 12:**
```php
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

$middleware->append(TrustProxies::class);
```

With environment variable:
```
TRUSTED_PROXIES=*
```

## Why eloquent-webauthn is NOT the Problem

1. **Extends EloquentUserProvider:**
   - Inherits all standard session authentication methods
   - `retrieveById()` is NOT overridden - uses parent implementation
   - Session restoration works correctly (verified in tests)

2. **Only Overrides Credential Handling:**
   - `retrieveByCredentials()` - Adds WebAuthn support
   - `validateCredentials()` - Checks WebAuthn assertion OR password
   - Both methods respect `password_fallback=true`

3. **SessionGuard Integration:**
   - SessionGuard doesn't know or care about custom provider
   - Only cares that provider implements `retrieveById()`
   - WebAuthnUserProvider inherits this from EloquentUserProvider

4. **Testing Confirms:**
   - Login works and stores user in session
   - Session persists to database correctly
   - Provider can retrieve user by ID from session
   - The break is between browser and Laravel (cookie delivery)

## Required Fix

### Primary Fix: Configure Proxy Trust

**File:** `bootstrap/app.php`

**Change:**
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*'); // Trust all proxies (behind Cloudflare/Nginx)

    $middleware->alias([
        // ... existing aliases
    ]);
    // ... rest of middleware config
})
```

**OR (with environment variable):**

**File:** `.env`
```
TRUSTED_PROXIES=*
```

**File:** `bootstrap/app.php`
```php
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(
        at: env('TRUSTED_PROXIES', '*')
    );

    // ... rest of config
})
```

### Why This Fixes the Issue

1. **Trust X-Forwarded-Proto header:**
   - Nginx sends `X-Forwarded-Proto: https`
   - Laravel trusts this header when proxy is trusted
   - Laravel correctly detects HTTPS
   - Laravel generates correct secure cookies

2. **Trust X-Forwarded-Host header:**
   - Laravel knows the real hostname
   - Cookies set for correct domain

3. **Trust X-Forwarded-For header:**
   - Laravel logs correct client IPs
   - Rate limiting works correctly

### Secondary Fix: Remove Redundant Session Regeneration

**File:** `app/Http/Controllers/Auth/LoginController.php`
**Line:** 39

**Change:**
```php
// REMOVE this line (already done by Auth::attempt):
// $request->session()->regenerate();
```

**Reason:** `Auth::attempt()` already calls `$this->session->regenerate(true)` internally. Calling it again is redundant but not harmful.

### Tertiary: WebAuthn Format Warning (Optional)

The SimpleWebAuthn warnings are cosmetic and don't break functionality. To fix:

1. Wrap server response in expected format
2. OR: Update to latest laragear/webauthn version
3. OR: Suppress warnings (they're backward-compatible)

**This is NOT causing the login failures.**

## Verification Steps After Fix

1. **Test Standard Login:**
   - Visit /login
   - Enter email/password
   - Submit
   - Should redirect to /dashboard (not back to /login)
   - Browser DevTools → Application → Cookies: Should see `kalfa-session` cookie

2. **Test Session Persistence:**
   - Login
   - Refresh page
   - Should stay logged in
   - Check database: `sessions` table should have `user_id = 1`

3. **Test WebAuthn Login:**
   - Use passkey
   - Should authenticate and redirect
   - No 500 error

4. **Test Across Subdomains:**
   - Login on kalfa.me
   - Visit www.kalfa.me (if configured)
   - Should maintain session (`.kalfa.me` wildcard domain)

## Conclusion

**The eloquent-webauthn provider is SAFE and ARCHITECTURALLY SOUND.** It correctly extends Laravel's EloquentUserProvider and preserves all session authentication functionality.

**The PRIMARY ROOT CAUSE is missing proxy trust configuration.** Laravel doesn't trust Nginx's X-Forwarded headers, causing incorrect HTTPS detection and breaking secure cookie delivery.

**The fix is simple and safe:** Configure Laravel to trust the reverse proxy, then all authentication flows (standard and WebAuthn) will work correctly.
