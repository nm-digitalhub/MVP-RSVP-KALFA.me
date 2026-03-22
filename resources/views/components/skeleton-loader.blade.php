@props([
    'count' => 1,
    'variant' => 'text', // text, card, circle, custom
    'width' => null,
    'height' => null,
])

@php
    $baseClasses = 'animate-pulse bg-surface/80 rounded';

    $variantClasses = match($variant) {
        'text' => 'h-4 w-full',
        'card' => 'h-24 w-full rounded-2xl',
        'circle' => 'h-10 w-10 rounded-full',
        'custom' => '',
        default => 'h-4 w-full',
    };

    $style = '';
    if ($width) $style .= "width: {$width};";
    if ($height) $style .= "height: {$height};";
@endphp

@foreach(range(1, $count) as $i)
    <div class="{{ $baseClasses }} {{ $variantClasses }}" @if($style) style="{{ $style }}" @endif aria-hidden="true"></div>
@endforeach
