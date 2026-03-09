# מדריך הקמה: מוצר `Twilio SMS`

## מטרת המסמך

מסמך זה מתאר לעומק כיצד להקים, להפעיל, ולתחזק מוצר `Twilio SMS` בתוך מערכת המוצרים של Kalfa, תוך הסתמכות על המימוש הקיים ב־`app/Livewire/System/Products` ועל האינטגרציה הפעילה עם Twilio.

המסמך נכתב על בסיס:

- קוד המערכת המקומי
- רכיבי Livewire של ניהול מוצרים
- ה־Seeder של מוצר `Twilio SMS`
- מימושי Twilio הקיימים עבור Voice RSVP, Verify ו־SMS
- תיעוד רשמי של Twilio

---

## תקציר מנהלים

במערכת הנוכחית, `Product` הוא ה־aggregate root. מוצר לא מיוצג כטופס זמני אלא כרשומת DB אמיתית, ויתר ההגדרות שלו נשמרות דרך קשרי Eloquent:

- `Product -> entitlements()`
- `Product -> limits()`
- `Product -> features()`

לכן, הקמה נכונה של מוצר `Twilio SMS` היא:

1. יצירת/נרמול מוצר בקטלוג
2. חיבור entitlements שמייצגים את יכולות ה־SMS
3. הקצאת המוצר לחשבון (`Account`)
4. בדיקה שקוד המשלוח בפועל מכבד את ה־entitlements

בפועל זה כבר מחובר במערכת:

- המוצר מנוהל בקטלוג המוצרים
- נוצר Seeder אידמפוטנטי: `database/seeders/TwilioSmsProductSeeder.php`
- שליחת SMS ב־`RsvpVoiceController` תלויה עכשיו ב־account entitlements

---

## 1. מפת המערכת

### 1.1 רכיבי ניהול המוצרים

הקבצים המרכזיים ב־`app/Livewire/System/Products`:

- `Index.php`  
  רשימת המוצרים, חיפוש, פילטרים וניווט ליצירת מוצר.

- `CreateProductWizard.php`  
  ה־wizard ליצירת מוצר חדש. הזרימה היא draft-backed:
  - שלב 1 יוצר `Product` אמיתי
  - שלבים מאוחרים מוסיפים נתונים דרך relationships

- `Show.php`  
  ניהול מוצר קיים:
  - עריכת פרטי מוצר
  - הוספת entitlements
  - סינון תצוגת entitlements
  - מחיקת מוצר

### 1.2 מודל הדומיין

המודל `app/Models/Product.php` מגדיר:

- `entitlements()`
- `limits()`
- `features()`
- `plans()`

המשמעות: מוצר הוא ישות מרכזית אחת, וכל פיצ'ר עסקי מתווסף אליו כ־relationship, לא כ־JSON אקראי.

---

## 2. איפה Twilio SMS חי בקוד היום

### 2.1 שליחת SMS בפועל

שליחת SMS אישור ל־RSVP מתבצעת ב:

- `app/Http/Controllers/Twilio/RsvpVoiceController.php`

שם קיימת המתודה:

- `sendSmsConfirmation(Guest $guest, ?Event $event, RsvpResponseType $responseType)`

המתודה:

1. בודקת שהתגובה היא `Yes`
2. בודקת שיש `Guest`, `Event` וטלפון
3. בודקת שלחשבון של הארגון יש entitlements מתאימים
4. בודקת quota חודשי
5. שולחת SMS דרך Twilio
6. מגדילה usage ב־`account_feature_usage`

### 2.2 הגדרות Twilio

הגדרות Twilio נטענות דרך:

- `app/Settings/TwilioSettings.php`
- `app/Providers/SystemSettingsServiceProvider.php`
- `app/Providers/AppServiceProvider.php`

בפועל המערכת תומכת ב:

- `sid`
- `token`
- `number`
- `messaging_service_sid`
- `verify_sid`
- `api_key`
- `api_secret`
- `is_active`

