<x-layouts.app>
    <x-slot:title>{{ __('Tables') }} — {{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-3xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Tables')"
            :subtitle="$event->name"
        />
        @if($event->eventTables->isNotEmpty())
            <p class="mt-1 text-sm text-gray-500">
                <a href="{{ route('dashboard.events.seat-assignments.index', $event) }}" class="font-medium text-brand hover:text-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 rounded">
                    {{ __('Assign guests to tables') }}
                </a>
            </p>
        @endif
    </x-slot:header>

    <livewire:dashboard.event-tables :event="$event" />
</x-layouts.app>
