<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Billing intents') }}</x-slot:title>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Billing intents')"
        :subtitle="__('Purchase intents (read-only)')"
    />

    <livewire:billing.billing-intents-index />
</div>
</x-layouts.enterprise-app>
