{{-- Tailwind v4 Product UI: Mobile-first navbar + drawer. No Bootstrap, no UI kits. --}}
@props(['location' => 'header'])
@php
  $appName = config('app.name');
  $isSystemAdmin = auth()->check() && auth()->user()->is_system_admin;
@endphp

<nav class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4">
    <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-gray-900">
        {{ $appName }}
    </a>

    {{-- Desktop nav --}}
    <div class="hidden md:flex items-center gap-6">
        @guest
            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Login</a>
            <a href="{{ route('register') }}" class="text-gray-600 hover:text-gray-900">Register</a>
        @else
            @php
                $currentOrg = auth()->user()->currentOrganization();
                $userOrganizations = auth()->user()->organizations()->orderBy('name')->get();
            @endphp
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
            <details class="relative group">
                <summary class="flex items-center gap-1 cursor-pointer list-none text-gray-600 hover:text-gray-900 focus:outline-none font-medium [&::-webkit-details-marker]:hidden">
                    <span>{{ $currentOrg ? $currentOrg->name : __('Organizations') }}</span>
                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="absolute right-0 mt-1 w-56 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5 z-50 border border-gray-200">
                    @foreach($userOrganizations as $org)
                        <div class="block">
                            <form action="{{ route('organizations.switch', $org) }}" method="POST" class="w-full text-left">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between">
                                    <span>{{ $org->name }}</span>
                                    @if($currentOrg && $currentOrg->id === $org->id)
                                        <span class="text-indigo-600">✓</span>
                                    @endif
                                </button>
                            </form>
                        </div>
                    @endforeach
                    <div class="border-t border-gray-100 mt-1 pt-1">
                        <a href="{{ route('organizations.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Manage Organizations') }}</a>
                    </div>
                </div>
            </details>
            @if(session('impersonation.original_organization_id'))
                <form method="POST" action="{{ route('system.impersonation.exit') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-amber-600 hover:text-amber-800 text-sm font-medium">{{ __('Exit impersonation') }}</button>
                </form>
            @endif
            <a href="{{ route('profile') }}" class="text-gray-600 hover:text-gray-900">Profile</a>
            @if($isSystemAdmin)
                <span class="text-gray-300" aria-hidden="true">|</span>
                <a href="{{ route('system.dashboard') }}" class="{{ request()->routeIs('system.dashboard') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">{{ __('System Dashboard') }}</a>
                <a href="{{ route('system.organizations.index') }}" class="{{ request()->routeIs('system.organizations.*') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">{{ __('System Organizations') }}</a>
                <a href="{{ route('system.users.index') }}" class="{{ request()->routeIs('system.users.*') ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">{{ __('System Users') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-gray-900">Logout</button>
            </form>
        @endguest
    </div>

    <button id="mobile-menu-toggle" type="button" class="md:hidden p-2 text-gray-600 hover:text-gray-900" aria-label="Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>
</nav>

<div id="mobile-overlay" class="fixed inset-0 bg-black/40 hidden z-40 md:hidden" aria-hidden="true"></div>

<aside id="mobile-drawer"
    class="fixed inset-y-0 left-0 w-64 bg-white shadow-xl transform -translate-x-full transition duration-300 z-50 md:hidden">
    <div class="p-6 border-b border-gray-200">
        @auth
            <p class="font-medium text-gray-900">{{ auth()->user()->name }}</p>
        @else
            <p class="font-medium text-gray-900">{{ $appName }}</p>
        @endauth
    </div>
    <nav class="flex flex-col p-6 space-y-6 text-lg text-gray-700">
        @guest
            <a href="{{ route('login') }}" class="hover:text-gray-600">Login</a>
            <a href="{{ route('register') }}" class="hover:text-gray-600">Register</a>
        @else
            @php
                $currentOrgMobile = auth()->user()->currentOrganization();
            @endphp
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
            @if($currentOrgMobile)
                <p class="text-sm text-gray-500">{{ __('Current') }}: <span class="font-medium text-gray-900">{{ $currentOrgMobile->name }}</span></p>
            @endif
            <a href="{{ route('organizations.index') }}" class="hover:text-gray-600">{{ __('Manage Organizations') }}</a>
            @if(session('impersonation.original_organization_id'))
                <form method="POST" action="{{ route('system.impersonation.exit') }}">
                    @csrf
                    <button type="submit" class="text-left text-amber-600 hover:text-amber-800 w-full">{{ __('Exit impersonation') }}</button>
                </form>
            @endif
            <a href="{{ route('profile') }}" class="hover:text-gray-600">{{ __('Profile') }}</a>
            @if($isSystemAdmin)
                <span class="text-gray-400 text-sm border-t border-gray-200 pt-4 mt-2 w-full">{{ __('System Admin') }}</span>
                <a href="{{ route('system.dashboard') }}" class="{{ request()->routeIs('system.dashboard') ? 'font-semibold text-gray-900' : 'hover:text-gray-600' }}">{{ __('System Dashboard') }}</a>
                <a href="{{ route('system.organizations.index') }}" class="{{ request()->routeIs('system.organizations.*') ? 'font-semibold text-gray-900' : 'hover:text-gray-600' }}">{{ __('System Organizations') }}</a>
                <a href="{{ route('system.users.index') }}" class="{{ request()->routeIs('system.users.*') ? 'font-semibold text-gray-900' : 'hover:text-gray-600' }}">{{ __('System Users') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-left hover:text-gray-600 w-full">Logout</button>
            </form>
        @endguest
    </nav>
</aside>

<script>
(function() {
    var toggle = document.getElementById('mobile-menu-toggle');
    var drawer = document.getElementById('mobile-drawer');
    var overlay = document.getElementById('mobile-overlay');
    if (!toggle || !drawer || !overlay) return;
    toggle.addEventListener('click', function() {
        drawer.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
    overlay.addEventListener('click', function() {
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });
})();
</script>
