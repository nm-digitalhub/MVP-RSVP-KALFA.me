# Billing Access Gate — Architecture & Flow

> **תאריך:** מרץ 2026  
> **סטטוס:** פרודקשן

---

## רקע — הבעיה שנפתרה

הארגון "Kalfa-test" (Account #2) היה נגיש לכל פיצ'רים של הפלטפורמה למרות שלא בוצע כל תשלום ולא הוענק אף מוצר.

**סיבת השורש:** ה-middleware הקיים בדק רק `is_suspended` (השעיה ידנית). לא הייתה שכבת בקרה בין **מצב billing** לבין **גישה לפיצ'רים**.

---

## ארכיטקטורת הפתרון

### שכבות הגישה (middleware stack)

כל בקשה לנתיבי tenant עוברת בסדר הבא:

```
web
 └─ auth
     └─ verified
         └─ ensure.organization        ← מכניס context ארגוני
             └─ ensure.account_active  ← בודק billing (מוצר / מנוי / trial)
                 └─ controller
```

לנתיבי **features ספציפיים** (Twilio, voice):

```
ensure.organization
 └─ ensure.account_active
     └─ ensure.feature:twilio_enabled  ← בודק entitlement ספציפי
         └─ controller
```

### חריגים (bypass)

שני מקרים תמיד עוברים ישירות:

| מקרה | סיבה |
|---|---|
| `users.is_system_admin = true` | סמכות כלל-מערכתית |
| `session('impersonation.original_organization_id')` | Admin מתחזה לארגון לצורך פתרון בעיות |

---

## קבצים שנוצרו

### `app/Http/Middleware/EnsureAccountActive.php`

שומר על נתיבי tenant. בודק לפי הסדר:

1. **AccountProduct פעיל** — מוצר שהוענק ידנית עם `status=active` ו-`expires_at > now`
2. **Subscription פעיל** — מנוי עם `status=active` ולא הסתיים
3. **Trial פעיל** — מנוי עם `status=trial` ו-`trial_ends_at > now`

**Web (session):** redirect ל-`/billing`  
**API (`expectsJson`):** HTTP 402 JSON

```php
// רזולוציית הארגון
$routeOrg = $request->route('organization');
$org = $routeOrg instanceof Organization
    ? $routeOrg                     // API — route param {organization}
    : $user->currentOrganization;   // Web — session/DB
```

---

### `app/Http/Middleware/EnsureFeatureAccess.php`

שומר על פיצ'ר ספציפי לפי מפתח (`feature_key`).

```
Route::middleware('ensure.feature:twilio_enabled')
Route::middleware('ensure.feature:voice_rsvp_calls')
```

**הרזולוציה:**
1. Admin bypass (is_system_admin / impersonation)
2. `Gate::allows('feature', $featureKey)`
3. Gate מפנה ל-`FeatureResolver` — בודק `account_entitlements` → `product_entitlements`

**Web:** redirect ל-`/billing?reason=feature:twilio_enabled`  
**API:** HTTP 403 JSON עם `reason: "feature:twilio_enabled"`

---

### `app/Enums/Feature.php`

רישום קנוני של כל מפתחות הפיצ'ר. הערכים חייבים להתאים ל-`feature_key` בטבלאות `product_entitlements` / `account_entitlements`.

| Case | Value | תיאור |
|---|---|---|
| `TwilioEnabled` | `twilio_enabled` | מאסטר-סוויץ' לכל Twilio |
| `VoiceRsvpCalls` | `voice_rsvp_calls` | שיחות RSVP קוליות |
| `SmsConfirmationEnabled` | `sms_confirmation_enabled` | SMS אישורים |
| `SmsConfirmationLimit` | `sms_confirmation_limit` | מגבלת SMS לתקופה |
| `SmsConfirmationMessages` | `sms_confirmation_messages` | מדד usage SMS |
| `CreateEvent` | `create_event` | יצירת אירועים |
| `MaxActiveEvents` | `max_active_events` | מקסימום אירועים פעילים |
| `MaxGuestsPerEvent` | `max_guests_per_event` | מקסימום אורחים לאירוע |
| `GuestImport` | `guest_import` | ייבוא אורחים מ-CSV |
| `SeatingManagement` | `seating_management` | ניהול הושבה |
| `InvitationSending` | `invitation_sending` | שליחת הזמנות |

---

## קבצים שעודכנו

### `bootstrap/app.php` — רישום aliases

```php
'ensure.account_active' => EnsureAccountActive::class,
'ensure.feature'        => EnsureFeatureAccess::class,
```

---

### `app/Providers/AppServiceProvider.php` — Gate definitions

```php
// Admin bypass (Gate::before)
Gate::before(function (User $user, string $ability) {
    if ($user->is_system_admin) return true;
    if (session()->has('impersonation.original_organization_id')) return true;
    return null; // ממשיך לבדיקה הרגילה
});

// Feature gate
Gate::define('feature', fn (User $user, string $key) => FeatureResolver::allows(
    $user->currentOrganization?->account,
    $key
));
```

> **הערה:** `Gate::before` מחזיר `null` (לא `true`) לאדמינים שאינם מתחזים על abilities שאינן מערכתיות — לכן `EnsureFeatureAccess` מוסיף bypass מפורש לפני קריאת `Gate::allows`.

---

### `routes/web.php` — מבנה קבוצות

```
auth + verified
└─ ensure.organization
    ├─ /billing/*          ← ללא ensure.account_active (שחרורי)
    │   ├─ billing.account
    │   ├─ billing.entitlements
    │   ├─ billing.usage
    │   └─ billing.intents
    │
    └─ ensure.account_active
        ├─ /dashboard
        ├─ /team
        ├─ /events/*
        └─ /rsvp-calls/* ← גם ensure.feature:twilio_enabled
```

---

### `routes/api.php` — מבנה קבוצות

```
auth:sanctum
└─ ensure.account_active
    ├─ organizations/{org}/events
    ├─ organizations/{org}/events/{event}/guests
    ├─ organizations/{org}/events/{event}/event-tables
    ├─ organizations/{org}/events/{event}/seat-assignments
    └─ organizations/{org}/events/{event}/invitations

ללא ensure.account_active (שחרורי):
├─ organizations/{org}/checkout        ← לתשלום
├─ payments/{payment}                  ← בדיקת סטטוס
├─ rsvp/{slug}                         ← RSVP ציבורי
└─ webhooks/{gateway}                  ← webhook billing
```

---

### `app/Models/Organization.php` — מצב billing

**תיקון קריטי (מרץ 2026):** הארכיטקטורה הקודמת הכילה שני מקורות אמת סותרים:
- `billing_status` הסתמך על `hasActivePlan()` שקרא `account->accountProducts->isNotEmpty()` ללא scope — כולל מוצרים פגי-תוקף → **false positive**
- `hasBillingAccess()` השתמש ב-scopes נכונים → **נכון**

**אחרי התיקון:** מקור אמת אחד בלבד.

```php
/** בודק אם קיים מוצר/מנוי/trial פעיל — fast path דרך activeAccountProducts relation */
public function hasActivePlan(): bool
{
    if ($this->account === null) return false;
    // Fast path: scoped relation כבר טעון
    if ($this->account->relationLoaded('activeAccountProducts')) {
        return $this->account->activeAccountProducts->isNotEmpty();
    }
    // Authoritative: cached hasBillingAccess()
    return $this->account->hasBillingAccess();
}

/** מצב billing קנוני — מאוחד עם hasBillingAccess() */
public function getBillingStatusAttribute(): string
{
    if ($this->is_suspended) return 'suspended';
    if ($this->account?->hasBillingAccess()) return 'active';
    return 'no_plan';
}
```

---

## מצב Billing — 3 מצבים

| `billing_status` | תנאי | Badge בממשק |
|---|---|---|
| `suspended` | `is_suspended = true` | 🔴 Suspended |
| `active` | לא מושעה + יש מוצר/מנוי פעיל | 🟢 Active |
| `no_plan` | לא מושעה + אין מוצר/מנוי | 🟡 No Plan |

---

### `app/Livewire/System/Organizations/Index.php` — שינויים

1. **Eager load (scoped relation):** `with(['account' => fn($q) => $q->with(['activeAccountProducts'])])` — 3 queries, ללא N+1, רק רשומות פעילות
2. **פילטר חדש:** `filter_no_plan` — מסנן ארגונים ללא תוכנית פעילה
3. **Query לפילטר:**
   ```php
   ->whereDoesntHave('account.accountProducts', fn($ap) => $ap->active())
   ```

---

### `resources/views/livewire/system/organizations/index.blade.php` — שינויים

1. **Badge** — עבר מ-`@if/$org->is_suspended` ל-`@switch($org->billing_status)` עם 3 cases
2. **פילטר "No Active Plan"** — dropdown חדש מחובר ל-`wire:model.live="filter_no_plan"`

---

## זרימת בקשה מלאה (Web)

```
1. משתמש פותח /dashboard/events/123
2. middleware: auth         → בדיקת session
3. middleware: verified     → אימות email
4. middleware: ensure.organization → קורא users.current_organization_id מ-DB
5. middleware: ensure.account_active:
   a. is_system_admin? → pass ✓
   b. impersonation? → pass ✓
   c. account.activeAccountProducts().exists()? → pass ✓ / continue
   d. account.activeSubscriptions().exists()? → pass ✓ / continue
   e. active trial? → pass ✓ / continue
   f. deny → redirect /billing (web) או 402 JSON (API)
6. controller / Livewire component
```

---

## זרימת בקשה מלאה (API)

```
1. POST /api/organizations/4/events
2. middleware: auth:sanctum → token validation
3. middleware: ensure.account_active:
   a. resolves org from route param {organization} = 4
   b. org.account.activeAccountProducts().exists() → false
   c. return 402 JSON { message: "...", reason: "no_active_plan" }
```

---

## DB — מצב Account #2 (Kalfa-test)

| שדה | ערך |
|---|---|
| `accounts.id` | 2 |
| מוצרים שהוענקו | Product 1 (AI Voice RSVP) + Product 3 (Twilio SMS) |
| תוקף | 30 יום מרגע ההענקה |
| entitlements | `voice_rsvp_enabled=true`, `twilio_enabled=true`, `sms_confirmation_enabled=true`, `sms_confirmation_limit=500` |
| `billing_status` של org #4 | `active` |

---

## `Account::hasBillingAccess()` — single source of truth

כל בקרת הגישה עוברת דרך שיטה אחת במודל `Account`:

```php
public function hasBillingAccess(): bool
{
    return Cache::remember("account:{$this->id}:billing_access", 60, function (): bool {
        return $this->activeAccountProducts()->exists()
            || $this->activeSubscriptions()->exists()
            || $this->subscriptions()
                ->where('status', AccountSubscriptionStatus::Trial->value)
                ->where('trial_ends_at', '>', now())
                ->exists();
    });
}
```

`EnsureAccountActive` קורא רק:
```php
if (! $account->hasBillingAccess()) { /* deny */ }
```

### Cache Invalidation

`invalidateBillingAccessCache()` נקרא אוטומטית ב:

| מקום | מתי |
|---|---|
| `Account::grantProduct()` | לאחר הענקת מוצר |
| `SubscriptionService::clearFeatureCache()` | activate / cancel / suspend / renew |

---

## התאמות עתידיות מומלצות

| נושא | פעולה מוצעת |
|---|---|
| **Usage tracking** | `SmsConfirmationMessages` — מוניטור אוטומטי על שליחת SMS מול `sms_confirmation_limit` |
| **UX** | הוסף `?reason=no_active_subscription` ל-redirect לביטוי הודעה ספציפית ב-billing page |
| **API clients** | תעד ש-HTTP 402 משמש ל-"no active plan" (חלק מה-SDKs מצפים ל-403) |
