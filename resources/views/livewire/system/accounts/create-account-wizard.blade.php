<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 min-w-0 text-start animate-in fade-in duration-700" role="main" aria-label="{{ __('Create Account Wizard') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
    
    {{-- Top Navigation --}}
    <div class="mb-10 flex items-center justify-between">
        <a href="{{ route('system.accounts.index') }}" class="group inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-content-muted hover:text-brand hover:bg-brand/5 transition-all">
            <x-heroicon-m-arrow-left class="size-4 group-hover:-translate-x-1 transition-transform rtl:rotate-180" />
            {{ __('Cancel & Exit') }}
        </a>
        <div class="text-xs font-black text-content-muted uppercase tracking-widest">{{ __('New Billing Entity') }}</div>
    </div>

    {{-- Modern Stepper --}}
    <div class="mb-12">
        <div class="relative flex items-center justify-between w-full">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-stroke rounded-full -z-10"></div>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand rounded-full -z-10 transition-all duration-500 ease-out" style="width: {{ ($step - 1) / ($totalSteps - 1) * 100 }}%"></div>
            
            @foreach(range(1, $totalSteps) as $i)
                <div class="flex flex-col items-center gap-2 relative group cursor-default">
                    <div class="size-10 rounded-full flex items-center justify-center text-sm font-black border-4 transition-all duration-500 {{ $step >= $i ? 'bg-brand border-brand text-white shadow-lg shadow-brand/30 scale-110' : 'bg-surface border-stroke text-content-muted' }}">
                        @if($step > $i)
                            <x-heroicon-m-check class="size-5" />
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <span class="absolute top-12 whitespace-nowrap text-[10px] font-bold uppercase tracking-wider transition-colors duration-300 {{ $step >= $i ? 'text-brand' : 'text-content-muted' }}">
                        @if($i == 1) {{ __('Entity Type') }}
                        @elseif($i == 2) {{ __('Ownership') }}
                        @else {{ __('Review') }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-card rounded-[2.5rem] shadow-2xl shadow-slate-900/10 border border-stroke p-8 sm:p-12 min-h-[400px] flex flex-col relative overflow-hidden">
        
        {{-- Step 1: Type & Identity --}}
        @if ($step === 1)
            <div wire:transition.out.opacity.duration.200ms.in.opacity.duration.300ms class="space-y-8">
                <div class="text-center sm:text-start">
                    <h2 class="text-3xl font-black text-content tracking-tight">{{ __('Who is this account for?') }}</h2>
                    <p class="mt-2 text-content-muted text-lg font-medium">{{ __('Select the legal entity type for billing and invoices.') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <label class="relative group cursor-pointer">
                        <input type="radio" wire:model.live="type" value="organization" class="peer sr-only">
                        <div class="p-6 rounded-[2rem] border-2 transition-all duration-300 flex flex-col items-center gap-4 text-center hover:shadow-xl peer-checked:border-brand peer-checked:bg-brand/5 peer-checked:shadow-brand/20 bg-surface border-transparent">
                            <div class="size-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-content-muted peer-checked:text-brand peer-checked:bg-white peer-checked:shadow-inner transition-colors">
                                <x-heroicon-o-building-office-2 class="size-8" />
                            </div>
                            <div>
                                <span class="block text-lg font-black text-content peer-checked:text-brand">{{ __('Organization') }}</span>
                                <span class="text-xs font-bold text-content-muted mt-1 block">{{ __('Company, NGO, or Business') }}</span>
                            </div>
                            <div class="absolute top-6 right-6 opacity-0 peer-checked:opacity-100 transition-opacity text-brand">
                                <x-heroicon-s-check-circle class="size-6" />
                            </div>
                        </div>
                    </label>

                    <label class="relative group cursor-pointer">
                        <input type="radio" wire:model.live="type" value="individual" class="peer sr-only">
                        <div class="p-6 rounded-[2rem] border-2 transition-all duration-300 flex flex-col items-center gap-4 text-center hover:shadow-xl peer-checked:border-emerald-500 peer-checked:bg-emerald-500/5 peer-checked:shadow-emerald-500/20 bg-surface border-transparent">
                            <div class="size-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-content-muted peer-checked:text-emerald-600 peer-checked:bg-white peer-checked:shadow-inner transition-colors">
                                <x-heroicon-o-user class="size-8" />
                            </div>
                            <div>
                                <span class="block text-lg font-black text-content peer-checked:text-emerald-700">{{ __('Individual') }}</span>
                                <span class="text-xs font-bold text-content-muted mt-1 block">{{ __('Private person or Freelancer') }}</span>
                            </div>
                            <div class="absolute top-6 right-6 opacity-0 peer-checked:opacity-100 transition-opacity text-emerald-600">
                                <x-heroicon-s-check-circle class="size-6" />
                            </div>
                        </div>
                    </label>
                </div>

                <div class="space-y-3">
                    <x-input-label for="wizard-name" :value="__('Display Name')" class="text-xs font-black text-content-muted uppercase tracking-widest px-1" />
                    <input id="wizard-name" type="text" wire:model.defer="name" class="block w-full px-6 py-4 rounded-2xl bg-surface border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-lg font-bold shadow-inner placeholder:text-content-muted/50" placeholder="{{ $type === 'organization' ? 'Acme Corp Ltd.' : 'John Doe' }}" />
                    <x-input-error :messages="$errors->get('name')" class="px-2" />
                </div>
            </div>
        @endif

        {{-- Step 2: Ownership --}}
        @if ($step === 2)
            <div wire:transition.out.opacity.duration.200ms.in.opacity.duration.300ms class="space-y-8">
                <div class="text-center sm:text-start">
                    <h2 class="text-3xl font-black text-content tracking-tight">{{ __('Assign Ownership') }}</h2>
                    <p class="mt-2 text-content-muted text-lg font-medium">{{ __('Who is the primary contact for this account?') }}</p>
                </div>

                <div class="space-y-4">
                    <x-input-label for="wizard-owner" :value="__('Select User')" class="text-xs font-black text-content-muted uppercase tracking-widest px-1" />
                    <div class="relative">
                        <select id="wizard-owner" wire:model.defer="owner_user_id" class="block w-full px-6 py-4 rounded-2xl bg-surface border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-lg font-bold shadow-inner appearance-none cursor-pointer">
                            <option value="">{{ __('— No Owner Assigned —') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-6 pointer-events-none text-content-muted">
                            <x-heroicon-m-chevron-up-down class="size-5" />
                        </div>
                    </div>
                    <p class="text-xs font-medium text-content-muted px-2">
                        <x-heroicon-o-information-circle class="size-4 inline me-1 -mt-0.5" />
                        {{ __('You can assign or change the owner later from the account settings.') }}
                    </p>
                    <x-input-error :messages="$errors->get('owner_user_id')" class="px-2" />
                </div>
            </div>
        @endif

        {{-- Step 3: Review --}}
        @if ($step === 3)
            <div wire:transition.out.opacity.duration.200ms.in.opacity.duration.300ms class="space-y-8">
                <div class="text-center sm:text-start">
                    <h2 class="text-3xl font-black text-content tracking-tight">{{ __('Ready to Launch') }}</h2>
                    <p class="mt-2 text-content-muted text-lg font-medium">{{ __('Review the details below before creating the account.') }}</p>
                </div>

                @if($type === 'organization' && $organizationsWithoutAccount->isNotEmpty())
                    <div class="p-6 rounded-[2rem] bg-indigo-50 border border-indigo-100 shadow-sm space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-white rounded-xl text-indigo-600 shadow-sm shrink-0">
                                <x-heroicon-o-link class="size-6" />
                            </div>
                            <div class="space-y-2 w-full">
                                <h3 class="text-sm font-black text-indigo-900 uppercase tracking-widest">{{ __('Quick Link') }}</h3>
                                <p class="text-sm text-indigo-700 font-medium leading-relaxed">{{ __('We found organizations without a billing account. Link one now?') }}</p>
                                <select id="wizard-attach-org" wire:model.defer="attach_organization_id" class="block w-full mt-2 px-4 py-3 rounded-xl bg-white border-transparent focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-bold shadow-sm">
                                    <option value="">{{ __('— Skip Linking —') }}</option>
                                    @foreach($organizationsWithoutAccount as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-surface/50 rounded-[2rem] border border-stroke p-8 space-y-6">
                    <div class="flex justify-between items-center pb-6 border-b border-stroke/50">
                        <span class="text-sm font-bold text-content-muted">{{ __('Account Type') }}</span>
                        <span class="px-4 py-1.5 rounded-full bg-white border border-stroke text-xs font-black uppercase tracking-wider shadow-sm">
                            {{ $type === 'organization' ? __('Organization') : __('Individual') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center pb-6 border-b border-stroke/50">
                        <span class="text-sm font-bold text-content-muted">{{ __('Display Name') }}</span>
                        <span class="text-lg font-black text-content">{{ $name ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-content-muted">{{ __('Primary Owner') }}</span>
                        <div class="text-end">
                            @if($owner_user_id)
                                @php $owner = $users->firstWhere('id', (int) $owner_user_id); @endphp
                                <span class="block text-sm font-black text-content">{{ $owner?->name }}</span>
                                <span class="block text-xs font-bold text-content-muted">{{ $owner?->email }}</span>
                            @else
                                <span class="text-sm font-bold text-content-muted italic">{{ __('Unassigned') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Action Bar --}}
        <div class="mt-auto pt-10 flex justify-between items-center gap-4">
            <button type="button" wire:click="previousStep"
                {{ $step === 1 ? 'disabled' : '' }}
                class="px-8 py-4 rounded-2xl font-bold text-content-muted hover:text-content hover:bg-surface disabled:opacity-30 disabled:cursor-not-allowed transition-all active:scale-95">
                {{ __('Back') }}
            </button>

            <button type="button" wire:click="nextStep"
                wire:loading.attr="disabled"
                class="group relative inline-flex items-center justify-center gap-3 px-10 py-4 rounded-2xl font-black text-white shadow-xl transition-all active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed min-w-[160px] {{ $step === $totalSteps ? 'bg-success hover:bg-success/90 shadow-success/20' : 'bg-brand hover:bg-brand-hover shadow-brand/20' }}">
                <span wire:loading.remove class="flex items-center gap-2">
                    {{ $step === $totalSteps ? __('Create Account') : __('Continue') }}
                    <x-heroicon-m-arrow-right class="size-5 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                </span>
                <span wire:loading>
                    <svg class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
            </button>
        </div>
    </div>
</div>
