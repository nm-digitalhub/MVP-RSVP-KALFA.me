<div>
    {{-- Back + actions bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
        <a href="{{ route('dashboard.events.show', $event) }}" class="btn btn-secondary btn-sm focus-ring inline-flex items-center gap-2">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">{{ __('Back to event') }}</span>
        </a>
        @can('update', $event)
            @if(!$showForm)
                <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    <span class="hidden sm:inline">{{ __('Add table') }}</span>
                </x-primary-button>
            @endif
        @endcan
    </div>

    {{-- Add/Edit form --}}
    @if($showForm)
        <div class="card p-4 sm:p-5 mb-4 sm:mb-5">
            <h2 class="text-base sm:text-lg font-semibold text-content mb-3 sm:mb-4">{{ $editingId ? __('Edit table') : __('Add table') }}</h2>
            <div class="space-y-3 sm:space-y-4 max-w-md">
                <div>
                    <x-ts-input id="table_name" wire:model="name" label="{{ __('Table name') }}" autofocus />
                </div>
                <div>
                    <x-ts-input id="table_capacity" type="number" min="0" wire:model="capacity" label="{{ __('Capacity') }}" />
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <x-primary-button wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                            <svg class="animate-spin motion-reduce:animate-none h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ __('Saving…') }}
                        </span>
                    </x-primary-button>
                    <x-secondary-button type="button" wire:click="cancelForm" wire:loading.attr="disabled">{{ __('Cancel') }}</x-secondary-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Summary (when tables exist) --}}
    @if($tables->isNotEmpty())
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
            <div class="flex flex-wrap gap-2 sm:gap-3">
                <div class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-surface border border-stroke">
                    <span class="text-xs sm:text-sm font-medium text-content-muted">{{ __('Tables') }}</span>
                    <span class="text-base sm:text-lg font-semibold text-content">{{ $tables->count() }}</span>
                </div>
                <div class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-brand/5 border border-brand/20">
                    <span class="text-xs sm:text-sm font-medium text-brand">{{ __('Total capacity') }}</span>
                    <span class="text-base sm:text-lg font-semibold text-brand">{{ $tables->sum('capacity') }}</span>
                </div>
            </div>
            {{-- View mode: List | Seating chart (seating plan) --}}
            <div class="inline-flex rounded-lg border border-stroke bg-surface p-0.5" role="group" aria-label="{{ __('View mode') }}">
                <button type="button" wire:click="$set('viewMode', 'list')" class="inline-flex items-center gap-2 px-2.5 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-md min-h-[44px] {{ $viewMode === 'list' ? 'bg-card text-content shadow-sm' : 'text-content-muted hover:text-content focus-ring rounded' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <span class="hidden sm:inline">{{ __('List') }}</span>
                </button>
                <button type="button" wire:click="$set('viewMode', 'chart')" class="inline-flex items-center gap-2 px-2.5 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-md min-h-[44px] {{ $viewMode === 'chart' ? 'bg-card text-content shadow-sm' : 'text-content-muted hover:text-content focus-ring rounded' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <span class="hidden sm:inline">{{ __('Seating chart') }}</span>
                </button>
            </div>
        </div>
    @endif

    {{-- List view: sortable cards (only when tables exist) --}}
    @if($viewMode === 'list' && $tables->isNotEmpty())
        <div class="space-y-3 sm:space-y-4" @can('update', $event) wire:sort="handleSort" @endcan>
            @foreach($tables as $t)
                <article wire:key="table-{{ $t->id }}" wire:sort:item="{{ $t->id }}" class="card overflow-hidden hover:border-stroke-hover transition-colors duration-200">
                    <div class="p-3.5 sm:p-4 md:p-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            @can('update', $event)
                                <div wire:sort:handle class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center text-content-muted hover:text-content hover:bg-surface cursor-grab active:cursor-grabbing focus-ring touch-none" aria-label="{{ __('Drag to reorder') }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8 6h2v2H8V6zm0 5h2v2H8v-2zm0 5h2v2H8v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2z"/>
                                    </svg>
                                </div>
                            @endcan
                            <div class="shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-surface flex items-center justify-center text-content-muted" aria-hidden="true">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-base font-semibold text-content truncate">{{ $t->name }}</h3>
                                <p class="text-xs sm:text-sm text-content-muted mt-0.5">
                                    {{ __('Capacity') }}: <span class="font-medium text-content">{{ $t->capacity ?? 0 }}</span>
                                </p>
                            </div>
                        </div>
                        @can('update', $event)
                            <div wire:sort:ignore class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                                <button type="button" wire:click="openEdit({{ $t->id }})" class="interactive inline-flex min-h-[44px] items-center px-2 sm:px-3 text-xs sm:text-sm font-medium text-brand hover:text-brand-hover rounded">
                                    {{ __('Edit') }}
                                </button>
                                <button type="button" wire:click="deleteTable({{ $t->id }})" wire:confirm="{{ __('Delete this table?') }}" class="interactive inline-flex min-h-[44px] items-center px-2 sm:px-3 text-xs sm:text-sm font-medium text-danger hover:text-danger-hover rounded">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Seating chart / Seating plan: visual layout --}}
    @if($viewMode === 'chart' && $tables->isNotEmpty())
        <section class="card overflow-hidden" aria-labelledby="seating-plan-heading">
            <div class="border-b border-stroke bg-surface px-3.5 sm:px-4 md:px-5 py-3">
                <h2 id="seating-plan-heading" class="text-sm sm:text-base font-semibold text-content">{{ __('Seating plan') }}</h2>
                <p class="mt-0.5 text-xs sm:text-sm text-content-muted">{{ __('Visual layout by table order. To reorder, switch to List view and drag.') }}</p>
            </div>
            <div class="p-4 sm:p-5 md:p-6 min-h-[220px] flex items-center justify-center">
                <div class="w-full max-w-3xl rounded-xl bg-surface/80 border-2 border-dashed border-stroke p-4 sm:p-5 md:p-6" role="img" aria-label="{{ __('Table layout') }}">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
                        @foreach($tables as $t)
                            <div wire:key="chart-table-{{ $t->id }}" class="bg-card rounded-xl shadow-sm border border-stroke p-3 sm:p-4 text-center transition-shadow duration-200 hover:shadow-md">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto rounded-lg bg-brand/5 border border-brand/20 flex items-center justify-center text-brand mb-2" aria-hidden="true">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                </div>
                                <p class="text-xs sm:text-sm font-semibold text-content truncate" title="{{ $t->name }}">{{ $t->name }}</p>
                                <p class="text-[10px] sm:text-xs text-content-muted">{{ $t->capacity ?? 0 }} {{ __('seats') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Empty state (no tables) --}}
    @if($tables->isEmpty())
        <div class="space-y-3 sm:space-y-4">
            <div class="card p-6 sm:p-8 md:p-10 lg:p-12 text-center">
                <div class="max-w-sm mx-auto">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-2xl bg-surface flex items-center justify-center text-content-muted mb-4" aria-hidden="true">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold text-content mb-1">{{ __('No tables yet') }}</h3>
                    <p class="text-xs sm:text-sm text-content-muted mb-4 sm:mb-6">{{ __('Add tables to manage seating for this event.') }}</p>
                    @can('update', $event)
                        <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                            <x-heroicon-o-plus class="w-5 h-5" />
                            <span class="hidden sm:inline">{{ __('Add table') }}</span>
                        </x-primary-button>
                    @endcan
                </div>
            </div>
        </div>
    @endif
</div>
