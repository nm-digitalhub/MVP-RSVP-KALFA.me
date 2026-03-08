<x-layouts.app>
    <x-slot:title>{{ __('Billing & Entitlements') }}</x-slot:title>
    <x-slot:containerWidth>max-w-3xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Billing & Entitlements')"
            :subtitle="__('Account overview for current organization')"
        />
    </x-slot:header>

    <livewire:billing.account-overview />
</x-layouts.app>
