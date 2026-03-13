<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <meta name="color-scheme" content="light dark">

    @if(session()->has('theme'))
        <script>document.documentElement.setAttribute('data-theme', '{{ session('theme') }}');</script>
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- PWA meta + manifest + icons --}}
    @PwaHead

    @stack('styles')

</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900 antialiased text-gray-900 dark:text-gray-100 transition-colors duration-200">

<div class="min-h-screen">

    <x-dynamic-navbar location="header" />

    @if(session('passkey_upgrade'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="bg-indigo-600 text-white"
        >
            <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span class="text-sm font-medium">רוצה להתחבר בפעם הבאה עם FaceID / Touch ID?</span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        id="banner-passkey-btn"
                        type="button"
                        class="text-sm font-semibold bg-white text-indigo-700 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors disabled:opacity-60"
                    >
                        צור מפתח זיהוי עכשיו
                    </button>
                    <button @click="show = false" type="button" class="text-indigo-200 hover:text-white" title="סגור">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('banner-passkey-btn');
                if (!btn || !window.Webpass || window.Webpass.isUnsupported()) {
                    if (btn) btn.style.display = 'none';
                    return;
                }
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.textContent = 'יוצר...';
                    try {
                        const { success, error } = await window.Webpass.attest(
                            '/webauthn/register/options',
                            '/webauthn/register'
                        );
                        if (success) {
                            btn.closest('[x-data]').__x.$data.show = false;
                        } else {
                            btn.disabled = false;
                            btn.textContent = 'צור מפתח זיהוי עכשיו';
                            alert(error ?? 'הרישום נכשל. נסה שנית.');
                        }
                    } catch (e) {
                        btn.disabled = false;
                        btn.textContent = 'צור מפתח זיהוי עכשיו';
                    }
                });
            });
        </script>
    @endif

    @isset($header)
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </div>
    @endisset

    <main
        role="main"
        class="{{ $containerWidth ?? 'max-w-7xl' }} mx-auto px-4 py-8 sm:px-6 lg:px-8"
    >
        {{ $slot }}
    </main>

</div>

@stack('scripts')

{{-- Register PWA service worker --}}
@RegisterServiceWorkerScript

@isset($paymentGatewayConfig)
<script>
window.paymentGatewayConfig = @json($paymentGatewayConfig);
</script>
@endisset

@if(config('app.debug') && class_exists('Barryvdh\Debugbar\ServiceProvider'))
{!! app('debugbar')->render() !!}
@endif

</body>
</html>