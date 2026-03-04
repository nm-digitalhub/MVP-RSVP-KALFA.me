# Routes and Navbar Mapping

תיעוד מיפוי בין routes של האפליקציה לבין ה-UI (נווט ראשי ודפים).

**קובץ נווט:** `resources/views/components/dynamic-navbar.blade.php`  
**עדכון אחרון:** 2026-03-05

---

## 1. Routes שמוצגים ב-Navbar

### אורח (Guest)

| Route       | תווית ב-Navbar | מיקום        |
|------------|----------------|-------------|
| `home` (/) | לוגו האפליקציה | דסקטופ + מובייל |
| `login`    | Login          | דסקטופ + מובייל |
| `register` | Register       | דסקטופ + מובייל |

### משתמש מחובר (Auth)

| Route                   | תווית ב-Navbar      | מיקום                          |
|-------------------------|---------------------|---------------------------------|
| `dashboard`             | Dashboard           | דסקטופ + מובייל                 |
| `dashboard.events.index`| Events              | דסקטופ + מובייל (נוסף 2026-03-05) |
| ארגון נבחר (dropdown)  | שם הארגון + החלפה  | דסקטופ: dropdown               |
| `dashboard.organization-settings.edit` | Organization settings | דסקטופ: תחת dropdown הארגון; מובייל: כשנבחר ארגון (נוסף 2026-03-05) |
| `organizations.index`   | Manage Organizations| דסקטופ: תחת dropdown; מובייל   |
| `organizations.switch`  | טופס בחירת ארגון   | דסקטופ: dropdown               |
| `profile`               | Profile            | דסקטופ + מובייל                 |
| `billing.account`       | Billing & Entitlements | דסקטופ + מובייל            |
| `logout`                 | Logout             | דסקטופ + מובייל                 |

### אימפרסונציה (כש־session מכיל impersonation)

| Route                     | תווית ב-Navbar   |
|---------------------------|------------------|
| `system.impersonation.exit` | Exit impersonation |

### מנהל מערכת (System Admin)

| Route                        | תווית ב-Navbar     |
|-----------------------------|---------------------|
| `system.dashboard`          | System Dashboard    |
| `system.organizations.index`| System Organizations |
| `system.accounts.index`     | Accounts            |
| `system.users.index`        | System Users        |

---

## 2. Routes שלא ב-Navbar אבל מחוברים ל-UI

נגישים מדפים, כפתורים או טפסים (לא מהנווט הראשי).

| Route / קבוצה | איך מגיעים |
|----------------|-------------|
| `organizations.create` | דף ארגונים: "Create New Organization" / "Create one" |
| `dashboard.events.create` | Dashboard: "Create event" |
| `dashboard.events.show` | Dashboard ו־Events: "View" ליד כל אירוע |
| `dashboard.events.edit` | דף אירוע: "Edit" |
| `dashboard.events.destroy` | דף אירוע: טופס "Delete" |
| `dashboard.events.guests.index` | דף אירוע: כרטיס "Guests" |
| `dashboard.events.tables.index` | דף אירוע: כרטיס "Tables" |
| `dashboard.events.invitations.index` | דף אירוע: כרטיס "Invitations" |
| `dashboard.events.seat-assignments.index` | דף אירוע: כרטיס "Seat assignments" |
| `dashboard.events.store` / `dashboard.events.update` | טפסים (POST/PUT) |
| `dashboard.organization-settings.update` | טופס בדף הגדרות ארגון |
| `billing.entitlements`, `billing.usage`, `billing.intents` | דף Billing (account-overview): לינקים Entitlements, Usage, Billing intents |
| `checkout.tokenize` | דף אירוע: "Proceed to payment" |
| `checkout.status` | redirect אחרי תשלום (לא לינק סטטי ב-Blade) |
| `event.show`, `rsvp.show`, `rsvp.responses.store` | דפים ציבוריים וטופס RSVP (הזמנות/מייל) |
| `system.organizations.show`, `system.users.show`, `system.accounts.show` | מעבר מרשימות מערכת לדף פרט |
| `system.impersonate` | כפתור Impersonate ברשימת ארגונים/דף ארגון מערכת |

---

## 3. Routes של Auth (לא ב-Navbar)

משמשים לזרימת התחברות, אימות ואימייל.

| Route / קבוצה | שימוש |
|----------------|--------|
| `password.request`, `password.email` | שחזור סיסמה (לינק מדף login) |
| `password.reset`, `password.store` | איפוס סיסמה (מייל) |
| `password.confirm` | אישור סיסמה לפעולות רגישות |
| `password.update` | שינוי סיסמה (פרופיל) |
| `verification.notice`, `verification.send`, `verification.verify` | אימות אימייל |

---

## 4. שינויים שבוצעו (2026-03-05)

1. **הוספת "Events" ב-Navbar**  
   - Route: `dashboard.events.index` (`/dashboard/events`).  
   - דסקטופ: לינק "Events" אחרי "Dashboard".  
   - מובייל: לינק "Events" תחת Main.  
   - מצב פעיל: `$isEventsIndex` (routeIs `dashboard.events.index`).

2. **הוספת "Organization settings" ב-Navbar**  
   - Route: `dashboard.organization-settings.edit` (`/organization/settings`).  
   - דסקטופ: תחת dropdown הארגון, מעל "Manage Organizations"; מוצג רק כש־`$currentOrg` קיים.  
   - מובייל: לינק "Organization settings" תחת Current Organization; מוצג רק כש־`$currentOrgMobile` קיים.  
   - מצב פעיל: `$isOrganizationSettings` (routeIs `dashboard.organization-settings.*`).

---

## 5. מקורות

- **Routes:** `routes/web.php`, `routes/api.php` (API לא ממופה לנווט).
- **Navbar:** `resources/views/components/dynamic-navbar.blade.php`.
- **בדיקת לינקים:** חיפוש `route(` ו־`href=` ב־`resources/views/**/*.blade.php`.
