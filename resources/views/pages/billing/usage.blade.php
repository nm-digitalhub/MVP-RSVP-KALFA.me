<x-layouts.app>
    <x-slot:title>{{ __('Usage') }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Usage')"
            :subtitle="__('Feature usage (read-only)')"
        />
    </x-slot:header>

    <livewire:billing.usage-index />
</x-layouts.app>
