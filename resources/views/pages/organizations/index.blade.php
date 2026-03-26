<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Organizations') }}</x-slot:title>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Organizations')"
        :subtitle="__('Manage and switch your organizations')"
    />

    <livewire:organizations.index />
</div>
</x-layouts.enterprise-app>
