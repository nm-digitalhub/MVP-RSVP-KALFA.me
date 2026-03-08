<x-layouts.app>
    <x-slot:title>{{ __('Entitlements') }}</x-slot:title>
    <x-slot:containerWidth>max-w-4xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Entitlements')"
            :subtitle="__('Feature grants for this account')"
        />
    </x-slot:header>

    <livewire:billing.entitlements-index />
</x-layouts.app>
