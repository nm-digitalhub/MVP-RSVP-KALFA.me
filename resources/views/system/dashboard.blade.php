<x-layouts.app>
    @can('manage-system')
    <x-slot:title>{{ __('System Dashboard') }}</x-slot:title>
    <x-slot:containerWidth>max-w-7xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('System Dashboard')"
            :subtitle="__('Global system overview')"
        />
    </x-slot:header>

    @livewire('system.dashboard')
    @else
        <div class="p-8 text-center">
            <p class="text-red-500 font-bold">{{ __('Unauthorized access.') }}</p>
        </div>
    @endcan
</x-layouts.app>
