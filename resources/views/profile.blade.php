<x-layouts.app>
    <x-slot:title>{{ __('Profile') }}</x-slot:title>
    <x-slot:containerWidth>max-w-3xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Profile')"
            :subtitle="__('Manage your account settings')"
        />
    </x-slot:header>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-profile-information-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-password-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.delete-user-form')
        </div>
    </div>
</x-layouts.app>
