@extends('layouts.guest')

@section('title', 'שגיאת שרת - 500')

@section('content')
    <section class="bg-white dark:bg-gray-900 min-h-screen flex items-center">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="mx-auto max-w-screen-md text-center">
                <!-- Animated Server Error Icon with Visual Feedback -->
                <div class="flex justify-center mb-8">
                    <div class="relative inline-flex items-center justify-center w-28 h-28 bg-red-100 rounded-full dark:bg-red-900 animate-pulse-slow">
                        <!-- Outer Ring Animation -->
                        <div class="absolute inset-0 rounded-full bg-red-200 dark:bg-red-800 animate-ping opacity-20"></div>

                        <!-- Server Icon with Shake Animation -->
                        <svg class="w-16 h-16 text-red-600 dark:text-red-300 relative z-10 animate-shake" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                </div>

                <!-- Error Code with Gradient -->
                <h1 class="mb-4 text-7xl tracking-tight font-extrabold lg:text-9xl bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent dark:from-red-500 dark:to-red-300 animate-fade-in">
                    500
                </h1>

                <!-- Error Title -->
                <p class="mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white animate-slide-up">
                    שגיאת שרת פנימית
                </p>

                <!-- Error Description with Animation -->
                <p class="mb-8 text-lg font-light text-gray-500 dark:text-gray-400 animate-slide-up-delay">
                    מצטערים, אירעה שגיאה בשרת שלנו. הצוות הטכני קיבל התראה ועובד על פתרון הבעיה.
                </p>

                <!-- System Recovery Progress Card -->
                <div class="p-6 mb-8 bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700 animate-slide-up-delay-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">סטטוס מערכת</h3>
                        <span class="flex items-center text-sm font-medium text-yellow-600 dark:text-yellow-400">
                            <span class="flex w-2.5 h-2.5 bg-yellow-500 rounded-full ms-1.5 animate-pulse"></span>
                            בתהליך שיקום
                        </span>
                    </div>

                    <!-- Progress Steps with Visual Indicators -->
                    <div class="space-y-4 text-end">
                        <!-- Step 1: Error Detected -->
                        <div class="flex items-start p-4 bg-green-50 border border-green-200 rounded-lg dark:bg-gray-700 dark:border-green-500">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">שגיאה זוהתה</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">המערכת זיהתה את הבעיה והתראה נשלחה לצוות הטכני</p>
                            </div>
                        </div>

                        <!-- Step 2: Team Notified -->
                        <div class="flex items-start p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-gray-700 dark:border-blue-500">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-500 dark:text-blue-400 animate-spin-slow" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">צוות טכני עובד על הבעיה</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">המומחים שלנו מנתחים את השגיאה ומיישמים פתרון</p>
                            </div>
                        </div>

                        <!-- Step 3: Recovery -->
                        <div class="flex items-start p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600 opacity-60">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="me-3 flex-1">
                                <h4 class="text-base font-semibold text-gray-500 dark:text-gray-400">המערכת תשוקם בקרוב</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-500">השירות יחזור לפעילות מלאה תוך זמן קצר</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">התקדמות שיקום</span>
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400" id="progress-percentage">65%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-600 to-blue-400 h-2.5 rounded-full animate-progress-fill" style="width: 65%"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                            זמן משוער לשיקום: 2-5 דקות
                        </p>
                    </div>
                </div>

                <!-- Action Buttons with Enhanced Interactivity -->
                <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 rtl:space-x-reverse mb-8 animate-slide-up-delay-3">
                    <!-- Refresh Button with Loading State -->
                    <button onclick="handleRefresh(this)" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 ms-2 -me-1 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        נסה שוב
                    </button>

                    <!-- Homepage Button -->
                    <a href="{{ url('/') }}" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 ms-2 -me-1 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        דף הבית
                    </a>

                    <!-- Contact Button -->
                    <a href="{{ url('/contact') }}" class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 me-2 -ms-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        צור קשר
                    </a>
                </div>

                <!-- Help Card -->
                <div class="p-6 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 animate-slide-up-delay-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">צריכים עזרה נוספת?</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            <a href="mailto:support@nm-digitalhub.com" class="text-sm text-blue-600 hover:underline dark:text-blue-500">support@nm-digitalhub.com</a>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            נשמח לעדכן אותך כשהמערכת תחזור לפעולה מלאה
                        </p>
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }

        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes progressFill {
            from { width: 0%; }
            to { width: 65%; }
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

        .animate-pulse-slow {
            animation: pulseSlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .animate-shake {
            animation: shake 2s ease-in-out infinite;
        }

        .animate-spin-slow {
            animation: spinSlow 3s linear infinite;
        }

        .animate-progress-fill {
            animation: progressFill 2s ease-out forwards;
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>

    <!-- Interactive JavaScript -->
    <script>
        function handleRefresh(button) {
            // Show loading state
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `
                <svg class="w-5 h-5 ms-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                טוען...
            `;

            // Simulate progress update
            setTimeout(() => {
                location.reload();
            }, 800);
        }

        // Simulate progress updates
        document.addEventListener('DOMContentLoaded', function() {
            let progress = 65;
            const progressBar = document.querySelector('.animate-progress-fill');
            const progressText = document.getElementById('progress-percentage');

            setInterval(() => {
                progress = Math.min(progress + Math.random() * 5, 95);
                if (progressBar && progressText) {
                    progressBar.style.width = progress + '%';
                    progressText.textContent = Math.round(progress) + '%';
                }
            }, 3000);

            console.log('500 Error Page - Enhanced UI/UX with progress indicators loaded');
        });
    </script>
@endsection
