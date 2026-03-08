<x-layouts.app>
    <x-slot:title>{{ __('System Organizations') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Organizations')"
            :subtitle="__('Manage all organizations')"
        />
    </x-slot:header>

    @livewire('system.organizations.index')
</x-layouts.app>
