<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Organization settings') }}</x-slot:title>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Organization settings')"
        :subtitle="$organization->name"
    />

    <div class="mt-4 sm:mt-6 card overflow-hidden">
        <form action="{{ route('dashboard.organization-settings.update') }}" method="POST" class="p-4 sm:p-6 space-y-4 sm:space-y-5">
            @csrf
            @method('PUT')
            <div>
                <x-input-label for="name" :value="__('Organization name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $organization->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="billing_email" :value="__('Billing email')" />
                <x-text-input id="billing_email" name="billing_email" type="email" class="mt-1 block w-full" :value="old('billing_email', $organization->billing_email)" />
                <x-input-error :messages="$errors->get('billing_email')" class="mt-1" />
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button type="submit">{{ __('Update') }}</x-primary-button>
                <a href="{{ route('organizations.index') }}" class="btn btn-secondary focus-ring">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
</x-layouts.enterprise-app>
