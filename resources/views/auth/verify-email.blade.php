<x-layouts.guest>
    <x-slot:title>אימות אימייל</x-slot:title>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-indigo-600">
                    NM-DigitalHUB
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                אימות כתובת אימייל
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                שלחנו קישור אימות לכתובת האימייל שלך. לחץ על הקישור כדי לאמת את החשבון.
            </p>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    בדוק את תיבת האימייל שלך
                </h3>
                
                <p class="mt-2 text-sm text-gray-600">
                    שלחנו הודעת אימייל לכתובת <strong>{{ Auth::user()->email }}</strong>
                </p>
                
                <p class="mt-2 text-xs text-gray-500">
                    לא קיבלת את האימייל? בדוק בתיקיית הספאם או נסה לשלוח שוב.
                </p>
            </div>
        </div>

        @if(session('status') == 'verification-link-sent')
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="me-3">
                        <p class="text-sm text-green-800">
                            נשלח קישור אימות חדש לכתובת האימייל שלך!
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <!-- Resend Verification Email -->
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    שלח קישור אימות שוב
                </button>
            </form>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    התנתק ונסה שוב
                </button>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="me-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        למה צריך לאמת את האימייל?
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>קבלת הודעות חשובות על השירותים שלך</li>
                            <li>איפוס סיסמה במקרה הצורך</li>
                            <li>אישורי הזמנות ותשלומים</li>
                            <li>הגנה על אבטחת החשבון</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layouts.guest>