<section>
    {{-- Header --}}
    <div class="section-header">
        <div class="icon-wrap bg-indigo-50 dark:bg-indigo-900/30">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div>
            <h2 class="section-title">
                {{ __('Profile Information') }}
            </h2>
            <p class="section-desc">
                {{ __("Update your account's profile information and email address.") }}
            </p>
        </div>
    </div>

    <form wire:submit="updateProfileInformation" class="space-y-5">
        {{-- Name --}}
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <div class="relative mt-1">
                <div class="field-icon">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <x-text-input
                    wire:model="name"
                    id="name"
                    name="name"
                    type="text"
                    class="block w-full ps-9"
                    required
                    autofocus
                    autocomplete="name"
                />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative mt-1">
                <div class="field-icon">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <x-text-input
                    wire:model="email"
                    id="email"
                    name="email"
                    type="email"
                    class="block w-full ps-9"
                    required
                    autocomplete="username"
                />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2 flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="text-amber-800 dark:text-amber-300">{{ __('Your email address is unverified.') }}</p>
                        <button wire:click.prevent="sendVerification" type="button" class="mt-0.5 text-amber-700 dark:text-amber-400 underline hover:text-amber-900 dark:hover:text-amber-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/50 focus-visible:rounded transition-colors">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </div>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div class="form-footer">
            <x-primary-button class="data-loading:opacity-75 data-loading:cursor-wait inline-flex items-center gap-2">
                <svg wire:loading wire:target="updateProfileInformation" class="animate-spin motion-reduce:animate-none h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ __('Save') }}
            </x-primary-button>
            <x-action-message on="profile-updated">
                <span class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('Saved.') }}
                </span>
            </x-action-message>
        </div>
    </form>
</section>
