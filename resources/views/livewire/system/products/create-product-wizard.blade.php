<div class="mx-auto max-w-6xl px-4 py-8 text-start sm:px-6 sm:py-12 lg:px-8" role="main" aria-label="{{ __('Create Product Wizard') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('system.products.index') }}" class="group inline-flex items-center gap-2 self-start rounded-xl px-4 py-2 text-sm font-bold text-slate-500 transition-all hover:bg-slate-100 hover:text-brand">
            <x-heroicon-m-arrow-left class="size-4 transition-transform group-hover:-translate-x-1 rtl:rotate-180" />
            <span>{{ __('Cancel & Exit') }}</span>
        </a>

        @if ($product)
            <div class="inline-flex items-center gap-2 self-start rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-amber-700 sm:self-auto">
                <span>{{ __('Draft Product') }}</span>
                <span>#{{ $product->id }}</span>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800" role="alert">
            <x-heroicon-o-check-circle class="mt-0.5 size-5 shrink-0 text-emerald-600" />
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="mb-10 overflow-hidden rounded-[2rem] border border-white/70 bg-card/90 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur xl:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-[0.3em] text-slate-400">{{ __('Product Domain Wizard') }}</p>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">{{ __('Create a draft-backed product') }}</h1>
                <p class="max-w-2xl text-sm font-medium leading-6 text-slate-500 sm:text-base">
                    {{ __('The product is created as a real draft entity in step one. Every entitlement, limit, and feature is then attached directly to that product through Eloquent relationships.') }}
                </p>
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-600">
                        <x-heroicon-o-squares-2x2 class="size-3.5" />
                        {{ __('Catalog Layer') }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-600">
                        <x-heroicon-o-credit-card class="size-3.5" />
                        {{ __('Commercial Layer') }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-600">
                        <x-heroicon-o-bolt class="size-3.5" />
                        {{ __('Runtime Ready') }}
                    </span>
                </div>
            </div>

            @if ($product)
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-slate-100 px-4 py-3">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Status') }}</div>
                        <div class="mt-2 text-sm font-black text-slate-900">{{ $product->status->label() }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-100 px-4 py-3">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Entitlements') }}</div>
                        <div class="mt-2 text-sm font-black text-slate-900">{{ $entitlements->count() }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-100 px-4 py-3">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Limits') }}</div>
                        <div class="mt-2 text-sm font-black text-slate-900">{{ $limits->count() }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-100 px-4 py-3">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Features') }}</div>
                        <div class="mt-2 text-sm font-black text-slate-900">{{ $features->count() }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-12">
        <div class="relative flex items-center justify-between gap-3">
            <div class="absolute inset-x-0 top-5 hidden h-1 rounded-full bg-slate-200 sm:block"></div>
            <div class="absolute top-5 hidden h-1 rounded-full bg-brand transition-all duration-500 sm:block" style="width: {{ ($step - 1) / max($totalSteps - 1, 1) * 100 }}%; {{ isRTL() ? 'right: 0;' : 'left: 0;' }}"></div>

            @foreach ([1 => __('Product Info'), 2 => __('Entitlements'), 3 => __('Limits & Config'), 4 => __('Plans & Pricing'), 5 => __('Review & Publish')] as $index => $label)
                <div class="relative flex flex-1 flex-col items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full border-4 text-sm font-black transition-all duration-300 sm:size-12 {{ $step >= $index ? 'border-brand bg-brand text-white shadow-lg shadow-brand/20' : 'border-slate-200 bg-white text-slate-400' }}">
                        @if ($step > $index)
                            <x-heroicon-m-check class="size-5" />
                        @else
                            {{ $index }}
                        @endif
                    </div>
                    <span class="text-center text-[10px] font-black uppercase tracking-[0.18em] {{ $step >= $index ? 'text-brand' : 'text-slate-400' }}">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-[2rem] border border-white/70 bg-card/95 shadow-2xl shadow-slate-900/10">
        @if ($step === 1)
            <section class="space-y-8 p-6 sm:p-8 lg:p-10">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ __('Step 1: Product identity') }}</h2>
                    <p class="text-sm font-medium leading-6 text-slate-500 sm:text-base">
                        {{ __('Validate the core product info and create the draft Product model immediately. If you return to this step later, the same draft record is updated instead of creating another one.') }}
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)]">
                    <div class="space-y-6 rounded-[1.75rem] bg-slate-50 p-5 sm:p-6">
                        <div class="space-y-2">
                            <label for="product-name" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Name') }}</label>
                            <input id="product-name" type="text" wire:model.live.blur="name" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-base font-bold text-slate-900 shadow-sm transition-all placeholder:text-slate-300 focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Premium Voice Suite') }}" />
                            @error('name') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <label for="product-slug" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Slug') }}</label>
                                <button type="button" wire:click="generateSlug" class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-brand transition hover:bg-brand hover:text-white">
                                    {{ __('Generate') }}
                                </button>
                            </div>
                            <div class="relative">
                                <span class="absolute start-5 top-1/2 -translate-y-1/2 text-xs font-black uppercase tracking-[0.18em] text-slate-300">/</span>
                                <input id="product-slug" type="text" wire:model.live.blur="slug" class="block w-full rounded-2xl border border-transparent bg-white py-4 pe-5 ps-10 text-base font-bold text-slate-900 shadow-sm transition-all placeholder:text-slate-300 focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('premium-voice-suite') }}" />
                            </div>
                            @error('slug') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="product-category" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Category') }}</label>
                                <input id="product-category" type="text" wire:model.live.blur="category" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-base font-bold text-slate-900 shadow-sm transition-all placeholder:text-slate-300 focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Communications') }}" />
                                @error('category') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="rounded-[1.5rem] bg-brand px-5 py-5 text-white shadow-xl shadow-brand/20">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/70">{{ __('Draft lifecycle') }}</div>
                                <p class="mt-3 text-sm font-semibold leading-6 text-white/90">
                                    {{ __('Submitting this step creates the product with draft status so the next steps can create related records against a real product id.') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="product-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                            <textarea id="product-description" wire:model.live.blur="description" rows="4" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-base font-bold text-slate-900 shadow-sm transition-all placeholder:text-slate-300 focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain what this product enables, who it is for, and what makes it publish-ready.') }}"></textarea>
                            @error('description') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <aside class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-white p-5 text-slate-900 sm:p-6">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ __('Domain rule') }}</p>
                            <h3 class="mt-2 text-xl font-black">{{ __('The product is the aggregate root') }}</h3>
                        </div>
                        <div class="space-y-3 text-sm font-medium leading-6 text-slate-900">
                            <p>{{ __('Step 1 creates the real Product model instead of caching temporary arrays in component state.') }}</p>
                            <p>{{ __('Later steps call Eloquent relationships directly, which keeps persistence, validation, and future auditing aligned with the domain model.') }}</p>
                            <p>{{ __('This also makes draft recovery, autosave, background processing, and collaboration extensions straightforward later.') }}</p>
                        </div>

                        @if ($product)
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/50">{{ __('Current draft') }}</div>
                                <div class="mt-3 space-y-2 text-sm font-semibold">
                                    <div>{{ $product->name }}</div>
                                    <div class="break-all text-white/70">/{{ $product->slug }}</div>
                                    <div class="text-white/70">{{ $product->status->label() }}</div>
                                </div>
                            </div>
                        @endif
                    </aside>
                </div>
            </section>
        @endif

        @if ($step === 2)
            <section class="space-y-8 p-6 sm:p-8 lg:p-10">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ __('Step 2: Attach entitlements') }}</h2>
                    <p class="text-sm font-medium leading-6 text-slate-500 sm:text-base">
                        {{ __('Each entitlement is written as a real `product_entitlements` record using the current draft product. Nothing here lives as a flat array in the wizard.') }}
                    </p>
                </div>

                <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <form wire:submit.prevent="addEntitlement" class="space-y-5 rounded-[1.75rem] bg-slate-50 p-5 sm:p-6">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="entitlement-key" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Feature Key') }}</label>
                                <input id="entitlement-key" type="text" wire:model.live.blur="entitlementFeatureKey" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('max_events') }}" />
                                @error('entitlementFeatureKey') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="entitlement-label" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Label') }}</label>
                                <input id="entitlement-label" type="text" wire:model.live.blur="entitlementLabel" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Maximum Events') }}" />
                                @error('entitlementLabel') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="entitlement-type" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Type') }}</label>
                                <select id="entitlement-type" wire:model.live="entitlementType" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                                    @foreach (\App\Enums\EntitlementType::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                @error('entitlementType') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="entitlement-value" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Value') }}</label>
                                <input id="entitlement-value" type="text" wire:model.live.blur="entitlementValue" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('100') }}" />
                                @error('entitlementValue') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="entitlement-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                            <textarea id="entitlement-description" wire:model.live.blur="entitlementDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain what this grant does in the product domain.') }}"></textarea>
                            @error('entitlementDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 text-sm font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover">
                            <x-heroicon-o-plus class="size-5" />
                            <span>{{ __('Add Entitlement') }}</span>
                        </button>
                    </form>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">{{ __('Draft entitlements') }}</h3>
                                <p class="text-sm font-medium text-slate-500">{{ __('Existing database records linked to this draft product.') }}</p>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-500">
                                {{ $entitlements->count() }}
                            </div>
                        </div>
                        <div class="space-y-3">
                            @if($product)
@forelse ($entitlements as $entitlement)                                <div wire:key="wizard-entitlement-{{ $entitlement->id }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-slate-900">{{ $entitlement->label }}</span>
                                                <span class="rounded-full bg-white px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">{{ $entitlement->type->label() }}</span>
                                            </div>
                                            <div class="break-all text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ $entitlement->feature_key }}</div>
                                            @if ($entitlement->value)
                                                <div class="text-sm font-semibold text-slate-600">{{ __('Value: :value', ['value' => $entitlement->value]) }}</div>
                                            @endif
                                            @if ($entitlement->description)
                                                <p class="text-sm font-medium leading-6 text-slate-500">{{ $entitlement->description }}</p>
                                            @endif
                                        </div>

                                        <button type="button" wire:click="removeEntitlement({{ $entitlement->id }})" class="inline-flex min-h-[44px] items-center justify-center gap-2 self-start rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-rose-600 transition hover:bg-rose-100">
                                            <x-heroicon-o-trash class="size-4" />
                                            <span>{{ __('Remove') }}</span>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm font-semibold text-slate-400">
                                    {{ __('No entitlements attached yet. Add at least the core grants that define the product.') }}
                                </div>
                            @endforelse
                        @endif
                    </div>
                    </div>
                </div>
            </section>
        @endif
        

        @if ($step === 3)
            <section class="space-y-8 p-6 sm:p-8 lg:p-10">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ __('Step 3: Limits and feature configuration') }}</h2>
                    <p class="text-sm font-medium leading-6 text-slate-500 sm:text-base">
                        {{ __('Attach operational limits and feature configuration as first-class related records so the draft product becomes the single source of truth for all later billing and provisioning logic.') }}
                    </p>
                </div>

                @if (! $supportsLimits || ! $supportsFeatures)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                        <div class="font-black">{{ __('Database migration required') }}</div>
                        <p class="mt-2 font-medium leading-6">
                            {{ __('The limits and feature tables are not available yet in this environment. Run the latest product migrations to enable this step fully.') }}
                        </p>
                    </div>
                @endif

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="space-y-6 rounded-[1.75rem] bg-slate-50 p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">{{ __('Product limits') }}</h3>
                                <p class="text-sm font-medium text-slate-500">{{ __('Numeric or textual constraints that can be enforced downstream.') }}</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-500">{{ $limits->count() }}</span>
                        </div>

                        <form wire:submit.prevent="addLimit" class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="limit-key" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Limit Key') }}</label>
                                    <input id="limit-key" type="text" wire:model.live.blur="limitKey" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('monthly_calls') }}" />
                                    @error('limitKey') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="limit-label" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Label') }}</label>
                                    <input id="limit-label" type="text" wire:model.live.blur="limitLabel" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Monthly Calls') }}" />
                                    @error('limitLabel') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="limit-value" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Value') }}</label>
                                    <input id="limit-value" type="text" wire:model.live.blur="limitValue" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('1000') }}" />
                                    @error('limitValue') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                    <input type="checkbox" wire:model.live="limitIsActive" class="size-5 rounded border-slate-300 text-brand focus:ring-brand/20" />
                                    <span class="text-sm font-bold text-slate-700">{{ __('Limit is active') }}</span>
                                </label>
                            </div>

                            <div class="space-y-2">
                                <label for="limit-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                                <textarea id="limit-description" wire:model.live.blur="limitDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Describe how this limit should be interpreted in the system.') }}"></textarea>
                                @error('limitDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 text-sm font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover">
                                <x-heroicon-o-plus class="size-5" />
                                <span>{{ __('Add Limit') }}</span>
                            </button>
                            @error('limits') <p class="text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </form>

                        <div class="space-y-3">
                            @forelse ($limits as $limit)
                                <div wire:key="wizard-limit-{{ $limit->id }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-slate-900">{{ $limit->label }}</span>
                                                <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] {{ $limit->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ $limit->is_active ? __('Active') : __('Disabled') }}
                                                </span>
                                            </div>
                                            <div class="break-all text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ $limit->limit_key }}</div>
                                            <div class="text-sm font-semibold text-slate-600">{{ __('Value: :value', ['value' => $limit->value]) }}</div>
                                            @if ($limit->description)
                                                <p class="text-sm font-medium leading-6 text-slate-500">{{ $limit->description }}</p>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="toggleLimit({{ $limit->id }})" class="inline-flex min-h-[38px] items-center justify-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-slate-600 transition hover:bg-slate-200">
                                                @if($limit->is_active)
                                                    <x-fwb-o-eye-slash class="size-4"/>
                                                @else
                                                    <x-fwb-o-eye class="size-4"/>
                                                @endif
                                                <span>{{ $limit->is_active ? __('Disable') : __('Enable') }}</span>
                                            </button>
                                            <button type="button" wire:click="removeLimit({{ $limit->id }})" class="inline-flex min-h-[38px] items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-rose-600 transition hover:bg-rose-100">
                                                <x-heroicon-o-trash class="size-4" />
                                                <span>{{ __('Remove') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-6 text-center text-sm font-semibold text-slate-400">
                                    {{ __('No limits have been attached yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">{{ __('Product features') }}</h3>
                                <p class="text-sm font-medium text-slate-500">{{ __('Feature toggles and configuration values linked directly to the draft product.') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-500">{{ $features->count() }}</span>
                        </div>

                        <form wire:submit.prevent="addFeature" class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="feature-key" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Feature Key') }}</label>
                                    <input id="feature-key" type="text" wire:model.live.blur="featureKey" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('voice_routing') }}" />
                                    @error('featureKey') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="feature-label" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Label') }}</label>
                                    <input id="feature-label" type="text" wire:model.live.blur="featureLabel" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Voice Routing') }}" />
                                    @error('featureLabel') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="feature-value" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Config Value') }}</label>
                                    <input id="feature-value" type="text" wire:model.live.blur="featureValue" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('regional-failover') }}" />
                                    @error('featureValue') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <input type="checkbox" wire:model.live="featureIsEnabled" class="size-5 rounded border-slate-300 text-brand focus:ring-brand/20" />
                                    <span class="text-sm font-bold text-slate-700">{{ __('Feature enabled') }}</span>
                                </label>
                            </div>

                            <div class="space-y-2">
                                <label for="feature-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                                <textarea id="feature-description" wire:model.live.blur="featureDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-slate-50 px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:bg-white focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain how this feature should be activated or interpreted.') }}"></textarea>
                                @error('featureDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-sm font-black text-white shadow-xl shadow-slate-900/20 transition-all hover:bg-slate-700">
                                <x-heroicon-o-plus class="size-5" />
                                <span>{{ __('Add Feature') }}</span>
                            </button>
                            @error('features') <p class="text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </form>

                        <div class="space-y-3">
                            @forelse ($features as $feature)
                                <div wire:key="wizard-feature-{{ $feature->id }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-slate-900">{{ $feature->label }}</span>
                                                <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] {{ $feature->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ $feature->is_enabled ? __('Enabled') : __('Disabled') }}
                                                </span>
                                            </div>
                                            <div class="break-all text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ $feature->feature_key }}</div>
                                            @if ($feature->value)
                                                <div class="text-sm font-semibold text-slate-600">{{ __('Config: :value', ['value' => $feature->value]) }}</div>
                                            @endif
                                            @if ($feature->description)
                                                <p class="text-sm font-medium leading-6 text-slate-500">{{ $feature->description }}</p>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="toggleFeature({{ $feature->id }})" class="inline-flex min-h-[38px] items-center justify-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-slate-600 transition hover:bg-slate-200">
                                                @if($feature->is_enabled)
                                                    <x-fwb-o-eye-slash class="size-4"/>
                                                @else
                                                    <x-fwb-o-eye class="size-4"/>
                                                @endif
                                                <span>{{ $feature->is_enabled ? __('Disable') : __('Enable') }}</span>
                                            </button>
                                            <button type="button" wire:click="removeFeature({{ $feature->id }})" class="inline-flex min-h-[38px] items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-rose-600 transition hover:bg-rose-100">
                                                <x-heroicon-o-trash class="size-4" />
                                                <span>{{ __('Remove') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-6 text-center text-sm font-semibold text-slate-400">
                                    {{ __('No feature records attached yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($step === 4)
            <section class="space-y-8 p-6 sm:p-8 lg:p-10">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ __('Step 4: Plans and pricing') }}</h2>
                    <p class="text-sm font-medium leading-6 text-slate-500 sm:text-base">
                        {{ __('Create the commercial plans for this product. A SKU will be automatically generated for each plan based on the product and plan slugs.') }}
                    </p>
                </div>

                <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <form wire:submit.prevent="addPlan" class="space-y-5 rounded-[1.75rem] bg-slate-50 p-5 sm:p-6">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="plan-name" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Plan Name') }}</label>
                                <input id="plan-name" type="text" wire:model.live.blur="planName" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Basic Plan') }}" />
                                @error('planName') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="plan-slug" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Slug') }}</label>
                                <input id="plan-slug" type="text" wire:model.live.blur="planSlug" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('basic-plan') }}" />
                                @error('planSlug') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="plan-amount" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Price (Cents)') }}</label>
                                <input id="plan-amount" type="number" wire:model.live.blur="planAmount" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('2990') }}" />
                                @error('planAmount') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="plan-currency" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Currency') }}</label>
                                <select id="plan-currency" wire:model.live="planCurrency" class="block w-full cursor-pointer rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10">
                                    <option value="USD">USD</option>
                                    <option value="ILS">ILS</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="plan-description" class="block px-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Description') }}</label>
                            <textarea id="plan-description" wire:model.live.blur="planDescription" rows="3" class="block w-full rounded-2xl border border-transparent bg-white px-5 py-4 text-sm font-bold text-slate-900 shadow-sm transition-all focus:border-brand focus:ring-8 focus:ring-brand/10" placeholder="{{ __('Explain what is included in this plan.') }}"></textarea>
                            @error('planDescription') <p class="px-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl bg-brand px-6 py-3 text-sm font-black text-white shadow-xl shadow-brand/20 transition-all hover:bg-brand-hover">
                            <x-heroicon-o-plus class="size-5" />
                            <span>{{ __('Add Plan') }}</span>
                        </button>
                    </form>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">{{ __('Draft plans') }}</h3>
                                <p class="text-sm font-medium text-slate-500">{{ __('Commercial models attached to this product.') }}</p>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-500">
                                {{ $product?->productPlans->count() ?? 0 }}
                            </div>
                        </div>

<div
    x-data
    x-init="
        Sortable.create($el,{
            animation:150,
            handle:'.drag-handle',
            onEnd(event){
                $wire.reorderPlans(
                    [...$el.children].map(el => el.dataset.id)
                )
            }
        })
    "
    class="space-y-3"
>
                            @if($product)
                                @forelse ($product->productPlans as $plan)
<div
data-id="{{ $plan->id }}"
wire:key="wizard-plan-{{ $plan->id }}"
class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 flex gap-3"
>
                                        {{-- Drag Handle --}}
                                        <div class="drag-handle cursor-move text-slate-400 flex items-start pt-1 hover:text-brand transition-colors" title="{{ __('Drag to reorder') }}">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"/>
                                            </svg>
                                        </div>

                                        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="space-y-2">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-black text-slate-900">{{ $plan->name }}</span>
                                                    @if($plan->primaryPrice())
                                                        <span class="rounded-full bg-brand/10 px-2 py-1 text-[10px] font-black text-brand uppercase">{{ $plan->primaryPrice()->amount / 100 }} {{ $plan->primaryPrice()->currency }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col gap-1">
                                                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                                                        {{ $plan->slug }}
                                                    </div>

                                                    <div class="flex items-center gap-1 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                                        <x-iconsax-bol-barcode class="size-3" />
                                                        <span>{{ $plan->sku }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="button" wire:click="removePlan({{ $plan->id }})" class="inline-flex min-h-[44px] items-center justify-center gap-2 self-start rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-rose-600 transition hover:bg-rose-100">
                                                <x-heroicon-o-trash class="size-4" />
                                                <span>{{ __('Remove') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm font-semibold text-slate-400">
                                        {{ __('No plans created yet. Add at least one commercial plan.') }}
                                    </div>
                                @endforelse
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif
    
        @if ($step === 5)
            <section class="space-y-8 p-6 sm:p-8 lg:p-10">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ __('Step 5: Review and publish') }}</h2>
                    <p class="text-sm font-medium leading-6 text-slate-500 sm:text-base">
                        {{ __('Review the persisted draft product and publish it by promoting the status from draft to active.') }}
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                    <div class="space-y-4 rounded-[1.75rem] bg-slate-50 p-5 sm:p-6">
                        <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Product') }}</div>
                            <div class="mt-3 space-y-2">
                                <div class="text-2xl font-black text-slate-900">{{ $product?->name }}</div>
                                <div class="break-all text-xs font-black uppercase tracking-[0.18em] text-slate-400">/{{ $product?->slug }}</div>
                                @if ($product?->category)
                                    <div class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-indigo-700">{{ $product->category }}</div>
                                @endif
                                @if ($product?->description)
                                    <p class="text-sm font-medium leading-6 text-slate-500">{{ $product->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-4">
                            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Entitlements') }}</div>
                                <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $entitlements->count() }}</div>
                            </div>
                            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Limits') }}</div>
                                <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $limits->count() }}</div>
                            </div>
                            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Features') }}</div>
                                <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $features->count() }}</div>
                            </div>
                            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ __('Plans') }}</div>
                                <div class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $product?->productPlans->count() ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <h3 class="text-lg font-black text-slate-900">{{ __('Publish checklist') }}</h3>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-amber-700">{{ __('Draft → Active') }}</span>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 px-4 py-4">
                                <div class="text-sm font-black text-slate-900">{{ __('Core identity') }}</div>
                                <p class="mt-2 text-sm font-medium leading-6 text-slate-500">{{ __('The Product record already exists and can be referenced by every downstream configuration record.') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 px-4 py-4">
                                <div class="text-sm font-black text-slate-900">{{ __('Relational completeness') }}</div>
                                <p class="mt-2 text-sm font-medium leading-6 text-slate-500">{{ __('Entitlements, limits, and features are separate related records, which keeps the model extensible and queryable.') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 px-4 py-4">
                                <div class="text-sm font-black text-slate-900">{{ __('Publication action') }}</div>
                                <p class="mt-2 text-sm font-medium leading-6 text-slate-500">{{ __('Publishing only changes the product status. The draft record and all attached relations remain the same domain aggregate.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
            <button type="button" wire:click="previousStep" @disabled($step === 1) class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-slate-100 px-6 py-3 text-sm font-black text-slate-600 transition-all hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40">
                {{ __('Back') }}
            </button>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if ($product)
                    <a href="{{ route('system.products.show', $product) }}" class="inline-flex min-h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-black text-slate-600 transition-all hover:bg-slate-50">
                        {{ __('View Draft') }}
                    </a>
                @endif

                <button type="button" wire:click="nextStep" wire:loading.attr="disabled" class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-2xl px-7 py-3 text-sm font-black text-white shadow-xl transition-all disabled:cursor-not-allowed disabled:opacity-60 {{ $step === 5 ? 'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700' : 'bg-brand shadow-brand/20 hover:bg-brand-hover' }}">
                    <span wire:loading.remove>
                        {{ $step === 1 ? __('Create Draft & Continue') : ($step === 5 ? __('Publish Product') : __('Continue')) }}
                    </span>
                    <span wire:loading>{{ __('Working...') }}</span>
                    <x-heroicon-m-arrow-right class="size-5 rtl:rotate-180" />
                </button>
            </div>
        </div>
    </div>
</div>
