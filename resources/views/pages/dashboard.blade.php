<x-layouts.app>
    <x-slot:title>{{ __('Dashboard') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Dashboard')"
            :subtitle="__('Organization overview and performance')"
        />
    </x-slot:header>

    <livewire:dashboard />
</x-layouts.app>
