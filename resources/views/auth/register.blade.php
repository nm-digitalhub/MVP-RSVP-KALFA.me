<x-layouts.guest>
    <x-slot:title>הרשמה</x-slot:title>

<div class="auth-screen">
    <div class="auth-shell auth-shell-wide">
        <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-kicker">פתיחת חשבון חדש</div>
            <x-auth-logo />
            <h2 class="auth-title">
                הרשמה לחשבון חדש
            </h2>
            <p class="auth-subtitle">
                או
                <a href="{{ route('login') }}" class="auth-link">
                    התחברות לחשבון קיים
                </a>
            </p>
        </div>
        
        <form class="auth-form" action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="auth-form-section">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-bold text-content">פרטים אישיים</h3>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-content-muted">שלב 1</span>
                </div>

                <div class="auth-form-grid">
                    <div>
                        <label for="first_name" class="auth-label">שם פרטי</label>
                        <input id="first_name" name="first_name" type="text" autocomplete="given-name" required 
                               value="{{ old('first_name') }}"
                               class="auth-input" 
                               placeholder="שם פרטי">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="last_name" class="auth-label">שם משפחה</label>
                        <input id="last_name" name="last_name" type="text" autocomplete="family-name" required 
                               value="{{ old('last_name') }}"
                               class="auth-input" 
                               placeholder="שם משפחה">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="auth-label">כתובת אימייל *</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           value="{{ old('email') }}"
                           class="auth-input" 
                           placeholder="example@domain.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form-grid">
                    <div>
                        <label for="password" class="auth-label">סיסמה</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required 
                               class="auth-input" 
                               placeholder="לפחות 8 תווים">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-content-muted">מומלץ לשלב אותיות, מספרים וסימן מיוחד.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="auth-label">אימות סיסמה</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                               class="auth-input" 
                               placeholder="הזן שוב את הסיסמה">
                    </div>
                </div>
            </div>

            <div class="auth-form-section">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-bold text-content">פרטי חיוב וזיהוי</h3>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-content-muted">שלב 2</span>
                </div>

                <div>
                    <label for="id_number" class="auth-label">מספר תעודת זהות / ח.פ *</label>
                    <input id="id_number" name="id_number" type="text" required 
                           value="{{ old('id_number') }}"
                           pattern="[0-9]{9}|[0-9]{8,9}"
                           class="auth-input" 
                           placeholder="123456789">
                    @error('id_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-content-muted">נדרש לצורכי זיהוי, הגנת הצרכן ומניעת הלבנת הון.</p>
                </div>

                <div>
                    <label for="phone" class="auth-label">מספר טלפון *</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" required
                           value="{{ old('phone') }}"
                           pattern="[0-9\-\+\s]+"
                           class="auth-input" 
                           placeholder="050-1234567">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-content-muted">ישמש לעדכוני שירות, תמיכה ואימות במידת הצורך.</p>
                </div>

                <div class="auth-form-grid">
                    <div>
                        <label for="address" class="auth-label">כתובת מלאה *</label>
                        <input id="address" name="address" type="text" required
                               value="{{ old('address') }}"
                               class="auth-input" 
                               placeholder="רחוב 123, עיר">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="company" class="auth-label">חברה (אופציונלי)</label>
                        <input id="company" name="company" type="text" autocomplete="organization" 
                               value="{{ old('company') }}"
                               class="auth-input" 
                               placeholder="שם החברה">
                        @error('company')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
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

            <div class="auth-consent-panel">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-base font-bold text-content">הסכמות נדרשות עפ"י חוק</h4>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-content-muted">שלב 3</span>
                </div>
                
                <div class="flex items-start">
                    <input id="terms" name="terms" type="checkbox" required
                           class="auth-checkbox">
                    <label for="terms" class="me-2 block text-sm leading-6 text-content">
                        אני מסכים ל<a href="{{ route('terms') }}" class="auth-link underline" target="_blank">תנאי השימוש</a>,
                        <a href="{{ route('privacy') }}" class="auth-link underline" target="_blank">מדיניות הפרטיות</a> ו<a href="{{ route('refund.policy') }}" class="auth-link underline" target="_blank">מדיניות ביטולים והחזרות</a> *
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="age_confirmation" name="age_confirmation" type="checkbox" required
                           class="auth-checkbox">
                    <label for="age_confirmation" class="me-2 block text-sm leading-6 text-content">
                        אני מאשר כי אני מעל גיל 18 ובעל כושר משפטי מלא לחתימה על הסכמים *
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="data_processing" name="data_processing" type="checkbox" required
                           class="auth-checkbox">
                    <label for="data_processing" class="me-2 block text-sm leading-6 text-content">
                        אני מסכים לעיבוד הנתונים האישיים שלי למטרות מתן השירות, תמיכה טכנית וחיוב עפ"י <a href="{{ route('privacy') }}" class="auth-link underline" target="_blank">מדיניות הפרטיות</a> *
                    </label>
                </div>
                
                <div class="flex items-start">
                    <input id="newsletter_subscribed" name="newsletter_subscribed" type="checkbox" value="1"
                           {{ old('newsletter_subscribed') ? 'checked' : '' }}
                           class="auth-checkbox">
                    <label for="newsletter_subscribed" class="me-2 block text-sm leading-6 text-content">
                        אני מעוניין לקבל עדכונים שיווקיים ומבצעים באימייל (אופציונלי)
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="sms_marketing" name="sms_marketing" type="checkbox" value="1"
                           {{ old('sms_marketing') ? 'checked' : '' }}
                           class="auth-checkbox">
                    <label for="sms_marketing" class="me-2 block text-sm leading-6 text-content">
                        אני מסכים לקבל הודעות שיווקיות בסמס (אופציונלי)
                    </label>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="auth-primary-button group relative">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-white/70 transition group-hover:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                        </svg>
                    </span>
                    הירשם עכשיו
                </button>
            </div>

            <div class="auth-inline-links">
                <p>
                    יש לך כבר חשבון?
                    <a href="{{ route('login') }}" class="auth-link">
                        התחבר כאן
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
</x-layouts.guest>
