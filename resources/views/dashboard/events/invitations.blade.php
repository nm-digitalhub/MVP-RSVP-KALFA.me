<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Invitations') }} — {{ $event->name }}</x-slot:title>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Invitations')"
        :subtitle="$event->name"
    />

    <livewire:dashboard.event-invitations :event="$event" />
</div>
</x-layouts.enterprise-app>
