<x-layouts.app>
    @can('manage-users')
    <x-slot:title>{{ __('System Users') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Users')"
            :subtitle="__('Manage all users')"
        />
    </x-slot:header>

    @livewire('system.users.index')
    @else
        <div class="p-8 text-center">
            <p class="text-red-500 font-bold">{{ __('Unauthorized access.') }}</p>
        </div>
    @endcan
</x-layouts.app>
