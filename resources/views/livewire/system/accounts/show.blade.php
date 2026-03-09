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

    {{-- Scoreboard Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-start">
        <div class="bg-card rounded-[2rem] shadow-brand border border-brand/20 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-brand transition-all duration-500">
            <span class="text-[10px] font-black text-brand uppercase tracking-[0.2em]">{{ __('Tenant Organizations') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-4xl font-black text-slate-900 tracking-tighter">{{ $account->organizations->count() }}</span>
                <div class="size-12 rounded-2xl bg-brand/5 flex items-center justify-center text-brand">
                    <x-heroicon-o-building-office-2 class="size-6" />
                </div>
            </div>
        </div>
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-indigo-300 transition-all duration-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Active Entitlements') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-4xl font-black text-slate-900 tracking-tighter">{{ $account->entitlements->count() }}</span>
                <div class="size-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
                    <x-heroicon-o-key class="size-6" />
                </div>
            </div>
        </div>
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-emerald-300 transition-all duration-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('SUMIT Identity') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-xl font-black text-slate-900 tracking-tight">{{ $account->sumit_customer_id ?: __('Unlinked') }}</span>
                <div class="size-12 rounded-2xl {{ $account->sumit_customer_id ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-50 text-slate-400' }} flex items-center justify-center">
                    <x-heroicon-o-credit-card class="size-6" />
                </div>
            </div>
        </div>
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-amber-300 transition-all duration-500">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Last Update') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-sm font-black text-slate-700 leading-tight">{{ $account->updated_at->diffForHumans() }}</span>
                <div class="size-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
                    <x-heroicon-o-clock class="size-6" />
                </div>
            </div>
        </div>
    </div>

    {{-- Main Glassmorphism Container --}}
    <div class="bg-card/90 rounded-[3rem] shadow-2xl shadow-slate-900/10 border border-white/50 overflow-hidden backdrop-blur-2xl">
        {{-- Navigation Tabs --}}
        <div class="px-6 sm:px-12 border-b border-slate-100 bg-slate-50/30 overflow-x-auto no-scrollbar">
            <nav class="flex gap-10 sm:gap-16 whitespace-nowrap" aria-label="Tabs">
                @foreach([
                    'overview' => __('Identity Overview'),
                    'organizations' => __('Tenant Linkage'),
                    'entitlements' => __('Resource Grants'),
                    'usage' => __('Operational Usage'),
                    'intents' => __('Billing Intents')
                ] as $key => $label)
                    <button type="button" wire:click="setTab('{{ $key }}')" class="relative py-6 sm:py-8 text-[11px] sm:text-xs font-black uppercase tracking-[0.25em] transition-all group {{ $activeTab === $key ? 'text-brand' : 'text-slate-400 hover:text-slate-600' }}">
                        {{ $label }}
                        @if($activeTab === $key)
                            <span class="absolute bottom-0 left-0 w-full h-1 bg-brand rounded-t-full animate-in slide-in-from-bottom-1"></span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="p-6 sm:p-12 text-start">
            {{-- Tab: Overview --}}
            @if($activeTab === 'overview')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 animate-in fade-in duration-500">
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
                    <div class="lg:col-span-5">
                        <div class="bg-slate-50/50 rounded-[2.5rem] border border-slate-100 p-8 space-y-8 shadow-inner">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Governance Summary') }}</h3>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4 group">
                                    <div class="size-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-colors border border-stroke">
                                        <x-heroicon-o-shield-check class="size-6" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase leading-none mb-1.5">{{ __('Active Tenants') }}</p>
                                        <p class="text-sm font-black text-slate-900 leading-none">{{ $account->organizations->count() }} {{ __('Orgs linked') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 group">
                                    <div class="size-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-emerald-600 transition-colors border border-stroke">
                                        <x-heroicon-o-banknotes class="size-6" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase leading-none mb-1.5">{{ __('Fiscal State') }}</p>
                                        <p class="text-sm font-black text-slate-900 leading-none">{{ $account->sumit_customer_id ? __('External Sync Active') : __('Local Billing Only') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab: Organizations --}}
            @if($activeTab === 'organizations')
                <div class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2 text-start">
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Tenant Linkage') }}</h2>
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('Attach or detach tenant organizations to this master billing entity.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                        <table class="min-w-full divide-y divide-stroke">
                            <thead>
                                <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 text-start">{{ __('Organization Entity') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('UID') }}</th>
                                    <th class="px-8 py-5 text-end"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke text-start">
                                @forelse($organizationsAttached as $org)
                                    <tr wire:key="org-{{ $org->id }}" class="hover:bg-brand/5 transition-all group">
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
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-8 py-20 text-center text-slate-400 italic font-bold">
                                            {{ __('No organizations currently linked to this billing account.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Quick Link Section --}}
                    <div class="p-8 sm:p-10 rounded-[2.5rem] bg-slate-50 border border-slate-100 shadow-inner">
                        <h3 class="text-xs font-black text-indigo-900 uppercase tracking-[0.2em] mb-6">{{ __('Connect Existing Tenant') }}</h3>
                        <div class="flex flex-col md:flex-row gap-4">
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
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2 text-start">
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ __('Resource Grants') }}</h2>
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ __('Control the quotas and features available to this billing account.') }}</p>
                        </div>
                        <button wire:click="openCreateEntitlement" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand text-white font-black rounded-2xl hover:bg-brand-hover shadow-lg shadow-brand/20 transition-all active:scale-95 cursor-pointer">
                            <x-heroicon-o-plus class="size-5" />
                            {{ __('Add New Grant') }}
                        </button>
                    </div>

                    @if($showEntitlementForm)
                        <div class="p-8 sm:p-10 rounded-[2.5rem] bg-indigo-50/50 border border-indigo-100 shadow-inner animate-in zoom-in-95 duration-300">
                            <h3 class="text-xs font-black text-indigo-900 uppercase tracking-[0.2em] mb-8">{{ $editingEntitlementId ? __('Edit Feature Grant') : __('Create New Feature Grant') }}</h3>
                            <form wire:submit.prevent="saveEntitlement" class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <div class="md:col-span-5 space-y-2">
                                    <x-input-label for="e-key" :value="__('Feature Key')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                    <input id="e-key" type="text" wire:model="entitlement_feature_key" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" placeholder="e.g. max_guests" />
                                </div>
                                <div class="md:col-span-3 space-y-2">
                                    <x-input-label for="e-val" :value="__('Value')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                    <input id="e-val" type="text" wire:model="entitlement_value" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" placeholder="e.g. 500" />
                                </div>
                                <div class="md:col-span-4 space-y-2 text-start">
                                    <x-input-label for="e-exp" :value="__('Expiry Date (Optional)')" class="text-[10px] font-black text-indigo-900/50 uppercase px-1" />
                                    <input id="e-exp" type="date" wire:model="entitlement_expires_at" class="block w-full px-6 py-4 rounded-2xl bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl" />
                                </div>
                                <div class="md:col-span-12 flex gap-3 pt-4">
                                    <button type="submit" class="grow py-4 bg-brand text-white font-black rounded-2xl shadow-xl shadow-brand/20 hover:bg-brand-hover transition-all active:scale-95 cursor-pointer">
                                        {{ __('Authorize Grant') }}
                                    </button>
                                    <button type="button" wire:click="cancelEntitlement" class="px-10 py-4 bg-white border border-slate-200 text-slate-500 font-bold rounded-2xl hover:bg-slate-50 transition-all cursor-pointer">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                        <table class="min-w-full divide-y divide-stroke">
                            <thead>
                                <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 text-start">{{ __('Operational Feature') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Grant Value') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Timeline Expiry') }}</th>
                                    <th class="px-8 py-5 text-end"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke text-start">
                                @forelse($entitlements as $e)
                                    <tr wire:key="ent-{{ $e->id }}" class="hover:bg-brand/5 transition-all group">
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
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-8 py-20 text-center text-slate-400 italic font-bold">
                                            {{ __('No active resource grants found for this account.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

                    <div class="overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                        <table class="min-w-full divide-y divide-stroke">
                            <thead>
                                <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 text-start">{{ __('Resource Key') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Billing Period') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Metered Count') }}</th>
                                    <th class="px-8 py-5 text-end">{{ __('Last Logged') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke text-start">
                                @forelse($usage as $u)
                                    <tr wire:key="use-{{ $u->id }}" class="hover:bg-slate-50 transition-all">
                                        <td class="px-8 py-6 font-bold text-slate-900">{{ $u->feature_key }}</td>
                                        <td class="px-8 py-6">
                                            <span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-black uppercase">{{ $u->period_key }}</span>
                                        </td>
                                        <td class="px-8 py-6 font-black text-lg text-slate-900">{{ $u->usage_count }}</td>
                                        <td class="px-8 py-6 text-end text-xs font-medium text-slate-400">
                                            {{ $u->updated_at->format('M d, H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-8 py-20 text-center text-slate-400 italic font-bold">
                                            {{ __('No metered usage records found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

                    <div class="overflow-hidden rounded-[2.5rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card text-start">
                        <table class="min-w-full divide-y divide-stroke">
                            <thead>
                                <tr class="bg-surface text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 text-start">{{ __('Operational ID') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Lifecycle Status') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Intent / Asset') }}</th>
                                    <th class="px-8 py-5 text-end">{{ __('Created At') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke">
                                @forelse($billingIntents as $intent)
                                    <tr wire:key="intent-{{ $intent->id }}" class="hover:bg-slate-50 transition-all">
                                        <td class="px-8 py-6 font-bold text-slate-900">#{{ $intent->id }}</td>
                                        <td class="px-8 py-6">
                                            <span class="inline-flex px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest {{ $intent->status === 'completed' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                                                {{ $intent->status }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="text-sm font-bold text-slate-700">{{ $intent->intent_type ?: 'Manual' }}</div>
                                            <div class="text-[10px] text-slate-400 uppercase font-black mt-1">{{ $intent->payable_type ? (new ReflectionClass($intent->payable_type))->getShortName() . ' #' . $intent->payable_id : 'No Asset' }}</div>
                                        </td>
                                        <td class="px-8 py-6 text-end text-xs font-medium text-slate-400">
                                            {{ $intent->created_at->format('M d, Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-8 py-20 text-center text-slate-400 italic font-bold">
                                            {{ __('No billing intents found for this entity.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
