<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <meta name="color-scheme" content="light dark">

    @session('theme')
        <script>document.documentElement.setAttribute('data-theme', '{{ $value }}');</script>
    @endsession

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- PWA meta + manifest + icons --}}
    @PwaHead

    @stack('styles')

</head>

<body class="min-h-screen bg-surface antialiased text-content transition-colors duration-200">

{{-- Skip to main content (accessibility) --}}
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:start-4 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-brand focus:text-white focus:text-sm focus:font-semibold focus:shadow-lg">
    {{ __('Skip to main content') }}
</a>

<div class="min-h-screen">

    <x-dynamic-navbar location="header" />

    @session('passkey_upgrade')
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="bg-brand text-white"
        >
            <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <x-kalfa-app-icon class="h-8 w-8 rounded-xl bg-white/10 p-1.5 ring-1 ring-white/10" alt="" />
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span class="text-sm font-medium">רוצה להתחבר בפעם הבאה עם FaceID / Touch ID?</span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        id="banner-passkey-btn"
                        type="button"
                        class="text-sm font-semibold bg-white text-brand px-3 py-1 rounded-md hover:bg-brand/5 transition-colors disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                    >
                        צור מפתח זיהוי עכשיו
                    </button>
                    <button @click="show = false" type="button" class="text-brand-light hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70 rounded" title="סגור">
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
    @endsession

    @isset($header)
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </div>
    @endisset

    <main
        id="main-content"
        role="main"
        class="{{ $containerWidth ?? 'max-w-7xl' }} mx-auto px-4 py-8 sm:px-6 lg:px-8"
    >
        {{ $slot }}
    </main>

</div>

@stack('scripts')

<tallstackui:script />

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
