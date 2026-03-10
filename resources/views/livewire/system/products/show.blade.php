<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 text-start sm:px-6 sm:py-10 lg:px-8" role="main" aria-label="{{ __('Product Administration') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
    <nav class="flex overflow-x-auto no-scrollbar pb-1" aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 whitespace-nowrap text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 sm:text-[11px] sm:space-x-2 rtl:gap-1.5 rtl:space-x-reverse">
            <li><a href="{{ route('system.dashboard') }}" class="transition-colors hover:text-indigo-600">{{ __('System') }}</a></li>
            <li><x-heroicon-m-chevron-right class="size-2.5 shrink-0 sm:size-3" /></li>
            <li><a href="{{ route('system.products.index') }}" class="transition-colors hover:text-indigo-600">{{ __('Products') }}</a></li>
            <li><x-heroicon-m-chevron-right class="size-2.5 shrink-0 sm:size-3" /></li>
            <li class="truncate text-slate-900" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200/60 bg-emerald-50 p-4 text-sm text-emerald-800" role="alert">
            <x-heroicon-o-check-circle class="size-5 text-emerald-600" />
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <section class="overflow-hidden rounded-[2rem] border border-white/60 bg-card/90 shadow-2xl shadow-slate-900/10 backdrop-blur-2xl">
        <div class="bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.15),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.12),_transparent_30%)] p-5 sm:p-8 lg:p-10">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="mb-5 flex items-start gap-4">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-brand/15 via-sky-50 to-emerald-50 text-lg font-black text-brand shadow-inner sm:size-16 sm:text-xl">
                            {{ mb_strtoupper(mb_substr($product->name, 0, 1, 'UTF-8'), 'UTF-8') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-4xl">{{ $product->name }}</h1>
                                <livewire:system.products.product-status-badge :status="$product->status" />
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <code class="inline-flex break-all rounded-xl bg-slate-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-slate-600">
                                    {{ $product->slug }}
                                </code>
                                @if($product->category)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500 shadow-sm">
                                        <x-heroicon-o-tag class="size-3.5" />
                                        {{ $product->category }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500 shadow-sm">
                                    <x-heroicon-o-hashtag class="size-3.5" />
                                    {{ __('Product #:id', ['id' => $product->id]) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-3xl space-y-3">
                        <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Product Engine Overview') }}</p>
                        <p class="text-sm leading-7 text-slate-600 sm:text-base">
                            {{ $product->description ?: __('This product does not have a published description yet. Use the editor below to document its domain purpose, commercial packaging, and runtime behavior.') }}
                        </p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm">
                            <x-heroicon-o-calendar class="size-4 text-slate-400" />
                            {{ __('Created :date', ['date' => $product->created_at->format('M d, Y')]) }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm">
                            <x-heroicon-o-clock class="size-4 text-slate-400" />
                            {{ __('Updated :date', ['date' => $product->updated_at->diffForHumans()]) }}
                        </span>
                    </div>
                </div>

                <div class="grid w-full shrink-0 gap-3 sm:grid-cols-2 xl:w-[22rem]">
                    @if(!$showEditForm)
                        <button wire:click="openEditForm" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 font-black text-slate-700 shadow-sm transition-all hover:bg-slate-50 data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-pencil-square class="size-5 text-brand" />
                            <span>{{ __('Edit Product') }}</span>
                        </button>
                    @else
                        <button wire:click="cancelEdit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 font-black text-rose-600 transition-all hover:bg-rose-100">
                            <x-heroicon-o-x-mark class="size-5" />
                            <span>{{ __('Cancel Edit') }}</span>
                        </button>
                    @endif

                    <a href="{{ route('system.products.index') }}" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 font-black text-slate-600 shadow-sm transition-all hover:bg-slate-50">
                        <x-heroicon-o-arrow-left class="size-5 rtl:rotate-180" />
                        <span>{{ __('Back to Catalog') }}</span>
                    </a>

                    <button
                        type="button"
                        wire:click="deleteProduct"
                        wire:confirm="{{ __('Are you sure you want to delete this product? This action cannot be undone.') }}"
                        class="sm:col-span-2 inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 font-black text-rose-600 transition-all hover:bg-rose-100 data-loading:pointer-events-none data-loading:opacity-60"
                    >
                        <x-heroicon-o-trash class="size-5" />
                        <span>{{ __('Delete Product') }}</span>
                    </button>
                </div>
            </div>

            @if($showEditForm)
                <form wire:submit.prevent="saveProduct" class="mt-8 rounded-[1.75rem] border border-slate-200 bg-white/80 p-5 shadow-xl shadow-slate-900/5 sm:p-8">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="edit-name" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Name') }}</label>
                            <input id="edit-name" wire:model.live.blur="name" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                            @error('name') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="edit-slug" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Slug') }}</label>
                            <div class="relative">
                                <span class="absolute start-4 top-1/2 -translate-y-1/2 text-xs font-black uppercase tracking-[0.18em] text-slate-300">/</span>
                                <input id="edit-slug" wire:model.live.blur="slug" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 py-4 pe-5 ps-10 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                            </div>
                            @error('slug') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="plan-slug" class="block px-1 text-[10px] font-black uppercase tracking-[0.20em] text-slate-400">{{ __('Slug') }}</label>
                                    <input id="plan-slug" wire:model.live.blur="planSlug" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                                    @error('planSlug') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="plan-sku" class="block px-1 text-[10px] font-black uppercase tracking-[0.20em] text-slate-400">{{ __('SKU') }}</label>
                                    <input id="plan-sku" wire:model.live.blur="planSku" type="text" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" />
                                    @error('planSku') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>
                            </div>

                        <div class="space-y-2">
                            <label for="edit-category" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Category') }}</label>
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

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Entitlements') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['entitlements'] }}</div>
        </div>
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Limits') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['limits'] }}</div>
        </div>
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Features') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['features'] }}</div>
        </div>
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Plans') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['plans'] }}</div>
        </div>
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Assignments') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['assignments'] }}</div>
        </div>
        <div class="rounded-3xl border border-stroke bg-card p-5 shadow-lg shadow-slate-900/5">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Recent Usage') }}</div>
            <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $overview['usage_records'] }}</div>
        </div>
    </section>

    @if($commercialInsights['hasPricingModel'])
        <section class="rounded-[2rem] border border-white/60 bg-card/90 p-5 shadow-xl shadow-slate-900/5 backdrop-blur sm:p-6">
            <div class="mb-5 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Commercial Layer') }}</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900">{{ __('Pricing Basis') }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ __('Operational cost model used to shape bundle pricing and overage rates for this product.') }}</p>
                </div>
                <x-heroicon-o-calculator class="size-6 text-slate-300" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Average Call') }}</div>
                    <div class="mt-2 text-2xl font-black text-slate-900">{{ $commercialInsights['assumedAverageCallMinutes'] }}</div>
                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ __('Minutes assumed per RSVP call') }}</div>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Direct Cost / Minute') }}</div>
                    <div class="mt-2 text-2xl font-black text-slate-900">${{ number_format((float) $commercialInsights['estimatedDirectCostUsdPerMinute'], 4) }}</div>
                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ __('Estimated blended vendor cost') }}</div>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Direct Cost / Call') }}</div>
                    <div class="mt-2 text-2xl font-black text-slate-900">${{ number_format((float) $commercialInsights['estimatedDirectCostUsdPerCall'], 4) }}</div>
                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ __('At the current average call duration') }}</div>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Target Margin') }}</div>
                    <div class="mt-2 text-2xl font-black text-slate-900">{{ $commercialInsights['targetMarginPercent'] }}%</div>
                    <div class="mt-1 text-xs font-semibold text-slate-500">{{ __('Configured commercial uplift') }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Cost Components') }}</div>
                    <div class="mt-4 space-y-3">
                        @foreach($commercialInsights['costComponents'] as $componentKey => $componentCost)
                            <div class="flex flex-col items-start gap-2 rounded-2xl bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                <div class="min-w-0">
                                    <div class="break-all text-xs font-black uppercase tracking-[0.16em] text-slate-500">{{ str_replace('_', ' ', $componentKey) }}</div>
                                </div>
                                <div class="shrink-0 text-sm font-black text-slate-900">${{ number_format((float) $componentCost, 4) }}/min</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{{ __('Pricing Sources') }}</div>
                    <div class="mt-4 space-y-3">
                        @foreach($commercialInsights['sources'] as $sourceKey => $sourceUrl)
                            <a href="{{ $sourceUrl }}" target="_blank" rel="noreferrer" class="flex flex-col items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">{{ str_replace('_', ' ', $sourceKey) }}</div>
                                    <div class="mt-1 break-all text-xs font-semibold text-slate-600">{{ $sourceUrl }}</div>
                                </div>
                                <x-heroicon-o-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Product Tree --}}
    <livewire:system.products.product-tree :product="$product" />


    <section class="overflow-hidden rounded-[2rem] border border-white/60 bg-card/90 shadow-2xl shadow-slate-900/10 backdrop-blur-2xl">
        <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/40 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Catalog Entitlements') }}</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">{{ __('Resource Grants') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('Manage the default grants that propagate from the product into account runtime state.') }}</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <span class="shrink-0 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Filter') }}</span>
                    <button wire:click="clearTypeFilter" class="shrink-0 rounded-lg border px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] transition-all {{ $filterType === null ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ __('All') }}
                    </button>
                    @foreach(\App\Enums\EntitlementType::cases() as $type)
                        <button wire:click="setFilterType('{{ $type->value }}')" class="shrink-0 rounded-lg border px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] transition-all {{ $filterButtonClasses[$type->value]['bgClass'] }} {{ $filterButtonClasses[$type->value]['textClass'] }} {{ $filterButtonClasses[$type->value]['borderClass'] }}">
                            {{ $type->label() }}
                        </button>
                    @endforeach
                </div>

                <button wire:click="openAddEntitlementForm" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-brand px-5 py-3 font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                    <x-heroicon-o-plus class="size-5" />
                    <span>{{ __('Add Grant') }}</span>
                </button>
            </div>
        </div>

        @if($showAddEntitlementForm)
            <div class="border-b border-indigo-100 bg-indigo-50/40 p-4 sm:p-6 lg:p-8">
                <form wire:submit.prevent="addEntitlement" class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="new-key" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/50">{{ __('Feature Key') }}</label>
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
                        <label for="new-label" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/50">{{ __('Display Label') }}</label>
                        <input id="new-label" wire:model.live.blur="newLabel" type="text" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Maximum Guests') }}" />
                        @error('newLabel') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="new-type" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/50">{{ __('Entitlement Type') }}</label>
                        <select id="new-type" wire:model.live="newType" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                            @foreach(\App\Enums\EntitlementType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="new-value" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/50">{{ __('Grant Value') }}</label>
                        <input id="new-value" wire:model.live.blur="newValue" type="text" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="1000" />
                        @error('newValue') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="new-desc" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/50">{{ __('Description') }}</label>
                        <textarea id="new-desc" wire:model.live.blur="newDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain what this grant does in the domain model.') }}"></textarea>
                        @error('newDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:col-span-2 sm:flex-row">
                        <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover data-loading:pointer-events-none data-loading:opacity-60">
                            <x-heroicon-o-plus class="size-5" />
                            <span>{{ __('Add Grant') }}</span>
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
                    <livewire:system.products.entitlement-row :entitlement="$entitlement" />
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
