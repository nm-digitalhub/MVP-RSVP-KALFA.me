<section>
    {{-- Header --}}
    <div class="section-header">
        <div class="icon-wrap bg-indigo-50 dark:bg-indigo-900/30">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div>
            <h2 class="section-title">
                {{ __('Update Password') }}
            </h2>
            <p class="section-desc">
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            </p>
        </div>
    </div>

    <form wire:submit="updatePassword" class="space-y-5">
        {{-- Current password --}}
        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="relative mt-1">
                <div class="field-icon">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                </div>
                <x-text-input
                    wire:model="current_password"
                    id="update_password_current_password"
                    name="current_password"
                    type="password"
                    class="block w-full ps-9"
                    autocomplete="current-password"
                />
            </div>
            <x-input-error :messages="$errors->get('current_password')" class="mt-1.5" />
        </div>

        <div class="border-t border-stroke pt-5 space-y-5">
            {{-- New password --}}
            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <div class="relative mt-1">
                    <div class="field-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <x-text-input
                        wire:model="password"
                        id="update_password_password"
                        name="password"
                        type="password"
                        class="block w-full ps-9"
                        autocomplete="new-password"
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            {{-- Confirm password --}}
            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <div class="relative mt-1">
                    <div class="field-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <x-text-input
                        wire:model="password_confirmation"
                        id="update_password_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="block w-full ps-9"
                        autocomplete="new-password"
                    />
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        {{-- Footer --}}
        <div class="form-footer">
            <x-primary-button class="data-loading:opacity-75 data-loading:cursor-wait inline-flex items-center gap-2">
                <svg wire:loading wire:target="updatePassword" class="animate-spin motion-reduce:animate-none h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ __('Save') }}
            </x-primary-button>
            <x-action-message on="password-updated">
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
