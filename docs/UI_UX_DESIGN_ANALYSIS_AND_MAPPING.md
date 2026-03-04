# UI/UX Design — ניתוח ומיפוי (שלב 1: קריאה בלבד)

תאריך: 2026-03-03  
 scope: `app/Livewire/` + `resources/views/` (ללא יוצא מן הכלל)

---

## 1. סיכום מנהלים

- **Livewire components**: 22 קבצי PHP.
- **Blade views**: 105 קבצים (כולל components, layouts, livewire views, pages, auth, errors, vendor pagination).
- **טכנולוגיות UI**: Tailwind CSS v4, Flowbite 4, Alpine.js, Vite 7, RTL (Hebrew).
- **Layout**: `layouts.app` (auth) עם `dir="rtl"`, `layouts.guest` ללא RTL מפורש בחלק מה־auth.
- **עקביות**: רמת עקביות בינונית — יש שני "עולמות" עיצוביים (Breeze-style vs custom gradient/rounded-xl), וחוסר אחידות ב־forms, כפתורים וטבלאות.

---

## 2. מיפוי מלא — Livewire (`app/Livewire/`)

| קובץ | View מקושר | תפקיד UI | Layout | הערות UX |
|------|------------|----------|--------|----------|
| `Dashboard.php` | livewire.dashboard | דשבורד ארגון, כרטיסי KPI, טבלת אירועים | (דרך page) | כרטיסים אחידים, כפתור Create event אינדיגו, טבלה עם status badges |
| `Organizations/Index.php` | livewire.organizations.index | רשימת ארגונים, בחירה/החלפה | (דרך page) | כרטיסי org עם border-2, CTA Create, הודעות success/error |
| `Organizations/Create.php` | livewire.organizations.create | טופס שם ארגון | (דרך page) | form עם x-text-input, כפתורי Create/Cancel |
| `Profile/UpdateProfileInformationForm.php` | livewire.profile.update-profile-information-form | פרופיל + אימייל | profile | x-input-label, x-text-input, x-primary-button, x-action-message |
| `Profile/UpdatePasswordForm.php` | livewire.profile.update-password-form | שינוי סיסמה | profile | אותו סט קומפוננטים |
| `Profile/DeleteUserForm.php` | livewire.profile.delete-user-form | מחיקת חשבון | profile | x-danger-button, x-modal (Alpine open-modal), סיסמה באישור |
| `Dashboard/EventGuests.php` | livewire.dashboard.event-guests | אורחים: טבלה, טופס הוספה/עריכה, ייבוא CSV | event page | showForm, inline form, טבלה text-right (RTL), Edit/Delete links |
| `Dashboard/EventInvitations.php` | livewire.dashboard.event-invitations | הזמנות: טבלה, יצירת הזמנה עם select אורח | event page | select ללא עיצוב מלא, כפתור Mark as sent |
| `Dashboard/EventTables.php` | livewire.dashboard.event-tables | שולחנות: טבלה, טופס name/capacity | event page | דפוס זהה ל־EventGuests |
| `Dashboard/EventSeatAssignments.php` | livewire.dashboard.event-seat-assignments | שיבוץ אורח–שולחן | event page | טבלה + select לכל אורח, כפתור Save assignments |
| `System/Dashboard.php` | livewire.system.dashboard | דשבורד מערכת: KPIs, Health, Billing, רשימות | layouts.app | grid כרטיסים, רשימות Recent Orgs/Users |
| `System/Organizations/Index.php` | livewire.system.organizations.index | רשימת ארגונים מערכת + פילטרים | layouts.app | form פילטרים (search, selects), טבלה עם Suspended/Active badge, Impersonate |
| `System/Organizations/Show.php` | livewire.system.organizations.show | פרט ארגון, כרטיסים, אירועים, Admin actions | layouts.app | כרטיסי סטטיסטיקה, טבלת אירועים, כפתורי סכנה (Suspend, Force delete, Reset), מודל סיסמה |
| `System/Users/Index.php` | livewire.system.users.index | רשימת משתמשים + פילטרים | layouts.app | טבלה, Toggle Admin, עיצוב פשוט (rounded-md inputs) |
| `System/Users/Show.php` | livewire.system.users.show | פרט משתמש, ארגונים, Admin actions | layouts.app | כרטיסי מספרים, רשימת ארגונים, כפתורי Promote/Demote/Disable/Reset, מודל סיסמה |
| `System/Accounts/Index.php` | livewire.system.accounts.index | רשימת חשבונות מערכת + חיפוש | layouts.app | פילטרים מרובי שדות, טבלה |
| `System/Accounts/Show.php` | livewire.system.accounts.show | פרט חשבון: Tabs (Overview, Orgs, Entitlements, Usage, Intents) | layouts.app | Tab bar (border-b-2), טפסים inline, טבלאות |
| `Billing/AccountOverview.php` | livewire.billing.account-overview | סקירת חשבון tenant / Create account | billing page | dl/dd, לינקים ל־Entitlements/Usage/Intents |
| `Billing/EntitlementsIndex.php` | livewire.billing.entitlements-index | CRUD entitlements | billing page | טבלה, טופס Add/Edit inline |
| `Billing/UsageIndex.php` | livewire.billing.usage-index | קריאה בלבד usage + פילטר | billing page | טבלה, שדות filter |
| `Billing/BillingIntentsIndex.php` | livewire.billing.billing-intents-index | קריאה בלבד billing intents | billing page | טבלה בלבד |
| `Actions/Logout.php` | — | Action only (לא view) | — | — |

