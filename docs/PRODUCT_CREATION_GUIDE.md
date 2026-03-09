# מדריך כללי: הוספה ויצירה של מוצר במערכת

## מטרת המסמך

מסמך זה מתאר איך מוסיפים מוצר חדש למערכת המוצרים של Kalfa, בצורה שתואמת לארכיטקטורה הקיימת ב־`app/Livewire/System/Products`.

המדריך מיועד לכל מוצר, לא רק `Twilio SMS`.

---

## עקרון דומייני מרכזי

במערכת הזו `Product` הוא ה־aggregate root.

המשמעות:

- לא בונים מוצר כטופס זמני שמוחזק במערכים
- שלב 1 ב־wizard יוצר רשומת `products` אמיתית
- כל שלב המשך עובד מול `$this->product`
- יכולות, מגבלות וקונפיגורציות נשמרות דרך relationships

זה מאפשר:

- recovery של draft
- autosave עתידי
- audit trail עקבי
- background processing
- collaboration בהמשך

---

## מפת הקבצים הרלוונטיים

### רכיבי Livewire

- `app/Livewire/System/Products/Index.php`
- `app/Livewire/System/Products/CreateProductWizard.php`
- `app/Livewire/System/Products/Show.php`

### מודלים

- `app/Models/Product.php`
- `app/Models/ProductEntitlement.php`
- `app/Models/ProductLimit.php`
- `app/Models/ProductFeature.php`
- `app/Models/ProductPlan.php`
- `app/Models/ProductPrice.php`
- `app/Models/AccountProduct.php`
- `app/Models/AccountSubscription.php`
- `app/Models/UsageRecord.php`

### תצוגות

- `resources/views/livewire/system/products/create-product-wizard.blade.php`
- `resources/views/livewire/system/products/show.blade.php`

---

## מודל הנתונים

המוצר עצמו נשמר בטבלת `products`.

למוצר יש שלושה סוגי הרחבות עיקריים:

1. `entitlements`
   - הרשאות או capabilities שהמוצר מעניק

2. `limits`
   - מגבלות כמותיות או operational ceilings

3. `features`
   - קונפיגורציות או toggles משלימים

ברמת ה־runtime, המערכת כבר לא נשענת רק על `AccountEntitlement`, אלא על שכבת assignment מפורשת:

1. `account_products`
   - מייצג אילו מוצרים הוקצו לחשבון

2. `account_entitlements`
   - מייצג את היכולות האפקטיביות של החשבון בזמן ריצה

בנוסף, קיימת עכשיו גם שכבה מסחרית:

1. `product_plans`
   - מייצג שכבות מסחריות של אותו מוצר

2. `product_prices`
   - מייצג מחירים למחזורי חיוב שונים

3. `account_subscriptions`
   - מייצג מנוי של חשבון לתוכנית מסוימת

4. `usage_records`
   - מייצג usage granular לצורך limits, reporting ו־future billing

במודל [Product.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/Product.php) הקשרים הם:

```php
public function entitlements(): HasMany
{
    return $this->hasMany(ProductEntitlement::class, 'product_id');
}

public function limits(): HasMany
{
    return $this->hasMany(ProductLimit::class, 'product_id');
}

public function features(): HasMany
{
    return $this->hasMany(ProductFeature::class, 'product_id');
}
```

---

## מתי מוסיפים מוצר חדש

מוסיפים מוצר חדש כאשר יש יכולת עסקית שצריכה להתנהל כישות עצמאית בקטלוג, למשל:

- ערוץ תקשורת חדש
- מודול billing חדש
- add-on לחשבון
- integration capability
- operational package עם quota והרשאות

לא מוסיפים מוצר חדש כאשר מדובר רק בפרמטר קטן בתוך מוצר קיים. במקרה כזה עדיף להוסיף entitlement, limit או feature למוצר קיים.

---

## מבנה מומלץ של מוצר

לכל מוצר חדש כדאי להגדיר לפחות:

- `name`
- `slug`
- `description`
- `category`
- `status`

ערכים מומלצים:

