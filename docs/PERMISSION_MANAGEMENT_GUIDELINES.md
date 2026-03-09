# הנחיות לניהול הרשאות (Permission Management Guidelines)

מסמך זה מגדיר את הסטנדרטים והחוקים לניהול הרשאות ואבטחת נתונים במערכת. המערכת פועלת במודל **SaaS Multi-tenant** כאשר ה**ארגון (Organization)** הוא גבול האבטחה העליון, הממומש באמצעות יכולת ה-**Teams** של חבילת `spatie/laravel-permission`.

---

## 1. ארכיטקטורת הרשאות

המערכת משתמשת בשלוש שכבות של בדיקת הרשאות:

1.  **שכבת המאקרו (System Admin)**: בדיקה גלובלית של שדה `is_system_admin` בטבלת המשתמשים.
2.  **שכבת החברות (Organization Membership)**: בדיקה האם המשתמש משויך לארגון (Organization) דרך טבלת ה-Pivot `organization_users`.
3.  **שכבת הפעולות (Spatie Permissions)**: בדיקת הרשאות וסמכויות ספציפיות בתוך ההקשר (Team) של הארגון הנוכחי.

---

## 2. ניהול "צוותים" (Teams) וארגונים

המערכת מוגדרת להשתמש בתכונת ה-Teams של Spatie, כאשר כל `Organization` נחשב ל-Team.

-   **מזהה הצוות**: שדה `organization_id` בטבלאות ההרשאות.
-   **Middleware**: המערכת משתמשת ב-`SpatiePermissionTeam` middleware שקובע את הצוות הנוכחי (`setPermissionsTeamId`) לפי ה-`current_organization_id` של המשתמש המחובר בכל בקשה (Web).
-   **משמעות**: הרשאות ותפקידים שניתנו למשתמש בארגון א', אינם תקפים בארגון ב'.

---

## 3. תפקידים והרשאות סטנדרטיים

### תפקידים גלובליים (Global Roles)
-   **Super Admin**: תפקיד גלובלי (ללא `organization_id`) שניתן למנהלי מערכת. עוקף את כל בדיקות ה-Gate דרך `Gate::before` ב-`AppServiceProvider`.

### תפקידים בתוך הארגון (Scoped Roles)
תפקידים אלו נוצרים פר ארגון ומשוייכים ל-`organization_id` המתאים:

| תפקיד | הרשאות כלולות (דוגמה) |
|-------|-----------------------|
| **Organization Admin** | `view-event-details`, `manage-event-guests`, `manage-event-tables`, `send-invitations` |
| **Organization Editor** | `view-event-details`, `manage-event-guests` |

### הרשאות (Permissions)
ההרשאות הן גלובליות בשמן, אך השיוך שלהן למשתמש/תפקיד הוא תלוי צוות:
-   `manage-system`: ניהול הגדרות ליבה של המערכת (מנהלי מערכת בלבד).
-   `impersonate-users`: יכולת התחזות למשתמשים אחרים.
-   `view-event-details`: צפייה בפרטי אירוע.
-   `manage-event-guests`: הוספה/עריכה/מחיקה של אורחים.
-   `manage-event-tables`: ניהול סידורי הושבה ושולחנות.
-   `send-invitations`: שליחת הזמנות ו-RSVP.

---

## 4. שימוש ב-Policies (חובה)

בדיקות הרשאה חייבות להתבצע ב-Policies. ה-Policies במערכת משלבים בדיקת חברות בארגון יחד עם בדיקת הרשאות Spatie.

### דוגמה ליישום ב-EventPolicy:
```php
public function update(User $user, Event $event): bool
{
    // 1. בדיקה שהמשתמש שייך לארגון של האירוע
    // 2. בדיקה שיש למשתמש הרשאה לערוך אורחים בתוך הצוות הנוכחי
    return $user->organizations()->where('organizations.id', $event->organization_id)->exists()
        && $user->can('manage-event-guests');
}
```

---

## 5. מנהל מערכת (System Admin)

משתמשים עם `is_system_admin = true`:
-   מקבלים אוטומטית אישור לכל פעולת `can()` דרך ה-Gate.
-   יכולים לבצע **Impersonation** (התחזות) לארגונים.
-   **חשוב**: בעת התחזות, ה-Middleware עדיין יקבע את ה-`PermissionsTeamId` לפי הארגון אליו התחזו, מה שמאפשר בדיקה נכונה של הנתונים תחת הקשר הארגון.

---

## 6. כללים למפתחים (Constraints)

1.  **תמיד להשתמש ב-`$user->can()`**: אל תבדקו תפקידים ישירות (`hasRole`) במידת האפשר; העדיפו בדיקת הרשאות (`can`).
2.  **הקשר הצוות (Team Context)**: זכרו שבדיקת `$user->can('edit')` תלויה בצוות שהוגדר ב-Middleware. אם אתם מבצעים בדיקה מחוץ ל-Web request (למשל ב-Job או ב-CLI), עליכם לקבוע את ה-Team ID ידנית: `app(PermissionRegistrar::class)->setPermissionsTeamId($id)`.
3.  **OrganizationUserRole Enum**: שדה ה-`role` בטבלת ה-pivot `organization_users` משמש כסימון ראשוני (Legacy/Core) לחברות בארגון וסטטוס "Owner". הוא עדיין בשימוש ב-`OrganizationPolicy` ומומלץ לסנכרן בינו לבין תפקידי Spatie בעת שינוי בעלות.
4.  **מניעת N+1**: בעת טעינת משתמשים, מומלץ לטעון מראש (`with`) את ה-roles וה-permissions במידה ומתבצעות בדיקות בלולאה.

---
*עודכן לאחרונה: מרץ 2026*
