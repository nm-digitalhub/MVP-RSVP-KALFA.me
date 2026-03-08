<x-layouts.app>
    <x-slot:title>{{ __('Seat assignments') }} — {{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Seat assignments')"
            :subtitle="$event->name"
        />
    </x-slot:header>

    <livewire:dashboard.event-seat-assignments :event="$event" />
</x-layouts.app>
