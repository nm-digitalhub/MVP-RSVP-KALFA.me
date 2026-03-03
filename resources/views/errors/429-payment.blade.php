@extends('layouts.guest')

@section('title', 'גישה מוגבלת - אבטחת תשלומים')

@section('content')
<section class="bg-white dark:bg-gray-900 min-h-screen flex items-center">
    <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
        <div class="mx-auto max-w-screen-md text-center">
            <!-- Animated Security Icon with Visual Feedback -->
            <div class="flex justify-center mb-8">
                <div class="relative inline-flex items-center justify-center w-28 h-28 bg-red-100 rounded-full dark:bg-red-900 animate-pulse-slow">
                    <!-- Outer Ring Animation -->
                    <div class="absolute inset-0 rounded-full bg-red-200 dark:bg-red-800 animate-ping opacity-20"></div>

                    <!-- Lock Icon with Shake Animation -->
                    <svg class="w-16 h-16 text-red-600 dark:text-red-300 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>

            <!-- Error Code with Gradient -->
            <h1 class="mb-4 text-6xl tracking-tight font-extrabold lg:text-7xl bg-gradient-to-r from-red-600 to-orange-500 bg-clip-text text-transparent dark:from-red-500 dark:to-orange-400 animate-fade-in">
                429
            </h1>

            <!-- Error Title -->
            <p class="mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white animate-slide-up">
                גישה מוגבלת זמנית
            </p>

            <!-- Error Description with Animation -->
            <p class="mb-8 text-lg font-light text-gray-500 dark:text-gray-400 animate-slide-up-delay">
                מערכת האבטחה זיהתה פעילות חשודה והגבילה את הגישה זמנית להגנה על המערכת
            </p>

            <!-- Flowbite Alert - Security Notice -->
            <div class="p-4 mb-8 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Danger</span>
                <div class="inline">
                    <span class="font-medium">{{ $message ?? 'יותר מדי בקשות זוהו ממכשיר זה.' }}</span>
                </div>
            </div>

            <!-- Flowbite Card - Security Info -->
            <div class="p-6 mb-8 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
                <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">למה זה קורה?</h3>

                <!-- Flowbite Accordion for Different Security Types -->
                <div class="space-y-4 text-end">
                    @if(isset($type))
                        @if($type === 'payment_security')
                            <!-- Payment Security Badge -->
                            <div class="flex items-start p-4 bg-orange-50 border border-orange-200 rounded-lg dark:bg-gray-700 dark:border-orange-500">
                                <svg class="flex-shrink-0 w-6 h-6 text-orange-500 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="me-3">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">הגנה על הזמנה ספציפית</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">זוהו יותר מדי ניסיונות גישה להזמנה זו במטרה למנוע שימוש לרעה</p>
                                </div>
                            </div>
                        @elseif($type === 'ip_security')
                            <!-- IP Security Badge -->
                            <div class="flex items-start p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-gray-700 dark:border-red-500">
                                <svg class="flex-shrink-0 w-6 h-6 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="me-3">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">הגנת IP</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">יותר מדי בקשות מכתובת IP זו - מנגנון אבטחה אוטומטי</p>
                                </div>
                            </div>
                        @elseif($type === 'system_protection')
                            <!-- System Protection Badge -->
                            <div class="flex items-start p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-gray-700 dark:border-blue-500">
                                <svg class="flex-shrink-0 w-6 h-6 text-blue-500 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                                </svg>
                                <div class="me-3">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">הגנת מערכת</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">עומס גבוה במערכת - הגבלה זמנית לצורך ייצוב</p>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Security Confirmed Badge -->
                    <div class="flex items-start p-4 bg-green-50 border border-green-200 rounded-lg dark:bg-gray-700 dark:border-green-500">
                        <svg class="flex-shrink-0 w-6 h-6 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="me-3">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">התשלום שלך מוגן</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">כל התשלומים מעובדים בצורה מאובטחת עם הצפנה מלאה</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons with Enhanced Interactivity -->
            <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 rtl:space-x-reverse mb-8">
                <!-- Try Again Button with Loading State -->
                <button onclick="window.location.reload()"
                        class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 ms-2 -me-1 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    נסה שוב
                </button>

                <!-- Dashboard Button -->
                <a href="/client/dashboard"
                   class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 me-2 -ms-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    לוח בקרה
                </a>

                <!-- Homepage Button -->
                <a href="/"
                   class="group inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 ms-2 -me-1 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    דף הבית
                </a>
            </div>

            <!-- Flowbite Support Info Card -->
            <div class="p-6 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">צריכים עזרה?</h3>

                <div class="space-y-3 text-end">
                    <!-- Email Contact -->
                    <div class="flex items-center justify-start">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 ms-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                        <a href="mailto:support@nm-digitalhub.com" class="text-sm text-blue-600 hover:underline dark:text-blue-500">support@nm-digitalhub.com</a>
                    </div>

                    <!-- Wait Time Info -->
                    <div class="flex items-center justify-start">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 ms-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-400">ניתן לנסות שוב בעוד כמה דקות</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Auto Refresh Info with Countdown -->
            <div class="mt-8 animate-slide-up-delay-2">
                <!-- Circular Countdown Indicator -->
                <div class="flex justify-center mb-6">
                    <div class="relative inline-flex items-center justify-center">
                        <!-- SVG Progress Ring -->
                        <svg class="transform -rotate-90 w-32 h-32">
                            <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200 dark:text-gray-700" />
                            <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="4" fill="none" class="text-blue-600 dark:text-blue-400 progress-ring-circle" stroke-linecap="round" />
                        </svg>
                        <!-- Countdown Number -->
                        <div class="absolute flex flex-col items-center justify-center">
                            <span id="countdown-timer" class="text-4xl font-bold text-blue-600 dark:text-blue-400">60</span>
                            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">שניות</span>
                        </div>
                    </div>
                </div>

                <!-- Auto Refresh Alert with Dynamic Colors -->
                <div class="flex p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400 transition-colors duration-300" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="flex-1">
                        <span class="font-medium">רענון אוטומטי:</span>
                        <span id="countdown-text"> הדף ירוענן אוטומטית בעוד <strong id="countdown-timer" class="font-bold text-blue-600 dark:text-blue-400">60</strong> שניות</span>
                    </div>
                </div>

                <!-- Visual Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 overflow-hidden">
                        <div id="countdown-progress" class="bg-gradient-to-r from-blue-600 to-blue-400 h-2 rounded-full transition-all duration-1000 ease-linear" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Countdown Timer & Visual Indicators -->
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

    @keyframes countdownPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes progressRing {
        from {
            stroke-dashoffset: 251.2; /* 2 * PI * 40 */
        }
        to {
            stroke-dashoffset: 0;
        }
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

    .animate-countdown-pulse {
        animation: countdownPulse 1s ease-in-out infinite;
    }

    .progress-ring-circle {
        stroke-dasharray: 251.2;
        stroke-dashoffset: 251.2;
        animation: progressRing 60s linear forwards;
    }

    /* Focus visible for accessibility */
    *:focus-visible {
        outline: 2px solid #f59e0b;
        outline-offset: 2px;
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
</style>

<!-- Interactive Countdown & Auto-refresh -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let countdown = 60; // 60 seconds
    const countdownElement = document.getElementById('countdown-timer');
    const countdownText = document.getElementById('countdown-text');
    const progressBar = document.getElementById('countdown-progress');
    const autoRefreshInfo = document.querySelector('[role="alert"]');

    // Update countdown every second
    const countdownInterval = setInterval(function() {
        countdown--;

        if (countdownElement) {
            countdownElement.textContent = countdown;
        }

        if (countdownText) {
            if (countdown <= 10) {
                countdownText.classList.add('text-red-600', 'dark:text-red-400', 'font-bold');
                countdownElement.classList.add('animate-countdown-pulse');
            }
            countdownText.innerHTML = `הדף ירוענן אוטומטית בעוד <strong id="countdown-timer" class="font-bold text-blue-600 dark:text-blue-400">${countdown}</strong> שניות`;
        }

        // Update progress bar
        if (progressBar) {
            const progressPercentage = ((60 - countdown) / 60) * 100;
            progressBar.style.width = progressPercentage + '%';
        }

        // Visual feedback when countdown is low
        if (countdown === 10 && autoRefreshInfo) {
            autoRefreshInfo.classList.remove('text-blue-800', 'bg-blue-50', 'dark:text-blue-400');
            autoRefreshInfo.classList.add('text-orange-800', 'bg-orange-50', 'dark:text-orange-400', 'animate-pulse-slow');
        }

        if (countdown === 5 && autoRefreshInfo) {
            autoRefreshInfo.classList.remove('text-orange-800', 'bg-orange-50', 'dark:text-orange-400');
            autoRefreshInfo.classList.add('text-red-800', 'bg-red-50', 'dark:text-red-400');
        }

        // Auto refresh at 0
        if (countdown <= 0) {
            clearInterval(countdownInterval);
            console.log('Auto-refreshing after security rate limit delay...');

            // Show loading state
            if (countdownText) {
                countdownText.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin h-5 w-5 ms-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> מרענן...</span>';
            }

            setTimeout(function() {
                window.location.reload();
            }, 500);
        }
    }, 1000);

    // Manual refresh button enhancement
    const refreshButtons = document.querySelectorAll('button[onclick*="reload"]');
    refreshButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            clearInterval(countdownInterval);

            // Show loading state
            this.disabled = true;
            this.innerHTML = `
                <svg class="w-5 h-5 ms-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                מרענן...
            `;

            setTimeout(() => window.location.reload(), 500);
        });
    });

    console.log('429 Rate Limit Page - Enhanced UI/UX with countdown timer loaded');
});
</script>
@endsection
