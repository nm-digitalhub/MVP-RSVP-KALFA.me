<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Profile') }}</x-slot:title>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Profile')"
        :subtitle="__('Manage your account settings')"
    />


    @php
        $profileUser = auth()->user();
        $memberSince = $profileUser->created_at
            ? $profileUser->created_at->locale(app()->getLocale())->translatedFormat('F j, Y')
            : __('Unknown');
        $lastActive = $profileUser->updated_at
            ? $profileUser->updated_at->locale(app()->getLocale())->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW, 'short' => false])
            : __('Just now');
        $isVerified = $profileUser->hasVerifiedEmail();
    @endphp

    <flux:callout
        icon="sparkles"
        variant="filled"
        color="white"
        dir="{{ isRTL() ? 'rtl' : 'ltr' }}"
        class="mt-6"
    >
        <flux:callout.heading>{{ __('Profile snapshot') }}</flux:callout.heading>

        <flux:callout.text>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-content-muted">{{ __('Member since') }}</p>
                    <p class="text-base font-semibold text-content mt-1">{{ $memberSince }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-content-muted">{{ __('Email') }}</p>
                    <p class="text-base font-semibold text-content mt-1">{{ $profileUser->email }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-content-muted">{{ __('Last update') }}</p>
                    <p class="text-base font-semibold text-content mt-1">{{ $lastActive }}</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3 items-center">
                <flux:badge color="{{ $isVerified ? 'emerald' : 'amber' }}" variant="solid">
                    {{ $isVerified ? __('Email verified') : __('Email needs verification') }}
                </flux:badge>

                <flux:badge color="sky" variant="solid">
                    {{ __('Active member') }}
                </flux:badge>
            </div>
        </flux:callout.text>

        <div class="mt-4 flex flex-wrap gap-3" data-slot="actions">
            <flux:button href="{{ route('dashboard') }}" variant="outline" size="sm" icon="shield-check">
                {{ __('Security center') }}
            </flux:button>
            <flux:button href="{{ route('profile') }}" variant="primary" size="sm" icon="sparkles">
                {{ __('Refresh profile view') }}
            </flux:button>
        </div>
    </flux:callout>

    <div class="mt-4 sm:mt-6 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-profile-information-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-password-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.manage-passkeys')
        </div>
        <div class="bg-card rounded-xl shadow-sm border border-stroke p-6">
            @livewire('profile.delete-user-form')
        </div>
    </div>
</div>
</x-layouts.enterprise-app>
