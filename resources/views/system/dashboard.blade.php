<x-layouts.app>
    <x-slot:title>{{ __('System Dashboard') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Dashboard')"
            :subtitle="__('Global system overview')"
        />
    </x-slot:header>

    @livewire('system.dashboard')
</x-layouts.app>
