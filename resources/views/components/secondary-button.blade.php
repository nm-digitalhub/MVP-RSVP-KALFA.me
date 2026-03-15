<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-card border border-stroke rounded-lg text-sm font-medium text-content shadow-sm hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:ring-offset-2 disabled:opacity-50 transition-colors duration-200']) }}>
    {{ $slot }}
</button>
