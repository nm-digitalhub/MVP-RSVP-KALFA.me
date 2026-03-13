# Passkey (WebAuthn) — תיעוד מימוש

## סטאטוס: ✅ מימוש מלא ופעיל בפרודקשן

---

## ארכיטקטורה

```
משתמש לוחץ "כניסה עם מפתח זיהוי"
    ↓
Webpass.assert() → POST /webauthn/login/options (Challenge)
    ↓
הדפדפן מפעיל WebAuthn API → ביומטריה / FaceID / TouchID / Windows Hello
    ↓
המכשיר חותם את ה-Challenge עם Private Key
    ↓
POST /webauthn/login → Laravel מאמת חתימה מול Public Key שמור
    ↓
Auth::login() → redirect לדשבורד
```

---

## קבצים

| קובץ | תפקיד |
|---|---|
| `app/Models/User.php` | `WebAuthnAuthenticatable` contract + `WebAuthnAuthentication` trait |
| `config/auth.php` | driver: `eloquent-webauthn`, `password_fallback: true` |
| `config/webauthn.php` | RP name/id, challenge config |
| `app/Http/Controllers/WebAuthn/WebAuthnLoginController.php` | `options()` + `login()` |
| `app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php` | `options()` + `register()` |
| `routes/web.php` | `WebAuthnRoutes::register()` ללא CSRF |
| `resources/js/passkey-login.js` | Vite entry נפרד לדף login |
| `resources/js/app.js` | `window.Webpass = Webpass` — גלובלי ל-Livewire |
| `app/Livewire/Profile/ManagePasskeys.php` | רשימה + מחיקת Passkeys |
| `resources/views/livewire/profile/manage-passkeys.blade.php` | UI ניהול Passkeys בפרופיל |
| `resources/views/auth/login.blade.php` | כפתור Passkey מחוץ ל-form + @vite(passkey-login.js) |
| `resources/views/profile.blade.php` | כולל @livewire('profile.manage-passkeys') |

---

## נתיבים

```
POST /webauthn/login/options       → webauthn.login.options
POST /webauthn/login               → webauthn.login
POST /webauthn/register/options    → webauthn.register.options
POST /webauthn/register            → webauthn.register
```

---

## .env

```
WEBAUTHN_ID=kalfa.me
WEBAUTHN_NAME=Kalfa
```

> חובה! בלי WEBAUTHN_ID — FaceID / TouchID לא יעבדו.

---

## תיקונים שיושמו

| # | בעיה מקורית | פתרון |
|---|---|---|
| 1 | import() dynamic בתוך script רגיל | resources/js/passkey-login.js כ-Vite entry → @vite(...) |
| 2 | כפתור Passkey בתוך form | הוצא אחרי </form> |
| 3 | Redirect קשיח /dashboard | data-redirect="{{ url()->intended(route('dashboard')) }}" |
| 4 | WEBAUTHN_ID לא הוגדר | נוסף ל-.env |
| 5 | import Webpass ב-@script Livewire | window.Webpass מוזרק מ-app.js |
| 6 | דפדפן לא נתמך — רק disabled | style.display = 'none' |

---

## Build Output

```
passkey-login-[hash].js   0.79 kB  (נטען רק בדף login)
webpass-[hash].js        23.52 kB  (chunk נפרד)
app-[hash].js           698.96 kB
```

---

## תמיכה

- iOS FaceID / TouchID
- Android Biometric
- Windows Hello
- Mac TouchID
- מפתח אבטחה פיזי (FIDO2)
- כמה Passkeys למשתמש (טבלה webauthn_credentials)

> WebAuthn פועל רק על HTTPS (או localhost בפיתוח).
