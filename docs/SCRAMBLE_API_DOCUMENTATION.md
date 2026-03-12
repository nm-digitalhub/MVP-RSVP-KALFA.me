# תיעוד API — Scramble

> **גרסת חבילה:** `dedoc/scramble ^0.13.14`  
> **כתובת תיעוד:** [https://kalfa.me/docs/api](https://kalfa.me/docs/api)  
> **קובץ OpenAPI:** [https://kalfa.me/docs/api.json](https://kalfa.me/docs/api.json)

---

## תוכן עניינים

1. [מהי Scramble?](#1-מהי-scramble)
2. [התקנה ודרישות](#2-התקנה-ודרישות)
3. [קונפיגורציה](#3-קונפיגורציה)
4. [אופן ההטמעה בפרויקט](#4-אופן-ההטמעה-בפרויקט)
5. [התאמה אישית של ממשק ה-UI](#5-התאמה-אישית-של-ממשק-ה-ui)
6. [תגיות (Tags)](#6-תגיות-tags)
7. [אבטחה ואימות](#7-אבטחה-ואימות)
8. [נקודות קצה (Endpoints)](#8-נקודות-קצה-endpoints)
9. [הוספת תיעוד עתידי](#9-הוספת-תיעוד-עתידי)
10. [פקודות שימושיות](#10-פקודות-שימושיות)

---

## 1. מהי Scramble?

Scramble היא חבילת Laravel שמייצרת תיעוד API בפורמט **OpenAPI 3.1** אוטומטית, ישירות מקוד ה-PHP. בניגוד לכלים אחרים שדורשים כתיבת YAML ידנית, Scramble מנתחת:

- **Form Request classes** — גוזרת ממנה את שדות הבקשה ואת כללי הוולידציה
- **PHPDoc comments** — מחלצת תיאורים, תגיות, ומצבי אימות
- **Return types** — בונה את סכמת התגובה מ-`JsonResponse` ומ-`array` returns
- **Route parameters** — מזהה פרמטרים ב-URL אוטומטית

תוצאה: תיעוד API מדויק ועדכני ללא תחזוקה ידנית.

---

## 2. התקנה ודרישות

```bash
composer require dedoc/scramble
```

החבילה מצריכה:
- PHP 8.1+
- Laravel 10+
- הפרויקט הנוכחי: **PHP 8.4.18** + **Laravel 12.54.0** ✅

---

## 3. קונפיגורציה

קובץ הקונפיג: **`config/scramble.php`**

### הגדרות עיקריות

| מפתח | ערך נוכחי | תיאור |
|---|---|---|
| `api_path` | `api` | כל route שמתחיל ב-`api/` נכלל בתיעוד |
| `api_domain` | `null` | משתמש בדומיין ברירת מחדל של האפליקציה |
| `export_path` | `api.json` | מיקום קובץ ה-OpenAPI המיוצא |
| `info.version` | `1.0.0` | נשלט דרך env: `API_VERSION` |
| `info.description` | Markdown | תיאור מפורט בראש דף התיעוד |
| `ui.title` | `Kalfa API Documentation` | כותרת העמוד |
| `ui.theme` | `light` | ערכת הנושא (light / dark / system) |
| `ui.layout` | `responsive` | פריסה רספונסיבית |
| `middleware` | `['web']` | middleware המגן על עמוד התיעוד |

### שינוי גרסת ה-API

בקובץ `.env`:
```env
API_VERSION=2.0.0
```

---

## 4. אופן ההטמעה בפרויקט

כל ההגדרות הפרוגרמטיות נמצאות ב-**`app/Providers/AppServiceProvider.php`** בפונקציה `configureScramble()`.

### 4.1 סינון Routes

```php
Scramble::configure()
    ->routes(function (Route $route) {
        $uri = $route->uri();
        // כולל רק routes תחת /api/
        if (! str_starts_with($uri, 'api/')) {
            return false;
        }
        // לא כולל endpoints פנימיים של Twilio (מאובטחים במפתח סודי)
        if (str_starts_with($uri, 'api/twilio/')) {
            return false;
        }
        return true;
    });
```

**Routes שמוחרגים:**
- `api/twilio/*` — endpoints פנימיים של Node.js bridge לשיחות קוליות, מאובטחים עם מפתח סודי ולא עם Sanctum

### 4.2 הוספת Bearer Token Security

```php
->withDocumentTransformers(function (OpenApi $openApi) {
    $openApi->secure(
        SecurityScheme::http('bearer')
    );
});
```

מגדיר **Bearer token** (Sanctum) כדרישת אימות גלובלית על כל ה-endpoints.
endpoints ציבוריים מסומנים ב-`@unauthenticated` ומקבלים `security: []` אוטומטית.

### 4.3 מיפוי Tags אוטומטי

```php
Scramble::resolveTagsUsing(function ($routeInfo) {
    $action = $routeInfo->route->getAction('controller');
    $parts = explode('\\', $controller);
    $className = end($parts);
    $tag = str_replace('Controller', '', $className);
    // CamelCase → מילים מופרדות: EventTable → Event Table
    $tag = preg_replace('/([a-z])([A-Z])/', '$1 $2', $tag);
    return [$tag ?: 'General'];
});
```

**תוצאה:**

| Controller | Tag בתיעוד |
|---|---|
| `EventController` | `Event` |
| `GuestController` | `Guest` |
| `EventTableController` | `Event Table` |
| `SeatAssignmentController` | `Seat Assignment` |
| `InvitationController` | `Invitation` |
| `CheckoutController` | `Checkout` |
| `PaymentController` | `Payment` |
| `PublicRsvpController` | `Public Rsvp` |
| `WebhookController` | `Webhook` |
| `OrganizationController` | `Organization` |
| `GuestImportController` | `Guest Import` |

---

## 5. התאמה אישית של ממשק ה-UI

Scramble משתמש ב-**[Stoplight Elements](https://github.com/stoplightio/elements)** כ-UI לתצוגת התיעוד. ישנן שלוש שכבות של התאמה אישית.

---

### 5.1 הגדרות UI ב-`config/scramble.php`

כל ההגדרות הבאות נמצאות תחת מפתח `ui` בקובץ `config/scramble.php`:

```php
'ui' => [
    'title'                   => 'Kalfa API Documentation',
    'theme'                   => 'light',    // 'light' | 'dark' | 'system'
    'layout'                  => 'responsive', // 'sidebar' | 'responsive' | 'stacked'
    'hide_try_it'             => false,
    'hide_schemas'            => false,
    'logo'                    => '',          // URL לתמונה (PNG/SVG קטנה)
    'try_it_credentials_policy' => 'include', // 'omit' | 'include' | 'same-origin'
],
```

#### פירוט הגדרות

| מפתח | ברירת מחדל | אפשרויות | תיאור |
|---|---|---|---|
| `title` | שם האפליקציה | כל מחרוזת | כותרת כרטיסיית הדפדפן ועמוד התיעוד |
| `theme` | `light` | `light` / `dark` / `system` | ערכת הנושא. `system` עוקב אחרי העדפת מערכת ההפעלה |
| `layout` | `responsive` | `sidebar` / `responsive` / `stacked` | **sidebar**: תמיד 3 עמודות · **responsive**: מתמוטט ל-drawer במובייל · **stacked**: עמודה אחת (מתאים לשילוב באתר קיים) |
| `hide_try_it` | `false` | `true` / `false` | מסתיר את כפתור "Try It" (שימושי בסביבת production) |
| `hide_schemas` | `false` | `true` / `false` | מסתיר את פרק "Schemas" מתפריט הניווט |
| `logo` | `''` | URL מלא | לוגו קטן (ריבוע) שמוצג מעל תפריט הניווט |
| `try_it_credentials_policy` | `include` | `include` / `omit` / `same-origin` | מדיניות cookies בבקשות "Try It". `include` נחוץ לאימות Sanctum |

#### דוגמה — מצב Dark עם הסתרת Try It:

```php
'ui' => [
    'title'       => 'Kalfa API',
    'theme'       => 'dark',
    'layout'      => 'responsive',
    'hide_try_it' => true,
    'logo'        => 'https://kalfa.me/logo.png',
],
```

---

### 5.2 Override של ה-View (HTML מלא)

ה-UI מרונדר דרך Blade view: `vendor/dedoc/scramble/resources/views/docs.blade.php`.

כדי לדרוס אותו לחלוטין (הוספת CSS, שינוי גופן, הטמעת analytics וכו'), יש להעתיק אותו:

```bash
mkdir -p resources/views/vendor/scramble
cp vendor/dedoc/scramble/resources/views/docs.blade.php \
   resources/views/vendor/scramble/docs.blade.php
```

Laravel מוצא אוטומטית views מ-`resources/views/vendor/{package}/` לפני ה-vendor.

#### מה ה-View מכיל (ניתוח הקוד):

```html
<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <!-- טוען Stoplight Elements מ-unpkg CDN -->
    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link  rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <!-- Intercept fetch לתמיכה בסנכרון CSRF/Sanctum ב-Try It -->
    <script>/* ... */</script>

    <!-- תיקוני CSS לדארק-מוד של syntax highlighting */
    <style>/* ... */</style>
</head>
<body>
    <elements-api
        tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
        router="hash"
        @if($config->get('ui.hide_try_it'))  hideTryIt="true"  @endif
        @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
        @if($config->get('ui.logo'))         logo="{{ $config->get('ui.logo') }}" @endif
        @if($config->get('ui.layout'))       layout="{{ $config->get('ui.layout') }}" @endif
    />
    <script>
        // הזרקת ה-OpenAPI spec ישירות לתוך ה-web component
        document.getElementById('docs').apiDescriptionDocument = @json($spec);
    </script>
</body>
```

#### דוגמאות להתאמה בקובץ ה-View המועתק:

**הוספת Google Font וכותרת מותאמת:**
```html
<head>
    <!-- ... -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif !important; }
        .sl-stack { direction: ltr !important; } /* Elements תמיד LTR */
    </style>
</head>
```

**שינוי גרסת Stoplight Elements:**
```html
<!-- שינוי 8.4.2 לגרסה עדכנית יותר -->
<script src="https://unpkg.com/@stoplight/elements@8.5.0/web-components.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.5.0/styles.min.css">
```

**הוספת JavaScript Analytics:**
```html
<!-- בתוך <head> -->
<script>
    // Google Analytics / Mixpanel וכו'
    window.dataLayer = window.dataLayer || [];
</script>
```

**הסתרת אלמנטים ספציפיים ב-CSS:**
```css
<style>
    /* הסתרת footer של Stoplight */
    .sl-bg-canvas-pure .sl-text-sm { display: none; }

    /* התאמת צבע ראשי */
    :root {
        --color-primary-light: #your-color;
        --color-primary:       #your-color;
        --color-primary-dark:  #your-color;
    }
</style>
```

---

### 5.3 הגדרת כתובות מותאמות (`expose`)

ברירת המחדל: `/docs/api` ו-`/docs/api.json`. ניתן לשנות דרך `AppServiceProvider`:

```php
Scramble::configure()
    ->expose(
        ui:       '/api-docs',        // כתובת ה-UI
        document: '/openapi.json',    // כתובת ה-JSON spec
    );
```

**ביטול routes ברירת מחדל לחלוטין:**
```php
// register() — לפני boot
Scramble::ignoreDefaultRoutes();
```

**הגדרת כתובות ב-`routes/web.php`:**
```php
use Dedoc\Scramble\Scramble;

// Route מותאם עם middleware
Route::middleware(['auth', 'verified'])
    ->group(function () {
        Scramble::registerUiRoute('internal/api-docs');
        Scramble::registerJsonSpecificationRoute('internal/api-docs.json');
    });
```

---

### 5.4 שליטה בגישה לעמוד התיעוד

ברירת מחדל: נגיש רק ב-`local` environment.

**הגדרת גישה לפי תפקיד (Gate):**
```php
// app/Providers/AppServiceProvider.php → boot()
Gate::define('viewApiDocs', function (User $user) {
    return $user->is_system_admin;
});
```

**שימוש ב-`RestrictedDocsAccess` middleware** (כבר כלול בחבילה):
```php
// config/scramble.php
'middleware' => [
    'web',
    \Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess::class,
],
```
`RestrictedDocsAccess` בודק את `viewApiDocs` Gate בסביבות שאינן `local`.

---

### 5.5 שינוי פרטי המסמך (Info Object)

ניתן לשנות את כותרת ה-API, גרסה ותיאור דרך `withDocumentTransformers`:

```php
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\InfoObject;

Scramble::configure()
    ->withDocumentTransformers(function (OpenApi $openApi) {
        $openApi->info->setTitle('My Custom API Title');
        $openApi->info->setVersion('v2.0');
        $openApi->info->description = 'תיאור ה-API בעברית';
    });
```

---

### 5.6 הגדרת Servers מותאמים

```php
// config/scramble.php
'servers' => [
    'Production' => 'https://kalfa.me/api',
    'Staging'    => 'https://staging.kalfa.me/api',
    'Local'      => 'http://localhost:8000/api',
],
```

---

### 5.7 הגדרת משתני Server (Server Variables)

```php
use Dedoc\Scramble\Support\Generator\ServerVariable;

Scramble::configure()
    ->withServerVariables([
        'environment' => new ServerVariable(
            default: 'production',
            enum: ['production', 'staging', 'local'],
            description: 'סביבת הריצה'
        ),
    ]);
```

---

## 6. תגיות (Tags)

התיעוד מחולק ל-**11 קבוצות**:

| Tag | תיאור |
|---|---|
| **Organization** | ניהול ארגון — קריאה ועדכון פרטים |
| **Event** | ניהול אירועים — יצירה, עדכון, מחיקה |
| **Guest** | ניהול אורחים באירוע |
| **Guest Import** | ייבוא אורחים מקובץ CSV |
| **Event Table** | ניהול שולחנות/אזורי ישיבה |
| **Seat Assignment** | שיבוץ אורחים לשולחנות |
| **Invitation** | יצירה ושליחת הזמנות RSVP |
| **Checkout** | תהליך תשלום לאירוע |
| **Payment** | בדיקת סטטוס תשלום |
| **Public Rsvp** | ❌ ללא אימות — API ציבורי לאורחים |
| **Webhook** | ❌ ללא אימות — קבלת webhooks מ-gateway |

---

## 7. אבטחה ואימות

### Bearer Token (Sanctum)

כל ה-endpoints הפרטיים דורשים header:
```
Authorization: Bearer {token}
```

Token מתקבל ב-login ומשמש לאימות מול `auth:sanctum` middleware.

### Endpoints ציבוריים (`@unauthenticated`)

שלושה endpoints לא דורשים אימות:

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/rsvp/{slug}` | קבלת פרטי הזמנה לפי slug |
| `POST` | `/api/rsvp/{slug}/responses` | שליחת תגובת RSVP |
| `POST` | `/api/webhooks/{gateway}` | קבלת webhook מ-payment gateway |

### Rate Limiting

| קבוצה | מגבלה |
|---|---|
| `rsvp_show` (GET RSVP) | 60 בקשות/דקה |
| `rsvp_submit` (POST RSVP) | 10 בקשות/דקה |
| `webhooks` | 120 בקשות/דקה |

---

## 8. נקודות קצה (Endpoints)

### 7.1 Organizations

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{organization}` | קבלת פרטי ארגון |
| `PATCH` | `/api/organizations/{organization}` | עדכון פרטי ארגון |

**גוף עדכון ארגון:**
```json
{
  "name": "string (optional, max:255)",
  "billing_email": "email (optional)",
  "settings": "object (optional)"
}
```

---

### 7.2 Events

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{org}/events` | רשימת אירועים (ניתן לסנן לפי `?status=`) |
| `POST` | `/api/organizations/{org}/events` | יצירת אירוע חדש |
| `GET` | `/api/organizations/{org}/events/{event}` | פרטי אירוע מלאים |
| `PUT/PATCH` | `/api/organizations/{org}/events/{event}` | עדכון אירוע |
| `DELETE` | `/api/organizations/{org}/events/{event}` | מחיקה רכה (soft delete) |

**יצירת אירוע — שדות:**
```json
{
  "name": "string (required, max:255)",
  "slug": "string (required, max:255)",
  "event_date": "date|null (optional)",
  "venue_name": "string|null (optional, max:255)",
  "settings": "object|null (optional)"
}
```

**ערכי `status` לסינון:**
`Draft` | `PendingPayment` | `Active` | `Cancelled` | `Completed`

> אירוע נוצר תמיד בסטטוס `Draft`. הוא עובר ל-`PendingPayment` עם תחילת תשלום, ול-`Active` עם אישור התשלום.

---

### 7.3 Guests

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{org}/events/{event}/guests` | רשימת אורחים (ממוין לפי `sort_order`) |
| `POST` | `/api/organizations/{org}/events/{event}/guests` | הוספת אורח |
| `GET` | `/api/organizations/{org}/events/{event}/guests/{guest}` | פרטי אורח |
| `PUT/PATCH` | `/api/organizations/{org}/events/{event}/guests/{guest}` | עדכון אורח |
| `DELETE` | `/api/organizations/{org}/events/{event}/guests/{guest}` | מחיקת אורח |

**הוספת אורח — שדות:**
```json
{
  "name": "string (required, max:255)",
  "email": "email|null (optional)",
  "phone": "string|null (optional, max:50)",
  "group_name": "string|null (optional, max:255)",
  "notes": "string|null (optional)",
  "sort_order": "integer|null (optional)"
}
```

### 7.4 Guest Import (CSV)

| Method | Path | תיאור |
|---|---|---|
| `POST` | `/api/organizations/{org}/events/{event}/guests/import` | ייבוא מ-CSV |

**Body:** `multipart/form-data` עם שדה `file` (CSV)

**פורמט CSV נתמך:**

```csv
name,email,phone,notes
"ישראל ישראלי","israel@example.com","050-1234567","שורה ראשונה"
"שרה כהן",,,"אורחת"
```

גם עמודות בעברית נתמכות: `שם`, `email`, `phone`, `notes`.
שורות ללא שם וללא email — מדולגות אוטומטית.

---

### 7.5 Event Tables

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{org}/events/{event}/event-tables` | רשימת שולחנות |
| `POST` | `/api/organizations/{org}/events/{event}/event-tables` | יצירת שולחן |
| `GET` | `/api/.../event-tables/{eventTable}` | פרטי שולחן |
| `PUT/PATCH` | `/api/.../event-tables/{eventTable}` | עדכון שולחן |
| `DELETE` | `/api/.../event-tables/{eventTable}` | מחיקת שולחן |

**יצירת שולחן — שדות:**
```json
{
  "name": "string (required, max:255)",
  "capacity": "integer|null (optional)",
  "sort_order": "integer|null (optional)"
}
```

---

### 7.6 Seat Assignments

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{org}/events/{event}/seat-assignments` | רשימת שיבוצים |
| `PUT` | `/api/organizations/{org}/events/{event}/seat-assignments` | עדכון/יצירת שיבוצים (bulk) |

**גוף Bulk Upsert:**
```json
{
  "assignments": [
    {
      "guest_id": 1,
      "event_table_id": 5,
      "seat_number": "A3"
    }
  ]
}
```

> פעולה אידמפוטנטית: קריאה חוזרת עם אותם נתונים לא יוצרת כפילויות.

---

### 7.7 Invitations

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/organizations/{org}/events/{event}/invitations` | רשימת הזמנות |
| `POST` | `/api/organizations/{org}/events/{event}/invitations` | יצירת הזמנה |
| `POST` | `/api/.../invitations/{invitation}/send` | שליחת הזמנה (WhatsApp) |

**יצירת הזמנה:**
```json
{
  "guest_id": "integer|null (optional)"
}
```

**שליחה ב-WhatsApp:**
```json
{
  "send_whatsapp": true
}
```

slug נוצר אוטומטית בעת היצירה, ומשמש לקישור RSVP ציבורי.

---

### 7.8 Checkout

| Method | Path | תיאור |
|---|---|---|
| `POST` | `/api/organizations/{org}/events/{event}/checkout` | פתיחת תהליך תשלום |

**שדות:**
```json
{
  "plan_id": "integer (required)",
  "token": "string|null (optional) — PaymentsJS single-use token"
}
```

**תגובה — redirect flow** (ללא `token`):
```json
{
  "redirect_url": "https://..."
}
```

**תגובה — token flow** (עם `token`):
```json
{
  "status": "processing",
  "payment_id": 42
}
```

> ⚠️ **PCI**: אסור לשלוח נתוני כרטיס אשראי (מספר כרטיס, CVV, תוקף) ישירות לשרת. יש להשתמש ב-PaymentsJS בלבד.

---

### 7.9 Payments

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/payments/{payment}` | סטטוס תשלום |

**תגובה:**
```json
{
  "status": "Pending | Processing | Succeeded | Failed"
}
```

---

### 7.10 Public RSVP ❌ (ללא אימות)

| Method | Path | תיאור |
|---|---|---|
| `GET` | `/api/rsvp/{slug}` | פרטי הזמנה לפי slug |
| `POST` | `/api/rsvp/{slug}/responses` | שליחת תגובת RSVP |

**תגובת GET:**
```json
{
  "slug": "abc123-1234567890",
  "event_name": "חתונת ישראל ושרה",
  "event_date": "2026-08-15",
  "venue_name": "אולם הוורדים",
  "guest_name": "דוד לוי"
}
```

**גוף POST — שדות:**
```json
{
  "response": "yes | no | maybe (required)",
  "attendees_count": "integer|null (optional)",
  "message": "string|null (optional, max:1000)"
}
```

**תגובת POST:**
```json
{
  "success": true,
  "response": "yes"
}
```

---

### 7.11 Webhooks ❌ (ללא אימות Sanctum)

| Method | Path | תיאור |
|---|---|---|
| `POST` | `/api/webhooks/{gateway}` | קבלת webhook מ-gateway |

- `{gateway}` — כרגע נתמך: `sumit`
- מאמת HMAC signature אם `BILLING_WEBHOOK_SECRET` מוגדר
- אידמפוטנטי: תשלומים שכבר עובדו — מדולגים בשקט

---

## 9. הוספת תיעוד עתידי

### 8.1 הוספת תיאור ל-Endpoint

כדי להוסיף תיאור ל-endpoint, יש להוסיף PHPDoc לפני המתודה בקונטרולר:

```php
/**
 * כותרת קצרה של הפעולה.
 *
 * פסקה מורחבת עם הסבר. תופיע בתיעוד כ-description.
 * תומכת ב-Markdown.
 *
 * @param Request $request
 */
public function index(Request $request): JsonResponse
```

### 8.2 סימון Endpoint כציבורי

```php
/**
 * @unauthenticated
 */
public function publicEndpoint(): JsonResponse
```

### 8.3 הסתרת Endpoint מהתיעוד

```php
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

#[ExcludeRouteFromDocs]
public function internalEndpoint(): JsonResponse
```

להסתרת כל הקונטרולר:
```php
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;

#[ExcludeAllRoutesFromDocs]
class InternalController extends Controller
```

### 8.4 הוספת Form Request לתיעוד מדויק

כאשר controller משתמש ב-`$request->validate()` ישירות (ולא ב-Form Request), Scramble מנתחת את הוולידציה inline. עם זאת, עדיף להפוך לתמיד לשימוש ב-**Form Request** לתיעוד מדויק יותר:

```bash
php artisan make:request Api/UpdateGuestRequest
```

```php
public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
```

### 8.5 הוספת Tag לקונטרולר

```php
/**
 * @tags Guests, Import
 */
class GuestImportController extends Controller
```

---

## 10. פקודות שימושיות

### יצוא קובץ OpenAPI

```bash
php artisan scramble:export
```
מייצר קובץ `api.json` בתיקיית הפרויקט.

### ניתוח שגיאות בתיעוד

```bash
php artisan scramble:analyze
```
מציג אזהרות על endpoints שלא ניתן לתעד אוטומטית.

### הצגת כתובת התיעוד

```
https://kalfa.me/docs/api       # ממשק Stoplight Elements
https://kalfa.me/docs/api.json  # מסמך OpenAPI JSON גולמי
```

### הגבלת גישה בסביבת Production

בברירת מחדל, עמוד `/docs/api` נגיש רק ב-`local`. כדי להתיר גישה בסביבות נוספות, יש להגדיר Gate:

```php
// app/Providers/AppServiceProvider.php — בתוך boot()
Gate::define('viewApiDocs', function (User $user) {
    return $user->is_system_admin;
});
```

---

## נספח: מבנה הקבצים הרלוונטיים

```
app/
├── Providers/
│   └── AppServiceProvider.php         ← configureScramble() — כל ההגדרות
├── Http/
│   ├── Controllers/Api/
│   │   ├── EventController.php         ← PHPDoc על כל method
│   │   ├── GuestController.php
│   │   ├── GuestImportController.php
│   │   ├── EventTableController.php
│   │   ├── SeatAssignmentController.php
│   │   ├── InvitationController.php
│   │   ├── CheckoutController.php
│   │   ├── PaymentController.php
│   │   ├── PublicRsvpController.php    ← @unauthenticated
│   │   ├── WebhookController.php       ← @unauthenticated
│   │   └── OrganizationController.php
│   └── Requests/Api/
│       ├── StoreEventRequest.php       ← Scramble קוראת rules() אוטומטית
│       ├── UpdateEventRequest.php
│       ├── StoreGuestRequest.php
│       ├── InitiateCheckoutRequest.php
│       └── StoreRsvpResponseRequest.php
config/
└── scramble.php                        ← הגדרות UI, info, middleware
api.json                                ← מסמך OpenAPI מיוצא (scramble:export)
```
