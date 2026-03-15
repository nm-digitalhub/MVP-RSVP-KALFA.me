<div>
    {{-- Back + actions bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <a href="{{ route('dashboard.events.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 rounded-lg transition-colors duration-200 min-h-[44px]">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('Back to event') }}
        </a>
        @can('update', $event)
            @if(!$showForm)
                <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                    <x-heroicon-o-plus-circle class="w-5 h-5" />
                    {{ __('Add table') }}
                </x-primary-button>
            @endif
        @endcan
    </div>

    {{-- Add/Edit form --}}
    @if($showForm)
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" aria-labelledby="table-form-heading">
            <h2 id="table-form-heading" class="text-lg font-semibold text-gray-900 mb-4">{{ $editingId ? __('Edit table') : __('Add table') }}</h2>
            <div class="space-y-4 max-w-md">
                <div>
                    <x-input-label for="table_name" :value="__('Table name')" />
                    <x-text-input id="table_name" wire:model="name" class="mt-1 block w-full" autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="table_capacity" :value="__('Capacity')" />
                    <x-text-input id="table_capacity" type="number" min="0" wire:model="capacity" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('capacity')" class="mt-1" />
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
        </section>
    @endif

    {{-- Summary (when tables exist) --}}
    @if($tables->isNotEmpty())
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex flex-wrap gap-4">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-50 border border-gray-200">
                    <span class="text-sm font-medium text-gray-500">{{ __('Tables') }}</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $tables->count() }}</span>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 border border-indigo-100">
                    <span class="text-sm font-medium text-indigo-700">{{ __('Total capacity') }}</span>
                    <span class="text-lg font-semibold text-indigo-900">{{ $tables->sum('capacity') }}</span>
                </div>
            </div>
            {{-- View mode: List | Seating chart (seating plan) --}}
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" role="group" aria-label="{{ __('View mode') }}">
                <button type="button" wire:click="$set('viewMode', 'list')" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md min-h-[44px] transition-colors duration-200 {{ $viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900 cursor-pointer' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    {{ __('List') }}
                </button>
                <button type="button" wire:click="$set('viewMode', 'chart')" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md min-h-[44px] transition-colors duration-200 {{ $viewMode === 'chart' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900 cursor-pointer' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    {{ __('Seating chart') }}
                </button>
            </div>
        </div>
    @endif

    {{-- List view: sortable cards (only when tables exist) --}}
    @if($viewMode === 'list' && $tables->isNotEmpty())
        <div class="space-y-4" @can('update', $event) wire:sort="handleSort" @endcan>
            @foreach($tables as $t)
                <article wire:key="table-{{ $t->id }}" wire:sort:item="{{ $t->id }}" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:border-gray-300 transition-colors duration-200">
                    <div class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            @can('update', $event)
                                <div wire:sort:handle class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-grab active:cursor-grabbing focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 touch-none" aria-label="{{ __('Drag to reorder') }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8 6h2v2H8V6zm0 5h2v2H8v-2zm0 5h2v2H8v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2z"/>
                                    </svg>
                                </div>
                            @endcan
                            <div class="shrink-0 w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600" aria-hidden="true">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $t->name }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    {{ __('Capacity') }}: <span class="font-medium text-gray-700">{{ $t->capacity ?? 0 }}</span>
                                </p>
                            </div>
                        </div>
                        @can('update', $event)
                            <div wire:sort:ignore class="flex items-center gap-2 shrink-0">
                                <button type="button" wire:click="openEdit({{ $t->id }})" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 py-2 text-sm font-medium text-brand hover:text-indigo-800 hover:bg-indigo-50 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">
                                    {{ __('Edit') }}
                                </button>
                                <button type="button" wire:click="deleteTable({{ $t->id }})" wire:confirm="{{ __('Delete this table?') }}" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 py-2 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">
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
        <section class="rounded-2xl border-2 border-gray-200 bg-white shadow-sm overflow-hidden" aria-labelledby="seating-plan-heading">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6">
                <h2 id="seating-plan-heading" class="text-base font-semibold text-gray-900">{{ __('Seating plan') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500">{{ __('Visual layout by table order. To reorder, switch to List view and drag.') }}</p>
            </div>
            <div class="p-6 min-h-[220px] flex items-center justify-center">
                <div class="w-full max-w-3xl rounded-xl bg-gray-50/80 border-2 border-dashed border-gray-200 p-6" role="img" aria-label="{{ __('Table layout') }}">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($tables as $t)
                            <div wire:key="chart-table-{{ $t->id }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center transition-shadow duration-200 hover:shadow-md">
                                <div class="w-12 h-12 mx-auto rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-brand mb-2" aria-hidden="true">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $t->name }}">{{ $t->name }}</p>
                                <p class="text-xs text-gray-500">{{ $t->capacity ?? 0 }} {{ __('seats') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Empty state (no tables) --}}
    @if($tables->isEmpty())
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 sm:p-12 text-center">
                <div class="max-w-sm mx-auto">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400 mb-4" aria-hidden="true">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('No tables yet') }}</h3>
                    <p class="text-sm text-gray-500 mb-6">{{ __('Add tables to manage seating for this event.') }}</p>
                    @can('update', $event)
                        <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                            <x-heroicon-o-plus-circle class="w-5 h-5" />
                            {{ __('Add table') }}
                        </x-primary-button>
                    @endcan
                </div>
            </div>
        </div>
    @endif
</div>
