<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12 space-y-8 animate-in fade-in duration-700" role="main" aria-label="{{ __('Account Administration') }}">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 text-start">
        <div class="space-y-4 flex-1 min-w-0">
            <nav class="flex overflow-x-auto no-scrollbar pb-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 rtl:space-x-reverse text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 whitespace-nowrap">
                    <li><a href="{{ route('system.dashboard') }}" class="hover:text-indigo-600 transition-colors">{{ __('System') }}</a></li>
                    <li><x-heroicon-m-chevron-right class="size-3 shrink-0" /></li>
                    <li><a href="{{ route('system.accounts.index') }}" class="hover:text-indigo-600 transition-colors">{{ __('Accounts') }}</a></li>
                    <li><x-heroicon-m-chevron-right class="size-3 shrink-0" /></li>
                    <li class="text-slate-900 truncate" aria-current="page">{{ $account->name ?: __('Managed Account') }}</li>
                </ol>
            </nav>
            <div class="flex flex-wrap items-center gap-4">
                <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tighter leading-none break-words">{{ $account->name ?: __('Managed Account') }}</h1>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 uppercase tracking-widest">{{ __($account->type) }}</span>
                    @if($account->sumit_customer_id)
                        <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 uppercase tracking-widest">{{ __('SUMIT Connected') }}</span>
                    @endif
                </div>
            </div>
            <p class="text-sm sm:text-lg text-slate-500 font-medium italic text-start">
                Account ID: <span class="font-bold text-slate-700">#{{ $account->id }}</span> • {{ __('Created on') }} {{ $account->created_at->format('M d, Y') }}
            </p>
        </div>
        <div class="flex shrink-0">
            <button wire:click="openEdit" class="group w-full inline-flex items-center justify-center gap-3 px-8 py-4 bg-white border border-slate-200 text-slate-900 font-black rounded-[1.5rem] hover:bg-slate-50 shadow-xl shadow-slate-900/5 transition-all active:scale-95 cursor-pointer min-h-[60px]">
                <x-heroicon-o-pencil-square class="size-6 text-indigo-600" />
                <span class="text-lg">{{ __('Edit Profile') }}</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-sm flex items-center gap-3 backdrop-blur-sm animate-in fade-in slide-in-from-top-4" role="alert">
            <x-heroicon-o-check-circle class="size-5 text-emerald-600" />
            <span class="font-medium text-start">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/60 text-rose-800 text-sm flex items-center gap-3 backdrop-blur-sm animate-in fade-in slide-in-from-top-4" role="alert">
            <x-heroicon-o-exclamation-circle class="size-5 text-rose-600" />
            <span class="font-medium text-start">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Scoreboard Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-start">
        <div class="bg-card/80 rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-brand/30 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-brand uppercase tracking-[0.25em] leading-none">{{ __('Tenant Organizations') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-slate-900 tracking-tighter leading-none">{{ $account->organizations->count() }}</span>
                <div class="size-14 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-sm border border-brand/10">
                    <x-heroicon-o-building-office-2 class="size-7" />
                </div>
            </div>
        </div>
        <div class="bg-card/80 rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-indigo-300 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] leading-none">{{ __('Active Entitlements') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-slate-900 tracking-tighter leading-none">{{ $account->entitlements->count() }}</span>
                <div class="size-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-500 shadow-sm border border-slate-100">
                    <x-heroicon-o-key class="size-7" />
                </div>
            </div>
        </div>
        <div class="bg-card/80 rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-emerald-300 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] leading-none">{{ __('SUMIT Identity') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $account->sumit_customer_id ?: __('Unlinked') }}</span>
                <div class="size-14 rounded-2xl {{ $account->sumit_customer_id ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-100' }} flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-sm border">
                    <x-heroicon-o-credit-card class="size-7" />
                </div>
            </div>
        </div>
        <div class="bg-card/80 rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-amber-300 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] leading-none">{{ __('Last Update') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-xs font-black text-slate-700 leading-tight">{{ $account->updated_at->diffForHumans() }}</span>
                <div class="size-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-500 shadow-sm border border-slate-100">
                    <x-heroicon-o-clock class="size-7" />
                </div>
            </div>
        </div>
    </div>

    {{-- Main Glassmorphism Container --}}
    <div class="bg-card/90 rounded-[3rem] shadow-2xl shadow-slate-900/10 border border-white/50 overflow-hidden backdrop-blur-2xl">
        <div class="border-b border-white/20 bg-slate-900/5 px-4 py-4 sm:px-12 sm:py-0 overflow-x-auto no-scrollbar">
            <nav class="flex items-center gap-6 sm:gap-10 lg:gap-12 xl:gap-16 min-w-max" aria-label="Tabs">
                @foreach([
                    'overview' => __('Identity Overview'),
                    'organizations' => __('Tenant Linkage'),
                    'entitlements' => __('Resource Grants'),
                    'usage' => __('Operational Usage'),
                    'intents' => __('Billing Intents')
                ] as $key => $label)
                    <button
                        type="button"
                        wire:click="setTab('{{ $key }}')"
                        class="relative py-8 text-[11px] font-black uppercase tracking-[0.25em] transition-all active:scale-95 whitespace-nowrap {{ $activeTab === $key ? 'text-brand' : 'text-slate-400 hover:text-slate-600' }}"
                    >
                        {{ $label }}
                        @if($activeTab === $key)
                            <span class="absolute bottom-0 inset-x-0 h-1 rounded-t-full bg-brand animate-in slide-in-from-bottom-2 duration-300"></span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="p-4 sm:p-8 lg:p-12 text-start">
            {{-- Tab: Overview --}}
            @if($activeTab === 'overview')
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12 animate-in fade-in duration-500">
                    <div class="lg:col-span-7 space-y-10">
                        @if($showEditForm)
                            {{-- Edit Account Inline Form --}}
                            <div class="p-8 sm:p-10 rounded-[2.5rem] bg-indigo-50/50 border border-indigo-100 shadow-inner">
                                <h3 class="text-xs font-black text-indigo-900 uppercase tracking-[0.2em] mb-8">{{ __('Edit Account Profile') }}</h3>
                                <form wire:submit.prevent="saveAccount" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <x-input-label for="edit-name" :value="__('Account Name')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                            <input id="edit-name" type="text" wire:model="edit_name" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" />
                                        </div>
                                        <div class="space-y-2">
                                            <x-input-label for="edit-owner" :value="__('Owner User ID')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                            <input id="edit-owner" type="number" wire:model="edit_owner_user_id" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" />
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <x-input-label for="edit-sumit" :value="__('SUMIT Customer ID')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                            <input id="edit-sumit" type="number" wire:model="edit_sumit_customer_id" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" />
                                        </div>
                                    </div>
                                    <div class="flex gap-3 pt-4">
                                        <button type="submit" class="flex-1 py-4 bg-brand text-white font-black rounded-2xl shadow-xl shadow-brand/20 hover:bg-brand-hover active:scale-95 transition-all cursor-pointer">
                                            {{ __('Save Changes') }}
                                        </button>
                                        <button type="button" wire:click="cancelEdit" class="px-8 py-4 bg-white border border-slate-200 text-slate-500 font-bold rounded-2xl hover:bg-slate-50 transition-all cursor-pointer">
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="space-y-8">
                                <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Operational Identity') }}</h2>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-10">
                                    <div class="space-y-2">
                                        <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Account Legal Type') }}</dt>
                                        <dd class="text-lg font-black text-slate-900">{{ __($account->type) }}</dd>
                                    </div>
                                    <div class="space-y-2">
                                        <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('SUMIT Reference') }}</dt>
                                        <dd class="text-lg font-black text-slate-900">{{ $account->sumit_customer_id ?: '—' }}</dd>
                                    </div>
                                    <div class="space-y-2 sm:col-span-2">
                                        <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Primary Owner') }}</dt>
                                        <dd class="flex items-center gap-4 mt-2">
                                            <div class="size-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black">
                                                {{ substr($account->owner?->name ?? '?', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-base font-black text-slate-900 leading-none">{{ $account->owner?->name ?? __('Unassigned') }}</p>
                                                <p class="text-xs font-bold text-slate-400 mt-1.5">{{ $account->owner?->email }}</p>
                                            </div>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @endif
                    </div>
                    <div class="lg:col-span-12 xl:col-span-5 space-y-6">
                        <div class="bg-indigo-950/5 rounded-[2.5rem] border border-indigo-100/50 p-8 space-y-8 shadow-inner backdrop-blur-sm">
                            <h3 class="text-[10px] font-black text-indigo-900/40 uppercase tracking-[0.25em] leading-none">{{ __('Governance Summary') }}</h3>
                            <div class="space-y-6">
                                <div class="flex items-center gap-5 group">
                                    <div class="size-14 rounded-2xl bg-white shadow-xl shadow-slate-900/5 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-all duration-500 border border-white">
                                        <x-heroicon-o-shield-check class="size-7" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 leading-none">{{ __('Active Tenants') }}</p>
                                        <p class="text-base font-black text-slate-900 leading-none">{{ $account->organizations->count() }} {{ __('Orgs linked') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-5 group">
                                    <div class="size-14 rounded-2xl bg-white shadow-xl shadow-slate-900/5 flex items-center justify-center text-slate-400 group-hover:text-emerald-600 transition-all duration-500 border border-white">
                                        <x-heroicon-o-banknotes class="size-7" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 leading-none">{{ __('Fiscal State') }}</p>
                                        <p class="text-base font-black text-slate-900 leading-none">{{ $account->sumit_customer_id ? __('External Sync Active') : __('Local Billing Only') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2.5rem] border border-white/50 bg-white/50 p-8 shadow-2xl shadow-slate-900/5 backdrop-blur-xl">
                            <div class="space-y-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 leading-none">{{ __('SUMIT Customer Link') }}</h3>
                                <p class="text-sm font-medium leading-relaxed text-slate-500">
                                    {{ __('Search the OfficeGuy customer model and SUMIT CRM, then connect an existing SUMIT customer to this account.') }}
                                </p>
                            </div>

                            <div class="mt-8 space-y-6">
                                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 shadow-inner">
                                    <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 leading-none">{{ __('Connected SUMIT ID') }}</div>
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="text-xl font-black text-slate-900 tracking-tight">
                                            {{ $account->sumit_customer_id ?: __('Not connected yet') }}
                                        </div>
                                        @if($account->sumit_customer_id)
                                            <button
                                                type="button"
                                                wire:click="disconnectSumitCustomer"
                                                wire:confirm="{{ __('Disconnect the linked SUMIT customer from this account?') }}"
                                                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-rose-100 bg-rose-50 px-5 text-[10px] font-black uppercase tracking-[0.15em] text-rose-600 transition-all hover:bg-rose-100 active:scale-95"
                                            >
                                                <x-heroicon-o-link-slash class="size-4" />
                                                <span>{{ __('Disconnect') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label for="sumit-search" class="block px-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 leading-none">{{ __('Search SUMIT Customer') }}</label>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="relative grow">
                                            <input
                                                id="sumit-search"
                                                type="text"
                                                wire:model.defer="sumit_customer_search"
                                                wire:keydown.enter.prevent="searchSumitCustomers"
                                                class="block w-full rounded-2xl border-transparent bg-slate-50 px-6 py-4 text-sm font-bold text-slate-900 transition-all focus:ring-8 focus:ring-brand/10 focus:border-brand shadow-inner placeholder:font-medium placeholder:text-slate-300"
                                                placeholder="{{ __('Email, ID, Org Name...') }}"
                                            />
                                        </div>
                                        <button type="button" wire:click="searchSumitCustomers" class="inline-flex min-h-[56px] items-center justify-center gap-3 rounded-2xl bg-brand px-8 font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover active:scale-95">
                                            <x-heroicon-o-magnifying-glass class="size-5" />
                                            <span>{{ __('Search') }}</span>
                                        </button>
                                    </div>
                                    @error('sumit_customer_search') <p class="px-2 text-[10px] font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                @if($account->owner?->email)
                                    <button type="button" wire:click="useOwnerEmailForSumitSearch" class="w-full inline-flex min-h-[56px] items-center justify-center gap-3 rounded-2xl border border-slate-100 bg-white px-6 font-black text-slate-600 transition-all hover:bg-slate-50 active:scale-95 shadow-lg shadow-slate-900/5">
                                        <x-heroicon-o-envelope class="size-5 text-indigo-500" />
                                        <span>{{ __('Use Owner Email') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                                @if($sumit_customer_search_message)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
                                        {{ $sumit_customer_search_message }}
                                    </div>
                                @endif

                                @if($sumit_customer_results !== [])
                                    <div class="space-y-3">
                                        @foreach($sumit_customer_results as $candidate)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 hover:border-brand transition-colors group">
                                                <div class="flex flex-col gap-4">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-black text-slate-900 leading-none mb-1">{{ $candidate['name'] }}</div>
                                                        <div class="text-[9px] font-black uppercase tracking-widest text-slate-400">
                                                            {{ __('Sumit ID') }}: #{{ $candidate['sumit_customer_id'] }}
                                                        </div>
                                                        @if(!empty($candidate['email']))
                                                            <div class="mt-2 text-xs font-bold text-slate-600 truncate">{{ $candidate['email'] }}</div>
                                                        @endif
                                                        <div class="mt-3 flex items-center gap-2">
                                                            <span class="px-2 py-0.5 rounded bg-white text-[8px] font-black uppercase tracking-widest text-slate-500 ring-1 ring-slate-100">
                                                                {{ $candidate['source_label'] }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <button type="button" wire:click="connectSumitCustomer({{ $candidate['sumit_customer_id'] }})" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-900/20 hover:bg-indigo-700 active:scale-95 transition-all">
                                                        {{ __('Connect Identity') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div id="billing-methods" class="rounded-[2.5rem] border border-white/50 bg-white/50 p-8 shadow-2xl shadow-slate-900/5 backdrop-blur-xl">
                            <div class="space-y-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 leading-none">{{ __('Saved Billing Methods') }}</h3>
                                <p class="text-sm font-medium leading-relaxed text-slate-500">
                                    {{ __('Manage the saved SUMIT payment methods that can be charged for subscriptions.') }}
                                </p>
                            </div>

                            <div class="mt-8 space-y-4">
                                @if($paymentMethods->isEmpty())
                                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/50 p-10 text-center">
                                        <p class="text-sm font-bold text-slate-400 italic">{{ __('No saved payment methods available.') }}</p>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 gap-4">
                                        @foreach($paymentMethods as $paymentMethod)
                                            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-900/5 group transition-all hover:border-brand/30">
                                                <div class="flex flex-col gap-5">
                                                    <div class="flex items-start justify-between">
                                                        <div class="space-y-1.5">
                                                            <div class="flex items-center gap-3">
                                                                <span class="text-lg font-black text-slate-900 tracking-tight">{{ $paymentMethod->getMaskedNumber() }}</span>
                                                                @if($paymentMethod->is_default)
                                                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-[9px] font-black uppercase text-emerald-600 tracking-widest ring-1 ring-emerald-100">{{ __('Default') }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-3 text-xs font-bold text-slate-400">
                                                                <span class="uppercase">{{ $paymentMethod->getCardTypeName() }}</span>
                                                                <span>•</span>
                                                                <span>{{ __('Exp') }}: {{ $paymentMethod->getFormattedExpiry() }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="size-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300">
                                                            <x-heroicon-o-credit-card class="size-6" />
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        @if(! $paymentMethod->is_default)
                                                            <form method="POST" action="{{ route('system.accounts.payment-methods.default', [$account, $paymentMethod]) }}" class="contents">
                                                                @csrf
                                                                <button type="submit" class="py-3 rounded-xl bg-slate-50 border border-slate-100 text-[10px] font-black text-slate-600 uppercase tracking-widest hover:bg-slate-100 transition-all active:scale-95">
                                                                    {{ __('Set Default') }}
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form method="POST" action="{{ route('system.accounts.payment-methods.destroy', [$account, $paymentMethod]) }}" onsubmit="return confirm('{{ __('Remove this payment method from the account?') }}');" class="contents">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="py-3 rounded-xl border border-rose-100 bg-rose-50 text-[10px] font-black text-rose-500 uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all active:scale-95 {{ $paymentMethod->is_default ? 'col-span-2' : '' }}">
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if(! $sumitPaymentsReady)
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
                                        {{ __('SUMIT payment method management is not configured yet. Verify company ID, public key, and private key before adding a card.') }}
                                    </div>
                                @elseif(blank($account->owner?->email))
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
                                        {{ __('An owner email is required before adding a SUMIT payment method to this account.') }}
                                    </div>
                                @else
                                    <div wire:ignore class="rounded-[2rem] border border-slate-200 bg-slate-50 p-5 shadow-inner">
                                        <div class="space-y-2">
                                            <h4 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Add and Save a New Payment Method') }}</h4>
                                            <p class="text-sm font-medium leading-relaxed text-slate-500">{{ __('The card will be tokenized by SUMIT and saved as the default payment method for future subscription charges.') }}</p>
                                        </div>

                                        <form id="sumit-payment-method-form-{{ $account->id }}" data-og="form" method="POST" action="{{ route('system.accounts.payment-methods.store', $account) }}" class="mt-5 space-y-4">
                                            @csrf
                                            <input type="hidden" name="og-token" data-og="token">
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div class="md:col-span-2">
                                                    <label for="og-ccnum" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Card Number') }}</label>
                                                    <input id="og-ccnum" type="text" name="card_number" data-og="cardnumber" dir="ltr" maxlength="19" inputmode="numeric" autocomplete="cc-number" required class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-bold text-slate-900 transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="0000 0000 0000 0000">
                                                </div>
                                                <div>
                                                    <label for="og-expmonth" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Expiration Month') }}</label>
                                                    <select id="og-expmonth" name="exp_month" data-og="expirationmonth" required class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                                                        <option value="">MM</option>
                                                        @for($month = 1; $month <= 12; $month++)
                                                            <option value="{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="og-expyear" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Expiration Year') }}</label>
                                                    <select id="og-expyear" name="exp_year" data-og="expirationyear" required class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                                                        <option value="">YY</option>
                                                        @for($offset = 0; $offset <= 15; $offset++)
                                                            <option value="{{ (string) (now()->year + $offset) }}">{{ substr((string) (now()->year + $offset), -2) }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                @if(in_array(config('officeguy.cvv', 'required'), ['required', 'yes'], true))
                                                <div class="md:col-span-2">
                                                    <label for="og-cvv" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Security Code (CVV)') }}</label>
                                                    <input id="og-cvv" type="text" name="cvv" data-og="cvv" dir="ltr" maxlength="4" inputmode="numeric" autocomplete="cc-csc" @if(config('officeguy.cvv', 'required') === 'required') required @endif class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-bold text-slate-900 transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="•••">
                                                </div>
                                                @endif
                                                @if(in_array(config('officeguy.citizen_id', 'required'), ['required', 'yes'], true))
                                                <div class="md:col-span-2">
                                                    <label for="og-citizenid" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('ID Number') }}</label>
                                                    <input id="og-citizenid" type="text" name="citizen_id" data-og="citizenid" dir="ltr" maxlength="9" inputmode="numeric" @if(config('officeguy.citizen_id', 'required') === 'required') required @endif class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-bold text-slate-900 transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="000000000">
                                                </div>
                                                @endif
                                            </div>

                                            @error('og-token')
                                                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                            <div data-og-runtime-error class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert"></div>

                                            <button type="submit" data-og-submit class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-60">
                                                <x-heroicon-o-credit-card class="size-5" />
                                                <span>{{ __('Save Payment Method') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab: Organizations --}}
            @if($activeTab === 'organizations')
                <div class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2 text-start">
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Tenant Linkage') }}</h2>
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('Attach or detach tenant organizations to this master billing entity.') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 lg:hidden">
                            @forelse($organizationsAttached as $org)
                                <div wire:key="org-mobile-{{ $org->id }}" class="bg-card/60 backdrop-blur-sm rounded-3xl p-6 border border-white/50 shadow-xl shadow-slate-900/5 group relative active:scale-[0.98] transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0">
                                            <a href="{{ route('system.organizations.show', $org) }}" class="text-base font-black text-slate-900 group-hover:text-brand transition-colors block truncate tracking-tight">{{ $org->name }}</a>
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID: #{{ $org->id }}</span>
                                        </div>
                                        <button type="button" wire:click="detachOrganization({{ $org->id }})" wire:confirm="{{ __('Detach this organization from the account?') }}" class="size-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-sm active:scale-90">
                                            <x-heroicon-o-link-slash class="size-6" />
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-card/40 rounded-3xl p-16 text-center border border-dashed border-slate-200">
                                    <x-heroicon-o-building-office-2 class="size-12 text-slate-200 mx-auto mb-4" />
                                    <p class="text-slate-400 italic font-bold leading-relaxed">{{ __('No organizations linked.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Desktop Table View for Organizations --}}
                        <div class="hidden lg:block overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-stroke">
                                    <thead>
                                        <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                            <th class="px-8 py-5 text-start">{{ __('Organization Entity') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('UID') }}</th>
                                            <th class="px-8 py-5 text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stroke text-start">
                                        @foreach($organizationsAttached as $org)
                                            <tr wire:key="org-desktop-{{ $org->id }}" class="hover:bg-brand/5 transition-all group">
                                                <td class="px-8 py-6">
                                                    <a href="{{ route('system.organizations.show', $org) }}" class="text-base font-black text-slate-900 group-hover:text-brand transition-colors">{{ $org->name }}</a>
                                                </td>
                                                <td class="px-8 py-6">
                                                    <span class="text-xs font-bold text-slate-400">#{{ $org->id }}</span>
                                                </td>
                                                <td class="px-8 py-6 text-end">
                                                    <button type="button" wire:click="detachOrganization({{ $org->id }})" wire:confirm="{{ __('Detach this organization from the account?') }}" class="text-xs font-black text-rose-500 hover:text-rose-700 uppercase tracking-widest transition-all">
                                                        {{ __('Detach') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Link Section --}}
                    <div class="p-8 sm:p-10 rounded-[2.5rem] bg-slate-50 border border-slate-100 shadow-inner">
                        <h3 class="text-xs font-black text-indigo-900 uppercase tracking-[0.2em] mb-6">{{ __('Connect Existing Tenant') }}</h3>
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="grow">
                                <select wire:model="attach_organization_id" class="w-full rounded-2xl border-transparent bg-white py-4 px-6 text-sm font-black shadow-xl ring-1 ring-slate-200 focus:ring-8 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                    <option value="">{{ __('Select Unlinked Organization...') }}</option>
                                    @foreach($organizationsAvailable as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }} (ID #{{ $org->id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" wire:click="attachOrganization" class="px-10 py-4 bg-brand text-white font-black rounded-2xl hover:bg-brand-hover shadow-xl shadow-brand/20 transition-all active:scale-95 cursor-pointer min-h-[56px] whitespace-nowrap">
                                {{ __('Attach Tenant') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab: Entitlements (Management View) --}}
            @if($activeTab === 'entitlements')
                <div class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2 text-start">
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Resource Grants') }}</h2>
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('Control the quotas and features available to this billing account.') }}</p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <div class="relative">
                                <select wire:model="selected_product_id" class="w-full rounded-2xl border-transparent bg-slate-50 px-4 py-3 pr-10 text-xs font-black shadow-inner ring-1 ring-slate-200 focus:border-indigo-500 focus:ring-8 focus:ring-indigo-500/10 transition-all appearance-none cursor-pointer sm:min-w-[220px]">
                                    <option value="">{{ __('Select Product to Grant...') }}</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($selectedProduct && $selectedProductPlans->isNotEmpty())
                                <div class="relative">
                                    <select wire:model="selected_plan_id" class="w-full rounded-2xl border-transparent bg-slate-50 px-4 py-3 pr-10 text-xs font-black shadow-inner ring-1 ring-slate-200 focus:border-indigo-500 focus:ring-8 focus:ring-indigo-500/10 transition-all appearance-none cursor-pointer sm:min-w-[240px]">
                                        <option value="">{{ __('Select Plan to Activate...') }}</option>
                                        @foreach($selectedProductPlans as $plan)
                                            @php($primaryPrice = $plan->activePrices->first())
                                            <option value="{{ $plan->id }}">
                                                {{ $plan->name }}
                                                @if($primaryPrice)
                                                    - {{ strtoupper($primaryPrice->currency) }} {{ number_format(((int) $primaryPrice->amount) / 100, 2) }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <button wire:click="grantSelectedProduct" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 font-black text-white shadow-lg shadow-indigo-900/20 transition-all active:scale-95 cursor-pointer whitespace-nowrap">
                                <x-heroicon-o-gift class="size-5" />
                                {{ $selectedProduct && $selectedProductPlans->isNotEmpty() ? __('Activate Subscription') : __('Grant Complimentary Access') }}
                            </button>
                            <button wire:click="openCreateEntitlement" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-black text-white shadow-lg shadow-brand/20 transition-all active:scale-95 cursor-pointer whitespace-nowrap">
                                <x-heroicon-o-plus class="size-5" />
                                {{ __('Add New Grant') }}
                            </button>
                        </div>
                    </div>
                    @error('selected_product_id') <p class="text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    @error('selected_plan_id') <p class="text-xs font-bold text-rose-500">{{ $message }}</p> @enderror

                    @if($selectedProduct)
                        <div class="rounded-[2rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm font-medium text-slate-600">
                            @if($selectedProductPlans->isNotEmpty())
                                {{ __('This product has a commercial layer. Activation will create a subscription and use the selected plan pricing instead of granting free access.') }}
                            @else
                                {{ __('This product has no active commercial plans, so access will be granted as complimentary manual access.') }}
                            @endif
                        </div>
                    @endif

                    @if($showEntitlementForm)
                        <div class="p-8 sm:p-10 rounded-[2.5rem] bg-indigo-50/50 border border-indigo-100/50 shadow-inner backdrop-blur-sm animate-in zoom-in-95 duration-500">
                            <h3 class="text-[10px] font-black text-indigo-900/40 uppercase tracking-[0.25em] mb-10 leading-none">{{ $editingEntitlementId ? __('Edit Feature Grant') : __('Create New Feature Grant') }}</h3>
                            <form wire:submit.prevent="saveEntitlement" class="grid grid-cols-1 md:grid-cols-12 gap-8">
                                <div class="md:col-span-5 space-y-3">
                                    <x-input-label for="e-key" :value="__('Feature Key')" class="text-[10px] font-black text-indigo-900/40 uppercase tracking-widest px-2" />
                                    <input id="e-key" type="text" list="common-feature-keys" wire:model="entitlement_feature_key" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 placeholder:font-medium placeholder:text-slate-200" placeholder="e.g. max_guests" />
                                    <datalist id="common-feature-keys">
                                        <option value="max_guests">
                                        <option value="max_events">
                                        <option value="twilio_enabled">
                                        <option value="sms_confirmation_enabled">
                                        <option value="sms_confirmation_limit">
                                        <option value="whatsapp_enabled">
                                        <option value="custom_domain_enabled">
                                        <option value="api_access">
                                    </datalist>
                                    @error('entitlement_feature_key') <p class="px-2 text-[10px] font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3 space-y-3">
                                    <x-input-label for="e-val" :value="__('Value')" class="text-[10px] font-black text-indigo-900/40 uppercase tracking-widest px-2" />
                                    <input id="e-val" type="text" wire:model="entitlement_value" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 placeholder:font-medium placeholder:text-slate-200" placeholder="e.g. 500" />
                                    @error('entitlement_value') <p class="px-2 text-[10px] font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-4 space-y-3">
                                    <x-input-label for="e-exp" :value="__('Expiry Date (Optional)')" class="text-[10px] font-black text-indigo-900/40 uppercase tracking-widest px-2" />
                                    <input id="e-exp" type="date" wire:model="entitlement_expires_at" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5" />
                                </div>
                                <div class="md:col-span-12 flex flex-col gap-4 pt-4 sm:flex-row">
                                    <button type="submit" class="grow min-h-[56px] bg-brand text-white font-black rounded-2xl shadow-xl shadow-brand/20 hover:bg-brand-hover transition-all active:scale-95 cursor-pointer">
                                        {{ $editingEntitlementId ? __('Update Grant') : __('Authorize Grant') }}
                                    </button>
                                    <button type="button" wire:click="cancelEntitlement" class="px-10 min-h-[56px] bg-white border border-slate-100 text-slate-500 font-bold rounded-2xl hover:bg-slate-50 transition-all active:scale-95 shadow-lg shadow-slate-900/5">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="space-y-4">
                        {{-- Mobile Card View for Entitlements --}}
                        <div class="grid grid-cols-1 gap-4 lg:hidden">
                            @forelse($entitlements as $e)
                                <div wire:key="ent-mobile-{{ $e->id }}" class="bg-card/60 backdrop-blur-sm rounded-3xl p-6 border border-white/50 shadow-xl shadow-slate-900/5 group relative active:scale-[0.98] transition-all">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0">
                                            <div class="text-base font-black text-slate-900 tracking-tight mb-3 leading-none">{{ $e->feature_key }}</div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="px-3 py-1.5 rounded-xl bg-brand/5 text-brand text-[10px] font-black uppercase tracking-widest ring-1 ring-brand/10">{{ $e->value ?: __('Unlimited') }}</span>
                                                @if($e->expires_at)
                                                    <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest {{ $e->expires_at->isPast() ? 'bg-rose-50 text-rose-500 ring-1 ring-rose-100' : 'bg-slate-50 text-slate-400 ring-1 ring-slate-100' }}">
                                                        {{ $e->expires_at->format('M d, Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <button type="button" wire:click="openEditEntitlement({{ $e->id }})" class="size-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-90">
                                                <x-heroicon-o-pencil class="size-5" />
                                            </button>
                                            <button type="button" wire:click="deleteEntitlement({{ $e->id }})" wire:confirm="{{ __('Permanently delete this grant?') }}" class="size-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-sm active:scale-90">
                                                <x-heroicon-o-trash class="size-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-card/40 rounded-3xl p-16 text-center border border-dashed border-slate-200">
                                    <x-heroicon-o-key class="size-12 text-slate-200 mx-auto mb-4" />
                                    <p class="text-slate-400 italic font-bold leading-relaxed">{{ __('No active resource grants found.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Desktop Table View for Entitlements --}}
                        <div class="hidden lg:block overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-stroke">
                                    <thead>
                                        <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                            <th class="px-8 py-5 text-start">{{ __('Operational Feature') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('Grant Value') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('Timeline Expiry') }}</th>
                                            <th class="px-8 py-5 text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stroke text-start">
                                        @foreach($entitlements as $e)
                                            <tr wire:key="ent-desktop-{{ $e->id }}" class="hover:bg-brand/5 transition-all group">
                                                <td class="px-8 py-6">
                                                    <span class="text-sm font-black text-slate-900">{{ $e->feature_key }}</span>
                                                </td>
                                                <td class="px-8 py-6">
                                                    <span class="inline-flex px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-black">{{ $e->value ?: 'Unlimited' }}</span>
                                                </td>
                                                <td class="px-8 py-6">
                                                    @if($e->expires_at)
                                                        <span class="text-xs font-bold {{ $e->expires_at->isPast() ? 'text-rose-500' : 'text-slate-500' }}">
                                                            {{ $e->expires_at->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-xs font-black text-slate-300 italic">{{ __('Perpetual') }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-8 py-6 text-end">
                                                    <div class="flex items-center justify-end gap-3">
                                                        <button type="button" wire:click="openEditEntitlement({{ $e->id }})" class="text-xs font-black text-indigo-600 hover:underline uppercase tracking-widest">{{ __('Modify') }}</button>
                                                        <button type="button" wire:click="deleteEntitlement({{ $e->id }})" wire:confirm="{{ __('Permanently delete this grant?') }}" class="text-xs font-black text-rose-500 hover:underline uppercase tracking-widest">{{ __('Revoke') }}</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab: Usage --}}
            @if($activeTab === 'usage')
                <div class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                    <div class="space-y-2 text-start">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Operational Usage') }}</h2>
                        <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('Track real-time resource consumption across all linked tenants.') }}</p>
                    </div>

                    <div class="space-y-4">
                        {{-- Mobile Card View for Usage --}}
                        <div class="grid grid-cols-1 gap-4 lg:hidden">
                            @forelse($usage as $u)
                                <div wire:key="usage-mobile-{{ $u->id }}" class="bg-card/60 backdrop-blur-sm rounded-3xl p-6 border border-white/50 shadow-xl shadow-slate-900/5 group active:scale-[0.98] transition-all">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-base font-black text-slate-900 leading-none tracking-tight">{{ $u->feature_key }}</span>
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-black tracking-widest border border-white">#{{ $u->id }}</span>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div class="space-y-1">
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ __('Consumed') }}</div>
                                            <div class="text-3xl font-black text-brand tracking-tighter leading-none">{{ $u->usage_count }}</div>
                                        </div>
                                        <div class="text-end space-y-1">
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ __('Last Event') }}</div>
                                            <div class="text-[10px] font-black text-slate-600 tracking-tight">{{ $u->updated_at->format('M d, H:i') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-card/40 rounded-3xl p-16 text-center border border-dashed border-slate-200">
                                    <x-heroicon-o-chart-bar class="size-12 text-slate-200 mx-auto mb-4" />
                                    <p class="text-slate-400 italic font-bold leading-relaxed">{{ __('No usage data recorded.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Desktop Table View for Usage --}}
                        <div class="hidden lg:block overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-stroke">
                                    <thead>
                                        <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                            <th class="px-8 py-5 text-start">{{ __('Operational Feature') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('Cumulative Consumption') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('Last Activity') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stroke text-start">
                                        @foreach($usage as $u)
                                            <tr wire:key="usage-desktop-{{ $u->id }}" class="hover:bg-brand/5 transition-all">
                                                <td class="px-8 py-6">
                                                    <span class="text-sm font-black text-slate-900">{{ $u->feature_key }}</span>
                                                </td>
                                                <td class="px-8 py-6">
                                                    <span class="text-sm font-black text-brand">{{ $u->usage_count }}</span>
                                                </td>
                                                <td class="px-8 py-6">
                                                    <span class="text-xs font-bold text-slate-500">{{ $u->updated_at->diffForHumans() }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab: Intents --}}
            @if($activeTab === 'intents')
                <div class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                    <div class="space-y-2 text-start">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Billing Intents') }}</h2>
                        <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('History of payment intentions and synchronization logs with external gateways.') }}</p>
                    </div>

                    <div class="space-y-4">
                        {{-- Mobile Card View for Intents --}}
                        <div class="grid grid-cols-1 gap-4 lg:hidden">
                            @forelse($billingIntents as $intent)
                                <div wire:key="intent-mobile-{{ $intent->id }}" class="bg-card/60 backdrop-blur-sm rounded-3xl p-6 border border-white/50 shadow-xl shadow-slate-900/5 group active:scale-[0.98] transition-all">
                                    <div class="flex items-center justify-between mb-5 text-start">
                                        <div class="min-w-0">
                                            <div class="text-base font-black text-slate-900 truncate tracking-tight mb-1 leading-none">{{ $intent->intent_type }}</div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ID: #{{ $intent->id }}</div>
                                        </div>
                                        <span class="px-3 py-1.5 rounded-xl border {{ $intent->status === 'completed' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100' }} text-[10px] font-black uppercase tracking-widest">
                                            {{ $intent->status === 'completed' ? __('Done') : __('Wait') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-start">
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-tight">
                                            {{ $intent->created_at->format('M d, H:i') }}
                                        </div>
                                        <button type="button" wire:click="retryIntent({{ $intent->id }})" class="inline-flex items-center gap-2 text-[10px] font-black text-brand uppercase tracking-widest hover:text-brand-hover transition-colors">
                                            <x-heroicon-o-arrow-path class="size-3.5" />
                                            <span>{{ __('Task Retry') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-card/40 rounded-3xl p-16 text-center border border-dashed border-slate-200">
                                    <x-heroicon-o-bell class="size-12 text-slate-200 mx-auto mb-4" />
                                    <p class="text-slate-400 italic font-bold leading-relaxed">{{ __('No operational intents.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Desktop Table View for Intents --}}
                        <div class="hidden lg:block overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-stroke">
                                    <thead>
                                        <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                            <th class="px-8 py-5 text-start">{{ __('Task Intent') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('State') }}</th>
                                            <th class="px-8 py-5 text-start">{{ __('Timeline') }}</th>
                                            <th class="px-8 py-5 text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stroke text-start">
                                        @foreach($billingIntents as $intent)
                                            <tr wire:key="intent-desktop-{{ $intent->id }}" class="hover:bg-brand/5 transition-all">
                                                <td class="px-8 py-6">
                                                    <span class="text-sm font-black text-slate-900">{{ $intent->intent_type }}</span>
                                                </td>
                                                <td class="px-8 py-6">
                                                    @if($intent->status === 'completed')
                                                        <span class="inline-flex px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase">{{ __('Processed') }}</span>
                                                    @else
                                                        <span class="inline-flex px-3 py-1 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-black uppercase">{{ __('Pending') }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-8 py-6">
                                                    <span class="text-xs font-bold text-slate-500">{{ $intent->created_at->diffForHumans() }}</span>
                                                </td>
                                                <td class="px-8 py-6 text-end">
                                                    <button type="button" wire:click="retryIntent({{ $intent->id }})" class="text-xs font-black text-brand hover:text-brand-hover uppercase tracking-widest transition-all">
                                                        {{ __('Retry') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

@once
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://app.sumit.co.il/scripts/payments.js"></script>
@endonce

<script>
    (function initializeSystemAccountPaymentForms() {
        const singleUseTokenEventName = 'og:single-use-token';

        const extractSingleUseToken = (payload) => {
            if (! payload || typeof payload !== 'object') {
                return null;
            }

            if (typeof payload.SingleUseToken === 'string' && payload.SingleUseToken.trim() !== '') {
                return payload.SingleUseToken.trim();
            }

            if (payload.Data && typeof payload.Data === 'object') {
                if (typeof payload.Data.SingleUseToken === 'string' && payload.Data.SingleUseToken.trim() !== '') {
                    return payload.Data.SingleUseToken.trim();
                }
            }

            return null;
        };

        const dispatchSingleUseToken = (token) => {
            document.dispatchEvent(new CustomEvent(singleUseTokenEventName, {
                detail: { token },
            }));
        };

        const inspectPayloadForToken = (payload) => {
            const token = extractSingleUseToken(payload);

            if (token !== null) {
                dispatchSingleUseToken(token);
            }
        };

        if (! window.__sumitSingleUseTokenInterceptorRegistered) {
            if (typeof window.fetch === 'function') {
                const originalFetch = window.fetch.bind(window);

                window.fetch = async (...args) => {
                    const response = await originalFetch(...args);

                    try {
                        const url = String(args[0] instanceof Request ? args[0].url : args[0] ?? '');

                        if (url.includes('/creditguy/vault/tokenizesingleuse')) {
                            const clonedResponse = response.clone();
                            const contentType = clonedResponse.headers.get('content-type') ?? '';

                            if (contentType.includes('application/json')) {
                                inspectPayloadForToken(await clonedResponse.json());
                            } else {
                                inspectPayloadForToken(JSON.parse(await clonedResponse.text()));
                            }
                        }
                    } catch (_error) {
                    }

                    return response;
                };
            }

            if (typeof XMLHttpRequest !== 'undefined') {
                const originalOpen = XMLHttpRequest.prototype.open;
                const originalSend = XMLHttpRequest.prototype.send;

                XMLHttpRequest.prototype.open = function (method, url, async, username, password) {
                    this.__ogUrl = typeof url === 'string' ? url : '';

                    return originalOpen.call(this, method, url, async, username, password);
                };

                XMLHttpRequest.prototype.send = function (body) {
                    this.addEventListener('load', function () {
                        try {
                            const url = String(this.__ogUrl ?? '');

                            if (! url.includes('/creditguy/vault/tokenizesingleuse')) {
                                return;
                            }

                            if (typeof this.responseText !== 'string' || this.responseText.trim() === '') {
                                return;
                            }

                            inspectPayloadForToken(JSON.parse(this.responseText));
                        } catch (_error) {
                        }
                    });

                    return originalSend.call(this, body);
                };
            }

            window.__sumitSingleUseTokenInterceptorRegistered = true;
        }

        const getTokenInput = (form) => {
            return form.querySelector('input[name="og-token"]') || form.querySelector('input[data-og="token"]');
        };

        const setRuntimeError = (form, message) => {
            const errorElement = form.querySelector('[data-og-runtime-error]');

            if (! errorElement) {
                return;
            }

            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        };

        const clearRuntimeError = (form) => {
            const errorElement = form.querySelector('[data-og-runtime-error]');

            if (! errorElement) {
                return;
            }

            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        };

        const waitForToken = (form, callback) => {
            const deadline = Date.now() + 5000;
            let hasCalledBack = false;

            const poll = () => {
                if (hasCalledBack) return;

                const tokenInput = getTokenInput(form);
                const tokenValue = tokenInput?.value?.trim() ?? '';

                if (tokenValue !== '') {
                    hasCalledBack = true;
                    callback(null);
                    return;
                }

                if (Date.now() >= deadline) {
                    hasCalledBack = true;
                    callback(new Error('timeout'));
                    return;
                }

                window.setTimeout(poll, 50);
            };

            poll();
        };

        const bindPaymentForms = () => {
            document.querySelectorAll('[id^="sumit-payment-method-form-"]').forEach((form) => {
                if (form.dataset.ogBound === '1') {
                    return;
                }

                if (!window.jQuery || !window.OfficeGuy?.Payments) {
                    setTimeout(bindPaymentForms, 100);

                    return;
                }

                if (typeof OfficeGuy.Payments.InitEditors === 'function') {
                    OfficeGuy.Payments.InitEditors();
                }

                OfficeGuy.Payments.BindFormSubmit({
                    FormSelector: `#${form.id}`,
                    CompanyID: @json(config('officeguy.company_id')),
                    APIPublicKey: @json(config('officeguy.public_key')),
                    ResponseLanguage: @json(app()->getLocale()),
                });

                if (form.dataset.ogSubmitBound !== '1') {
                    form.addEventListener('submit', (event) => {
                        if (form.dataset.ogSubmitting === '1') {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            return;
                        }

                        event.preventDefault();
                        clearRuntimeError(form);

                        const submitButton = form.querySelector('[data-og-submit]');

                        if (submitButton instanceof HTMLButtonElement) {
                            submitButton.disabled = true;
                        }

                        waitForToken(form, (error) => {
                            if (error) {
                                setRuntimeError(form, @json(__('SUMIT did not return a payment token. Please verify the card details and try again.')));

                                if (submitButton instanceof HTMLButtonElement) {
                                    submitButton.disabled = false;
                                }

                                return;
                            }

                            if (form.dataset.ogSubmitting === '1') {
                                return;
                            }

                            form.dataset.ogSubmitting = '1';
                            
                            // Delay slightly to ensure value is committed
                            setTimeout(() => {
                                HTMLFormElement.prototype.submit.call(form);
                            }, 50);
                        });
                    });

                    form.dataset.ogSubmitBound = '1';
                }

                form.dataset.ogBound = '1';
            });
        };

        bindPaymentForms();

        if (! window.__sumitAccountPaymentFormsListenerRegistered) {
            document.addEventListener(singleUseTokenEventName, (event) => {
                const token = event.detail?.token;
                
                document.querySelectorAll('[id^="sumit-payment-method-form-"]').forEach((form) => {
                    const tokenInput = getTokenInput(form);

                    if (typeof token !== 'string' || token.trim() === '' || ! tokenInput) {
                        return;
                    }

                    tokenInput.value = token.trim();
                    tokenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    tokenInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            document.addEventListener('livewire:navigated', bindPaymentForms);
            window.__sumitAccountPaymentFormsListenerRegistered = true;
        }
    })();
</script>
