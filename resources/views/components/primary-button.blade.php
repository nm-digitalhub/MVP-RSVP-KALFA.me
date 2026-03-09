<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600 active:bg-indigo-800 dark:active:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 transition-colors duration-200']) }}>
    {{ $slot }}
</button>
