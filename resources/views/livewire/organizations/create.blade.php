<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
    <div class="bg-white/95 rounded-2xl shadow-xl shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
    <div class="p-8">
        <div class="mb-8 flex items-center gap-4">
            <div class="flex size-14 items-center justify-center rounded-3xl bg-brand/5 ring-1 ring-brand/10">
                <x-kalfa-app-icon class="h-9 w-9" alt="" />
            </div>
            <div>
                <h1 class="text-2xl font-black tracking-tight text-content">{{ __('Create organization') }}</h1>
                <p class="text-sm text-content-muted">{{ __('Set up a new workspace under the :app brand.', ['app' => config('app.name')]) }}</p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div>
                <x-input-label for="org-name" :value="__('Organization name')" class="text-sm font-semibold text-gray-700" />
                <x-text-input id="org-name" type="text" wire:model="name" class="mt-2" autocomplete="organization" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <x-primary-button type="submit" class="inline-flex gap-2" aria-label="{{ __('Create organization') }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Create') }}
                </x-primary-button>
                <a href="{{ route('organizations.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-colors duration-200" aria-label="{{ __('Cancel') }}">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
    </div>
</div>
