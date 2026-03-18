{{--
  Subscription Checkout — PaymentsJS tokenization for plan purchase.
  Single-use token → POST /api/billing/checkout → storeSingleUseToken + SubscriptionService::activate()
  No card data is transmitted through our servers (PCI-safe).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>רכישת תוכנית — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    @if(app()->environment('local', 'staging'))
        <script src="https://app.sumit.co.il/scripts/payments.js" crossorigin="anonymous"></script>
    @else
        <script src="https://app.sumit.co.il/scripts/payments.js"></script>
    @endif
</head>
<body class="font-sans bg-surface min-h-screen flex items-start justify-center py-12 px-4">

<div class="w-full max-w-md space-y-6">

    {{-- Logo --}}
    <div class="text-center">
        <a href="{{ route('billing.plans') }}" class="inline-block">
            <img src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" class="h-9 w-auto mx-auto"
                 onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'">
        </a>
    </div>

    {{-- Plan Summary Card --}}
    <div class="rounded-2xl border border-stroke bg-card shadow-xl shadow-slate-900/5 overflow-hidden">

        {{-- Plan header --}}
        <div class="bg-brand px-6 py-5 text-white">
            <p class="text-xs font-black uppercase tracking-widest opacity-75 mb-1">{{ $plan->product?->name }}</p>
            <h1 class="text-xl font-black">{{ $plan->name }}</h1>
            @if($plan->description)
                <p class="text-sm opacity-80 mt-1">{{ $plan->description }}</p>
            @endif
        </div>

        {{-- Price summary --}}
        <div class="px-6 py-4 border-b border-stroke bg-surface/50">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-content-muted">סכום לחיוב</span>
                <span class="text-xl font-black text-content">
                    <span id="price-display">₪{{ number_format($price->amount / 100, 0) }}</span>
                    <span class="text-sm font-medium text-content-muted">
                        / {{ $price->billing_cycle?->value === 'yearly' ? 'שנה' : 'חודש' }}
                    </span>
                </span>
            </div>
            {{-- Coupon discount row (hidden until applied) --}}
            <div id="discount-row" class="hidden mt-2 flex items-center justify-between text-sm">
                <span class="text-emerald-600 font-semibold flex items-center gap-1">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                    </svg>
                    <span id="discount-label">קופון</span>
                </span>
                <span id="discount-amount" class="font-bold text-emerald-600"></span>
            </div>
            {{-- Final price row (hidden until coupon applied) --}}
            <div id="final-row" class="hidden mt-2 flex items-center justify-between border-t border-stroke pt-2">
                <span class="text-sm font-bold text-content">לתשלום</span>
                <span id="final-price" class="text-xl font-black text-brand"></span>
            </div>
            <p class="text-xs text-content-muted mt-2">
                ארגון: <span class="font-semibold text-content">{{ $organization->name }}</span>
            </p>
        </div>

        {{-- Payment form --}}
        <div class="px-6 py-6 space-y-5">

            <form id="checkout-form" data-og="form" method="post" action="#" class="space-y-4">
                @csrf
                {{-- PaymentsJS writes the single-use token into this hidden input, then calls requestSubmit() --}}
                <input type="hidden" name="og-token" data-og="token">

                <div>
                    <label for="og-ccnum" class="mb-1.5 block text-sm font-bold text-content">
                        מספר כרטיס <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="og-ccnum" name="og-ccnum"
                           placeholder="•••• •••• •••• ••••"
                           maxlength="19" inputmode="numeric" autocomplete="cc-number" required
                           class="input-base font-mono tracking-widest" dir="ltr">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="og-expmonth" class="mb-1.5 block text-sm font-bold text-content">
                                חודש תפוגה <span class="text-danger">*</span>
                            </label>
                            <select id="og-expmonth" name="og-expmonth" required class="input-base" dir="ltr">
                            <option value="">חודש</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label for="og-expyear" class="mb-1.5 block text-sm font-bold text-content">
                                שנת תפוגה <span class="text-danger">*</span>
                            </label>
                            <select id="og-expyear" name="og-expyear" required class="input-base" dir="ltr">
                            <option value="">שנה</option>
                            @for($i = 0; $i <= 15; $i++)
                                <option value="{{ (string)(date('Y') + $i) }}">{{ date('Y') + $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label for="og-ccv" class="mb-1.5 block text-sm font-bold text-content">
                        CVV <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="og-ccv" name="og-ccv"
                           placeholder="•••"
                           maxlength="4" inputmode="numeric" autocomplete="cc-csc" required
                           class="input-base w-28 font-mono tracking-widest" dir="ltr">
                </div>

                {{-- Error / Success --}}
                <div id="checkout-error" class="hidden rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger" role="alert"></div>
                <div id="checkout-success" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"></div>

                {{-- Coupon code --}}
                <div class="rounded-xl border border-stroke bg-surface/50 p-4 space-y-3">
                    <p class="text-xs font-bold text-content-muted uppercase tracking-wider">קוד קופון (אופציונלי)</p>
                    <div class="flex gap-2">
                        <input type="text" id="coupon-input"
                               placeholder="הזן קוד קופון"
                               maxlength="64"
                               dir="ltr"
                               class="flex-1 min-h-[40px] px-3 py-2 border border-stroke rounded-xl bg-card text-content placeholder-content-muted focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/25 transition font-mono uppercase tracking-widest text-sm">
                        <button type="button" id="coupon-btn"
                                class="min-h-[40px] px-4 py-2 rounded-xl border border-brand text-brand text-sm font-bold hover:bg-brand hover:text-white transition-colors disabled:opacity-50">
                            <span id="coupon-btn-text">אמת</span>
                            <span id="coupon-btn-loading" style="display:none">…</span>
                        </button>
                    </div>
                    <div id="coupon-error" class="hidden text-xs font-medium text-danger"></div>
                    <div id="coupon-ok" class="hidden text-xs text-emerald-600 font-semibold flex items-center gap-1.5">
                        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span id="coupon-ok-text"></span>
                        <button type="button" id="coupon-remove" class="ms-auto text-xs font-medium text-content-muted transition-colors hover:text-danger">הסר</button>
                    </div>
                </div>

                <button type="submit" id="pay-btn"
                        class="w-full min-h-[48px] px-4 py-3 bg-brand text-white text-base font-black rounded-xl hover:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed transition-all active:scale-95 shadow-lg shadow-brand/25">
                    <span id="pay-btn-text">אשר ורכוש — ₪{{ number_format($price->amount / 100, 0) }}</span>
                    <span id="pay-btn-loading" style="display:none" class="inline-flex items-center justify-center gap-2">
                        <svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        מעבד תשלום…
                    </span>
                </button>
            </form>

            {{-- PCI notice --}}
            <p class="text-center text-xs text-content-muted flex items-center justify-center gap-1.5">
                <svg class="size-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                פרטי הכרטיס מועברים ישירות ל-SUMIT. אנו לא שומרים מידע רגיש.
            </p>
        </div>
    </div>

    {{-- Back link --}}
    <div class="text-center">
        <a href="{{ route('billing.plans') }}" class="text-sm text-content-muted hover:text-brand transition-colors font-medium">
            ← חזור לבחירת תוכנית
        </a>
    </div>
</div>

<script>
(function () {
    var form     = document.getElementById('checkout-form');
    var payBtn   = document.getElementById('pay-btn');
    var btnText  = document.getElementById('pay-btn-text');
    var btnLoad  = document.getElementById('pay-btn-loading');
    var errEl    = document.getElementById('checkout-error');
    var okEl     = document.getElementById('checkout-success');

    var apiUrl            = @json($apiUrl);
    var couponValidateUrl = @json($couponValidateUrl);
    var bearerToken       = @json($bearerToken);
    var planId            = @json($plan->id);
    var originalAmount    = @json($price->amount); // agorot

    // Coupon state
    var appliedCouponCode = null;
    var finalAmount       = originalAmount;

    // ── Coupon UI ──────────────────────────────────────────────────────────────
    var couponInput   = document.getElementById('coupon-input');
    var couponBtn     = document.getElementById('coupon-btn');
    var couponError   = document.getElementById('coupon-error');
    var couponOk      = document.getElementById('coupon-ok');
    var couponOkText  = document.getElementById('coupon-ok-text');
    var couponRemove  = document.getElementById('coupon-remove');
    var discountRow   = document.getElementById('discount-row');
    var discountLabel = document.getElementById('discount-label');
    var discountAmt   = document.getElementById('discount-amount');
    var finalRow      = document.getElementById('final-row');
    var finalPrice    = document.getElementById('final-price');
    var priceDisplay  = document.getElementById('price-display');

    function formatNis(agorot) {
        return '₪' + (agorot / 100).toLocaleString('he-IL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function updatePayButton() {
        btnText.textContent = 'אשר ורכוש — ' + formatNis(finalAmount);
    }

    function applyDiscount(data) {
        finalAmount = data.final_amount;
        appliedCouponCode = data.coupon.code;

        var disc = data.discount_amount;
        discountLabel.textContent = 'קוד ' + data.coupon.code;
        discountAmt.textContent = '−' + formatNis(disc);
        discountRow.classList.remove('hidden');

        finalPrice.textContent = formatNis(finalAmount);
        finalRow.classList.remove('hidden');

        var desc = data.coupon.description || data.coupon.code;
        var durationText = '';
        if (data.coupon.discount_type === 'percentage' && data.coupon.discount_duration_months) {
            durationText = ' · ' + data.coupon.discount_duration_months + ' חודשים';
        } else if (data.coupon.discount_type === 'percentage') {
            durationText = ' · לצמיתות';
        }
        couponOkText.textContent = desc + ' — חיסכון: ' + formatNis(disc) + durationText;
        couponOk.classList.remove('hidden');
        couponError.classList.add('hidden');
        couponInput.disabled = true;
        couponBtn.style.display = 'none';
        updatePayButton();
    }

    function removeCoupon() {
        appliedCouponCode = null;
        finalAmount = originalAmount;
        couponInput.value = '';
        couponInput.disabled = false;
        couponBtn.style.display = '';
        couponOk.classList.add('hidden');
        couponError.classList.add('hidden');
        discountRow.classList.add('hidden');
        finalRow.classList.add('hidden');
        updatePayButton();
    }

    couponBtn.addEventListener('click', function () {
        var code = couponInput.value.trim();
        if (!code) { return; }

        couponError.classList.add('hidden');
        couponBtn.disabled = true;
        document.getElementById('coupon-btn-text').style.display = 'none';
        document.getElementById('coupon-btn-loading').style.display = '';

        fetch(couponValidateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + bearerToken,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ code: code, plan_id: planId, amount_minor: originalAmount })
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            if (res.data.valid) {
                applyDiscount(res.data);
            } else {
                couponError.textContent = res.data.message || 'קוד לא תקין.';
                couponError.classList.remove('hidden');
            }
        })
        .catch(function () {
            couponError.textContent = 'שגיאת תקשורת. נסה שוב.';
            couponError.classList.remove('hidden');
        })
        .finally(function () {
            couponBtn.disabled = false;
            document.getElementById('coupon-btn-text').style.display = '';
            document.getElementById('coupon-btn-loading').style.display = 'none';
        });
    });

    couponRemove.addEventListener('click', removeCoupon);

    couponInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); couponBtn.click(); }
    });

    // ── Helpers ────────────────────────────────────────────────────────────────
    function getTokenInput() {
        return form.querySelector('input[name="og-token"]') || form.querySelector('input[data-og="token"]');
    }

    function setLoading(on) {
        payBtn.disabled = on;
        btnText.style.display = on ? 'none' : '';
        btnLoad.style.display = on ? 'inline-flex' : 'none';
    }

    function showError(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
        okEl.classList.add('hidden');
    }

    function showSuccess(msg) {
        okEl.textContent = msg;
        okEl.classList.remove('hidden');
        errEl.classList.add('hidden');
        form.classList.add('hidden');
    }

    function doApiSubmit(tokenValue) {
        var payload = { plan_id: planId, payment_token: tokenValue };
        if (appliedCouponCode) { payload.coupon_code = appliedCouponCode; }

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':        'application/json',
                'Authorization': 'Bearer ' + bearerToken,
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        })
        .then(function (res) {
            if (res.ok && res.data.success) {
                showSuccess('התשלום אושר! מועבר ללוח הבקרה…');
                setTimeout(function () {
                    window.location.href = res.data.redirect_url || '/dashboard';
                }, 1500);
            } else {
                showError(res.data.message || 'התשלום נכשל. אנא נסה שוב.');
                setLoading(false);
                form.dataset.ogSubmitting = '';
            }
        })
        .catch(function () {
            showError('שגיאת תקשורת. אנא נסה שוב.');
            setLoading(false);
            form.dataset.ogSubmitting = '';
        });
    }

    // ── PaymentsJS init ────────────────────────────────────────────────────────
    if (typeof OfficeGuy === 'undefined' || !OfficeGuy.Payments || !OfficeGuy.Payments.BindFormSubmit) {
        showError('שגיאה בטעינת מערכת התשלום. נא לרענן את הדף.');
        return;
    }

    if (typeof OfficeGuy.Payments.InitEditors === 'function') {
        OfficeGuy.Payments.InitEditors();
    }

    OfficeGuy.Payments.BindFormSubmit({
        FormSelector:     '#checkout-form',
        CompanyID:        @json($companyId),
        APIPublicKey:     @json($publicKey),
        ResponseLanguage: @json(app()->getLocale()),
    });

    // ── Submit handler (isTrusted pattern) ────────────────────────────────────
    // First submit  (isTrusted=true):  user clicked → PaymentsJS tokenizes → requestSubmit()
    // Second submit (isTrusted=false): PaymentsJS has filled og-token → we POST to API
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!e.isTrusted) {
            // PaymentsJS finished tokenization and called requestSubmit().
            var tokenInput = getTokenInput();
            if (tokenInput && String(tokenInput.value || '').trim()) {
                doApiSubmit(tokenInput.value);
            } else {
                showError('לא התקבל אסימון תשלום. אנא נסה שוב.');
                setLoading(false);
                form.dataset.ogSubmitting = '';
            }
            return;
        }

        // Guard against duplicate clicks.
        if (form.dataset.ogSubmitting === '1') { return; }
        form.dataset.ogSubmitting = '1';

        errEl.classList.add('hidden');
        errEl.textContent = '';
        setLoading(true);

        // BindFormSubmit handles tokenization and fires requestSubmit() when done.
    });
})();
</script>
</body>
</html>
