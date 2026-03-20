# Mobile SecureStorage & Login — סקירה מעמיקה

> נכתב: 2026-03-19  
> גרסת NativePHP Mobile: v3.0.4  
> חבילה: `kalfa/secure-storage` (local path package)

---

## 1. הרקע

`nativephp/mobile` חושף את ה-interface הצד-לקוח של SecureStorage (Facade + JS API), אבל **ב-NativePHP Mobile v3 כל ה-native functionality מסופקת דרך plugins — כולל camera, biometrics, ו-SecureStorage.** זוהי ארכיטקטורה מכוונת, לא חסר בליבה.

> "All native functionality in NativePHP Mobile comes through plugins — including official plugins for camera, biometrics, push notifications, and more."  
> — [nativephp.com/docs/mobile/3/plugins/introduction](https://nativephp.com/docs/mobile/3/plugins/introduction)

ההתקנה הרשמית היא `composer require nativephp/mobile-secure-storage`.

`kalfa/secure-storage` הוא **מימוש מקומי** של אותה plugin capability — מספק את ה-Swift (ו-Kotlin) שמבצעת בפועל את פעולות ה-Keychain, במקום להסתמך על החבילה הרשמית.

---

## 2. ארכיטקטורת ה-Login המלאה

### 2.1 זרימה מלאה

```
[1] App launch → iOS boots PHP → WebView loads → /mobile (shell.blade.php)

[2] Alpine.js init() → GET /mobile/session
        ↓
[3] MobileSecureStorageSessionController::show()
    → MobileSecureTokenStore::isAvailable()
      = function_exists('nativephp_call') → true בתוך NativePHP
    → MobileSecureTokenStore::hasToken()
      = SecureStorage::get('kalfa.mobile.access_token')
        = nativephp_call('SecureStorage.Get', '{"key":"kalfa.mobile.access_token"}')
        → iOS Keychain
        → null (פעם ראשונה) / token (אם שמור קודם)

[4a] אין token → state: 'unauthenticated' → user ממלא email/password
        ↓
[5] submitLogin() → POST https://kalfa.me/api/mobile/auth/login
    payload: { email, password, device_name }
    → MobileAuthController::login()
    → Hash::check() + is_disabled check
    → Sanctum createToken(['mobile:base', 'mobile:read', 'mobile:write'])
    → חוזר: { access_token, token_type, device_name, abilities, user }

[6] persistSecureCredential(token) → PUT /mobile/session
    → MobileSecureStorageSessionController::store()
    → SecureStorage::set('kalfa.mobile.access_token', token)
      = nativephp_call('SecureStorage.Set', '{"key":"...", "value":"1|abc123..."}')
      → iOS Keychain — נשמר מוצפן עם kSecAttrAccessibleWhenUnlockedThisDeviceOnly

[7] bootstrapWithToken(token) → GET https://kalfa.me/api/bootstrap
    Authorization: Bearer 1|abc123...
    → MobileBootstrapController::show()
    → { user, current_organization, memberships, abilities, flags, server_time }

[8] state = 'authenticated' ✅

[4b] יש token שמור → restoreStoredSession() → bootstrapWithToken() ישירות → [7]

[9] 401/403 בכל שלב → handleRevokedState()
    → DELETE /mobile/session
    → SecureStorage::delete('kalfa.mobile.access_token')
      = nativephp_call('SecureStorage.Delete', ...)
      → iOS Keychain — מחיקה
    → state = 'revoked'
```

### 2.2 State Machine

| State | תנאי כניסה | תנאי יציאה |
|-------|-----------|-----------|
| `unauthenticated` | Initial / login fail (non-4xx) / bootstrap fail | login מוצלח → syncing |
| `syncing` | login/bootstrap בתהליך | הצלחה → authenticated, כישלון → unauthenticated/revoked |
| `authenticated` | bootstrap מוצלח מהשרת | logout / token expired |
| `revoked` | 401/403 מהשרת | login חדש |
| `offline-stale` | שמור לשימוש עתידי — לא מומש | — |

**עיקרון**: credential מקומי בלבד **לעולם אינו מספיק** לעבור ל-`authenticated`. השרת הוא source of truth.

---

## 3. מפת הקבצים

### PHP — Backend

| קובץ | תפקיד |
|------|--------|
| `routes/web.php` | `GET /mobile` (entry), `GET/PUT/DELETE /mobile/session` |
| `routes/api.php` | `POST /api/mobile/auth/login`, `GET /api/bootstrap` |
| `app/Http/Controllers/Mobile/MobileSecureStorageSessionController.php` | קריאה/שמירה/מחיקה של token ב-SecureStorage |
| `app/Http/Controllers/Api/MobileAuthController.php` | login, logout, logoutOtherDevices |
| `app/Http/Controllers/Api/MobileBootstrapController.php` | bootstrap payload |
| `app/Http/Requests/Api/MobileLoginRequest.php` | validation: email, password, device_name |
| `app/Http/Requests/Mobile/StoreMobileSessionTokenRequest.php` | validation: access_token (max 4096) |
| `app/Http/Resources/MobileBootstrapResource.php` | JSON shape של bootstrap |
| `app/Services/MobileSecureTokenStore.php` | wrapper מסביב ל-SecureStorage facade |
| `config/mobile.php` | base_url, endpoints, secure_storage key, cache config |
| `app/Providers/AppServiceProvider.php` | RateLimiter: `mobile_session` (30/min per IP) |

### JavaScript — Frontend

| קובץ | תפקיד |
|------|--------|
| `resources/views/mobile/shell.blade.php` | Entry view + Alpine.js state machine |
| `resources/js/mobile-shell.js` | Alpine.js init + `window.NativePHPMobile.secureStorage` |
| `vendor/nativephp/mobile/resources/dist/native.js` | `SecureStorage.get/set/delete` → `/_native/api/call` |
| `vite.config.js` | alias `#nativephp` → `vendor/nativephp/mobile/resources/dist/native.js` |

### Native — iOS

| קובץ | תפקיד |
|------|--------|
| `packages/kalfa/secure-storage/resources/ios/Sources/SecureStorageFunctions.swift` | Keychain Get/Set/Delete |
| `nativephp/ios/NativePHP/Bridge/BridgeFunctionRegistration.swift` | רישום bridge functions (כולל plugin functions) |
| `nativephp/ios/NativePHP/Bridge/Plugins/PluginBridgeFunctionRegistration.swift` | **auto-generated** — נוצר בזמן build ע"י `native:run ios` לאחר `native:plugin:register` |

### Package

| קובץ | תפקיד |
|------|--------|
| `packages/kalfa/secure-storage/nativephp.json` | manifest: bridge_functions, platforms, hooks |
| `packages/kalfa/secure-storage/composer.json` | type: nativephp-plugin, autoload, service provider |
| `packages/kalfa/secure-storage/src/SecureStorageServiceProvider.php` | registers singleton |
| `packages/kalfa/secure-storage/src/Commands/CopyAssetsCommand.php` | copy_assets hook |

---

## 4. Bridge Call Path

```
JS: window.NativePHPMobile.secureStorage.get(key)
        ↓
native.js: BridgeCall('SecureStorage.Get', { key }) → POST /_native/api/call
        ↓
NativeCallController::__invoke()
    → nativephp_can('SecureStorage.Get') → true
    → nativephp_call('SecureStorage.Get', '{"key":"..."}')
        ↓
NativePHPCall() [C export] → BridgeFunctionRegistry.shared → SecureStorageFunctions.Get
        ↓
iOS Keychain: SecItemCopyMatching()
        ↓
{ "value": "1|abc123..." } → JSON → PHP → JS

PHP (server-side): SecureStorage::get(key)
    → nativephp_call('SecureStorage.Get', ...) [PHP function, C-linked]
    → same path ↑
```

---

## 5. `kalfa/secure-storage` — ניתוח מלא

### ✅ מה בנוי נכון

**iOS Swift (`SecureStorageFunctions.swift`)**
- `keychainService = Bundle.main.bundleIdentifier` — מבודד לאפליקציה
- `Set`: pattern של delete-then-add — מטפל ב-update נכון
- `Set(value: nil)` → treat as delete
- `Delete` — idempotent (`errSecItemNotFound` = success)
- `Get` לא מצא → מחזיר `{"value": ""}` — PHP מטפל בזה כ-null
- `kSecAttrAccessibleWhenUnlockedThisDeviceOnly` — אבטחה מקסימלית
- `BridgeResponse.success/error` בפורמט הנכון

**`nativephp.json`** — מבנה תקין עם מיפוי מדויק:
```json
{ "name": "SecureStorage.Get", "ios": "SecureStorageFunctions.Get", "android": "..." }
```

**`composer.json`** — `type: "nativephp-plugin"` + `extra.nativephp.manifest` ✅

### 🔴 בעיות קריטיות

**1. Android — stub בלבד, לא מומש**
```kotlin
// SecureStorageFunctions.kt מכיל:
object Kalfa\SecureStorageFunctions {  // ← Kotlin syntax error! backslash לא חוקי
    class Execute(...)    // ← template אוטומטי
    class GetStatus(...)  // ← template אוטומטי
}
// חסרים לחלוטין: Get, Set, Delete
```
`nativephp.json` מצהיר על `SecureStorageFunctions.Get/Set/Delete` אבל ב-Kotlin הם לא קיימים.

**2. JavaScript — שם שגוי, exports לא תואמים**
```js
// קובץ: kalfa\SecureStorage.js (backslash בשם — לא חוקי ב-Unix)
export async function execute(options = {}) {}   // ← לא קשור ל-SecureStorage
export async function getStatus() {}             // ← לא קשור ל-SecureStorage
// חסרים: get(), set(), delete()
```
הקובץ הוא template שלא עודכן. בפועל `mobile-shell.js` משתמש ב-`SecureStorage` מ-`native.js` — לא מהחבילה הזו.

**3. `PluginTest.php` — בודק נתיבים שגויים**
```php
// הטסט מצפה:
'resources/android/Kalfa\SecureStorageFunctions.kt'
'resources/ios/Kalfa\SecureStorageFunctions.swift'

// קיים בפועל:
'resources/android/SecureStorageFunctions.kt'
'resources/ios/Sources/SecureStorageFunctions.swift'
```

**4. PHP `SecureStorage.php` ריק**
```php
class SecureStorage {}  // stub — Facade לא שימושי
```
הקריאות עוברות דרך `Native\Mobile\Facades\SecureStorage`, לא דרך `Kalfa\SecureStorage`.

### ⚠️ תצפיות

| נושא | מצב |
|------|-----|
| Keychain namespace collision עם APP_KEY | בטוח — account שונה (`APP_KEY` vs user key) |
| Bridge registration | עדיין לא בוצע — `PluginBridgeFunctionRegistration.swift` ריק |
| `CopyAssetsCommand` | stub — לא מעתיק כלום |

---

## 6. מה נדרש להשלמה

```bash
# 1. וידוא ש-composer מכיר את החבילה (path repository כבר מוגדר ב-composer.json)
composer require kalfa/secure-storage

# 2. רישום ה-plugin ב-NativeServiceProvider — מוסיף את ServiceProvider למערך plugins()
php artisan native:plugin:register kalfa/secure-storage

# 3. בדיקה שה-plugin רשום
php artisan native:plugin:list --all

# 4. אימות ה-manifest
php artisan native:plugin:validate packages/kalfa/secure-storage

# 5. בנייה — מייצר PluginBridgeFunctionRegistration.swift ומקמפל את ה-Swift
php artisan native:run ios
```

> ⚠️ כאשר משנים native code (Swift/Kotlin) או nativephp.json, יש להריץ:
> ```bash
> php artisan native:install --force  # rebuild native projects from scratch
> php artisan native:run ios
> ```

הפקודה `native:plugin:register` יוצרת ב-`app/Providers/NativeServiceProvider.php`:
```php
public function plugins(): array {
    return [
        \Kalfa\SecureStorage\SecureStorageServiceProvider::class,
    ];
}
```

בזמן `native:run ios`, NativePHP מייצר אוטומטית את `PluginBridgeFunctionRegistration.swift`:
```swift
// auto-generated
registry.register("SecureStorage.Get",    function: SecureStorageFunctions.Get())
registry.register("SecureStorage.Set",    function: SecureStorageFunctions.Set())
registry.register("SecureStorage.Delete", function: SecureStorageFunctions.Delete())
```

### תיקונים נדרשים בחבילה עצמה

| פריט | תיקון |
|------|-------|
| `SecureStorageFunctions.kt` | מימוש `Get`, `Set`, `Delete` עם Android `EncryptedSharedPreferences` / Keystore |
| `kalfa\SecureStorage.js` | שינוי שם הקובץ, עדכון exports ל-`get/set/delete` |
| `PluginTest.php` | תיקון נתיבי קבצים — `Sources/` + ללא namespace prefix |
| `README.md` | עדכון דוגמאות קוד לממשק האמיתי |

---

## 7. Tests קיימים

| קובץ | כיסוי |
|------|-------|
| `tests/Feature/MobileLoginTest.php` | login success, wrong password, disabled user |
| `tests/Feature/MobileSecureStorageSessionTest.php` | show/store/destroy + error cases |
| `tests/Feature/MobileEntryRouteTest.php` | route accessibility, state config exposure |
| `tests/Feature/MobileBootstrapTest.php` | bootstrap payload structure |
| `packages/kalfa/secure-storage/tests/PluginTest.php` | manifest, native files, PHP classes (Pest) |

```bash
# הרצת טסטים
php artisan test --compact tests/Feature/MobileLoginTest.php tests/Feature/MobileSecureStorageSessionTest.php
# → 9 passed (54 assertions) ✅
```
