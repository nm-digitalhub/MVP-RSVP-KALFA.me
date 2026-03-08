<x-layouts.app>
    <x-slot:title>{{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="$event->name"
            :subtitle="$event->event_date ? $event->event_date->format('Y-m-d') . ($event->venue_name ? ' · ' . $event->venue_name : '') : ($event->venue_name ?? '')"
        />
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200" role="status" aria-live="polite">{{ session('success') }}</div>
    @endif

    <div class="space-y-6">
        @if($event->imageUrl())
            <section class="rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm" aria-hidden="true">
                <img src="{{ $event->imageUrl() }}" alt="" class="w-full h-48 sm:h-64 object-cover" width="800" height="256" />
            </section>
        @endif

        {{-- Event summary card --}}
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" aria-labelledby="event-status-heading">
            <div class="p-6 border-b border-gray-200 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="event-status-heading" class="sr-only">{{ __('Event status') }}</h2>
                    <p class="mt-1">
                        <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full
                            @switch($event->status->value ?? '')
                                @case('draft') bg-gray-100 text-gray-800 @break
                                @case('pending_payment') bg-amber-100 text-amber-800 @break
                                @case('active') bg-green-100 text-green-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ $event->status?->value ? __($event->status->value) : __('—') }}
                        </span>
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('update', $event)
                        <a href="{{ route('dashboard.events.edit', $event) }}" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Edit') }}</a>
                    @endcan
                    @can('delete', $event)
                        <form action="{{ route('dashboard.events.destroy', $event) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Delete this event?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-4 py-2.5 border border-red-200 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Delete') }}</button>
                        </form>
                    @endcan
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <p class="text-sm text-gray-600 leading-relaxed">{{ __('Guests') }}: <strong class="text-gray-900">{{ $event->guests->count() }}</strong></p>
                <p class="text-sm text-gray-600 leading-relaxed">{{ __('Tables') }}: <strong class="text-gray-900">{{ $event->eventTables->count() }}</strong></p>
            </div>
            @if($event->eventBilling)
                <div class="px-6 pb-6">
                    <p class="text-sm text-gray-600 leading-relaxed">{{ __('Billing status') }}: <strong class="text-gray-900">{{ $event->eventBilling->status?->value ? __($event->eventBilling->status->value) : __('—') }}</strong></p>
                </div>
            @endif
            @if(! empty($event->settings['description'] ?? null))
                <div class="px-6 pb-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Description') }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $event->settings['description'] }}</p>
                </div>
            @endif
            @php
                $addToCalendarUrl = $eventLinks->addToCalendarUrl($event);
                $navLinks = $eventLinks->navigationLinks($event);
            @endphp
            @if($addToCalendarUrl || count($navLinks) > 0)
                <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-2">
                    @if($addToCalendarUrl)
                        <a href="{{ $addToCalendarUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer">
                            <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-500" />
                            <span>{{ __('Add to calendar') }}</span>
                        </a>
                    @endif
                    @foreach($navLinks as $nav)
                        <a href="{{ $nav['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer">
                            <x-heroicon-o-map-pin class="h-5 w-5 text-gray-500" />
                            <span>{{ __($nav['label_key']) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            @if(count($event->customFields()) > 0)
                <div class="px-6 pb-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Additional info') }}</h3>
                    <dl class="space-y-1 text-sm">
                        @foreach($event->customFields() as $row)
                            <div><dt class="inline font-medium text-gray-700">{{ e($row['label']) }}:</dt> <dd class="inline text-gray-600">{{ e($row['value']) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </section>

        {{-- Payment --}}
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" aria-labelledby="payment-heading">
            <h2 id="payment-heading" class="text-lg font-medium text-gray-900 mb-3">{{ __('Payment') }}</h2>
            @if($event->eventBilling && $event->eventBilling->status?->value === 'paid')
                <p class="text-sm text-gray-600 leading-relaxed">{{ __('This event has been paid.') }}</p>
            @elseif($event->status->value === 'draft' || $event->status->value === 'pending_payment')
                @can('initiatePayment', $event)
                    <a href="{{ route('checkout.tokenize', [$event->organization, $event]) }}" class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Proceed to payment') }}</a>
                @else
                    <p class="text-sm text-gray-600 leading-relaxed">{{ __('Only organization owners and admins can initiate payment.') }}</p>
                @endcan
            @else
                <p class="text-sm text-gray-600 leading-relaxed">{{ __('Payment is not applicable for this event status.') }}</p>
            @endif
        </section>

        {{-- Management cards: Guests, Tables, Invitations, Seat assignments --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4" aria-label="{{ __('Event management') }}">
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
                <a href="{{ route($card['route'], $event) }}" class="group block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:border-indigo-200 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">
                    <div class="px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">{{ $titles[$card['key']] }}</h2>
                        <span class="text-sm font-medium text-indigo-600 group-hover:text-indigo-700">{{ __('Manage') }}</span>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 leading-relaxed"><span class="font-semibold text-gray-900">{{ $card['count'] }}</span> {{ $label }}</p>
                    </div>
                </a>
            @endforeach
        </section>
    </div>
</x-layouts.app>