- `name`: שם עסקי ברור
- `slug`: מזהה טכני יציב, lowercase, עם `-`
- `description`: תיאור קצר של היכולת העסקית
- `category`: קבוצת שיוך לוגית
- `status`: להתחיל ב־`draft`, ורק אחרי review להעביר ל־`active`

אם מדובר במוצר מסחרי מלא, כדאי להגדיר גם:

- לפחות `ProductPlan` אחד
- לפחות `ProductPrice` פעיל אחד לכל plan מסחרי
- limits ברמת plan כאשר המחיר משתנה לפי tier

---

## תהליך יצירה דרך ה־UI

### שלב 1: Product Info

הקומפוננטה [CreateProductWizard.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/CreateProductWizard.php) יוצרת את המוצר כבר בשלב הראשון:

```php
$this->product = Product::create([
    'name' => $this->name,
    'slug' => Str::slug($this->slug),
    'description' => $validated['description'] ?: null,
    'category' => $validated['category'] ?: null,
    'status' => ProductStatus::Draft,
]);
```

מה לעשות בפועל:

1. להיכנס ל־System → Products
2. ללחוץ `Create Product`
3. להזין:
   - שם מוצר
   - slug
   - description
   - category
4. להמשיך לשלב הבא

בשלב הזה כבר נוצר `Product` אמיתי במסד הנתונים.

### שלב 2: Entitlements

בשלב הזה מוסיפים capabilities דרך:

```php
$this->product->entitlements()->create([...]);
```

דוגמאות ל־entitlements:

- `whatsapp_enabled = true`
- `api_access_enabled = true`
- `seats_limit = 100`
- `priority_support = true`

לכל entitlement רצוי להגדיר:

- `feature_key`
- `label`
- `value`
- `type`
- `description`

### שלב 3: Limits / Features

אם המיגרציות קיימות, אפשר להוסיף:

- `limits()` עבור quotas ומגבלות
- `features()` עבור flags או קונפיגורציות נוספות

דוגמאות:

- `monthly_sms_limit = 5000`
- `max_events_per_month = 20`
- `record_calls = true`
- `allow_branding_override = true`

אם חסרות הטבלאות `product_limits` או `product_features`, ה־wizard חוסם את הפעולות האלה ומציג הודעה מתאימה עד להרצת migrations.

### שלב 4: Review and Publish

בשלב האחרון מתבצע publish:

```php
$this->product->update([
    'status' => ProductStatus::Active,
]);
```

רק אחרי review נכון להעביר את המוצר מ־`draft` ל־`active`.

---

## תהליך יצירה דרך Seeder

כאשר המוצר הוא חלק מיכולות core של המערכת, עדיף לייצר אותו גם דרך Seeder אידמפוטנטי.

Seeder טוב צריך:

- למצוא מוצר קיים לפי `slug` או `name`
- לנרמל אותו אם הוא קיים
- ליצור אותו אם הוא לא קיים
- לסנכרן entitlements צפויים

דוגמה כללית:

```php
$product = Product::query()
    ->where('slug', 'example-product')
    ->first();

if ($product === null) {
    $product = new Product;
}

$product->fill([
    'name' => 'Example Product',
    'slug' => 'example-product',
    'description' => 'Example product description.',
    'category' => 'Example',
    'status' => ProductStatus::Active,
])->save();

$product->entitlements()->updateOrCreate(
    ['feature_key' => 'example_enabled'],
    [
        'label' => 'Example Enabled',
        'value' => 'true',
        'type' => EntitlementType::Boolean,
        'description' => 'Enables the example capability.',
        'is_active' => true,
    ]
);
```

Seeder מתאים במיוחד עבור:

- מוצרים שמגיעים עם המערכת
- bootstrap ל־production
- normalize של legacy data
- environments חדשים

אם מדובר במוצר מסחרי, ה־Seeder יכול וצריך גם ליצור:

- `ProductPlan`
- `ProductPrice`
- plan metadata עם limits

---

## איך לבחור בין Entitlement, Limit ו־Feature

