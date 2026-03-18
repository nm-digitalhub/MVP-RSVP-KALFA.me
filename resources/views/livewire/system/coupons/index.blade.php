<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.3em] text-content-muted">{{ __('ניהול מבצעים') }}</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-content sm:text-3xl">{{ __('קופונים') }}</h1>
            <p class="mt-1 text-sm text-content-muted">{{ __('קודי הנחה, מבצעים והארכות Trial.') }}</p>
        </div>
        <a href="{{ route('system.coupons.create') }}" class="btn-primary btn-lg self-start sm:self-auto">
            <x-heroicon-m-plus class="size-4" />
            {{ __('קופון חדש') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative w-full sm:w-64">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-content-muted" />
            <input wire:model.live.debounce.300ms="search"
                   type="search"
                   class="input-base w-full ps-9"
                   placeholder="{{ __('חיפוש לפי קוד…') }}" />
        </div>

        <div class="flex gap-3">
            <select wire:model.live="filterActive" class="input-base flex-1 sm:w-40">
                <option value="">{{ __('כל הסטטוסים') }}</option>
                <option value="1">{{ __('פעיל') }}</option>
                <option value="0">{{ __('לא פעיל') }}</option>
            </select>

            <select wire:model.live="filterType" class="input-base flex-1 sm:w-44">
                <option value="">{{ __('כל הסוגים') }}</option>
                <option value="percentage">{{ __('אחוז') }}</option>
                <option value="fixed">{{ __('קבוע') }}</option>
                <option value="trial_extension">{{ __('Trial') }}</option>
            </select>
        </div>
    </div>

    @php
        $isActive = fn ($coupon) => $coupon->is_active && (! $coupon->expires_at || $coupon->expires_at->isFuture());
        $discountBadgeClass = fn ($coupon) => match ($coupon->discount_type->value) {
            'percentage'      => 'bg-blue-100 text-blue-700',
            'fixed'           => 'bg-emerald-100 text-emerald-700',
            'trial_extension' => 'bg-violet-100 text-violet-700',
            default           => 'bg-surface text-content-muted',
        };
    @endphp

    {{-- ── MOBILE: Card list (hidden md+) ───────────────────────────────── --}}
    <div class="space-y-3 md:hidden">
        @forelse ($coupons as $coupon)
            <div wire:key="coupon-card-{{ $coupon->id }}"
                 class="rounded-2xl border border-stroke bg-card p-4 shadow-sm transition-all active:scale-[0.99]">

                {{-- Top row: code + status badge + actions --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <span class="font-mono text-base font-black tracking-wider text-content">{{ $coupon->code }}</span>
                        @if ($coupon->description)
                            <p class="mt-0.5 truncate text-xs text-content-muted">{{ $coupon->description }}</p>
                        @endif
                    </div>

                    {{-- Status pill --}}
                    @if ($isActive($coupon))
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>{{ __('פעיל') }}
                        </span>
                    @else
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-surface px-2.5 py-0.5 text-xs font-bold text-content-muted ring-1 ring-stroke">
                            {{ __('לא פעיל') }}
                        </span>
                    @endif
                </div>

                {{-- Meta row --}}
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-content-muted">
                    {{-- Discount badge --}}
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-bold {{ $discountBadgeClass($coupon) }}">
                        {{ $coupon->discount_value }}{{ $coupon->discount_type->unit() }}
                    </span>
                    @if ($coupon->discount_type === \App\Enums\CouponDiscountType::Percentage && $coupon->discount_duration_months)
                        <span class="inline-flex items-center gap-1 rounded-full bg-surface px-2 py-0.5 text-[10px] font-bold text-content-muted ring-1 ring-stroke">
                            {{ $coupon->discount_duration_months }}{{ __(' חודשים') }}
                        </span>
                    @endif

                    <span class="text-stroke">·</span>
                    <span>{{ $coupon->target_type->label() }}</span>

                    <span class="text-stroke">·</span>
                    <span class="tabular-nums">
                        {{ $coupon->redemptions_count }}<span class="text-stroke">/{{ $coupon->max_uses ?? '∞' }}</span>
                    </span>

                    @if ($coupon->expires_at)
                        <span class="text-stroke">·</span>
                        <span @class(['text-danger font-semibold' => $coupon->expires_at->isPast()])>
                            {{ $coupon->expires_at->format('d/m/Y') }}
                        </span>
                    @endif
                </div>

                {{-- Action bar --}}
                <div class="mt-3 flex items-center justify-end gap-2 border-t border-stroke pt-3">

                    {{-- Copy --}}
                    <button type="button"
                        x-data="{ copied: false }"
                        x-on:click="navigator.clipboard.writeText('{{ $coupon->code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-content-muted transition-colors hover:bg-surface hover:text-content"
                        x-bind:class="copied ? 'text-emerald-600' : ''">
                        <x-heroicon-o-clipboard-document class="size-3.5" x-show="!copied" />
                        <x-heroicon-o-check class="size-3.5" x-show="copied" x-cloak />
                        <span x-text="copied ? '{{ __('הועתק') }}' : '{{ __('העתק') }}'"></span>
                    </button>

                    {{-- Edit --}}
                    <a href="{{ route('system.coupons.edit', $coupon) }}"
                       class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-content-muted transition-colors hover:bg-surface hover:text-brand">
                        <x-heroicon-o-pencil-square class="size-3.5" />
                        {{ __('עריכה') }}
                    </a>

                    {{-- Toggle --}}
                    <button type="button"
                        wire:click="toggleActive({{ $coupon->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleActive({{ $coupon->id }})"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors {{ $isActive($coupon) ? 'text-emerald-600 hover:bg-emerald-50' : 'text-content-muted hover:bg-surface' }}">
                        <span wire:loading.remove wire:target="toggleActive({{ $coupon->id }})">
                            @if ($isActive($coupon))
                                <x-heroicon-o-pause-circle class="size-3.5" />
                            @else
                                <x-heroicon-o-play-circle class="size-3.5" />
                            @endif
                        </span>
                        <span wire:loading wire:target="toggleActive({{ $coupon->id }})">
                            <svg class="size-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        {{ $isActive($coupon) ? __('השבת') : __('הפעל') }}
                    </button>

                    {{-- Delete --}}
                    <button type="button"
                        wire:click="deleteCoupon({{ $coupon->id }})"
                        wire:confirm="{{ __('למחוק את הקופון \":code\"? פעולה זו אינה הפיכה.', ['code' => $coupon->code]) }}"
                        wire:loading.attr="disabled"
                        wire:target="deleteCoupon({{ $coupon->id }})"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-content-muted transition-colors hover:bg-danger/10 hover:text-danger">
                        <x-heroicon-o-trash class="size-3.5" />
                        {{ __('מחק') }}
                    </button>

                </div>
            </div>
        @empty
            <div class="py-16 text-center">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-surface">
                    <x-heroicon-o-ticket class="size-8 text-content-muted" />
                </div>
                <p class="mt-4 font-semibold text-content">{{ __('אין קופונים עדיין') }}</p>
                <p class="mt-1 text-sm text-content-muted">{{ __('צור קופון חדש כדי להתחיל.') }}</p>
                <a href="{{ route('system.coupons.create') }}" class="btn-primary mt-6 inline-flex">
                    <x-heroicon-m-plus class="size-4" />
                    {{ __('קופון חדש') }}
                </a>
            </div>
        @endforelse
    </div>

    {{-- ── DESKTOP: Table (hidden below md) ─────────────────────────────── --}}
    <div class="hidden overflow-hidden rounded-2xl border border-stroke bg-card shadow-sm md:block">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stroke bg-surface text-xs font-black uppercase tracking-wide text-content-muted">
                    <th class="px-5 py-3.5 text-start">{{ __('קוד') }}</th>
                    <th class="px-5 py-3.5 text-start">{{ __('יעד') }}</th>
                    <th class="px-5 py-3.5 text-start">{{ __('הטבה') }}</th>
                    <th class="px-5 py-3.5 text-center">{{ __('שימושים') }}</th>
                    <th class="px-5 py-3.5 text-start">{{ __('פקיעה') }}</th>
                    <th class="px-5 py-3.5 text-center">{{ __('סטטוס') }}</th>
                    <th class="px-5 py-3.5 text-end">{{ __('פעולות') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stroke">
                @forelse ($coupons as $coupon)
                    <tr wire:key="coupon-row-{{ $coupon->id }}" class="transition-colors hover:bg-surface/60">
                        <td class="px-5 py-4">
                            <span class="font-mono font-black tracking-wider text-content">{{ $coupon->code }}</span>
                            @if ($coupon->description)
                                <p class="mt-0.5 max-w-[200px] truncate text-xs text-content-muted">{{ $coupon->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-content-muted">{{ $coupon->target_type->label() }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $discountBadgeClass($coupon) }}">
                                    {{ $coupon->discount_value }}{{ $coupon->discount_type->unit() }}
                                </span>
                                @if ($coupon->discount_type === \App\Enums\CouponDiscountType::Percentage && $coupon->discount_duration_months)
                                    <span class="inline-flex items-center rounded-full bg-surface px-2 py-0.5 text-[10px] font-bold text-content-muted ring-1 ring-stroke">
                                        {{ $coupon->discount_duration_months }}{{ __(' חודשים') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center tabular-nums text-content-muted">
                            {{ $coupon->redemptions_count }}<span class="text-stroke">/{{ $coupon->max_uses ?? '∞' }}</span>
                        </td>
                        <td class="px-5 py-4 text-content-muted">
                            @if ($coupon->expires_at)
                                <span @class(['text-danger font-semibold' => $coupon->expires_at->isPast()])>
                                    {{ $coupon->expires_at->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-content-muted">{{ __('ללא') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if ($isActive($coupon))
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>{{ __('פעיל') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-surface px-2.5 py-0.5 text-xs font-bold text-content-muted ring-1 ring-stroke">
                                    {{ __('לא פעיל') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Copy --}}
                                <button type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard.writeText('{{ $coupon->code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="rounded-lg p-1.5 text-content-muted transition-colors hover:bg-surface hover:text-content"
                                    x-bind:title="copied ? '{{ __('הועתק!') }}' : '{{ __('העתק קוד') }}'">
                                    <x-heroicon-o-clipboard-document class="size-4" x-show="!copied" />
                                    <x-heroicon-o-check class="size-4 text-emerald-500" x-show="copied" x-cloak />
                                </button>
                                {{-- Edit --}}
                                <a href="{{ route('system.coupons.edit', $coupon) }}"
                                   title="{{ __('עריכה') }}"
                                   class="rounded-lg p-1.5 text-content-muted transition-colors hover:bg-surface hover:text-brand">
                                    <x-heroicon-o-pencil-square class="size-4" />
                                </a>
                                {{-- Toggle --}}
                                <button type="button"
                                    wire:click="toggleActive({{ $coupon->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleActive({{ $coupon->id }})"
                                    title="{{ $isActive($coupon) ? __('השבת') : __('הפעל') }}"
                                    class="rounded-lg p-1.5 transition-colors {{ $isActive($coupon) ? 'text-emerald-600 hover:bg-emerald-50' : 'text-content-muted hover:bg-surface' }}">
                                    <span wire:loading.remove wire:target="toggleActive({{ $coupon->id }})">
                                        @if ($isActive($coupon))
                                            <x-heroicon-o-pause-circle class="size-4" />
                                        @else
                                            <x-heroicon-o-play-circle class="size-4" />
                                        @endif
                                    </span>
                                    <span wire:loading wire:target="toggleActive({{ $coupon->id }})">
                                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                </button>
                                {{-- Delete --}}
                                <button type="button"
                                    wire:click="deleteCoupon({{ $coupon->id }})"
                                    wire:confirm="{{ __('למחוק את הקופון \":code\"? פעולה זו אינה הפיכה.', ['code' => $coupon->code]) }}"
                                    wire:loading.attr="disabled"
                                    wire:target="deleteCoupon({{ $coupon->id }})"
                                    title="{{ __('מחק') }}"
                                    class="rounded-lg p-1.5 text-content-muted transition-colors hover:bg-danger/10 hover:text-danger">
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-20 text-center">
                            <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-surface">
                                <x-heroicon-o-ticket class="size-8 text-content-muted" />
                            </div>
                            <p class="mt-4 font-semibold text-content">{{ __('אין קופונים עדיין') }}</p>
                            <p class="mt-1 text-sm text-content-muted">{{ __('צור קופון חדש כדי להתחיל.') }}</p>
                            <a href="{{ route('system.coupons.create') }}" class="btn-primary mt-6 inline-flex">
                                <x-heroicon-m-plus class="size-4" />
                                {{ __('קופון חדש') }}
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($coupons->hasPages())
        <div class="mt-6">
            {{ $coupons->links() }}
        </div>
    @endif
</div>

