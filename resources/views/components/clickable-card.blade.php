@props([
    'href' => null,
    'wireNavigate' => false,
    'hover' => true,
])

@php
    $baseClasses = 'block cursor-pointer transition-all duration-200';
    $hoverClasses = $hover ? 'hover:shadow-md hover:translate-y-[-2px] hover:border-brand/30' : '';
    $wrapperTag = $href ? 'a' : 'div';
@endphp

<{{ $wrapperTag }}
    @if ($href) href="{{ $href }}" @endif
    @if ($wireNavigate) wire:navigate @endif
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $hoverClasses]) }}
>
    {{ $slot }}
</{{ $wrapperTag }}>
