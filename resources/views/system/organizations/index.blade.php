<x-layouts.app>
    @can('manage-organizations')
    <x-slot:title>{{ __('System Organizations') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Organizations')"
            :subtitle="__('Manage all organizations')"
        />
    </x-slot:header>

    @livewire('system.organizations.index')
    @else
        <div class="p-8 text-center">
            <p class="text-red-500 font-bold">{{ __('Unauthorized access.') }}</p>
        </div>
    @endcan
</x-layouts.app>
