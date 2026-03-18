<div class="mx-auto max-w-3xl px-4 py-8 text-start sm:px-6 sm:py-12 lg:px-8" role="main" aria-label="{{ __('יצירת קופון') }}" dir="{{ isRTL() ? 'rtl' : 'ltr' }}">

    {{-- Header row --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('system.coupons.index') }}"
           class="group inline-flex items-center gap-2 self-start rounded-xl px-4 py-2 text-sm font-bold text-content-muted transition-all hover:bg-surface hover:text-brand">
            <x-heroicon-m-arrow-left class="size-4 transition-transform group-hover:-translate-x-1 rtl:rotate-180" />
            <span>{{ __('ביטול ויציאה') }}</span>
        </a>
        <div class="inline-flex items-center gap-2 self-start rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-violet-700 sm:self-auto">
            <x-heroicon-o-ticket class="size-4" />
            <span>{{ __('אשף יצירת קופון') }}</span>
        </div>
    </div>

    {{-- Page hero --}}
    <div class="mb-10 overflow-hidden rounded-[2rem] border border-white/70 bg-card/90 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur xl:p-8">
        <div class="space-y-2">
            <p class="text-xs font-black uppercase tracking-[0.3em] text-content-muted">{{ __('ניהול מבצעים') }}</p>
            <h1 class="text-3xl font-black tracking-tight text-content sm:text-4xl">{{ __('יצירת קוד קופון') }}</h1>
            <p class="max-w-lg text-sm font-medium leading-6 text-content-muted">
                {{ __('הגדר קוד הנחה, טווח יעד וסוג הטבה. הקופון יהיה זמין מיד לאחר האישור.') }}
            </p>
        </div>
    </div>

    {{-- Progress steps --}}
    <div class="mb-10">
        <div class="relative flex items-center justify-between gap-2">
            <div class="absolute inset-x-0 top-5 hidden h-1 rounded-full bg-stroke sm:block"></div>
            <div class="absolute top-5 hidden h-1 rounded-full bg-brand transition-all duration-500 sm:block"
                 style="width: {{ ($step - 1) / max($totalSteps - 1, 1) * 100 }}%; {{ isRTL() ? 'right: 0;' : 'left: 0;' }}">
            </div>

            @foreach ([1 => __('בסיסי'), 2 => __('יעד'), 3 => __('הטבה'), 4 => __('מגבלות'), 5 => __('סיכום')] as $index => $label)
                <div wire:key="step-{{ $index }}" class="relative flex flex-1 flex-col items-center gap-2">
                    <div class="flex size-10 items-center justify-center rounded-full border-4 text-sm font-black transition-all duration-300 sm:size-12
                        {{ $step >= $index ? 'border-brand bg-brand text-white shadow-lg shadow-brand/20' : 'border-stroke bg-white text-content-muted' }}">
                        @if ($step > $index)
                            <x-heroicon-m-check class="size-5" />
                        @else
                            {{ $index }}
                        @endif
                    </div>
                    <span class="hidden text-[10px] font-bold tracking-wide sm:block {{ $step >= $index ? 'text-brand' : 'text-content-muted' }}">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step card --}}
    <div class="rounded-3xl border border-stroke/80 bg-card p-6 shadow-xl xl:p-8">

        {{-- STEP 1 — Basic --}}
        @if ($step === 1)
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-black text-content">{{ __('פרטים בסיסיים') }}</h2>
                    <p class="mt-1 text-sm text-content-muted">{{ __('הגדר את קוד הקופון ותוקפו.') }}</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('קוד קופון') }} <span class="text-danger">*</span></label>
                        <input wire:model.live="code"
                               type="text"
                               class="input-base font-mono uppercase tracking-widest"
                               placeholder="PROMO2024"
                               dir="ltr"
                               maxlength="64" />
                        @error('code') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-content-muted">{{ __('אותיות לועזיות, מספרים וקו תחתון/מקף בלבד.') }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('תיאור') }}</label>
                        <input wire:model="description"
                               type="text"
                               class="input-base"
                               placeholder="{{ __('למשל: מבצע השקה ינואר 2025') }}"
                               maxlength="255" />
                        @error('description') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('תאריך פקיעה') }}</label>
                        <input wire:model="expiresAt"
                               type="date"
                               class="input-base"
                               dir="ltr" />
                        @error('expiresAt') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-content-muted">{{ __('השאר ריק לקופון ללא תאריך פקיעה.') }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input wire:model="isActive" type="checkbox" class="peer sr-only" />
                            <div class="peer h-6 w-11 rounded-full bg-stroke after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:bg-brand peer-checked:after:translate-x-full peer-focus:outline-none rtl:peer-checked:after:-translate-x-full"></div>
                        </label>
                        <span class="text-sm font-semibold text-content">{{ __('קופון פעיל') }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 2 — Scope --}}
        @if ($step === 2)
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-black text-content">{{ __('יעד הקופון') }}</h2>
                    <p class="mt-1 text-sm text-content-muted">{{ __('הגדר על אילו רכישות הקופון חל.') }}</p>
                </div>

                <div class="space-y-3">
                    @foreach ($targetTypes as $type)
                        <label wire:key="target-{{ $type->value }}" class="flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-4 transition-colors
                            {{ $targetType === $type->value ? 'border-brand bg-brand/5' : 'border-stroke hover:border-stroke' }}">
                            <input wire:model.live="targetType" type="radio" name="targetType" value="{{ $type->value }}" class="mt-0.5 accent-brand" />
                            <div>
                                <p class="font-bold text-content">{{ $type->label() }}</p>
                                <p class="text-xs text-content-muted">{{ $type->description() }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Plan multi-select shown only when targetType = plan --}}
                @if ($targetType === 'plan')
                    <div class="mt-4">
                        <label class="mb-2 block text-sm font-bold text-content">{{ __('בחר תוכניות') }} <span class="text-danger">*</span></label>
                        <div class="max-h-60 space-y-2 overflow-y-auto rounded-2xl border border-stroke p-3">
                            @forelse ($plans as $plan)
                                <label wire:key="plan-select-{{ $plan->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 transition-colors hover:bg-surface">
                                    <input wire:model="targetPlanIds"
                                           type="checkbox"
                                           value="{{ $plan->id }}"
                                           class="accent-brand size-4 rounded" />
                                    <span class="text-sm font-semibold text-content">{{ $plan->product?->name ?? '—' }} — {{ $plan->name }}</span>
                                </label>
                            @empty
                                <p class="py-4 text-center text-sm text-content-muted">{{ __('אין תוכניות פעילות.') }}</p>
                            @endforelse
                        </div>
                        @error('targetPlanIds') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- STEP 3 — Discount type --}}
        @if ($step === 3)
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-black text-content">{{ __('סוג ההטבה') }}</h2>
                    <p class="mt-1 text-sm text-content-muted">{{ __('בחר את סוג ההנחה שהקופון מעניק.') }}</p>
                </div>

                <div class="space-y-3">
                    @foreach ($discountTypes as $type)
                        <label wire:key="discount-{{ $type->value }}" class="flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-4 transition-colors
                            {{ $discountType === $type->value ? 'border-brand bg-brand/5' : 'border-stroke hover:border-stroke' }}">
                            <input wire:model.live="discountType" type="radio" name="discountType" value="{{ $type->value }}" class="mt-0.5 accent-brand" />
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
                        {{ __('ערך ההנחה') }}
                        <span class="ms-1 text-content-muted">({{ collect($discountTypes)->firstWhere('value', $discountType)?->unit() ?? '' }})</span>
                        <span class="text-danger"> *</span>
                    </label>
                    <input wire:model="discountValue"
                           type="number"
                           min="1"
                           {{ $discountType === 'percentage' ? 'max="100"' : '' }}
                           class="input-base w-40"
                           dir="ltr"
                           placeholder="{{ $discountType === 'percentage' ? '20' : ($discountType === 'fixed' ? '50' : '30') }}" />
                    @error('discountValue') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror

                    @if ($discountType === 'fixed')
                        <p class="mt-1 text-xs text-content-muted">{{ __('הזן את הסכום בשקלים (₪). מירבי — מחיר הרכישה.') }}</p>
                    @elseif ($discountType === 'percentage')
                        <p class="mt-1 text-xs text-content-muted">{{ __('בין 1 ל-100 אחוזים.') }}</p>
                    @elseif ($discountType === 'trial_extension')
                        <p class="mt-1 text-xs text-content-muted">{{ __('מספר ימים שיתווספו לתקופת ה-Trial הפעילה.') }}</p>
                    @endif
                </div>

                @if ($discountType === 'percentage')
                <div>
                    <label class="mb-1.5 block text-sm font-bold text-content">
                        {{ __('תקופת ההנחה') }}
                        <span class="ms-1 text-xs font-normal text-content-muted">({{ __('ישים רק לאחוז הנחה') }})</span>
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
                    @error('discountDurationMonths') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>
        @endif

        {{-- STEP 4 — Usage limits --}}
        @if ($step === 4)
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-black text-content">{{ __('מגבלות שימוש') }}</h2>
                    <p class="mt-1 text-sm text-content-muted">{{ __('הגדר כמה פעמים ניתן להשתמש בקופון.') }}</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('מקסימום שימושים (סה"כ)') }}</label>
                        <input wire:model="maxUses"
                               type="number" min="1"
                               class="input-base w-48"
                               dir="ltr"
                               placeholder="{{ __('ללא הגבלה') }}" />
                        @error('maxUses') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-content-muted">{{ __('השאר ריק לשימוש בלתי מוגבל.') }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-content">{{ __('מקסימום שימושים לחשבון') }}</label>
                        <input wire:model="maxUsesPerAccount"
                               type="number" min="1"
                               class="input-base w-48"
                               dir="ltr"
                               placeholder="{{ __('ללא הגבלה') }}" />
                        @error('maxUsesPerAccount') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl border border-stroke p-4">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input wire:model="firstTimeOnly" type="checkbox" class="peer sr-only" />
                            <div class="peer h-6 w-11 rounded-full bg-stroke after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:bg-brand peer-checked:after:translate-x-full peer-focus:outline-none rtl:peer-checked:after:-translate-x-full"></div>
                        </label>
                        <div>
                            <p class="text-sm font-bold text-content">{{ __('רכישה ראשונה בלבד') }}</p>
                            <p class="text-xs text-content-muted">{{ __('הקופון יחול רק על חשבונות שטרם רכשו בעבר.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 5 — Review --}}
        @if ($step === 5)
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-black text-content">{{ __('סיכום ואישור') }}</h2>
                    <p class="mt-1 text-sm text-content-muted">{{ __('בדוק את פרטי הקופון לפני הפרסום.') }}</p>
                </div>

                <dl class="divide-y divide-stroke rounded-2xl border border-stroke bg-surface text-sm">
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="font-semibold text-content-muted">{{ __('קוד') }}</dt>
                        <dd class="font-mono font-black tracking-widest text-content">{{ strtoupper($code) }}</dd>
                    </div>
                    @if ($description)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('תיאור') }}</dt>
                            <dd class="text-content">{{ $description }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="font-semibold text-content-muted">{{ __('יעד') }}</dt>
                        <dd class="text-content">{{ collect($targetTypes)->firstWhere('value', $targetType)?->label() }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="font-semibold text-content-muted">{{ __('סוג הטבה') }}</dt>
                        <dd class="text-content">{{ collect($discountTypes)->firstWhere('value', $discountType)?->label() }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="font-semibold text-content-muted">{{ __('ערך') }}</dt>
                        <dd class="font-black text-brand">
                            {{ $discountValue }}{{ collect($discountTypes)->firstWhere('value', $discountType)?->unit() }}
                        </dd>
                    </div>
                    @if ($discountType === 'percentage' && $discountDurationMonths !== '')
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('תקופה') }}</dt>
                            <dd class="text-content">{{ $discountDurationMonths }} {{ __('חודשים') }}</dd>
                        </div>
                    @elseif ($discountType === 'percentage')
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('תקופה') }}</dt>
                            <dd class="text-content">{{ __('לצמיתות') }}</dd>
                        </div>
                    @endif
                    @if ($maxUses)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('מקס׳ שימושים') }}</dt>
                            <dd class="text-content">{{ $maxUses }}</dd>
                        </div>
                    @endif
                    @if ($maxUsesPerAccount)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('מקס׳ לחשבון') }}</dt>
                            <dd class="text-content">{{ $maxUsesPerAccount }}</dd>
                        </div>
                    @endif
                    @if ($firstTimeOnly)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('רכישה ראשונה') }}</dt>
                            <dd class="font-bold text-amber-600">{{ __('כן') }}</dd>
                        </div>
                    @endif
                    @if ($expiresAt)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="font-semibold text-content-muted">{{ __('פקיעה') }}</dt>
                            <dd class="text-content">{{ $expiresAt }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="font-semibold text-content-muted">{{ __('סטטוס') }}</dt>
                        <dd>
                            @if ($isActive)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">
                                    <x-heroicon-m-check-circle class="size-3.5" />{{ __('פעיל') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-surface px-2.5 py-0.5 text-xs font-bold text-content-muted">
                                    {{ __('לא פעיל') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        @endif

        {{-- Navigation buttons --}}
        <div class="mt-8 flex items-center justify-between gap-4 border-t border-stroke pt-6">
            <button type="button"
                    wire:click="previousStep"
                    class="{{ $step === 1 ? 'invisible' : '' }} btn-secondary btn-lg inline-flex items-center gap-2">
                <x-heroicon-m-arrow-right class="size-4 rtl:rotate-0 ltr:rotate-180" />
                {{ __('חזור') }}
            </button>

            <button type="button"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                    class="btn-primary btn-lg inline-flex items-center gap-2">
                <span wire:loading.remove wire:target="nextStep">
                    @if ($step === $totalSteps)
                        <x-heroicon-m-check class="size-4" />
                        {{ __('שמור קופון') }}
                    @else
                        {{ __('המשך') }}
                        <x-heroicon-m-arrow-left class="size-4 rtl:rotate-0 ltr:rotate-180" />
                    @endif
                </span>
                <span wire:loading wire:target="nextStep" class="inline-flex items-center gap-2">
                    <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('שומר…') }}
                </span>
            </button>
        </div>
    </div>
</div>
