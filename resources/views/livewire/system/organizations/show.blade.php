<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12 space-y-8 animate-in fade-in duration-700" role="main" aria-label="{{ __('Organization Administration') }}">
    {{-- High-End Header & Breadcrumbs --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
        <div class="space-y-4 flex-1 min-w-0">
            <nav class="flex overflow-x-auto no-scrollbar pb-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 rtl:space-x-reverse text-[10px] font-black uppercase tracking-[0.2em] text-content-muted whitespace-nowrap">
                    <li><a href="{{ route('system.dashboard') }}" class="hover:text-brand transition-colors">{{ __('System') }}</a></li>
                    <li><x-heroicon-m-chevron-right class="size-3 shrink-0" /></li>
                    <li><a href="{{ route('system.organizations.index') }}" class="hover:text-brand transition-colors">{{ __('Organizations') }}</a></li>
                    <li><x-heroicon-m-chevron-right class="size-3 shrink-0" /></li>
                    <li class="text-content truncate" aria-current="page">{{ $organization->name }}</li>
                </ol>
            </nav>
            <div class="flex flex-wrap items-center gap-4">
                <h1 class="text-4xl sm:text-5xl font-black text-content tracking-tighter leading-none break-words">{{ $organization->name }}</h1>
                <div class="flex items-center gap-2">
                    @if($organization->is_suspended)
                        <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black bg-danger/10 text-danger ring-1 ring-danger/20 uppercase tracking-widest animate-pulse">{{ __('Suspended') }}</span>
                    @else
                        <span class="inline-flex px-3 py-1.5 rounded-xl text-[10px] font-black bg-success/10 text-success ring-1 ring-success/20 uppercase tracking-widest">{{ __('Active Tenant') }}</span>
                    @endif
                </div>
            </div>
            <p class="text-sm sm:text-lg text-content-muted font-medium italic">
                UUID: <span class="font-bold text-content">#{{ $organization->id }}</span> • {{ __('Onboarded') }} {{ $organization->created_at->format('d.m.Y') }}
            </p>
        </div>
        <div class="flex shrink-0">
            <form action="{{ route('system.impersonate', $organization) }}" method="POST" class="w-full">
                @csrf
                <button type="submit" class="group w-full inline-flex items-center justify-center gap-3 px-8 py-4 bg-warning text-white font-black rounded-[1.5rem] hover:bg-warning/90 shadow-lg shadow-warning/20 transition-all active:scale-95 cursor-pointer min-h-[60px]">
                    <x-heroicon-o-user-circle class="size-6 group-hover:rotate-12 transition-transform" />
                    <span class="text-lg">{{ __('Impersonate') }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Platform Scoreboard - Primary vs Secondary Hierarchy --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-start">
        {{-- Team Size - Primary --}}
        <div class="bg-card rounded-[2rem] shadow-brand border border-brand/20 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-brand transition-all duration-500">
            <span class="text-[10px] font-black text-brand uppercase tracking-[0.2em]">{{ __('Team Size') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $membersCount }}</span>
                <div class="size-12 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 transition-transform">
                    <x-heroicon-o-users class="size-6" />
                </div>
            </div>
        </div>

        {{-- Events - Primary --}}
        <div class="bg-card rounded-[2rem] shadow-brand border border-brand/20 p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-brand transition-all duration-500">
            <span class="text-[10px] font-black text-brand uppercase tracking-[0.2em]">{{ __('Total Events') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-4xl font-black text-content tracking-tighter">{{ $organization->events()->count() }}</span>
                <div class="size-12 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 transition-transform">
                    <x-heroicon-o-calendar-days class="size-6" />
                </div>
            </div>
        </div>

        {{-- Billing Link - Contextual Color --}}
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-success/30 transition-all duration-500">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Billing Link') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <div>
                    @if($organization->account)
                        <span class="inline-flex px-2 py-1 rounded-lg bg-success/10 text-success text-[10px] font-black uppercase mb-2">{{ __('Linked') }}</span>
                        <a href="{{ route('system.accounts.show', $organization->account) }}" class="block text-lg font-black text-content hover:text-brand transition-colors underline decoration-brand/30 underline-offset-4">{{ $organization->account->name ?: __('Profile') }}</a>
                    @else
                        <span class="inline-flex px-2 py-1 rounded-lg bg-danger/10 text-danger text-[10px] font-black uppercase mb-2">{{ __('Missing') }}</span>
                        <span class="block text-sm font-bold text-content-muted italic">{{ __('No Account') }}</span>
                    @endif
                </div>
                <div class="size-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-success/5 group-hover:text-success transition-all">
                    <x-heroicon-o-credit-card class="size-6" />
                </div>
            </div>
        </div>

        {{-- Activity - Secondary --}}
        <div class="bg-card rounded-[2rem] shadow-xl shadow-slate-900/5 border border-stroke p-8 backdrop-blur-xl flex flex-col justify-between group hover:border-warning/30 transition-all duration-500 text-start">
            <span class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Last Activity') }}</span>
            <div class="mt-6 flex items-end justify-between">
                <span class="text-xl font-black text-content tracking-tighter">{{ $organization->updated_at->diffForHumans(short: true) }}</span>
                <div class="size-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-warning/5 group-hover:text-warning transition-all">
                    <x-heroicon-o-bolt class="size-6" />
                </div>
            </div>
        </div>
    </div>

    {{-- Main Glassmorphism Container --}}
    <div class="bg-card/90 rounded-[3rem] shadow-2xl shadow-slate-900/10 border border-white/50 overflow-hidden backdrop-blur-2xl">
        {{-- Tabs Navigation --}}
        <div class="px-6 sm:px-12 border-b border-stroke bg-surface/30 overflow-x-auto no-scrollbar">
            <nav class="flex gap-10 sm:gap-16 whitespace-nowrap" aria-label="Tabs">
                @foreach(['team' => __('Team & Governance'), 'events' => __('Event Ecosystem'), 'billing' => __('Billing & Subscription'), 'admin' => __('Infrastructure Control')] as $key => $label)
                    <button type="button" wire:click="setTab('{{ $key }}')" class="relative py-6 sm:py-8 text-[11px] sm:text-xs font-black uppercase tracking-[0.25em] transition-all group {{ $activeTab === $key ? 'text-brand' : 'text-content-muted hover:text-content' }}">
                        {{ $label }}
                        @if($activeTab === $key)
                            <span class="absolute bottom-0 left-0 w-full h-1 bg-brand rounded-t-full animate-in slide-in-from-bottom-1"></span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="p-6 sm:p-12 text-start">
            @if($activeTab === 'team')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    <div class="lg:col-span-8 space-y-12">
                        {{-- Members Table --}}
                        <div class="space-y-6">
                            <h2 class="text-2xl font-black text-content tracking-tight">{{ __('Active Membership') }}</h2>
                            <div class="overflow-hidden rounded-[2rem] border border-stroke shadow-inner bg-surface/50">
                                <table class="min-w-full divide-y divide-stroke">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-black text-content-muted uppercase tracking-widest">
                                            <th class="px-8 py-5 text-start min-w-[200px]">{{ __('Identity') }}</th>
                                            <th class="px-8 py-5 text-start min-w-[150px]">{{ __('Permissions') }}</th>
                                            <th class="px-8 py-5 text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stroke bg-transparent text-sm">
                                        @foreach($members as $m)
                                            <tr wire:key="member-{{ $m->id }}" class="hover:bg-card transition-all group">
                                                <td class="px-8 py-6">
                                                    <div class="font-black text-content group-hover:text-brand transition-colors">{{ $m->name }}</div>
                                                    <div class="text-xs font-bold text-content-muted mt-1">{{ $m->email }}</div>
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap text-start">
                                                    <span class="inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter {{ $m->pivot->role->value === 'owner' ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'bg-slate-100 text-slate-500' }}">
                                                        {{ __($m->pivot->role->value) }}
                                                    </span>
                                                </td>
                                                <td class="px-8 py-6 text-end">
                                                    @if($m->pivot->role->value !== 'owner')
                                                        <button type="button" wire:click="removeMemberDirect({{ $m->id }})" wire:confirm="{{ __('Remove user from organization?') }}" class="size-10 rounded-xl hover:bg-danger/10 text-danger/60 hover:text-danger flex items-center justify-center transition-all ml-auto">
                                                            <x-heroicon-o-user-minus class="size-5" />
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Modern Add Form - Primary Action Style --}}
                        <div class="p-8 sm:p-12 rounded-[3rem] bg-gradient-to-br from-brand/5 to-card border border-brand/10 shadow-inner">
                            <h3 class="text-xs font-black text-brand uppercase tracking-[0.2em] mb-8">{{ __('Onboard Platform User') }}</h3>
                            <form wire:submit.prevent="addMemberDirect" class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <div class="md:col-span-7">
                                    <select wire:model="directAddUserId" class="w-full rounded-2xl border-transparent bg-card py-4 px-6 text-sm font-black shadow-xl ring-1 ring-brand/10 focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all appearance-none cursor-pointer">
                                        <option value="">{{ __('Search Userbase...') }}</option>
                                        @foreach($allUsers as $u)
                                            <option wire:key="user-opt-{{ $u->id }}" value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <select wire:model="directAddRole" class="w-full rounded-2xl border-transparent bg-card py-4 px-6 text-sm font-black shadow-xl ring-1 ring-brand/10 focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all appearance-none cursor-pointer">
                                        <option value="member">{{ __('Member') }}</option>
                                        <option value="admin">{{ __('Admin') }}</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="w-full h-full flex items-center justify-center bg-brand text-white rounded-2xl hover:bg-brand-hover shadow-xl shadow-brand/20 transition-all active:scale-90 cursor-pointer min-h-[56px]">
                                        <x-heroicon-o-plus class="size-6" />
                                    </button>
                                </div>
                            </form>
                            <x-input-error :messages="$errors->get('directAddUserId')" class="mt-4 px-4 font-bold text-xs text-danger" />
                        </div>
                    </div>

                    {{-- Context Sidebar --}}
                    <aside class="lg:col-span-4 space-y-8 text-start">
                        <div class="bg-surface/50 rounded-[2.5rem] border border-stroke p-8 space-y-8 shadow-inner">
                            <h3 class="text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">{{ __('Governance Data') }}</h3>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4 group">
                                    <div class="size-12 rounded-2xl bg-card shadow-sm flex items-center justify-center text-content-muted group-hover:text-brand transition-colors border border-stroke">
                                        <x-heroicon-o-shield-check class="size-6" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-content-muted uppercase leading-none mb-1.5">{{ __('Primary Admin') }}</p>
                                        <p class="text-sm font-black text-content leading-none truncate max-w-[180px]">{{ $owner?->name }}</p>
                                    </div>
                                </div>
                                @if($organization->account)
                                    <div class="flex items-center gap-4 group">
                                        <div class="size-12 rounded-2xl bg-card shadow-sm flex items-center justify-center text-content-muted group-hover:text-success transition-colors border border-stroke">
                                            <x-heroicon-o-building-library class="size-6" />
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-content-muted uppercase leading-none mb-1.5">{{ __('Fiscal Profile') }}</p>
                                            <a href="{{ route('system.accounts.show', $organization->account) }}" class="text-sm font-black text-brand hover:underline leading-none">{{ __('View Account Profile') }}</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </aside>
                </div>
            @endif

            @if($activeTab === 'events')
                <div class="space-y-8 animate-in slide-in-from-right-4 duration-500">
                    <h2 class="text-2xl font-black text-content tracking-tight">{{ __('Event Management History') }}</h2>
                    <div class="overflow-hidden rounded-[2rem] border border-stroke shadow-xl shadow-slate-900/5 bg-card">
                        <table class="min-w-full divide-y divide-stroke">
                            <thead>
                                <tr class="bg-surface text-[10px] font-black text-content-muted uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 text-start">{{ __('Operational Name') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Event Timestamp') }}</th>
                                    <th class="px-8 py-5 text-start">{{ __('Life-cycle State') }}</th>
                                    <th class="px-8 py-5 text-end"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke">
                                @forelse($events as $event)
                                    <tr wire:key="event-{{ $event->id }}" class="hover:bg-brand/5 transition-all group">
                                        <td class="px-8 py-6">
                                            <div class="text-base font-black text-content group-hover:text-brand transition-colors">{{ $event->name }}</div>
                                        </td>
                                        <td class="px-8 py-6 text-sm font-bold text-content-muted">
                                            {{ $event->event_date?->format('d.m.Y') }}
                                        </td>
                                        <td class="px-8 py-6 text-start">
                                            <span class="inline-flex px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest {{ $event->status?->value === 'active' ? 'bg-success/10 text-success ring-1 ring-success/20' : 'bg-warning/10 text-warning ring-1 ring-warning/20' }}">
                                                {{ $event->status?->label() ?? __('Draft') }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-end">
                                            @if($event->status !== \App\Enums\EventStatus::Active)
                                                <button type="button" wire:click="requestAction('setEventActive', null, {{ $event->id }})" class="text-[10px] font-black text-success hover:text-white hover:bg-success px-4 py-2 rounded-xl transition-all uppercase tracking-widest ring-1 ring-success/30">
                                                    {{ __('Force Live') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-8 py-32 text-center">
                                            <x-heroicon-o-calendar-days class="size-16 text-slate-100 mx-auto mb-4" />
                                            <p class="text-content-muted font-bold italic">{{ __('No operational events found for this tenant.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8 px-4">{{ $events->links() }}</div>
                </div>
            @endif

            @if($activeTab === 'billing')
                <div class="space-y-8 animate-in slide-in-from-left-4 duration-500">
                    @if(session('success'))
                        <div class="rounded-2xl bg-success/10 border border-success/20 px-6 py-4 text-sm font-medium text-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-2xl bg-danger/10 border border-danger/20 px-6 py-4 text-sm font-medium text-danger">{{ session('error') }}</div>
                    @endif

                    {{-- Subscription Card --}}
                    <div class="bg-surface rounded-3xl border border-stroke p-8 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-black text-content tracking-tight">{{ __('Active Subscription') }}</h3>
                            <button wire:click="syncSubscriptions" wire:loading.attr="disabled" class="flex items-center gap-2 text-xs font-bold text-brand hover:text-brand/80 transition-colors">
                                <x-heroicon-o-arrow-path class="size-4 wire:loading:animate-spin" wire:target="syncSubscriptions" />
                                {{ __('Sync from SUMIT') }}
                            </button>
                        </div>

                        @if($subscription)
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Status') }}</p>
                                    <span @class([
                                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-black',
                                        'bg-success/10 text-success' => $subscription->isActive(),
                                        'bg-warning/10 text-warning' => $subscription->isPending() || $subscription->isPaused(),
                                        'bg-danger/10 text-danger' => $subscription->isCancelled() || $subscription->isFailed() || $subscription->isExpired(),
                                    ])>{{ ucfirst($subscription->status) }}</span>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Plan') }}</p>
                                    <p class="font-bold text-content">{{ $subscription->name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Amount') }}</p>
                                    <p class="font-bold text-content">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Billing cycle') }}</p>
                                    <p class="font-bold text-content">{{ __('Every :n month(s)', ['n' => $subscription->interval_months]) }}</p>
                                </div>
                                @if($subscription->next_charge_at)
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Next charge') }}</p>
                                        <p class="font-bold text-content">{{ $subscription->next_charge_at->format('d.m.Y') }}</p>
                                    </div>
                                @endif
                                @if($subscription->trial_ends_at)
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Trial ends') }}</p>
                                        <p class="font-bold {{ $subscription->isInTrial() ? 'text-warning' : 'text-content-muted line-through' }}">{{ $subscription->trial_ends_at->format('d.m.Y') }}</p>
                                    </div>
                                @endif
                                @if($subscription->last_charged_at)
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Last charged') }}</p>
                                        <p class="font-bold text-content">{{ $subscription->last_charged_at->format('d.m.Y') }}</p>
                                    </div>
                                @endif
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-content-muted uppercase tracking-widest">{{ __('Cycles') }}</p>
                                    <p class="font-bold text-content">{{ $subscription->completed_cycles }} / {{ $subscription->total_cycles ?? '∞' }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-16 text-center space-y-3">
                                <x-heroicon-o-credit-card class="size-12 text-content-muted/30" />
                                <p class="text-content-muted font-medium">{{ __('No active subscription found.') }}</p>
                                <p class="text-xs text-content-muted/60">{{ __('Use "Sync from SUMIT" to fetch the latest data.') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Billing Actions --}}
                    @if($subscription && ($subscription->isActive() || $subscription->isPending() || $subscription->isPaused() || $subscription->isInTrial()))
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-surface rounded-3xl border border-stroke p-8 space-y-4">
                                <div class="size-12 rounded-2xl bg-warning/10 flex items-center justify-center text-warning">
                                    <x-heroicon-o-clock class="size-6" />
                                </div>
                                <h4 class="font-black text-content">{{ __('Extend Trial') }}</h4>
                                <p class="text-sm text-content-muted">{{ __('Add days to the trial period.') }}</p>
                                <div class="flex items-center gap-3">
                                    <input type="number" wire:model.live="trialExtendDays" min="1" max="365" class="w-24 rounded-xl border border-stroke bg-card px-3 py-2 text-sm font-bold text-center focus:ring-2 focus:ring-brand/30 focus:border-brand/30" />
                                    <span class="text-sm text-content-muted">{{ __('days') }}</span>
                                </div>
                                <button wire:click="requestAction('extendTrial')" class="w-full py-3 rounded-2xl bg-warning/10 text-warning font-black text-sm hover:bg-warning/20 transition-colors">
                                    {{ __('Extend Trial') }}
                                </button>
                            </div>

                            <div class="bg-surface rounded-3xl border border-stroke p-8 space-y-4">
                                <div class="size-12 rounded-2xl bg-danger/10 flex items-center justify-center text-danger">
                                    <x-heroicon-o-x-circle class="size-6" />
                                </div>
                                <h4 class="font-black text-content">{{ __('Cancel Subscription') }}</h4>
                                <p class="text-sm text-content-muted">{{ __('Immediately cancel the active subscription. This action requires password confirmation.') }}</p>
                                <button wire:click="requestAction('cancelSubscription')" class="w-full py-3 rounded-2xl bg-danger/10 text-danger font-black text-sm hover:bg-danger/20 transition-colors">
                                    {{ __('Cancel Subscription') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($activeTab === 'admin')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-in slide-in-from-left-4 duration-500">
                    <div class="p-10 rounded-[3rem] bg-card border border-stroke shadow-sm space-y-8 transition-all hover:shadow-xl group">
                        <div class="size-14 rounded-2xl bg-warning/10 flex items-center justify-center text-warning group-hover:scale-110 transition-transform">
                            <x-heroicon-o-pause-circle class="size-8" />
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-black text-content tracking-tight">{{ __('Tenant Access Control') }}</h3>
                            <p class="text-content-muted font-medium leading-relaxed">{{ __('Freeze all operations for this organization immediately. Users will be logged out.') }}</p>
                        </div>
                        @if($organization->is_suspended)
                            <button wire:click="requestAction('activate')" class="w-full py-5 px-8 rounded-2xl bg-success text-white font-black shadow-xl shadow-success/20 hover:bg-success/90 transition-all active:scale-95 cursor-pointer min-h-[64px]">
                                {{ __('Restore Tenant Access') }}
                            </button>
                        @else
                            <button wire:click="requestAction('suspend')" class="w-full py-5 px-8 rounded-2xl bg-warning text-white font-black shadow-xl shadow-warning/20 hover:bg-warning/90 transition-all active:scale-95 cursor-pointer min-h-[64px]">
                                {{ __('Suspend Operational Access') }}
                            </button>
                        @endif
                    </div>

                    <div class="p-10 rounded-[3rem] bg-card border border-stroke shadow-sm space-y-8 transition-all hover:shadow-xl group">
                        <div class="size-14 rounded-2xl bg-brand/5 flex items-center justify-center text-brand group-hover:scale-110 transition-transform">
                            <x-heroicon-o-arrows-right-left class="size-8" />
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-black text-content tracking-tight">{{ __('Root Ownership') }}</h3>
                            <p class="text-content-muted font-medium leading-relaxed">{{ __('Reassign the master administrative rights to another verified member.') }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-3 max-h-[180px] overflow-y-auto no-scrollbar">
                            @foreach($members as $m)
                                @if($m->id !== $owner?->id)
                                    <button wire:key="transfer-btn-{{ $m->id }}" wire:click="requestAction('transferOwnership', {{ $m->id }})" class="flex items-center justify-between p-4 rounded-2xl bg-surface border border-transparent hover:border-brand/30 hover:bg-card transition-all group/btn">
                                        <span class="text-sm font-black text-content group-hover/btn:text-brand">{{ $m->name }}</span>
                                        <x-heroicon-o-chevron-right class="size-4 text-content-muted group-hover/btn:translate-x-1 transition-transform" />
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2 p-12 rounded-[4rem] bg-gradient-to-br from-danger to-danger/80 text-white space-y-10 shadow-danger relative overflow-hidden group">
                        <x-heroicon-o-shield-exclamation class="absolute -right-10 -bottom-10 size-64 opacity-10 rotate-12 group-hover:rotate-6 transition-transform duration-1000" />
                        <div class="relative z-10 flex items-center gap-6 text-start">
                            <div class="size-16 rounded-3xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                                <x-heroicon-o-trash class="size-8 text-white" />
                            </div>
                            <div>
                                <h3 class="text-3xl font-black uppercase tracking-tighter">{{ __('Terminal Actions') }}</h3>
                                <p class="text-rose-100 font-bold opacity-80">{{ __('Irreversible infrastructure operations. Proceed with extreme caution.') }}</p>
                            </div>
                        </div>
                        <div class="relative z-10 flex flex-wrap gap-6 pt-4">
                            <button wire:click="requestAction('forceDelete')" class="px-10 py-5 rounded-2xl bg-card text-danger font-black shadow-2xl hover:bg-rose-50 transition-all active:scale-95 cursor-pointer min-h-[64px]">
                                {{ __('Destroy All Tenant Assets') }}
                            </button>
                            <button wire:click="requestAction('resetData')" class="px-10 py-5 rounded-2xl bg-danger/20 text-white font-black border-2 border-white/20 hover:bg-danger/40 transition-all active:scale-95 cursor-pointer min-h-[64px]">
                                {{ __('Clear Operational Data') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Security Verification Overlay --}}
    @if($pendingAction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/95 backdrop-blur-2xl p-4 sm:p-8 animate-in fade-in duration-500" role="dialog">
            <div class="bg-card rounded-[4rem] shadow-2xl max-w-lg w-full p-12 sm:p-20 transform animate-in zoom-in-95 slide-in-from-bottom-12 duration-700 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-danger via-warning to-danger"></div>
                <div class="text-center mb-12">
                    <div class="inline-flex items-center justify-center size-24 rounded-[2rem] bg-danger/5 text-danger mb-10 ring-[16px] ring-danger/5 animate-pulse">
                        <x-heroicon-o-lock-closed class="size-12" />
                    </div>
                    <h3 class="text-4xl font-black text-content tracking-tight mb-6 leading-none">{{ __('Master Unlock') }}</h3>
                    <p class="text-lg text-content-muted font-medium leading-relaxed">{{ __('You are initiating a level-1 system override. Identity verification required.') }}</p>
                </div>
                
                <form wire:submit.prevent="confirmAndExecute" class="space-y-10">
                    <input type="password" wire:model="confirmPassword" class="block w-full px-8 py-6 rounded-3xl bg-surface border-transparent focus:bg-card focus:ring-[16px] focus:ring-danger/5 focus:border-danger/30 transition-all text-center text-3xl font-black tracking-[0.3em] shadow-inner placeholder:tracking-normal placeholder:font-normal placeholder:text-slate-300" placeholder="••••••••" autofocus />
                    
                    <div class="flex flex-col gap-5">
                        <button type="submit" class="w-full py-6 bg-danger text-white font-black text-xl rounded-2xl shadow-danger hover:bg-danger/90 transition-all active:scale-95 cursor-pointer min-h-[72px] uppercase tracking-widest text-center">
                            {{ __('Authorize Now') }}
                        </button>
                        <button type="button" wire:click="cancelConfirm" class="w-full py-4 text-xs font-black text-content-muted hover:text-content transition-colors uppercase tracking-[0.3em] cursor-pointer">
                            {{ __('Abort Session') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
