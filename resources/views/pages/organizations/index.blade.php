<x-layouts.app>
    <x-slot:title>{{ __('Organizations') }}</x-slot:title>
    <x-slot:containerWidth>max-w-3xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Organizations')"
            :subtitle="__('Manage and switch your organizations')"
        />
    </x-slot:header>

    <livewire:organizations.index />
</x-layouts.app>
