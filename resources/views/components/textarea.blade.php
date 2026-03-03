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
    {{ $attributes->merge(['class' => 'w-full px-4 py-2 border rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:outline-none rtl:text-end border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white']) }}
>{{ $name ? old($name) : ($slot ?? '') }}</textarea>
