<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10" role="main" aria-label="{{ __('System Accounts Management') }}">
    {{-- Header Section --}}
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-black text-content tracking-tight leading-none">{{ __('Billing Accounts') }}</h1>
            <p class="mt-3 text-lg text-content-muted font-medium leading-relaxed">{{ __('Centralized management of billing entities, customer profiles, and subscriptions.') }}</p>
        </div>
        <div class="flex items-center gap-4 shrink-0">
            <a href="{{ route('system.accounts.create') }}" class="group inline-flex items-center justify-center gap-3 px-6 py-3 bg-brand text-white font-black rounded-2xl hover:bg-brand-hover shadow-lg shadow-brand/25 transition-all active:scale-95">
                <x-heroicon-o-plus class="size-5 group-hover:rotate-90 transition-transform" />
                <span class="text-base">{{ __('New Account') }}</span>
            </a>
        </div>
    </header>

    {{-- Scoreboard Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-in slide-in-from-bottom-4 duration-700 delay-100">
        {{-- Total Accounts --}}
        <div class="bg-card/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 flex flex-col justify-between group hover:border-brand/30 hover:shadow-brand transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.25em] leading-none">{{ __('Total Accounts') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-content tracking-tighter leading-none">{{ $stats['total'] }}</span>
                <div class="size-14 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-sm border border-brand/10">
                    <x-heroicon-o-users class="size-7" />
                </div>
            </div>
        </div>

        {{-- Organizations --}}
        <div class="bg-card/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 flex flex-col justify-between group hover:border-indigo-300 hover:shadow-indigo-500/10 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.25em] leading-none">{{ __('Organizations') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-content tracking-tighter leading-none">{{ $stats['organizations'] }}</span>
                <div class="size-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-500 shadow-sm border border-indigo-100">
                    <x-heroicon-o-building-office-2 class="size-7" />
                </div>
            </div>
        </div>

        {{-- Individuals --}}
        <div class="bg-card/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 flex flex-col justify-between group hover:border-emerald-300 hover:shadow-emerald-500/10 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.25em] leading-none">{{ __('Individuals') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-content tracking-tighter leading-none">{{ $stats['individuals'] }}</span>
                <div class="size-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-sm border border-emerald-100">
                    <x-heroicon-o-user class="size-7" />
                </div>
            </div>
        </div>

        {{-- New This Week --}}
        <div class="bg-card/80 backdrop-blur-xl rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-white/50 p-8 flex flex-col justify-between group hover:border-amber-300 hover:shadow-amber-500/10 transition-all duration-500 active:scale-[0.98]">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.25em] leading-none">{{ __('New This Week') }}</span>
            <div class="mt-8 flex items-end justify-between">
                <span class="text-5xl font-black text-content tracking-tighter leading-none">{{ $stats['new_this_week'] }}</span>
                <div class="size-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-500 shadow-sm border border-amber-100">
                    <x-heroicon-o-sparkles class="size-7" />
                </div>
            </div>
        </div>
    </div>

    <section class="bg-card/40 backdrop-blur-xl rounded-[3rem] border border-white/50 p-6 sm:p-10 shadow-2xl shadow-slate-900/5 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-200">
        <form wire:submit.prevent class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-5 sm:gap-8">
            <div class="lg:col-span-4 space-y-2.5">
                <x-input-label for="filter-name" :value="__('Search Account')" class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em] px-2" />
                <div class="relative group">
                    <div class="absolute inset-y-0 start-0 ps-5 flex items-center pointer-events-none transition-colors group-focus-within:text-brand text-content-muted">
                        <x-heroicon-o-magnifying-glass class="size-5" />
                    </div>
                    <input id="filter-name" type="text" wire:model.live.debounce.300ms="search_name" class="block w-full ps-12 py-4 rounded-[1.5rem] bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 placeholder:font-medium placeholder:text-content-muted/50" placeholder="{{ __('Name, ID or external ref...') }}" />
                </div>
            </div>
            <div class="lg:col-span-2 space-y-2.5">
                <x-input-label for="filter-type" :value="__('Type')" class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em] px-2" />
                <select id="filter-type" wire:model.live.debounce.300ms="search_type" class="block w-full px-5 py-4 rounded-[1.5rem] bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 appearance-none cursor-pointer">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="organization">{{ __('Organization') }}</option>
                    <option value="individual">{{ __('Individual') }}</option>
                </select>
            </div>
            <div class="lg:col-span-3 space-y-2.5">
                <x-input-label for="filter-owner" :value="__('Owner ID')" class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em] px-2" />
                <input id="filter-owner" type="number" wire:model.live.debounce.300ms="search_owner_user_id" class="block w-full px-6 py-4 rounded-[1.5rem] bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 placeholder:font-medium placeholder:text-content-muted/50" placeholder="{{ __('User ID...') }}" />
            </div>
            <div class="lg:col-span-3 space-y-2.5">
                <x-input-label for="filter-sumit" :value="__('SUMIT ID')" class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em] px-2" />
                <input id="filter-sumit" type="number" wire:model.live.debounce.300ms="search_sumit_customer_id" class="block w-full px-6 py-4 rounded-[1.5rem] bg-white border-transparent focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-xl shadow-slate-900/5 placeholder:font-medium placeholder:text-content-muted/50" placeholder="{{ __('External ID...') }}" />
            </div>
        </form>
    </section>

    {{-- Accounts Data --}}
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-300">
        {{-- Mobile Card View --}}
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @forelse($accounts as $account)
                <div wire:key="account-mobile-{{ $account->id }}" class="bg-card rounded-3xl p-5 border border-stroke shadow-xl shadow-slate-900/5 active:scale-[0.98] transition-all relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $account->type === 'organization' ? 'from-indigo-600/5 to-purple-600/5' : 'from-emerald-600/5 to-teal-600/5' }} rounded-full -mr-16 -mt-16 blur-3xl"></div>
                    
                    <div class="flex items-start gap-4 text-start">
                        <div class="size-14 rounded-2xl bg-gradient-to-br {{ $account->type === 'organization' ? 'from-indigo-500 to-purple-600' : 'from-emerald-500 to-teal-600' }} flex items-center justify-center text-white font-black text-xl shadow-lg shrink-0 transition-transform group-hover:scale-105">
                            {{ substr($account->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('system.accounts.show', $account) }}" class="text-lg font-black text-content block truncate pr-8 group-hover:text-brand transition-colors">{{ $account->name ?: __('Unnamed Account') }}</a>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="text-[10px] font-bold text-content-muted uppercase tracking-tight">#{{ $account->id }}</span>
                                <span class="text-stroke text-[10px]">•</span>
                                @if($account->type === 'organization')
                                    <span class="text-[10px] font-black uppercase text-indigo-600 tracking-widest">{{ __('Org') }}</span>
                                @else
                                    <span class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">{{ __('Ind') }}</span>
                                @endif
                            </div>
                            
                            <div class="mt-4 flex flex-col gap-2">
                                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-surface/50 border border-stroke/50">
                                    <x-heroicon-o-user class="size-3.5 text-content-muted" />
                                    <span class="text-xs font-bold text-content-muted truncate">{{ $account->owner?->name ?? '—' }}</span>
                                </div>
                                @if($account->sumit_customer_id)
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-success/5 border border-success/10 text-success">
                                        <div class="size-1.5 rounded-full bg-success animate-pulse"></div>
                                        <span class="text-[10px] font-black font-mono tracking-tight uppercase">SUMIT: {{ $account->sumit_customer_id }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <a href="{{ route('system.accounts.show', $account) }}" class="absolute top-5 end-5 size-10 rounded-[1.25rem] bg-surface border border-stroke flex items-center justify-center text-content-muted group-hover:bg-brand group-hover:text-white transition-all shadow-sm active:scale-90" aria-label="{{ __('View Details') }}">
                            <x-heroicon-m-chevron-right class="size-5 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
            @empty
                <div class="bg-card rounded-3xl p-12 text-center border border-stroke">
                    <x-heroicon-o-document-magnifying-glass class="size-12 text-content-muted/30 mx-auto mb-4" />
                    <h3 class="text-lg font-black text-content">{{ __('No Accounts Found') }}</h3>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden md:block bg-card rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-stroke overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="min-w-full divide-y divide-stroke">
                    <thead>
                        <tr class="bg-surface/50">
                            <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Account Entity') }}</th>
                            <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Type') }}</th>
                            <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Ownership') }}</th>
                            <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Integrations') }}</th>
                            <th scope="col" class="px-8 py-5 text-center text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Links') }}</th>
                            <th scope="col" class="px-8 py-5 text-end text-[10px] font-black text-content-muted uppercase tracking-[0.2em]"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stroke bg-card">
                        @foreach($accounts as $account)
                            <tr wire:key="account-desktop-{{ $account->id }}" class="group hover:bg-surface transition-all duration-300">
                                <td class="px-8 py-6 whitespace-nowrap text-start">
                                    <div class="flex items-center gap-5">
                                        <div class="size-14 rounded-[1.25rem] bg-gradient-to-br {{ $account->type === 'organization' ? 'from-indigo-500 to-purple-600' : 'from-emerald-500 to-teal-600' }} flex items-center justify-center text-white font-black text-xl shadow-lg group-hover:rotate-3 transition-transform duration-300">
                                            {{ substr($account->name ?? 'A', 0, 1) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('system.accounts.show', $account) }}" class="text-lg font-black text-content hover:text-brand transition-colors leading-none block mb-1 underline-offset-4 hover:underline">{{ $account->name ?: __('Unnamed Account') }}</a>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-content-muted uppercase">ID: #{{ $account->id }}</span>
                                                <span class="text-[10px] text-stroke">•</span>
                                                <span class="text-[10px] font-bold text-content-muted uppercase">{{ $account->created_at->format('d.m.Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-start">
                                    @if($account->type === 'organization')
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/50">
                                            <x-heroicon-o-building-office-2 class="size-3.5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Organization') }}</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50">
                                            <x-heroicon-o-user class="size-3.5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Individual') }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-start">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-content leading-none">{{ $account->owner?->name ?? '—' }}</span>
                                        @if($account->owner)
                                            <span class="text-xs font-medium text-content-muted mt-1.5">ID: {{ $account->owner_user_id }}</span>
                                        @elseif($account->owner_user_id)
                                            <span class="text-xs font-medium text-warning mt-1.5">{{ __('User ID') }}: {{ $account->owner_user_id }} ({{ __('Missing') }})</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-start">
                                    @if($account->sumit_customer_id)
                                        <div class="flex items-center gap-2 text-content">
                                            <div class="size-2 rounded-full bg-success animate-pulse"></div>
                                            <span class="text-xs font-bold font-mono">SUMIT: {{ $account->sumit_customer_id }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-content-muted/50 italic">{{ __('Not Connected') }}</span>
                                    @endif
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-center">
                                    @if($account->organizations->isNotEmpty())
                                        <div class="inline-flex -space-x-2 overflow-hidden">
                                            @foreach($account->organizations->take(3) as $org)
                                                <div wire:key="acc-org-{{ $account->id }}-{{ $org->id }}" class="size-8 rounded-full ring-2 ring-white bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500" title="{{ $org->name }}">
                                                    {{ substr($org->name, 0, 1) }}
                                                </div>
                                            @endforeach
                                            @if($account->organizations->count() > 3)
                                                <div class="size-8 rounded-full ring-2 ring-white bg-slate-50 flex items-center justify-center text-[10px] font-bold text-slate-400">
                                                    +{{ $account->organizations->count() - 3 }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-content-muted/50">—</span>
                                    @endif
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-end">
                                    <a href="{{ route('system.accounts.show', $account) }}" class="size-10 rounded-xl bg-surface text-content-muted hover:bg-brand hover:text-white flex items-center justify-center transition-all shadow-sm active:scale-90 ms-auto" aria-label="{{ __('View Details') }}">
                                        <x-heroicon-m-chevron-right class="size-5 rtl:rotate-180" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        @if($accounts->isEmpty())
                            <tr>
                                <td colspan="6" class="px-8 py-24 text-center">
                                    <div class="max-w-sm mx-auto text-center">
                                        <div class="size-24 bg-surface rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner ring-1 ring-stroke">
                                            <x-heroicon-o-document-magnifying-glass class="size-12 text-content-muted/30" />
                                        </div>
                                        <h3 class="text-xl font-black text-content leading-tight">{{ __('No Accounts Found') }}</h3>
                                        <p class="text-content-muted font-medium mt-2 leading-relaxed">{{ __('Try adjusting your filters or create a new account to get started.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 sm:px-8 sm:py-6 rounded-[2rem] border border-stroke bg-surface/30">
            {{ $accounts->links() }}
        </div>
    </div>
</div>
