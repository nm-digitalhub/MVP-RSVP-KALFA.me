<x-layouts.guest>
    <x-slot:title>אישור סיסמה</x-slot:title>

<div class="auth-screen">
    <div class="auth-shell">
        <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-kicker">גישה רגישה</div>
            <x-auth-logo />
            <h2 class="auth-title">
                אישור סיסמה
            </h2>
            <p class="auth-subtitle">
                זוהי אזור מאובטח. אנא אשר את הסיסמה שלך לפני המשך.
            </p>
        </div>
        
        <form class="auth-form" action="{{ route('password.confirm') }}" method="POST">
            @csrf
            
            <div>
                <label for="password" class="auth-label">סיסמה</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required 
                       class="auth-input" 
                       placeholder="הזן את הסיסמה שלך">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" 
                        class="auth-primary-button group relative">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-white/70 transition group-hover:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    אשר סיסמה
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('password.request') }}" class="auth-link text-sm">
                    שכחת את הסיסמה?
                </a>
            </div>
        </form>

        <div class="auth-status auth-status-warning">
            <div class="flex">
                <div class="shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="me-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        אזור מאובטח
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>
                            אתה עומד לגשת לאזור רגיש במערכת. מטעמי אבטחה, אנא אשר את הסיסמה שלך.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
</x-layouts.guest>
