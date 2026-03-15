@props([
    'id' => null,
    'name' => null,
    'rows' => 4,
    'placeholder' => '',
])

<textarea
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge(['class' => 'w-full rounded-lg border border-stroke bg-card px-4 py-2 shadow-sm transition-colors focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30 focus:ring-offset-0 text-content placeholder:text-content-muted rtl:text-end']) }}
>{{ $name ? old($name) : ($slot ?? '') }}</textarea>
