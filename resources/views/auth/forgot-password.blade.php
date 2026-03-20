<x-layouts.guest>
    <x-slot:title>איפוס סיסמה</x-slot:title>

<div class="auth-screen">
    <div class="auth-shell">
        <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-kicker">שחזור גישה</div>
            <x-auth-logo />
            <h2 class="auth-title">
                איפוס סיסמה
            </h2>
            <p class="auth-subtitle">
                שכחת את הסיסמה? אין בעיה! הזן את כתובת האימייל שלך ונשלח לך קישור לאיפוס.
            </p>
        </div>
        
        <form class="auth-form" action="{{ route('password.email') }}" method="POST">
            @csrf
            
            <div>
                <label for="email" class="auth-label">כתובת אימייל</label>
                <input id="email" name="email" type="email" autocomplete="email" required 
                       value="{{ old('email') }}"
                       class="auth-input" 
                       placeholder="האימייל שלך">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @session('status')
                <div class="auth-status auth-status-success">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="me-3">
                            <p class="text-sm text-green-800">
                                {{ $value }}
                            </p>
                        </div>
                    </div>
                </div>
            @endsession

            <div>
                <button type="submit" 
                        class="auth-primary-button group relative">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-white/70 transition group-hover:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </span>
                    שלח קישור לאיפוס
                </button>
            </div>

            <div class="auth-inline-links space-y-2">
                <p>
                    <a href="{{ route('login') }}" class="auth-link">
                        חזור להתחברות
                    </a>
                </p>
                <p>
                    אין לך חשבון עדיין?
                    <a href="{{ route('register') }}" class="auth-link">
                        הירשם כאן
                    </a>
                </p>
            </div>
        </form>
        </div>
    </div>
</div>
</x-layouts.guest>
