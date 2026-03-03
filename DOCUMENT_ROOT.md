# Plesk — הגדרת Document Root

1. ב־**Plesk** → **Hosting & DNS** → **Hosting Settings**
2. הגדר **Document root** ל־: `httpdocs/public`

---

## לבנתיים: דף הנחיתה מוצג ראשון

במהלך בניית המערכת מוצג דף הנחיתה הסטטי ("בפיתוח"):

- **`public/index.html`** — הדף הראשי
- **`public/app.js`** — הלוגיקה (טופס, טאבים וכו')

כדי שהדומיין יציג את הדף הסטטי, קובץ הכניסה של Laravel שונה ל־**`index-laravel.php`**. כך השרת מציג את `index.html` כשנכנסים ל־`/`.

### מעבר ל־Laravel כשהמערכת מוכנה

1. שנה את השם של `public/index-laravel.php` חזרה ל־`public/index.php`
2. הסר או שנה את השם של `public/index.html` (או העבר לגיבוי)
3. מחק או העבר את `public/app.js` אם לא נדרש

אחרי זה הכניסה ל־`/` תטען את Laravel.

---

## הרשאות

ודא שהתיקיות הבאות ניתנות לכתיבה על ידי שרת האינטרנט:
- `storage/`
- `bootstrap/cache/`

## גיבוי דף הנחיתה

עותק נוסף של דף הנחיתה שמור ב־`landing-backup/` (בשורש הפרויקט).
