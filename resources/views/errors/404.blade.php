<x-layouts.guest>
    <x-slot:title>דף לא נמצא - 404</x-slot:title>

    <section class="bg-white dark:bg-gray-900 min-h-screen flex items-center" aria-labelledby="error-404-heading">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="mx-auto max-w-screen-sm text-center">
                <!-- Error Icon (reduced-motion friendly) -->
                <div class="flex justify-center mb-8">
                    <div class="error-404-icon relative inline-flex items-center justify-center w-28 h-28 bg-primary-100 rounded-full dark:bg-primary-900">
                        <svg class="w-16 h-16 text-primary-600 dark:text-primary-300 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <h1 id="error-404-heading" class="mb-4 text-7xl tracking-tight font-extrabold lg:text-9xl bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent dark:from-primary-500 dark:to-primary-300 error-404-code">
                    404
                </h1>

                <p class="mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white error-404-title">
                    הדף לא נמצא
                </p>

                <p class="mb-8 text-lg font-light text-gray-600 dark:text-gray-400 error-404-desc">
                    מצטערים, לא הצלחנו למצוא את הדף שחיפשת. אפשר שהדף הועבר, נמחק, או שהכתובת שגויה.
                </p>

                <!-- Primary actions: min 44px touch targets, cursor-pointer, focus-visible -->
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-center sm:gap-4 rtl:space-x-reverse mb-12 error-404-actions">
                    <a href="{{ route('home') }}" wire:navigate class="group inline-flex justify-center items-center min-h-[44px] min-w-[44px] py-3 px-6 text-base font-medium text-center text-white rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:ring-4 focus:ring-primary-300 focus-visible:ring-4 focus-visible:ring-primary-400 dark:focus:ring-primary-900 shadow-lg hover:shadow-xl transition-colors duration-200 transition-shadow duration-200 cursor-pointer" aria-label="{{ __('Back to home') }}">
                        <svg class="w-5 h-5 ms-2 rtl:ms-0 rtl:me-2 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        חזרה לדף הבית
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="group inline-flex justify-center items-center min-h-[44px] min-w-[44px] py-3 px-6 text-base font-medium text-center text-gray-900 rounded-lg border-2 border-gray-300 hover:bg-gray-100 hover:border-gray-400 focus:ring-4 focus:ring-gray-200 focus-visible:ring-4 focus-visible:ring-gray-300 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-600 transition-colors duration-200 cursor-pointer" aria-label="{{ __('Dashboard') }}">
                            <svg class="w-5 h-5 me-2 rtl:me-0 rtl:ms-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="group inline-flex justify-center items-center min-h-[44px] min-w-[44px] py-3 px-6 text-base font-medium text-center text-gray-900 rounded-lg border-2 border-gray-300 hover:bg-gray-100 hover:border-gray-400 focus:ring-4 focus:ring-gray-200 focus-visible:ring-4 focus-visible:ring-gray-300 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-600 transition-colors duration-200 cursor-pointer" aria-label="{{ __('Log in') }}">
                            <svg class="w-5 h-5 me-2 rtl:me-0 rtl:ms-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            {{ __('Log in') }}
                        </a>
                    @endauth
                </div>

                <!-- Quick links: Kalfa app (Dashboard, Events, Organizations) -->
                <div class="mb-12 error-404-links">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">קישורים שימושיים</h2>
                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                        <a href="{{ route('home') }}" wire:navigate class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transition-shadow duration-200 cursor-pointer focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none min-h-[120px] justify-center" aria-label="{{ __('Home') }}">
                            <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 mb-3">
                                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200">דף הבית</span>
                        </a>
                        @auth
                            <a href="{{ route('dashboard.events.index') }}" wire:navigate class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transition-shadow duration-200 cursor-pointer focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none min-h-[120px] justify-center" aria-label="{{ __('Events') }}">
                                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 mb-3">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200">{{ __('Events') }}</span>
                            </a>
                            <a href="{{ route('organizations.index') }}" wire:navigate class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transition-shadow duration-200 cursor-pointer focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none min-h-[120px] justify-center" aria-label="{{ __('Organizations') }}">
                                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 mb-3">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200">{{ __('Organizations') }}</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transition-shadow duration-200 cursor-pointer focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none min-h-[120px] justify-center" aria-label="{{ __('Log in') }}">
                                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 mb-3">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                </div>
                                <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200">{{ __('Log in') }}</span>
                            </a>
                            <a href="{{ route('register') }}" wire:navigate class="group flex flex-col items-center p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transition-shadow duration-200 cursor-pointer focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:outline-none min-h-[120px] justify-center" aria-label="{{ __('Register') }}">
                                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 mb-3">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                </div>
                                <span class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200">{{ __('Register') }}</span>
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Help text (no broken /contact link) -->
                <div class="error-404-help">
                    <p class="p-4 text-sm text-gray-700 dark:text-gray-300 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700" role="note">
                        <span class="font-semibold">זקוק לעזרה?</span>
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-primary-600 dark:text-primary-400 underline hover:no-underline focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded cursor-pointer">{{ __('Go to dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary-600 dark:text-primary-400 underline hover:no-underline focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded cursor-pointer">{{ __('Log in') }}</a>
                        @endauth
                    </p>
                </div>
            </div>
        </div>
    </section>

    <style>
        /* Respect prefers-reduced-motion (UI/UX Pro Max: reduced-motion) */
        @media (prefers-reduced-motion: no-preference) {
            .error-404-icon {
                animation: error-404-pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            .error-404-code { animation: error-404-fadeIn 0.8s ease-out both; }
            .error-404-title { animation: error-404-slideUp 0.6s ease-out 0.2s both; }
            .error-404-desc { animation: error-404-slideUp 0.6s ease-out 0.4s both; }
            .error-404-actions { animation: error-404-slideUp 0.6s ease-out 0.6s both; }
            .error-404-links { animation: error-404-slideUp 0.6s ease-out 0.8s both; }
            .error-404-help { animation: error-404-slideUp 0.6s ease-out 1s both; }
        }

        @keyframes error-404-fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes error-404-slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes error-404-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }

        *:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
    </style>
</x-layouts.guest>
