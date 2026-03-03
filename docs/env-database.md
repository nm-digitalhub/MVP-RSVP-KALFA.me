# הגדרת מסד נתונים ב־`.env`

תיעוד הגדרות ה־DB של פרויקט RSVP + סידור שולחנות (ב־`httpdocs/.env`).

---

## 1. מה להגדיר ב־`.env`

ב־`httpdocs/.env` מופיעה בלוק ההגדרות הבא:

```env
# RSVP + Seating — PostgreSQL (מסד נפרד לפרויקט)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kalfa_rsvp
DB_USERNAME=kalfa_rsvp
DB_PASSWORD=...
```

- **DB_CONNECTION** — `pgsql` ל־PostgreSQL.
- **DB_HOST** — כתובת שרת PostgreSQL. אם ה־DB על אותו שרת: `127.0.0.1`. אם על שרת אחר: IP או hostname.
- **DB_PORT** — פורט PostgreSQL (ברירת מחדל: `5432`).
- **DB_DATABASE** — שם המסד: `kalfa_rsvp`.
- **DB_USERNAME** — שם המשתמש: `kalfa_rsvp`.
- **DB_PASSWORD** — סיסמת המשתמש (ראה להלן להחלפת סיסמה).

**הקונפיג הזה תקין ל־PostgreSQL לוקאלי כאשר:**

- PostgreSQL רץ על **אותו שרת** כמו האפליקציה.
- המשתמש `kalfa_rsvp` **קיבל הרשאות** למסד `kalfa_rsvp`.
- **pg_hba.conf** מאפשר חיבור מקומי (local / host).

---

## 2. בדיקת תקינות לפני migrate

לפני הרצת מיגרציות:

```bash
php artisan migrate --pretend
```

- אם **אין שגיאת חיבור** — הרץ:
  ```bash
  php artisan migrate
  ```

---

## 3. שגיאות אופייניות ופתרון

### ❌ could not connect to server

**בדוק:**

1. **PostgreSQL רץ:**
   ```bash
   sudo systemctl status postgresql
   ```

2. **השרת מאזין על פורט 5432:**
   ```bash
   sudo netstat -plnt | grep 5432
   ```

וודא ש־`pg_hba.conf` מאפשר חיבור מ־127.0.0.1 (או מ־האפליקציה).

### ❌ password authentication failed

**בדוק:**

```bash
sudo -u postgres psql
\du
```

וודא שהמשתמש `kalfa_rsvp` קיים ויש לו סיסמה. אם צריך — עדכן סיסמה (ראה סעיף 8).

---

## 4. search_path ב־PostgreSQL

ב־`config/database.php` חיבור ה־pgsql כולל `'search_path' => 'public'`.  
לוודא שבשרת הסכמה אכן `public`:

```sql
SHOW search_path;
```

אם הסכמה שונה — raw queries עלולות להישבר גם כשהמיגרציות עוברות. פירוט: [hardening-and-production-readiness.md](hardening-and-production-readiness.md).

---

## 5. הגדרות מומלצות ל־Production (`config/database.php`)

ב־`config/database.php` חיבור ה־pgsql אמור לכלול:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
```

- **אל תשתמש ב־`sslmode=disable`** אם ה־DB חיצוני (שרת מרוחק); השאר `prefer` או `require`.

הפרויקט כבר מוגדר עם `search_path` ו־`sslmode => 'prefer'`.

---

## 6. בדיקה אחרונה — המסד קיים

ב־psql:

```sql
SELECT datname FROM pg_database;
```

וודא ש־**kalfa_rsvp** מופיע ברשימה.

---

## 7. שרת מרוחק (PostgreSQL על שרת אחר)

אם PostgreSQL רץ על שרת אחר:

- עדכן ב־`.env` את **DB_HOST** ל־IP או ל־hostname של שרת ה־DB.
- ודא שפתיחת פורט 5432 מ־שרת האפליקציה ל־שרת ה־DB מותרת (חומת אש / Security Group).
- השאר `sslmode=prefer` (או `require`) ב־`config/database.php`.

---

## 8. החלפת סיסמה

1. **מהטרמינל (כ־postgres):**
   ```bash
   sudo -u postgres psql -c "ALTER USER kalfa_rsvp WITH PASSWORD 'הסיסמה_החדשה';"
   ```

2. **עדכן ב־`.env`:**
   ```env
   DB_PASSWORD=הסיסמה_החדשה
   ```

---

## 9. יצירת המסד והמשתמש (הפניה)

המסד `kalfa_rsvp` והמשתמש `kalfa_rsvp` נוצרו ב־PostgreSQL (מהטרמינל / Plesk).  
אחרי ש־`.env` מוגדר והבדיקות עברו — הרץ:

```bash
php artisan migrate
```

**הערה:** במסד `kalfa_rsvp` חלק מהמיגרציות (OfficeGuy) מדלגות אוטומטית כי הטבלאות המבוקשות לא קיימות. פירוט ב־[MVP-RSVP-Seating-Phase1.md](MVP-RSVP-Seating-Phase1.md) (סעיף 11 — ביצוע בפועל).

---

*עדכון אחרון: תיעוד .env, בדיקות לפני migrate, שגיאות אופייניות, והגדרות Production.*