### Entitlement

השתמש כאשר מדובר בהרשאה עסקית או capability שנבדקת בזמן ריצה.

דוגמאות:

- `twilio_enabled`
- `voice_rsvp_enabled`
- `api_access_enabled`

### Limit

השתמש כאשר מדובר במגבלה כמותית או quota.

דוגמאות:

- `monthly_sms_limit`
- `max_users`
- `max_events_per_month`

### Feature

השתמש כאשר מדובר בקונפיגורציה או toggle שאינו entitlement קלאסי.

דוגמאות:

- `record_calls`
- `custom_branding`
- `use_advanced_routing`

כלל אצבע:

- אם הקוד שואל "מותר או אסור?" → `entitlement`
- אם הקוד שואל "כמה מותר?" → `limit`
- אם הקוד שואל "איך לפעול?" → `feature`

---

## naming conventions מומלצות

כדי לשמור על מערכת עקבית:

- להשתמש ב־snake_case ל־keys
- להעדיף שמות ברורים ולא כלליים מדי
- להפריד בין enablement לבין quota

דוגמאות טובות:

- `sms_confirmation_enabled`
- `sms_confirmation_limit`
- `whatsapp_enabled`
- `record_calls`

דוגמאות פחות טובות:

- `sms`
- `enabled`
- `limit`
- `feature1`

---

## הקצאת מוצר לחשבון

יצירת מוצר בקטלוג לא מפעילה אותו אוטומטית עבור לקוח.

כדי שחשבון ישתמש במוצר:

1. נכנסים למסך החשבון
2. בוחרים את המוצר
3. המערכת מבצעת:

```php
$account->grantProduct($product);
```

מה שהמערכת עושה עכשיו בפועל:

1. יוצרת `AccountProduct`
2. מסמנת assignment כ־`active`
3. שומרת `granted_at`, `expires_at`, `granted_by`, `metadata`
4. מעתיקה `ProductEntitlement` ל־`AccountEntitlement`
5. שומרת `product_entitlement_id` לצורך traceability ו־resolution
6. מנקה cache של feature resolution עבור החשבון

כלומר, assignment של מוצר הוא כבר לא "רק העתקת entitlements", אלא ישות runtime בפני עצמה.

המודל הנכון הוא:

- `Product` = הגדרת קטלוג
- `AccountProduct` = assignment של מוצר לחשבון
- `AccountEntitlement` = אפקט runtime בפועל

---

## השכבה המסחרית

המערכת אינה רק product catalog. היא תומכת עכשיו גם במבנה commercial מלא:

- `ProductPlan`
- `ProductPrice`
- `AccountSubscription`
- `UsageRecord`

הזרימה הדומיינית היא:

```text
Product
  -> ProductPlan
      -> ProductPrice
          -> AccountSubscription
              -> AccountProduct
                  -> AccountEntitlement
                      -> FeatureResolver
```

### ProductPlan

[ProductPlan.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/ProductPlan.php) מייצג tier מסחרי של מוצר.

דוגמאות:

- `basic`
- `pro`
- `enterprise`

כל plan שייך ל־`Product` יחיד, ויכול להכיל metadata מסחרי כמו:

- plan limits
- bundle flags
- future billing configuration

דוגמה:

```php
$product->productPlans()->create([
    'name' => 'Pro',
    'slug' => 'pro',
    'description' => 'Professional tier',
    'is_active' => true,
    'metadata' => [
        'limits' => [
            'sms_confirmation_limit' => 1200,
        ],
    ],
]);
```

### ProductPrice

[ProductPrice.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/ProductPrice.php) מייצג מחיר עבור plan.

השדות המרכזיים:

- `currency`
- `amount`
- `billing_cycle`
- `is_active`
- `metadata`

מחזורי חיוב נתמכים:

- `monthly`
- `yearly`
- `usage`

דוגמה:

```php
$plan->prices()->create([
    'currency' => 'ILS',
    'amount' => 9900,
    'billing_cycle' => 'monthly',
    'is_active' => true,
]);
```

### AccountSubscription

