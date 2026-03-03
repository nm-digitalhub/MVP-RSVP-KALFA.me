# RSVP + סידור שולחנות — MVP Phase 1 (תיעוד יישום)

## סקירה

יישום Laravel ל־MVP Phase 1: מערכת RSVP וסידור שולחנות רב־דיירית (multi-tenant) עם תשלום חד־פעמי לאירוע.  
**ללא** מנויים, חיוב חוזר או מעקב שימוש.

---

## 1. מיגרציות ומסד נתונים

### טבלאות Core

| טבלה | תיאור |
|------|--------|
| `organizations` | ארגון (tenant): name, slug (unique), billing_email, settings (JSON) |
| `organization_users` | pivot user–organization עם role (owner/admin/member), unique(organization_id, user_id) |
| `events` | אירוע: organization_id, name, slug, event_date, venue_name, settings, status, **soft deletes** |
| `guests` | מוזמן: event_id, name, email, phone, group_name, notes, sort_order, **soft deletes** |
| `invitations` | הזמנה: event_id, guest_id (nullable), token (unique), slug (unique), expires_at, status, responded_at |
| `rsvp_responses` | תשובת RSVP: invitation_id, guest_id, response (yes/no/maybe), attendees_count, message, ip, user_agent |
| `event_tables` | שולחן: event_id, name, capacity, sort_order, **soft deletes** |
| `seat_assignments` | שיבוץ: event_id, guest_id, event_table_id, seat_number, unique(event_id, guest_id) |

### טבלאות Billing

| טבלה | תיאור |
|------|--------|
| `plans` | תוכנית: name, slug, type (per_event), limits (JSON), price_cents, billing_interval |
| `events_billing` | חיוב לאירוע: organization_id, event_id, plan_id, amount_cents, currency, status (pending/paid/cancelled), paid_at |
| `payments` | תשלום (polymorphic): organization_id, payable_type, payable_id, amount_cents, currency, status, gateway, gateway_transaction_id (unique), gateway_response (JSON) |
| `billing_webhook_events` | לוג webhooks: source, event_type, payload (JSON), processed_at |

**מיקום מיגרציות:** `database/migrations/2026_03_01_100000_*` עד `2026_03_01_100011_*`.

---

## 2. מודלים ויחסים

| מודל | יחסים עיקריים |
|------|----------------|
| `Organization` | hasMany Event, EventBilling, Payment; belongsToMany User (via organization_users) |
| `User` | belongsToMany Organization (via organization_users) |
| `Event` | belongsTo Organization; hasMany Guest, Invitation, EventTable, SeatAssignment; hasOne EventBilling |
| `Guest` | belongsTo Event; hasOne Invitation, SeatAssignment; hasMany RsvpResponse |
| `Invitation` | belongsTo Event, Guest; hasMany RsvpResponse |
| `EventTable` | belongsTo Event; hasMany SeatAssignment |
| `SeatAssignment` | belongsTo Event, Guest, EventTable |
| `EventBilling` | belongsTo Organization, Event, Plan; morphMany Payment |
| `Payment` | belongsTo Organization; morphTo payable (EventBilling) |
| `Plan` | hasMany EventBilling |

---

## 3. Enums

| Enum | ערכים |
|------|--------|
| `EventStatus` | draft, pending_payment, active, locked, archived, cancelled |
| `PaymentStatus` | pending, succeeded, failed, refunded, cancelled |
| `EventBillingStatus` | pending, paid, cancelled |
| `InvitationStatus` | pending, sent, opened, responded, expired |
| `OrganizationUserRole` | owner, admin, member |
| `RsvpResponseType` | yes, no, maybe |

**מיקום:** `app/Enums/`.

---

## 4. שכבת Billing

### Interface: `App\Contracts\PaymentGatewayInterface`

- `createOneTimePayment(int $organizationId, int $amount, array $metadata): array` — מחזיר redirect_url / client_secret / transaction_id.
- `handleWebhook(array $payload, string $signature): void` — מטפל ב־webhook ומעדכן סטטוס תשלום.

### Service: `App\Services\BillingService`