### 2.3 זרימות Twilio נוספות שכבר קיימות

Twilio במערכת לא מוגבל רק ל־SMS:

- Voice RSVP
- WhatsApp fallback
- Verify OTP (`sms` / `whatsapp`)

עם זאת, מוצר `Twilio SMS` שהוגדר עכשיו מייצג את היכולת היישומית הקיימת בפועל:  
**שליחת SMS אישורי RSVP ו־transactional messaging דרך Twilio**.

---

## 3. הגדרת המוצר `Twilio SMS`

### 3.1 זהות המוצר

המוצר התקין במערכת צריך להיראות כך:

- `name`: `Twilio SMS`
- `slug`: `twilio-sms`
- `category`: `Twilio`
- `status`: `active`
- `description`:  
  `Transactional SMS confirmations and messaging powered by Twilio.`

### 3.2 ה־entitlements של המוצר

המוצר מוגדר עם שלושה entitlements:

1. `twilio_enabled = true`
   - מסמן שהחשבון רשאי להשתמש ביכולות Twilio

2. `sms_confirmation_enabled = true`
   - מאפשר שליחת SMS אישורי RSVP

3. `sms_confirmation_limit = 500`
   - quota חודשי לשליחת הודעות SMS מהסוג הזה

### 3.3 למה אלו ה־keys הנכונים

ה־keys נבחרו כך שישקפו התנהגות קיימת ואמיתית במערכת:

- `twilio_enabled`  
  מפתח כללי, טוב לשיתוף לוגיקה עם מוצרים עתידיים שקשורים ל־Twilio

- `sms_confirmation_enabled`  
  מפתח ספציפי לפונקציונליות שכבר רצה בפועל

- `sms_confirmation_limit`  
  מפתח כמותי שמתחבר ישירות ל־usage tracking

---

## 4. איך להקים את המוצר בפועל

### אפשרות א: הדרך המומלצת

להריץ את ה־Seeder האידמפוטנטי:

```bash
php artisan db:seed --class=Database\\Seeders\\TwilioSmsProductSeeder --no-interaction --force
```

ה־Seeder:

- יוצר את המוצר אם הוא לא קיים
- מנרמל מוצר ישן/שבור אם הוא כבר קיים (`sms-twilio`)
- מעדכן את ה־slug, השם והסטטוס
- מסנכרן את ה־entitlements הצפויים

### אפשרות ב: דרך ה־UI

ניתן להקים את המוצר גם דרך ה־wizard:

1. היכנס ל־System → Products
2. לחץ `Create Product`
3. שלב 1:
   - Name: `Twilio SMS`
   - Slug: `twilio-sms`
   - Category: `Twilio`
   - Description: מוצר ל־transactional SMS confirmations
4. שלב 2:
   - `twilio_enabled = true`
   - `sms_confirmation_enabled = true`
   - `sms_confirmation_limit = 500`
5. שלב 4:
   - Publish

הערה:  
לשלב 3 (`limits` / `features`) יש צורך בטבלאות:

- `product_limits`
- `product_features`

אם הן עדיין לא קיימות, צריך קודם להריץ:

```bash
php artisan migrate --no-interaction --force
```

---

## 5. איך לחבר את המוצר לחשבון לקוח

המוצר בקטלוג לא מספיק בפני עצמו. כדי שהחשבון ישתמש ב־SMS, צריך להקצות לו את המוצר.

הזרימה הקיימת:

1. היכנס ל־System → Accounts → Account
2. בחר מוצר מתוך רשימת המוצרים
3. המערכת קוראת:

```php
$account->grantProduct($product);
```

המשמעות:

- כל `ProductEntitlement` של המוצר מועתק ל־`AccountEntitlement`
- משלב זה, קוד האפליקציה בודק את entitlements ברמת החשבון

זה תואם בדיוק את הארכיטקטורה הרצויה:

- Product = catalog definition
- AccountEntitlement = effective runtime permissions

---

