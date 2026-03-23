@props([
    'title' => null,
    'value' => null,
    'change' => null,
    'trend' => 'neutral', // up, down, neutral
    'loading' => false,
])

@php
    $trendColors = [
        'up' => 'text-emerald-600 bg-emerald-50',
        'down' => 'text-red-600 bg-red-50',
        'neutral' => 'text-content-muted bg-surface/50',
    ];

    $hasIconSlot = $slot->isNotEmpty('icon');
@endphp

<div class="card group">
    <div class="p-3 sm:p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                @if ($title)
                    <p class="text-[10px] sm:text-xs font-medium uppercase tracking-wider text-content-muted">
                        {{ $title }}
                    </p>
                @endif

                @if ($loading)
                    <div class="loading-skeleton h-6 w-16 mt-1"></div>
                @else
                    @if ($value !== null)
                        <p class="mt-0.5 text-lg sm:text-xl font-bold text-content truncate">
                            {{ $value }}
                        </p>
                    @endif
                @endif

                @if ($change !== null)
                    <div class="mt-1 flex items-center gap-1 text-xs font-medium {{ $trendColors[$trend] ?? $trendColors['neutral'] }}">
                        @if ($trend === 'up')
                            <svg class="h-3 w-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        @elseif ($trend === 'down')
                            <svg class="h-3 w-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        @endif
                        <span class="truncate">{{ $change }}</span>
                    </div>
                @endif
            </div>

            @if ($hasIconSlot)
                <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-lg bg-brand/5 ring-1 ring-brand/10 transition-all duration-200 group-hover:bg-brand/10">
                    {{ $icon }}
                </div>
            @endif
        </div>
    </div>
</div>
