<div>
    {{-- Header --}}
    <div class="section-header">
        <div class="icon-wrap bg-brand/10">
            <svg class="w-5 h-5 text-brand dark:text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="section-title">מפתחות זיהוי (Passkeys)</h2>
            <p class="section-desc">כניסה ללא סיסמה באמצעות ביומטריה, Face ID, Touch ID או Windows Hello.</p>
        </div>
        <button
            id="passkey-register-btn"
            type="button"
            @if($this->credentials->count() >= \App\Livewire\Profile\ManagePasskeys::MAX_PASSKEYS) disabled title="הגעת למגבלת {{ \App\Livewire\Profile\ManagePasskeys::MAX_PASSKEYS }} מפתחות זיהוי" @endif
            class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-brand dark:bg-brand text-white text-sm font-medium rounded-lg hover:bg-brand-hover dark:hover:bg-brand focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            הוסף מפתח זיהוי
        </button>
    </div>

    {{-- Minimum credentials nudge --}}
    @if($this->credentials->count() === 1)
        <div class="mb-4 flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-sm text-amber-800 dark:text-amber-300">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>מומלץ לשמור לפחות שני מפתחות זיהוי (למשל: טלפון + מחשב) כדי למנוע נעילה מחוץ לחשבון אם מכשיר אחד אינו זמין.</span>
        </div>
    @endif

    {{-- Status messages --}}
    <div id="passkey-status" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

    {{-- Feedback toasts --}}
    <x-action-message on="passkey-deleted">
        <div class="mb-4 flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg text-sm text-green-700 dark:text-green-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            מפתח הזיהוי נמחק בהצלחה.
        </div>
    </x-action-message>

    <x-action-message on="passkey-renamed">
        <div class="mb-4 flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg text-sm text-green-700 dark:text-green-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            שם המפתח עודכן.
        </div>
    </x-action-message>

    {{-- Registered passkeys --}}
    @if($this->credentials->isEmpty())
        <div class="text-center py-10 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <p class="text-sm">עדיין לא רשמת מפתחות זיהוי.</p>
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-700" id="passkeys-list">
            @foreach($this->credentials as $credential)
                @php
                    $device   = $this->resolveDeviceInfo($credential);
                    $label    = $credential->alias ?: $device['name'];
                    $usedAt   = $this->currentCredentialId === $credential->id
                                    ? 'שימוש אחרון: עכשיו'
                                    : (($credential->counter > 0 && $credential->updated_at?->ne($credential->created_at))
                                        ? 'שימוש אחרון ' . $credential->updated_at->diffForHumans()
                                        : 'טרם נעשה שימוש');
                    $addedAt  = 'נוסף ' . $credential->created_at?->diffForHumans();
                    $useCount = $credential->counter > 0
                                    ? 'שומש ' . number_format($credential->counter) . ' פעמים'
                                    : null;
                @endphp
                <li wire:key="passkey-{{ $credential->id }}"
                    wire:loading.remove wire:target="delete('{{ $credential->id }}')"
                    class="py-3.5">

                    @if($editingId === $credential->id)
                        {{-- ── Inline rename form ── --}}
                        <div class="flex items-center gap-2" x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                            <div class="flex-1 min-w-0">
                                <input
                                    wire:model="editingAlias"
                                    wire:keydown.enter="saveAlias"
                                    wire:keydown.escape="cancelRename"
                                    type="text"
                                    maxlength="64"
                                    autofocus
                                    placeholder="לדוגמה: MacBook Pro, iPhone 15..."
                                    class="w-full min-h-[38px] rounded-lg border border-brand/50 dark:border-brand bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-brand/50 rtl:text-end"
                                />
                                <x-input-error :messages="$errors->get('editingAlias')" class="mt-1" />
                            </div>
                            <button
                                wire:click="saveAlias"
                                wire:loading.attr="disabled"
                                wire:target="saveAlias"
                                type="button"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-brand hover:bg-brand-hover text-white text-sm font-medium rounded-lg disabled:opacity-50 transition-colors"
                            >
                                <svg wire:loading wire:target="saveAlias" class="animate-spin motion-reduce:animate-none h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                שמור
                            </button>
                            <button
                                wire:click="cancelRename"
                                type="button"
                                class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors"
                            >
                                ביטול
                            </button>
                        </div>
                    @else
                        {{-- ── Normal display row ── --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Device icon --}}
                                <div class="icon-wrap
                                    {{ $device['icon'] === 'biometric' ? 'bg-violet-50 dark:bg-violet-900/30' : '' }}
                                    {{ $device['icon'] === 'cloud'     ? 'bg-sky-50 dark:bg-sky-900/30'       : '' }}
                                    {{ $device['icon'] === 'windows'   ? 'bg-blue-50 dark:bg-blue-900/30'     : '' }}
                                    {{ $device['icon'] === 'chrome'    ? 'bg-green-50 dark:bg-green-900/30'   : '' }}
                                    {{ $device['icon'] === 'shield'    ? 'bg-amber-50 dark:bg-amber-900/30'   : '' }}
                                    {{ $device['icon'] === 'key'       ? 'bg-brand/10' : '' }}
                                ">
                                    @if($device['icon'] === 'biometric')
                                        <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                                        </svg>
                                    @elseif($device['icon'] === 'cloud')
                                        <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                                        </svg>
                                    @elseif($device['icon'] === 'windows')
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-13.051-1.851"/>
                                        </svg>
                                    @elseif($device['icon'] === 'chrome')
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    @elseif($device['icon'] === 'shield')
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m0-5a9 9 0 11-6.219 15.568"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-brand dark:text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $label }}</p>
                                        @if($this->currentCredentialId === $credential->id)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                המכשיר הנוכחי
                                            </span>
                                        @endif
                                        <button
                                            wire:click="beginRename('{{ $credential->id }}')"
                                            type="button"
                                            title="שנה שם"
                                            class="text-gray-300 hover:text-brand dark:text-gray-600 dark:hover:text-brand-light transition-colors focus:outline-none"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-2 mt-0.5">
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $addedAt }}</span>
                                        <span class="text-xs text-gray-300 dark:text-gray-600" aria-hidden="true">·</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $usedAt }}</span>
                                        @if($useCount)
                                            <span class="text-xs text-gray-300 dark:text-gray-600" aria-hidden="true">·</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $useCount }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Delete --}}
                            <button
                                wire:click="delete('{{ $credential->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="delete('{{ $credential->id }}')"
                                wire:confirm="האם למחוק מפתח זיהוי זה?"
                                type="button"
                                class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 dark:hover:text-red-400 focus:outline-none disabled:opacity-50 transition-colors ms-3 shrink-0"
                            >
                                <svg wire:loading wire:target="delete('{{ $credential->id }}')" class="animate-spin motion-reduce:animate-none h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                מחק
                            </button>
                        </div>
                    @endif
                </li>
                <li wire:loading wire:target="delete('{{ $credential->id }}')"
                    class="flex items-center gap-2.5 py-3 text-sm text-gray-400 dark:text-gray-500">
                    <svg class="animate-spin motion-reduce:animate-none h-4 w-4 shrink-0 text-brand-light" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    מוחק מפתח זיהוי...
                </li>
            @endforeach
        </ul>
    @endif
</div>

@script
<script>
    const btn = document.getElementById('passkey-register-btn');
    const statusEl = document.getElementById('passkey-status');
    const Webpass = window.Webpass;

    function showStatus(message, isError = false) {
        statusEl.textContent = message;
        statusEl.className = isError
            ? 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200'
            : 'mb-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
    }

    if (!Webpass || Webpass.isUnsupported()) {
        btn.style.display = 'none';
    } else {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const { success, error } = await Webpass.attest(
                    '/webauthn/register/options',
                    '/webauthn/register'
                );

                if (success) {
                    statusEl.className = 'hidden';
                    await $wire.prepareLatest();
                } else {
                    showStatus(error ?? 'הרישום נכשל. נסה שנית.', true);
                }
            } catch (e) {
                showStatus('שגיאה: ' + (e.message ?? 'הרישום נכשל'), true);
            } finally {
                btn.disabled = false;
            }
        });
    }
</script>
@endscript
