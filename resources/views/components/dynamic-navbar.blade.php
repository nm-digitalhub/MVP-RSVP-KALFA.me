{{-- Tailwind v4 + tailwind-css-patterns: mobile-first navbar, container, flex layout, focus ring, transition. --}}
@props(['location' => 'header'])
@php
  $appName = config('app.name');
  $isSystemAdmin = auth()->check() && auth()->user()->is_system_admin;
  $navLinkClass = 'text-gray-600 hover:text-gray-900 hover:bg-gray-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-50 rounded-lg min-h-[44px] inline-flex items-center px-3 py-2 transition-all duration-200 ease-out cursor-pointer motion-reduce:transition-none';
  $navLinkActiveClass = 'font-semibold text-gray-900 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200';
  $mobileNavBase = 'min-h-[44px] inline-flex items-center px-4 py-2.5 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-inset transition-all duration-200 ease-out cursor-pointer';
  $mobileNavDefault = 'text-gray-700 hover:bg-gray-100/90 hover:text-gray-900 ' . $mobileNavBase;
  $mobileNavActive = 'border-s-4 border-indigo-600 rtl:border-s-0 rtl:border-e-4 bg-indigo-50 text-indigo-700 font-semibold ' . $mobileNavBase;
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
@endphp

<nav class="bg-white/95 border-b border-gray-200/80 backdrop-blur-md shadow-sm sticky top-0 z-30" aria-label="{{ __('Main navigation') }}">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center h-16">
            <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 bg-clip-text text-transparent hover:from-indigo-600 hover:via-indigo-500 hover:to-indigo-600 hover:text-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 rounded-lg min-h-[44px] inline-flex items-center px-3 py-2 transition-all duration-300 ease-out motion-reduce:transition-none">
                {{ $appName }}
            </a>

            {{-- Desktop nav (hidden md:flex space-x-8 pattern) --}}
            <div class="hidden md:flex items-center gap-6">
        @guest
            <a href="{{ route('login') }}" class="{{ $navLinkClass }}">{{ __('login') }}</a>
            <a href="{{ route('register') }}" class="{{ $navLinkClass }}">{{ __('register') }}</a>
        @else
            @php
                $currentOrg = auth()->user()->currentOrganization;
                $userOrganizations = auth()->user()->organizations()->orderBy('name')->get();
            @endphp
            @can('view-event-details')
                <a href="{{ route('dashboard') }}" class="{{ $navLinkClass }} {{ $isDashboard ? $navLinkActiveClass : '' }}">{{ __('dashboard') }}</a>
                <a href="{{ route('dashboard.events.index') }}" class="{{ $navLinkClass }} {{ $isEventsIndex ? $navLinkActiveClass : '' }}">{{ __('Events') }}</a>
            @endcan
            @can('manage-system')
                <a href="{{ route('twilio.calling.index') }}" class="{{ $navLinkClass }} {{ $isCalling ? $navLinkActiveClass : '' }}">{{ __('Calling') }}</a>
            @endcan
            <details class="relative group">
                <summary class="flex items-center gap-2 px-3 py-2 cursor-pointer list-none text-gray-600 hover:text-gray-900 hover:bg-gray-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-50 rounded-lg min-h-[44px] font-medium [&::-webkit-details-marker]:hidden transition-all duration-200 ease-out motion-reduce:transition-none">
                    <div class="flex items-center gap-2">
                        <span class="truncate max-w-[150px]">{{ $currentOrg ? $currentOrg->name : __('Organizations') }}</span>
                        @if($userOrganizations->count() > 1)
                            <span class="text-xs bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded-full font-medium ring-1 ring-indigo-200/50 shadow-sm">{{ $userOrganizations->count() }}</span>
                        @endif
                    </div>
                    <x-heroicon-o-chevron-down class="w-4 h-4 shrink-0 text-gray-400 group-hover:text-indigo-500 transition-transform duration-300 ease-out group-open:rotate-180" />
                </summary>
                <div class="absolute right-0 mt-2 w-64 rounded-xl bg-white/95 py-2 shadow-2xl ring-1 ring-gray-200/80 z-50 border border-gray-200/60 backdrop-blur-md opacity-0 invisible scale-95 group-open:opacity-100 group-open:visible group-open:scale-100 transition-all duration-200 ease-out origin-top-right">
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500/80 uppercase tracking-wider bg-gray-50/50">{{ __('Your Organizations') }}</div>
                    @foreach($userOrganizations as $org)
                        <div class="block">
                            <form action="{{ route('organizations.switch', $org) }}" method="POST" class="w-full text-start">
                                @csrf
                                <button type="submit" class="w-full min-h-[44px] px-4 py-2.5 text-sm {{ $currentOrg && $currentOrg->id === $org->id ? 'text-indigo-600 bg-indigo-50/80 ring-1 ring-indigo-200/50' : 'text-gray-700 hover:bg-gray-100/80' }} flex items-center justify-between focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset transition-all duration-200 ease-out">
                                    <span class="truncate">{{ $org->name }}</span>
                                    @if($currentOrg && $currentOrg->id === $org->id)
                                        <x-heroicon-o-check class="w-4 h-4 shrink-0 text-indigo-600" />
                                    @endif
                                </button>
                            </form>
                        </div>
                    @endforeach
                    <div class="border-t border-gray-100 my-1">
                        @if($currentOrg)
                            <a href="{{ route('dashboard.organization-settings.edit') }}" class="block min-h-[44px] px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100/80 font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset transition-all duration-200 ease-out">{{ __('Organization settings') }}</a>
                            <a href="{{ route('dashboard.team') }}" class="block min-h-[44px] px-4 py-2.5 text-sm text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset transition-all duration-200 ease-out">{{ __('Team Management') }}</a>
                        @endif
                        <a href="{{ route('organizations.index') }}" class="block min-h-[44px] px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100/80 font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset transition-all duration-200 ease-out">{{ __('Manage Organizations') }}</a>
                    </div>
                </div>
            </details>
            {{-- Dark mode toggle --}}
            <x-dark-mode-toggle class="hidden md:inline-flex" />

            @if(session('impersonation.original_organization_id'))
                <form method="POST" action="{{ route('system.impersonation.exit') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-amber-600 hover:text-amber-700 hover:bg-amber-50 text-sm font-semibold min-h-[44px] inline-flex items-center px-3 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/50 focus-visible:ring-offset-2 rounded-lg transition-all duration-200 ease-out cursor-pointer motion-reduce:transition-none">{{ __('Exit impersonation') }}</button>
                </form>
            @endif
            <a href="{{ route('billing.account') }}" class="{{ $navLinkClass }} {{ request()->routeIs('billing.*') ? $navLinkActiveClass : '' }}">{{ __('Billing & Entitlements') }}</a>
            @can('manage-system')
                <div class="flex items-center">
                    <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-gradient-to-br from-amber-50 to-amber-100/70 border border-amber-200/80 rounded-full ring-1 ring-amber-200/40 shadow-sm">
                        <x-heroicon-o-shield-check class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ __('Admin') }}</span>
                    </span>
                </div>
                <a href="{{ route('system.dashboard') }}" class="{{ $navLinkClass }} {{ request()->routeIs('system.dashboard') ? $navLinkActiveClass : '' }}">{{ __('System Dashboard') }}</a>
                <a href="/pulse" class="{{ $navLinkClass }}" target="_blank">{{ __('System Pulse') }}</a>
                <a href="/telescope" class="{{ $navLinkClass }}" target="_blank">{{ __('Telescope') }}</a>
                <a href="{{ route('system.organizations.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('system.organizations.*') ? $navLinkActiveClass : '' }}">{{ __('System Organizations') }}</a>
                <a href="{{ route('system.accounts.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('system.accounts.*') ? $navLinkActiveClass : '' }}">{{ __('Accounts') }}</a>
                <a href="{{ route('system.users.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('system.users.*') ? $navLinkActiveClass : '' }}">{{ __('System Users') }}</a>
            @endcan
            {{-- User menu: Profile + Logout so Logout is always visible (avoids overflow on narrow desktop) --}}
            <details class="relative group/user">
                <summary class="flex items-center gap-2 px-3 py-2 cursor-pointer list-none text-gray-600 hover:text-gray-900 hover:bg-gray-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-50 rounded-lg min-h-[44px] font-medium [&::-webkit-details-marker]:hidden transition-all duration-200 ease-out motion-reduce:transition-none">
                    <span class="truncate max-w-[120px]">{{ auth()->user()->name }}</span>
                    <x-heroicon-o-chevron-down class="w-4 h-4 shrink-0 text-gray-400 group-hover/user:text-indigo-500 transition-transform duration-300 ease-out group-open/user:rotate-180" />
                </summary>
                <div class="absolute end-0 mt-2 w-56 rounded-xl bg-white/95 py-2 shadow-2xl ring-1 ring-gray-200/80 z-50 border border-gray-200/60 backdrop-blur-md opacity-0 invisible scale-95 group-open/user:opacity-100 group-open/user:visible group-open/user:scale-100 transition-all duration-200 ease-out origin-top-right rtl:origin-top-left">
                    <a href="{{ route('profile') }}" class="block min-h-[44px] px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100/80 font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset transition-all duration-200 ease-out">{{ __('Profile') }}</a>
                    <div class="border-t border-gray-100 my-1">
                        <form method="POST" action="{{ route('logout') }}" class="block">
                            @csrf
                            <button type="submit" class="w-full text-start min-h-[44px] px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-inset transition-all duration-200 ease-out cursor-pointer">{{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            </details>
        @endguest
            </div>

            <button id="mobile-menu-toggle" type="button" class="md:hidden min-h-[44px] min-w-[44px] p-2.5 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:ring-offset-2 rounded-xl transition-all duration-200 ease-out cursor-pointer motion-reduce:transition-none" aria-label="{{ __('Open menu') }}">
                <x-heroicon-o-bars-3 class="w-6 h-6" />
            </button>
        </div>
    </div>
</nav>

<div id="mobile-overlay" class="fixed inset-0 z-40 md:hidden bg-gray-900/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 ease-out" aria-hidden="true"></div>

<aside id="mobile-drawer"
    class="mobile-drawer fixed inset-y-0 start-0 z-50 md:hidden flex flex-col w-80 max-w-[85vw] bg-white shadow-2xl transition-transform duration-300 ease-out will-change-transform"
    aria-label="{{ __('Mobile menu') }}" aria-hidden="true">
    <div class="p-4 sm:p-6 border-b border-gray-200 flex items-center justify-between shrink-0 bg-white">
        <div>
            @auth
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $appName }}</p>
            @else
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $appName }}</p>
            @endauth
        </div>
        <div class="flex items-center gap-2">
            <x-dark-mode-toggle />
            <button id="mobile-drawer-close" type="button" class="min-h-[44px] min-w-[44px] p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-all duration-200 ease-out" aria-label="{{ __('Close menu') }}">
                <x-heroicon-o-x-mark class="w-6 h-6" />
            </button>
        </div>
    </div>
    <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain p-4 text-base" aria-label="{{ __('Mobile navigation') }}">
        @guest
            <div class="flex flex-col gap-y-0.5">
                <a href="{{ route('login') }}" class="{{ $mobileNavBase }} text-gray-700 hover:bg-gray-100/90 hover:text-gray-900">{{ __('login') }}</a>
                <a href="{{ route('register') }}" class="{{ $mobileNavBase }} text-gray-700 hover:bg-gray-100/90 hover:text-gray-900">{{ __('register') }}</a>
            </div>
        @else
            @php
                $currentOrgMobile = auth()->user()->currentOrganization;
            @endphp
            {{-- Main: section header + links (tighter spacing, active accent) --}}
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mt-0 mb-1 px-4">{{ __('Main') }}</p>
            <div class="flex flex-col gap-y-0.5 mb-2">
                @can('view-event-details')
                    <a href="{{ route('dashboard') }}" class="{{ $isDashboard ? $mobileNavActive : $mobileNavDefault }}" {{ $isDashboard ? 'aria-current="page"' : '' }}>{{ __('dashboard') }}</a>
                    <a href="{{ route('dashboard.events.index') }}" class="{{ $isEventsIndex ? $mobileNavActive : $mobileNavDefault }}" {{ $isEventsIndex ? 'aria-current="page"' : '' }}>{{ __('Events') }}</a>
                @endcan
                @can('manage-system')
                    <a href="{{ route('twilio.calling.index') }}" class="{{ $isCalling ? $mobileNavActive : $mobileNavDefault }}" {{ $isCalling ? 'aria-current="page"' : '' }}>{{ __('Calling') }}</a>
                @endcan
                @if($currentOrgMobile)
                    <div class="mx-1 my-1.5 p-2.5 bg-gray-50 rounded-xl border border-gray-200/80">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-0.5">{{ __('Current Organization') }}</p>
                        <p class="font-semibold text-gray-900 text-sm">{{ $currentOrgMobile->name }}</p>
                    </div>
                    <a href="{{ route('dashboard.organization-settings.edit') }}" class="{{ $isOrganizationSettings ? $mobileNavActive : $mobileNavDefault }}" {{ $isOrganizationSettings ? 'aria-current="page"' : '' }}>{{ __('Organization settings') }}</a>
                @endif
                <a href="{{ route('organizations.index') }}" class="{{ $isOrganizations ? $mobileNavActive : $mobileNavDefault }}" {{ $isOrganizations ? 'aria-current="page"' : '' }}>{{ __('Manage Organizations') }}</a>
                <a href="{{ route('billing.account') }}" class="{{ $isBilling ? $mobileNavActive : $mobileNavDefault }}" {{ $isBilling ? 'aria-current="page"' : '' }}>{{ __('Billing & Entitlements') }}</a>
                <a href="{{ route('profile') }}" class="{{ $isProfile ? $mobileNavActive : $mobileNavDefault }}" {{ $isProfile ? 'aria-current="page"' : '' }}>{{ __('Profile') }}</a>
            </div>
            @if(session('impersonation.original_organization_id'))
                <form method="POST" action="{{ route('system.impersonation.exit') }}">
                    @csrf
                    <button type="submit" class="text-start w-full {{ $mobileNavBase }} text-amber-600 hover:bg-amber-50/90 hover:text-amber-700 focus-visible:ring-amber-500/50 inline-flex items-center">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 me-2 shrink-0" />
                        {{ __('Exit impersonation') }}
                    </button>
                </form>
            @endif
            @can('manage-system')
                <div class="mt-3 pt-3 border-t border-gray-200/80">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-1.5 px-4 flex items-center gap-2">
                        {{ __('System Administration') }}
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/70 rounded-full" title="{{ __('Administrator access') }}">{{ __('Admin') }}</span>
                    </p>
                    <div class="bg-gray-50 rounded-lg p-1.5 flex flex-col gap-y-0.5">
                        <a href="{{ route('system.dashboard') }}" class="{{ $isSystemDashboard ? $mobileNavActive : $mobileNavDefault }}" {{ $isSystemDashboard ? 'aria-current="page"' : '' }}>{{ __('System Dashboard') }}</a>
                        <a href="/pulse" class="{{ $mobileNavDefault }}" target="_blank">{{ __('System Pulse') }}</a>
                        <a href="/telescope" class="{{ $mobileNavDefault }}" target="_blank">{{ __('Telescope') }}</a>
                        <a href="{{ route('system.organizations.index') }}" class="{{ $isSystemOrgs ? $mobileNavActive : $mobileNavDefault }}" {{ $isSystemOrgs ? 'aria-current="page"' : '' }}>{{ __('System Organizations') }}</a>
                        <a href="{{ route('system.accounts.index') }}" class="{{ $isSystemAccounts ? $mobileNavActive : $mobileNavDefault }}" {{ $isSystemAccounts ? 'aria-current="page"' : '' }}>{{ __('Accounts') }}</a>
                        <a href="{{ route('system.users.index') }}" class="{{ $isSystemUsers ? $mobileNavActive : $mobileNavDefault }}" {{ $isSystemUsers ? 'aria-current="page"' : '' }}>{{ __('System Users') }}</a>
                    </div>
                </div>
            @endcan
        @endguest
    </nav>
    {{-- Logout always visible at bottom of drawer (auth only) --}}
    @auth
    <div class="border-t border-gray-200 p-4 bg-white shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-start w-full {{ $mobileNavBase }} text-red-600 hover:bg-red-50 hover:text-red-700 focus-visible:ring-red-500/50 font-medium" aria-label="{{ __('Log out') }}">
                {{ __('Logout') }}
            </button>
        </form>
    </div>
    @endauth
</aside>

<script>
(function() {
    var toggle = document.getElementById('mobile-menu-toggle');
    var drawer = document.getElementById('mobile-drawer');
    var overlay = document.getElementById('mobile-overlay');
    var closeBtn = document.getElementById('mobile-drawer-close');
    if (!toggle || !drawer || !overlay || !closeBtn) return;

    function openDrawer() {
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer();
    });
})();
</script>
