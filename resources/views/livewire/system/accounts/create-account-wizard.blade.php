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
    <div class="mb-12 relative px-4">
        <div class="relative px-4 sm:px-10 py-10 sm:py-16 bg-slate-50/50 backdrop-blur-xl rounded-[3rem] border border-white shadow-2xl shadow-slate-900/5 overflow-hidden">
            <div class="absolute top-0 right-0 p-12 -mt-10 -mr-10 bg-brand/5 size-40 rounded-full blur-3xl"></div>
            
            {{-- Horizontal Stepper --}}
            <nav aria-label="Progress" class="relative z-10 mb-12 sm:mb-20">
                <ol class="flex items-center justify-between">
                    @foreach([
                        1 => __('Identity'),
                        2 => __('Ownership'),
                        3 => __('Verification')
                    ] as $stepNum => $label)
                        <li class="relative flex-1 group">
                            <div class="flex flex-col items-center">
                                <div class="relative flex items-center justify-center size-10 sm:size-14 rounded-2xl border-2 transition-all duration-500 shadow-lg {{ $step === $stepNum ? 'bg-brand border-brand text-white shadow-brand/20 scale-110' : ($step > $stepNum ? 'bg-emerald-500 border-emerald-500 text-white shadow-emerald-500/20' : 'bg-white border-slate-200 text-slate-300') }}">
                                    @if($step > $stepNum)
                                        <x-heroicon-m-check class="size-6 sm:size-8" />
                                    @else
                                        <span class="text-sm sm:text-lg font-black">{{ $stepNum }}</span>
                                    @endif
                                </div>
                                <span class="hidden sm:block mt-4 text-[10px] font-black uppercase tracking-[0.25em] {{ $step === $stepNum ? 'text-slate-900' : 'text-slate-400' }}">{{ $label }}</span>
                            </div>
                            @if(!$loop->last)
                                <div class="absolute top-5 sm:top-7 left-1/2 w-full h-[3px] -z-10 bg-slate-100">
                                    <div class="h-full bg-brand transition-all duration-700 ease-out" style="width: {{ $step > $stepNum ? '100' : ($step === $stepNum ? '50' : '0') }}%"></div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-[3rem] shadow-2xl shadow-slate-900/10 border border-white p-8 sm:p-16 min-h-[500px] flex flex-col relative overflow-hidden">
        
        {{-- Step 1: Identity Selection --}}
        @if ($step === 1)
            <div wire:key="step-1" class="space-y-12 animate-in fade-in slide-in-from-bottom-8 duration-500">
                <div class="text-start space-y-3">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none">{{ __('Identify Entity Persona') }}</h2>
                    <p class="text-base text-slate-500 font-medium leading-relaxed">{{ __('Is this account for a legal organization or a private individual?') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <button type="button" wire:click="$set('type', 'organization')" class="group relative p-10 rounded-[3rem] border-2 text-start transition-all duration-500 active:scale-95 {{ $type === 'organization' ? 'bg-brand border-brand shadow-2xl shadow-brand/20' : 'bg-slate-50 border-transparent hover:border-brand/30 shadow-inner' }}">
                        <div class="flex flex-col gap-8">
                            <div class="size-20 rounded-[2rem] flex items-center justify-center transition-all duration-500 {{ $type === 'organization' ? 'bg-white/20 text-white scale-110' : 'bg-white text-brand shadow-xl shadow-slate-900/5 group-hover:scale-110 group-hover:rotate-3' }}">
                                <x-heroicon-o-building-office-2 class="size-10" />
                            </div>
                            <div>
                                <p class="text-2xl font-black tracking-tight leading-none mb-3 {{ $type === 'organization' ? 'text-white' : 'text-slate-900' }}">{{ __('Organization') }}</p>
                                <p class="text-sm font-bold leading-relaxed {{ $type === 'organization' ? 'text-brand-light/70' : 'text-slate-400' }}">{{ __('Company, Non-profit, or Government Entity.') }}</p>
                            </div>
                        </div>
                        @if($type === 'organization')
                            <div class="absolute top-8 right-8 size-8 bg-white rounded-full flex items-center justify-center text-brand animate-in zoom-in duration-300 shadow-lg">
                                <x-heroicon-m-check-circle class="size-6" />
                            </div>
                        @endif
                    </button>

                    <button type="button" wire:click="$set('type', 'individual')" class="group relative p-10 rounded-[3rem] border-2 text-start transition-all duration-500 active:scale-95 {{ $type === 'individual' ? 'bg-brand border-brand shadow-2xl shadow-brand/20' : 'bg-slate-50 border-transparent hover:border-brand/30 shadow-inner' }}">
                        <div class="flex flex-col gap-8">
                            <div class="size-20 rounded-[2rem] flex items-center justify-center transition-all duration-500 {{ $type === 'individual' ? 'bg-white/20 text-white scale-110' : 'bg-white text-brand shadow-xl shadow-slate-900/5 group-hover:scale-110 group-hover:-rotate-3' }}">
                                <x-heroicon-o-user class="size-10" />
                            </div>
                            <div>
                                <p class="text-2xl font-black tracking-tight leading-none mb-3 {{ $type === 'individual' ? 'text-white' : 'text-slate-900' }}">{{ __('Individual') }}</p>
                                <p class="text-sm font-bold leading-relaxed {{ $type === 'individual' ? 'text-brand-light/70' : 'text-slate-400' }}">{{ __('Private person or sole proprietor.') }}</p>
                            </div>
                        </div>
                        @if($type === 'individual')
                            <div class="absolute top-8 right-8 size-8 bg-white rounded-full flex items-center justify-center text-brand animate-in zoom-in duration-300 shadow-lg">
                                <x-heroicon-m-check-circle class="size-6" />
                            </div>
                        @endif
                    </button>
                </div>

                <div class="space-y-4 pt-4">
                    <x-input-label for="acc-name" :value="__('Account Display Name')" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] px-4" />
                    <input id="acc-name" type="text" wire:model="name" class="block w-full px-8 py-6 rounded-3xl bg-slate-50 border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-lg font-bold shadow-inner placeholder:font-medium placeholder:text-slate-300" placeholder="{{ __('e.g. Acme Corp or Jane Doe...') }}" />
                    @error('name') <p class="px-4 text-[11px] font-black text-rose-500 uppercase tracking-wider">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        {{-- Step 2: Ownership Assignment --}}
        @if ($step === 2)
            <div wire:key="step-2" class="space-y-12 animate-in fade-in slide-in-from-bottom-8 duration-500 text-start">
                <div class="space-y-3">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none">{{ __('Establish Ownership') }}</h2>
                    <p class="text-base text-slate-500 font-medium leading-relaxed">{{ __('Select the primary user responsible for this account.') }}</p>
                </div>

                <div class="space-y-6">
                    <div class="relative group">
                        <div class="absolute inset-y-0 start-0 ps-7 flex items-center pointer-events-none text-slate-300 group-focus-within:text-brand transition-colors">
                            <x-heroicon-o-magnifying-glass class="size-6" />
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" class="block w-full ps-16 py-6 rounded-3xl bg-slate-50 border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-lg font-bold shadow-inner placeholder:font-medium placeholder:text-slate-300" placeholder="{{ __('Search users by name or email...') }}" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 max-h-[400px] overflow-y-auto no-scrollbar pr-4 -mr-4">
                        @forelse($users as $user)
                            <button type="button" wire:click="$set('owner_user_id', {{ $user->id }})" class="group w-full p-6 rounded-3xl border-2 text-start transition-all duration-300 active:scale-[0.98] flex items-center gap-6 {{ (int) $owner_user_id === (int) $user->id ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-white hover:border-brand/20 shadow-xl shadow-slate-900/5' }}">
                                <div class="size-14 rounded-2xl flex items-center justify-center font-black transition-all duration-300 {{ (int) $owner_user_id === (int) $user->id ? 'bg-brand text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-brand/10 group-hover:text-brand' }}">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-base font-black text-slate-900 truncate tracking-tight mb-1">{{ $user->name }}</p>
                                    <p class="text-xs font-bold text-slate-400 truncate tracking-tight">{{ $user->email }}</p>
                                </div>
                                @if((int) $owner_user_id === (int) $user->id)
                                    <div class="size-7 bg-brand text-white rounded-full flex items-center justify-center animate-in zoom-in duration-300 shadow-lg">
                                        <x-heroicon-m-check class="size-4.5" />
                                    </div>
                                @endif
                            </button>
                        @empty
                            <div class="py-20 text-center bg-slate-50/50 rounded-[3rem] border border-dashed border-slate-200">
                                <x-heroicon-o-user-plus class="size-12 text-slate-200 mx-auto mb-4" />
                                <p class="text-base font-bold text-slate-400 italic">{{ __('No users matching search.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 3: Verification & Finalize --}}
        @if ($step === 3)
            <div wire:key="step-3" class="space-y-12 animate-in fade-in slide-in-from-bottom-8 duration-500">
                <div class="text-start space-y-3">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none">{{ __('Deployment Summary') }}</h2>
                    <p class="text-base text-slate-500 font-medium leading-relaxed">{{ __('Review your account parameters before activating the new identity.') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div class="p-10 rounded-[3rem] bg-indigo-950/5 border border-indigo-100/50 shadow-inner group transition-all">
                        <div class="flex flex-col gap-8">
                            <div class="size-20 rounded-[2.5rem] bg-white shadow-2xl shadow-slate-900/5 flex items-center justify-center text-indigo-600 border border-white">
                                <x-heroicon-o-building-office-2 class="size-10" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-indigo-900/40 uppercase tracking-[0.25em] mb-3 leading-none">{{ __('Account Identity') }}</p>
                                <p class="text-2xl font-black text-slate-900 tracking-tight leading-none mb-3">{{ $name }}</p>
                                <span class="px-4 py-2 rounded-2xl bg-white text-[10px] font-black uppercase text-indigo-700 tracking-widest ring-1 ring-indigo-100 shadow-sm">{{ __($type) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-10 rounded-[3rem] bg-emerald-950/5 border border-emerald-100/50 shadow-inner group transition-all">
                        <div class="flex flex-col gap-8">
                            <div class="size-20 rounded-[2.5rem] bg-white shadow-2xl shadow-slate-900/5 flex items-center justify-center text-emerald-600 border border-white">
                                <x-heroicon-o-user class="size-10" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-emerald-900/40 uppercase tracking-[0.25em] mb-3 leading-none">{{ __('Master Owner') }}</p>
                                <p class="text-2xl font-black text-slate-900 tracking-tight leading-none mb-3">{{ $owner ? $owner->name : __('Direct ID: #') . $owner_user_id }}</p>
                                @if($owner)
                                    <p class="text-xs font-bold text-slate-400 mb-4">{{ $owner->email }}</p>
                                @endif
                                <span class="px-4 py-2 rounded-2xl bg-white text-[10px] font-black uppercase text-emerald-700 tracking-widest ring-1 ring-emerald-100 shadow-sm">UID: #{{ $owner_user_id }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($type === 'organization' && $organizationsWithoutAccount->isNotEmpty())
                    <div class="p-8 rounded-[2.5rem] bg-amber-50/50 border border-amber-100 shadow-inner group animate-in slide-in-from-left-4 duration-700">
                        <div class="flex items-start gap-6">
                            <div class="size-14 rounded-2xl bg-white shadow-xl shadow-slate-900/5 flex items-center justify-center text-amber-600 border border-white">
                                <x-heroicon-o-link class="size-7" />
                            </div>
                            <div class="space-y-4 grow">
                                <div class="space-y-1">
                                    <h3 class="text-[10px] font-black text-amber-900/40 uppercase tracking-[0.2em] leading-none">{{ __('Orphan Organization Map') }}</h3>
                                    <p class="text-sm text-amber-900 font-bold leading-relaxed">{{ __('Link an existing unassigned organization?') }}</p>
                                </div>
                                <select id="wizard-attach-org" wire:model.defer="attach_organization_id" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-amber-500/10 focus:border-amber-500 text-sm font-bold shadow-xl shadow-slate-900/5">
                                    <option value="">{{ __('— Create New Private Org —') }}</option>
                                    @foreach($organizationsWithoutAccount as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }} (ID: {{ $org->id }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Action Bar --}}
        <div class="mt-auto pt-16 flex flex-col sm:flex-row items-center gap-4 border-t border-slate-50 relative z-10 transition-all duration-500">
            @if($step > 1)
                <button type="button" wire:click="previousStep" class="w-full sm:w-auto px-12 py-6 bg-white border border-slate-200 text-slate-500 font-black rounded-3xl hover:bg-slate-50 transition-all active:scale-95 shadow-lg shadow-slate-900/5">
                    {{ __('Previous') }}
                </button>
            @endif
            
            <div class="grow"></div>
            
            <button type="button" wire:click="{{ $step === $totalSteps ? 'save' : 'nextStep' }}" 
                wire:loading.attr="disabled"
                class="w-full sm:w-auto px-16 py-6 font-black text-white rounded-3xl shadow-2xl transition-all active:scale-95 disabled:opacity-70 disabled:grayscale disabled:cursor-not-allowed group flex items-center justify-center gap-3 {{ $step === $totalSteps ? 'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700' : 'bg-brand shadow-brand/20 hover:bg-brand-hover' }}">
                <span wire:loading.remove class="flex items-center gap-3">
                    {{ $step === $totalSteps ? __('Finalize & Activate Account') : __('Continue to Next Stage') }}
                    <x-heroicon-m-chevron-right class="size-6 group-hover:translate-x-1.5 transition-transform rtl:rotate-180" />
                </span>
                <span wire:loading>
                    <svg class="animate-spin size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
            </button>
        </div>
    </div>
</div>

</div>
