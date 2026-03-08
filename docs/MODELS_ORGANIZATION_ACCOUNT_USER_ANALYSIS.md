# דוח ניתוח: מודלי Organization, OrganizationUser, Account, AccountFeatureUsage, AccountEntitlement, User

**תאריך:** 2026-03-04  
**מטרה:** בדיקת חיבור הקבצים למערכת, זרימת נתונים ויחסים בין המודלים.

---

## 1. סיכום מנהלים

| מודל | מחובר למערכת? | שימוש ישיר | שימוש דרך יחסים | הערות |
|------|---------------|------------|------------------|--------|
| **User** | ✅ כן | רוחב (Auth, Policies, Livewire, Seeders) | כל המודלים האחרים | ליבת אימות ורב-דייר |
| **Organization** | ✅ כן | API, Livewire, Config, Tests | User, Account, Event, EventBilling, Payment | ליבת multi-tenant |
| **OrganizationUser** | ⚠️ עקיף | רק כ־pivot ב־Organization/User | — | אין שימוש ב־`OrganizationUser::` ישיר; רק טבלת pivot |
| **Account** | ✅ כן | Livewire System/Billing, Models | Organization, Entitlements, Usage, EventBilling, Payment | שכבת billing/entitlements |
| **AccountEntitlement** | ✅ כן | Livewire Billing/EntitlementsIndex, System/Accounts/Show | Account, ProductEntitlement | ניהול הרשאות לפי חשבון |
| **AccountFeatureUsage** | ✅ כן | דרך `$account->featureUsage()` | Account | קריאה ב־System/Accounts/Show, Billing/UsageIndex |

**מסקנה:** כל ששת הקבצים מחוברים למערכת. **OrganizationUser** מחובר רק דרך יחסי Eloquent (pivot) ולא נקרא ישירות כ־Model במעבדים/בקרים.

---

## 2. ניתוח לפי קובץ

### 2.1 `app/Models/User.php`

- **מחובר:** ✅ כן.
- **יחסים מוגדרים:**
  - `ownedOrganizations()` → Organization (pivot: organization_users, role = owner)
  - `organizations()` → Organization (pivot: organization_users)
  - `currentOrganization()` → Organization (לוגיקה: current_organization_id + impersonation)
- **שימוש במערכת:**
  - **Auth:** LoginController, RegisterController, PasswordController, VerificationController, VerifyEmailController.
  - **Middleware:** EnsureOrganizationSelected, ImpersonationExpiry, EnsureSystemAdmin.
  - **Policies:** EventPolicy, GuestPolicy, OrganizationPolicy, PaymentPolicy (כולם מקבלים `User $user`).
  - **Livewire:** Dashboard, Organizations (Create, Index), Profile, System (Dashboard, Users, Organizations, Accounts).
  - **Controllers:** OrganizationSwitchController, SystemImpersonationController, CheckoutTokenizeController.
  - **Services:** OrganizationContext.
  - **Seeders:** DatabaseSeeder, InitialAdminSeeder, CheckoutWorkflowSeeder.
- **זרימה:** User הוא צומת מרכזי: אימות → בחירת ארגון → גישה ל־Organization ו־Account (דרך ארגון).

---

### 2.2 `app/Models/Organization.php`

- **מחובר:** ✅ כן.
- **יחסים:**
  - `account()` → Account
  - `users()` → User (pivot: organization_users)
  - `events()` → Event
  - `eventsBilling()` → EventBilling
  - `payments()` → Payment
  - `owner()` → User (דרך pivot role = owner)
