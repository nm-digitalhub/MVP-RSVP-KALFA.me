<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Tables') }} — {{ $event->name }}</x-slot:title>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Tables')"
        :subtitle="$event->name"
    />
    @if($event->eventTables->isNotEmpty())
        <p class="mt-1 text-sm text-content-muted">
            <a href="{{ route('dashboard.events.seat-assignments.index', $event) }}" class="interactive font-medium text-brand hover:text-brand-hover focus-ring rounded">
                {{ __('Assign guests to tables') }}
            </a>
        </p>
    @endif

    <livewire:dashboard.event-tables :event="$event" />
</div>
</x-layouts.enterprise-app>
