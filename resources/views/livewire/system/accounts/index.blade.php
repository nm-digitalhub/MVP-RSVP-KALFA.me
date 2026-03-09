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
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-6 flex flex-col justify-between group hover:border-brand/30 transition-all">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Total Accounts') }}</span>
            <div class="mt-4 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $stats['total'] }}</span>
                <div class="size-12 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 transition-transform">
                    <x-heroicon-o-users class="size-6" />
                </div>
            </div>
        </div>

        {{-- Organizations --}}
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-6 flex flex-col justify-between group hover:border-indigo-300 transition-all">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Organizations') }}</span>
            <div class="mt-4 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $stats['organizations'] }}</span>
                <div class="size-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-building-office-2 class="size-6" />
                </div>
            </div>
        </div>

        {{-- Individuals --}}
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-6 flex flex-col justify-between group hover:border-emerald-300 transition-all">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Individuals') }}</span>
            <div class="mt-4 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $stats['individuals'] }}</span>
                <div class="size-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-user class="size-6" />
                </div>
            </div>
        </div>

        {{-- New This Week --}}
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-6 flex flex-col justify-between group hover:border-amber-300 transition-all">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('New This Week') }}</span>
            <div class="mt-4 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $stats['new_this_week'] }}</span>
                <div class="size-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-sparkles class="size-6" />
                </div>
            </div>
        </div>
    </div>

    {{-- Advanced Filters --}}
    <section class="bg-surface/50 rounded-[2.5rem] border border-stroke p-8 shadow-inner animate-in fade-in slide-in-from-bottom-8 duration-700 delay-200">
        <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 space-y-2">
                <x-input-label for="filter-name" :value="__('Search Account')" class="text-[10px] font-black text-content-muted uppercase tracking-tighter px-1" />
                <div class="relative group">
                    <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none transition-colors group-focus-within:text-brand text-content-muted">
                        <x-heroicon-o-magnifying-glass class="size-5" />
                    </div>
                    <input id="filter-name" type="text" wire:model.live.debounce.300ms="search_name" class="block w-full ps-12 py-4 rounded-2xl bg-card border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-sm placeholder:font-medium placeholder:text-content-muted/60" placeholder="{{ __('Name, ID or external ref...') }}" />
                </div>
            </div>
            <div class="lg:col-span-2 space-y-2">
                <x-input-label for="filter-type" :value="__('Type')" class="text-[10px] font-black text-content-muted uppercase tracking-tighter px-1" />
                <select id="filter-type" wire:model.live.debounce.300ms="search_type" class="block w-full py-4 rounded-2xl bg-card border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-sm appearance-none cursor-pointer">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="organization">{{ __('Organization') }}</option>
                    <option value="individual">{{ __('Individual') }}</option>
                </select>
            </div>
            <div class="lg:col-span-3 space-y-2">
                <x-input-label for="filter-owner" :value="__('Owner ID')" class="text-[10px] font-black text-content-muted uppercase tracking-tighter px-1" />
                <input id="filter-owner" type="number" wire:model.live.debounce.300ms="search_owner_user_id" class="block w-full py-4 rounded-2xl bg-card border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-sm placeholder:font-medium placeholder:text-content-muted/60" placeholder="{{ __('User ID...') }}" />
            </div>
            <div class="lg:col-span-3 space-y-2">
                <x-input-label for="filter-sumit" :value="__('SUMIT ID')" class="text-[10px] font-black text-content-muted uppercase tracking-tighter px-1" />
                <input id="filter-sumit" type="number" wire:model.live.debounce.300ms="search_sumit_customer_id" class="block w-full py-4 rounded-2xl bg-card border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-sm placeholder:font-medium placeholder:text-content-muted/60" placeholder="{{ __('External ID...') }}" />
            </div>
        </form>
    </section>

    {{-- Accounts Table --}}
    <div class="bg-card rounded-[2.5rem] shadow-xl shadow-slate-900/5 border border-stroke overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-300">
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
                    @forelse($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}" class="group hover:bg-surface transition-all duration-300">
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex items-center gap-5">
                                    <div class="size-14 rounded-[1.25rem] bg-gradient-to-br {{ $account->type === 'organization' ? 'from-indigo-500 to-purple-600' : 'from-emerald-500 to-teal-600' }} flex items-center justify-center text-white font-black text-xl shadow-lg group-hover:rotate-3 transition-transform duration-300">
                                        {{ substr($account->name ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('system.accounts.show', $account) }}" class="text-lg font-black text-content hover:text-brand transition-colors leading-none block mb-1 underline-offset-4 hover:underline">{{ $account->name ?: __('Unnamed Account') }}</a>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-content-muted uppercase">ID: #{{ $account->id }}</span>
                                            <span class="text-[10px] text-stroke">•</span>
                                            <span class="text-[10px] font-bold text-content-muted uppercase">{{ $account->created_at->format('M d') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
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
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-content leading-none">{{ $account->owner?->name ?? '—' }}</span>
                                    @if($account->owner)
                                        <span class="text-xs font-medium text-content-muted mt-1.5">ID: {{ $account->owner_user_id }}</span>
                                    @elseif($account->owner_user_id)
                                        <span class="text-xs font-medium text-warning mt-1.5">{{ __('User ID') }}: {{ $account->owner_user_id }} ({{ __('Missing') }})</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
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
                                            <div class="size-8 rounded-full ring-2 ring-white bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500" title="{{ $org->name }}">
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
                                <a href="{{ route('system.accounts.show', $account) }}" class="size-10 rounded-xl bg-surface text-content-muted hover:bg-brand hover:text-white flex items-center justify-center transition-all shadow-sm active:scale-90 ml-auto" aria-label="{{ __('View Details') }}">
                                    <x-heroicon-o-chevron-right class="size-5" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="max-w-sm mx-auto">
                                    <div class="size-24 bg-surface rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner ring-1 ring-stroke">
                                        <x-heroicon-o-document-magnifying-glass class="size-12 text-content-muted/30" />
                                    </div>
                                    <h3 class="text-xl font-black text-content leading-tight">{{ __('No Accounts Found') }}</h3>
                                    <p class="text-content-muted font-medium mt-2 leading-relaxed">{{ __('Try adjusting your filters or create a new account to get started.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 border-t border-stroke bg-surface/30">
            {{ $accounts->links() }}
        </div>
    </div>
</div>
