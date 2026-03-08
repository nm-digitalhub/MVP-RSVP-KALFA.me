<x-layouts.app>
    <x-slot:title>{{ __('Dashboard') }}</x-slot:title>

<div class="min-h-screen bg-gray-50 py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <h1 class="text-2xl font-semibold text-gray-900">{{ $organization->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Dashboard') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Events') }}</h2>
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
                            <tr>
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
                                        {{ $event->status?->value ? __($event->status->value) : __('—') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $event->guests_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('dashboard.events.show', $event) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
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
    </div>
</div>
</x-layouts.app>
