@props([
    'items' => [],
    'separator' => '/',
    'class' => 'text-sm',
    'listClass' => 'flex flex-wrap items-center gap-1',
])

@php
    $items = $items ?: [['label' => $slot->isEmpty() ? __('Dashboard') : $slot, 'url' => null]];
    $visibleItems = array_values($items);
@endphp

<nav aria-label="Breadcrumb" class="{{ $class }}">
    <ol class="{{ $listClass }}">
        @foreach($visibleItems as $index => $item)
            <li class="inline-flex items-center gap-1">
                @if(isset($item['url']) && $item['url'])
                    <a href="{{ $item['url'] }}" class="text-content-muted hover:text-content transition-colors">{{ $item['label'] }}</a>
                @else
                    <span class="font-medium text-content">{{ $item['label'] }}</span>
                @endif

                @if($index < count($visibleItems) - 1)
                    <span aria-hidden="true" class="text-content-muted">{{ $separator }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
