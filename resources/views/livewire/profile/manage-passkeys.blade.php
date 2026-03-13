<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">מפתחות זיהוי (Passkeys)</h3>
            <p class="text-sm text-gray-500 mt-1">כניסה ללא סיסמה באמצעות ביומטריה, Face ID, Touch ID או Windows Hello.</p>
        </div>
        <button
            id="passkey-register-btn"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            הוסף מפתח זיהוי
        </button>
    </div>

    {{-- Minimum credentials nudge --}}
    @if($credentials->count() === 1)
        <div class="mb-4 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>מומלץ לשמור לפחות שני מפתחות זיהוי (למשל: טלפון + מחשב) כדי למנוע נעילה מחוץ לחשבון אם מכשיר אחד אינו זמין.</span>
        </div>
    @endif

    {{-- Status messages --}}
    <div id="passkey-status" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

    {{-- Registered passkeys --}}
    @if($credentials->isEmpty())
        <div class="text-center py-8 text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <p>עדיין לא רשמת מפתחות זיהוי.</p>
        </div>
    @else
        <ul class="divide-y divide-gray-100" id="passkeys-list">
            @foreach($credentials as $credential)
                @php
                    $transports = $credential->transports ?? [];
                    $transportLabel = match(true) {
                        in_array('internal', $transports) => 'ביומטרי (FaceID / TouchID)',
                        in_array('usb', $transports)      => 'USB Security Key',
                        in_array('nfc', $transports)      => 'NFC Key',
                        in_array('ble', $transports)      => 'Bluetooth Key',
                        default                           => 'מפתח זיהוי',
                    };
                    $lastUsed = $credential->updated_at && $credential->updated_at != $credential->created_at
                        ? 'שימוש אחרון ' . $credential->updated_at->diffForHumans()
                        : 'טרם נעשה שימוש';
                @endphp
                <li class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $credential->alias ?? $transportLabel }}</p>
                            <p class="text-xs text-gray-400">
                                נרשם {{ $credential->created_at?->diffForHumans() }} · {{ $lastUsed }}
                            </p>
                        </div>
                    </div>
                    <button
                        wire:click="delete('{{ $credential->id }}')"
                        wire:confirm="האם למחוק מפתח זיהוי זה?"
                        type="button"
                        class="text-sm text-red-500 hover:text-red-700 focus:outline-none"
                    >
                        מחק
                    </button>
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
                    showStatus('מפתח הזיהוי נרשם בהצלחה! ✓');
                    $wire.$refresh();
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
