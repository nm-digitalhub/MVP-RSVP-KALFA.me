# מערכת הרשאות מבוססת תשלום — תיעוד טכני

**תאריך:** מרץ 2026  
**גרסת Laravel:** 12 / PHP 8.4  
**Commits:** `32311744` → `31a3f0da` → `e4638f23`  
**סקירה טכנית:** ראו [`BILLING_PERMISSION_GATE-Review.md`](./BILLING_PERMISSION_GATE-Review.md)

---

## תוכן עניינים

1. [סקירה כללית](#סקירה-כללית)
2. [זרימת המשתמש המלאה](#זרימת-המשתמש-המלאה)
3. [ארכיטקטורת המערכת](#ארכיטקטורת-המערכת)
4. [קבצים ורכיבים](#קבצים-ורכיבים)
5. [שני נתיבי ההפעלה (Flow A + B)](#שני-נתיבי-ההפעלה)
6. [תנאי ההרשאה](#תנאי-ההרשאה)
7. [שלילת הרשאות](#שלילת-הרשאות)
8. [ה-Observer](#accountproductobserver)
9. [PermissionSyncService](#permissionsyncservice)
10. [שגיאות נפוצות ופתרונות](#שגיאות-נפוצות-ופתרונות)
11. [בדיקות ואימות](#בדיקות-ואימות)
12. [שיפורים מומלצים (מהסקירה)](#שיפורים-מומלצים)
13. [תרשים שלם](#תרשים-שלם)

---

## סקירה כללית

המערכת שולטת בגישה לפיצ'רים (יצירת אירועים, ניהול אורחים וכדומה) על-ידי **הקצאת הרשאות Spatie מוגבלות לצוות (team-scoped)** רק לאחר שחשבון הארגון קיבל **מוצר פעיל (AccountProduct)** מגובה בתשלום מוצלח **או** בהענקה ידנית של מנהל מערכת.

### העיקרון

> **אין מוצר פעיל = אין הרשאות = redirect לעמוד billing עם הסבר**

---

## זרימת המשתמש המלאה

```
1. משתמש נרשם → RegisterController → יצירת User
       ↓
2. יצירת ארגון → Organizations/Create (Livewire)
       ↓ pivot role = Owner (ב-organization_users)
       ↓ אין הרשאות Spatie בשלב זה ← עיצוב מכוון
       ↓
3. ניסיון לגשת ל- /dashboard/events/create
       ↓
4. EventController::create()
       ↓ authorize('create', [Event::class, $org->id])
       ↓ EventPolicy::create() → $user->can('manage-event-guests') = FALSE
       ↓ AuthorizationException נתפסת
       ↓
5. redirect → /billing עם warning:
   "כדי ליצור אירועים נדרש חשבון פעיל..."
       ↓
6. עמוד /billing → AccountOverview (Livewire)
       ↓ מציג banner כתום עם ההסבר
       ↓ OrganizationPolicy::update() ← בודק pivot role (לא Spatie)
       ↓ Owner רשאי → מציג כפתור "Create account"
       ↓
7. לחיצה "Create account for this organization"
       ↓ AccountOverview::createAccount()
       ↓ Account::create() + organization.account_id מקושר
       ↓ עדיין אין הרשאות — Account בלבד אינו תשלום
       ↓
8. בחירת Plan → תשלום (SUMIT / subscription)
       ↓
9. SubscriptionService::activate() → Account::grantProduct()
   OR System admin → Account::grantProduct($grantedBy=adminId)
       ↓
10. AccountProduct נוצר עם status=active
        ↓ AccountProductObserver::created() מופעל
        ↓ PermissionSyncService::hasActivePaidOrGranted() = TRUE
        ↓
11. syncForAccount() → givePermissionTo() לכל Owner/Admin
        ↓ team-scoped לפי organization_id
        ↓
12. משתמש יכול ליצור אירועים ✓
```

---

## ארכיטקטורת המערכת

### שכבות הנתונים

```
┌─────────────────────────────────────────────────────┐
│                  PRODUCT CATALOG                     │
│  Product → ProductPlan → ProductEntitlement          │
│  (טמפלטים — לא ניתן לשינוי)                         │
└─────────────────────────┬───────────────────────────┘
                          │ grantProduct()
                          ▼
┌─────────────────────────────────────────────────────┐
│                 ACCOUNT ASSIGNMENT                   │
│  Account → AccountProduct (status, expires_at,       │
│                            granted_by)               │
│  Account → AccountSubscription (Trial/Active/...)    │
└─────────────────────────┬───────────────────────────┘
                          │ propagate entitlements
                          ▼
┌─────────────────────────────────────────────────────┐
│                 FEATURE ENTITLEMENTS                 │
│  AccountEntitlement (feature_key, value, type)       │
│  - product_entitlement_id SET   = מגיע ממוצר         │
│  - product_entitlement_id NULL  = override ידני       │
└─────────────────────────────────────────────────────┘
                          │
                          ▼ (trigger)
┌─────────────────────────────────────────────────────┐
│              SPATIE PERMISSIONS (team-scoped)        │
│  model_has_permissions WHERE team_id=organization_id │
│  PermissionSyncService ← AccountProductObserver      │
└─────────────────────────────────────────────────────┘
```

### מיפוי: מדיניות הרשאות

| בדיקה | Policy | משתמשת ב- | הסבר |
|-------|--------|-----------|------|
| יצירת/עדכון אירוע | `EventPolicy::create/update` | `$user->can('manage-event-guests')` | Spatie — נדרש מוצר פעיל |
| יצירת חשבון | `OrganizationPolicy::update` | `isOwnerOrAdmin()` — pivot role | **לא** Spatie — עובד לפני תשלום |
| ניהול ארגון | `OrganizationPolicy::initiateBilling` | `isOwnerOrAdmin()` — pivot role | לא Spatie |
| צפייה באירוע | `EventPolicy::view` | `can('view-event-details')` | Spatie — נדרש מוצר פעיל |

---

## קבצים ורכיבים

### קבצים חדשים

| קובץ | תפקיד |
|------|--------|
| `app/Services/PermissionSyncService.php` | לוגיקת הענקה/שלילת הרשאות |
| `app/Observers/AccountProductObserver.php` | מפעיל את ה-sync בשינוי AccountProduct |
| `resources/lang/he/billing.php` | הודעות billing בעברית |
| `resources/lang/en/billing.php` | הודעות billing באנגלית |

### קבצים ששונו

| קובץ | שינוי |
|------|-------|
| `app/Http/Controllers/Dashboard/EventController.php` | catch `AuthorizationException` → redirect billing |
| `app/Livewire/Billing/AccountOverview.php` | הסרת `grantOwnerPermissions()` |
| `app/Livewire/Organizations/Create.php` | הסרת הקצאת הרשאות בשלב יצירת org |
| `app/Providers/AppServiceProvider.php` | רישום `AccountProduct::observe()` |
| `resources/views/pages/billing/account.blade.php` | banner כתום להודעת warning |
| `app/Http/Controllers/Auth/RegisterController.php` | StoreRegisterRequest + first+last name |
| `app/Http/Requests/Auth/StoreRegisterRequest.php` | FormRequest לרישום |

---

## שני נתיבי ההפעלה

### Flow A — Subscription (SubscriptionService)

```php
// 1. משתמש מצטרף לתוכנית
$account->subscribeToPlan($plan);
// → AccountSubscription::status = Trial

// 2. לאחר תשלום / הפעלה ידנית
app(SubscriptionService::class)->activate($subscription, $adminUser);
// → AccountSubscription::status = Active
// → Account::grantProduct($plan->product, $adminId)
//   → AccountProduct::create(['status' => AccountProductStatus::Active])
//   → AccountProductObserver::created() ← מופעל כאן
//   → PermissionSyncService::syncForAccount($account)
```

### Flow B — Manual Admin Grant

```php
// System admin מקצה מוצר ישירות
$account->grantProduct(
    product: $product,
    grantedBy: auth()->id(),  // ← חובה כדי לעמוד בתנאי hasActivePaidOrGranted
);
// → AccountProduct::create(['status' => active, 'granted_by' => $adminId])
// → AccountProductObserver::created() ← מופעל כאן
// → PermissionSyncService::syncForAccount($account)
```

> **חשוב:** ב-Flow B — `granted_by` **חובה** להיות מוגדר כדי שהתנאי יתקיים ללא תשלום.  
> קריאה ל-`grantProduct()` ללא `grantedBy` ובלי תשלום **לא** תעניק הרשאות.

---

## תנאי ההרשאה

### `PermissionSyncService::hasActivePaidOrGranted()`

```php
public function hasActivePaidOrGranted(Account $account): bool
{
    // תנאי בסיס: חייב להיות AccountProduct פעיל (לא פג, לא מושהה)
    $activeProducts = $account->activeAccountProducts();
    if (! $activeProducts->exists()) {
        return false;
    }

    // נתיב A: תשלום מוצלח קיים בחשבון
    $hasPaid = $account->payments()
        ->where('status', PaymentStatus::Succeeded->value)
        ->exists();
    if ($hasPaid) {
        return true;
    }

    // נתיב B: מוצר הוקצה ידנית ע"י אדמין (granted_by מוגדר)
    return $activeProducts->whereNotNull('granted_by')->exists();
}
```

### `Account::activeAccountProducts()` — הגדרה

```php
// רק AccountProducts שמקיימים את שני התנאים:
WHERE status = 'active'
  AND (expires_at IS NULL OR expires_at > NOW())
```

---

## שלילת הרשאות

הרשאות נשללות אוטומטית כאשר:

| טריגר | קריאה ל-Observer | תוצאה |
|--------|-----------------|--------|
| `AccountProduct::status` → `suspended` | `updated()` | `syncForAccount()` → revoke |
| `AccountProduct::status` → `revoked` | `updated()` | `syncForAccount()` → revoke |
| `AccountProduct::expires_at` עבר | `updated()` | `syncForAccount()` → revoke |
| `AccountProduct` נמחק | `deleted()` | `syncForAccount()` → revoke אם אין אחרים |
| `SubscriptionService::cancel()` | דרך `grantProduct()` revoke | revoke products → Observer → revoke permissions |
| `SubscriptionService::suspend()` | דרך status change | `past_due` → Observer → revoke |

---

## AccountProductObserver

**קובץ:** `app/Observers/AccountProductObserver.php`  
**רישום:** `AppServiceProvider::boot()` — `AccountProduct::observe(AccountProductObserver::class)`

```php
final class AccountProductObserver
{
    public function __construct(
        private readonly PermissionSyncService $sync,
    ) {}

    // AccountProduct חדש — sync אם כבר active
    public function created(AccountProduct $accountProduct): void
    {
        if ($accountProduct->status === AccountProductStatus::Active) {
            $this->sync->syncForAccount($accountProduct->account);
        }
    }

    // שינוי סטטוס או תאריך תפוגה
    public function updated(AccountProduct $accountProduct): void
    {
        if ($accountProduct->wasChanged('status') || $accountProduct->wasChanged('expires_at')) {
            $this->sync->syncForAccount($accountProduct->account);
        }
    }

    // מחיקה — ייתכן שנדרש לשלול הרשאות
    public function deleted(AccountProduct $accountProduct): void
    {
        $this->sync->syncForAccount($accountProduct->account);
    }
}
```

---

## PermissionSyncService

**קובץ:** `app/Services/PermissionSyncService.php`

### הרשאות מנוהלות (`TENANT_PERMISSIONS`)

```php
private const TENANT_PERMISSIONS = [
    'view-event-details',    // צפייה בפרטי אירוע
    'manage-event-guests',   // ניהול אורחים (יצירת אירוע)
    'manage-event-tables',   // ניהול שולחנות
    'send-invitations',      // שליחת הזמנות
];
```

### משתמשים מקבלים הרשאות

רק משתמשים עם role של **Owner** או **Admin** ב-`organization_users`:

```php
$organization->users()
    ->wherePivotIn('role', [
        OrganizationUserRole::Owner->value,
        OrganizationUserRole::Admin->value,
    ])
    ->each(fn (User $user) => $user->givePermissionTo($permissions));
```

### Team Scoping

הרשאות ניתנות **תמיד** בהקשר של `organization_id` כ-team:

```php
app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
```

כך שמשתמש יכול להיות Owner בארגון אחד (עם הרשאות) ו-Viewer בארגון אחר (ללא הרשאות).

---

## redirect לעמוד Billing

### EventController

```php
try {
    $this->authorize('create', [Event::class, $organization->id]);
} catch (AuthorizationException) {
    return redirect()->route('billing.account')
        ->with('warning', __('billing.event_creation_requires_account'));
}
```

### billing/account.blade.php — Banner

```html
@if (session('warning'))
    <div class="... rounded-xl border border-amber-200 bg-amber-50 text-amber-800 ...">
        <!-- אייקון + הודעה -->
        {{ session('warning') }}
    </div>
@endif
```

### הודעות תרגום

| שפה | מחרוזת |
|-----|---------|
| עברית | כדי ליצור אירועים נדרש חשבון פעיל. אנא הגדירו חשבון ארגון לפני שתמשיכו. |
| אנגלית | An active account is required to create events. Please set up your organization account first. |

---

## שגיאות נפוצות ופתרונות

### 1. משתמש עדיין לא יכול ליצור אירועים אחרי תשלום

**בדיקה:**
```php
// ב-Tinker
$account = \App\Models\Account::find($accountId);
app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($orgId);

echo app(\App\Services\PermissionSyncService::class)
    ->hasActivePaidOrGranted($account) ? 'TRUE' : 'FALSE';
```

**סיבות אפשריות:**
- `AccountProduct` עדיין לא נוצר / סטטוסו אינו `active`
- תשלום אינו ב-`Succeeded` (אולי `Processing`)
- `granted_by` הוא `null` בהקצאה ידנית

**פתרון ידני:**
```php
app(\App\Services\PermissionSyncService::class)->syncForAccount($account);
```

---

### 2. משתמש חדש ב-org קיים לא מקבל הרשאות

ה-Observer מופעל רק בשינוי `AccountProduct`. משתמש חדש שמצטרף ל-org לאחר שהמוצר כבר פעיל לא יקבל הרשאות אוטומטית.

**פתרון:** בעת הוספת משתמש ל-org, לקרוא ל-`syncForAccount` מפורשות, או להפעיל re-sync מהממשק של System Admin.

---

### 3. `OrganizationPolicy::update` מחזיר false לא-Owner

`isOwnerOrAdmin()` בודק את pivot role — לא Spatie. אם המשתמש אינו Owner/Admin בטבלת `organization_users`, הוא לא יוכל ליצור Account.

---

### 4. הרשאות לא נשללות אחרי ביטול מנוי

וודא ש-`SubscriptionService::cancel()` קורא ל-`$account->revokeProduct()` או משנה את `AccountProduct::status` ל-`revoked`.  
ה-Observer יטפל בשאר אוטומטית.

---

## בדיקות ואימות

### בדיקה מהירה ב-Tinker

```php
// בדוק מצב משתמש
$user = \App\Models\User::where('email', 'user@example.com')->first();
$org  = $user->currentOrganization;

app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($org->id);
$user->unsetRelation('permissions')->unsetRelation('roles');

dump([
    'has_account'       => $org->account ? 'YES' : 'NO',
    'has_active_product'=> $org->account?->activeAccountProducts()->exists() ?? false,
    'has_paid'          => $org->account?->payments()
                              ->where('status', 'succeeded')->exists() ?? false,
    'permissions'       => $user->getAllPermissions()->pluck('name'),
    'can_create_event'  => $user->can('manage-event-guests'),
]);
```

### כפות ידנית לאחר תשלום (אם Observer לא הופעל)

```php
$account = \App\Models\Organization::find($orgId)->account;
app(\App\Services\PermissionSyncService::class)->syncForAccount($account);
```

### שלילת הרשאות ידנית (לצורכי debug)

```php
$org = \App\Models\Organization::find($orgId);
app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($org->id);
$user->revokePermissionTo(['view-event-details','manage-event-guests','manage-event-tables','send-invitations']);
```

---

## שיפורים מומלצים

> נקודות אלו עלו בסקירה טכנית (ראו `BILLING_PERMISSION_GATE-Review.md`) ומהוות עבודה עתידית, לא חובה לשחרור הנוכחי.

---

### 1. Observer — Guard מפני null + debounce בעתיד

כרגע `AccountProductObserver` קורא לסנכרון ישירות. בסביבות עם batch updates (שינוי מרובה של AccountProducts בלולאה), ייתכן שהסנכרון יופעל מספר פעמים.

**Guard מינימלי מומלץ (כבר קיים):**
```php
if ($accountProduct->account?->exists) {
    $this->sync->syncForAccount($accountProduct->account);
}
```

**לכשמספר הארגונים לחשבון יגדל:** שקול להפוך את `syncForAccount()` ל-Job מוגבל ב-queue עם unique job key.

---

### 2. `hasActivePaidOrGranted()` — שתי שאילתות בכל קריאה

הפונקציה מריצה שתי שאילתות (`activeAccountProducts`, `payments`). אם `EventPolicy::create()` נקרא בכל render של Livewire, עלול להיות עומס.

**פתרון מומלץ:** cache קצר לפי `account_id`:

```php
public function hasActivePaidOrGranted(Account $account): bool
{
    return cache()->remember(
        "billing.gate.{$account->id}",
        now()->addSeconds(60),
        fn () => $this->computeGate($account),
    );
}

private function computeGate(Account $account): bool
{
    // ... לוגיקה קיימת ...
}
```

> **חשוב:** לנקות את ה-cache בעת `syncForAccount()`:
> ```php
> cache()->forget("billing.gate.{$account->id}");
> ```

---

### 3. `ROLE_PERMISSION_MAP` — הרחבה עתידית

כרגע Owner ו-Admin מקבלים את **אותן** הרשאות. אם בעתיד יתווספו roles כמו `EventManager` או `Staff` עם subset של הרשאות, כדאי להחליף את הרשימה הקשיחה ב-map:

```php
// עתידי — לא בקוד הנוכחי
private const ROLE_PERMISSION_MAP = [
    OrganizationUserRole::Owner->value => self::TENANT_PERMISSIONS,
    OrganizationUserRole::Admin->value => self::TENANT_PERMISSIONS,
    // OrganizationUserRole::EventManager->value => ['view-event-details', 'manage-event-guests'],
];
```

כך ניתן להרחיב ללא שינוי לוגיקה.

---

### 4. משתמש שמצטרף לארגון אחרי שמוצר כבר פעיל ⚠️

**בעיה:** ה-Observer מופעל רק בשינוי `AccountProduct`. משתמש חדש שמצטרף לארגון לאחר שהמוצר כבר פעיל **לא מקבל הרשאות אוטומטית**.

**פתרון מומלץ:** Observer על `OrganizationUser`:

```php
// app/Observers/OrganizationUserObserver.php
final class OrganizationUserObserver
{
    public function __construct(
        private readonly PermissionSyncService $sync,
    ) {}

    public function created(OrganizationUser $organizationUser): void
    {
        $account = $organizationUser->organization->account;
        if ($account) {
            $this->sync->syncForAccount($account);
        }
    }

    public function updated(OrganizationUser $organizationUser): void
    {
        // שינוי role — ייתכן שצריך לעדכן הרשאות
        if ($organizationUser->wasChanged('role')) {
            $account = $organizationUser->organization->account;
            if ($account) {
                $this->sync->syncForAccount($account);
            }
        }
    }
}
```

**רישום:**
```php
// AppServiceProvider::boot()
OrganizationUser::observe(OrganizationUserObserver::class);
```

**עד שיוטמע:** ניתן להריץ re-sync ידני דרך System Admin panel.

---

### 5. אבטחה — ההפרדה בין pivot role ל-Spatie היא כוונתית

> מציטוט הסקירה: *"ההחלטה להפריד בין Policy שמסתמך על pivot roles (לפני תשלום) לבין Policy שמסתמך על Spatie permissions (אחרי תשלום) היא בחירה נכונה. היא מונעת bootstrap deadlock שבו משתמש לא יכול ליצור חשבון כי עדיין אין לו permissions."*

זהו עיצוב מכוון ויש לשמור עליו:
- `OrganizationPolicy::update` ← **pivot role** (עובד לפני תשלום)
- `EventPolicy::create` ← **Spatie** (נדרש מוצר פעיל)

---



```
                    ┌─────────────────────┐
                    │   User Registration  │
                    │  RegisterController  │
                    │  first_name+last_name│
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │   Create Organization│
                    │  Organizations/Create│
                    │  pivot: Owner role   │
                    │  NO Spatie perms yet │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
           ┌───YES──┤ Try /events/create  │
           │        │ EventPolicy::create │
           │        │ can('manage-event-  │
           │        │  guests') = FALSE   │
           │        └──────────┬──────────┘
           │                   │ NO
           │        ┌──────────▼──────────┐
           │        │ redirect /billing   │
           │        │ + amber warning     │
           │        └──────────┬──────────┘
           │                   │
           │        ┌──────────▼──────────┐
           │        │ AccountOverview      │
           │        │ OrganizationPolicy   │
           │        │ ::update (pivot) ✓  │
           │        │ "Create account"    │
           │        └──────────┬──────────┘
           │                   │
           │        ┌──────────▼──────────┐
           │        │   Account created   │
           │        │   org.account_id    │
           │        │   NO perms yet      │
           │        └──────────┬──────────┘
           │                   │
           │          ┌────────┴──────────┐
           │          │                   │
           │   ┌──────▼──────┐  ┌─────────▼──────┐
           │   │  Flow A      │  │   Flow B        │
           │   │ Subscription │  │ Manual Admin    │
           │   │ + Payment    │  │ grantProduct(   │
           │   │ Succeeded    │  │   grantedBy=id) │
           │   └──────┬──────┘  └─────────┬───────┘
           │          └────────────────────┘
           │                   │
           │        ┌──────────▼──────────┐
           │        │ AccountProduct       │
           │        │ status = active      │
           │        └──────────┬──────────┘
           │                   │
           │        ┌──────────▼──────────┐
           │        │AccountProductObserver│
           │        │::created()          │
           │        └──────────┬──────────┘
           │                   │
           │        ┌──────────▼──────────┐
           │        │PermissionSyncService │
           │        │hasActivePaidOrGranted│
           │        │  paid? OR granted_by?│
           │        └──────────┬──────────┘
           │                   │ TRUE
           │        ┌──────────▼──────────┐
           │        │ givePermissionTo()   │
           │        │ team_id=org.id       │
           │        │ Owner + Admin users  │
           │        └──────────┬──────────┘
           │                   │
           └───────────────────┘
                    ✓ Create Events
```

---

*תיעוד זה מכסה את כל השינויים שבוצעו ב-commits `32311744`–`e4638f23`. לפרטים נוספים על מודל הנתונים ראו `docs/MODELS_ORGANIZATION_ACCOUNT_USER_ANALYSIS.md`.*
