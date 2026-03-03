# Hardening ו־Production Readiness — RSVP MVP

תיעוד ביקורת טכנית, אימותים שבוצעו, והשלב ההנדסי הבא (Hardening + בדיקות).

---

## 1. אימותים שבוצעו (Verification)

### 1.1 UNIQUE על `events.slug`

- **דרישה:** `slug` ייחודי **per organization** (multi-tenant), לא גלובלי.
- **מצב:** במיגרציה `2026_03_01_100002_create_events_table.php`:
  ```php
  $table->unique(['organization_id', 'slug']);
  ```
- **סטטוס:** ✅ תקין.

---

### 1.2 Foreign Keys עם onDelete

| טבלה / FK | דרישה | מימוש נוכחי | סטטוס |
|-----------|--------|-------------|--------|
| `guests.event_id` | CASCADE כשנמחק event | `constrained()->cascadeOnDelete()` | ✅ |
| `seat_assignments.guest_id` | CASCADE כשנמחק guest | `constrained()->cascadeOnDelete()` | ✅ |
| `events_billing.event_id` | **לא** CASCADE — לשמור היסטוריה | `restrictOnDelete()` (מיגרציה נפרדת) | ✅ |

**מיגרציה:** `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php` — מחליפה את ה־FK על `event_id` ל־`ON DELETE RESTRICT`. מחיקת אירוע נכשלת כל עוד קיים רשומת `events_billing` לאותו אירוע.

---

### 1.3 search_path ב־PostgreSQL

- **config:** ב־`config/database.php` חיבור `pgsql` כולל:
  ```php
  'search_path' => 'public',
  ```
- **אימות בשרת:** להריץ ב־psql (או דרך Laravel):
  ```sql
  SHOW search_path;
  ```
  יש לוודא שהסכמה היא `public` (או שהנתיב כולל אותה). אחרת — מיגרציות יעבדו אבל raw queries עלולות להישבר.

---

### 1.4 Sanctum — בדיקה אמיתית

- **התקנה:** `composer require laravel/sanctum` הורצה; מיגרציית `personal_access_tokens` פורסמה והורצה.
- **User:** הוספת `HasApiTokens` ל־`App\Models\User`.
- **בדיקה:**
  ```bash
  php artisan tinker --execute="\$u = App\Models\User::first(); echo \$u ? \$u->createToken('test')->plainTextToken : 'no user';"
  ```
  אם יש משתמש — מתקבל טוקן (או "OK"). אם נכשל — לבדוק ש־Sanctum מותקן ו־User משתמש ב־`HasApiTokens`.

---

### 1.5 OfficeGuy Skip Logic

- המיגרציות שתלויות ב־OfficeGuy מדלגות אם `officeguy_transactions` לא קיימת (מסד `kalfa_rsvp` מבודד).
- **הבהרה:** זה פותר את **ההרצה** על DB נפרד; לא פותר תלות עתידית אם תשתמשו באותה חבילה/DB. כרגע — מבודד ל־kalfa_rsvp.

---

## 2. Hardening שבוצע

### 2.1 Throttle

- **RSVP ציבורי:** `GET/POST /api/rsvp/{slug}` ו־`/api/rsvp/{slug}/responses` — בתוך `throttle:60,1` (60 בקשות לדקה).
- **Webhooks:** `POST /api/webhooks/{gateway}` — `throttle:120,1` (לאפשר retries מספק הסליקה).

### 2.2 BillingService — DB Transaction

- **initiateEventPayment:** כל הלוגיקה (עדכון event, יצירת EventBilling, Payment, קריאה ל־gateway, עדכון payment) בתוך `DB::transaction(...)`.
- **markPaymentSucceeded** ו־**markPaymentFailed:** כל העדכונים בתוך `DB::transaction(...)`.
- **מטרה:** למנוע מצב חלקי (למשל payment succeeded אבל event לא עודכן ל־active).

### 2.3 events_billing — RESTRICT on delete

