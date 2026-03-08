<x-layouts.app>
    <x-slot:title>{{ __('Organization settings') }}</x-slot:title>
    <x-slot:containerWidth>max-w-2xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Organization settings')"
            :subtitle="$organization->name"
        />
    </x-slot:header>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('dashboard.organization-settings.update') }}" method="POST" class="p-6 space-y-4">
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
                <a href="{{ route('organizations.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-layouts.app>
