@props(['alt' => null])

<img
    src="{{ asset('images/Logo-Kalfa/Logo_App_Icon.svg') }}"
    alt="{{ $alt ?? config('app.name') }}"
    {{ $attributes->merge(['class' => 'h-12 w-12 object-contain']) }}
    onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_App_Icon.png') }}'"
>