## 6. מה קורה בזמן שליחת SMS

ב־`RsvpVoiceController::sendSmsConfirmation()` הזרימה כעת היא:

1. נשלף החשבון דרך:
   - `event -> organization -> account`
2. נבדק:
   - `twilio_enabled = true`
   - `sms_confirmation_enabled = true`
3. אם יש `sms_confirmation_limit`, נבדק usage חודשי
4. ההודעה נשלחת דרך:
   - `messagingServiceSid` אם מוגדר
   - אחרת דרך `from = services.twilio.number`
5. usage נשמר תחת:
   - `feature_key = sms_confirmation_messages`

כלומר, המוצר `Twilio SMS` כבר לא רק "קטלוג", אלא שולט בפועל ביכולת המערכת לשלוח SMS.

---

## 7. prerequisites סביבתיים

### 7.1 הגדרות Twilio שחייבות להיות תקינות

מינימום נדרש:

- `services.twilio.sid`
- `services.twilio.token`
- `services.twilio.number`

מומלץ מאוד:

- `services.twilio.messaging_service_sid`

אם משתמשים ב־Verify:

- `services.twilio.verify_sid`

בדיקות מבנה מומלצות לפי Twilio:

- `messaging_service_sid` תקין מתחיל בדרך כלל ב־`MG`
- `verify_sid` תקין מתחיל בדרך כלל ב־`VA`
- מספרי יעד צריכים להישלח בפורמט `E.164`

### 7.2 איפה מגדירים את זה

אפשר דרך:

- System Settings → Twilio

או דרך ENV / Settings backend.

### 7.3 למה `Messaging Service` עדיף

לפי תיעוד Twilio, Messaging Service הוא המנגנון המומלץ כאשר רוצים ניהול מרוכז של senders, routing ו־configuration של הודעות. לכן, אם `messaging_service_sid` קיים, המערכת משתמשת בו קודם.

### 7.4 מגבלות Trial שחשוב להכיר

אם חשבון Twilio עדיין ב־Trial:

- אפשר לשלוח SMS רק למספרים שאומתו מראש בתוך Twilio Console
- מספר יעד לא מאומת עלול להיכשל עם שגיאת Twilio `21608`
- בארה״ב ובקנדה יש גם דרישות רגולטוריות נוספות סביב מספרי toll-free ו־A2P 10DLC

במילים אחרות, לפני דיבוג קוד האפליקציה צריך לוודא שהחשבון ב־Twilio בכלל מורשה לשלוח ליעד שנבדק.

---

## 8. תרחיש הקמה מומלץ ל־Production

1. ודא שהמיגרציות העדכניות הורצו:

```bash
php artisan migrate --no-interaction --force
```

2. ודא שהגדרות Twilio מוגדרות ונשמרו

3. הרץ את ה־Seeder:

```bash
php artisan db:seed --class=Database\\Seeders\\TwilioSmsProductSeeder --no-interaction --force
```

4. ודא שהמוצר נוצר:

```sql
select id, name, slug, status, category
from products
where slug = 'twilio-sms';
```

5. ודא שה־entitlements נוצרו:

```sql
select feature_key, value, type
from product_entitlements
where product_id = <product_id>;
```

6. הקצה את המוצר לחשבון יעד

7. בצע בדיקת end-to-end על RSVP קולי עם תשובת `Yes`

---

## 9. checklist לבדיקת תקינות

### ברמת הקטלוג

- [ ] המוצר `Twilio SMS` קיים
- [ ] slug הוא `twilio-sms`
- [ ] status הוא `active`
- [ ] שלושת ה־entitlements קיימים

### ברמת החשבון

- [ ] המוצר הוקצה לחשבון
- [ ] `account_entitlements` מכיל:
  - `twilio_enabled`
  - `sms_confirmation_enabled`
  - `sms_confirmation_limit`

### ברמת Twilio

- [ ] מספר השולח פעיל
- [ ] `messaging_service_sid` תקין, אם משתמשים בו
- [ ] החשבון יכול לשלוח SMS מה־region הרלוונטי

