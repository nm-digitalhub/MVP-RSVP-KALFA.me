<x-layouts.app>
    <x-slot:title>{{ __('Invitations') }} — {{ $event->name }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Invitations')"
            :subtitle="$event->name"
        />
    </x-slot:header>

    <livewire:dashboard.event-invitations :event="$event" />
</x-layouts.app>
