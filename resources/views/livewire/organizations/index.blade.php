<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16" role="main" aria-label="{{ __('Organization management') }}">
    <div class="mb-8 flex flex-col gap-4 rounded-[2rem] border border-gray-200/70 bg-white/90 p-6 shadow-lg shadow-gray-900/5 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex size-14 items-center justify-center rounded-3xl bg-brand/5 ring-1 ring-brand/10">
                <x-kalfa-app-icon class="h-9 w-9" alt="" />
            </div>
            <div class="space-y-2">
                <x-kalfa-wordmark class="justify-start" />
                <p class="text-sm text-content-muted">{{ __('Manage your workspaces, switch context quickly, and keep your event operations organized.') }}</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50/90 border border-red-200/60 text-red-700 text-sm" role="alert">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0L7 9m5 5v0M12 9v2m0 0L5 9m5 5v0"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50/90 border border-green-200/60 text-green-800 text-sm" role="alert">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($currentOrg = auth()->user()->currentOrganization)
        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-indigo-50 to-white rounded-xl border border-indigo-100/50 shadow-sm" role="status" aria-live="polite">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14m14 0h-3.8a1 1 0 00-.8-9.5l-1.9-1.9a1 1 0 00-.8-9.5 6.9V21a1 1 0 00-.8-.5z"/>
                </svg>
                <p class="text-sm text-gray-700">{{ __('Current organization') }}: <strong class="text-indigo-700">{{ $currentOrg->name }}</strong></p>
            </div>
            @can('update', $currentOrg)
                <a href="{{ route('dashboard.organization-settings.edit') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 text-sm font-medium text-brand hover:text-indigo-800 hover:bg-indigo-100 rounded-lg transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2" aria-label="{{ __('Settings') }}">
                    {{ __('Settings') }}
                </a>
            @endcan
        </div>
    @endif

    {{-- CTA section — Design System primary --}}
    <div class="flex justify-start">
        <a href="{{ route('organizations.create') }}" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2.5 bg-brand border border-transparent rounded-lg text-sm font-medium text-white hover:bg-brand-hover active:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-colors duration-200" aria-label="{{ __('Create New Organization') }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Create New Organization') }}
        </a>
    </div>

    {{-- Centered card list — focused management layout --}}
    <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
        <div class="p-6">
            <ul class="space-y-3" role="list" aria-label="{{ __('Your organizations') }}">
                @forelse($organizations as $org)
                    <li wire:key="org-{{ $org->id }}">
                        <form action="{{ route('organizations.switch', $org) }}" method="POST" class="block" role="listitem">
                            @csrf
                            <button type="submit" class="w-full text-start rounded-xl border-2 border-gray-200/60 bg-gradient-to-br from-white to-gray-50/30 hover:border-indigo-300 hover:from-indigo-50 hover:to-white focus:outline-none focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/30 transition-all duration-200 ease-out min-h-[44px] p-4 flex items-center justify-between gap-4 cursor-pointer shadow-sm hover:shadow-md group relative" aria-label="{{ __('Switch to organization', ['name' => $org->name]) }}">
                                <span class="font-medium text-gray-900 group-hover:text-indigo-700 transition-colors duration-200">{{ $org->name }}</span>
                                <span class="text-sm text-gray-500 group-hover:text-brand/70 transition-colors duration-200">{{ $org->events_count ?? 0 }} {{ __('events') }}</span>
                                @if($currentOrg && $currentOrg->id === $org->id)
                                    <span class="absolute inset-0 rounded-xl border-2 border-brand bg-indigo-50/90 pointer-events-none flex items-center justify-end pe-4" aria-hidden="true">
                                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @endif
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-gray-500 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/30" role="status">
                        <div class="space-y-3">
                            <x-kalfa-app-icon class="mx-auto h-12 w-12 opacity-70" alt="" />
                            <p class="text-base font-medium text-gray-700">{{ __('No organizations yet.') }}</p>
                            <a href="{{ route('organizations.create') }}" class="inline-flex items-center justify-center gap-1 min-h-[44px] px-4 py-2.5 text-brand hover:text-indigo-800 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2" aria-label="{{ __('Create one') }}">
                                {{ __('Create one') }}
                            </a>
                        </div>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
