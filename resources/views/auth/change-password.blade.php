<x-layouts.app>
    <x-slot:title>שינוי סיסמה</x-slot:title>

<div class="min-h-screen flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8" dir="rtl">
    <div class="max-w-md w-full space-y-8">
        <div>
            @if($forceChange)
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="me-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                שינוי סיסמה נדרש
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>יש לך סיסמה זמנית שפגה תוקפה. יש לשנות את הסיסמה כדי להמשיך להשתמש במערכת.</p>
                                @if($expiresAt)
                                    <p class="mt-1">תוקף הסיסמה הזמנית: {{ $expiresAt->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="text-center">
                <img class="mx-auto h-12 w-auto" src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    שינוי סיסמה
                </h2>
                @if(!$forceChange)
                    <p class="mt-2 text-sm text-gray-600">
                        הזן את הסיסמה הנוכחית והסיסמה החדשה
                    </p>
                @endif
            </div>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('password.update') }}" method="POST" id="password-change-form">
            @csrf
            
            <div class="space-y-4">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                        סיסמה נוכחית
                    </label>
                    <div class="mt-1 relative">
                        <input id="current_password" 
                               name="current_password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="הזן סיסמה נוכחית">
                        <button type="button" class="absolute inset-y-0 left-0 pe-3 flex items-center" onclick="togglePassword('current_password')">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">
                        סיסמה חדשה
                    </label>
                    <div class="mt-1 relative">
                        <input id="new_password" 
                               name="new_password" 
                               type="password" 
                               autocomplete="new-password" 
                               required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="הזן סיסמה חדשה">
                        <button type="button" class="absolute inset-y-0 left-0 pe-3 flex items-center" onclick="togglePassword('new_password')">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('new_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    
                    <!-- Password Strength Indicator -->
                    <div id="password-strength" class="mt-2 hidden">
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div id="strength-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                            <span id="strength-text" class="text-xs font-medium"></span>
                        </div>
                        <div id="strength-feedback" class="mt-1 text-xs text-gray-600"></div>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">
                        אימות סיסמה חדשה
                    </label>
                    <div class="mt-1 relative">
                        <input id="new_password_confirmation" 
                               name="new_password_confirmation" 
                               type="password" 
                               autocomplete="new-password" 
                               required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="הזן שוב את הסיסמה החדשה">
                        <button type="button" class="absolute inset-y-0 left-0 pe-3 flex items-center" onclick="togglePassword('new_password_confirmation')">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('new_password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Password Requirements -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">דרישות סיסמה:</h4>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• לפחות {{ config('security.passwords.strength.min_length', 8) }} תווים</li>
                    @if(config('security.passwords.strength.require_uppercase', true))
                        <li>• אותיות גדולות באנגלית (A-Z)</li>
                    @endif
                    @if(config('security.passwords.strength.require_lowercase', true))
                        <li>• אותיות קטנות באנגלית (a-z)</li>
                    @endif
                    @if(config('security.passwords.strength.require_numbers', true))
                        <li>• מספרים (0-9)</li>
                    @endif
                    @if(config('security.passwords.strength.require_symbols', true))
                        <li>• סימנים מיוחדים (!@#$%^&*)</li>
                    @endif
                    <li>• לא סיסמה נפוצה או פשוטה</li>
                </ul>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        id="submit-button">
                    <span class="absolute right-0 inset-y-0 flex items-center pe-3">
                        <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                    </span>
                    שינוי סיסמה
                </button>
            </div>

            @if(!$forceChange)
                <div class="text-center">
                    <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-500">
                        חזור לדשבורד
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
    field.setAttribute('type', type);
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    if (password.length > 0) {
        checkPasswordStrength(password);
        document.getElementById('password-strength').classList.remove('hidden');
    } else {
        document.getElementById('password-strength').classList.add('hidden');
    }
});

function checkPasswordStrength(password) {
    // Simple client-side strength checking
    let score = 0;
    let feedback = [];

    // Length
    if (password.length >= 8) score += 20;
    else feedback.push('לפחות 8 תווים');

    // Uppercase
    if (/[A-Z]/.test(password)) score += 15;
    else feedback.push('אותיות גדולות');

    // Lowercase  
    if (/[a-z]/.test(password)) score += 15;
    else feedback.push('אותיות קטנות');

    // Numbers
    if (/[0-9]/.test(password)) score += 15;
    else feedback.push('מספרים');

    // Symbols
    if (/[^A-Za-z0-9]/.test(password)) score += 20;
    else feedback.push('סימנים מיוחדים');

    // Extra length bonus
    if (password.length > 12) score += 10;

    // Update UI
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const strengthFeedback = document.getElementById('strength-feedback');

    strengthBar.style.width = score + '%';
    
    if (score < 40) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-red-500';
        strengthText.textContent = 'חלשה';
        strengthText.className = 'text-xs font-medium text-red-600';
    } else if (score < 70) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-yellow-500';
        strengthText.textContent = 'בינונית';
        strengthText.className = 'text-xs font-medium text-yellow-600';
    } else {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-green-500';
        strengthText.textContent = 'חזקה';
        strengthText.className = 'text-xs font-medium text-green-600';
    }

    if (feedback.length > 0) {
        strengthFeedback.textContent = 'נדרש: ' + feedback.join(', ');
    } else {
        strengthFeedback.textContent = 'סיסמה עומדת בכל הדרישות!';
    }
}

// Form validation
document.getElementById('password-change-form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('אימות הסיסמה אינו תואם');
        return false;
    }
    
    // Disable submit button to prevent double submission
    const submitButton = document.getElementById('submit-button');
    submitButton.disabled = true;
    submitButton.textContent = 'משנה סיסמה...';
});
</script>
</x-layouts.app>