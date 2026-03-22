@props([
    'id' => null,
    'name' => null,
    'rows' => 4,
    'placeholder' => '',
    'value' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $defaultValue = $value ?? (isset($slot) ? trim((string) $slot) : null);
    $resolvedValue = $name ? old($name, $defaultValue) : $defaultValue;
@endphp

<textarea
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    @if ($required) required @endif
    @if ($disabled) disabled @endif
    {{ $attributes->merge(['class' => 'textarea-base']) }}
>{{ $resolvedValue ?? '' }}</textarea>
