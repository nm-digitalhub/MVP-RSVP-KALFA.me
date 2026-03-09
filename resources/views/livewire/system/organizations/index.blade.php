<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10" role="main" aria-label="{{ __('System Organizations Management') }}">
    {{-- Header Section --}}
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-black text-gray-900 tracking-tight leading-none">{{ __('System Organizations') }}</h1>
            <p class="mt-3 text-lg text-gray-500 font-medium leading-relaxed">{{ __('Complete overview and administrative control of all platform tenants.') }}</p>
        </div>
        <div class="flex items-center gap-4 shrink-0">
            <div class="px-5 py-3 rounded-2xl bg-white border border-gray-200 shadow-sm ring-1 ring-gray-900/5 flex items-center gap-4 transition-all hover:shadow-md">
                <div class="size-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <x-heroicon-o-building-office class="size-6" />
                </div>
                <div>
                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Active Tenants') }}</span>
                    <span class="text-2xl font-black text-gray-900 leading-none">{{ $organizations->total() }}</span>
                </div>
            </div>
        </div>
    </header>

    {{-- Advanced Filters Card --}}
    <section class="bg-white/80 rounded-[2.5rem] shadow-2xl shadow-gray-900/5 border border-gray-200/60 overflow-hidden backdrop-blur-xl animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
        <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
            <x-heroicon-o-funnel class="size-4 text-gray-400" />
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('Smart Filtering') }}</h2>
        </div>
        <div class="p-8 sm:p-10">
            <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-5 space-y-2">
                    <x-input-label for="filter-name" :value="__('Search Organization')" class="text-[10px] font-black text-gray-500 uppercase tracking-tighter px-1" />
                    <div class="relative group">
                        <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none transition-colors group-focus-within:text-indigo-600 text-gray-400">
                            <x-heroicon-o-magnifying-glass class="size-5" />
                        </div>
                        <input id="filter-name" type="text" wire:model.live.debounce.300ms="search_name" class="block w-full ps-12 py-4 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-8 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold shadow-inner" placeholder="{{ __('Name, ID or domain...') }}" />
                    </div>
                </div>
                <div class="lg:col-span-3 space-y-2">
                    <x-input-label for="filter-owner" :value="__('Owner Email')" class="text-[10px] font-black text-gray-500 uppercase tracking-tighter px-1" />
                    <input id="filter-owner" type="text" wire:model.live.debounce.300ms="search_owner_email" class="block w-full py-4 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-8 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold shadow-inner" placeholder="owner@email.com" />
                </div>
                <div class="lg:col-span-2 space-y-2">
                    <x-input-label for="filter-status" :value="__('Status')" class="text-[10px] font-black text-gray-500 uppercase tracking-tighter px-1" />
                    <select id="filter-status" wire:model.live="filter_suspended" class="block w-full py-4 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-8 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold shadow-inner appearance-none cursor-pointer">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="0">{{ __('Active Only') }}</option>
                        <option value="1">{{ __('Suspended') }}</option>
                    </select>
                </div>
                <div class="lg:col-span-2 space-y-2">
                    <x-input-label for="filter-activity" :value="__('Activity')" class="text-[10px] font-black text-gray-500 uppercase tracking-tighter px-1" />
                    <select id="filter-activity" wire:model.live="filter_no_events" class="block w-full py-4 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-8 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold shadow-inner appearance-none cursor-pointer">
                        <option value="">{{ __('Any Activity') }}</option>
                        <option value="1">{{ __('Inactive (No Events)') }}</option>
                    </select>
                </div>
            </form>
        </div>
    </section>

    {{-- Table Section --}}
    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-gray-900/5 border border-gray-200/70 overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-200">
        <div class="overflow-x-auto no-scrollbar">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ __('Organization Entity') }}</th>
                        <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ __('Primary Owner') }}</th>
                        <th scope="col" class="px-8 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ __('Health Stats') }}</th>
                        <th scope="col" class="px-8 py-5 text-start text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ __('Platform Status') }}</th>
                        <th scope="col" class="px-8 py-5 text-end text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @forelse($organizations as $org)
                        <tr wire:key="org-{{ $org->id }}" class="group hover:bg-indigo-50/20 transition-all duration-300">
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex items-center gap-5">
                                    <div class="size-14 rounded-[1.25rem] bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-xl shadow-lg group-hover:rotate-3 transition-transform duration-300">
                                        {{ substr($org->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('system.organizations.show', $org) }}" class="text-lg font-black text-gray-900 hover:text-indigo-600 transition-colors leading-none block mb-1 underline-offset-4 hover:underline">{{ $org->name }}</a>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">UID: #{{ $org->id }}</span>
                                            <span class="text-[10px] text-gray-300">•</span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">{{ $org->created_at->format('M Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-gray-800 leading-none">{{ $org->owner()?->name ?? '—' }}</span>
                                    <span class="text-xs font-medium text-gray-400 mt-1.5">{{ $org->owner()?->email }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-6">
                                    <div class="flex flex-col items-center">
                                        <span class="text-base font-black text-gray-900 leading-none">{{ $org->users_count ?? 0 }}</span>
                                        <span class="text-[9px] font-black text-gray-400 uppercase mt-1 tracking-tighter">{{ __('Users') }}</span>
                                    </div>
                                    <div class="w-px h-6 bg-gray-100"></div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-base font-black text-gray-900 leading-none">{{ $org->events_count ?? 0 }}</span>
                                        <span class="text-[9px] font-black text-gray-400 uppercase mt-1 tracking-tighter">{{ __('Events') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap">
                                @if($org->is_suspended ?? false)
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-red-50 text-red-700 ring-1 ring-red-200/50">
                                        <div class="size-1.5 rounded-full bg-red-600 animate-pulse"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Suspended') }}</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-green-50 text-green-700 ring-1 ring-green-200/50">
                                        <div class="size-1.5 rounded-full bg-green-600"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Active') }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-6 whitespace-nowrap text-end">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('system.organizations.show', $org) }}" class="size-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-indigo-600 hover:text-white flex items-center justify-center transition-all shadow-sm active:scale-90" aria-label="{{ __('View Details') }}">
                                        <x-heroicon-o-arrow-right class="size-5" />
                                    </a>
                                    <form action="{{ route('system.impersonate', $org) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="{{ __('Impersonate') }}" class="size-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-amber-500 hover:text-white flex items-center justify-center transition-all shadow-sm active:scale-90 cursor-pointer">
                                            <x-heroicon-o-user-circle class="size-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-24 text-center">
                                <div class="max-w-sm mx-auto">
                                    <div class="size-24 bg-gray-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner ring-1 ring-gray-100">
                                        <x-heroicon-o-magnifying-glass-circle class="size-12 text-gray-300" />
                                    </div>
                                    <h3 class="text-xl font-black text-gray-900 leading-tight">{{ __('No Organizations Found') }}</h3>
                                    <p class="text-gray-500 font-medium mt-2 leading-relaxed">{{ __('Adjust your search parameters or check filters to see more results.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 border-t border-gray-100 bg-gray-50/30">
            {{ $organizations->links() }}
        </div>
    </div>
</div>
