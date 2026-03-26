@props(['location' => 'header'])
@php
    $appName = config('app.name');
    $isSystemAdmin = auth()->check() && auth()->user()->is_system_admin;
    
    // Check current routes for active states
    $isDashboard = request()->routeIs('dashboard');
    $isEventsIndex = request()->routeIs('dashboard.events.index');
    $isCalling = request()->routeIs('twilio.calling.index');
    $isOrganizations = request()->routeIs('organizations.index');
    $isOrganizationSettings = request()->routeIs('dashboard.organization-settings.*');
    $isBilling = request()->routeIs('billing.*');
    $isProfile = request()->routeIs('profile');
    $isSystemDashboard = request()->routeIs('system.dashboard');
    $isSystemOrgs = request()->routeIs('system.organizations.*');
    $isSystemAccounts = request()->routeIs('system.accounts.*');
    $isSystemUsers = request()->routeIs('system.users.*');
    $isSystemProducts = request()->routeIs('system.products.*');
    $isSystemSettings = request()->routeIs('system.settings.index');
@endphp

<div x-data="{ mobileMenuOpen: false }" x-id="['navbar']" x-cloak>
    {{-- Lock body scroll when drawer is open --}}
    <div x-effect="mobileMenuOpen ? document.body.style.overflow = 'hidden' : document.body.style.overflow = ''"></div>

    {{-- Overlay --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 z-50 bg-content/80 backdrop-blur-sm lg:hidden"
         role="presentation"
         aria-hidden="true"></div>

    {{-- Main Navbar --}}
    <nav class="glass-navbar sticky top-0 z-40" aria-label="{{ __('Main navigation') }}">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                {{-- Brand Logo --}}
                <a href="{{ url('/') }}" wire:navigate class="group flex items-center gap-3 transition-all duration-300 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 rounded-lg py-1">
                    <x-kalfa-wordmark class="transition duration-300 ease-out group-hover:opacity-95" />
                </a>

                {{-- Desktop Navigation --}}
                <div class="hidden lg:flex items-center gap-1 ms-auto">
                    @guest
                        <a href="{{ route('login') }}" wire:navigate class="nav-link whitespace-nowrap">{{ __('login') }}</a>
                        <a href="{{ route('register') }}" wire:navigate class="px-5 py-2 bg-brand text-white font-bold rounded-xl shadow-lg shadow-brand/20 hover:bg-brand-hover hover:scale-[1.02] active:scale-95 transition-all">{{ __('register') }}</a>
                    @else
                        @php
                            $currentOrg = auth()->user()->currentOrganization;
                            $userOrganizations = auth()->user()->organizations()->orderBy('name')->get();
                        @endphp

                        {{-- Main Links --}}
                        <div class="flex items-center gap-1 border-e border-gray-200 dark:border-gray-800 pe-4 me-4">
                            @can('view-event-details')
                                <a href="{{ route('dashboard') }}" wire:navigate class="nav-link {{ $isDashboard ? 'nav-link-active' : '' }}">{{ __('dashboard') }}</a>
                                <a href="{{ route('dashboard.events.index') }}" wire:navigate class="nav-link {{ $isEventsIndex ? 'nav-link-active' : '' }}">{{ __('Events') }}</a>
                            @endcan
                            @can('manage-system')
                                <a href="{{ route('twilio.calling.index') }}" wire:navigate class="nav-link {{ $isCalling ? 'nav-link-active' : '' }}">{{ __('Calling') }}</a>
                            @endcan
                        </div>

                        {{-- Organizations Dropdown --}}
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" class="nav-link gap-2 group {{ $isOrganizations || $isOrganizationSettings ? 'nav-link-active' : '' }}">
                                <span class="truncate max-w-[140px]">{{ $currentOrg ? $currentOrg->name : __('Organizations') }}</span>
                                @if($userOrganizations->count() > 1)
                                    <span class="bg-brand/10 text-brand text-[10px] font-black px-1.5 py-0.5 rounded-full ring-1 ring-brand/20">{{ $userOrganizations->count() }}</span>
                                @endif
                                <span x-bind:class="{ 'rotate-180': open }" class="transition-transform duration-300">
                                    <x-heroicon-o-chevron-down class="size-4 shrink-0 text-gray-400 group-hover:text-brand" />
                                </span>
                            </button>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 origin-top-right"
                                 x-transition:enter-end="opacity-100 scale-100 origin-top-right"
                                 x-cloak
                                 class="absolute right-0 mt-3 w-64 rounded-2xl bg-white dark:bg-gray-900 py-3 shadow-2xl ring-1 ring-black/5 z-50 border border-gray-100 dark:border-gray-800">
                                 
                                <div class="px-5 py-2 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">{{ __('Your Organizations') }}</div>
                                <div class="max-h-[300px] overflow-y-auto custom-scrollbar">
                                    @foreach($userOrganizations as $org)
                                        <form action="{{ route('organizations.switch', $org) }}" method="POST" class="block">
                                            @csrf
                                            <button type="submit" class="w-full px-5 py-2.5 text-start text-sm flex items-center justify-between transition-colors {{ $currentOrg && $currentOrg->id === $org->id ? 'text-brand font-bold bg-brand/5' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                                <span class="truncate">{{ $org->name }}</span>
                                                @if($currentOrg && $currentOrg->id === $org->id)
                                                    <x-heroicon-o-check class="size-4 text-brand" />
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                                
                                <div class="border-t border-gray-100 dark:border-gray-800 mt-2 pt-2 px-2 flex flex-col gap-1">
                                    @if($currentOrg)
                                        <a href="{{ route('dashboard.organization-settings.edit') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-cog-6-tooth class="size-4 shrink-0 text-gray-400" />
                                            {{ __('Settings') }}
                                        </a>
                                        <a href="{{ route('dashboard.team') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-brand hover:bg-brand/5 dark:hover:bg-brand/10 rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-users class="size-4 shrink-0" />
                                            {{ __('Team Management') }}
                                        </a>
                                    @endif
                                    <a href="{{ route('organizations.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg flex items-center gap-2">
                                        <x-heroicon-o-squares-plus class="size-4 shrink-0 text-gray-400" />
                                        {{ __('All Organizations') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Admin Dropdown --}}
                        @can('manage-system')
                            <div x-data="{ open: false }" @click.away="open = false" class="relative ms-1">
                                <button @click="open = !open" class="nav-link gap-2 group {{ str_contains(request()->route()->getName(), 'system.') ? 'nav-link-active' : '' }}">
                                    <span class="px-2 py-0.5 bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest rounded-full ring-1 ring-amber-200/50">Admin</span>
                                    <span x-bind:class="{ 'rotate-180': open }" class="transition-transform duration-300">
                                        <x-heroicon-o-chevron-down class="size-4 shrink-0 text-gray-300 group-hover:text-amber-500" />
                                    </span>
                                </button>
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95 origin-top-right"
                                     x-transition:enter-end="opacity-100 scale-100 origin-top-right"
                                     x-cloak
                                     class="absolute right-0 mt-3 w-60 rounded-2xl bg-white dark:bg-gray-900 py-3 shadow-2xl ring-1 ring-black/5 z-50 border border-gray-100 dark:border-gray-800">
                                    
                                    <div class="px-5 py-2 text-[10px] font-black text-amber-600/60 uppercase tracking-[0.2em] mb-1">{{ __('System Administration') }}</div>
                                    <div class="px-2 flex flex-col gap-1">
                                        <a href="{{ route('system.dashboard') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemDashboard ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-presentation-chart-line class="size-4 shrink-0" />
                                            {{ __('Dashboard') }}
                                        </a>
                                        <a href="{{ route('system.organizations.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemOrgs ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-building-office class="size-4 shrink-0" />
                                            {{ __('Organizations') }}
                                        </a>
                                        <a href="{{ route('system.accounts.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemAccounts ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-credit-card class="size-4 shrink-0" />
                                            {{ __('Accounts') }}
                                        </a>
                                        <a href="{{ route('system.products.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemProducts ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-cube class="size-4 shrink-0" />
                                            {{ __('Products') }}
                                        </a>
                                        <a href="{{ route('system.users.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemUsers ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-user-group class="size-4 shrink-0" />
                                            {{ __('Users') }}
                                        </a>
                                        <a href="{{ route('system.settings.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium {{ $isSystemSettings ? 'text-brand bg-brand/5 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }} rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-cog-8-tooth class="size-4 shrink-0" />
                                            {{ __('Settings') }}
                                        </a>
                                        <a href="/pulse" target="_blank" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg flex items-center gap-2">
                                            <x-heroicon-o-bolt class="size-4 shrink-0" />
                                            {{ __('Pulse') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endcan

                        {{-- Billing --}}
                        <a href="{{ route('billing.account') }}" wire:navigate class="nav-link ms-1 {{ $isBilling ? 'nav-link-active' : '' }}">
                            <x-heroicon-o-credit-card class="size-5 lg:hidden xl:block me-2 opacity-50" />
                            {{ __('Billing') }}
                        </a>

                        {{-- User Menu --}}
                        <div x-data="{ open: false }" @click.away="open = false" class="relative ms-4 ps-4 border-s border-gray-200 dark:border-gray-800">
                            <button @click="open = !open" class="flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:rounded-lg transition-all active:scale-95">
                                <div class="size-10 rounded-xl bg-gradient-to-br from-brand to-brand-hover flex items-center justify-center text-white font-black shadow-md shadow-brand/20 group-hover:shadow-lg transition-all">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="hidden xl:block text-start leading-none pe-2">
                                    <p class="text-xs font-black text-gray-900 dark:text-gray-100 truncate max-w-[100px] mb-1">{{ auth()->user()->name }}</p>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Member') }}</p>
                                </div>
                                <span x-bind:class="{ 'rotate-180': open }" class="transition-transform duration-300">
                                    <x-heroicon-o-chevron-down class="size-4 text-gray-300 group-hover:text-brand" />
                                </span>
                            </button>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 origin-top-right"
                                 x-transition:enter-end="opacity-100 scale-100 origin-top-right"
                                 x-cloak
                                 class="absolute right-0 mt-3 w-56 rounded-2xl bg-white dark:bg-gray-900 py-3 shadow-2xl ring-1 ring-black/5 z-50 border border-gray-100 dark:border-gray-800">
                                
                                <a href="{{ route('profile') }}" wire:navigate class="px-5 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center gap-3">
                                    <x-heroicon-o-user-circle class="size-5 text-gray-400" />
                                    {{ __('My Profile') }}
                                </a>
                                <x-dark-mode-toggle class="w-full px-5 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center gap-3" />
                                
                                <div class="border-t border-gray-100 dark:border-gray-800 mt-2 pt-2">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full px-5 py-3 text-start text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 flex items-center gap-3 transition-colors">
                                            <x-heroicon-o-arrow-left-on-rectangle class="size-5" />
                                            {{ __('Logout') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endguest
                </div>

                {{-- Mobile Toggle --}}
                <div class="flex items-center gap-3 lg:hidden shrink-0">
                    @auth
                        <div class="size-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center font-black shadow-sm shadow-brand/10">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    @endauth
                    <button @click="mobileMenuOpen = true" class="p-2.5 rounded-xl bg-surface border border-stroke text-content-muted hover:text-brand hover:border-brand/30 active:scale-95 transition-all"
                            aria-label="{{ __('Open menu') }}">
                        <x-heroicon-o-bars-3-bottom-right class="size-7" />
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- Mobile Sidebar Drawer --}}
    <div x-show="mobileMenuOpen"
         @keydown.escape.window="mobileMenuOpen = false"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="{{ isRTL() ? 'translate-x-full' : '-translate-x-full' }}"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="{{ isRTL() ? 'translate-x-full' : '-translate-x-full' }}"
         class="fixed inset-y-0 {{ isRTL() ? 'right-0 is-rtl' : 'left-0' }} z-[60] w-72 max-w-[85vw] bg-card border-l border-stroke flex flex-col shadow-xl overflow-hidden mobile-drawer lg:hidden"
         :class="{ 'is-open': mobileMenuOpen }"
         x-cloak
         role="dialog"
         aria-modal="true"
         aria-label="{{ __('Mobile navigation') }}">

        <div class="px-4 py-3 border-b border-stroke flex items-center justify-between shrink-0 bg-surface">
            <div class="flex items-center gap-2">
                <x-kalfa-wordmark class="justify-start" />
            </div>
            <button @click="mobileMenuOpen = false"
                    class="size-9 rounded-xl bg-card border border-stroke text-content-muted hover:text-content hover:border-brand/30 hover:bg-surface flex items-center justify-center active:scale-95 transition-all"
                    aria-label="{{ __('Close menu') }}">
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 overscroll-contain">
            @guest
                <div class="space-y-2 px-1">
                    <a href="{{ route('login') }}" wire:navigate class="flex items-center justify-center w-full py-2.5 text-sm text-content font-semibold hover:bg-surface hover:text-brand rounded-xl transition-colors">{{ __('login') }}</a>
                    <a href="{{ route('register') }}" wire:navigate class="flex items-center justify-center w-full py-2.5 bg-brand text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand/20 hover:bg-brand-hover hover:scale-[1.02] active:scale-95 transition-all">{{ __('register') }}</a>
                </div>
            @else
                {{-- User Summary - Compact --}}
                <div class="flex items-center gap-3 px-3 py-2.5 bg-surface border border-stroke rounded-xl">
                    <div class="size-9 rounded-xl bg-gradient-to-br from-brand to-brand-hover flex items-center justify-center text-white font-bold shadow-md shadow-brand/20 shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-content truncate">{{ auth()->user()->name }}</p>
                    </div>
                    <x-dark-mode-toggle class="text-content-muted hover:text-content transition-colors" />
                </div>

                {{-- Main Nav --}}
                <div>
                    <div class="space-y-0.5">
                        @can('view-event-details')
                            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 px-3 py-2 rounded-lg font-semibold transition-all text-sm {{ $isDashboard ? 'bg-brand/10 text-brand' : 'text-content-muted hover:bg-surface hover:text-content' }}">
                                <x-heroicon-o-home class="size-4.5 shrink-0" />
                                {{ __('Dashboard') }}
                            </a>
                            <a href="{{ route('dashboard.events.index') }}" wire:navigate class="flex items-center gap-2 px-3 py-2 rounded-lg font-semibold transition-all text-sm {{ $isEventsIndex ? 'bg-brand/10 text-brand' : 'text-content-muted hover:bg-surface hover:text-content' }}">
                                <x-heroicon-o-calendar class="size-4.5 shrink-0" />
                                {{ __('Events') }}
                            </a>
                        @endcan
                        @can('manage-system')
                            <a href="{{ route('twilio.calling.index') }}" wire:navigate class="flex items-center gap-2 px-3 py-2 rounded-lg font-semibold transition-all text-sm {{ $isCalling ? 'bg-brand/10 text-brand' : 'text-content-muted hover:bg-surface hover:text-content' }}">
                                <x-heroicon-o-phone class="size-4.5 shrink-0" />
                                {{ __('Calling') }}
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- Organizations - Compact --}}
                <div>
                    <p class="px-3 text-[9px] font-semibold text-content-muted uppercase tracking-wider mb-2">{{ __('Organization') }}</p>
                    @if($currentOrg)
                        <div class="mx-1 px-3 py-2 bg-brand/5 border border-brand/10 rounded-xl mb-2">
                            <p class="text-xs font-semibold text-content truncate">{{ $currentOrg->name }}</p>
                        </div>
                    @endif
                    <a href="{{ route('organizations.index') }}" wire:navigate class="flex items-center gap-2 px-3 py-2 rounded-lg font-semibold text-sm text-content-muted hover:bg-surface hover:text-content transition-colors">
                        <x-heroicon-o-squares-2x2 class="size-4.5 shrink-0" />
                        {{ __('All Organizations') }}
                    </a>
                </div>

                {{-- System Administration - Compact Grid --}}
                @can('manage-system')
                    <div class="pt-3 border-t border-stroke">
                        <p class="px-3 text-[9px] font-semibold text-warning uppercase tracking-wider mb-2">{{ __('Admin') }}</p>
                        <div class="grid grid-cols-4 gap-2 px-1">
                            <a href="{{ route('system.dashboard') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-presentation-chart-line class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Stats') }}</span>
                            </a>
                            <a href="{{ route('system.organizations.index') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-building-office class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Orgs') }}</span>
                            </a>
                            <a href="{{ route('system.accounts.index') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-credit-card class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Bill') }}</span>
                            </a>
                            <a href="{{ route('system.users.index') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-user-group class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Users') }}</span>
                            </a>
                            <a href="{{ route('system.products.index') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-cube class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Prod') }}</span>
                            </a>
                            <a href="{{ route('system.settings.index') }}" wire:navigate class="flex flex-col items-center gap-1.5 p-2.5 bg-surface border border-stroke rounded-xl text-content hover:text-brand hover:border-brand/20 hover:bg-brand/5 transition-colors">
                                <x-heroicon-o-cog-8-tooth class="size-4 text-warning" />
                                <span class="text-[8px] font-semibold uppercase text-center leading-tight">{{ __('Set') }}</span>
                            </a>
                        </div>
                    </div>
                @endcan
            @endguest
        </nav>

        @auth
            <div class="px-3 py-3 border-t border-stroke bg-card/95 backdrop-blur-md shrink-0">
                <div class="flex items-center gap-2">
                    <a href="{{ route('profile') }}" wire:navigate class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-semibold text-content hover:bg-surface hover:text-brand rounded-xl transition-colors">
                        <x-heroicon-o-user-circle class="size-4" />
                        {{ __('Profile') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2 text-sm font-semibold text-danger hover:bg-danger/10 rounded-xl transition-colors">
                            <x-heroicon-o-arrow-left-on-rectangle class="size-4" />
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</div>
