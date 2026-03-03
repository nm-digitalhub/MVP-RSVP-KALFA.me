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
    {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full']) }}
>
