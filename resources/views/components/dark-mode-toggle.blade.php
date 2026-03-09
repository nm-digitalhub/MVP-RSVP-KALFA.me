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
    :aria-label="isDark ? __('Switch to light mode') : __('Switch to dark mode')"
    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 {{ $class }}"
>
    {{-- Sun icon for dark mode (click to switch to light) --}}
    <svg x-show="isDark" x-transition class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.002-1.602-1.241-1.241M5.636 6.002 1.638 1.241 1.241m-9.36 15.502 1.638 1.241 1.241M12 3a9 9 0 0118 0 9 9 0 01-18 0m0 2.25v2.25M12 18.75a9 9 0 11-18 0m9.36-15.502-1.638-1.241-1.241M5.636 17.997-1.638 1.24-1.24m-9.36-15.502-1.638-1.241-1.241" />
    </svg>

    {{-- Moon icon for light mode (click to switch to dark) --}}
    <svg x-show="!isDark" x-transition class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.386 0-9.75-4.364-9.75-9.75 0-1.33.266-2.597.734-3.707 1.292 4.621 1.937 1.292 2.597 3.707 1.292 4.622 4.621 1.292.504-.583.504-1.345.504-2.07 0-.675-.217-1.313-.528-1.903.528-1.49 0-2.735.396-3.543 1.292-1.385 2.629-1.385 4.622 0 1.635.408 3.095 1.292 4.621.528 1.386.528 2.893-.813 3.543-1.292.958-2.285 1.385-3.026 2.893-3.026 5.258 0 .547.267 1.014.539 1.535.539 2.319 0 .927-.378 1.799-.958 2.355-.876.626-2.055.993-3.053-1.385.876-2.285-1.385-4.622 0-1.936.378-3.59 1.292-4.621zM18.75 21a2.25 2.25 0 100-4.5 0V7.5a2.25 2.25 0 000-4.5v3.75a2.25 2.25 0 004.5 0V21z" />
    </svg>
</button>
