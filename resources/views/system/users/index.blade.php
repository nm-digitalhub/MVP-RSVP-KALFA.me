<x-layouts.enterprise-app>
    @can('manage-users')
    <x-slot:title>{{ __('System Users') }}</x-slot:title>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('System Users')"
        :subtitle="__('Manage all users')"
    />

    @livewire('system.users.index')
</div>
@else
<div class="p-8 text-center">
    <p class="text-red-500 font-bold">{{ __('Unauthorized access.') }}</p>
</div>
@endcan
</x-layouts.enterprise-app>