- `initiateEventPayment(Event $event, Plan $plan): array` — מעבר אירוע מ־draft ל־pending_payment, יצירת EventBilling + Payment, קריאה ל־gateway.
- `markPaymentSucceeded(Payment $payment): void` — עדכון תשלום ו־EventBilling ל־paid, מעבר אירוע ל־active.
- `markPaymentFailed(Payment $payment): void` — סימון תשלום כ־failed.

**כלל:** אירוע עובר ל־active **רק** דרך `markPaymentSucceeded` (בדרך כלל מקריאת webhook).

### Gateways

- **Stub:** `App\Services\StubPaymentGateway` — מימוש לפיתוח (ללא חיוב אמיתי). ברירת מחדל כאשר `BILLING_GATEWAY=stub` או לא מוגדר.
- **SUMIT:** `App\Services\SumitPaymentGateway` — אדפטר דק ל־`officeguy/laravel-sumit-gateway` (מצב headless/API בלבד, בלי UI/ Filament של החבילה). רישום: `AppServiceProvider` לפי `config('billing.default_gateway')` — כאשר `sumit` נבחר מחובר `SumitPaymentGateway`.  
  **תיעוד:** [sumit-integration.md](sumit-integration.md).

---

## 5. API Controllers

| Controller | פעולות | הערות |
|------------|--------|--------|
| `OrganizationController` | show, update | לפי Policy |
| `EventController` | index, store, show, update, destroy | CRUD + סינון status |
| `GuestController` | index, store, show, update, destroy | תחת event |
| `EventTableController` | index, store, show, update, destroy | טבלאות אירוע |
| `SeatAssignmentController` | index, update (bulk) | PUT לעדכון מרובה |
| `InvitationController` | index, store, send | send = סימון כ־sent (ללא שליחת מייל ב־MVP) |
| `PublicRsvpController` | showBySlug, storeResponse | ציבורי, ללא auth |
| `CheckoutController` | initiate | מפעיל BillingService.initiateEventPayment |
| `WebhookController` | handle | קבלת webhook מספק סליקה, אידמפוטנטיות |

**אימות:** `auth:sanctum` על כל ה־API מלבד RSVP ציבורי ו־webhooks.  
**Validation:** Form Requests ב־`App\Http\Requests\Api\`.

---

## 6. הרשאות (Policies)

| Policy | כללים |
|--------|--------|
| `OrganizationPolicy` | view — שייך לארגון; update, initiateBilling — owner או admin בלבד |
| `EventPolicy` | viewAny, view, create, update, delete — משתמש שייך לארגון של האירוע; initiatePayment — owner/admin |
| `GuestPolicy` | viewAny, view, create, update, delete — משתמש שייך לארגון של האירוע (דרך event) |

---

## 7. אידמפוטנטיות ב־Webhook

- בדיקה: אם קיים `Payment` עם אותו `gateway_transaction_id` ובסטטוס succeeded/failed — מחזירים 200 ללא עיבוד חוזר.
- שמירת כל webhook ב־`billing_webhook_events` (source, event_type, payload, processed_at).
- עדכון `processed_at` לאחר עיבוד מוצלח.

---

## 8. Routes (API)

- **Base path:** `/api` (ברירת מחדל Laravel).
- **ארגונים:** `GET/PATCH /api/organizations/{organization}`.
- **אירועים:** `GET/POST /api/organizations/{organizationId}/events`, `GET/PATCH/DELETE /api/organizations/{organizationId}/events/{event}`.
- **מוזמנים:** `GET/POST .../events/{event}/guests`, `GET/PATCH/DELETE .../guests/{guest}`.
- **שולחנות:** `GET/POST .../events/{event}/event-tables`, `GET/PATCH/DELETE .../event-tables/{eventTable}`.
- **שיבוץ:** `GET .../events/{event}/seat-assignments`, `PUT .../events/{event}/seat-assignments`.
- **הזמנות:** `GET/POST .../events/{event}/invitations`, `POST .../invitations/{invitation}/send`.
- **תשלום:** `POST .../events/{event}/checkout` (body: plan_id).
- **RSVP ציבורי:** `GET /api/rsvp/{slug}`, `POST /api/rsvp/{slug}/responses`.
- **Webhook:** `POST /api/webhooks/{gateway}`.

---

## 9. מבנה קבצים שנוצרו

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Enums/
│   ├── EventBillingStatus.php
│   ├── EventStatus.php
│   ├── InvitationStatus.php
│   ├── OrganizationUserRole.php
│   ├── PaymentStatus.php
│   └── RsvpResponseType.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── CheckoutController.php
│   │   ├── EventController.php
│   │   ├── EventTableController.php
│   │   ├── GuestController.php
│   │   ├── InvitationController.php
│   │   ├── OrganizationController.php
│   │   ├── PublicRsvpController.php
│   │   ├── SeatAssignmentController.php
│   │   └── WebhookController.php
│   └── Requests/Api/
│       ├── InitiateCheckoutRequest.php
│       ├── StoreEventRequest.php
│       ├── StoreGuestRequest.php
│       ├── StoreRsvpResponseRequest.php
│       └── UpdateEventRequest.php
├── Models/
│   ├── BillingWebhookEvent.php
│   ├── Event.php
│   ├── EventBilling.php
│   ├── EventTable.php
│   ├── Guest.php
│   ├── Invitation.php
│   ├── Organization.php
│   ├── OrganizationUser.php
│   ├── Payment.php
│   ├── Plan.php
│   ├── RsvpResponse.php
│   └── SeatAssignment.php
├── Policies/
│   ├── EventPolicy.php
│   ├── GuestPolicy.php
│   └── OrganizationPolicy.php
├── Providers/
│   └── AppServiceProvider.php  (עודכן: binding Gateway)
└── Services/
    ├── BillingService.php
    └── StubPaymentGateway.php

config/
└── billing.php

database/migrations/
└── 2026_03_01_100000_* ... 2026_03_01_100011_*

routes/
└── api.php
```

