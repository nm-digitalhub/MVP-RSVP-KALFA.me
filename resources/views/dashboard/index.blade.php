<x-layouts.app>
    <x-slot:title>{{ __('Dashboard') }}</x-slot:title>

<div class="min-h-screen bg-surface py-6 sm:py-8 px-3 sm:px-4">
    <div class="max-w-7xl mx-auto">
        <div class="mb-5 sm:mb-6">
            @session('success')
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-3 text-sm text-emerald-900 ring-1 ring-inset ring-emerald-200/50" role="status" aria-live="polite">{{ $value }}</div>
            @endsession
            <h1 class="text-xl sm:text-2xl font-semibold text-content">{{ $organization->name }}</h1>
            <p class="mt-1 text-sm text-content-muted">{{ __('Dashboard') }}</p>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-stroke px-3 sm:px-4 py-3 sm:py-4 flex justify-between items-center gap-3">
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
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Title') }}</th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Date') }}</th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Status') }}</th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-start text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">{{ __('Guests') }}</th>
                            <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-stroke">
                        @forelse($events as $event)
                            <tr class="data-table-row">
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm font-medium text-content">{{ $event->name }}</td>
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted">{{ $event->event_date?->format('Y-m-d') }}</td>
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
                                <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted hidden sm:table-cell">{{ $event->guests_count ?? 0 }}</td>
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
    </div>
</div>
</x-layouts.app>