---

## 3. מיפוי מלא — Views (`resources/views/`)

### 3.1 Layouts

| קובץ | תפקיד | הערות UI/UX |
|------|--------|--------------|
| `layouts/app.blade.php` | Layout ראשי (auth) | `dir="rtl"`, bg-gray-50, navbar, @yield header + content, max-w-7xl, @livewireScripts |
| `layouts/guest.blade.php` | Layout אורח | ללא RTL, min-h-screen, slot בלבד — לא אחיד עם app |

### 3.2 Components (מבנה וסגנון)

| קובץ | שימוש | סגנון / נגישות |
|------|--------|-----------------|
| `components/dynamic-navbar.blade.php` | ניווט ראשי + mobile drawer | כבר שופר: סקשנים, active accent, Logout אדום, overlay, aria-current |
| `components/primary-button.blade.php` | כפתור ראשי | gray-800/dark, rounded-md, focus-visible:ring-indigo-500 — שונה מכפתורי אינדיגו בעמודים |
| `components/secondary-button.blade.php` | כפתור משני | לבן, border-gray-300, rounded-md |
| `components/danger-button.blade.php` | כפתור סכנה | bg-red-600, rounded-md |
| `components/modal.blade.php` | מודל Alpine | x-data show, bg-gray-500/75, maxWidth, focusable, Escape/click outside |
| `components/page-header.blade.php` | כותרת עמוד | text-3xl font-bold, subtitle, border-b |
| `components/text-input.blade.php` | input | border-gray-300, focus:ring-indigo-500, rounded-md, dark |
| `components/input-label.blade.php` | label | text-sm font-medium text-gray-700, rtl:text-end |
| `components/input-error.blade.php` | שגיאות validation | text-red-600, רשימה |
| `components/textarea.blade.php` | textarea | rounded-md, focus:ring |
| `components/action-message.blade.php` | הודעת "נשמר" | Alpine, x-show transition, 2s |
| `components/auth-session-status.blade.php` | סטטוס session | text-green-600 |
| `components/file-upload-modern.blade.php` | העלאת קבצים | Alpine, drag-drop, RTL, touch, progress, קשור ל־KYC API — לא גנרי RSVP |

אייקונים (Heroicons outline):  
`heroicon-o-*.blade.php` — עשרות אייקונים; משמשים בניווט, טבלאות וטפסים.

### 3.3 Livewire views (מקבילים ל־Livewire components)

כל ה־livewire views משתמשים ב:
- טבלאות: `min-w-full divide-y divide-gray-200`, `thead bg-gray-50`, `text-right` ב־RTL בחלק מהמקומות.
- כרטיסים: `bg-white rounded-xl shadow-sm border border-gray-200`.
- כפתורים: שילוב של x-primary-button/x-secondary-button עם כפתורים inline (לעיתים `bg-indigo-600 rounded-lg`).
- טפסים: x-input-label, x-text-input, x-input-error; בחלק מהמערכת — input/select גולמיים עם `rounded-md` או `rounded-xl`.

### 3.4 Pages (מעטפת + Livewire או תוכן)