---

## 10. הרצה

1. **מסד נתונים:** ב־`.env` מוגדר מסד PostgreSQL נפרד (`kalfa_rsvp`). פירוט מלא: **[docs/env-database.md](env-database.md)** — מה להגדיר, שרת מרוחק, והחלפת סיסמה.
2. **מיגרציות:** רק אחרי ש־`.env` מצביע על DB החדש — `php artisan migrate`.
3. **אימות:** התקנת Laravel Sanctum אם טרם הותקן; שימוש ב־`auth:sanctum` ל־API.
4. **Gateway:** החלפת `StubPaymentGateway` במימוש אמיתי ורישום ב־`AppServiceProvider` כשמוכנים לחבר ספק סליקה.

**ביקורת טכנית והקשחה (Production Readiness):** [hardening-and-production-readiness.md](hardening-and-production-readiness.md) — אימות UNIQUE/FKs, search_path, Sanctum, throttle, BillingService ב־transaction, PlanSeeder, בדיקת Idempotency ו־Billing E2E.

---

## 11. ביצוע בפועל (Execution Log)

### תנאי מקדם

- **תלויות PHP:** הרצת `composer install` (או `composer update`) בתיקיית `httpdocs` — נדרש לפני `php artisan` כלשהו.
- **מסד נתונים:** `.env` מוגדר ל־PostgreSQL עם `DB_DATABASE=kalfa_rsvp` (ראה [env-database.md](env-database.md)).

### מיגרציות שתלויות ב־OfficeGuy

