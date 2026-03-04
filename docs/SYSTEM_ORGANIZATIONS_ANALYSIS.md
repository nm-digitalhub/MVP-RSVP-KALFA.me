# System Organizations — ניתוח קוד (Index + Show)

סטטוס: סריקה ותצוגות — מה נדרש לתקן/לשפר.

---

## 1. System Organizations Index

### 1.1 לוגיקה (PHP)

| נושא | סטטוס | הערה |
|------|--------|------|
| פילטרים | ✅ | `filter_suspended`, `filter_no_events`, `filter_no_users` — עובדים עם `when()` |
| חיפוש | ✅ | `search_name` (LIKE), `search_owner_email` (whereHas על owner) |
| Pagination | ✅ | `WithPagination`, 15 per page |
| Eager load | ✅ | `withCount(['users', 'events'])` — מונע N+1 על counts |
| סדר | ✅ | `latest()` על טבלת organizations |

**שיפור אפשרי:**  
- אי־תלות ב־`latest()`: אם אין עמודת `created_at` או רוצים מיון לפי `updated_at`, להגדיר במפורש: `->latest('updated_at')` או לפי צורך.

### 1.2 תצוגה (Index Blade)

| נושא | סטטוס | נדרש |
|------|--------|------|
| Design System | ⚠️ | כפתורים/שדות משתמשים ב־`rounded-xl`, gradient, ללא `min-h-[44px]` ו־`focus-visible:ring-offset-2` |
| Inputs | ⚠️ | חסר `border` מפורש (רק `border-gray-300/80`), חסר `min-h-[44px]`, חסר `rounded-lg` ו־`focus:ring-2 focus:ring-indigo-500/50` לפי Design System |
| Labels | ✅ | קיימים, `text-xs font-semibold uppercase` — עקביות עם שאר המערכת |
| טבלה | ✅ | thead/tbody, רווחים, ריווח |
| קישור לארגון | ✅ | `route('system.organizations.show', $org)` |
| Impersonate | ⚠️ | כפתור ללא `min-h-[44px]`, ללא `focus-visible:ring-2 ring-offset-2` |
| RTL | ⚠️ | אין `rtl:text-end` על inputs (ה־layout RTL) |
| נגישות | ⚠️ | חסר `role="main"`, `aria-label` לאזור הטבלה/פילטרים |

**תיקונים מומלצים — Index View:**  
1. החלפת כל הכפתורים/שדות לקלאסים מתוך Design System (primary/secondary/danger, inputs עם `min-h-[44px]`, `rounded-lg`, focus ring).  
2. הוספת `rtl:text-end` ל־input/select.  
3. הוספת `role="main"` ו־`aria-label` מתאימים.  
4. שימוש ב־`x-primary-button` / `x-secondary-button` / קומפוננטות קיימות במקום כפתורים עם gradient קשיח.

---

## 2. System Organizations Show

### 2.1 לוגיקה (PHP)

| נושא | סטטוס | הערה |
|------|--------|------|
| Mount | ✅ | מקבל `Organization $organization` (route model binding) |
| Password confirmation | ✅ | `requestAction` → מודל סיסמה → `confirmAndExecute` עם `Hash::check` |
| Transfer ownership | ✅ | בודק שהמשתמש חבר בארגון, מעדכן pivot ל־Admin/Owner, רישום ב־SystemAuditLogger |
| Suspend/Activate | ✅ | עדכון `is_suspended`, רישום ב־audit |
| Force delete | ✅ | try/catch על foreign key, flash הודעת שגיאה ו־redirect, אחרת audit + redirect ל־index |
| Reset data | ✅ | placeholder — רק רישום ב־audit ו־refresh |
| Render | ✅ | מעביר `owner`, `membersCount`, `events` (paginate 10) |

**שיפור אפשרי:**  
- ב־`executeTransferOwnership`: הפעולה `owner()` על ה־organization יכולה להחזיר null; הלוגיקה מטפלת (רק מעדכנת pivot אם יש currentOwner).  
- ב־`resetData`: כרגע לא מבצע פעולה על הנתונים — להשאיר placeholder או לתעד ב־UI שזו "בקשה ל־reset" בלבד.

