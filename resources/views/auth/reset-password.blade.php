<x-layouts.guest>
    <x-slot:title>איפוס סיסמה</x-slot:title>

<div class="auth-screen">
    <div class="auth-shell">
        <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-kicker">אבטחת חשבון</div>
            <x-auth-logo />
            <h2 class="auth-title">
                איפוס סיסמה
            </h2>
            <p class="auth-subtitle">
                הזן סיסמה חדשה לחשבון שלך
            </p>
        </div>
        
        <form class="auth-form" action="{{ route('password.store') }}" method="POST">
            @csrf
            
            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <div class="space-y-4">
                <div>
                    <label for="email" class="auth-label">כתובת אימייל</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           value="{{ old('email', request()->input('email')) }}"
                           class="auth-input" 
                           placeholder="האימייל שלך">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="auth-label">סיסמה חדשה</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required 
                           class="auth-input" 
                           placeholder="לפחות 8 תווים">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-content-muted">בחר סיסמה חזקה שלא השתמשת בה בעבר.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="auth-label">אימות סיסמה</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                           class="auth-input" 
                           placeholder="הזן שוב את הסיסמה החדשה">
                </div>
            </div>

            @if($errors->any())
                <div class="auth-status auth-status-danger">
                    <div class="text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <button type="submit" 
                        class="auth-primary-button group relative">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-white/70 transition group-hover:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    איפוס סיסמה
                </button>
            </div>

            <div class="auth-inline-links">
                <p>
                    <a href="{{ route('login') }}" class="auth-link">
                        חזור להתחברות
                    </a>
                </p>
            </div>
        </form>
        </div>
    </div>
</div>
</x-layouts.guest>
