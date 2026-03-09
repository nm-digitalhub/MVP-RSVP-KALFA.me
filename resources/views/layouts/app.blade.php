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