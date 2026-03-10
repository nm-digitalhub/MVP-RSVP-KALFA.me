# מדריך תפעולי: שליחת מיילים במערכת Kalfa

מדריך זה מסביר כיצד לשלוח את מייל ברוך הבא למארגני אירועים, הן בצורה אוטומטית והן בצורה ידנית, וכיצד לבדוק את חיבור ה-SMTP.

---

## 1. שליחה אוטומטית (זרימת המשתמש)

המערכת מוגדרת לשלוח מייל ברוך הבא באופן אוטומטי בכל פעם שנוצר ארגון (Organization) חדש.

**איך זה עובד?**
1. משתמש נרשם למערכת.
2. המשתמש מועבר לדף יצירת ארגון (`/organizations/create`).
3. עם לחיצה על כפתור "צור ארגון", המערכת:
   - יוצרת את הארגון בבסיס הנתונים.
   - משייכת את המשתמש כבעלים (Owner).
   - **שולחת מייל ברוך הבא** לכתובת האימייל של המשתמש המחובר.

---

## 2. הגדרות מייל (`.env`)

המערכת משתמשת ב-**Gmail SMTP** (בדומה ל־nm-digitalhub). ההגדרות הרלוונטיות:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=support@nm-digitalhub.com
MAIL_PASSWORD="<App Password של Gmail>"
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=netanel.kalfa@kalfa.me
MAIL_FROM_NAME="Netanel Mevorach KALFA"
MAIL_REPLY_TO_ADDRESS=netanel.kalfa@kalfa.me
MAIL_REPLY_TO_NAME="${MAIL_FROM_NAME}"
```

**חשוב:** אחרי כל שינוי ב־`.env` יש להריץ:
```bash
php artisan config:clear
```

---

## 3. שליחה ידנית ובדיקת SMTP

### א. בדיקת חיבור SMTP (מומלץ)

פקודה ייעודית לשליחת מייל בדיקה ולהצגת תגובת השרת (SMTP transcript):

```bash
php artisan mail:test
```
שולח לכתובת ברירת המחדל (`MAIL_FROM_ADDRESS`).

```bash
php artisan mail:test klnetanel@gmail.com
```
שולח לכתובת שתציין. אם השליחה הצליחה, יוצג גם תמלול ה-SMTP.

### ב. שליחת מייל ברוך הבא למשתמש קיים

לשלוח את המייל המעוצב (WelcomeOrganizer) למשתמש וארגון קיימים:

```bash
php artisan tinker --execute="\Illuminate\Support\Facades\Mail::to('user@example.com')->send(new \App\Mail\WelcomeOrganizer(\App\Models\Organization::first(), \App\Models\User::where('email', 'user@example.com')->first()));"
```
(החלף `user@example.com` בכתובת הרצויה.)

### ג. שליחת טקסט גולמי (Tinker)

```bash
php artisan tinker --execute="\Illuminate\Support\Facades\Mail::raw('בדיקת חיבור SMTP', function (\$msg) { \$msg->to('your-email@example.com')->subject('SMTP Test'); });"
```

---

## 4. אימות ופתרון תקלות (Troubleshooting)

1. **ניקוי Cache:** אחרי שינוי ב־`.env`:
   ```bash
   php artisan config:clear
   ```

2. **בדיקת לוגים:** צפייה ב־100 השורות האחרונות (הלוג מקצר exceptions ארוכים כדי למנוע dump של HTML):
   ```bash
   tail -n 100 storage/logs/laravel.log
   ```

3. **תיקיית ספאם:** וודא בדיקה גם בדואר זבל / ספאם. במייל חיצוני (למשל Gmail) השליחה דרך Gmail SMTP אמורה להגיע; אם המייל לא מגיע לתיבת kalfa.me, ייתכן בעיה במסירה בצד ספק הדומיין.

4. **אימות הגדרות:** וידוא שה־mailer הוא smtp וה־host נכון:
   ```bash
   php artisan tinker --execute="echo config('mail.default').' | '.config('mail.mailers.smtp.host');"
   ```

---

## 5. קבצים רלוונטיים במערכת

- **Class השליחה:** `app/Mail/WelcomeOrganizer.php`
- **תבנית העיצוב:** `resources/views/emails/welcome-organizer.blade.php`
- **הגדרות שרת:** קובץ `.env` (משתני `MAIL_*`)
- **פקודת בדיקה:** `app/Console/Commands/MailTestCommand.php` (`mail:test`)
