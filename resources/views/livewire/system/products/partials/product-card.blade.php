@props(['product'])

<article class="group relative overflow-hidden rounded-[1.75rem] border border-stroke bg-card shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-2xl hover:shadow-brand/10">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand via-sky-500 to-emerald-500 opacity-80"></div>

    <div class="absolute top-4 end-4 z-10">
        <livewire:system.products.product-status-badge :status="$product->status" />
    </div>

    <div class="p-4 sm:p-6">
        <div class="flex items-start gap-4 mb-5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand/10 via-sky-50 to-emerald-50 text-sm font-black text-brand shadow-inner sm:size-14 sm:text-base">
                {{ mb_strtoupper(mb_substr($product->name, 0, 1, 'UTF-8'), 'UTF-8') }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="mb-1 text-base font-black leading-tight text-slate-900 transition-colors group-hover:text-brand sm:text-lg">
                    {{ $product->name }}
                </h3>
                <code class="inline-block break-all rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    {{ $product->slug }}
                </code>
                @if($product->category)
                    <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                        <x-heroicon-o-tag class="size-3" />
                        {{ $product->category }}
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs font-bold text-slate-600">
            <div class="rounded-2xl bg-slate-50 px-3 py-3">
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Grants') }}</div>
                <div class="mt-2 text-lg font-black text-slate-900">{{ $product->active_entitlements_count ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 px-3 py-3">
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Limits') }}</div>
                <div class="mt-2 text-lg font-black text-slate-900">{{ $product->active_limits_count ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 px-3 py-3">
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Features') }}</div>
                <div class="mt-2 text-lg font-black text-slate-900">{{ $product->enabled_features_count ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 px-3 py-3">
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Runtime') }}</div>
                <div class="mt-2 text-lg font-black text-slate-900">{{ $product->active_account_products_count ?? 0 }}</div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <div>
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Commercial Readiness') }}</div>
                <div class="mt-1 text-sm font-black text-slate-900">
                    {{ trans_choice(':count plan|:count plans', $product->product_plans_count ?? 0, ['count' => $product->product_plans_count ?? 0]) }}
                </div>
            </div>
            <x-heroicon-o-credit-card class="size-5 text-slate-300" />
        </div>

        <div class="mt-5 flex items-center gap-2 sm:gap-3">
            <a href="{{ route('system.products.show', $product) }}" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 font-black text-white transition-all active:scale-95 hover:bg-brand-hover">
                <x-heroicon-o-cog-6-tooth class="size-4" />
                <span class="text-sm">{{ __('Manage') }}</span>
            </a>
            <a href="{{ route('system.products.show', $product) }}" class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 font-bold text-slate-600 transition-all hover:bg-slate-50 sm:flex">
                                               <x-fwb-o-eye class="size-4" />
                <span class="text-sm">{{ __('View') }}</span>
            </a>
        </div>
    </div>
</article>
