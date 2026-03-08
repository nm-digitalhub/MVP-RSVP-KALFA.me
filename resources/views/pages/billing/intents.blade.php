<x-layouts.app>
    <x-slot:title>{{ __('Billing intents') }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Billing intents')"
            :subtitle="__('Purchase intents (read-only)')"
        />
    </x-slot:header>

    <livewire:billing.billing-intents-index />
</x-layouts.app>
