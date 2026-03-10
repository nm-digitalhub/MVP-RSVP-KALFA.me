# תיעוד תשתית המייל וניהול מסמכים - Kalfa

דף זה מרכז את המידע הטכני הסופי, ההגדרות והקוד שהוטמעו במערכת עבור שליחת מיילים (SMTP) וניהול קבצי PDF.

---

## 1. קונפיגורציית שרת דואר (SMTP)
המערכת משתמשת בשרת ה-SMTP של **IONOS** עם הצפנת STARTTLS.

### הגדרות בסיסיות בקובץ `.env`
יש לוודא שהערכים הבאים מוגדרים בצורה מדויקת:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ionos.com
MAIL_PORT=587
MAIL_USERNAME=netanel.kalfa@kalfa.me
MAIL_PASSWORD="your_password_here"
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=netanel.kalfa@kalfa.me
MAIL_FROM_NAME="Netanel Mevorach KALFA"
```

> **חשוב:** השתמש בפורט **587** עם הצפנת **tls** (STARTTLS). אין להשתמש בפורט 465 אלא אם יש דרישה מפורשת לכך.

---

## 2. הסבר על הגדרות Laravel Mail
Laravel טוענת את ההגדרות מקובץ `config/mail.php`, שבתורו קורא את הערכים מה-`.env`. 
- במידה ושינית ערכים ב-`.env` והם לא משפיעים, ייתכן שקיים Cache לקונפיגורציה.
- ה-Mailer הראשי מוגדר ב-`default`.
- הפרטים הטכניים של כל ספק (Host, Port, וכו') נמצאים תחת המפתח `mailers`.

---

## 3. פקודות ניהול ופתרון בעיות
במידה והמיילים לא נשלחים או שהשינויים לא נקלטים, יש להריץ את הפקודות הבאות בסדר הזה:

### ניקוי Cache (קריטי לאחר שינוי הגדרות)
```bash
# ניקוי זיכרון המטמון של ההגדרות
php artisan config:clear

# ניקוי Cache כללי
php artisan cache:clear
```

### בדיקת סטטוס הגדרות (באמצעות Tinker)
כדי לוודא מה המערכת "רואה" בפועל:
```bash
php artisan tinker --execute="config('mail.mailers.smtp')"
```

---

## 4. בדיקת שליחה ידנית (Test Command)
ניתן להריץ פקודת בדיקה ישירה דרך הטרמינל כדי לאמת שהחיבור ל-SMTP תקין:

```bash
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'netanel.kalfa@kalfa.me')->first() ?? \App\Models\User::first();
\$organization = \$user->organizations()->first() ?? \App\Models\Organization::first();
\Illuminate\Support\Facades\Mail::to('netanel.kalfa@kalfa.me')->send(new \App\Mail\WelcomeOrganizer(\$organization, \$user));
echo 'Email sent successfully!';"
```

---

## 5. מייל ברוך הבא (Mailable)
ה-Class `App\Mail\WelcomeOrganizer` מנהל את הלוגיקה של המייל.
- **תבנית:** `resources/views/emails/welcome-organizer.blade.php` (Markdown).
- **תמיכה ב-PDF:** המייל תומך בקבלת פרמטר `pdfUrl` להוספת קישור למסמך חיצוני.

---

## 6. ניהול מסמכים ו-PDF
1. **מסמכים חשבונאיים (SUMIT):** שימוש ב-`App\Services\OfficeGuy\DocumentService`. קבצי ה-PDF נמשכים כ-URL מה-API של SUMIT.
2. **דוחות פנימיים (DomPDF):** שימוש בחבילת `barryvdh/laravel-dompdf` ליצירת PDF מקומי.
   - פקודה לייצור: `PDF::loadView('view.name', \$data)->save('path/to/file.pdf')`.

---

## 7. המלצות אבטחה
- **לעולם אל תבצע Commit לקובץ `.env`**: וודא שהוא מופיע ב-`.gitignore`.
- **סיסמאות:** אם הסיסמה כוללת תווים מיוחדים, יש להקיף אותה במירכאות כפולות ב-`.env`.
- **הגבלת שליחה:** מומלץ להשתמש ב-Rate Limiting במידה ושולחים כמות גדולה של מיילים בבת אחת.

---
*עודכן לאחרונה: מרץ 2026*
