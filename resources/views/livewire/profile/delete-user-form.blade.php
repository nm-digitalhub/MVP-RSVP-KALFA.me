<section>
    {{-- Danger zone header --}}
    <div class="section-header">
        <div class="icon-wrap bg-red-50 dark:bg-red-900/30">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <div>
            <h2 class="section-title">
                {{ __('Delete Account') }}
            </h2>
            <p class="section-desc">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>
        </div>
    </div>

    {{-- Danger zone box --}}
    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10 p-4 flex items-center justify-between gap-4">
        <p class="text-sm text-red-700 dark:text-red-400">
            פעולה זו בלתי הפיכה. כל הנתונים יימחקו לצמיתות.
        </p>
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="shrink-0"
        >{{ __('Delete Account') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="icon-wrap bg-red-100 dark:bg-red-900/40">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h2 class="section-title">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <flux:field>
                <flux:label for="password" class="sr-only">{{ __('Password') }}</flux:label>
                <flux:input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Password') }}"
                    icon="lock-closed"
                    class="mt-1"
                />
                <flux:error name="password" class="mt-2" />
            </flux:field>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" type="button">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-danger-button class="data-loading:opacity-75 data-loading:cursor-wait inline-flex items-center gap-2">
                    <svg wire:loading wire:target="deleteUser" class="animate-spin motion-reduce:animate-none h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
