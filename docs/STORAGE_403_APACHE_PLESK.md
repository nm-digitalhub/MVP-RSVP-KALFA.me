# 403 על /storage — Apache חוסם Symlink (Plesk)

**תסמין:** תמונות אירועים (או כל קובץ ב־`/storage/...`) מחזירות **403 Forbidden**.

**לוג Apache (הסיבה המדויקת):**
```text
AH00037: Symbolic link not allowed or link target not accessible
```

כלומר:
- ה־symlink `public/storage` → `storage/app/public` תקין
- הקבצים קיימים והרשאות תקינות
- **Apache לא מורשה לעקוב אחרי symlink** (מדיניות ברירת מחדל נפוצה ב־Plesk)

---

## פתרון 1 — הפעלת FollowSymLinks (מינימלי)

ב־Plesk: **Websites & Domains** → **Apache & Nginx Settings** → **Additional Apache directives**.

הוסף:

```apache
<Directory /var/www/vhosts/kalfa.me/httpdocs>
    Options +FollowSymLinks
</Directory>
```

שמור. Plesk יבצע reload ל־Apache. אם לא: `service apache2 reload`.

**בדיקה:**
```bash
curl -I https://kalfa.me/storage/events/1/PDg9rytYnWCP8Di2UiH1MrGwgThlq5gbbtL1wGZL.png
```
צריך: `HTTP/1.1 200 OK`.

---

## פתרון 2 — Alias (מומלץ, בלי symlink)

במקום להסתמך על symlink, מגישים את `/storage` ישירות מהנתיב האמיתי באמצעות **Alias**. יציב יותר ולא תלוי ב־FollowSymLinks.

ב־Plesk: **Additional Apache directives**:

```apache
Alias /storage /var/www/vhosts/kalfa.me/httpdocs/storage/app/public
<Directory /var/www/vhosts/kalfa.me/httpdocs/storage/app/public>
    Require all granted
    Options -Indexes
</Directory>
```

- `Alias /storage ...` — בקשות ל־`https://kalfa.me/storage/...` ממומשות מתוך `storage/app/public`.
- `Require all granted` — מאפשר גישת קריאה.
- `Options -Indexes` — מונע רישום תיקיות (ללא listing).

**יתרונות:** אין צורך ב־symlink, אין תלות ב־FollowSymLinks, אותה כתובת URL (`/storage/events/...`) ממשיכה לעבוד עם `Storage::url()`.

**בדיקה:** כמו למעלה — `curl -I https://kalfa.me/storage/events/1/...`.

---

## סיכום

| נושא | הסבר |
|------|------|
| **הבעיה** | Apache (Plesk) חוסם גישה דרך symlink — AH00037. |
| **לא ב-Laravel** | Laravel עובד תקנית: `storage:link` + `Storage::url()`. |
| **תיקון** | פתרון 1: `Options +FollowSymLinks` בתיקיית האתר. |
| **תיקון מומלץ** | פתרון 2: `Alias /storage` ל־`storage/app/public` + `Directory` עם `Require all granted`. |

לאחר יישום אחד מהפתרונות, כתובות כמו  
`https://kalfa.me/storage/events/{event_id}/filename.png`  
יחזירו 200 ויתצוגו בדפדפן.
