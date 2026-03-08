<x-layouts.app>
    <x-slot:title>{{ __('Create organization') }}</x-slot:title>
    <x-slot:containerWidth>max-w-lg</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Create organization')"
            :subtitle="__('Add a new organization to get started.')"
        />
    </x-slot:header>

    <livewire:organizations.create />
</x-layouts.app>
