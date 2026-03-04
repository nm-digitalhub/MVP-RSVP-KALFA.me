# Panel: Read-Only → Full Management — Gap Analysis

ניתוח הפאנל הקיים מול מסמכי התיעוד ([PANEL_UI_STRUCTURE.md](PANEL_UI_STRUCTURE.md), [PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md](PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md), [ACCOUNT_ENTITLEMENTS_README.md](ACCOUNT_ENTITLEMENTS_README.md), [DB_SCHEMA_AUDIT.md](DB_SCHEMA_AUDIT.md)) — מה מוגדר כ־Read-Only ומה נדרש כדי להפוך את המערכת לפאנל ניהול אמיתי.

---

## 1. סיכום מצב נוכחי

### 1.1 פאנל Tenant (ארגון פעיל)

| אזור | מצב נוכחי | ראיות |
|------|------------|--------|
| **Dashboard** | קריאה בלבד: כרטיסי מדדים + טבלת אירועים + קישור "View" לאירוע | `app/Livewire/Dashboard.php`, `resources/views/livewire/dashboard.blade.php` |
| **Dashboard (controller)** | קריאה בלבד: רשימת אירועים בלי קישור לדף אירוע | `app/Http/Controllers/Dashboard/DashboardController.php` — "read-only"; `resources/views/dashboard/index.blade.php` — אין עמודה עם לינק |
| **Event show** | קריאה בלבד: שם, תאריך, מקום, סטטוס, מספר אורחים/שולחנות. אין עריכה, מחיקה, ניהול אורחים, שולחנות, הזמנות, תשלום | `app/Http/Controllers/Dashboard/EventController.php` — רק `show()`; `resources/views/dashboard/events/show.blade.php` |
| **Organizations** | ניהול מלא: רשימה, יצירה, מעבר ארגון | Livewire `Organizations\Index`, `Create`; `OrganizationSwitchController` |
| **Profile** | ניהול מלא: עדכון פרופיל, סיסמה, מחיקת משתמש | Livewire profile forms |
| **Billing & Entitlements** | חלקי: Account — יצירה + תצוגה; Entitlements — CRUD; Usage ו־Billing intents — קריאה בלבד | `app/Livewire/Billing/*`; UsageIndex, BillingIntentsIndex ללא פעולות עריכה |

### 1.2 פאנל System Admin

| אזור | מצב נוכחי | ראיות |
|------|------------|--------|
| **System Dashboard** | קריאה בלבד: KPIs, Health, Billing (stub), רשימות אחרונות | `app/Livewire/System/Dashboard.php`; `SystemBillingService` — stub |
| **System Organizations** | ניהול: רשימה, דף ארגון, השעיה/הפעלה, העברת בעלות, מחיקה, Impersonation | `System\Organizations\Index`, `Show`; פעולות עם אימות סיסמה |
| **System Users** | ניהול: רשימה, דף משתמש, toggle מערכת (admin) | `System\Users\Index`, `Show` |
| **System Accounts** | ניהול חלקי: רשימה, דף Account, צירוף/ניתוק ארגון; Entitlements/Usage/Intents — תצוגה בלבד | `System\Accounts\Index`, `Show` — אין עריכת Account (name, sumit_customer_id), אין ניהול Products |

### 1.3 API לעומת פאנל

כל פעולות הניהול על אירועים, אורחים, שולחנות, הזמנות ותשלום קיימות **רק ב־API** (`routes/api.php`). הפאנל (web) לא חושף אותן.

| יכולת | API | פאנל (Web) |
|--------|-----|-------------|
| אירועים: יצירה, עריכה, מחיקה | ✅ | ❌ |
| אורחים: רשימה, יצירה, עריכה, מחיקה, ייבוא | ✅ | ❌ |
| שולחנות אירוע: רשימה, יצירה, עריכה, מחיקה | ✅ | ❌ |
| שיבוץ מושבים: קריאה, עדכון (bulk) | ✅ | ❌ |
| הזמנות: רשימה, יצירה, שליחה | ✅ | ❌ |
| Checkout: initiate (תשלום) | ✅ | יש דף tokenize לפי URL; אין כפתור "שלם" מדף האירוע |
| ארגון: עדכון (PATCH) | ✅ | ❌ (רק יצירה + מעבר) |

