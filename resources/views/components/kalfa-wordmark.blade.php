@props(['alt' => null])

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    <img
        src="{{ asset('images/Logo-Kalfa/Logo_Icon_(K).svg') }}"
        alt="{{ $alt ?? config('app.name') }}"
        class="h-8 w-auto shrink-0 object-contain sm:h-9"
        onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_Icon_(K).png') }}'"
    >

    <img
        src="{{ asset('images/Logo-Kalfa/Logo_Text_(Kalfa).svg') }}"
        alt="{{ $alt ?? config('app.name') }}"
        class="h-6 max-w-[7.5rem] w-auto object-contain dark:brightness-0 dark:invert sm:h-8 sm:max-w-none"
        onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_Text_(Kalfa).png') }}'"
    >
</span>