### 2.2 תצוגה (Show Blade)

| נושא | סטטוס | נדרש |
|------|--------|------|
| Design System | ⚠️ | כפתורים עם gradient ו־rounded-xl — לא תואם Design System (primary/danger/secondary עם rounded-lg, min-h, focus ring) |
| קישור "Back" | ⚠️ | חסר `min-h-[44px]`, `focus-visible:ring-2 focus-visible:ring-offset-2` |
| Impersonate | ⚠️ | כפתור gradient — להחליף ל־primary Design System |
| כרטיסי סטטיסטיקה | ✅ | רשת 4 עמודות, טקסט קריא |
| טבלת אירועים | ✅ | כותרות, תאריך, סטטוס, pagination |
| Admin actions | ⚠️ | Suspend/Activate/Transfer/Force delete/Reset — כולם כפתורים עם gradient או border ללא Design System; חסר min-h ו־focus ring |
| מודל סיסמה | ⚠️ | input עם `rounded-xl` — להחליף ל־`rounded-lg` + `min-h-[44px]` + focus ring; כפתורי Cancel/Confirm — secondary/primary |
| RTL | ⚠️ | חסר `rtl:text-end` בשדה הסיסמה ובטקסטים ארוכים |
| נגישות | ⚠️ | `role="dialog"` למודל — טוב; חסר `aria-modal="true"`, `aria-labelledby`/`aria-describedby`, וסגירה עם Escape (אופציונלי ב־Livewire) |
| N+1 ב־Show | ⚠️ | `$organization->users()->orderBy('name')->get()` בתוך התצוגה — עדיף לחשב ב־render ולהעביר כ־`$members` |

**תיקונים מומלצים — Show View:**  
1. החלפת כל הכפתורים ל־Design System (x-primary-button, x-secondary-button, x-danger-button או קלאסים מקבילים).  
2. קישור Back ו־Impersonate — touch target 44px ו־focus-visible.  
3. שדה סיסמה — input לפי Design System (rounded-lg, min-h-[44px], focus ring), כפתורי מודל — secondary + primary.  
4. מודל אימות — הוספת `aria-modal="true"`, `aria-labelledby` (כותרת), `aria-describedby` (תיאור).  
5. העברת רשימת members מהקומפוננטה ל־view (משתנה `$members` ב־render) במקום query בתצוגה.

---

## 3. סיכום תיקונים לפי עדיפות

### P1 (עקביות + נגישות)

- **Index + Show:** החלפת כפתורים ושדות לקלאסי Design System (rounded-lg, min-h-[44px], focus-visible:ring-2 ring-offset-2).  
- **Index + Show:** הוספת `rtl:text-end` ל־inputs/selects.  
- **Show:** מודל סיסמה — `aria-modal="true"`, `aria-labelledby`, `aria-describedby`.

### P2 (איכות קוד)

- **Show:** העברת `$members` מ־render ל־view (במקום `@php $members = $organization->users()->...`) כדי למנוע query מתוך view.  
- **Index:** הוספת `role="main"` ו־`aria-label` לאזור התוכן הראשי.

### P3 (אופציונלי)

- **Index:** מיון מפורש (למשל `latest('updated_at')`) אם נדרש.  
- **Show:** תמיכה ב־Escape לסגירת מודל אימות (אם רוצים התנהגות מקבילה ל־modal כללי).

---

## 4. קבצים לעדכון

| קובץ | שינויים עיקריים | סטטוס |
|------|------------------|--------|
| `resources/views/livewire/system/organizations/index.blade.php` | Design System לכפתורים/inputs, RTL, aria | ✅ בוצע |
| `resources/views/livewire/system/organizations/show.blade.php` | Design System לכפתורים/input סיסמה, RTL, aria למודל, שימוש ב־$members מ־render | ✅ בוצע |
| `app/Livewire/System/Organizations/Show.php` | העברת `$members` ב־render (במקום query ב־Blade) | ✅ בוצע |
