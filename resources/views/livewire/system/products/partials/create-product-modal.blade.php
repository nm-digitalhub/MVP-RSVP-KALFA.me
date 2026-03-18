{{-- CreateProductModal — 2-step product creation modal --}}
@if($isOpen)
<div
    x-data
    x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('יצירת מוצר חדש') }}"
>
    {{-- Backdrop --}}
    <div
        wire:click="close"
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Modal panel --}}
    <div class="relative w-full max-w-lg rounded-3xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5 animate-in fade-in zoom-in-95 duration-200">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-brand/10">
                    <x-heroicon-o-cube class="size-5 text-brand" />
                </div>
                <div>
                    <h2 class="text-sm font-black text-content">{{ __('מוצר חדש') }}</h2>
                    <p class="text-xs text-content-muted">{{ __('שלב :step מתוך 2', ['step' => $step]) }}</p>
                </div>
            </div>
            <button
                type="button"
                wire:click="close"
                class="rounded-xl p-2 text-content-muted transition hover:bg-surface hover:text-content"
                aria-label="{{ __('סגור') }}"
            >
                <x-heroicon-m-x-mark class="size-5" />
            </button>
        </div>

        {{-- Step progress --}}
        <div class="flex gap-1.5 px-6 pt-4">
            @foreach([1, 2] as $s)
                <div class="h-1.5 flex-1 rounded-full transition-all duration-300 {{ $step >= $s ? 'bg-brand' : 'bg-slate-100' }}"></div>
            @endforeach
        </div>

        {{-- Body --}}
        <div class="px-6 py-6">

            @if($step === 1)
            {{-- Step 1: Name & Slug --}}
            <div wire:key="step-1" class="space-y-5 animate-in fade-in slide-in-from-bottom-4 duration-300">
                <div>
                    <label for="product-name" class="mb-1.5 block text-xs font-bold text-content">
                        {{ __('שם המוצר') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="product-name"
                        type="text"
                        wire:model.live.debounce.300ms="name"
                        class="block w-full rounded-2xl border border-stroke bg-surface px-4 py-3 text-sm font-medium text-content placeholder-content-muted transition focus:border-brand focus:ring-4 focus:ring-brand/10 focus:outline-none"
                        placeholder="{{ __('e.g. Starter Plan') }}"
                        autofocus
                    />
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product-slug" class="mb-1.5 flex items-center justify-between text-xs font-bold text-content">
                        <span>{{ __('Slug') }} <span class="text-red-500">*</span></span>
                        <button type="button" wire:click="generateSlug" class="text-xs font-medium text-brand hover:underline">
                            {{ __('יצירה אוטומטית') }}
                        </button>
                    </label>
                    <div class="flex overflow-hidden rounded-2xl border border-stroke bg-surface focus-within:border-brand focus-within:ring-4 focus-within:ring-brand/10">
                        <span class="flex items-center border-e border-stroke px-3 text-xs font-bold text-content-muted">slug/</span>
                        <input
                            id="product-slug"
                            type="text"
                            wire:model="slug"
                            class="flex-1 bg-transparent px-3 py-3 text-sm font-medium text-content placeholder-content-muted focus:outline-none"
                            placeholder="{{ __('starter-plan') }}"
                            dir="ltr"
                        />
                    </div>
                    @error('slug')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endif

            @if($step === 2)
            {{-- Step 2: Description & Category --}}
            <div wire:key="step-2" class="space-y-5 animate-in fade-in slide-in-from-bottom-4 duration-300">
                <div>
                    <label for="product-description" class="mb-1.5 block text-xs font-bold text-content">
                        {{ __('תיאור') }}
                        <span class="ms-1 text-xs font-normal text-content-muted">({{ __('אופציונלי') }})</span>
                    </label>
                    <textarea
                        id="product-description"
                        wire:model="description"
                        rows="3"
                        class="block w-full rounded-2xl border border-stroke bg-surface px-4 py-3 text-sm font-medium text-content placeholder-content-muted transition focus:border-brand focus:ring-4 focus:ring-brand/10 focus:outline-none resize-none"
                        placeholder="{{ __('תיאור קצר של המוצר...') }}"
                    ></textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product-category" class="mb-1.5 block text-xs font-bold text-content">
                        {{ __('קטגוריה') }}
                        <span class="ms-1 text-xs font-normal text-content-muted">({{ __('אופציונלי') }})</span>
                    </label>
                    <input
                        id="product-category"
                        type="text"
                        wire:model="category"
                        class="block w-full rounded-2xl border border-stroke bg-surface px-4 py-3 text-sm font-medium text-content placeholder-content-muted transition focus:border-brand focus:ring-4 focus:ring-brand/10 focus:outline-none"
                        placeholder="{{ __('e.g. subscription, addon') }}"
                        dir="ltr"
                    />
                    @error('category')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4">
            <button
                type="button"
                wire:click="{{ $step === 1 ? 'close' : 'goToStep(1)' }}"
                class="rounded-xl px-4 py-2.5 text-sm font-bold text-content-muted transition hover:bg-surface hover:text-content"
            >
                {{ $step === 1 ? __('ביטול') : __('← חזרה') }}
            </button>

            @if($step === 1)
                <button
                    type="button"
                    wire:click="goToStep(2)"
                    class="flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-brand/20 transition hover:bg-brand-hover active:scale-95"
                >
                    {{ __('המשך') }}
                    <x-heroicon-m-arrow-left class="size-4 rtl:rotate-180" />
                </button>
            @else
                <button
                    type="button"
                    wire:click="createProduct"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-brand/20 transition hover:bg-brand-hover active:scale-95 disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="createProduct">{{ __('יצירת מוצר') }}</span>
                    <span wire:loading wire:target="createProduct">{{ __('יוצר...') }}</span>
                    <x-heroicon-m-cube class="size-4" wire:loading.remove wire:target="createProduct" />
                </button>
            @endif
        </div>
    </div>
</div>
@endif