[AccountSubscription.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/AccountSubscription.php) מייצג subscription של חשבון ל־plan.

statuses נתמכים:

- `trial`
- `active`
- `past_due`
- `cancelled`

כאשר subscription הופך ל־`active`, המערכת:

1. משמרת את מצב המנוי
2. יוצרת `AccountProduct`
3. מפיצה entitlements דרך `grantProduct()`
4. מפעילה resolution רגיל דרך `FeatureResolver`

בפועל:

```php
$subscription = $account->subscribeToPlan($plan);
$subscription->activate();
```

וכאשר מנוי מבוטל:

```php
$subscription->cancel();
```

ה־assignment הרלוונטי מסומן כ־revoked ולכן מפסיק להשתתף ב־runtime resolution.

---

## AccountProduct

המודל [AccountProduct.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/AccountProduct.php) מייצג הקצאת מוצר לחשבון.

השדות המרכזיים:

- `account_id`
- `product_id`
- `status`
- `granted_at`
- `expires_at`
- `granted_by`
- `metadata`

ה־statusים הנתמכים:

- `active`
- `suspended`
- `revoked`

זו השכבה שמאפשרת בעתיד:

- subscription lifecycle
- suspension / reactivation
- revoke מבלי למחוק היסטוריה
- billing linkage
- usage contracts

במילים אחרות:

- `Product` הוא catalog object
- `AccountProduct` הוא commercial/runtime assignment object

---

## Feature Resolution Layer

אחרי שמוצר מוקצה לחשבון, הקוד העסקי לא אמור לשאול:

- האם למוצר יש entitlement מסוים
- או לבצע query ישיר על `account_entitlements` בכל service

במקום זה, המערכת משתמשת עכשיו בשכבת resolution ייעודית:

- [FeatureResolver.php](/var/www/vhosts/kalfa.me/httpdocs/app/Services/FeatureResolver.php)
- [Feature.php](/var/www/vhosts/kalfa.me/httpdocs/app/Support/Feature.php)

ה־API הדומייני הוא:

```php
Feature::enabled($account, 'voice_rsvp_enabled');
Feature::allows($account, 'twilio_enabled');
Feature::value($account, 'routing_profile');
Feature::integer($account, 'sms_confirmation_limit');
```

### למה זה חשוב

השכבה הזו מנתקת את הקוד העסקי ממבנה האחסון.

כלומר:

- services ו־controllers עובדים מול שפה דומיינית
- אפשר לשנות מאחורי הקלעים את מודל האחסון בלי לשבור קוד עסקי
- יש מקום אחד אחיד להכניס expiration, overrides, casting, caching ו־audit

### כללי resolution הנוכחיים

ה־resolver מתנהג כך:

1. `Account override entitlement`
2. `Propagated entitlement` שהגיע מ־`AccountProduct`
3. `Plan limit` דרך `AccountSubscription`
4. `Product default entitlement` דרך assignment פעיל
5. `System default` מתוך config

בנוסף:

- entitlements שפג תוקפם נזנחים אוטומטית
- assignments שפג תוקפם לא משתתפים ב־resolution
- subscriptions לא פעילים לא משתתפים ב־plan resolution
- יש casting ל־`boolean`, `number`, `text`
- יש cache לפי `account_id + feature_key`

מפתח cache בפועל:

```text
feature:{account_id}:{feature_key}
```

TTL מוגדר ב:

- [product-engine.php](/var/www/vhosts/kalfa.me/httpdocs/config/product-engine.php)

ה־runtime API נשאר זהה:

```php
Feature::enabled($account, 'voice_rsvp_enabled');
Feature::allows($account, 'twilio_enabled');
Feature::integer($account, 'sms_confirmation_limit');
```

אבל נוספו גם helpers של usage:

```php
Feature::usage($account, 'voice_minutes', $subscription);
Feature::remaining($account, 'voice_minutes_limit', 'voice_minutes', $subscription);
Feature::allowsUsage($account, 'voice_minutes_limit', 'voice_minutes', 100, $subscription);
```

