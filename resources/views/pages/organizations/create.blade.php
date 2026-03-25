<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Create organization') }}</x-slot:title>

<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Create organization')"
        :subtitle="__('Add a new organization to get started.')"
    />

    <livewire:organizations.create />
</div>
</x-layouts.enterprise-app>
