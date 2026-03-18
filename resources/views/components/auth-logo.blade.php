<div class="mx-auto flex justify-center">
    <a href="{{ route('home') }}" class="group flex items-center justify-center transition duration-300 ease-out hover:opacity-95 hover:drop-shadow-[0_18px_35px_rgba(108,76,241,0.14)]">
        <img
            src="{{ asset('images/Logo-Kalfa/Logo_App_Icon.svg') }}"
            alt="{{ config('app.name') }}"
            class="h-16 w-auto object-contain transition duration-300 ease-out group-hover:-translate-y-0.5 sm:hidden motion-reduce:transform-none"
            onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_App_Icon.png') }}'"
        >

        <span class="hidden items-center gap-3 sm:flex">
            <img
                src="{{ asset('images/Logo-Kalfa/Logo_Icon_(K).svg') }}"
                alt=""
                class="h-11 w-auto object-contain transition duration-300 ease-out group-hover:-translate-y-0.5 motion-reduce:transform-none"
                onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_Icon_(K).png') }}'"
            >
            <img
                src="{{ asset('images/Logo-Kalfa/Logo_Text_(Kalfa).svg') }}"
                alt="{{ config('app.name') }}"
                class="h-10 w-auto object-contain transition duration-300 ease-out group-hover:translate-x-0.5 motion-reduce:transform-none"
                onerror="this.onerror=null;this.src='{{ asset('images/Logo-Kalfa/Logo_Text_(Kalfa).png') }}'"
            >
        </span>
    </a>
</div>
