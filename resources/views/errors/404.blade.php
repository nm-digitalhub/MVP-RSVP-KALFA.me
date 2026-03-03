@extends('layouts.guest')

@section('title', 'דף לא נמצא - 404')

@section('content')
    <section class="bg-white dark:bg-gray-900 min-h-screen flex items-center">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="mx-auto max-w-screen-sm text-center">
                <!-- Animated Error Icon with Pulse Effect -->
                <div class="flex justify-center mb-8">
                    <div class="relative inline-flex items-center justify-center w-28 h-28 bg-primary-100 rounded-full dark:bg-primary-900 animate-pulse-slow">
                        <!-- Outer Ring Animation -->
                        <div class="absolute inset-0 rounded-full bg-primary-200 dark:bg-primary-800 animate-ping opacity-20"></div>

                        <svg class="w-16 h-16 text-primary-600 dark:text-primary-300 relative z-10 transform hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Error Code with Gradient -->
                <h1 class="mb-4 text-7xl tracking-tight font-extrabold lg:text-9xl bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent dark:from-primary-500 dark:to-primary-300 animate-fade-in">
                    404
                </h1>

                <!-- Error Title -->
                <p class="mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white animate-slide-up">
                    הדף לא נמצא
                </p>

                <!-- Error Description -->
                <p class="mb-8 text-lg font-light text-gray-500 dark:text-gray-400 animate-slide-up-delay">
                    מצטערים, לא הצלחנו למצוא את הדף שחיפשת. אפשר שהדף הועבר, נמחק, או שהכתובת שגויה.
                </p>

                <!-- Primary Action Buttons with Hover Effects -->
                <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 rtl:space-x-reverse mb-12 animate-slide-up-delay-2">
                    <!-- Primary CTA Button -->
                    <a href="{{ url('/') }}" class="group inline-flex justify-center items-center py-3 px-6 text-base font-medium text-center text-white rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-5 h-5 ms-2 -me-1 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        חזרה לדף הבית
                    </a>

                    <!-- Secondary Button -->
                    <a href="{{ url('/services') }}" class="group inline-flex justify-center items-center py-3 px-6 text-base font-medium text-center text-gray-900 rounded-lg border-2 border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800 transform hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-5 h-5 me-2 -ms-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        עיון בשירותים
                    </a>
                </div>

                <!-- Enhanced Search Bar with Live Feedback -->
                <div class="mb-12 animate-slide-up-delay-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">מה אתה מחפש?</h3>
                    <form action="{{ url('/search') }}" method="GET" class="max-w-md mx-auto">
                        <label for="search-404" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">חיפוש</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-focus-within:text-primary-600 dark:group-focus-within:text-primary-400 transition-colors" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="search-404" name="q"
                                   class="block w-full p-4 ps-11 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 transition-all duration-200"
                                   placeholder="חפש שירותים, מוצרים, דומיינים..."
                                   autocomplete="off" />
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 transform hover:scale-105 transition-transform">
                                חיפוש
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Links Grid with Hover Cards -->
                <div class="mb-12 animate-slide-up-delay-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">קישורים מהירים</h3>
                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- eSIM Card -->
                        <a href="{{ url('/esim-packages') }}" class="group block p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-2xl dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transform hover:-translate-y-1 transition-all duration-300">
                            <div class="flex justify-center mb-4">
                                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900 group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">חבילות eSIM</h5>
                            <p class="font-normal text-gray-600 dark:text-gray-400 text-sm">גלישה בינלאומית במחירים משתלמים</p>
                            <span class="inline-flex items-center mt-3 text-primary-600 dark:text-primary-400 text-sm font-medium group-hover:underline">
                                לפרטים נוספים
                                <svg class="w-4 h-4 me-2 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </a>

                        <!-- Domains Card -->
                        <a href="{{ url('/domains') }}" class="group block p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-2xl dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transform hover:-translate-y-1 transition-all duration-300">
                            <div class="flex justify-center mb-4">
                                <div class="p-3 bg-blue-100 rounded-lg dark:bg-blue-900 group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                </div>
                            </div>
                            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">דומיינים</h5>
                            <p class="font-normal text-gray-600 dark:text-gray-400 text-sm">רכוש דומיין לעסק שלך</p>
                            <span class="inline-flex items-center mt-3 text-blue-600 dark:text-blue-400 text-sm font-medium group-hover:underline">
                                בדוק זמינות
                                <svg class="w-4 h-4 me-2 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </a>

                        <!-- Hosting Card -->
                        <a href="{{ url('/hosting') }}" class="group block p-6 bg-white border border-gray-200 rounded-xl shadow hover:shadow-2xl dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500 transform hover:-translate-y-1 transition-all duration-300">
                            <div class="flex justify-center mb-4">
                                <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900 group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                    </svg>
                                </div>
                            </div>
                            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">אחסון אתרים</h5>
                            <p class="font-normal text-gray-600 dark:text-gray-400 text-sm">שרתים מהירים ואמינים</p>
                            <span class="inline-flex items-center mt-3 text-green-600 dark:text-green-400 text-sm font-medium group-hover:underline">
                                צפה בחבילות
                                <svg class="w-4 h-4 me-2 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Help Section with Enhanced Alert -->
                <div class="animate-slide-up-delay-5">
                    <div class="p-4 text-sm text-blue-800 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 dark:text-blue-400 border border-blue-200 dark:border-blue-900" role="alert">
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 inline w-5 h-5 ms-3 animate-bounce" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <div class="inline">
                                <span class="font-semibold">זקוק לעזרה נוספת?</span>
                                <a href="{{ url('/contact') }}" class="font-bold underline hover:no-underline hover:text-blue-900 dark:hover:text-blue-300 transition-colors">צור קשר עם צוות התמיכה שלנו</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Animations & Styles -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulseSlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        .animate-slide-up {
            animation: slideUp 0.6s ease-out 0.2s both;
        }

        .animate-slide-up-delay {
            animation: slideUp 0.6s ease-out 0.4s both;
        }

        .animate-slide-up-delay-2 {
            animation: slideUp 0.6s ease-out 0.6s both;
        }

        .animate-slide-up-delay-3 {
            animation: slideUp 0.6s ease-out 0.8s both;
        }

        .animate-slide-up-delay-4 {
            animation: slideUp 0.6s ease-out 1s both;
        }

        .animate-slide-up-delay-5 {
            animation: slideUp 0.6s ease-out 1.2s both;
        }

        .animate-pulse-slow {
            animation: pulseSlow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
    </style>
@endsection