- מיגרציה נפרדת משנה את ה־FK של `event_id` ל־`restrictOnDelete()` כדי לשמור רשומות היסטוריות.

### 2.4 PlanSeeder

- **קובץ:** `database/seeders/PlanSeeder.php`.
- **תוכן:** תוכנית אחת `per_event` (slug: `per-event-basic`, מחיר לדוגמה 99.00 ILS).
- **הרצה:** `php artisan db:seed --class=Database\\Seeders\\PlanSeeder`.

---

## 3. בדיקת Idempotency (Webhook כפול)

**מטרה:** לוודא ששליחת אותו webhook פעמיים עם אותו `gateway_transaction_id` לא מעבדת פעמיים.

**צעדים:**

1. ליצור אירוע ב־draft, להריץ checkout (initiate) — נוצר Payment עם `gateway_transaction_id` (למשל מהסטאב).
2. לשלוח POST ל־`/api/webhooks/stub` עם payload שמכיל את אותו `transaction_id` / `id` — בפעם הראשונה: 200, העיבוד מתבצע.
3. לשלוח שוב את **אותו** payload — התשובה צריכה להיות 200 עם `{"message":"Already processed"}` בלי לעדכן שוב את ה־Payment/Event.

**איך לבדוק בקוד:** ב־`WebhookController::handle` — בדיקה על `Payment::where('gateway_transaction_id', $transactionId)->whereIn('status', ['succeeded', 'failed'])->exists()` מחזירה true בפעם השנייה ולכן מחזירים 200 ללא עיבוד חוזר.

---

## 4. בדיקת Billing Flow End-to-End (עם Stub)

**מטרה:** לוודא ש־initiate → (סימון תשלום) → event active עובד עקבי.

**צעדים (ידני / Tinker):**

1. **ארגון ומשתמש:** משתמש שמשויך לארגון (owner/admin).
2. **אירוע ב־draft:** ליצור Event עם status draft.
3. **תוכנית:** להריץ `PlanSeeder` אם טרם הור� — קיימת תוכנית עם slug `per-event-basic`.
4. **initiate:**  
   `POST /api/organizations/{orgId}/events/{eventId}/checkout`  
   body: `{"plan_id": <id של התוכנית>}`  
   עם Auth: Bearer token של משתמש מהארגון.
5. **אימות:** Event ב־pending_payment, נוצרו EventBilling ו־Payment (עם gateway_transaction_id מהסטאב).
6. **סימון הצלחה (סטאב):** ב־Tinker או בבדיקה — למצוא את ה־Payment של ה־EventBilling של האירוע:
   ```php
   $billing = EventBilling::where('event_id', $eventId)->latest()->first();
   $payment = $billing->payments()->latest()->first();
   app(\App\Services\BillingService::class)->markPaymentSucceeded($payment);
   ```
7. **אימות:** `Event::find($eventId)->status === 'active'`, `EventBilling` ב־paid, `Payment` ב־succeeded.

**הערה:** עם gateway אמיתי (למשל SUMIT), ה־webhook יפעיל את `markPaymentSucceeded`; עם Stub ניתן לבדוק את הזרימה ידנית כמתואר למעלה.

---

## 5. סטטוס נוכחי ושלב הבא

**כרגע:**

- Infrastructure Ready  
- Database Ready  
- API Layer Ready  
- Billing Stub Ready  
- Sanctum מותקן ומוגדר  
- Throttle על RSVP ו־Webhooks  
- BillingService בעטיפה של transaction  
- events_billing עם RESTRICT למחיקת אירוע  
- PlanSeeder עם תוכנית per_event  

**עדיין לא:**

- Gateway אמיתי (SUMIT)
- Feature Tests אוטומטיים
- Hardening אבטחתי נוסף (לפי צורך)

**המלצה:** לפני חיבור SUMIT — להריץ בדיקת Idempotency (סעיף 3) ובדיקת Billing E2E (סעיף 4) ולוודא שהכל עובר.

---

*עדכון אחרון: ביקורת טכנית, hardening, ותיעוד בדיקות.*  
