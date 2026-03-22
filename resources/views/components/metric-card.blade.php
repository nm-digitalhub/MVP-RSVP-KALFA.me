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
    <div class="p-5">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                @if ($title)
                    <p class="text-sm font-medium uppercase tracking-wider text-content-muted">
                        {{ $title }}
                    </p>
                @endif

                @if ($loading)
                    <div class="loading-skeleton h-8 w-20 mt-2"></div>
                @else
                    @if ($value !== null)
                        <p class="mt-2 text-3xl font-bold text-content">
                            {{ $value }}
                        </p>
                    @endif
                @endif

                @if ($change !== null)
                    <div class="mt-2 flex items-center gap-1.5 text-sm font-medium {{ $trendColors[$trend] ?? $trendColors['neutral'] }}">
                        @if ($trend === 'up')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        @elseif ($trend === 'down')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        @endif
                        <span>{{ $change }}</span>
                    </div>
                @endif
            </div>

            @if ($hasIconSlot)
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand/5 ring-1 ring-brand/10 transition-all duration-200 group-hover:bg-brand/10 group-hover:scale-110">
                    {{ $icon }}
                </div>
            @endif
        </div>
    </div>
</div>
