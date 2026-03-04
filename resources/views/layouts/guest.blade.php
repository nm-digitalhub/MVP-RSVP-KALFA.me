<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire styles --}}
    @livewireStyles

    @stack('styles')

</head>

<body class="min-h-screen bg-gray-50 antialiased text-gray-900">

<div class="min-h-screen px-4 py-8 sm:py-12">

    {{ $slot }}

</div>

@stack('scripts')

{{-- Livewire scripts --}}
@livewireScripts

</body>

</html>