<div>
    @if($organization)
        {{-- SaaS metric cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('Total Events') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalEvents ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('Total Guests') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalGuests ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('Upcoming Event') }}</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">
                    @if($upcomingEvent ?? null)
                        {{ $upcomingEvent->name }}
                        <span class="block text-sm font-normal text-gray-500">{{ $upcomingEvent->event_date?->format('M j, Y') }}</span>
                    @else
                        <span class="text-gray-500">{{ __('None scheduled') }}</span>
                    @endif
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('Organization') }}</p>
                <p class="mt-1">
                    @if($organizationStatusBadge ?? null)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('Active') }}</span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Events') }}</h2>
                <a href="{{ route('dashboard.events.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Create event') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Title') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Event status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Guests count') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($events as $event)
                                <tr wire:key="event-{{ $event->id }}">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $event->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $event->event_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            @switch($event->status->value ?? '')
                                                @case('draft') bg-gray-100 text-gray-800 @break
                                                @case('pending_payment') bg-amber-100 text-amber-800 @break
                                                @case('active') bg-green-100 text-green-800 @break
                                                @case('locked') bg-blue-100 text-blue-800 @break
                                                @case('archived') bg-gray-100 text-gray-600 @break
                                                @case('cancelled') bg-red-100 text-red-800 @break
                                                @default bg-gray-100 text-gray-800
                                            @endswitch">
                                            {{ $event->status->value ?? __('—') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $event->guests_count ?? 0 }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('dashboard.events.show', [$organization, $event]) }}" class="inline-flex items-center min-h-[44px] text-indigo-600 hover:text-indigo-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 rounded transition-colors duration-200 cursor-pointer">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No events yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>
        </div>
        @endif
</div>
