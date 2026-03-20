<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('system.coupons.index') }}"
           class="group inline-flex items-center gap-2 self-start rounded-xl px-4 py-2 text-sm font-bold text-content-muted transition-all hover:bg-surface hover:text-brand">
            <x-heroicon-o-arrow-right class="size-4 transition-transform group-hover:-translate-x-0.5 rtl:rotate-180" />
            {{ __('חזרה לקופונים') }}
        </a>
        <div class="inline-flex items-center gap-2 self-start rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-amber-700 sm:self-auto">
            <x-heroicon-o-pencil-square class="size-3.5" />
            {{ __('עריכת קופון') }}
        </div>
    </div>

    {{-- Flash --}}
    @session('success')
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
            {{ $value }}
        </div>
    @endsession

    {{-- Form card --}}
    <div class="rounded-3xl border border-stroke/80 bg-card p-6 shadow-xl xl:p-8">

        <div class="mb-6 flex items-start gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                <x-heroicon-o-ticket class="size-6" />
            </div>
            <div>
                <h1 class="text-xl font-black text-content">{{ $coupon->code }}</h1>
                <p class="text-sm text-content-muted">{{ __('נוצר') }} {{ $coupon->created_at->diffForHumans() }}
                    @if($coupon->redemptions_count ?? $coupon->redemptions()->count())
                        · {{ $coupon->redemptions()->count() }} {{ __('שימושים') }}
                    @endif
                </p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-8">

            {{-- ── Section 1: Basic ──────────────────────────────────────── --}}
            <section class="space-y-5">
                <h2 class="border-b border-stroke pb-2 text-xs font-black uppercase tracking-[0.2em] text-content-muted">
                    {{ __('פרטים בסיסיים') }}
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">
                            {{ __('קוד קופון') }} <span class="text-danger">*</span>
                        </label>
                        <input wire:model.blur="code" type="text"
                               class="input-base font-mono uppercase tracking-widest"
                               placeholder="SUMMER25" dir="ltr" />
                        @error('code') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('תאריך פקיעה') }}</label>
                        <input wire:model="expiresAt" type="date"
                               class="input-base"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}" dir="ltr" />
                        @error('expiresAt') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold text-content">{{ __('תיאור') }}</label>
                    <input wire:model="description" type="text"
                           class="input-base"
                           placeholder="{{ __('תיאור פנימי (לא מוצג ללקוח)') }}" />
                    @error('description') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-stroke p-4">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input wire:model="isActive" type="checkbox" class="peer sr-only" />
                        <div class="peer h-6 w-11 rounded-full bg-stroke transition-all after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-brand peer-checked:after:translate-x-full peer-focus:ring-4 peer-focus:ring-brand/20"></div>
                    </label>
                    <div>
                        <p class="font-bold text-content">{{ __('קופון פעיל') }}</p>
                        <p class="text-xs text-content-muted">{{ __('קופון לא פעיל לא ניתן לממוש') }}</p>
                    </div>
                </div>
            </section>

            {{-- ── Section 2: Scope ──────────────────────────────────────── --}}
            <section class="space-y-4">
                <h2 class="border-b border-stroke pb-2 text-xs font-black uppercase tracking-[0.2em] text-content-muted">
                    {{ __('יעד הקופון') }}
                </h2>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($targetTypes as $type)
                        <label wire:key="edit-target-{{ $type->value }}"
                               class="flex cursor-pointer items-start gap-3 rounded-2xl border-2 p-4 transition-colors
                                   {{ $targetType === $type->value ? 'border-brand bg-brand/5' : 'border-stroke hover:border-brand/30' }}">
                            <input wire:model.live="targetType" type="radio" name="targetType"
                                   value="{{ $type->value }}" class="mt-0.5 accent-brand" />
                            <div>
                                <p class="font-bold text-content">{{ $type->label() }}</p>
                                <p class="text-xs text-content-muted">{{ $type->description() }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('targetType') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror

                @if ($targetType === 'plan')
                    <div wire:key="plan-select-section">
                        <label class="mb-2 block text-sm font-bold text-content">
                            {{ __('בחר תוכניות') }} <span class="text-danger">*</span>
                        </label>
                        <div class="max-h-60 space-y-2 overflow-y-auto rounded-2xl border border-stroke p-3">
                            @foreach ($plans as $plan)
                                <label wire:key="edit-plan-{{ $plan->id }}"
                                       class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 transition-colors hover:bg-surface">
                                    <input wire:model="targetPlanIds" type="checkbox"
                                           value="{{ $plan->id }}" class="accent-brand" />
                                    <span class="text-sm font-semibold text-content">
                                        {{ $plan->product->name ?? '—' }} — {{ $plan->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('targetPlanIds') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                    </div>
                @endif
            </section>

            {{-- ── Section 3: Discount ───────────────────────────────────── --}}
            <section class="space-y-4">
                <h2 class="border-b border-stroke pb-2 text-xs font-black uppercase tracking-[0.2em] text-content-muted">
                    {{ __('סוג ההטבה') }}
                </h2>

                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($discountTypes as $type)
                        <label wire:key="edit-discount-{{ $type->value }}"
                               class="flex cursor-pointer items-start gap-3 rounded-2xl border-2 p-4 transition-colors
                                   {{ $discountType === $type->value ? 'border-brand bg-brand/5' : 'border-stroke hover:border-brand/30' }}">
                            <input wire:model.live="discountType" type="radio" name="discountType"
                                   value="{{ $type->value }}" class="mt-0.5 accent-brand" />
                            <div>
                                <p class="font-bold text-content">{{ $type->label() }}</p>
                                <p class="text-xs text-content-muted">{{ $type->description() }}</p>
                                <span class="mt-1 inline-flex items-center rounded-full bg-surface px-2 py-0.5 text-[10px] font-black text-content-muted ring-1 ring-stroke">
                                    {{ __('יחידה:') }} {{ $type->unit() }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold text-content">
                        @if ($discountType === 'percentage') {{ __('אחוז הנחה') }} (0–100)
                        @elseif ($discountType === 'fixed') {{ __('סכום הנחה') }} (₪)
                        @else {{ __('ימי הארכה') }}
                        @endif
                        <span class="text-danger">*</span>
                    </label>
                    <input wire:model="discountValue" type="number"
                           class="input-base w-40"
                           min="1"
                           max="{{ $discountType === 'percentage' ? 100 : 99999 }}"
                           dir="ltr" />
                    @error('discountValue') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                </div>

                @if ($discountType === 'percentage')
                <div>
                    <label class="mb-1.5 block text-sm font-bold text-content">
                        {{ __('תקופת ההנחה') }}
                        <span class="ms-1 text-xs font-normal text-content-muted">({{ __('אופציונלי') }})</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input wire:model="discountDurationMonths"
                               type="number"
                               min="1"
                               max="120"
                               class="input-base w-28"
                               dir="ltr"
                               placeholder="∞" />
                        <span class="text-sm text-content-muted">{{ __('חודשים') }}</span>
                    </div>
                    <p class="mt-1 text-xs text-content-muted">{{ __('ריק = הנחה לצמיתות. 1–120 חודשים לתקופה מוגבלת.') }}</p>
                    @error('discountDurationMonths') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                </div>
                @endif
            </section>

            {{-- ── Section 4: Limits ────────────────────────────────────── --}}
            <section class="space-y-5">
                <h2 class="border-b border-stroke pb-2 text-xs font-black uppercase tracking-[0.2em] text-content-muted">
                    {{ __('מגבלות שימוש') }}
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">
                            {{ __('מקסימום שימושים (סה"כ)') }}
                        </label>
                        <input wire:model="maxUses" type="number" min="1"
                               class="input-base" placeholder="{{ __('ללא הגבלה') }}" dir="ltr" />
                        @error('maxUses') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">
                            {{ __('מקסימום שימושים לחשבון') }}
                        </label>
                        <input wire:model="maxUsesPerAccount" type="number" min="1"
                               class="input-base" placeholder="{{ __('ללא הגבלה') }}" dir="ltr" />
                        @error('maxUsesPerAccount') <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-stroke p-4">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input wire:model="firstTimeOnly" type="checkbox" class="peer sr-only" />
                        <div class="peer h-6 w-11 rounded-full bg-stroke transition-all after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-brand peer-checked:after:translate-x-full peer-focus:ring-4 peer-focus:ring-brand/20"></div>
                    </label>
                    <div>
                        <p class="font-bold text-content">{{ __('לקוחות חדשים בלבד') }}</p>
                        <p class="text-xs text-content-muted">{{ __('רק חשבונות שטרם רכשו') }}</p>
                    </div>
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-stroke pt-6 sm:flex-row sm:justify-between">
                <a href="{{ route('system.coupons.index') }}" class="btn-secondary btn-lg text-center">
                    {{ __('ביטול') }}
                </a>
                <button type="submit" class="btn-primary btn-lg"
                        wire:loading.attr="disabled" wire:loading.class="opacity-75">
                    <span wire:loading.remove>
                        <x-heroicon-o-check class="size-4" />
                        {{ __('שמור שינויים') }}
                    </span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ __('שומר…') }}
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>