| קובץ | תוכן | הערות |
|------|------|--------|
| `pages/dashboard.blade.php` | @livewire:dashboard, page-header | containerWidth max-w-7xl |
| `pages/organizations/index.blade.php` | @livewire:organizations.index, page-header | max-w-3xl |
| `pages/organizations/create.blade.php` | טופס create org | — |
| `profile.blade.php` | 3 קופסאות: profile, password, delete | space-y-6, rounded-xl |
| `pages/billing/account.blade.php` | Account overview | — |
| `pages/billing/entitlements.blade.php` | Entitlements | — |
| `pages/billing/usage.blade.php` | Usage | — |
| `pages/billing/intents.blade.php` | Billing intents | — |

### 3.5 Dashboard (אירועים — Controllers + Views)

| קובץ | תפקיד | הערות UI |
|------|--------|-----------|
| `dashboard/index.blade.php` | רשימת אירועים (לא Livewire) | טבלה, status badges — דומה ל־livewire.dashboard |
| `dashboard/events/show.blade.php` | פרט אירוע | page-header, סקשנים (סטטוס, תשלום, כרטיסי ניהול), כפתורי Edit/Delete/Proceed to payment |
| `dashboard/events/create.blade.php` | יצירת אירוע | — |
| `dashboard/events/edit.blade.php` | עריכת אירוע | — |
| `dashboard/events/guests.blade.php` | מארח Livewire EventGuests | — |
| `dashboard/events/invitations.blade.php` | מארח EventInvitations | — |
| `dashboard/events/tables.blade.php` | מארח EventTables | — |
| `dashboard/events/seat-assignments.blade.php` | מארח EventSeatAssignments | — |
| `dashboard/organizations/edit.blade.php` | הגדרות ארגון | — |

### 3.6 System (מעטפת)

| קובץ | תוכן |
|------|--------|
| `system/dashboard.blade.php` | Livewire system dashboard |
| `system/users/index.blade.php` | Livewire system users index |
| `system/organizations/index.blade.php` | Livewire system organizations index |

### 3.7 Auth

| קובץ | הערות UI |
|------|----------|
| `auth/login.blade.php` | guest layout, logo, טופס התחברות, לינק להרשמה, טקסט עברי, מקום ל־url.intended (eSIM) |
| `auth/register.blade.php` | — |
| `auth/forgot-password.blade.php` | — |
| `auth/reset-password.blade.php` | — |
| `auth/verify-email.blade.php` | — |
| `auth/confirm-password.blade.php` | — |
| `auth/change-password.blade.php` | — |

### 3.8 Errors

| קובץ | הערות UI |
|------|----------|
| `errors/404.blade.php` | עיצוב עשיר: אנימציות, primary/secondary, חיפוש, קישורים מהירים (eSIM, דומיינים, אחסון), dark — לא תואם לשאר האפליקציה (RSVP) |
| `errors/403.blade.php` | — |
| `errors/500.blade.php` | — |
| `errors/429-payment.blade.php` | — |

### 3.9 ציבורי / Checkout / RSVP

| קובץ | הערות UI |
|------|----------|
| `checkout/status.blade.php` | סטטוס תשלום, כרטיס לבן, טקסט לפי status |
| `checkout/tokenize.blade.php` | tokenization תשלום | — |
| `rsvp/show.blade.php` | טופס RSVP לאורח, select Yes/No/Maybe, כפתור submit |
| `events/show.blade.php` | דף אירוע ציבורי | — |
| `welcome.blade.php` | דף ברירת מחדל Laravel (לוגו, לינקים) — לא מותאם RSVP |

### 3.10 Vendor (Pagination)

נוכחים: `vendor/pagination/*.blade.php` (tailwind, bootstrap-4/5, default, simple-*, semantic-ui). בדרך כלל משתמשים ב־`{{ $items->links() }}` עם תבנית אחת (למשל Tailwind).

---

## 4. דפוסים חוזרים (Patterns)

### 4.1 כפתורים

- **פריימרי (אפליקציה)**: הרבה מקומות משתמשים ב־`bg-indigo-600 hover:bg-indigo-700` + `rounded-lg` או `rounded-xl` + shadow.
- **קומפוננטה**: `x-primary-button` — gray-800/dark, rounded-md (Breeze).
- **פעולות הרסניות**: `text-red-600`, `bg-red-600`, או `x-danger-button`.
- **משני**: `border border-gray-300 bg-white` או `x-secondary-button`.

→ אי־עקביות בין Breeze (gray-800) לבין אינדיגו בעמודים.

### 4.2 טבלאות

- דפוס אחיד: `min-w-full divide-y divide-gray-200`, `thead bg-gray-50`, `text-xs font-medium text-gray-500 uppercase` ב־th.
- בחלק מה־views: `text-right` ל־RTL, באחרים `text-left`.
- Empty state: `colspan`, `text-center text-sm text-gray-500`.

