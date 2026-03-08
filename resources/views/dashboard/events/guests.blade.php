<x-layouts.app>
    <x-slot:title>{{ __('Guests') }} — {{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Guests')"
            :subtitle="$event->name"
        />
    </x-slot:header>

    <livewire:dashboard.event-guests :event="$event" />
</x-layouts.app>
