<div class="space-y-4 sm:space-y-5" x-data="{ mobileFiltersOpen: false }">
    {{-- Success Message --}}
    @session('success')
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-3 text-sm text-emerald-900 ring-1 ring-inset ring-emerald-200/50"
             role="status" aria-live="polite">
            {{ $value }}
        </div>
    @endsession

    {{-- Search & Filter Bar with Flux UI --}}
    <div class="card overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-stroke bg-surface-alt/50">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                {{-- Search Input with Flux --}}
                <div class="flex-1 w-full lg:max-w-md">
                    <flux:field>
                        <flux:label for="search">{{ __('Search events') }}</flux:label>
                        <flux:description>
                            {{ __('Search by event name...') }}
                        </flux:description>

                        <div class="relative">
                            {{-- Search Icon --}}
                            <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none text-content-muted">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            </div>

                            <flux:input
                                id="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('Search events...') }}"
                                class="pe-10"
                            />

                            {{-- Clear Button (when search has value) --}}
                            @if($search !== '')
                                <button
                                    wire:click="clearSearch"
                                    class="absolute inset-y-0 end-0 flex items-center pe-3 text-content-muted hover:text-content transition-colors"
                                    title="{{ __('Clear search') }}"
                                >
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            @endif
                        </div>
                    </flux:field>
                </div>

                {{-- Status Filter & Actions Row --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Status Filter Dropdown --}}
                    <div class="w-full sm:w-auto">
                        <flux:field>
                            <flux:label for="filter-status">{{ __('Status') }}</flux:label>

                            <flux:select
                                id="filter-status"
                                wire:model.live="filterStatus"
                                placeholder="{{ __('All statuses') }}"
                            >
                                <option value="">{{ __('All statuses') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="draft">{{ __('Draft') }}</option>
                                <option value="pending_payment">{{ __('Pending Payment') }}</option>
                                <option value="cancelled">{{ __('Cancelled') }}</option>
                            </flux:select>
                        </flux:field>
                    </div>

                    {{-- Create Event Button --}}
                    <a href="{{ route('dashboard.events.create') }}" class="w-full sm:w-auto">
                        <flux:button variant="primary" class="w-full sm:w-auto">
                            <x-slot:icon>
                                <x-heroicon-o-plus class="w-4 h-4" />
                            </x-slot:icon>
                            {{ __('Create') }}
                        </flux:button>
                    </a>

                    {{-- Reset Filters Button (when filters active) --}}
                    @if($hasActiveFilters)
                        <flux:button wire:click="resetFilters" variant="ghost" class="w-full sm:w-auto">
                            {{ __('Reset') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Results Indicator --}}
        <div class="px-4 sm:px-5 py-3 bg-surface border-b border-stroke flex items-center justify-between">
            @if($search !== '' || $filterStatus !== '')
                <div class="flex items-center gap-2 text-sm">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand/10 text-brand text-xs font-medium">
                        <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5" />
                        {{ __('Showing results') }}
                    </span>
                    <span class="text-content-muted">
                        @if($events->total() > 0)
                            {{ __(':count events found', ['count' => $events->total()]) }}
                        @else
                            {{ __('No events found') }}
                        @endif
                    </span>
                </div>
            @else
                <span class="text-sm text-content-muted">
                    {{ __(':total events', ['total' => $events->total()]) }}
                </span>
            @endif
        </div>

        {{-- Events Table with Empty State --}}
        <div class="overflow-x-auto">
            @if($events->count() > 0)
                <table class="min-w-full divide-y divide-stroke">
                    <thead class="bg-surface">
                        <tr>
                            <th scope="col" class="px-4 sm:px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Event') }}
                            </th>
                            <th scope="col" class="px-4 sm:px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Date') }}
                            </th>
                            <th scope="col" class="px-4 sm:px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Status') }}
                            </th>
                            <th scope="col" class="px-4 sm:px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">
                                {{ __('Guests') }}
                            </th>
                            <th scope="col" class="px-4 sm:px-5 py-3 text-end text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-stroke">
                        @foreach($events as $event)
                            <tr class="data-table-row hover:bg-surface-alt/30 transition-colors">
                                <td class="px-4 sm:px-5 py-3 sm:py-4">
                                    <div class="flex items-start gap-3">
                                        <span class="font-medium text-content">{{ $event->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-5 py-3 sm:py-4 text-sm text-content-muted">
                                    {{ $event->event_date?->format('Y-m-d') ?? '—' }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 sm:py-4">
                                    <flux:badge
                                        :color="$event->status->getBadgeColor()"
                                        variant="solid"
                                    >
                                        {{ $event->status?->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 sm:px-5 py-3 sm:py-4 text-sm text-content-muted hidden sm:table-cell">
                                    {{ number_format($event->guests_count) }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 sm:py-4 text-end">
                                    <a href="{{ route('dashboard.events.show', $event) }}"
                                       class="interactive inline-flex items-center font-medium text-brand hover:text-brand-hover focus-ring rounded px-2 py-1 text-xs">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Pagination --}}
                @if($events->hasPages())
                    <div class="px-4 sm:px-5 py-3 sm:py-4 border-t border-stroke bg-surface flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <span class="text-sm text-content-muted">
                            {{ __('Showing :from - :to of :total events', [
                                'from' => $events->firstItem(),
                                'to' => $events->lastItem(),
                                'total' => $events->total(),
                            ]) }}
                        </span>
                        {{ $events->links() }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="py-12 sm:py-16 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-surface/50 text-content-muted mb-4">
                        @if($search !== '')
                            <x-heroicon-o-magnifying-glass class="w-8 h-8" />
                        @else
                            <x-kalfa-app-icon class="w-8 h-8 opacity-40" />
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-content mb-2">
                        @if($search !== '')
                            {{ __('No events found') }}
                        @else
                            {{ __('No events yet') }}
                        @endif
                    </h3>
                    <p class="text-sm text-content-muted mb-6 max-w-sm mx-auto">
                        @if($search !== '')
                            {{ __('No events match your search criteria.') }}
                        @else
                            {{ __('Get started by creating your first event.') }}
                        @endif
                    </p>
                    @if($search !== '')
                        <flux:button wire:click="resetFilters" variant="ghost">
                            {{ __('Clear search') }}
                        </flux:button>
                    @else
                        <a href="{{ route('dashboard.events.create') }}">
                            <flux:button variant="primary">
                                {{ __('Create your first event') }}
                            </flux:button>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