---

## 2. פערים להשלמת פאנל ניהול אמיתי

### 2.1 Tenant — אירועים ואורחים (קריטי)

כדי שהפאנל יהיה ניהול אמיתי ולא רק תצוגה:

1. **אירועים**
   - **יצירת אירוע** — טופס/מודל (שם, תאריך, מקום, וכו') → קריאה ל־API או ל־Controller שמבצע את אותה לוגיקה כמו `EventController::store`.
   - **עריכת אירוע** — דף/מודל עריכה (שדות כמו ב־API) → עדכון כמו `EventController::update`.
   - **מחיקת אירוע** — כפתור עם אימות → כמו `EventController::destroy`.
   - **ניווט**: כפתור "Create event" ב־Dashboard / ברשימת האירועים; כפתור "Edit" ו־"Delete" בדף האירוע.

2. **דף אירוע (Event show) — הרחבה**
   - **אורחים**: רשימת אורחים (עם חיפוש/סינון אם רלוונטי), הוספה, עריכה, מחיקה, **ייבוא** (קריאה ל־`guests.import`).
   - **שולחנות**: רשימת שולחנות/אזורים, הוספה, עריכה, מחיקה.
   - **שיבוץ מושבים**: תצוגה + עריכה (drag-and-drop או טבלה) — קריאה ל־`seat-assignments.update`.
   - **הזמנות**: רשימת הזמנות, יצירה, שליחה (לפי API).
   - **תשלום**: כפתור "המשך לתשלום" / "שלם עכשיו" שמפנה ל־`checkout.tokenize` עם ה־organization וה־event הנוכחיים (או יוזם checkout דרך API ואז redirect ל־tokenize/status). הצגת סטטוס תשלום (EventBilling, Payment) אם קיים.

3. **Dashboard (controller)**
   - להוסיף עמודה "פעולות" עם קישור ל־`dashboard.events.show` (כמו ב־Livewire dashboard) כדי לאחד חוויית הניווט.

### 2.2 Tenant — Billing & Entitlements

- **Account**: כבר ניהול (יצירה + תצוגה). אופציונלי: עריכת שדות (name, sumit_customer_id) אם רוצים שהלקוח ינהל אותם מהפאנל.
- **Usage**: נשאר read-only אלא אם מוגדר צורך לעדכן/לאפס usage ידנית.
- **Billing intents**: נשאר read-only אלא אם מוסיפים זרימת "יצירת intent" מהפאנל.

### 2.3 System Admin

- **System Dashboard — Billing**: כרגע נתונים מ־`SystemBillingService` (stub). להשלים חיבור ל־OfficeGuy/SUMIT או למקור אמת אחר כדי להציג MRR, מנויים, churn אמיתיים (או להשאיר כ־placeholder עד אינטגרציה).
- **System Organizations — Show**: אופציונלי — הצגת Account צמוד (אם יש `account_id`), קישור ל־System Accounts, וסטטוס תשלום/מנוי אם קיים.
- **System Accounts**:
  - עריכת Account: name, type, owner_user_id, sumit_customer_id (אם רוצים ניהול מלא מצד מערכת).
  - ניהול Products ו־ProductEntitlements (קטלוג מוצרים ו־feature_key לכל מוצר) — אם רוצים להגדיר חבילות/תכונות מהמערכת.
- **Plans**: אין כיום UI לניהול תוכניות תמחור (`plans`). אם נדרש להגדיר/לערוך plans מהמערכת — להוסיף מסלול System (או Tenant אם רלוונטי) ל־CRUD של Plans.

### 2.4 ארגון (Tenant)

- **עדכון ארגון**: ב־API יש `PATCH organizations/{organization}`. בפאנל אין עדכון (שם, billing_email, settings). להוסיף דף/מודל "Settings" או "Edit organization" שמבצע עדכון (דרך API או Controller).

### 2.5 Checkout ותשלום

- **גישה מהפאנל**: וודא שיש נקודת כניסה ברורה מתצוגת האירוע (למשל כפתור "שלם לאירוע") ל־`checkout.tokenize` (או ל־initiate + redirect), ו־`checkout.status` לאחר תשלום.
- **תצוגת סטטוס**: בדף האירוע — הצגת סטטוס EventBilling (Pending/Paid/Failed) וקישור ל־checkout status אם רלוונטי.

---

## 3. סדר עדיפויות מומלץ (ליישום)

| עדיפות | רכיב | תיאור קצר |
|--------|------|------------|
| **P0** | אירועים — יצירה, עריכה, מחיקה | ללא זה הפאנל לא "מנהל" אירועים |
| **P0** | דף אירוע — אורחים (רשימה, הוספה, עריכה, מחיקה, ייבוא) | ליבת RSVP |
| **P0** | דף אירוע — כניסה לתשלום (checkout) + סטטוס תשלום | סגירת מעגל אירוע → תשלום |
| **P1** | דף אירוע — שולחנות (CRUD) | נדרש ל־seating |
| **P1** | דף אירוע — הזמנות (רשימה, יצירה, שליחה) | השלמת זרימת ההזמנות |
| **P1** | דף אירוע — שיבוץ מושבים (תצוגה + עדכון) | השלמת seating |
| **P2** | עדכון ארגון (Settings) | שם, billing_email, settings |
| **P2** | Dashboard (controller) — קישור "View" לאירוע | עקביות עם Livewire dashboard |
| **P2** | System — עריכת Account (שדות בסיס) | אם נדרש ניהול Account ממערכת |
| **P3** | System — Billing אמיתי (חיבור ל־OfficeGuy/נתונים אמיתיים) | תלוי באינטגרציה |
| **P3** | System — ניהול Plans / Products | אם רוצים להגדיר חבילות מהפאנל |

---

## 4. תיעוד ומקורות

- **מבנה פאנל**: [PANEL_UI_STRUCTURE.md](PANEL_UI_STRUCTURE.md)
- **חיבור פאנל ↔ Account ו־Entitlements**: [PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md](PANEL_TO_ACCOUNT_ENTITLEMENTS_CONNECTION.md)
- **תשתית Account + Entitlements**: [ACCOUNT_ENTITLEMENTS_README.md](ACCOUNT_ENTITLEMENTS_README.md)
- **סכמת DB**: [DB_SCHEMA_AUDIT.md](DB_SCHEMA_AUDIT.md)
- **API**: `routes/api.php` — EventController, GuestController, EventTableController, SeatAssignmentController, InvitationController, CheckoutController
- **Web**: `routes/web.php` — tenant routes תחת `ensure.organization`, system תחת `system.admin`

---

## 5. סיכום

- **פאנל Tenant** כיום: אירועים ו־Event show הם **read-only**; ניהול מלא קיים רק לארגונים ולפרופיל, ו־Billing/Entitlements חלקי (Account + Entitlements CRUD, Usage/Intents read-only).
- **הפער העיקרי**: כל ניהול **אירועים, אורחים, שולחנות, הזמנות, שיבוץ ותשלום** קיים ב־API בלבד — יש לחשוף אותו בפאנל (טפסים, כפתורים, דפים) כדי להגיע לפאנל ניהול אמיתי.
- **System**: ניהול ארגונים ומשתמשים ו־Accounts (צירוף/ניתוק ארגון) קיים; חסרים עריכת Account, ניהול Products/Plans (לפי צורך), וחיבור Billing אמיתי ב־Dashboard.