### דוגמאות runtime אמיתיות

הזרימות העסקיות הבאות כבר משתמשות בשכבה הזו:

- [CallingService.php](/var/www/vhosts/kalfa.me/httpdocs/app/Services/CallingService.php)
- [RsvpVoiceController.php](/var/www/vhosts/kalfa.me/httpdocs/app/Http/Controllers/Twilio/RsvpVoiceController.php)

במילים אחרות:

- קטלוג המוצר מגדיר capability
- החשבון מקבל state אפקטיבי
- ה־resolver מחזיר תשובה אחידה לקוד העסקי

זו שכבת התיווך שהופכת את מערכת המוצרים מ־catalog management ל־product engine אמיתי.

---

## Account Overrides

כעת אפשר להגדיר override ישיר לחשבון:

```php
$account->overrideFeature('voice_rsvp_enabled', false);
```

Override נשמר כ־`AccountEntitlement` ללא `product_entitlement_id`, ולכן הוא מקבל קדימות על entitlement propagated מהמוצר.

כלומר:

- catalog מגדיר ברירת מחדל
- assignment מפעיל את המוצר
- override מאפשר התאמה פרטנית לחשבון

זה pattern חשוב למערכות SaaS כי הוא מאפשר:

- exception per customer
- pilot features
- manual enablement
- זמני grace / rollback

בלי לפגוע בקטלוג עצמו.

---

## Expiration

גם `AccountProduct` וגם `AccountEntitlement` תומכים ב־`expires_at`.

המשמעות:

- אפשר להקצות מוצר לזמן מוגבל
- אפשר לתת override זמני
- ה־resolver מתעלם אוטומטית מרשומות שפג תוקפן

כך ניתן לבנות בהמשך:

- trials
- temporary upgrades
- promotional access
- time-boxed capabilities

בלי להוסיף עוד שכבה דומיינית רק בשביל זה.

---

## Usage Metering

[UsageMeter.php](/var/www/vhosts/kalfa.me/httpdocs/app/Services/UsageMeter.php) ו־[UsageRecord.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/UsageRecord.php) מרחיבים את המערכת ל־usage-based runtime.

דוגמאות ל־metrics:

- `sms_sent`
- `voice_minutes`
- `api_calls`

רישום usage:

```php
app(\App\Services\UsageMeter::class)->record(
    $account,
    $product,
    'voice_minutes',
    120,
);
```

Aggregation לפי billing period:

```php
app(\App\Services\UsageMeter::class)->sumForCurrentBillingPeriod(
    $subscription,
    'voice_minutes',
);
```

המטרה של השכבה הזו:

- לבדוק limits בזמן ריצה
- לתמוך ב־future billing integration
- לאפשר reporting פר subscription / per product
- לבנות overage billing בהמשך בלי לשנות את המודל הדומייני

---

## מתי להשתמש ב־Plan Limits

אם limit משתנה לפי tier מסחרי, עדיף להגדיר אותו ב־`ProductPlan` ולא ב־`ProductEntitlement`.

כלל אצבע:

- limit שהוא ברירת מחדל לכל המוצר → `ProductEntitlement`
- limit שמשתנה לפי `basic/pro/enterprise` → `ProductPlan.metadata.limits`

דוגמה:

- `Voice RSVP` יכול להיות מוצר אחד
- `basic` נותן `voice_rsvp_limit = 50`
- `pro` נותן `voice_rsvp_limit = 250`
- `enterprise` נותן `voice_rsvp_limit = 2000`

במקרה כזה, `FeatureResolver` ימשוך את הערך מה־plan הפעיל של החשבון.

---

## ארכיטקטורת היעד

המערכת בנויה עכשיו במבנה הבא:

### Catalog

- `Product`
- `ProductEntitlement`
- `ProductLimit`
- `ProductFeature`

### Commercial

- `ProductPlan`
- `ProductPrice`

### Assignment

- `AccountSubscription`
- `AccountProduct`

### Runtime

- `AccountEntitlement`

### Resolution

- `FeatureResolver`
- `Feature`

