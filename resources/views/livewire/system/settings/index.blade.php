<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: @entangle('activeTab') }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-content">{{ __('System Settings') }}</h1>
    </div>

    @session('success')
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm" role="alert">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ $value }}</span>
        </div>
    @endsession

    @session('error')
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm" role="alert">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ $value }}</span>
        </div>
    @endsession

    <div class="card overflow-hidden">
        {{-- Tab nav --}}
        <div class="border-b border-stroke">
            <nav class="-mb-px flex gap-0 px-6" aria-label="{{ __('Settings tabs') }}">
                @foreach([['sumit', 'SUMIT'], ['twilio', 'Twilio'], ['gemini', 'Gemini']] as [$tab, $label])
                <button wire:key="tab-btn-{{ $tab }}" @click="activeTab = '{{ $tab }}'" type="button"
                    :class="activeTab === '{{ $tab }}' ? 'border-brand text-brand' : 'border-transparent text-content-muted hover:text-content hover:border-stroke'"
                    class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand/50">
                    {{ $label }}
                </button>
                @endforeach
            </nav>
        </div>

        <div class="p-6">
            {{-- Sumit Tab --}}
            <div x-show="activeTab === 'sumit'" x-cloak wire:key="tab-content-sumit">
                <form wire:submit.prevent="saveSumit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Company ID" />
                            <input type="text" wire:model="sumit_company_id" class="input-base mt-1">
                            @error('sumit_company_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Environment" />
                            <select wire:model="sumit_environment" class="input-base mt-1">
                                <option value="www">Production (www)</option>
                                <option value="test">Test</option>
                            </select>
                            @error('sumit_environment') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label value="Private Key" />
                            <input type="password" wire:model="sumit_private_key" class="input-base mt-1">
                            @error('sumit_private_key') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label value="Public Key" />
                            <input type="text" wire:model="sumit_public_key" class="input-base mt-1">
                            @error('sumit_public_key') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="sumit_is_active" class="rounded border-stroke text-brand focus-visible:ring-brand/30">
                                <span class="text-sm text-content">{{ __('Active') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="sumit_is_test_mode" class="rounded border-stroke text-brand focus-visible:ring-brand/30">
                                <span class="text-sm text-content">{{ __('Test Mode') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary btn-sm">
                            <svg wire:loading wire:target="saveSumit" class="animate-spin motion-reduce:animate-none w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Save Sumit Settings') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Twilio Tab --}}
            <div x-show="activeTab === 'twilio'" x-cloak wire:key="tab-content-twilio">
                <form wire:submit.prevent="saveTwilio" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Account SID" />
                            <input type="text" wire:model="twilio_sid" class="input-base mt-1">
                            @error('twilio_sid') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Auth Token" />
                            <input type="password" wire:model="twilio_token" class="input-base mt-1">
                            @error('twilio_token') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Twilio Number" />
                            <input type="text" wire:model="twilio_number" class="input-base mt-1">
                            @error('twilio_number') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Messaging Service SID" />
                            <input type="text" wire:model="twilio_messaging_service_sid" class="input-base mt-1">
                            @error('twilio_messaging_service_sid') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Verify SID" />
                            <input type="text" wire:model="twilio_verify_sid" class="input-base mt-1">
                            @error('twilio_verify_sid') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="API Key" />
                                <input type="text" wire:model="twilio_api_key" class="input-base mt-1">
                                @error('twilio_api_key') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label value="API Secret" />
                                <input type="password" wire:model="twilio_api_secret" class="input-base mt-1">
                                @error('twilio_api_secret') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="twilio_is_active" class="rounded border-stroke text-brand focus-visible:ring-brand/30">
                                <span class="text-sm text-content">{{ __('Active') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary btn-sm">
                            <svg wire:loading wire:target="saveTwilio" class="animate-spin motion-reduce:animate-none w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Save Twilio Settings') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Gemini Tab --}}
            <div x-show="activeTab === 'gemini'" x-cloak wire:key="tab-content-gemini">
                <form wire:submit.prevent="saveGemini" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-input-label value="API Key" />
                            <input type="password" wire:model="gemini_api_key" class="input-base mt-1">
                            @error('gemini_api_key') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label value="Model" />
                            <input type="text" wire:model="gemini_model" class="input-base mt-1">
                            @error('gemini_model') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="gemini_is_active" class="rounded border-stroke text-brand focus-visible:ring-brand/30">
                                <span class="text-sm text-content">{{ __('Active') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary btn-sm">
                            <svg wire:loading wire:target="saveGemini" class="animate-spin motion-reduce:animate-none w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Save Gemini Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
