@props([
    'for' => null,
    'value' => null,
    'class' => 'block mb-1 text-sm font-medium text-content rtl:text-end',
])
<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => $class]) }}
>
    {{ $value ?? $slot }}
</label>
