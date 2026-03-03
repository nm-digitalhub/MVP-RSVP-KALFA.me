@extends('layouts.guest')

@section('title', 'גישה נדחתה - 403')

@section('content')
    <section class="bg-white dark:bg-gray-900 min-h-screen flex items-center">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="mx-auto max-w-screen-md text-center">
                <!-- Animated Forbidden Icon with Visual Feedback -->
                <div class="flex justify-center mb-8">
                    <div class="relative inline-flex items-center justify-center w-28 h-28 bg-amber-100 rounded-full dark:bg-amber-900 animate-pulse-slow">
                        <!-- Outer Ring Animation -->
                        <div class="absolute inset-0 rounded-full bg-amber-200 dark:bg-amber-800 animate-ping opacity-20"></div>

                        <!-- Shield Icon with Bounce Animation -->
                        <svg class="w-16 h-16 text-amber-600 dark:text-amber-300 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h-1V6a5 5 0 10-10 0v2H6"></path>
                        </svg>
                    </div>
                </div>

                <!-- Error Code with Gradient -->
                <h1 class="mb-4 text-7xl tracking-tight font-extrabold lg:text-9xl bg-gradient-to-r from-amber-600 to-orange-500 bg-clip-text text-transparent dark:from-amber-500 dark:to-orange-400 animate-fade-in">
                    403
                </h1>

                <!-- Error Title -->
                <p class="mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white animate-slide-up">
                    גישה נדחתה
                </p>

                <!-- Error Description with Animation -->
                <p class="mb-8 text-lg font-light text-gray-500 dark:text-gray-400 animate-slide-up-delay">
                    אין לך הרשאות מספיקות לגשת למשאב זה. אם אתה מאמין שזו טעות, צור קשר עם התמיכה.
                </p>

                <!-- Security Alert -->
                <div class="p-4 mb-8 text-sm text-amber-800 rounded-lg bg-amber-50 dark:bg-gray-800 dark:text-amber-400 animate-slide-up-delay-2" role="alert">
                    <div class="flex items-center justify-center">
                        <svg class="flex-shrink-0 w-5 h-5 ms-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="sr-only">Warning</span>
                        <div>
                            <span class="font-medium">אזור מוגבל:</span> נדרשות הרשאות מיוחדות לצפייה בתוכן זה
                        </div>
                    </div>
                </div>

                <!-- Access Requirements Card -->
                <div class="p-6 mb-8 bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">למה זה קורה?</h3>

                    <!-- Reasons List -->
                    <div class="space-y-4 text-end">
                        <!-- Reason 1: Not Logged In -->
                        <div class="flex items-start p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-gray-700 dark:border-blue-500">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">לא מחובר למערכת</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">יתכן שאתה צריך להתחבר כדי לצפות בתוכן זה</p>
                            </div>
                        </div>

                        <!-- Reason 2: Insufficient Permissions -->
                        <div class="flex items-start p-4 bg-amber-50 border border-amber-200 rounded-lg dark:bg-gray-700 dark:border-amber-500">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">אין הרשאות מספיקות</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">התוכן זמין רק למשתמשים עם הרשאות מיוחדות</p>
                            </div>
                        </div>

                        <!-- Reason 3: Restricted Area -->
                        <div class="flex items-start p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-gray-700 dark:border-red-500">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">אזור מוגבל</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">המשאב מוגבל למשתמשים מורשים בלבד</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons with Enhanced Interactivity -->
                <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 rtl:space-x-reverse mb-8">
                    <!-- Login Button -->
                    @guest
                    <a href="{{ route('login') }}" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 ms-2 -me-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        התחבר למערכת
                    </a>
                    @endguest

                    <!-- Back Button -->
                    <button onclick="history.back()" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 me-2 -ms-1 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        חזור לדף הקודם
                    </button>

                    <!-- Homepage Button -->
                    <a href="{{ url('/') }}" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 ms-2 -me-1 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        דף הבית
                    </a>
                </div>

                <!-- Help Card -->
                <div class="p-6 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">זקוק לעזרה?</h3>
                    <div class="space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            אם אתה מאמין שאמור להיות לך גישה למשאב זה, צור קשר עם צוות התמיכה שלנו
                        </p>
                        <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            <a href="mailto:support@nm-digitalhub.com" class="text-sm text-blue-600 hover:underline dark:text-blue-500">support@nm-digitalhub.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Animations & Interactions -->
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

        .animate-pulse-slow {
            animation: pulseSlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #d97706;
            outline-offset: 2px;
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>

    <!-- Interactive JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add subtle animation to alert
            const alertElement = document.querySelector('[role="alert"]');
            if (alertElement) {
                setTimeout(() => {
                    alertElement.classList.add('animate-pulse-slow');
                }, 1000);
            }

            console.log('403 Forbidden Page - Enhanced UI/UX loaded');
        });
    </script>
@endsection
