<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-brand border border-transparent rounded-lg text-sm font-medium text-white hover:bg-brand-hover active:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:ring-offset-2 disabled:opacity-50 transition-colors duration-200']) }}>
    {{ $slot }}
</button>
