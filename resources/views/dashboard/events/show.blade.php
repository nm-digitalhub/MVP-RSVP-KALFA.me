<x-layouts.app>
    <x-slot:title>{{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="$event->name"
            :subtitle="$event->event_date ? $event->event_date->format('Y-m-d') . ($event->venue_name ? ' · ' . $event->venue_name : '') : ($event->venue_name ?? '')"
        />
    </x-slot:header>

    @session('success')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-3 text-sm text-emerald-900 ring-1 ring-inset ring-emerald-200/50" role="status" aria-live="polite">{{ $value }}</div>
    @endsession

    <div class="space-y-4 sm:space-y-5">
        @if($event->imageUrl)
            <section class="card card-elevated overflow-hidden" aria-hidden="true">
                <img src="{{ $event->imageUrl }}" alt="" class="w-full h-48 sm:h-64 object-cover" width="800" height="256" />
            </section>
        @endif

        {{-- Event summary card --}}
        <section class="card overflow-hidden" aria-labelledby="event-status-heading">
            <div class="p-4 sm:p-5 border-b border-stroke flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="event-status-heading" class="sr-only">{{ __('Event status') }}</h2>
                    <p class="mt-1">
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
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('update', $event)
                        <a href="{{ route('dashboard.events.edit', [$organization, $event]) }}" class="btn btn-secondary btn-sm focus-ring">{{ __('Edit') }}</a>
                    @endcan
                    @can('delete', $event)
                        <form action="{{ route('dashboard.events.destroy', [$organization, $event]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Delete this event?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger-outline btn-sm focus-ring">{{ __('Delete') }}</button>
                        </form>
                    @endcan
                </div>
            </div>
            <div class="p-4 sm:p-5 grid grid-cols-2 gap-3 sm:gap-4">
                <p class="text-sm text-content-muted leading-relaxed">{{ __('Guests') }}: <strong class="text-content">{{ $event->guests->count() }}</strong></p>
                <p class="text-sm text-content-muted leading-relaxed">{{ __('Tables') }}: <strong class="text-content">{{ $event->eventTables->count() }}</strong></p>
            </div>
            @if($event->eventBilling)
                <div class="px-4 sm:px-5 pb-4 sm:pb-5">
                    <p class="text-sm text-content-muted leading-relaxed">{{ __('Billing status') }}: <strong class="text-content">{{ $event->eventBilling->status?->label() ?? __('—') }}</strong></p>
                </div>
            @endif
            @if(! empty($event->settings['description'] ?? null))
                <div class="px-4 sm:px-5 pb-4 sm:pb-5 border-t border-stroke">
                    <h3 class="text-sm font-semibold text-content mb-2">{{ __('Description') }}</h3>
                    <p class="text-sm text-content-muted leading-relaxed whitespace-pre-wrap">{{ $event->settings['description'] }}</p>
                </div>
            @endif
            @php
                $addToCalendarUrl = $eventLinks->addToCalendarUrl($event);
                $navLinks = $eventLinks->navigationLinks($event);
            @endphp
            @if($addToCalendarUrl || count($navLinks) > 0)
                <div class="px-4 sm:px-5 py-3 sm:py-4 border-t border-stroke flex flex-wrap gap-2">
                    @if($addToCalendarUrl)
                        <a href="{{ $addToCalendarUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm focus-ring inline-flex gap-2">
                            <x-heroicon-o-calendar-days class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ __('Add to calendar') }}</span>
                        </a>
                    @endif
                    @foreach($navLinks as $nav)
                        <a href="{{ $nav['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm focus-ring inline-flex gap-2">
                            <x-heroicon-o-map-pin class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ __($nav['label_key']) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            @if(count($event->customFields) > 0)
                <div class="px-4 sm:px-5 pb-4 sm:pb-5 border-t border-stroke">
                    <h3 class="text-sm font-semibold text-content mb-2">{{ __('Additional info') }}</h3>
                    <dl class="space-y-1 text-sm">
                        @foreach($event->customFields as $row)
                            <div><dt class="inline font-medium text-content">{{ e($row['label']) }}:</dt> <dd class="inline text-content-muted">{{ e($row['value']) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </section>

        {{-- Payment --}}
        <section class="card p-4 sm:p-5" aria-labelledby="payment-heading">
            <h2 id="payment-heading" class="text-base sm:text-lg font-semibold text-content mb-3">{{ __('Payment') }}</h2>
            @if($event->eventBilling && $event->eventBilling->status?->value === 'paid')
                <p class="text-sm text-content-muted leading-relaxed">{{ __('This event has been paid.') }}</p>
            @elseif(! $event->requiresPerEventPayment())
                <p class="text-sm text-content-muted leading-relaxed">{{ __('This event is covered by your active plan.') }}</p>
            @elseif($event->status->value === 'draft' || $event->status->value === 'pending_payment')
                @can('initiatePayment', $event)
                    <a href="{{ route('checkout.tokenize', [$event->organization, $event]) }}" class="btn btn-primary focus-ring">{{ __('Proceed to payment') }}</a>
                @else
                    <p class="text-sm text-content-muted leading-relaxed">{{ __('Only organization owners and admins can initiate payment.') }}</p>
                @endcan
            @else
                <p class="text-sm text-content-muted leading-relaxed">{{ __('Payment is not applicable for this event status.') }}</p>
            @endif
        </section>

        {{-- Management cards: Guests, Tables, Invitations, Seat assignments --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4" aria-label="{{ __('Event management') }}">
            @php
                $guestCount = $event->guests->count();
                $tableCount = $event->eventTables->count();
                $invitationCount = $event->invitations->count();
                $assignedCount = $event->seatAssignments->count();
                $cards = [
                    ['key' => 'guests', 'route' => 'dashboard.events.guests.index', 'count' => $guestCount, 'label_singular' => __('guest'), 'label_plural' => __('guests')],
                    ['key' => 'tables', 'route' => 'dashboard.events.tables.index', 'count' => $tableCount, 'label_singular' => __('table'), 'label_plural' => __('tables')],
                    ['key' => 'invitations', 'route' => 'dashboard.events.invitations.index', 'count' => $invitationCount, 'label_singular' => __('invitation'), 'label_plural' => __('invitations')],
                    ['key' => 'seat-assignments', 'route' => 'dashboard.events.seat-assignments.index', 'count' => $assignedCount, 'label_singular' => __('assigned'), 'label_plural' => __('assigned_plural')],
                ];
                $titles = ['guests' => __('Guests'), 'tables' => __('Tables'), 'invitations' => __('Invitations'), 'seat-assignments' => __('Seat assignments')];
            @endphp
            @foreach($cards as $card)
                @php
                    $label = $card['count'] === 1 ? $card['label_singular'] : $card['label_plural'];
                @endphp
                <a href="{{ route($card['route'], [$organization, $event]) }}" class="card card-clickable group block">
                    <div class="px-3 sm:px-4 py-3 sm:py-4 border-b border-stroke flex justify-between items-center">
                        <h2 class="text-base sm:text-lg font-medium text-content">{{ $titles[$card['key']] }}</h2>
                        <span class="text-sm font-medium text-brand group-hover:text-brand-hover">{{ __('Manage') }}</span>
                    </div>
                    <div class="p-4 sm:p-5">
                        <p class="text-sm text-content-muted leading-relaxed"><span class="font-semibold text-content">{{ $card['count'] }}</span> {{ $label }}</p>
                    </div>
                </a>
            @endforeach
        </section>
    </div>
</x-layouts.app>
