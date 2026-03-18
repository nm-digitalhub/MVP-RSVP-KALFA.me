<div>
    @if($organization)
        <div class="mb-8 flex flex-col gap-4 rounded-[2rem] border border-gray-200/70 bg-white/95 p-6 shadow-lg shadow-gray-900/5 backdrop-blur-sm lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-3xl bg-brand/5 ring-1 ring-brand/10">
                    <x-kalfa-app-icon class="h-9 w-9" alt="" />
                </div>
                <div class="space-y-2">
                    <x-kalfa-wordmark class="justify-start" />
                    <p class="text-sm text-gray-500">{{ __('Your event workspace for :organization.', ['organization' => $organization->name]) }}</p>
                </div>
            </div>
        </div>

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
                        <span class="block text-sm font-normal text-gray-500">{{ $upcomingEvent->event_date?->format('d.m.Y') }}</span>
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
                <a href="{{ route('dashboard.events.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-brand hover:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Create event') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Title') }}</th>
                                <th scope="col" class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Event status') }}</th>
                                <th scope="col" class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Guests count') }}</th>
                                <th scope="col" class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($events as $event)
                                <tr wire:key="event-{{ $event->id }}">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $event->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $event->event_date?->format('d.m.Y') }}</td>
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
                                            {{ $event->status?->label() ?? __('—') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $event->guests_count ?? 0 }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('dashboard.events.show', [$organization, $event]) }}" class="inline-flex items-center min-h-[44px] text-brand hover:text-indigo-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 rounded transition-colors duration-200 cursor-pointer">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                        <x-kalfa-app-icon class="mx-auto mb-3 h-10 w-10 opacity-60" alt="" />
                                        <span class="block">{{ __('No events yet.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-[2rem] border border-gray-200/70 bg-white/95 p-8 text-center shadow-lg shadow-gray-900/5 backdrop-blur-sm">
            <x-kalfa-wordmark class="mb-5" />
            <p class="mx-auto max-w-2xl text-sm leading-6 text-gray-500">{{ __('Choose or create an organization to start managing events, guests, and invitations in one place.') }}</p>
            <div class="mt-6">
                <a href="{{ route('organizations.index') }}" wire:navigate class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-brand px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand/20 transition-all hover:bg-brand-hover">
                    {{ __('Go to organizations') }}
                </a>
            </div>
        </div>
    @endif
</div>
