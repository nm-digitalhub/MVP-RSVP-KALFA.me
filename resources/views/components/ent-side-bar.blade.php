@props(['collapsed' => false])

@php
    $currentRoute = request()->route()->getName();
    $organization = auth()->user()->currentOrganization;
@endphp

<aside
    x-data="{
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        toggleSidebar() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        }
    }"
    :class="collapsed ? 'w-14' : 'w-64'"
    class="bg-card border-r border-stroke flex-shrink-0 flex flex-col transition-all duration-200"
>
    {{-- Logo Area --}}
    <div class="h-12 flex items-center px-3 border-b border-stroke flex-shrink-0">
        <x-kalfa-app-icon class="h-6 w-6 flex-shrink-0" />
        <span x-show="!collapsed" class="ms-2.5 font-bold text-sm">{{ config('app.name') }}</span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                  {{ $currentRoute === 'dashboard' ? 'bg-brand/10 text-brand font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
            <x-heroicon-o-home class="h-4.5 w-4.5 flex-shrink-0" />
            <span x-show="!collapsed" class="text-xs">{{ __('Dashboard') }}</span>
        </a>

        {{-- Events --}}
        <a href="{{ route('dashboard.events.index') }}"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                  {{ str_starts_with($currentRoute, 'dashboard.events') ? 'bg-brand/10 text-brand font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
            <x-heroicon-o-calendar class="h-4.5 w-4.5 flex-shrink-0" />
            <span x-show="!collapsed" class="text-xs">{{ __('Events') }}</span>
        </a>

        {{-- Calling --}}
        @can('manage-system')
            <a href="{{ route('twilio.calling.index') }}"
               class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                      {{ str_starts_with($currentRoute, 'twilio') ? 'bg-brand/10 text-brand font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
                <x-heroicon-o-phone class="h-4.5 w-4.5 flex-shrink-0" />
                <span x-show="!collapsed" class="text-xs">{{ __('Calling') }}</span>
            </a>
        @endcan

        {{-- Billing Section --}}
        <div x-show="!collapsed">
            <p class="px-2.5 text-[9px] font-semibold text-content-muted uppercase tracking-wider mb-1 mt-3">
                {{ __('Billing') }}
            </p>
        </div>

        <a href="{{ route('billing.account') }}"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                  {{ str_starts_with($currentRoute, 'billing') ? 'bg-brand/10 text-brand font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
            <x-heroicon-o-credit-card class="h-4.5 w-4.5 flex-shrink-0" />
            <span x-show="!collapsed" class="text-xs">{{ __('Billing') }}</span>
        </a>

        {{-- Settings Section --}}
        <div x-show="!collapsed">
            <p class="px-2.5 text-[9px] font-semibold text-content-muted uppercase tracking-wider mb-1 mt-3">
                {{ __('Settings') }}
            </p>
        </div>

        <a href="{{ route('dashboard.organization-settings.edit', $organization) }}"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                  {{ str_starts_with($currentRoute, 'dashboard.organization-settings') ? 'bg-brand/10 text-brand font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
            <x-heroicon-o-cog-6-tooth class="h-4.5 w-4.5 flex-shrink-0" />
            <span x-show="!collapsed" class="text-xs">{{ __('Organization') }}</span>
        </a>

        {{-- Admin Section --}}
        @can('manage-system')
            <div x-show="!collapsed">
                <p class="px-2.5 text-[9px] font-semibold text-amber-600 uppercase tracking-wider mb-1 mt-3">
                    {{ __('Admin') }}
                </p>
            </div>

            <a href="{{ route('system.dashboard') }}"
               class="flex items-center gap-2 px-2.5 py-1.5 rounded-md transition-colors
                      {{ str_starts_with($currentRoute, 'system') ? 'bg-amber-50 text-amber-600 font-semibold' : 'text-content-muted hover:bg-surface hover:text-content' }}">
                <x-heroicon-o-shield-check class="h-4.5 w-4.5 flex-shrink-0" />
                <span x-show="!collapsed" class="text-xs">{{ __('System') }}</span>
            </a>
        @endcan
    </nav>

    {{-- Collapse Toggle --}}
    <button
        @click="toggleSidebar"
        class="h-9 flex items-center justify-center border-t border-stroke hover:bg-surface transition-colors flex-shrink-0"
        title="{{ $collapsed ? __('Expand sidebar') : __('Collapse sidebar') }}"
    >
        <x-heroicon-o-chevron-left x-show="!collapsed" class="h-4 w-4" />
        <x-heroicon-o-chevron-right x-show="collapsed" class="h-4 w-4" />
    </button>
</aside>
