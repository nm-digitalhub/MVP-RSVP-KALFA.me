<div class="mx-auto max-w-7xl space-y-5 px-4 py-5 text-start sm:px-6 sm:py-8 lg:px-8" role="main" aria-label="{{ __('Product Administration') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}" data-audit-root="product-show">
    <nav class="flex overflow-x-auto no-scrollbar pb-1" aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 whitespace-nowrap text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 sm:text-[11px] sm:space-x-2 rtl:gap-1.5 rtl:space-x-reverse">
            <li><a href="{{ route('system.dashboard') }}" class="transition-colors hover:text-indigo-600">{{ __('System') }}</a></li>
            <li><x-heroicon-m-chevron-right class="size-2.5 shrink-0 sm:size-3" /></li>
            <li><a href="{{ route('system.products.index') }}" class="transition-colors hover:text-indigo-600">{{ __('Products') }}</a></li>
            <li><x-heroicon-m-chevron-right class="size-2.5 shrink-0 sm:size-3" /></li>
            <li class="truncate text-slate-900" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    @session('success')
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-4 text-sm text-emerald-800" role="alert">
            <x-heroicon-o-check-circle class="size-5 text-emerald-600" />
            <span class="font-semibold">{{ $value }}</span>
        </div>
    @endsession

    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="product-hero">
        <div class="p-3.5 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="mb-3 flex items-start gap-2.5 sm:mb-4 sm:gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-brand sm:size-11 sm:text-base">
                            {{ mb_strtoupper(mb_substr($product->name, 0, 1, 'UTF-8'), 'UTF-8') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Product Workspace') }}</p>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-lg font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ $product->name }}</h1>
                                <livewire:system.products.product-status-badge :status="$product->status" />
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <code class="inline-flex break-all rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">
                                    {{ $product->slug }}
                                </code>
                                @if($product->category)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                        <x-heroicon-o-tag class="size-3.5" />
                                        {{ $product->category }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                    <x-heroicon-o-hashtag class="size-3.5" />
                                    {{ __('Product #:id', ['id' => $product->id]) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-3xl space-y-2">
                        <p class="hidden text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 sm:block">{{ __('Product Engine Overview') }}</p>
                        <p class="text-[13px] leading-5 text-slate-600 sm:text-[15px] sm:leading-6">
                            {{ $product->description ?: __('This product does not have a published description yet. Use the editor below to document its domain purpose, commercial packaging, and runtime behavior.') }}
                        </p>
                    </div>

                    <div class="mt-4 grid gap-2 text-xs font-medium text-slate-500 sm:mt-6 sm:flex sm:flex-wrap sm:gap-3">
                        <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <x-heroicon-o-calendar class="size-4 text-slate-400" />
                            {{ __('Created :date', ['date' => $product->created_at->format('d.m.Y')]) }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <x-heroicon-o-clock class="size-4 text-slate-400" />
                            {{ __('Updated :date', ['date' => $product->updated_at->diffForHumans()]) }}
                        </span>
                    </div>
                </div>

                <div class="grid w-full shrink-0 grid-cols-2 gap-2 xl:flex xl:w-[17rem] xl:flex-col">
                    @if(!$showEditForm)
                        <button wire:click="openEditForm" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-100 data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-pencil-square class="size-4.5 text-brand" />
                            <span>{{ __('Edit Product') }}</span>
                        </button>
                    @else
                        <button wire:click="cancelEdit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600 transition-all hover:bg-rose-100">
                            <x-heroicon-o-x-mark class="size-4.5" />
                            <span>{{ __('Cancel Edit') }}</span>
                        </button>
                    @endif

                    <a href="{{ route('system.products.index') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition-all hover:bg-slate-100">
                        <x-heroicon-o-arrow-left class="size-4.5 rtl:rotate-180" />
                        <span>{{ __('Back to Catalog') }}</span>
                    </a>

                    <button
                        type="button"
                        wire:click="deleteProduct"
                        wire:confirm="{{ __('Are you sure you want to delete this product? This action cannot be undone.') }}"
                        class="col-span-2 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600 transition-all hover:bg-rose-100 data-loading:pointer-events-none data-loading:opacity-60 xl:col-span-1"
                    >
                        <x-heroicon-o-trash class="size-4.5" />
                        <span>{{ __('Delete Product') }}</span>
                    </button>
                </div>
            </div>

            @if($showEditForm)
                <form wire:submit.prevent="saveProduct" class="mt-8 rounded-[1.25rem] border border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="edit-name" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Name') }}</label>
                            <input id="edit-name" wire:model.live.blur="name" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                            @error('name') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="edit-slug" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Slug') }}</label>
                            <div class="relative">
                                <span class="absolute start-4 top-1/2 -translate-y-1/2 text-xs font-black uppercase tracking-[0.18em] text-slate-300">/</span>
                                <input id="edit-slug" wire:model.live.blur="slug" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 py-4 pe-5 ps-10 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                            </div>
                            @error('slug') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="edit-category" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Category') }}</label>
                            <input id="edit-category" wire:model.live.blur="category" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                            @error('category') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="edit-status" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Status') }}</label>
                            <select id="edit-status" wire:model.live="editStatus" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10">
                                <option value="draft">{{ __('Draft') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="archived">{{ __('Archived') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        <label for="edit-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                        <textarea id="edit-description" wire:model.live.blur="description" rows="4" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10"></textarea>
                        @error('description') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ __('Save Changes') }}</span>
                        </button>
                        <button type="button" wire:click="cancelEdit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-bold text-slate-600 transition-all hover:bg-slate-50">
                            <x-heroicon-o-arrow-uturn-left class="size-5" />
                            <span>{{ __('Reset') }}</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </section>

    <section class="grid grid-cols-2 gap-2.5 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3 2xl:grid-cols-6" data-audit-section="overview-stats">
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Entitlements') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['entitlements'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Limits') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['limits'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Features') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['features'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Plans') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['plans'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Assignments & Activation') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['assignments'] }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Recent Metered Activity') }}</div>
            <div class="mt-1.5 text-xl font-semibold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">{{ $overview['usage_records'] }}</div>
        </div>
    </section>

    @if($commercialInsights['hasPricingModel'])
        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-audit-section="commercial-layer">
            <div class="mb-4 flex flex-col items-start gap-3 sm:mb-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Commercial Layer') }}</p>
                    <h2 class="mt-1.5 text-base font-semibold text-slate-900 sm:mt-2 sm:text-xl">{{ __('Pricing Basis') }}</h2>
                    <p class="mt-1.5 text-[13px] leading-5 text-slate-500 sm:mt-2 sm:text-sm">{{ __('Operational cost model used to shape bundle pricing and overage rates for this product.') }}</p>
                </div>
                <x-heroicon-o-calculator class="hidden size-6 text-slate-300 sm:block" />
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-2 sm:gap-4 2xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Average Call') }}</div>
                    <div class="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">{{ $commercialInsights['assumedAverageCallMinutes'] }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('Minutes assumed per RSVP call') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Direct Cost / Minute') }}</div>
                    <div class="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">${{ number_format((float) $commercialInsights['estimatedDirectCostUsdPerMinute'], 4) }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('Estimated blended vendor cost') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Direct Cost / Call') }}</div>
                    <div class="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">${{ number_format((float) $commercialInsights['estimatedDirectCostUsdPerCall'], 4) }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('At the current average call duration') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Target Margin') }}</div>
                    <div class="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">{{ $commercialInsights['targetMarginPercent'] }}%</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('Configured commercial uplift') }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Cost Components') }}</div>
                    <div class="mt-4 space-y-3">
                        @foreach($commercialInsights['costComponents'] as $componentKey => $componentCost)
                            <div class="flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                <div class="min-w-0">
                                    <div class="break-all text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ str_replace('_', ' ', $componentKey) }}</div>
                                </div>
                                <div class="shrink-0 text-sm font-semibold text-slate-900">${{ number_format((float) $componentCost, 4) }}/min</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Pricing Sources') }}</div>
                    <div class="mt-4 space-y-3">
                        @foreach($commercialInsights['sources'] as $sourceKey => $sourceUrl)
                            <a href="{{ $sourceUrl }}" target="_blank" rel="noreferrer" class="flex flex-col items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ str_replace('_', ' ', $sourceKey) }}</div>
                                    <div class="mt-1 break-all text-xs font-semibold text-slate-600">{{ $sourceUrl }}</div>
                                </div>
                                <x-heroicon-o-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

    @foreach($productPlans->filter(fn($p) => data_get($p->metadata, 'commercial') !== null) as $commercialPlan)
        @php
            $planPrice = $commercialPlan->prices->first();
            $includedQty = (int) data_get($commercialPlan->metadata, 'commercial.included_quantity', 0);
            $overageMinor = (int) data_get($commercialPlan->metadata, 'commercial.overage_amount_minor', 0);
            $overageUnit = data_get($commercialPlan->metadata, 'commercial.overage_unit', '');
            $revenuePerCall = ($planPrice && $includedQty > 0)
                ? number_format($planPrice->amount / $includedQty / 100, 3)
                : '—';
        @endphp
        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6" data-audit-section="unit-economics">
            <div class="mb-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Unit Economics Snapshot') }}</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900 sm:text-xl">{{ $commercialPlan->name }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Included Capacity') }}</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $includedQty ?: '—' }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ data_get($commercialPlan->metadata, 'commercial.included_unit', '') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Overage Rate') }}</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $overageMinor > 0 ? number_format($overageMinor / 100, 2) : '—' }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $overageUnit ? __('per :unit', ['unit' => $overageUnit]) : '' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Revenue / Included Call') }}</div>
                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $revenuePerCall }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $planPrice?->currency }}</div>
                </div>
            </div>
        </section>
    @endforeach

    @endif

    {{-- Product Tree --}}
    <livewire:system.products.product-tree :product="$product" />

    @if($showAddPlanForm)
        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="plan-editor">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-6 sm:py-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Commercial') }}</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">{{ $editingPlanId ? __('Edit Plan') : __('Add Plan') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ __('Manage commercial packaging, limits, and overage settings for this plan.') }}</p>
            </div>

            <div class="p-4 sm:p-6">
                <form wire:submit.prevent="savePlan" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="space-y-2">
                        <label for="plan-name" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Plan Name') }}</label>
                        <input id="plan-name" wire:model.live.blur="planName" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planName') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-slug" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Slug') }}</label>
                        <input id="plan-slug" wire:model.live.blur="planSlug" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planSlug') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-sku" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('SKU') }}</label>
                        <input id="plan-sku" wire:model.live.blur="planSku" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planSku') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-included-unit" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Included Unit') }}</label>
                        <input id="plan-included-unit" wire:model.live.blur="planIncludedUnit" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planIncludedUnit') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-included-quantity" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Included Quantity') }}</label>
                        <input id="plan-included-quantity" wire:model.live.blur="planIncludedQuantity" type="number" min="0" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planIncludedQuantity') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-status" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Status') }}</label>
                        <select id="plan-status" wire:model.live="planIsActive" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10">
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="plan-rsvp-limit" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Voice RSVP Limit') }}</label>
                        <input id="plan-rsvp-limit" wire:model.live.blur="planVoiceRsvpLimit" type="number" min="0" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planVoiceRsvpLimit') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-minutes-limit" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Voice Minutes Limit') }}</label>
                        <input id="plan-minutes-limit" wire:model.live.blur="planVoiceMinutesLimit" type="number" min="0" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planVoiceMinutesLimit') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-overage-metric" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Overage Metric Key') }}</label>
                        <input id="plan-overage-metric" wire:model.live.blur="planOverageMetricKey" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planOverageMetricKey') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-overage-unit" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Overage Unit') }}</label>
                        <input id="plan-overage-unit" wire:model.live.blur="planOverageUnit" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planOverageUnit') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-overage-amount" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Overage Amount Minor') }}</label>
                        <input id="plan-overage-amount" wire:model.live.blur="planOverageAmountMinor" type="number" min="0" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planOverageAmountMinor') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="plan-margin" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Target Margin %') }}</label>
                        <input id="plan-margin" wire:model.live.blur="planTargetMarginPercent" type="number" min="0" max="100" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('planTargetMarginPercent') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 sm:col-span-2 xl:col-span-3">
                        <label for="plan-description" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Description') }}</label>
                        <textarea id="plan-description" wire:model.live.blur="planDescription" rows="3" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10"></textarea>
                        @error('planDescription') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 xl:col-span-3 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-semibold text-white transition hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ $editingPlanId ? __('Save Plan') : __('Create Plan') }}</span>
                        </button>
                        <button type="button" wire:click="cancelPlanEdit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-600 transition hover:bg-slate-50">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    @if($showPriceForm)
        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="price-editor">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-6 sm:py-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Pricing') }}</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">{{ $editingPriceId ? __('Edit Price') : __('Add Price') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ __('Configure pricing tiers and billing cycles for a plan.') }}</p>
            </div>

            <div class="p-4 sm:p-6">
                <form wire:submit.prevent="savePrice" class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="price-plan" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Plan') }}</label>
                        <select id="price-plan" wire:model.live="pricePlanId" class="block w-full cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" {{ $editingPriceId ? 'disabled' : '' }}>
                            @foreach($productPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->slug }})</option>
                            @endforeach
                        </select>
                        @error('pricePlanId') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price-currency" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Currency') }}</label>
                        <input id="price-currency" wire:model.live.blur="priceCurrency" type="text" maxlength="3" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('priceCurrency') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price-amount" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Amount (Minor Units)') }}</label>
                        <input id="price-amount" wire:model.live.blur="priceAmount" type="number" min="0" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('priceAmount') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                        <p class="text-[10px] text-slate-500">{{ __('Enter amount in minor units (cents for USD). For example, enter 9900 for $99.00.') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="price-cycle" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Billing Cycle') }}</label>
                        <select id="price-cycle" wire:model.live="priceBillingCycle" class="block w-full cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10">
                            @foreach(\App\Enums\ProductPriceBillingCycle::cases() as $cycle)
                                <option value="{{ $cycle->value }}">{{ $cycle->label() }}</option>
                            @endforeach
                        </select>
                        @error('priceBillingCycle') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price-status" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Status') }}</label>
                        <select id="price-status" wire:model.live="priceIsActive" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10">
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-semibold text-white transition hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ $editingPriceId ? __('Save Price') : __('Create Price') }}</span>
                        </button>
                        <button type="button" wire:click="cancelPriceEdit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-600 transition hover:bg-slate-50">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    @if($showAddLimitForm)
        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="limit-editor">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-6 sm:py-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Constraint') }}</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">{{ $editingLimitId ? __('Edit Limit') : __('Add Limit') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ __('Configure a product-level threshold or operational cap.') }}</p>
            </div>

            <div class="p-4 sm:p-6">
                <form wire:submit.prevent="saveLimit" class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="limit-key" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Limit Key') }}</label>
                        <input id="limit-key" wire:model.live.blur="limitKey" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('limitKey') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="limit-label" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Label') }}</label>
                        <input id="limit-label" wire:model.live.blur="limitLabel" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('limitLabel') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="limit-value" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Value') }}</label>
                        <input id="limit-value" wire:model.live.blur="limitValue" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('limitValue') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="limit-status" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Status') }}</label>
                        <select id="limit-status" wire:model.live="limitIsActive" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10">
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="limit-description" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Description') }}</label>
                        <textarea id="limit-description" wire:model.live.blur="limitDescription" rows="3" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10"></textarea>
                        @error('limitDescription') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-semibold text-white transition hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ $editingLimitId ? __('Save Limit') : __('Create Limit') }}</span>
                        </button>
                        <button type="button" wire:click="cancelLimitEdit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-600 transition hover:bg-slate-50">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    @if($showAddFeatureForm)
        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="feature-editor">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-6 sm:py-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Capability') }}</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">{{ $editingFeatureId ? __('Edit Feature') : __('Add Feature') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ __('Configure a product-level capability or feature flag.') }}</p>
            </div>

            <div class="p-4 sm:p-6">
                <form wire:submit.prevent="saveFeature" class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="feature-key" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Feature Key') }}</label>
                        <input id="feature-key" wire:model.live.blur="featureKey" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('featureKey') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="feature-label" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Label') }}</label>
                        <input id="feature-label" wire:model.live.blur="featureLabel" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('featureLabel') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="feature-value" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Value / Mode') }}</label>
                        <input id="feature-value" wire:model.live.blur="featureValue" type="text" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10" />
                        @error('featureValue') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="feature-status" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Status') }}</label>
                        <select id="feature-status" wire:model.live="featureIsEnabled" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10">
                            <option value="1">{{ __('Enabled') }}</option>
                            <option value="0">{{ __('Disabled') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="feature-description" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Description') }}</label>
                        <textarea id="feature-description" wire:model.live.blur="featureDescription" rows="3" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition focus:border-brand focus:ring-4 focus:ring-brand/10"></textarea>
                        @error('featureDescription') <p class="px-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-semibold text-white transition hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ $editingFeatureId ? __('Save Feature') : __('Create Feature') }}</span>
                        </button>
                        <button type="button" wire:click="cancelFeatureEdit" class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-600 transition hover:bg-slate-50">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="catalog-entitlements">
        <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50 px-4 py-5 sm:px-6 sm:py-5 lg:px-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Catalog Entitlements') }}</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900 sm:text-xl">{{ __('Resource Grants') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('Manage the default grants that propagate from the product into account runtime state.') }}</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <span class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Filter') }}</span>
                    <button wire:click="clearTypeFilter" class="shrink-0 rounded-lg border px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] transition-all {{ $filterType === null ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ __('All') }}
                    </button>
                    @foreach(\App\Enums\EntitlementType::cases() as $type)
                        <button wire:click="setFilterType('{{ $type->value }}')" class="shrink-0 rounded-lg border px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] transition-all {{ $filterButtonClasses[$type->value]['bgClass'] }} {{ $filterButtonClasses[$type->value]['textClass'] }} {{ $filterButtonClasses[$type->value]['borderClass'] }}">
                            {{ $type->label() }}
                        </button>
                    @endforeach
                </div>

                <button wire:click="openAddEntitlementForm" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-brand px-5 py-3 font-semibold text-white shadow-sm transition-all hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                    <x-heroicon-o-plus class="size-5" />
                    <span>{{ __('Add Grant') }}</span>
                </button>
            </div>
        </div>

        @if($showAddEntitlementForm)
            <div class="border-b border-slate-200 bg-slate-50 p-4 sm:p-6 lg:p-8">
                <form wire:submit.prevent="addEntitlement" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Grant Editor') }}</p>
                        <h3 class="mt-1.5 text-lg font-semibold text-slate-900">{{ $editingEntitlementId ? __('Edit Grant') : __('Add Grant') }}</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="new-key" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Feature Key') }}</label>
                        <input id="new-key" wire:model.live.blur="newFeatureKey" type="text" list="common-feature-keys" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="max_guests" />
                        <datalist id="common-feature-keys">
                            <option value="max_guests">
                            <option value="max_events">
                            <option value="twilio_enabled">
                            <option value="sms_confirmation_enabled">
                            <option value="sms_confirmation_limit">
                            <option value="whatsapp_enabled">
                            <option value="custom_domain_enabled">
                            <option value="voice_rsvp_enabled">
                            <option value="voice_rsvp_limit">
                        </datalist>
                        @error('newFeatureKey') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="new-label" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Display Label') }}</label>
                        <input id="new-label" wire:model.live.blur="newLabel" type="text" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Maximum Guests') }}" />
                        @error('newLabel') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="new-type" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Entitlement Type') }}</label>
                        <select id="new-type" wire:model.live="newType" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                            @foreach(\App\Enums\EntitlementType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="new-value" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Grant Value') }}</label>
                        <input id="new-value" wire:model.live.blur="newValue" type="text" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="1000" />
                        @error('newValue') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="entitlement-status" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Status') }}</label>
                        <select id="entitlement-status" wire:model.live="entitlementIsActive" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="new-desc" class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Description') }}</label>
                        <textarea id="new-desc" wire:model.live.blur="newDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain what this grant does in the domain model.') }}"></textarea>
                        @error('newDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <label class="block px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Constraints') }}</label>
                            <button
                                type="button"
                                wire:click="addConstraintRow"
                                class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                            >
                                <x-heroicon-o-plus class="size-3" />
                                <span>{{ __('Add Constraint') }}</span>
                            </button>
                        </div>

                        <div class="space-y-2">
                            @foreach(array_keys($this->newConstraints) as $index)
                                <div class="flex gap-2 items-start">
                                    <div class="flex-1 space-y-2">
                                        <input
                                            type="text"
                                            wire:model.live.blur="newConstraints.{{ $index }}.key"
                                            placeholder="{{ __('Key (e.g., max_events)') }}"
                                            class="w-full rounded-xl border border-transparent bg-white px-4 py-3 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-4 focus:ring-brand/10"
                                        />
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                wire:model.live.blur="newConstraints.{{ $index }}.value"
                                                placeholder="{{ __('Value') }}"
                                                class="flex-1 rounded-xl border border-transparent bg-white px-4 py-3 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-4 focus:ring-brand/10"
                                            />
                                            <select
                                                wire:model.live="newConstraints.{{ $index }}.type"
                                                class="w-32 rounded-xl border border-transparent bg-white px-3 py-3 text-xs font-bold text-slate-700 shadow-sm transition-all focus:border-brand focus:ring-2 focus:ring-brand/10"
                                            >
                                                <option value="string">{{ __('String') }}</option>
                                                <option value="number">{{ __('Number') }}</option>
                                                <option value="boolean">{{ __('Boolean') }}</option>
                                                <option value="json">{{ __('JSON') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeConstraintRow({{ $index }})"
                                        class="mt-2 inline-flex size-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 sm:mt-0"
                                    >
                                        <x-heroicon-o-trash class="size-4" />
                                    </button>
                                </div>
                                @error("newConstraints.{$index}.key") <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            @endforeach

                            @if(empty($this->newConstraints))
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-400">
                                    {{ __('No constraints defined. Click "Add Constraint" to add one.') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-semibold text-white shadow-sm transition-all hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-check class="size-5" />
                            <span>{{ $editingEntitlementId ? __('Save Grant') : __('Add Grant') }}</span>
                        </button>
                        <button type="button" wire:click="closeAddEntitlementForm" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 font-bold text-slate-600 transition-all hover:bg-slate-50">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="p-4 sm:p-6 lg:p-8">
            <div class="space-y-4">
                @forelse($entitlements as $entitlement)
                    <livewire:system.products.entitlement-row :entitlement="$entitlement" :key="'entitlement-'.$entitlement->id" />
                @empty
                    <div class="rounded-[1.75rem] border border-dashed border-slate-200 px-6 py-12 text-center">
                        <x-heroicon-o-gift class="mx-auto size-12 text-slate-300" />
                        <p class="mt-4 text-base font-black text-slate-500">{{ __('No entitlements defined yet') }}</p>
                        <p class="mt-2 text-sm text-slate-400">{{ __('Start by adding the grants that should propagate from the product into account runtime state.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
