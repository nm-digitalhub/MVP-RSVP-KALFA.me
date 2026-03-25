<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Usage') }}</x-slot:title>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Usage')"
        :subtitle="__('Feature usage (read-only)')"
    />

    <livewire:billing.usage-index />
</div>
</x-layouts.enterprise-app>
