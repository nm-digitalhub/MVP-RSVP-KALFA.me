<x-layouts.app>
    <x-slot:title>{{ __('System Users') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Users')"
            :subtitle="__('Manage all users')"
        />
    </x-slot:header>

    @livewire('system.users.index')
</x-layouts.app>
