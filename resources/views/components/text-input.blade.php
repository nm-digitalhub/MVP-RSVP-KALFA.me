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
    {{ $attributes->merge(['class' => 'input-base']) }}
>
