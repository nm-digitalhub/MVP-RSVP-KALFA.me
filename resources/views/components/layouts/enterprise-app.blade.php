<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
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

    {{-- Enterprise App Shell - Desktop (lg+) and Mobile Drawer (below lg) --}}
    <div class="flex h-screen overflow-hidden">
        {{-- Desktop Sidebar - Only visible on lg+ screens --}}
        @if(auth()->check())
            <div class="hidden lg:flex lg:flex-shrink-0">
                <x-ent-side-bar />
            </div>
        @endif

        {{-- Main Content Area --}}
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden bg-card">
            {{-- Desktop Topbar - Only visible on lg+ screens --}}
            @if(auth()->check())
                <div class="hidden lg:block">
                    <x-ent-top-bar />
                </div>
            @endif

            {{-- Mobile Navigation - Only visible below lg screens --}}
            @if(auth()->check())
                <div class="lg:hidden">
                    <x-dynamic-navbar location="header" />
                </div>
            @endif

            {{-- Page Content - Scrollable area --}}
            <main
                id="main-content"
                role="main"
                class="flex-1 overflow-y-auto"
            >
                {{ $slot }}
            </main>
        </div>
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
