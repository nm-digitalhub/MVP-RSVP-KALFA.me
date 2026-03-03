# תיקון הרשאות — tempnam / Blade compile (500)

**בעיה:** `tempnam(): file created in the system's temporary directory` — Laravel לא מצליח ליצור קובץ זמני (Blade compiler / Filesystem).

**גורם:** הרשאות קבצים — `storage` ו-`bootstrap/cache` לא ניתנים לכתיבה למשתמש ש־PHP רץ תחתיו ב־web.

---

## מה בוצע

### 1. הרשאות עודכנו

```bash
cd /var/www/vhosts/kalfa.me/httpdocs
chmod -R 775 storage bootstrap/cache
chown -R kalfa.me:psaserv storage bootstrap/cache
```

- **משתמש/קבוצה:** `kalfa.me:psaserv` (כמו תיקיית `public/` בדומיין).
- **תוצאה:** `storage`, `storage/framework`, `storage/framework/views`, `bootstrap/cache` — כולם `kalfa.me:psaserv` עם `775`.

### 2. ניקוי cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 3. בדיקות

| בדיקה | תוצאה |
|--------|--------|
| `ls -ld /tmp` | `drwxrwxrwt root root` — תקין |
| `sys_get_temp_dir()` (מ־CLI) | `/tmp`, writable |
| `ls -ld storage storage/framework storage/framework/views` | כולם `kalfa.me psaserv` |
| קבצים ב־storage שעדיין root | אין (find לא מצא) |

---

## אם עדיין 500 ב־web

אם `curl -X POST https://kalfa.me/api/webhooks/sumit` עדיין מחזיר 500:

1. **משתמש PHP ב־web**  
   ב־Plesk, PHP (FPM/Apache) יכול לרוץ תחת משתמש אחר (למשל `apache`, `nobody`, או משתמש הדומיין).  
   - **PHP-FPM:** ב־`/var/www/vhosts/kalfa.me/conf/` או ב־Plesk → PHP Settings → Pool configuration, בדוק עם איזה user רץ ה־pool.  
   - יש להגדיר `chown` ל־storage/bootstrap/cache **למשתמש ש־PHP רץ תחתיו** (או לכלול את המשתמש בקבוצה עם הרשאות כתיבה).

2. **open_basedir**  
   Plesk → Domains → kalfa.me → PHP Settings.  
   אם מוגדר `open_basedir`, וודא שהוא כולל:
   - `/tmp`
   - `/var/www/vhosts/kalfa.me/httpdocs`  
   בלי `/tmp` — PHP לא יכול ליצור קובץ זמני ב־`tempnam()`.

3. **SELinux / AppArmor**  
   אם מופעל, ייתכן חסימה על כתיבה ל־`storage` או `/tmp` — יש לבדוק logs ו־policy.

---

## סיכום

- הרשאות Laravel (`storage`, `bootstrap/cache`) תוקנו ל־`kalfa.me:psaserv` עם `775`.
- `/tmp` ו־`sys_get_temp_dir()` תקינים מ־CLI.
- אם ה־500 נמשך ב־web — לבדוק איזה user מריץ את PHP ב־web ולוודא ש־storage ו־/tmp ניתנים לכתיבה עבורו (ולוודא ש־open_basedir כולל את הנתיבים הנדרשים).