- **שימוש במערכת:**
  - **API:** `OrganizationController` (show, update); כל routes תחת `api/organizations/{organization}/...` (events, guests, checkout וכו').
  - **Web:** `OrganizationSettingsController`, `OrganizationSwitchController`, `CheckoutTokenizeController` (checkout/{organization}/{event}).
  - **Livewire:** Organizations/Create, System/Organizations/Index, System/Organizations/Show, System/Dashboard, System/Accounts/Show (ארגונים של account).
  - **Models:** Event, EventBilling, Payment (belongsTo Organization); User (belongsToMany); OrganizationContext.
  - **Config:** `config/officeguy.php` – customer_model_class = Organization.
  - **Tests:** GuestImportTest, SumitProductionValidationTest.
- **זרימה:** Organization מקושר ל־Account (account_id). גישה לארגון דרך User → currentOrganization() או דרך API/ממשק.

---

### 2.3 `app/Models/OrganizationUser.php`

- **מחובר:** ⚠️ **עקיף בלבד** (אין שימוש ישיר ב־`OrganizationUser::`).
- **שימוש:** הטבלה `organization_users` משמשת כ־**pivot** ב־:
  - `Organization::users()` (belongsToMany עם pivot role)
  - `User::organizations()`, `User::ownedOrganizations()`
  - `OrganizationPolicy::isOwnerOrAdmin()` – קורא ל־`$user->organizations()->where(...)->first()` ומשתמש ב־pivot role.
  - Livewire: `Organizations/Create` (attach עם role Owner), `System/Organizations/Show` (updateExistingPivot ל־role), `System/Organizations/Index` ו־`System/Dashboard` (where organization_users.role).
- **מסקנה:** המודל מחובר למערכת כ־**טבלת pivot**; אין קוד שקורא ל־`OrganizationUser::query()` או ל־`new OrganizationUser()`.

---

### 2.4 `app/Models/Account.php`

- **מחובר:** ✅ כן.
- **יחסים:**
  - `owner()` → User (owner_user_id)
  - `organizations()` → Organization
  - `eventsBilling()` → EventBilling
  - `payments()` → Payment
  - `entitlements()` → AccountEntitlement
  - `featureUsage()` → AccountFeatureUsage
  - `billingIntents()` → BillingIntent
- **שימוש במערכת:**
  - **Livewire:** System/Accounts/Index (רשימת accounts), System/Accounts/Show (פרטי account, entitlements, featureUsage), Billing/AccountOverview (יצירת account).
  - **Models:** Organization (belongsTo Account), EventBilling, Payment, BillingIntent, AccountEntitlement, AccountFeatureUsage (כולם מקושרים ל־Account).
- **Routes:** `system/accounts`, `system/accounts/{account}`.
- **זרימה:** Account הוא שכבת billing/entitlements; ארגון מקושר ל־Account דרך `organizations.account_id`. יצירת Account מ־Billing/AccountOverview; צפייה/עריכה ב־System ו־Billing.

---

### 2.5 `app/Models/AccountEntitlement.php`

- **מחובר:** ✅ כן.
- **יחסים:**
  - `account()` → Account
  - `productEntitlement()` → ProductEntitlement
- **שימוש במערכת:**
  - **Livewire:** Billing/EntitlementsIndex (CRUD: list, create, edit, delete לפי account), System/Accounts/Show (תצוגת entitlements של account).
  - **Models:** Account (hasMany AccountEntitlement), ProductEntitlement (hasMany AccountEntitlement).
- **זרימה:** כל פעולה על AccountEntitlement ממוקמת ב־account (tenant). אין גישה ישירה ל־entitlement ללא account.

---

### 2.6 `app/Models/AccountFeatureUsage.php`

- **מחובר:** ✅ כן.
- **יחסים:**
  - `account()` → Account
- **שימוש במערכת:**
  - **Livewire:** System/Accounts/Show (`$this->account->featureUsage()`), Billing/UsageIndex (`$account->featureUsage()`).
  - **Models:** Account (hasMany AccountFeatureUsage).
- **מסקנה:** שימוש **read-only** (תצוגת usage). אין קוד שכותב ישירות ל־`AccountFeatureUsage::create()` באפליקציה (ייתכן job/command חיצוני).
- **זרימה:** רק דרך Account; אין routes ישירים ל־AccountFeatureUsage.

---

## 3. זרימה ויחסים בין הקבצים

```
                    ┌─────────────┐
                    │    User     │
                    └──────┬──────┘
                           │
         ┌─────────────────┼─────────────────┐
         │                 │                 │
         ▼                 ▼                 ▼
  organization_users  current_organization  Account.owner_user_id
  (pivot)              (FK on users)         (owner)
         │                 │                 │
         ▼                 ▼                 ▼
┌────────────────┐  ┌──────────────┐  ┌─────────────┐
│  Organization  │◄─┤ User context │  │   Account   │
└────────┬───────┘  └──────────────┘  └──────┬──────┘
         │                                    │
         │ account_id                         │
         └────────────────┬───────────────────┘
                          │
         ┌────────────────┼────────────────────────┐
         ▼                ▼                        ▼
   Event, EventBilling   AccountEntitlement   AccountFeatureUsage
   Payment               (feature_key, value)  (usage_count, period)
```

**סיכום זרימה:**
1. **User** ↔ **Organization** דרך **organization_users** (pivot; תפקיד: owner/admin/editor/viewer).
2. **User** מחזיק **current_organization_id**; **OrganizationContext** קובע ארגון פעיל.
3. **Organization** שייך ל־**Account** (account_id); Account מחזיק sumit_customer_id ו־owner.
4. **Account** מחזיק **AccountEntitlement** ו־**AccountFeatureUsage** (תצוגה/ניהול ב־System ו־Billing).

---

## 4. טבלאות מסד נתונים רלוונטיות

| טבלה | מודל | Migration |
|------|------|-----------|
| users | User | (default) |
| organizations | Organization | create_organizations_table, add_account_id, add_is_suspended |
| organization_users | OrganizationUser | create_organization_users_table |
| accounts | Account | create_accounts_table, add_name |
| account_entitlements | AccountEntitlement | create_account_entitlements_table |
| account_feature_usage | AccountFeatureUsage | create_account_feature_usage_table |

---

## 5. Routes שמשפיעים על המודלים

| Route / קבוצה | מודלים רלוונטיים |
|----------------|-------------------|
| Auth (login, register, password, verify) | User |
| organizations, organizations/create, organizations/switch/{organization} | User, Organization |
| organization/settings | Organization |
| api/organizations/{organization}/* | Organization, User (auth) |
| checkout/{organization}/{event} | Organization, User |
| system/organizations, system/organizations/{organization} | Organization, User, OrganizationUser (pivot) |
| system/users, system/users/{user} | User |
| system/accounts, system/accounts/{account} | Account, Organization, AccountEntitlement, AccountFeatureUsage |
| Billing (EntitlementsIndex, UsageIndex, AccountOverview) | Account, AccountEntitlement, AccountFeatureUsage |

---

## 6. המלצות

1. **OrganizationUser:** אם נדרש לוגיקה ישירה על שורות pivot (למשל אירועים על שינוי תפקיד), אפשר להגדיר Listener/Observer על המודל; כרגע המערכת עובדת רק דרך `$organization->users()` ו־`$user->organizations()`.
2. **AccountFeatureUsage:** אם יש כתיבה ל־usage ממקור חיצוני (Job, Command, חבילה), מומלץ לתעד; באפליקציה נצפה רק קריאה דרך `$account->featureUsage()`.
3. **עקביות:** Organization מקושר ל־Account; וידוא שכל יצירת Organization (למשל ב־Organizations/Create) מקצה account_id בהתאם למדיניות (אם נדרש account לכל ארגון).

---

## 7. תהליך יצירת "לקוח" (Customer Creation Flow)

בהקשר האפליקציה יש שלוש ישויות שניתן לכנות "לקוח" בהקשרים שונים; הזרימה המלאה מתחילה ב־**משתמש** (User), עוברת ל־**ארגון** (Organization) ורק בהמשך אופציונלי ל־**חשבון חיוב** (Account).

### 7.1 הגדרת "לקוח" בהקשר המערכת

| ישות | תפקיד | איפה נוצר | קישור ל-SUMIT/חיוב |
|------|--------|-----------|---------------------|
| **User** | משתמש מאומת (לוגין) | הרשמה (`RegisterController`) | אין ישיר; גישה דרך ארגון |
| **Organization** | דייר (tenant) – יחידת עבודה (אירועים, אורחים) | יצירת ארגון (`Organizations/Create`) | ב־`config/officeguy.php`: `customer_model_class` = Organization |
| **Account** | שכבת חיוב/הרשאות (entitlements, usage, sumit_customer_id) | פעולה מפורשת בדף Billing (`AccountOverview::createAccount`) | `Account.sumit_customer_id` – מזהה לקוח ב-SUMIT |

**מסקנה:** "לקוח" לצורכי תשלום/חיוב מזוהה ב־**Account** (ו־`sumit_customer_id`); ב־config החבילה OfficeGuy/SUMIT מוגדרת לעבוד עם **Organization** כ־customer model, בעוד ש־**Account** הוא זה שמחזיק את `sumit_customer_id`. יצירת "לקוח" מלא לצורכי חיוב = יצירת **Account** וקישורו ל־Organization.

---

### 7.2 זרימה מלאה: מהרשמה עד חשבון חיוב

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 1. הרשמה (Register)                                                              │
│    RegisterController::store()                                                     │
│    → User::create(name, email, password)                                           │
│    → Auth::login($user)                                                            │
│    → redirect()->intended(route('dashboard'))                                      │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 2. כניסה ל-Dashboard + Middleware (EnsureOrganizationSelected)                   │
│    אם user->organizations()->count() === 0  →  redirect(organizations.create)     │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 3. יצירת ארגון ראשון (Organizations/Create)                                       │
│    Livewire: Organizations\Create::save()                                         │
│    → Organization::create(['name' => $name, 'slug' => Str::slug($name)])          │
│       ⚠️ ללא account_id (null)                                                     │
│    → auth()->user()->organizations()->attach($organization->id, ['role' => Owner]) │
│       (רישום ב־organization_users כ־Owner)                                         │
│    → auth()->user()->update(['current_organization_id' => $organization->id])     │
│    → OrganizationContext::set($organization)                                       │
│    → redirect(dashboard)                                                           │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 4. גישה ל-Dashboard עם ארגון פעיל                                                 │
│    current_organization_id מוגדר; OrganizationContext::current() מחזיר את הארגון  │
│    ארגון עדיין ללא Account (account_id = null)                                     │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 5. יצירת חשבון חיוב (רק בפעולה מפורשת)                                           │
│    משתמש נכנס ל־Billing & Entitlements (route: billing.account)                   │
│    דף: pages/billing/account.blade.php  →  Livewire: Billing\AccountOverview     │
│    אם $organization->account_id === null: מוצג כפתור "Create account for this     │
│    organization"                                                                   │
│    → createAccount():                                                             │
│       → Account::create(['type' => 'organization', 'name' => $org->name,           │
│                          'owner_user_id' => $organization->owner()?->id])          │
│       → $organization->update(['account_id' => $account->id])                      │
│    → redirect(billing.account)                                                    │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

### 7.3 נקודות יצירה בקוד

| ישות | קובץ | מתודה/מקום | תנאי/הערה |
|------|------|------------|-----------|
| **User** | `App\Http\Controllers\Auth\RegisterController` | `store()` | אחרי ולידציה; לא יוצר Organization או Account |
| **Organization** | `App\Livewire\Organizations\Create` | `save()` | רק `name` + `slug`; **לא** מגדיר `account_id` |
| **OrganizationUser** | אותו Create | `attach(..., ['role' => Owner])` | נוצר כחלק מ־pivot ב־יצירת ארגון |
| **Account** | `App\Livewire\Billing\AccountOverview` | `createAccount()` | רק אם `$organization->account_id === null`; דורש `authorize('update', $organization)` |

**אין** יצירה אוטומטית של Account ביצירת Organization. Account נוצר רק כאשר המשתמש נכנס לדף Billing ולוחץ על "Create account for this organization".

---

### 7.4 סיכום זרימה ותלויות

1. **User** נוצר בהרשמה בלבד.
2. **Organization** נוצר ב־Livewire Organizations/Create; נשאר עם `account_id = null` עד שלב 5.
3. **organization_users** מתעדכן אוטומטית ב־attach (Owner) ב־Create.
4. **Account** נוצר רק מ־Billing/AccountOverview, ומיד אחרי ה־create מתבצעת `organization->update(['account_id' => $account->id])`.
5. **AccountEntitlement** ו־**AccountFeatureUsage** לא נוצרים במסגרת תהליך "יצירת לקוח"; הם נוצרים/נקראים בהקשרי Billing/Entitlements ו־Usage בנפרד.

**פער מתועד:** ארגון יכול להתקיים **בלי** Account. במקרה כזה, דפי Billing שמסתמכים על `$organization->account` יציגו "No account" ויציעו יצירה ידנית. אם רוצים שכל ארגון יקבל Account אוטומטית, יש להוסיף ב־`Organizations\Create::save()` יצירת Account וקישור `account_id` (בהתאם למדיניות המוצר).

---

*דוח זה נוצר על בסיס סריקת קוד (app/, routes/, config/, database/, tests/) וניתוח יחסי Eloquent והפניות למודלים.*
