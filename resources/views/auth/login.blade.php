<x-layouts.guest>
    <x-slot:title>התחברות</x-slot:title>

@php
    $companyName = config('app.name');
    $logoUrl = asset('images/nm-logo-current.png');
@endphp

<div class="min-h-screen flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <a href="{{ route('home') }}">
                    <img src="{{ $logoUrl }}" alt="{{ $companyName }} Logo" class="h-12 w-auto">
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.sign_in') }}
            </h2>
            
            @if(session('url.intended') && str_contains(session('url.intended'), 'esim'))
                <div class="mt-3 text-center p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                        <span class="text-sm font-medium text-blue-800">רכישת eSIM</span>
                    </div>
                    <p class="text-xs text-blue-700">לאחר ההתחברות תועבר חזרה להשלמת הרכישה עם מילוי אוטומטי של הפרטים</p>
                </div>
            @endif
            
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('auth.or') }}
                <a href="{{ route('register') }}" class="font-medium text-brand hover:text-brand">
                    {{ __('auth.register_new_account') }}
                </a>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">{{ __('auth.email_placeholder') }}</label>
                    <input id="email" name="email" type="email" autocomplete="username webauthn" required 
                           value="{{ old('email') }}"
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-brand focus:border-brand focus:z-10 sm:text-sm @error('email') border-red-500 @enderror" 
                           placeholder="{{ __('auth.email_placeholder') }}">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="relative">
                    <label for="password" class="sr-only">{{ __('auth.password_placeholder') }}</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-brand focus:border-brand focus:z-10 sm:text-sm @error('password') border-red-500 @enderror" 
                           placeholder="{{ __('auth.password_placeholder') }}">
                    <div class="absolute inset-y-0 left-0 ps-3 flex items-center">
                        <button type="button" id="togglePassword" class="text-gray-400 hover:text-content-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:rounded transition-colors p-0.5">
                            <svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eyeOffIcon" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 .95-3.112 3.543-5.45 6.836-6.164M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.542 12c-1.274 4.057-5.064 7-9.542 7a9.953 9.953 0 01-2.212-.332M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if(session('status'))
                <div class="rounded-md bg-green-50 p-4">
                    <div class="text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between flex-wrap">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" 
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded">
                    <label for="remember" class="me-2 block text-sm text-gray-900">
                        {{ __('auth.remember_me') }}
                    </label>
                </div>

                <div class="text-sm">
                    <a href="{{ route('password.request') }}" class="font-medium text-brand hover:text-brand">
                        {{ __('auth.forgot_password') }}
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand">
                    <span id="buttonText">{{ __('auth.sign_in_button') }}</span>
                    <span class="absolute left-0 inset-y-0 flex items-center pe-3">
                        <svg class="h-5 w-5 text-brand group-hover:text-brand-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <svg id="loadingSpinner" class="animate-spin -ms-1 me-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    {{ __('auth.no_account') }}
                    <a href="{{ route('register') }}" class="font-medium text-brand hover:text-brand">
                        {{ __('auth.register_here') }}
                    </a>
                </p>
            </div>
        </form>

        {{-- Passkey login — outside the form to prevent accidental submit --}}
        <div class="relative mt-2">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-surface text-content-muted">
                    {{ __('auth.or_sign_in_with') }}
                </span>
            </div>
        </div>

        <div class="mt-4">
            <button type="button" id="passkey-login-btn"
                    data-redirect="{{ session('url.intended', route('dashboard')) }}"
                    data-msg-failed="{{ __('auth.passkey_failed') }}"
                    data-msg-error="{{ __('auth.passkey_error') }}"
                    aria-label="{{ __('auth.sign_in_with_passkey') }}"
                    class="w-full flex justify-center items-center gap-2 py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                {{ __('auth.sign_in_with_passkey') }}
            </button>
            <p class="mt-1 text-center text-xs text-gray-400">{{ __('auth.passkey_hint') }}</p>
            <p id="passkey-error" role="alert" aria-live="polite" class="hidden mt-2 text-sm text-red-600 text-center"></p>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOffIcon = document.getElementById('eyeOffIcon');

    togglePassword.addEventListener('click', function (e) {
        // toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        // toggle the eye slash icon
        eyeIcon.classList.toggle('hidden');
        eyeOffIcon.classList.toggle('hidden');
    });

    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        buttonText.classList.add('hidden');
        loadingSpinner.classList.remove('hidden');
    });
</script>

@vite('resources/js/passkey-login.js')
</x-layouts.guest>