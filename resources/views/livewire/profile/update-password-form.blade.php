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
        <flux:field>
            <flux:label for="update_password_current_password">{{ __('Current Password') }}</flux:label>
            <flux:input
                wire:model="current_password"
                id="update_password_current_password"
                name="current_password"
                type="password"
                icon="lock-closed"
                autocomplete="current-password"
                class="mt-1"
            />
            <flux:error name="current_password" class="mt-1" />
        </flux:field>

        <div class="border-t border-stroke pt-5 space-y-5">
            <flux:field>
                <flux:label for="update_password_password">{{ __('New Password') }}</flux:label>
                <flux:input
                    wire:model="password"
                    id="update_password_password"
                    name="password"
                    type="password"
                    icon="lock-closed"
                    autocomplete="new-password"
                    class="mt-1"
                />
                <flux:error name="password" class="mt-1" />
            </flux:field>

            <flux:field>
                <flux:label for="update_password_password_confirmation">{{ __('Confirm Password') }}</flux:label>
                <flux:input
                    wire:model="password_confirmation"
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    icon="lock-closed"
                    autocomplete="new-password"
                    class="mt-1"
                />
                <flux:error name="password_confirmation" class="mt-1" />
            </flux:field>
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
