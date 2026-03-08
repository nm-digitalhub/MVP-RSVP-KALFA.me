<x-layouts.guest>
    <x-slot:title>אישור סיסמה</x-slot:title>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-indigo-600">
                    NM-DigitalHUB
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                אישור סיסמה
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                זוהי אזור מאובטח. אנא אשר את הסיסמה שלך לפני המשך.
            </p>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('password.confirm') }}" method="POST">
            @csrf
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">סיסמה</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                       placeholder="הזן את הסיסמה שלך">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    אשר סיסמה
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    שכחת את הסיסמה?
                </a>
            </div>
        </form>

        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
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
</x-layouts.guest>