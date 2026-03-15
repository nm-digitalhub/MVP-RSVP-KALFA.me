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
    {{ $attributes->merge(['class' => 'min-h-[44px] w-full rounded-lg border border-stroke bg-card shadow-sm transition-colors focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 focus:ring-offset-0 text-content placeholder:text-content-muted rtl:text-end']) }}
>
