<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:space-y-8 sm:px-6 sm:py-12 lg:px-8" role="main" aria-label="{{ __('Product Catalog Administration') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between md:gap-8">
        <div class="flex-1 min-w-0">
            {{-- Breadcrumb --}}
            <nav class="flex overflow-x-auto no-scrollbar pb-1" aria-label="Breadcrumb">
                <ol class="flex items-center gap-1.5 sm:space-x-2 rtl:space-x-reverse rtl:gap-1.5 text-[10px] sm:text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 whitespace-nowrap">
                    <li><a href="{{ route('system.dashboard') }}" class="hover:text-indigo-600 transition-colors">{{ __('System') }}</a></li>
                    <li><x-heroicon-m-chevron-right class="size-2.5 sm:size-3 shrink-0" /></li>
                    <li class="text-slate-900 truncate" aria-current="page">{{ __('Product Catalog') }}</li>
                </ol>
            </nav>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter leading-none break-words mt-3 sm:mt-4">{{ __('Product Platform') }}</h1>
            <p class="text-xs sm:text-sm lg:text-lg text-slate-500 font-medium italic mt-2">
                {{ __('Browse catalog, commercial readiness, and runtime activation signals for every product.') }}
            </p>
        </div>

        {{-- Create button - FAB style on mobile, inline on desktop --}}
        <div class="flex shrink-0">
            <button wire:click="openCreateModal" class="group inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-2xl bg-brand px-4 py-3 text-sm font-black text-white shadow-xl shadow-brand/20 transition-all active:scale-95 hover:bg-brand-hover sm:min-h-[60px] sm:w-auto sm:gap-3 sm:px-8 sm:py-4 sm:text-lg">
                <x-heroicon-o-plus class="size-5 sm:size-6" />
                <span>{{ __('Create Product') }}</span>
            </button>
        </div>
    </div>

    @session('success')
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-sm flex items-center gap-3 backdrop-blur-sm animate-in fade-in slide-in-from-top-4" role="alert">
            <x-heroicon-o-check-circle class="size-5 text-emerald-600" />
            <span class="font-medium text-start">{{ $value }}</span>
        </div>
    @endsession

    {{-- Stats Overview - Compact cards --}}
    <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
        {{-- Total Products --}}
        <div class="bg-card rounded-2xl shadow-lg shadow-slate-900/5 border border-stroke p-4 sm:p-6 flex flex-col justify-between">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Total') }}</span>
            <div class="mt-4 sm:mt-6 flex items-end justify-between">
                <span class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['total'] }}</span>
                <div class="size-10 sm:size-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                    <x-heroicon-o-cube class="size-5 sm:size-6" />
                </div>
            </div>
        </div>

        {{-- Active Products --}}
        <div class="bg-card rounded-2xl shadow-lg shadow-slate-900/5 border border-stroke p-4 sm:p-6 flex flex-col justify-between">
            <span class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em]">{{ __('Active') }}</span>
            <div class="mt-4 sm:mt-6 flex items-end justify-between">
                <span class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['active'] }}</span>
                <div class="size-10 sm:size-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <x-heroicon-o-check-circle class="size-5 sm:size-6" />
                </div>
            </div>
        </div>

        {{-- Draft Products --}}
        <div class="bg-card rounded-2xl shadow-lg shadow-slate-900/5 border border-stroke p-4 sm:p-6 flex flex-col justify-between">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Drafts') }}</span>
            <div class="mt-4 sm:mt-6 flex items-end justify-between">
                <span class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['draft'] }}</span>
                <div class="size-10 sm:size-12 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                    <x-heroicon-o-pencil class="size-5 sm:size-6" />
                </div>
            </div>
        </div>

        <div class="bg-card rounded-2xl shadow-lg shadow-slate-900/5 border border-stroke p-4 sm:p-6 flex flex-col justify-between">
            <span class="text-[10px] font-black text-brand uppercase tracking-[0.2em]">{{ __('Live Assignments') }}</span>
            <div class="mt-4 sm:mt-6 flex items-end justify-between">
                <span class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['assignments'] }}</span>
                <div class="size-10 sm:size-12 rounded-xl bg-brand/10 flex items-center justify-center text-brand">
                    <x-heroicon-o-bolt class="size-5 sm:size-6" />
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold text-slate-500">{{ __(':plans active plans across the catalog', ['plans' => $stats['plans']]) }}</p>
        </div>

        @if(count($categories) > 0)
            <div class="hidden lg:flex bg-card rounded-2xl shadow-lg shadow-slate-900/5 border border-stroke p-4 sm:p-6 flex-col justify-between">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Categories') }}</span>
                <div class="mt-4 sm:mt-6 flex flex-wrap gap-2">
                    @foreach($categories as $category => $count)
                        <span class="inline-flex px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-wider">
                            {{ $category }} ({{ $count }})
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Filters & Search Bar --}}
    <div class="overflow-hidden rounded-3xl border border-white/50 bg-card/90 shadow-2xl shadow-slate-900/10 backdrop-blur-2xl">
        <div class="p-4 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-[11px] font-bold text-slate-500">
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                    <x-heroicon-o-squares-2x2 class="size-3.5" />
                    {{ __('Catalog') }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                    <x-heroicon-o-credit-card class="size-3.5" />
                    {{ __('Commercial') }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                    <x-heroicon-o-signal class="size-3.5" />
                    {{ __('Runtime') }}
                </span>
                <span class="text-xs">{{ __('Use the filters below to find products that are still draft-only or already assigned to live accounts.') }}</span>
            </div>

            {{-- Search input --}}
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                <div class="grow relative">
                    <x-heroicon-o-magnifying-glass class="absolute start-4 top-1/2 -translate-y-1/2 size-5 text-slate-400 focus-within:text-brand transition-colors" />
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, slug, or description...') }}" class="w-full pe-14 ps-12 py-3 sm:py-4 rounded-2xl bg-slate-50 border-transparent focus:bg-white focus:ring-8 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold shadow-inner" />
                </div>

                {{-- Filter buttons --}}
                <div class="grid shrink-0 grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <button wire:click="setFilterStatus(null)" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border px-4 py-2 text-sm font-bold {{ $filterStatus === null ? 'bg-brand text-white border-brand' : 'bg-white border-slate-200 text-slate-600' }} hover:{{ $filterStatus === null ? 'bg-brand-hover' : 'bg-slate-50' }} transition-all cursor-pointer">
                        {{ __('All') }}
                    </button>
                    <button wire:click="setFilterStatus(App\Enums\ProductStatus::Active)" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border px-4 py-2 text-sm font-bold {{ $filterStatus === \App\Enums\ProductStatus::Active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-white border-slate-200 text-slate-600' }} hover:{{ $filterStatus === \App\Enums\ProductStatus::Active ? 'bg-emerald-200' : 'bg-slate-50' }} transition-all cursor-pointer">
                        {{ __('Active') }}
                    </button>
                    <button wire:click="setFilterStatus(App\Enums\ProductStatus::Draft)" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border px-4 py-2 text-sm font-bold {{ $filterStatus === \App\Enums\ProductStatus::Draft ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-white border-slate-200 text-slate-600' }} hover:{{ $filterStatus === \App\Enums\ProductStatus::Draft ? 'bg-slate-200' : 'bg-slate-50' }} transition-all cursor-pointer">
                        {{ __('Drafts') }}
                    </button>
                    @if($filterStatus || $filterCategory || $search !== '')
                        <button wire:click="clearFilters" class="col-span-2 inline-flex min-h-[44px] items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-600 transition-all hover:bg-rose-100 sm:col-auto">
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Category filter pills --}}
            @if(count($categories) > 0)
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] shrink-0">{{ __('Category:') }}</span>
                    <button wire:click="setFilterCategory(null)" class="shrink-0 px-3 py-1.5 rounded-lg border {{ $filterCategory === null ? 'bg-brand text-white border-brand' : 'bg-white border-slate-200 text-slate-600' }} hover:{{ $filterCategory === null ? 'bg-brand-hover' : 'bg-slate-50' }} transition-all cursor-pointer text-[10px] font-black uppercase tracking-wider">
                        {{ __('All') }}
                    </button>
                    @foreach($categories as $category => $count)
                        <button wire:key="cat-filter-{{ $category }}" wire:click="setFilterCategory('{{ $category }}')" class="shrink-0 px-3 py-1.5 rounded-lg border {{ $filterCategory === $category ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white border-slate-200 text-slate-600' }} hover:{{ $filterCategory === $category ? 'bg-indigo-200' : 'bg-slate-50' }} transition-all cursor-pointer text-[10px] font-black uppercase tracking-wider">
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Products Grid - Cards View --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
        @forelse($products as $product)
            <livewire:system.products.product-card :product="$product" :key="'product-card-'.$product->id" />
        @empty
            <div class="col-span-full py-16 text-center">
                <div class="inline-flex flex-col items-center gap-4">
                    <x-heroicon-o-inbox class="size-16 text-slate-300" />
                    <p class="text-lg font-bold text-slate-500">{{ __('No products found') }}</p>
                    <p class="text-sm text-slate-400">{{ __('Try adjusting your filters or create a new product.') }}</p>
                    <button wire:click="openCreateModal" class="mt-4 inline-flex items-center gap-2 px-6 py-3 bg-brand text-white font-black rounded-xl hover:bg-brand-hover transition-all cursor-pointer">
                        <x-heroicon-o-plus class="size-5" />
                        <span>{{ __('Create First Product') }}</span>
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8 flex justify-center">
        {{ $products->links() }}
    </div>
</div>

@push('styles')
<style>
    /* RTL-aware positioning */
    [dir="rtl"] .absolute.top-4.end-4 {
        left: 1rem;
        right: auto;
    }
    [dir="ltr"] .absolute.top-4.end-4 {
        right: 1rem;
        left: auto;
    }
    [dir="rtl"] .start-4 {
        left: 1rem;
        right: auto;
    }
    [dir="ltr"] .start-4 {
        left: 1rem;
        right: auto;
    }
</style>
@endpush
