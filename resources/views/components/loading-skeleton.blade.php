@props([
    'type' => 'text', // text, circle, card, avatar
    'width' => null,
    'height' => null,
    'count' => 1,
    'class' => '',
])

@php
$typeClasses = [
    'text' => 'h-4 rounded w-3/4',
    'circle' => 'h-12 w-12 rounded-full',
    'card' => 'h-24 rounded-xl w-full',
    'avatar' => 'h-10 w-10 rounded-full',
][$type] ?? 'h-4 rounded w-3/4';

$widthClass = $width ? "w-{$width}" : '';
$heightClass = $height ? "h-{$height}" : '';
@endphp

<template x-for="i in {{ $count }}">
    <div class="animate-pulse bg-gray-200 dark:bg-gray-700 {{ $typeClasses }} {{ $widthClass }} {{ $heightClass }} {{ $class }}" aria-hidden="true"></div>
</template>