### 4.3 כרטיסים (Cards)

- Tenant/Dashboard: `bg-white rounded-xl shadow-sm border border-gray-200`, `p-4`–`p-6`.
- System: לעיתים `rounded-2xl`, `shadow-lg`, `border-gray-200/70`, `backdrop-blur-sm`.
- KPI: `text-xs`/`text-sm` ל־label, `text-2xl font-semibold` למספר.

### 4.4 טפסים

- פרופיל / ארגונים: `x-input-label`, `x-text-input`, `x-input-error`, `x-primary-button`.
- System / Billing: לעיתים `label` + `input` גולמי עם `rounded-md` או `rounded-xl`.
- מודלים מסוכנים: שדה סיסמה + כפתורי Cancel/Confirm.

### 4.5 ניווט

- Desktop: לינקים עם `min-h-[44px]`, focus ring, dropdown ארגונים.
- Mobile: drawer עם סקשנים (Main, System Administration), active עם border-s-4 + bg-indigo-50, Logout אדום.

### 4.6 סטטוס / Badges

- Event: draft (gray), pending_payment (amber), active (green), cancelled (red), וכו'.
- Organization: Active (green), Suspended (red).
- User: System Admin (indigo), Disabled (red).

---

## 5. בעיות ופערים (ללא שינוי קוד — רשימת תצפיות)

1. **כפתורים**: Breeze primary (gray-800) vs אינדיגו בעמודים — להחליט פלטת פעולה אחת (למשל אינדיגו לפריימרי).
2. **RTL**: `layouts.guest` ללא `dir="rtl"` — auth עלול להיות LTR.
3. **טבלאות**: חוסר אחידות בין `text-left` ל־`text-right` (RTL).
4. **טפסים**: שילוב של x-* components עם input/select גולמיים — עיצוב לא אחיד (rounded-md vs rounded-xl).
5. **404**: תוכן (eSIM, דומיינים, אחסון) ו־primary/secondary לא תואמים לאפליקציית RSVP.
6. **welcome.blade.php**: דף Laravel default — לא מותאם למוצר.
7. **file-upload-modern**: קשור ל־KYC API; לא גנרי ל־RSVP — סיכון בלבול.
8. **organizations/index**: יש כפילות `</ul></div>` (שגיאת HTML אפשרית).
9. **organizations/create**: כפילות `</form>`.
10. **נגישות**: בחלק מהכפתורים/לינקים חסר aria-label או aria-current; מודלים עם role="dialog" קיימים אך לא תמיד עם focus trap.

---

## 6. מפת תלויות (Views ↔ Livewire)

- `pages/dashboard` → `Livewire\Dashboard`.
- `pages/organizations/index` → `Livewire\Organizations\Index`.
- `pages/organizations/create` → `Livewire\Organizations\Create`.
- `profile` → `UpdateProfileInformationForm`, `UpdatePasswordForm`, `DeleteUserForm`.
- `dashboard/events/show` → לא Livewire; לינקים ל־guests, invitations, tables, seat-assignments (כל אחד עמוד עם Livewire).
- `dashboard/events/guests` → `Livewire\Dashboard\EventGuests`.
- `dashboard/events/invitations` → `Livewire\Dashboard\EventInvitations`.
- `dashboard/events/tables` → `Livewire\Dashboard\EventTables`.
- `dashboard/events/seat-assignments` → `Livewire\Dashboard\EventSeatAssignments`.
- `system/*` → System\Dashboard, System\Organizations\*, System\Users\*, System\Accounts\*.
- `pages/billing/*` → Billing\AccountOverview, EntitlementsIndex, UsageIndex, BillingIntentsIndex.

---

## 7. סיכום מספרי

| קטגוריה | מספר קבצים |
|---------|------------|
| Livewire PHP | 22 |
| Blade (סה"כ) | 105 |
| ├─ components | 35 (כולל heroicons) |
| ├─ livewire views | 22 |
| ├─ pages | 8+ |
| ├─ dashboard | 10 |
| ├─ system | 3 |
| ├─ auth | 7 |
| ├─ errors | 4 |
| ├─ checkout/rsvp/events | 4 |
| ├─ layouts | 2 |
| ├─ vendor pagination | 10 |
| └─ אחר | welcome, profile |

---

*מסמך זה הוא שלב 1 — ניתוח ומיפוי בלבד, ללא שינוי קוד.*
