# מיפוי Login / Logout ו־Auth UI

**עדכון:** 2026-03

---

## 1. איפה מוגדרים Login ו־Logout

### Routes (backend)

| שם Route | קובץ | שימוש |
|----------|------|--------|
| `login` (GET) | `routes/auth.php` (Breeze) | טופס התחברות |
| `login` (POST) | `routes/auth.php` | שליחת טופס |
| `logout` (POST) | `routes/auth.php` | התנתקות |
| `register` (GET/POST) | `routes/auth.php` | הרשמה |

הקבצים נטענים ב־`routes/web.php` דרך `require __DIR__.'/auth.php'`.

### UI – איפה מופיעים בפרויקט

| אלמנט | קובץ | מיקום |
|--------|------|--------|
| **Login** (אורח) | `resources/views/components/dynamic-navbar.blade.php` | דסקטופ: שורה 33; מובייל: שורה 138 |
| **Register** (אורח) | אותו קובץ | דסקטופ: אחרי Login; מובייל: אחרי Login |
| **Logout** (מחובר) | `resources/views/components/dynamic-navbar.blade.php` | דסקטופ: שורות 99–102 (טופס POST); מובייל: שורות 186–192 (טופס בתוך המגירה) |

### Layout – איך הנווט מגיע ל־Dashboard

- **Dashboard:** `resources/views/pages/dashboard.blade.php` → `@extends('layouts.app')`
- **Layout:** `resources/views/layouts/app.blade.php` → כולל `<x-dynamic-navbar location="header" />` (שורה 16)

לכן בכל דף שמשתמש ב־`layouts.app` (כולל Dashboard) מוצג הנווט עם Login (לאורח) או Logout (למשתמש מחובר).

---

## 2. מדוע Logout עלול לא להיראות ב־Dashboard

1. **דסקטופ – הרבה פריטים:** הנווט הדסקטופי (`hidden md:flex items-center gap-6`) מכיל: Dashboard, Events, ארגונים, Profile, Billing, (אם אדמין) System Dashboard/Organizations/Accounts/Users, ו־**Logout**. ב־RTL ובמסכים בינוניים הפריטים עלולים לדחוף את Logout החוצה או להסתיר אותו אם יש `overflow` חוסם.
2. **רק מובייל:** בדסקטופ יש כפתור המבורגר; Logout נמצא **בתוך המגירה** (drawer) תחת "Logout" עם מסגרת אדומה. בדסקטופ Logout אמור להיות בסוף שורת הנווט – אם הוא לא נראה, הסיבה בדרך כלל overflow או רוחב מסך.

---

## 3. שינוי שבוצע – Logout תמיד נגיש (תפריט משתמש)

כדי ש־Logout יהיה תמיד גלוי ב־Dashboard ובכל דף עם נווט (כולל כשמשתמש אדמין עם הרבה פריטים), נוסף **תפריט משתמש** בנווט הדסקטופי:

- **טריגר:** שם המשתמש + חץ (לחיצה פותחת dropdown).
- **בתוך התפריט:** Profile, קו מפריד, Logout (כפתור אדום).

התפריט מופיע בסוף שורת הנווט (לפני המבורגר במובייל). במובייל – Logout נשאר בתחתית המגירה כמו קודם.

---

## 4. קבצים רלוונטיים

| קובץ | תפקיד |
|------|--------|
| `resources/views/components/dynamic-navbar.blade.php` | כל הלינקים והכפתורים: Login, Register, Logout, Profile, Billing, ארגונים, מערכת |
| `resources/views/layouts/app.blade.php` | שימוש ב־`<x-dynamic-navbar />` |
| `resources/views/auth/login.blade.php` | דף התחברות (טופס) |
| `resources/views/auth/register.blade.php` | דף הרשמה |
| `resources/views/auth/verify-email.blade.php` | טופס Logout באימות אימייל |
| `routes/auth.php` | הגדרת routes של login, logout, register וכו' |
