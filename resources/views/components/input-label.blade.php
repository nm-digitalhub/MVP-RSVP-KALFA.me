@props([
    'for' => null,
    'value' => null,
    'class' => 'block mb-1 text-sm font-medium text-gray-700 rtl:text-end dark:text-gray-300',
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => $class]) }}
>
    {{ $value ?? $slot }}
</label>
