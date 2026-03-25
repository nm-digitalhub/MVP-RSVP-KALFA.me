<x-layouts.enterprise-app>
    @can('manage-system')
    <x-slot:title>{{ __('System Dashboard') }}</x-slot:title>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('System Dashboard')"
        :subtitle="__('Global system overview')"
    />

    @livewire('system.dashboard')
</div>
@else
<div class="p-8 text-center">
    <p class="text-red-500 font-bold">{{ __('Unauthorized access.') }}</p>
</div>
@endcan
</x-layouts.enterprise-app>
