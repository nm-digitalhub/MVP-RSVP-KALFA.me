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
    {{ $attributes->merge(['class' => 'w-full rounded-lg border border-gray-300 px-4 py-2 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 rtl:text-end dark:border-gray-600 dark:bg-gray-800 dark:text-white']) }}
>{{ $name ? old($name) : ($slot ?? '') }}</textarea>
