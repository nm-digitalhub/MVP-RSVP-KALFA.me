@props([
    'type' => 'text',
    'name' => null,
    'value' => null,
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
])

<input
    type="{{ $type }}"
    @if ($name) name="{{ $name }}" @endif
    value="{{ $name ? old($name, $value) : $value }}"
    placeholder="{{ $placeholder }}"
    @if ($required) required @endif
    @if ($disabled) disabled @endif
    {{ $attributes->merge(['class' => 'min-h-[44px] w-full rounded-lg border border-gray-300 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-800 dark:text-white rtl:text-end']) }}
>
