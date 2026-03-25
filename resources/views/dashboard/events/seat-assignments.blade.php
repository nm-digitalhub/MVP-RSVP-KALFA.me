<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Seat assignments') }} — {{ $event->name }}</x-slot:title>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Seat assignments')"
        :subtitle="$event->name"
    />

    <livewire:dashboard.event-seat-assignments :event="$event" />
</div>
</x-layouts.enterprise-app>
