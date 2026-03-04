# Livewire v4 — סריקה ויישום Best Practices

תאריך: לפי ריצת הסריקה.

---

## 1. מה בוצע

### 1.1 wire:key בלולאות

נוסף `wire:key` לאלמנט הראשון בתוך כל `@foreach` / `@forelse` בתצוגות Livewire, כדי למנוע component mismatch ורינדור שגוי (לפי [תיעוד Livewire 4](https://livewire.laravel.com/docs/nesting#rendering-children-in-a-loop)).

| קובץ | לולאה | wire:key |
|------|--------|----------|
| livewire/system/organizations/show.blade.php | events, members | `event-{{ $event->id }}`, `member-{{ $m->id }}` |
| livewire/system/organizations/index.blade.php | organizations | `org-{{ $org->id }}` |
| livewire/organizations/index.blade.php | organizations | `org-{{ $org->id }}` |
| livewire/dashboard/event-guests.blade.php | guests | `guest-{{ $guest->id }}` |
| livewire/dashboard/event-tables.blade.php | tables | `table-{{ $t->id }}` |
| livewire/dashboard/event-invitations.blade.php | guestsWithoutInvitation, invitations | `guest-{{ $g->id }}`, `invitation-{{ $inv->id }}` |
| livewire/dashboard/event-seat-assignments.blade.php | guests, tables | `guest-{{ $guest->id }}`, `table-{{ $t->id }}` (ב־option) |
| livewire/dashboard.blade.php | events | `event-{{ $event->id }}` |
| livewire/billing/entitlements-index.blade.php | entitlements | `entitlement-{{ $e->id }}` |
| livewire/billing/billing-intents-index.blade.php | intents | `intent-{{ $intent->id }}` |
| livewire/billing/usage-index.blade.php | usage | `usage-{{ $u->id }}` |
| livewire/system/accounts/index.blade.php | accounts | `account-{{ $account->id }}` |
| livewire/system/accounts/show.blade.php | organizationsAvailable, organizationsAttached, entitlements, usage, billingIntents | `org-*`, `entitlement-*`, `usage-*`, `intent-*` |
| livewire/system/dashboard.blade.php | recentOrganizations, recentUsers | `org-{{ $org->id }}`, `user-{{ $u->id }}` |
| livewire/system/users/index.blade.php | users | `user-{{ $u->id }}` |
| livewire/system/users/show.blade.php | user->organizations | `org-{{ $org->id }}` |

### 1.2 תיקון תחביר Blade (system/dashboard)

- `@forelse($recentOrganizations ?? [] as $org)` תוקן ל־`@forelse(($recentOrganizations ?? []) as $org)`.
- אותו תיקון ל־`@forelse($recentUsers ?? [] as $u)`.

### 1.3 קונפיג Livewire (v4)

- נוספו מפתחות v4: `component_layout` => `'layouts::app'`, `component_placeholder` => `null`, `smart_wire_keys` => `true`.
- נשמרו המפתחות הישנים (`layout`, `lazy_placeholder`) לתאימות.

### 1.4 ניתוב (Routes)

- נתיבי System Admin שהיו `Route::get(..., LivewireClass::class)` הוחלפו ל־`Route::livewire(...)` (ההמלצה ב־Livewire 4).

---

## 2. סטטוס שימוש ב־wire:model

- **wire:model.live.debounce** — בשימוש בפילטרים/חיפוש (system organizations, system users, system accounts, usage-index). מתאים לעדכון מיידי עם debounce.
- **wire:model** (ללא .live) — בשאר השדות (טפסים, סיסמה, selectים). מתאים כשמעדכנים רק ב־submit או ב־blur.
- לא נמצא שימוש ב־wire:model על קונטיינר (modal/accordion) — אין צורך ב־`.deep`.

---

## 3. המלצות אופציונליות

### 3.1 wire:loading

- כרגע אין שימוש ב־`wire:loading` / `wire:loading.delay` בתצוגות.
- מומלץ להוסיף מצבי טעינה לפעולות כמו: שמירה, מחיקה, החלפת בעלות, וכו', למשל:
  - `wire:loading.flex` על כפתור או אזור הפעולה,
  - או `wire:loading.class="opacity-50 pointer-events-none"` על טופס/כפתור.

### 3.2 בדיקות

- להריץ את כל הנתיבים של System (dashboard, organizations, users, accounts) ולוודא רינדור ותפקוד.
- לוודא ש־Route::livewire עם `->scopeBindings()` עובד כמצופה (אם יש שגיאות, לבדוק את המאקרו של Livewire).

---

## 4. קבצים שעודכנו

- `config/livewire.php` — הוספת מפתחות v4.
- `routes/web.php` — מעבר ל־Route::livewire בנתיבי system.
- כל תצוגות ה־livewire ברשימה למעלה — הוספת `wire:key` ותיקון forelse ב־dashboard.

---

## 5. שיפורים נוספים (קריאה ידנית + Livewire v4)

### 5.1 טיפוסי החזרה ו־strict_types

- **render(): View** — נוסף או אומת בכל קומפוננטות Livewire (Dashboard, Organizations/Index/Create, Profile/*, System/*, Dashboard/Event*).
- **declare(strict_types=1)** — נוסף ב־Dashboard, Organizations, Profile, System (שם שלא היה).
- **EventSeatAssignments** — `@var array<int, int|string>` ל־`$assignments` (ערך ריק = אין שולחן).

### 5.2 מה נבדק ולא דרש שינוי

- **wire:confirm** — בשימוש תקין (מחיקה, ניתוק); v4 תומך גם ב־`.prompt`.
- **#[Layout('layouts.app')]** — נשאר; תאימות ל־view path הקיים.
- **Billing** — EntitlementsIndex, BillingIntentsIndex, UsageIndex, AccountOverview — כבר עם `render(): View` ו־strict_types.
