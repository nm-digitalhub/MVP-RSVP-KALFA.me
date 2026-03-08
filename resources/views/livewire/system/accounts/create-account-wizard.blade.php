<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 min-w-0 text-start" role="main" aria-label="{{ __('Create account wizard') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
    <div class="mb-6">
        <a href="{{ route('system.accounts.index') }}" class="inline-flex items-center gap-1 min-h-[44px] px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2" aria-label="{{ __('Back to accounts') }}">
            @if(isRTL()){{ __('Back to accounts') }} &rarr;@else&larr; {{ __('Back to accounts') }}@endif
        </a>
    </div>

    {{-- Step indicator: RTL reverses order (3←2←1), LTR (1→2→3) --}}
    <div class="mb-8 overflow-x-auto">
        <ul class="flex items-center justify-between min-w-[280px] {{ isRTL() ? 'flex-row-reverse' : 'flex-row' }}" aria-label="{{ __('Steps') }}">
            @for ($i = 1; $i <= $totalSteps; $i++)
                <li class="flex-1 flex items-center min-w-0" wire:key="step-indicator-{{ $i }}">
                    <div class="flex items-center w-full">
                        <div class="flex flex-col items-center">
                            <span class="w-10 h-10 flex items-center justify-center rounded-full font-semibold transition-colors duration-300
                                {{ $step >= $i ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                {{ $i }}
                            </span>
                            <span class="text-xs mt-2 text-gray-600 text-center">
                                @if($i == 1) {{ __('Account holder details') }}
                                @elseif($i == 2) {{ __('Owner') }}
                                @else {{ __('Review & create') }}
                                @endif
                            </span>
                        </div>
                        @if ($i < $totalSteps)
                            <div class="flex-1 h-1 ms-2 me-2 sm:ms-4 sm:me-4 rounded min-w-[8px]
                                {{ $step > $i ? 'bg-indigo-600' : 'bg-gray-200' }}
                                transition-colors duration-300">
                            </div>
                        @endif
                    </div>
                </li>
            @endfor
        </ul>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[320px]">
        @if ($step === 1)
            <div wire:transition="step">
                <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Account holder details') }}</h2>
                <div class="space-y-4">
                    <div>
                        <x-input-label for="wizard-type" :value="__('Customer Type')" class="text-sm font-medium text-gray-700" />
                        <select id="wizard-type" wire:model.live="type" dir="{{ isRTL() ? 'rtl' : 'ltr' }}" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-start shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                            <option value="organization">{{ __('Organization') }}</option>
                            <option value="individual">{{ __('Individual') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="wizard-name" :value="__('Name')" class="text-sm font-medium text-gray-700" />
                        <x-text-input id="wizard-name" type="text" wire:model.defer="name" class="mt-1 block w-full min-h-[44px] text-start" placeholder="{{ __('Account display name') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                </div>
            </div>
        @endif

        @if ($step === 2)
            <div wire:transition="step">
                <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Owner (optional)') }}</h2>
                <div class="space-y-4">
                    <div>
                        <x-input-label for="wizard-owner" :value="__('Owner user')" class="text-sm font-medium text-gray-700" />
                        <select id="wizard-owner" wire:model.defer="owner_user_id" dir="{{ isRTL() ? 'rtl' : 'ltr' }}" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-start shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" wire:key="user-{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('owner_user_id')" class="mt-1" />
                    </div>
                </div>
            </div>
        @endif

        @if ($step === 3)
            <div wire:transition="step">
                <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Review & create') }}</h2>
                @if($type === 'organization' && $organizationsWithoutAccount->isNotEmpty())
                    <div class="mb-6">
                        <x-input-label for="wizard-attach-org" :value="__('Attach to organization (optional)')" class="text-sm font-medium text-gray-700" />
                        <select id="wizard-attach-org" wire:model.defer="attach_organization_id" dir="{{ isRTL() ? 'rtl' : 'ltr' }}" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-start shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach($organizationsWithoutAccount as $org)
                                <option value="{{ $org->id }}" wire:key="org-{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Organizations without an account. Attaching links this account to the organization.') }}</p>
                        <x-input-error :messages="$errors->get('attach_organization_id')" class="mt-1" />
                    </div>
                @endif
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
                    <p class="text-indigo-800 text-sm">{{ __('Please review before creating.') }}</p>
                </div>
                <dl class="space-y-3">
                    <div class="flex justify-between gap-4 py-2 border-b border-gray-100 text-start">
                        <dt class="text-sm text-gray-500">{{ __('Customer Type') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $type === 'organization' ? __('Organization') : __('Individual') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2 border-b border-gray-100 text-start">
                        <dt class="text-sm text-gray-500">{{ __('Name') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $name ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2 border-b border-gray-100 text-start">
                        <dt class="text-sm text-gray-500">{{ __('Owner') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">
                            @if($owner_user_id)
                                @php $owner = $users->firstWhere('id', (int) $owner_user_id); @endphp
                                {{ $owner?->name ?? $owner_user_id }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if($type === 'organization' && $attach_organization_id)
                        <div class="flex justify-between gap-4 py-2 border-b border-gray-100 text-start">
                            <dt class="text-sm text-gray-500">{{ __('Attach organization') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                @php $org = $organizationsWithoutAccount->firstWhere('id', (int) $attach_organization_id); @endphp
                                {{ $org?->name ?? $attach_organization_id }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>

    {{-- Buttons: RTL = Next | Previous, LTR = Previous | Next --}}
    <div class="flex justify-between items-center mt-6 gap-4 {{ isRTL() ? 'flex-row-reverse' : '' }}">
        <button type="button" wire:click="previousStep"
            {{ $step === 1 ? 'disabled' : '' }}
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50"
            class="min-h-[44px] px-6 py-2.5 rounded-lg text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
            {{ __('Previous') }}
        </button>
        <button type="button" wire:click="nextStep"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50"
            class="min-h-[44px] px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2
                {{ $step === $totalSteps ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700' }}">
            <span wire:loading.remove>
                {{ $step === $totalSteps ? __('Create account') : __('Next') }}
            </span>
            <span wire:loading>{{ __('Processing...') }}</span>
        </button>
    </div>
</div>

{{-- View Transitions API: directional step animation; RTL-aware (respects prefers-reduced-motion) --}}
<style>
@media (prefers-reduced-motion: no-preference) {
    /* LTR: next = slide left out, new from right; back = opposite */
    html:active-view-transition-type(forward) {
        &::view-transition-old(step) { animation: 300ms ease-out both slide-out-left; }
        &::view-transition-new(step) { animation: 300ms ease-in both slide-in-right; }
    }
    html:active-view-transition-type(backward) {
        &::view-transition-old(step) { animation: 300ms ease-out both slide-out-right; }
        &::view-transition-new(step) { animation: 300ms ease-in both slide-in-left; }
    }
    /* RTL: next = slide right out, new from left; back = opposite */
    [dir="rtl"] html:active-view-transition-type(forward) {
        &::view-transition-old(step) { animation: 300ms ease-out both slide-out-right; }
        &::view-transition-new(step) { animation: 300ms ease-in both slide-in-left; }
    }
    [dir="rtl"] html:active-view-transition-type(backward) {
        &::view-transition-old(step) { animation: 300ms ease-out both slide-out-left; }
        &::view-transition-new(step) { animation: 300ms ease-in both slide-in-right; }
    }
}
@keyframes slide-out-left { from { transform: translateX(0); opacity: 1; } to { transform: translateX(-100%); opacity: 0; } }
@keyframes slide-in-right { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slide-out-right { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
@keyframes slide-in-left { from { transform: translateX(-100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
