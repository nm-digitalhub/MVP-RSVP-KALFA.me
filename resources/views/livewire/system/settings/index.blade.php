<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: $wire.entangle('activeTab') }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-content">{{ __('System Settings') }}</h1>
    </div>

    @session('success')
        <flux:callout variant="success">
            <flux:callout.heading>{{ __('Success') }}</flux:callout.heading>
            <flux:callout.text>{{ $value }}</flux:callout.text>
        </flux:callout>
    @endsession

    @session('error')
        <flux:callout variant="danger">
            <flux:callout.heading>{{ __('Error') }}</flux:callout.heading>
            <flux:callout.text>{{ $value }}</flux:callout.text>
        </flux:callout>
    @endsession

    <div class="card overflow-hidden">
        {{-- Tab nav with Flux UI buttons --}}
        <div class="border-b border-stroke">
            <nav class="-mb-px flex gap-0 px-6" aria-label="{{ __('Settings tabs') }}">
                @foreach([['sumit', 'SUMIT'], ['twilio', 'Twilio'], ['gemini', 'Gemini']] as [$tab, $label])
                    <flux:button
                        wire:key="tab-btn-{{ $tab }}"
                        @click="activeTab = '{{ $tab }}'"
                        variant="subtle"
                        x-bind:class="activeTab === '{{ $tab }}' ? 'border-b-2 border-brand text-brand' : 'border-b-2 border-transparent text-content-muted hover:text-content hover:border-stroke/50'"
                        class="rounded-none px-4 py-4 font-medium text-sm transition-colors duration-200"
                    >
                        {{ $label }}
                    </flux:button>
                @endforeach
            </nav>
        </div>

        <div class="p-6">
            {{-- Sumit Tab --}}
            <div x-show="activeTab === 'sumit'" x-cloak wire:key="tab-content-sumit">
                <form wire:submit.prevent="saveSumit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Company ID --}}
                        <div>
                            <flux:field>
                                <flux:label for="sumit_company_id">Company ID</flux:label>
                                <flux:input
                                    id="sumit_company_id"
                                    wire:model="sumit_company_id"
                                    placeholder="Enter Company ID"
                                />
                                @error('sumit_company_id')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Environment --}}
                        <div>
                            <flux:field>
                                <flux:label for="sumit_environment">Environment</flux:label>
                                <flux:select
                                    id="sumit_environment"
                                    wire:model="sumit_environment"
                                >
                                    <option value="www">Production (www)</option>
                                    <option value="test">Test</option>
                                </flux:select>
                                @error('sumit_environment')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Private Key (with custom visibility toggle) --}}
                        <div class="md:col-span-2" x-data="{ showPassword: false }">
                            <flux:field>
                                <flux:label for="sumit_private_key">Private Key</flux:label>
                                <div class="relative">
                                    <flux:input
                                        id="sumit_private_key"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        wire:model="sumit_private_key"
                                        placeholder="Enter Private Key"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                @error('sumit_private_key')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Public Key --}}
                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label for="sumit_public_key">Public Key</flux:label>
                                <flux:input
                                    id="sumit_public_key"
                                    wire:model="sumit_public_key"
                                    placeholder="Enter Public Key"
                                />
                                @error('sumit_public_key')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Checkboxes --}}
                        <div class="md:col-span-2 flex items-center gap-6">
                            <flux:field>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <flux:checkbox wire:model="sumit_is_active" />
                                    <span class="text-sm text-content">{{ __('Active') }}</span>
                                </label>
                            </flux:field>
                            <flux:field>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <flux:checkbox wire:model="sumit_is_test_mode" />
                                    <span class="text-sm text-content">{{ __('Test Mode') }}</span>
                                </label>
                            </flux:field>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <x-slot:icon wire:loading wire:target="saveSumit">
                                <svg class="animate-spin motion-reduce:animate-none w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </x-slot:icon>
                            {{ __('Save Sumit Settings') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            {{-- Twilio Tab --}}
            <div x-show="activeTab === 'twilio'" x-cloak wire:key="tab-content-twilio">
                <form wire:submit.prevent="saveTwilio" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Account SID --}}
                        <div>
                            <flux:field>
                                <flux:label for="twilio_sid">Account SID</flux:label>
                                <flux:input
                                    id="twilio_sid"
                                    wire:model="twilio_sid"
                                    placeholder="Enter Account SID"
                                />
                                @error('twilio_sid')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Auth Token (with custom visibility toggle) --}}
                        <div x-data="{ showPassword: false }">
                            <flux:field>
                                <flux:label for="twilio_token">Auth Token</flux:label>
                                <div class="relative">
                                    <flux:input
                                        id="twilio_token"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        wire:model="twilio_token"
                                        placeholder="Enter Auth Token"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                @error('twilio_token')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Twilio Number --}}
                        <div>
                            <flux:field>
                                <flux:label for="twilio_number">Twilio Number</flux:label>
                                <flux:input
                                    id="twilio_number"
                                    wire:model="twilio_number"
                                    placeholder="+1234567890"
                                />
                                @error('twilio_number')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Messaging Service SID --}}
                        <div>
                            <flux:field>
                                <flux:label for="twilio_messaging_service_sid">Messaging Service SID</flux:label>
                                <flux:input
                                    id="twilio_messaging_service_sid"
                                    wire:model="twilio_messaging_service_sid"
                                    placeholder="Enter Messaging Service SID"
                                />
                                @error('twilio_messaging_service_sid')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Verify SID --}}
                        <div>
                            <flux:field>
                                <flux:label for="twilio_verify_sid">Verify SID</flux:label>
                                <flux:input
                                    id="twilio_verify_sid"
                                    wire:model="twilio_verify_sid"
                                    placeholder="Enter Verify SID"
                                />
                                @error('twilio_verify_sid')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- API Key & Secret --}}
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:field>
                                    <flux:label for="twilio_api_key">API Key</flux:label>
                                    <flux:input
                                        id="twilio_api_key"
                                        wire:model="twilio_api_key"
                                        placeholder="Enter API Key"
                                    />
                                    @error('twilio_api_key')
                                        <flux:description class="text-danger">{{ $message }}</flux:description>
                                    @enderror
                                </flux:field>
                            </div>
                            <div x-data="{ showPassword: false }">
                                <flux:field>
                                    <flux:label for="twilio_api_secret">API Secret</flux:label>
                                    <div class="relative">
                                        <flux:input
                                            id="twilio_api_secret"
                                            x-bind:type="showPassword ? 'text' : 'password'"
                                            wire:model="twilio_api_secret"
                                            placeholder="Enter API Secret"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                            x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        >
                                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('twilio_api_secret')
                                        <flux:description class="text-danger">{{ $message }}</flux:description>
                                    @enderror
                                </flux:field>
                            </div>
                        </div>

                        {{-- Active Checkbox --}}
                        <div class="md:col-span-2">
                            <flux:field>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <flux:checkbox wire:model="twilio_is_active" />
                                    <span class="text-sm text-content">{{ __('Active') }}</span>
                                </label>
                            </flux:field>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <x-slot:icon wire:loading wire:target="saveTwilio">
                                <svg class="animate-spin motion-reduce:animate-none w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </x-slot:icon>
                            {{ __('Save Twilio Settings') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            {{-- Gemini Tab --}}
            <div x-show="activeTab === 'gemini'" x-cloak wire:key="tab-content-gemini">
                <form wire:submit.prevent="saveGemini" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- API Key (with custom visibility toggle) --}}
                        <div class="md:col-span-2" x-data="{ showPassword: false }">
                            <flux:field>
                                <flux:label for="gemini_api_key">API Key</flux:label>
                                <div class="relative">
                                    <flux:input
                                        id="gemini_api_key"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        wire:model="gemini_api_key"
                                        placeholder="Enter Gemini API Key"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                @error('gemini_api_key')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Model --}}
                        <div>
                            <flux:field>
                                <flux:label for="gemini_model">Model</flux:label>
                                <flux:input
                                    id="gemini_model"
                                    wire:model="gemini_model"
                                    placeholder="models/gemini-2.0-flash-exp"
                                />
                                @error('gemini_model')
                                    <flux:description class="text-danger">{{ $message }}</flux:description>
                                @enderror
                            </flux:field>
                        </div>

                        {{-- Active Checkbox --}}
                        <div class="flex items-end">
                            <flux:field>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <flux:checkbox wire:model="gemini_is_active" />
                                    <span class="text-sm text-content">{{ __('Active') }}</span>
                                </label>
                            </flux:field>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <x-slot:icon wire:loading wire:target="saveGemini">
                                <svg class="animate-spin motion-reduce:animate-none w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </x-slot:icon>
                            {{ __('Save Gemini Settings') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
