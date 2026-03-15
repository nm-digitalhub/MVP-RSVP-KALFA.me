<x-layouts.guest>
    <x-slot:title>הרשמה</x-slot:title>

<div class="min-h-screen flex items-center justify-center bg-surface py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-brand">
                    NM-DigitalHUB
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                הרשמה לחשבון חדש
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                או
                <a href="{{ route('login') }}" class="font-medium text-brand hover:text-brand">
                    התחברות לחשבון קיים
                </a>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">שם פרטי</label>
                        <input id="first_name" name="first_name" type="text" autocomplete="given-name" required 
                               value="{{ old('first_name') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                               placeholder="שם פרטי">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">שם משפחה</label>
                        <input id="last_name" name="last_name" type="text" autocomplete="family-name" required 
                               value="{{ old('last_name') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                               placeholder="שם משפחה">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">כתובת אימייל *</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           value="{{ old('email') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                           placeholder="example@domain.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="id_number" class="block text-sm font-medium text-gray-700">מספר תעודת זהות / ח.פ *</label>
                    <input id="id_number" name="id_number" type="text" required 
                           value="{{ old('id_number') }}"
                           pattern="[0-9]{9}|[0-9]{8,9}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                           placeholder="123456789">
                    @error('id_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">נדרש עפ"י חוק הגנת הצרכן וחוק מניעת הלבנת הון</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">מספר טלפון *</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" required
                           value="{{ old('phone') }}"
                           pattern="[0-9\-\+\s]+"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                           placeholder="050-1234567">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">נדרש ליצירת קשר בנושאי שירות ותמיכה</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">כתובת מלאה *</label>
                        <input id="address" name="address" type="text" required
                               value="{{ old('address') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                               placeholder="רחוב 123, עיר">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700">חברה (אופציונלי)</label>
                        <input id="company" name="company" type="text" autocomplete="organization" 
                               value="{{ old('company') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                               placeholder="שם החברה">
                        @error('company')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">סיסמה</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                           placeholder="לפחות 8 תווים">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">הסיסמה חייבת להכיל לפחות 8 תווים</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">אימות סיסמה</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm" 
                           placeholder="הזן שוב את הסיסמה">
                </div>
            </div>

            @if($errors->any())
                <div class="rounded-md bg-red-50 p-4">
                    <div class="text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-4 bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900">הסכמות נדרשות עפ"י חוק:</h4>
                
                <div class="flex items-start">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded mt-1">
                    <label for="terms" class="me-2 block text-sm text-gray-900">
                        אני מסכים ל<a href="{{ route('terms') }}" class="text-brand hover:text-brand underline" target="_blank">תנאי השימוש</a>,
                        <a href="{{ route('privacy') }}" class="text-brand hover:text-brand underline" target="_blank">מדיניות הפרטיות</a> ו<a href="{{ route('refund.policy') }}" class="text-brand hover:text-brand underline" target="_blank">מדיניות ביטולים והחזרות</a> *
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="age_confirmation" name="age_confirmation" type="checkbox" required
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded mt-1">
                    <label for="age_confirmation" class="me-2 block text-sm text-gray-900">
                        אני מאשר כי אני מעל גיל 18 ובעל כושר משפטי מלא לחתימה על הסכמים *
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="data_processing" name="data_processing" type="checkbox" required
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded mt-1">
                    <label for="data_processing" class="me-2 block text-sm text-gray-900">
                        אני מסכים לעיבוד הנתונים האישיים שלי למטרות מתן השירות, תמיכה טכנית וחיוב עפ"י <a href="{{ route('privacy') }}" class="text-brand hover:text-brand underline" target="_blank">מדיניות הפרטיות</a> *
                    </label>
                </div>
                
                <div class="flex items-start">
                    <input id="newsletter_subscribed" name="newsletter_subscribed" type="checkbox" value="1"
                           {{ old('newsletter_subscribed') ? 'checked' : '' }}
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded mt-1">
                    <label for="newsletter_subscribed" class="me-2 block text-sm text-gray-900">
                        אני מעוניין לקבל עדכונים שיווקיים ומבצעים באימייל (אופציונלי)
                    </label>
                </div>

                <div class="flex items-start">
                    <input id="sms_marketing" name="sms_marketing" type="checkbox" value="1"
                           {{ old('sms_marketing') ? 'checked' : '' }}
                           class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded mt-1">
                    <label for="sms_marketing" class="me-2 block text-sm text-gray-900">
                        אני מסכים לקבל הודעות שיווקיות בסמס (אופציונלי)
                    </label>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-brand group-hover:text-brand-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                        </svg>
                    </span>
                    הירשם עכשיו
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    יש לך כבר חשבון?
                    <a href="{{ route('login') }}" class="font-medium text-brand hover:text-brand">
                        התחבר כאן
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
</x-layouts.guest>