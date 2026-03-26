<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Entitlements') }}</x-slot:title>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Entitlements')"
        :subtitle="__('Feature grants for this account')"
    />

    <livewire:billing.entitlements-index />
</div>
</x-layouts.enterprise-app>