במסד `kalfa_rsvp` אין טבלאות OfficeGuy (`officeguy_transactions`, `officeguy_documents` וכו'). המיגרציות הבאות עודכנו כך ש**ידלגו** אם הטבלה המבוקשת לא קיימת:

| קובץ | התנאי לדילוג |
|------|----------------|
| `2025_01_01_000008_create_webhook_events_table.php` | `!Schema::hasTable('officeguy_transactions')` |
| `2025_01_01_000009_create_sumit_incoming_webhooks_table.php` | `!Schema::hasTable('officeguy_transactions')` |
| `2025_12_26_000001_add_transaction_linking_fields_to_officeguy_transactions.php` | `!Schema::hasTable('officeguy_transactions')` |

כך ניתן להריץ `php artisan migrate` על `kalfa_rsvp` בלי ליצור טבלאות OfficeGuy או להיכשל עליהן.

### צעדים שבוצעו והצליחו

1. **`composer install --no-interaction`** — התקנת תלויות (Laravel 12, Sanctum, Livewire וכו').
2. **`php artisan migrate --pretend`** — אימות חיבור ל־DB והצגת ה־SQL ללא ביצוע.
3. **תיקון מיגרציות OfficeGuy** — הוספת תנאי דילוג כמתואר למעלה.
4. **`php artisan migrate --force`** — הרצת כל המיגרציות; הושלמה בהצלחה (כולל טבלאות RSVP 2026_03_01_*).
5. **`php artisan route:list --path=api`** — אימות רישום נתיבי API (ארגונים, אירועים, מוזמנים, שולחנות, שיבוץ, הזמנות, checkout, RSVP ציבורי, webhooks).
6. **`php artisan config:clear`** ו־**`php artisan cache:clear`** — ניקוי cache.

### אימות מהיר לאחר עדכון

```bash
cd /var/www/vhosts/kalfa.me/httpdocs
php artisan migrate --pretend   # בדיקת חיבור ו־SQL
php artisan route:list --path=api
```

---

## 12. Production Hardening Phase

בשלב ההקשחה (ללא פיצ'רים חדשים, ללא שינוי לוגיקה עסקית) בוצעו:

- **אינטגריטי DB:** אימות `events.slug` ייחודי per-organization; אימות FKs — guests/seat_assignments cascade, events_billing ו־payments עם restrict (שימור היסטוריה). מיגרציה חדשה: `payments.organization_id` מ־cascade ל־restrict.
- **תשלומים (Billing):** BillingService עטוף ב־`DB::transaction` ב־initiateEventPayment, markPaymentSucceeded, markPaymentFailed. מעבר סטטוס אירוע רק ב־BillingService (draft → pending_payment → active רק דרך markPaymentSucceeded).
- **אידמפוטנטיות Webhook:** UNIQUE על `payments.gateway_transaction_id`; בדיקה לפני עיבוד; לוג ב־billing_webhook_events; `processed_at` רק אחרי הצלחה.
- **Rate limiting:** הגבלת קצב מוגדרת ב־`AppServiceProvider` (rsvp_show, rsvp_submit, webhooks) ומופעלת על נתיבי RSVP ציבורי ו־webhooks.
- **הרשאות:** CheckoutController קורא ל־`authorize('initiatePayment', $event)`; כל ה־controllers משתמשים ב־policies; אין דליפת נתונים בין ארגונים.
- **PlanSeeder:** תוכנית אחת per_event (מינימלית, ללא לוגיקת מנוי).

דוח מלא: **[hardening-phase-report.md](hardening-phase-report.md)**.

---

## 13. SUMIT Integration (Headless)

אינטגרציית ספק הסליקה SUMIT באמצעות החבילה `officeguy/laravel-sumit-gateway` במצב **headless**: משתמשים רק בשירותי החבילה ליצירת תשלום (redirect) וללא UI/Filament/נתיבי checkout של החבילה.

- **החלפת gateway:** `BILLING_GATEWAY=stub|sumit` ב־`.env`; רישום דינמי ב־`AppServiceProvider`.
- **אדפטר:** `App\Services\SumitPaymentGateway` — מממש `PaymentGatewayInterface`, קורא ל־`PaymentService::processCharge` (redirect) ומנרמל webhook; מעדכן סטטוס רק דרך `BillingService`.
- **Payable:** `App\Services\Sumit\EventBillingPayable` — מממש את ה־Contract של החבילה עבור `EventBilling` (סכום, פריט שורה אחד, פרטי ארגון).
- **Webhook:** נשאר הנתיב שלנו `POST /api/webhooks/sumit`; אידמפוטנטיות ו־`billing_webhook_events` ללא שינוי.
- **תיעוד מפורט:** [sumit-integration.md](sumit-integration.md) — מחלקות החבילה בשימוש, קונפיגורציה, זרימת checkout ו־webhook.

---

*תיעוד זה מתאר את היישום הנוכחי של MVP Phase 1. שינויים עתידיים (מנויים, מגבלות שימוש) יתועדו בנפרד.*