### ברמת שימוש

- [ ] נרשמת שורת usage תחת `sms_confirmation_messages`

---

## 10. תקלות נפוצות

### `relation "product_limits" does not exist`

המשמעות:  
המיגרציות החדשות של product setup לא הורצו.

פתרון:

```bash
php artisan migrate --no-interaction --force
```

### המוצר קיים אבל SMS לא נשלח

בדוק:

- האם לחשבון יש `twilio_enabled = true`
- האם לחשבון יש `sms_confirmation_enabled = true`
- האם ה־quota לא מוצה
- האם `services.twilio.number` / `messaging_service_sid` מוגדרים
- האם מספר היעד בפורמט `E.164`
- האם מדובר בחשבון Trial ששולח למספר לא מאומת

### יש מוצר ישן בשם `SMS-twilio`

זה מצב legacy / draft broken data.  
ה־Seeder נועד לנרמל אותו ל־`Twilio SMS`.

### שגיאת Twilio `21608`

המשמעות:

- החשבון עדיין ב־Trial
- נעשה ניסיון לשלוח למספר שלא אומת ב־Twilio Console

פתרון:

- לאמת את מספר היעד ב־Twilio
- או לשדרג את החשבון ל־Paid

---

## 11. פקודות שימושיות

### יצירת המוצר / נרמולו

```bash
php artisan db:seed --class=Database\\Seeders\\TwilioSmsProductSeeder --no-interaction --force
```

### בדיקת מיגרציות

```bash
php artisan migrate:status --no-interaction
```

### בדיקת טסטים רלוונטיים

```bash
php artisan test --compact tests/Feature/System/Products/CreateProductWizardTest.php
```

### פורמט קוד

```bash
vendor/bin/pint --dirty --format agent
```

---

## 12. מסקנה אופרטיבית

אם המטרה היא "להוסיף מוצר Twilio SMS" בצורה נכונה במערכת הזו, הדרך הנכונה איננה רק להכניס שורה לטבלת `products`, אלא לבצע את כל השרשרת:

1. להגדיר מוצר בקטלוג
2. להגדיר entitlements ברמת המוצר
3. להקצות את המוצר לחשבון
4. לוודא שקוד המשלוח בודק את entitlements
5. למדוד usage בפועל

זהו המצב הנוכחי של המערכת לאחר החיבור שבוצע.

---

## מקורות רשמיים

- Twilio Messaging Services  
  https://www.twilio.com/docs/messaging/services

- Twilio Messaging Services tutorial  
  https://www.twilio.com/docs/messaging/tutorials/send-messages-with-messaging-services

- Twilio Messaging Service resource (`MG` SID)  
  https://www.twilio.com/docs/messaging/api/service-resource

- Twilio Verify API overview  
  https://www.twilio.com/docs/verify/api

- Twilio Verify: Verifications endpoint  
  https://www.twilio.com/docs/verify/api/verification

- Twilio trial account messaging limits  
  https://www.twilio.com/docs/messaging/guides/how-to-work-with-your-twilio-free-trial-account-us-only

- Twilio error `21608`  
  https://www.twilio.com/docs/api/errors/21608

---

## מסמך משלים

להדרכה כללית על יצירה והוספה של כל מוצר במערכת:

- [PRODUCT_CREATION_GUIDE.md](/var/www/vhosts/kalfa.me/httpdocs/docs/PRODUCT_CREATION_GUIDE.md)

---

## קבצים רלוונטיים במערכת

- `app/Livewire/System/Products/Index.php`
- `app/Livewire/System/Products/CreateProductWizard.php`
- `app/Livewire/System/Products/Show.php`
- `app/Models/Product.php`
- `app/Http/Controllers/Twilio/RsvpVoiceController.php`
- `database/seeders/TwilioSmsProductSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/livewire/system/products/show.blade.php`
- `resources/views/livewire/system/accounts/show.blade.php`
