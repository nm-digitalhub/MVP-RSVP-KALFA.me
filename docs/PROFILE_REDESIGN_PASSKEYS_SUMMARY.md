# סיכום שינויים — Profile Views Redesign & Passkeys Design System

**תאריך:** 2026-03-15  
**ענף:** feature/4-business-areas  
**קבצים שהשתנו:** 12 קבצים

---

## תוכן עניינים

1. [תיקון באגים — modal.blade.php](#1-תיקון-באגים--modalbladephp)
2. [עיצוב מחדש — 4 קומפוננטות Profile](#2-עיצוב-מחדש--4-קומפוננטות-profile)
3. [Livewire 4 Best Practices — ManagePasskeys](#3-livewire-4-best-practices--managepasskeys)
4. [זיהוי מכשיר (Device Detection)](#4-זיהוי-מכשיר-device-detection)
5. [Passkey Nickname — עריכת שם inline](#5-passkey-nickname--עריכת-שם-inline)
6. [שיפורי אבטחה ו-UX (ביקורת קוד)](#6-שיפורי-אבטחה-ו-ux-ביקורת-קוד)
7. [Design System — Tailwind v4](#7-design-system--tailwind-v4)
8. [Current Device Indicator](#8-current-device-indicator)

---

## 1. תיקון באגים — `modal.blade.php`

**קובץ:** `resources/views/components/modal.blade.php`

### באג 1 — `{!! ... }}` מבוא שגוי
```diff
- aria-hidden="{!! $show ? 'true' : 'false' }}"
+ aria-hidden="{{ $show ? 'true' : 'false' }}"
```
**סיבה:** `{!!` ללא `!!}` מסיים גורם ל-Blade לכשול בפרסור הקובץ כולו, ומוציא את כל ה-template כטקסט גולמי לדפדפן.

### באג 2 — מרכאות כפולות בתוך `x-data`
```diff
- x-data="{ ... [tabindex="-1"] ... }"
+ x-data="{ ... [tabindex=\'-1\'] ... }"
```
**סיבה:** `"` בתוך ערך attribute HTML סוגרת את ה-attribute בטרם עת. Alpine.js מקבל fragment שבור.

---

## 2. עיצוב מחדש — 4 קומפוננטות Profile

### קבצים שהשתנו:
- `resources/views/livewire/profile/update-profile-information-form.blade.php`
- `resources/views/livewire/profile/update-password-form.blade.php`
- `resources/views/livewire/profile/delete-user-form.blade.php`
- `resources/views/livewire/profile/manage-passkeys.blade.php`

### שינויים משותפים לכל 4 קבצים:

| לפני | אחרי | עקרון |
|------|------|--------|
| `<header>` פשוט עם כותרת טקסט | אייקון עגול + כותרת + תיאור | Visual hierarchy ברור |
| אין אייקונים בשדות קלט | SVG בתוך wrapper `relative` עם `ps-9` | Affordance + accessibility |
| אין dark mode | `dark:` variants על כל אלמנט | Full dark mode support |
| כפתור Save ללא feedback | `wire:loading` spinner + `x-action-message` עם ✓ | UX responsiveness |
| `flex-shrink-0` | `shrink-0` | Tailwind v4 deprecation fix |

### `delete-user-form.blade.php` — שינויים ייחודיים:
- **Danger Zone box**: `border border-red-200 bg-red-50/50` עם כפתור Delete
- **Modal header**: אייקון אזהרה אדום + כותרת
- **Loading spinner** על כפתור Delete Account

### `manage-passkeys.blade.php` — שינויים ייחודיים:
- כפתור "הוסף מפתח זיהוי" ב-header עצמו (ולא בגוף הטופס)
- רשימת passkeys עם מידע מורחב לכל פריט
- `wire:confirm` על כפתור מחיקה
- Toast `passkey-deleted` + `passkey-renamed`

---

## 3. Livewire 4 Best Practices — ManagePasskeys

**קובץ:** `app/Livewire/Profile/ManagePasskeys.php`

### לפני:
```php
public Collection $credentials;

public function mount(): void
{
    $this->loadCredentials();
}

public function loadCredentials(): void
{
    $this->credentials = auth()->user()->webAuthnCredentials()->get();
}
```

### אחרי:
```php
#[Computed]
public function credentials(): Collection
{
    return auth()->user()->webAuthnCredentials()->latest()->limit(25)->get();
}
```

**יתרונות:**
- `#[Computed]` מחשב מחדש רק כשצריך (lazy evaluation)
- לא נשמר ב-component state בין requests
- `->latest()->limit(25)` — ביצועים + הגבלה הגיונית

### `wire:loading` ו-`wire:key` בקובץ ה-View:
```blade
{{-- wire:key מונע key collisions --}}
<li wire:key="passkey-{{ $credential->id }}">

{{-- Optimistic UI — מסתיר שורה מיד בלחיצה על מחיקה --}}
<li wire:loading.remove wire:target="delete('{{ $credential->id }}')">

{{-- Placeholder "מוחק..." מופיע במקום --}}
<li wire:loading wire:target="delete('{{ $credential->id }}')">
    מוחק מפתח זיהוי...
</li>
```

### `data-loading:` Tailwind v4 pattern:
```blade
<x-primary-button class="data-loading:opacity-75 data-loading:cursor-wait">
```

---

## 4. זיהוי מכשיר (Device Detection)

**קובץ:** `app/Livewire/Profile/ManagePasskeys.php`

### Method `resolveDeviceInfo()`:

מחזיר `array{name: string, icon: string}` לפי:
1. **AAGUID** (16 ערכים ממופים) — זיהוי מדויק
2. **Transports fallback** — `internal` → biometric, `usb` → key, `nfc` → key

### AAGUID Map (17 ערכים):

| מכשיר | AAGUID | icon type |
|-------|--------|-----------|
| Touch ID (Safari/Chrome) | `ea9b8d66-...d4` | `biometric` |
| Touch ID (Chrome) | `adce0002-...03` | `biometric` |
| iCloud Keychain | `dd4ec289-...f2` | `cloud` |
| Windows Hello (x3) | `08987058-...`, etc. | `windows` |
| Android Fingerprint | `b93fd961-...8a` | `biometric` |
| YubiKey 5 NFC | `ee882879-...2a` | `key` |
| YubiKey Security Key NFC | `f8a011f3-...d` | `key` |
| YubiKey 5Ci | `2fc0579f-...a` | `key` |
| YubiKey 5 | `cb69481e-...8` | `key` |
| Dashlane | `531126d6-...9` | `cloud` |
| Keeper | `0ea242b4-...6` | `cloud` |
| NordPass | `b84e4048-...f` | `shield` |
| Google Password Manager | `ea9b8d66-...5`, `f09a6114-...0` | `chrome` |
| 1Password | `bada5566-...d` | `shield` |

### 6 סוגי אייקונים + צבעים:

| icon | color | רקע |
|------|-------|-----|
| `biometric` | violet | `bg-violet-50 dark:bg-violet-900/30` |
| `cloud` | sky | `bg-sky-50 dark:bg-sky-900/30` |
| `windows` | blue | `bg-blue-50 dark:bg-blue-900/30` |
| `chrome` | green | `bg-green-50 dark:bg-green-900/30` |
| `shield` | amber | `bg-amber-50 dark:bg-amber-900/30` |
| `key` | indigo | `bg-indigo-50 dark:bg-indigo-900/30` |

### מטא-דאטה מוצגת לכל מפתח:
- **שם** — `alias` או `device['name']` כ-fallback
- **נוסף** — `$credential->created_at->diffForHumans()`
- **שימוש אחרון** — `updated_at` (כש-`counter > 0` ו-`updated_at != created_at`)
- **מספר שימושים** — `counter` field (WebAuthn spec)

---

## 5. Passkey Nickname — עריכת שם inline

**קבצים:** `app/Livewire/Profile/ManagePasskeys.php` + view

### Properties חדשות:
```php
public const int MAX_PASSKEYS = 10;
public ?string $editingId = null;
public string $editingAlias = '';
```

### Methods חדשות:

| Method | תפקיד |
|--------|--------|
| `prepareLatest()` | נקרא אחרי WebAuthn registration — פותח inline edit על ה-credential החדש |
| `beginRename(string $id)` | פותח inline edit על credential קיים (scoped ל-user) |
| `saveAlias()` | שומר עם validation `nullable\|string\|max:64` + `trim()` |
| `cancelRename()` | מאפס `editingId` + `editingAlias` |

### Registration Flow (JS `@script`):
```javascript
if (success) {
    statusEl.className = 'hidden';
    await $wire.prepareLatest(); // → פותח inline rename לאחר רישום
}
```

### View — Inline Edit:
```blade
@if($editingId === $credential->id)
    {{-- Inline form עם x-init auto-focus --}}
    <div class="flex items-center gap-2"
         x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
        <input wire:model="editingAlias"
               wire:keydown.enter="saveAlias"
               wire:keydown.escape="cancelRename"
               maxlength="64" ... />
        <button wire:click="saveAlias">שמור</button>
        <button wire:click="cancelRename">ביטול</button>
    </div>
@else
    {{-- Normal display row --}}
@endif
```

### כפתור "הוסף" מושבת במגבלה:
```blade
@if($this->credentials->count() >= \App\Livewire\Profile\ManagePasskeys::MAX_PASSKEYS)
    disabled title="הגעת למגבלת 10 מפתחות זיהוי"
@endif
```

---

## 6. שיפורי אבטחה ו-UX (ביקורת קוד)

### אבטחה — `beginRename()`:
`auth()->user()->webAuthnCredentials()->where('id', $id)->firstOrFail()` — scoped ל-`user_id` של המשתמש המחובר. מונע עריכת credentials של משתמש אחר.

### Validation — `saveAlias()`:
```diff
- ['required', 'string', 'max:64']
+ ['nullable', 'string', 'max:64']   // מאפשר ריקוי שם
```

### Auto-focus בעריכה:
```blade
x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())"
```

### Max Passkeys:
```php
public const int MAX_PASSKEYS = 10;
```
כפתור "הוסף" מושבת + `title` tooltip כשמגיעים ל-10.

---

## 7. Design System — Tailwind v4

**קובץ:** `resources/css/app.css`

### 6 Component Classes ב-`@layer components`:

```css
/* Livewire form design system */
.section-header {
  @apply flex items-start gap-3 mb-6;
}

.icon-wrap {
  @apply shrink-0 w-10 h-10 rounded-full flex items-center justify-center;
}

.section-title {
  @apply text-base font-semibold text-content;       /* auto dark mode */
}

.section-desc {
  @apply mt-0.5 text-sm text-content-muted;          /* auto dark mode */
}

.field-icon {
  @apply pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3;
}

.form-footer {
  @apply flex items-center gap-3 pt-1;
}
```

### שימוש בצבעי theme (`@theme` CSS vars):

| Utility | CSS Variable | Light | Dark |
|---------|-------------|-------|------|
| `text-content` | `--color-content` | `#18181B` | `#F4F4F5` |
| `text-content-muted` | `--color-content-muted` | `#71717A` | `#A1A1AA` |
| `border-stroke` | `--color-stroke` | `#E4E4E7` | `#27272A` |
| `text-brand` | `--color-brand` | `#6C4CF1` | — |

> **יתרון:** `dark:` variants נעלמים מה-Blade. ה-CSS variable מטפל בשני modes אוטומטית דרך `.dark {}` ב-app.css.

### תיקון Tailwind v4 Deprecation:
`flex-shrink-0` → `shrink-0` בכל קבצי Livewire (profile + organizations/index + dashboard/event-tables).

---

## Template — Livewire Form חדש

```blade
<section>
    <div class="section-header">
        <div class="icon-wrap bg-indigo-50 dark:bg-indigo-900/30">
            <svg class="w-5 h-5 text-brand" ...>...</svg>
        </div>
        <div>
            <h2 class="section-title">{{ __('Title') }}</h2>
            <p class="section-desc">{{ __('Description') }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div>
            <x-input-label for="field" :value="__('Label')" />
            <div class="relative mt-1">
                <div class="field-icon">
                    <svg class="w-4 h-4 text-gray-400" ...>...</svg>
                </div>
                <x-text-input wire:model="field" id="field"
                              class="block w-full ps-9" />
            </div>
            <x-input-error :messages="$errors->get('field')" class="mt-1.5" />
        </div>

        <div class="form-footer">
            <x-primary-button class="data-loading:opacity-75 data-loading:cursor-wait
                                     inline-flex items-center gap-2">
                <svg wire:loading wire:target="save"
                     class="animate-spin h-4 w-4 shrink-0" ...>...</svg>
                {{ __('Save') }}
            </x-primary-button>
            <x-action-message on="saved">
                <span class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4" ...>✓</svg>
                    {{ __('Saved.') }}
                </span>
            </x-action-message>
        </div>
    </form>
</section>
```

---

## רשימת קבצים שהשתנו

| קובץ | סוג שינוי |
|------|-----------|
| `resources/views/components/modal.blade.php` | Bug fix ×2 |
| `resources/views/livewire/profile/update-profile-information-form.blade.php` | Redesign + Design System |
| `resources/views/livewire/profile/update-password-form.blade.php` | Redesign + Design System |
| `resources/views/livewire/profile/delete-user-form.blade.php` | Redesign + Design System |
| `resources/views/livewire/profile/manage-passkeys.blade.php` | Redesign + Passkeys UX + Design System + Current Device badge |
| `app/Livewire/Profile/ManagePasskeys.php` | Livewire 4 + Device Detection + Nickname + currentCredentialId |
| `app/Listeners/StoreWebAuthnCredentialInSession.php` | **חדש** — Current Device listener |
| `app/Providers/AppServiceProvider.php` | רישום listener CredentialAsserted |
| `resources/css/app.css` | Design System (6 component classes) |
| `resources/views/livewire/organizations/index.blade.php` | `shrink-0` fix |
| `resources/views/livewire/dashboard/event-tables.blade.php` | `shrink-0` fix |

---

## 8. Current Device Indicator

### מטרה
להציג badge "✔ המכשיר הנוכחי" ליד ה-passkey שבו המשתמש השתמש כדי להתחבר לסשן הנוכחי. Pattern זהה ל-GitHub, Apple ו-Google.

### ארכיטקטורה

```
משתמש מתחבר עם Passkey (WebAuthn assertion)
         ↓
Laragear validates → fires CredentialAsserted($user, $credential)
         ↓
StoreWebAuthnCredentialInSession::handle()
→ session(['webauthn.current_credential_id' => $credential->id])
         ↓
ManagePasskeys::currentCredentialId() #[Computed]
→ returns session('webauthn.current_credential_id')
         ↓
View: @if($this->currentCredentialId === $credential->id) → badge
```

### קובץ חדש: `app/Listeners/StoreWebAuthnCredentialInSession.php`

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Laragear\WebAuthn\Events\CredentialAsserted;

class StoreWebAuthnCredentialInSession
{
    public function handle(CredentialAsserted $event): void
    {
        session(['webauthn.current_credential_id' => $event->credential->id]);
    }
}
```

**מדוע Event Listener ולא middleware:**  
`CredentialAsserted` נורה ע"י Laragear *אחרי* שהאימות הושלם — זה הנקודה הנכונה היחידה שבה ה-`credential->id` ידוע ומאומת. Middleware לא יודע איזה credential הוצג.

### שינוי ב-`AppServiceProvider.php`

```php
use App\Listeners\StoreWebAuthnCredentialInSession;

// בתוך boot():
Event::listen(
    \Laragear\WebAuthn\Events\CredentialAsserted::class,
    StoreWebAuthnCredentialInSession::class
);
```

### שינוי ב-`ManagePasskeys.php`

```php
#[Computed]
public function currentCredentialId(): ?string
{
    return session('webauthn.current_credential_id');
}
```

### Badge ב-View

```blade
<div class="flex flex-wrap items-center gap-1.5">
    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
        {{ $label }}
    </p>
    @if($this->currentCredentialId === $credential->id)
        <span class="inline-flex items-center gap-1 rounded-full
                     bg-green-50 dark:bg-green-900/30
                     px-2 py-0.5 text-xs font-medium
                     text-green-700 dark:text-green-400
                     ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30">
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            המכשיר הנוכחי
        </span>
    @endif
    {{-- pencil edit button --}}
    <button wire:click="beginRename('{{ $credential->id }}')">...</button>
</div>
```

### התנהגות לפי מצב כניסה

| אופן כניסה | badge מוצג? | סיבה |
|-----------|-------------|------|
| Passkey (WebAuthn) | ✅ כן — על ה-credential שהוצג | session key נשמר ע"י listener |
| סיסמה / OTP | ❌ לא | `CredentialAsserted` לא נורה; session key ריק |
| Logout → כניסה מחדש עם סיסמה | ❌ לא | session נמחק ב-logout |
| מעבר בין Passkeys (re-auth) | ✅ מתעדכן | listener מעדכן לאחרון שנעשה assertion |

---

## 9. תיקוני ביקורת — Read-v1.md

### 9.1 `session()->put()` במקום `session([...])`

**קובץ:** `app/Listeners/StoreWebAuthnCredentialInSession.php`

```diff
- session(['webauthn.current_credential_id' => $event->credential->id]);
+ request()->session()->put('webauthn.current_credential_id', $event->credential->id);
```

**סיבה:** `request()->session()->put()` מפורש ומתעד שמדובר בsession של הבקשה הנוכחית.

---

### 9.2 ניקוי מפורש של session בעת Logout

**קובץ:** `app/Http/Controllers/Auth/LogoutController.php`

```diff
  Auth::guard('web')->logout();

+ $request->session()->forget('webauthn.current_credential_id');
  $request->session()->invalidate();
```

**הערה:** `invalidate()` מנקה את כל הsession ממילא — השורה המפורשת מתעדת כוונה ומגנה מפני רגרסיות עתידיות.

---

### 9.3 שיפור UX — "שימוש אחרון: עכשיו"

**קובץ:** `resources/views/livewire/profile/manage-passkeys.blade.php`

```diff
- $usedAt = ($credential->counter > 0 && $credential->updated_at?->ne($credential->created_at))
-               ? 'שימוש אחרון ' . $credential->updated_at->diffForHumans()
-               : 'טרם נעשה שימוש';
+ $usedAt = $this->currentCredentialId === $credential->id
+               ? 'שימוש אחרון: עכשיו'
+               : (($credential->counter > 0 && $credential->updated_at?->ne($credential->created_at))
+                   ? 'שימוש אחרון ' . $credential->updated_at->diffForHumans()
+                   : 'טרם נעשה שימוש');
```

**תוצאה:** ה-credential הנוכחי מציג "שימוש אחרון: עכשיו" במקום timestamp יחסי.

---

## Template — Livewire Form חדש