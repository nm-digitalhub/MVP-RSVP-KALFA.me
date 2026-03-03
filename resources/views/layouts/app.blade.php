<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', isset($title) ? $title : config('app.name', 'Laravel'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <meta name="theme-color" content="#3b82f6">
    <link rel="icon" sizes="192x192" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
</head>
<body class="bg-[#F9FAFB] antialiased text-gray-900">
    <div class="min-h-screen bg-gray-50">
        <x-dynamic-navbar location="header" />

        @hasSection('header')
            <div class="bg-white border-b">
                <div class="max-w-7xl mx-auto px-4 py-6">
                    @yield('header')
                </div>
            </div>
        @endif

        <main class="@yield('containerWidth', 'max-w-7xl') mx-auto px-4 py-8">
            @yield('content', $slot ?? '')
        </main>
    </div>
    @stack('scripts')
    @livewireScripts
    @if(isset($paymentGatewayConfig))
    <script>
        window.paymentGatewayConfig = @json($paymentGatewayConfig);
    </script>
    @endif
    @if(config('app.debug') && class_exists('Barryvdh\Debugbar\ServiceProvider'))
    {!! app('debugbar')->render() !!}
    @endif
</body>
</html>
