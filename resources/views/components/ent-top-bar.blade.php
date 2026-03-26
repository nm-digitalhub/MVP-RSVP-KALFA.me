@props(['breadcrumbs' => []])

@php
    $isSystemAdmin = auth()->check() && auth()->user()->is_system_admin;
    $isBilling = request()->routeIs('billing.*');
    $isSystem = str_contains(request()->route()?->getName() ?? '', 'system.');
@endphp

<header class="h-12 bg-card border-b border-stroke flex items-center justify-between px-3 sm:px-4 flex-shrink-0">
    {{-- Left: Breadcrumbs or Organization Name --}}
    <div class="flex items-center gap-1.5 text-xs sm:text-sm min-w-0">
        @if(count($breadcrumbs) > 0)
            @foreach($breadcrumbs as $index => $crumb)
                @if($index > 0)
                    <x-heroicon-o-chevron-right class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-content-muted flex-shrink-0" />
                @endif

                @if($index === count($breadcrumbs) - 1)
                    <span class="font-semibold text-content truncate">{{ $crumb['label'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="text-content-muted hover:text-content truncate">
                        {{ $crumb['label'] }}
                    </a>
                @endif
            @endforeach
        @else
            <span class="font-semibold text-content truncate">{{ auth()->user()->currentOrganization->name }}</span>
        @endif
    </div>

    {{-- Right: Utilities --}}
    <div class="flex items-center gap-2">
        {{-- Organization Switcher --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-1.5 px-2 py-1 rounded-md hover:bg-surface transition-colors">
                <div class="h-5 w-5 rounded-md bg-brand/10 flex items-center justify-center text-[10px] font-bold text-brand">
                    {{ substr(auth()->user()->currentOrganization->name, 0, 1) }}
                </div>
                <span class="text-xs font-medium hidden sm:inline truncate max-w-[120px]">{{ auth()->user()->currentOrganization->name }}</span>
                <x-heroicon-o-chevron-down :class="open ? 'rotate-180' : ''" class="h-3.5 w-3.5 text-content-muted transition-transform duration-200 flex-shrink-0" />
            </button>

            @if(auth()->user()->organizations->count() > 1)
                <div x-show="open" @click.away="open = false" class="absolute end-0 mt-1 w-56 bg-card border border-stroke rounded-lg shadow-lg py-1.5 z-50">
                    <p class="px-3 py-1.5 text-[9px] font-bold text-content-muted uppercase tracking-wider">
                        {{ __('Organizations') }}
                    </p>
                    @foreach(auth()->user()->organizations as $org)
                        <form action="{{ route('organizations.switch', $org) }}" method="POST" class="block">
                            @csrf
                            <button type="submit" class="w-full px-3 py-1.5 text-start text-xs hover:bg-surface flex items-center justify-between gap-2">
                                <span class="truncate">{{ $org->name }}</span>
                                @if((int) $org->id === (int) auth()->user()->current_organization_id)
                                    <x-heroicon-o-check class="h-3.5 w-3.5 text-brand flex-shrink-0" />
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- User Menu --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-1.5">
                <div class="h-7 w-7 rounded-md bg-gradient-to-br from-brand to-brand-hover flex items-center justify-center text-white font-bold text-xs">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </button>

            <div x-show="open" @click.away="open = false" class="absolute end-0 mt-1 w-44 bg-card border border-stroke rounded-lg shadow-lg py-1.5 z-50">
                <a href="{{ route('profile') }}" class="block px-3 py-1.5 text-xs font-medium hover:bg-surface">
                    {{ __('My Profile') }}
                </a>

                <hr class="my-1 border-stroke">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-start px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 flex items-center gap-2">
                        <x-heroicon-o-arrow-left-on-rectangle class="h-3.5 w-3.5" />
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
