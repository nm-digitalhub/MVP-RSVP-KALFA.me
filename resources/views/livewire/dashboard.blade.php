<div class="space-y-4 sm:space-y-5">
    @if($organization)
        {{-- SaaS metric cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">

        {{-- SaaS metric cards --}}
            <x-metric-card
                :title="__('Total Events')"
                :value="$totalEvents ?? 0"
            >
                <x-slot:icon>
                    <x-kalfa-app-icon class="h-6 w-6 text-brand" />
                </x-slot:icon>
            </x-metric-card>

            <x-metric-card
                :title="__('Total Guests')"
                :value="$totalGuests ?? 0"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </x-slot:icon>
            </x-metric-card>

            <x-metric-card
                :title="__('Upcoming Event')"
                :value="$upcomingEvent?->name ?? __('None scheduled')"
                :change="$upcomingEvent?->event_date?->format('d.m.Y')"
                trend="neutral"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </x-slot:icon>
            </x-metric-card>

            <x-metric-card
                :title="__('Organization')"
                :value="__('Active')"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </x-slot:icon>
            </x-metric-card>
        </div>

        {{-- Events Table --}}
        <div class="card overflow-hidden">
            <div class="border-b border-stroke px-3 sm:px-4 py-2.5 sm:py-3 flex justify-between items-center gap-3">
                <h2 class="text-base sm:text-lg font-semibold text-content">{{ __('Events') }}</h2>
                <a href="{{ route('dashboard.events.create') }}" class="btn btn-primary btn-sm focus-ring">
                    <x-heroicon-o-plus class="h-4 w-4" />
                    <span class="hidden sm:inline">{{ __('Create') }}</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stroke">
                    <thead class="bg-surface">
                        <tr>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Title') }}
                            </th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Date') }}
                            </th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">
                                {{ __('Status') }}
                            </th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">
                                {{ __('Guests') }}
                            </th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted text-end">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-stroke">
                        @forelse($events as $event)
                            <tr wire:key="event-{{ $event->id }}" class="data-table-row">
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm font-medium text-content">
                                    {{ $event->name }}
                                </td>
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted">
                                    {{ $event->event_date?->format('d.m.Y') }}
                                </td>
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3">
                                    <span class="badge text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1
                                        @switch($event->status->value ?? '')
                                            @case('draft') badge-neutral @break
                                            @case('pending_payment') badge-warning @break
                                            @case('active') badge-success @break
                                            @case('locked') badge-info @break
                                            @case('archived') badge-neutral @break
                                            @case('cancelled') badge-danger @break
                                            @default badge-neutral
                                        @endswitch">
                                        {{ $event->status?->label() ?? __('—') }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted hidden sm:table-cell">
                                    {{ $event->guests_count ?? 0 }}
                                </td>
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-end">
                                    <a href="{{ route('dashboard.events.show', [$organization, $event]) }}"
                                       class="interactive inline-flex font-medium text-brand hover:text-brand-hover focus-ring rounded px-2 py-1 text-xs">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 sm:px-4 py-8 sm:py-10 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="flex size-12 items-center justify-center rounded-xl bg-surface/50">
                                            <x-kalfa-app-icon class="h-7 w-7 opacity-40" alt="" />
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-sm font-medium text-content">{{ __('No events yet.') }}</p>
                                            <p class="text-xs text-content-muted">{{ __('Get started by creating your first event.') }}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- No Organization State --}}
        <div class="card card-elevated p-6 sm:p-8 text-center">
            <div class="flex justify-center mb-4">
                <div class="flex size-14 sm:size-16 items-center justify-center rounded-2xl bg-brand/5 ring-1 ring-brand/10">
                    <x-kalfa-app-icon class="h-8 w-8 sm:h-10 sm:w-10 text-brand" alt="" />
                </div>
            </div>

            <x-kalfa-wordmark class="mb-3 h-6 sm:h-8" />

            <p class="mx-auto text-sm leading-relaxed text-content-muted max-w-md">
                {{ __('Choose or create an organization to start managing events, guests, and invitations.') }}
            </p>

            <div class="mt-6">
                <a href="{{ route('organizations.index') }}"
                   wire:navigate
                   class="btn btn-primary focus-ring inline-flex min-h-[40px] px-6">
                    {{ __('Go to organizations') }}
                </a>
            </div>
        </div>
    @endif
</div>
