# Design System — Tailwind (מקור אמת יחיד)

מסמך זה מגדיר טוקנים וקלאסים לשימוש עקבי בכל `resources/views` ו־Livewire.  
מבוסס על **Tailwind CSS v4** וכלי **Tailwind MCP** (utilities, focus states, component templates).

---

## 1. עקרונות

- **נגישות**: `focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-* focus-visible:ring-offset-2` על כל אלמנט אינטראקטיבי.
- **Touch**: כפתורים וקישורים פעילים — `min-h-[44px]` (או `min-w-[44px]` לכפתורי אייקון).
- **RTL**: שימוש ב־`rtl:` רק כשצריך override; Layout קובע `dir="rtl"`.
- **עקביות**: צבע ראשי — `indigo`; סכנה — `red`; משני — `gray` (border/background).

---

## 2. כפתורים

### 2.1 Primary (פעולה ראשית)

```
inline-flex items-center justify-center min-h-[44px] px-4 py-2.5
bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
border border-transparent rounded-lg
text-sm font-medium text-white
focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2
transition-colors duration-200
```

- שימוש: Submit, Create, Save, Continue.
- לא: uppercase tracking-widest (מוריד קריאות).

### 2.2 Secondary (ביטול / משני)

```
inline-flex items-center justify-center min-h-[44px] px-4 py-2.5
bg-white border border-gray-300 rounded-lg
text-sm font-medium text-gray-700
hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2
disabled:opacity-50 transition-colors duration-200
```

### 2.3 Danger (מחיקה / פעולה הרסנית)

```
inline-flex items-center justify-center min-h-[44px] px-4 py-2.5
bg-red-600 hover:bg-red-700 active:bg-red-800
border border-transparent rounded-lg
text-sm font-medium text-white
focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2
transition-colors duration-200
```

---

## 3. שדות טופס (Input / Textarea)

### 3.1 Base (input + textarea)

- **גודל**: `min-h-[44px]` ל־input (ניתן override ל־textarea עם rows).
- **מסגרת**: `border border-gray-300 rounded-lg shadow-sm w-full`.
- **Focus**: `focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0` (או `focus:ring-offset-2` אם רוצים ריווח).
- **RTL**: `rtl:text-end` ב־textarea.
- **Dark** (אופציונלי): `dark:border-gray-600 dark:bg-gray-800 dark:text-white`.

### 3.2 Label

- `block text-sm font-medium text-gray-700 rtl:text-end`.

### 3.3 Error message

- `mt-2 text-sm text-red-600 rtl:text-end space-y-1`.

---

## 4. כרטיסים (Cards)

- **מכלול**: `bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden`.
- **כותרת כרטיס**: `px-4 py-4 sm:px-6 border-b border-gray-200` + `text-lg font-medium text-gray-900`.
- **תוכן**: `p-4 sm:p-6` או `px-4 py-3 sm:px-6`.

---

## 5. טבלאות

- **מעטפת**: `min-w-full divide-y divide-gray-200`.
- **thead**: `bg-gray-50`; **th**: `px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider` (RTL: text-start מתחלף אוטומטית).
- **tbody**: `bg-white divide-y divide-gray-200`; **td**: `px-4 py-3 text-sm text-gray-900` / `text-gray-600`.
- **Empty**: `colspan` + `px-4 py-8 text-center text-sm text-gray-500`.

---

## 6. Badges (סטטוס)

- **נייטרלי**: `inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800`.
- **הצלחה**: `bg-green-100 text-green-800`.
- **אזהרה**: `bg-amber-100 text-amber-800`.
- **שגיאה**: `bg-red-100 text-red-800`.
- **מידע**: `bg-indigo-100 text-indigo-800`.

---

## 7. Spacing (מרווחים)

- **סקשן**: `space-y-6` או `gap-6`.
- **טפסים**: `space-y-4` בין שדות; `space-y-6` בין בלוקים.
- **כפתורים בשורה**: `gap-2` או `gap-3`.

---

## 8. רפרנס Tailwind MCP

- **Focus states**: Hover, focus, and other states — שימוש ב־`focus-visible:` לנגישות.
- **Spacing**: p-2, p-4, p-6, px-4 py-2.5 — סולם עקבי.
- **Component templates**: כפתור (modern) — inline-flex, rounded-md, focus-visible:ring; טופס — space-y-2, rounded-md, border.

---

## 9. קומפוננטות Blade (ממופות)

| קומפוננטה | קובץ | תואם Design System |
|-----------|------|---------------------|
| Primary button | `components/primary-button.blade.php` | כן — indigo, rounded-lg, min-h-[44px], focus-visible:ring-2 |
| Secondary button | `components/secondary-button.blade.php` | כן — border gray, rounded-lg |
| Danger button | `components/danger-button.blade.php` | כן — red, rounded-lg |
| Text input | `components/text-input.blade.php` | כן — rounded-lg, focus:ring-2 focus:ring-indigo-500/50, min-h-[44px] |
| Textarea | `components/textarea.blade.php` | כן — rounded-lg, focus:ring-2, rtl:text-end |
| Input label | `components/input-label.blade.php` | כן — text-sm font-medium text-gray-700 rtl:text-end |
| Input error | `components/input-error.blade.php` | כן — text-sm text-red-600 rtl:text-end |

*מסמך זה מעודכן לפי שימוש בכלי Tailwind MCP ועדכון קומפוננטות Blade.*
