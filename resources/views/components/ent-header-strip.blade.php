@props(['breadcrumbs' => []])

<header class="h-14 bg-card border-b border-stroke flex items-center justify-between px-4 flex-shrink-0">
    {{-- Left: Breadcrumbs or Organization Name --}}
    <div class="flex items-center gap-2 text-sm min-w-0">
        @if(count($breadcrumbs) > 0)
            @foreach($breadcrumbs as $index => $crumb)
                @if($index > 0)
                    <x-heroicon-o-chevron-right class="h-4 w-4 text-content-muted flex-shrink-0" />
                @endif

                @if($index === count($breadcrumbs) - 1)
                    <span class="font-bold text-content truncate">{{ $crumb['label'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="text-content-muted hover:text-content truncate">
                        {{ $crumb['label'] }}
                    </a>
                @endif
            @endforeach
        @else
            <span class="font-bold text-content">{{ auth()->user()->currentOrganization->name }}</span>
        @endif
    </div>

    {{-- Right: Utilities --}}
    <div class="flex items-center gap-2">
        {{-- Organization Switcher --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-surface transition-colors">
                <div class="h-6 w-6 rounded-full bg-brand/10 flex items-center justify-center text-xs font-bold text-brand">
                    {{ substr(auth()->user()->currentOrganization->name, 0, 1) }}
                </div>
                <span class="text-sm font-medium hidden sm:inline">{{ auth()->user()->currentOrganization->name }}</span>
                <x-heroicon-o-chevron-down :class="open ? 'rotate-180' : ''" class="h-4 w-4 text-content-muted transition-transform duration-200" />
            </button>

            @if(auth()->user()->organizations->count() > 1)
                <div x-show="open" @click.away="open = false" class="absolute end-0 mt-1 w-64 bg-card border border-stroke rounded-lg shadow-lg py-2 z-50">
                    <p class="px-4 py-2 text-[10px] font-bold text-content-muted uppercase tracking-wider">
                        {{ __('Your Organizations') }}
                    </p>
                    @foreach(auth()->user()->organizations as $org)
                        <form action="{{ route('organizations.switch', $org) }}" method="POST" class="block">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-start text-sm hover:bg-surface flex items-center justify-between">
                                <span>{{ $org->name }}</span>
                                @if($org->id === auth()->user()->current_organization->id)
                                    <x-heroicon-o-check class="h-4 w-4 text-brand" />
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- User Menu --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-brand to-brand-hover flex items-center justify-center text-white font-bold text-sm">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </button>

            <div x-show="open" @click.away="open = false" class="absolute end-0 mt-1 w-48 bg-card border border-stroke rounded-lg shadow-lg py-2 z-50">
                <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm font-medium hover:bg-surface">
                    {{ __('My Profile') }}
                </a>

                <hr class="my-2 border-stroke">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-start px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 flex items-center gap-2">
                        <x-heroicon-o-arrow-left-on-rectangle class="h-4 w-4" />
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
