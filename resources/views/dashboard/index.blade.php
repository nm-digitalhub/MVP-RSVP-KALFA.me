<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Dashboard') }}</x-slot:title>

<div class="no-main-spacing min-h-screen bg-surface py-6 sm:py-8 px-3 sm:px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Page Header --}}
        <div class="mb-5 sm:mb-6">
            @session('success')
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-3 text-sm text-emerald-900 ring-1 ring-inset ring-emerald-200/50" role="status" aria-live="polite">{{ $value }}</div>
            @endsession
            <h1 class="text-xl sm:text-2xl font-semibold text-content">{{ $organization->name }}</h1>
            <p class="mt-1 text-sm text-content-muted">{{ __('Dashboard') }}</p>
        </div>

        {{-- EventList Livewire Component --}}
        @livewire('dashboard.events')
    </div>
</div>
</x-layouts.enterprise-app>
