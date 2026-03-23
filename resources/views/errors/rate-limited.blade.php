<x-app-layout>
    <div class="flex items-center justify-center min-h-screen px-4 bg-gray-50 dark:bg-gray-900">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-6">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ __('Too Many Requests') }}
                </h1>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('You\'ve made too many requests. Please wait a moment and try again.') }}
                </p>

                @if(isset($retry_after) && $retry_after > 0)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <span class="font-semibold">{{ __('Please wait:') }}</span>
                            <span class="mx-1">•</span>
                            <span class="font-mono">{{ __(':seconds seconds before trying again', ['seconds' => $retry_after]) }}</span>
                        </p>
                    </div>
                @endif

                <div class="space-y-3">
                    <a href="{{ request()->header('referer') ?: '/' }}" class="block w-full py-3 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors">
                        {{ __('Go Back') }}
                    </a>
                    <a href="/" class="block w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors">
                        {{ __('Home') }}
                    </a>
                </div>

                <p class="mt-6 text-xs text-gray-500 dark:text-gray-500">
                    {{ __('Error code: 429 - Rate Limit Exceeded') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
