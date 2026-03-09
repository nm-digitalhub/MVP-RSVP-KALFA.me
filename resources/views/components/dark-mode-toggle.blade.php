@props(['class' => ''])

<button
    x-data="{
        isDark: localStorage.getItem('theme') === 'dark' ||
                 (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggle() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.isDark);
        }
    }"
    x-init="
        // Apply theme on load
        document.documentElement.classList.toggle('dark', isDark);
    "
    x-on:click="toggle()"
    :aria-label="isDark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 {{ $class }}"
>
    {{-- Sun icon for dark mode (click to switch to light) --}}
    <x-heroicon-o-sun x-show="isDark" x-cloak class="w-5 h-5" aria-hidden="true" />

    {{-- Moon icon for light mode (click to switch to dark) --}}
    <x-heroicon-o-moon x-show="!isDark" x-cloak class="w-5 h-5" aria-hidden="true" />
</button>