### Usage

- `UsageRecord`
- `UsageMeter`

זהו כבר בסיס תקין ל־SaaS Product Platform, לא רק ל־catalog management.

---

## checklists ליצירת מוצר חדש

### Checklist דומייני

- [ ] המוצר מייצג capability עסקית אמיתית
- [ ] לא מדובר רק בפרמטר של מוצר קיים
- [ ] category מוגדרת נכון
- [ ] naming אחיד עם שאר המערכת

### Checklist טכני

- [ ] `slug` ייחודי
- [ ] status מתחיל כ־`draft`
- [ ] entitlements נשמרים דרך relationship
- [ ] limits נשמרים דרך relationship
- [ ] features נשמרים דרך relationship
- [ ] אין שימוש במערכים שטוחים כ־source of truth

### Checklist אינטגרציה

- [ ] הקוד העסקי בפועל בודק `AccountEntitlement`
- [ ] quotas נמדדים בנפרד אם צריך
- [ ] המוצר ניתן להקצאה מחשבון
- [ ] יש דרך bootstrap דרך Seeder אם זה מוצר core

---

## דוגמאות למוצרים אפשריים

### מוצר תקשורת

- `name`: `WhatsApp Notifications`
- `slug`: `whatsapp-notifications`
- entitlements:
  - `whatsapp_enabled = true`
  - `whatsapp_template_messages_enabled = true`
- limits:
  - `monthly_whatsapp_limit = 2000`

### מוצר הרשאות API

- `name`: `Public API Access`
- `slug`: `public-api-access`
- entitlements:
  - `api_access_enabled = true`
  - `api_write_enabled = false`
- limits:
  - `api_requests_per_minute = 60`

### מוצר voice

- `name`: `Voice RSVP Pro`
- `slug`: `voice-rsvp-pro`
- entitlements:
  - `voice_rsvp_enabled = true`
  - `call_recording_enabled = true`
- features:
  - `use_advanced_call_flow = true`

---

## תקלות נפוצות

### המוצר נוצר אבל לא עובד בפועל

ברוב המקרים המשמעות היא:

- המוצר קיים בקטלוג
- אבל לא הוקצה לחשבון
- או שהקוד העסקי לא בודק `AccountEntitlement`

### יש נתוני מוצר אבל חסרים limits / features

בדוק שהמיגרציות העדכניות הורצו:

```bash
php artisan migrate --no-interaction --force
```

### נוצרו keys כפולים

בדוק:

- uniqueness של `slug`
- uniqueness של `feature_key` בתוך אותו מוצר
- האם Seeder לא נכתב בצורה אידמפוטנטית

---

## תהליך מומלץ להוספת מוצר חדש

1. להגדיר את היכולת העסקית ואת הגבולות שלה
2. להחליט מהו `Product` ומהם `entitlements` / `limits` / `features`
3. ליצור draft דרך ה־wizard
4. להוסיף נתוני product דרך relationships
5. לפרסם את המוצר
6. להקצות אותו לחשבון בדיקה
7. לוודא שהקוד העסקי משתמש ב־account entitlements
8. אם זה מוצר מערכת, להוסיף Seeder אידמפוטנטי
9. להוסיף בדיקות אם נוסף flow חדש

---

## מסמכים קשורים

- [TWILIO_SMS_PRODUCT_SETUP_GUIDE.md](/var/www/vhosts/kalfa.me/httpdocs/docs/TWILIO_SMS_PRODUCT_SETUP_GUIDE.md)

---

## מסקנה

הדרך הנכונה ליצור מוצר חדש במערכת הזו היא לא כטופס חד־פעמי אלא כישות דומיינית אמיתית.

כל מוצר חדש צריך:

- להיווצר מוקדם כ־`Product`
- לקבל הרחבות דרך relationships
- להיות מוקצה לחשבונות דרך entitlement propagation
- להתחבר ללוגיקה עסקית שבודקת את ההרשאות האפקטיביות של החשבון

כך המערכת נשארת scalable, audit-friendly, וניתנת להרחבה.
