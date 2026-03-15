{{--
  RSVP Checkout — PaymentsJS tokenization (embedded).
  Requires: data-og="form", BindFormSubmit(CompanyID, APIPublicKey), requestSubmit() not submit().
  Server receives only token (og-token); no card data.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>תשלום — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://app.sumit.co.il/scripts/payments.js"></script>
</head>
<body class="font-sans text-gray-900 max-w-[420px] mx-auto my-8 px-4">
    <h1 class="text-xl font-semibold">תשלום אירוע</h1>
    <p class="mt-1 text-sm text-gray-600">{{ $event->name }} — {{ number_format($plan->price_cents / 100, 2) }} ₪</p>

    <form id="checkout-form" data-og="form" method="post" action="#" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

        <div>
            <label for="og-ccnum" class="block text-sm font-medium text-gray-700 mb-1">מספר כרטיס *</label>
            <input type="text" id="og-ccnum" name="og-ccnum" placeholder="•••• •••• •••• ••••" maxlength="19" inputmode="numeric" autocomplete="cc-number" required class="w-full min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg rtl:text-end focus:border-brand focus:ring-2 focus:ring-brand/50">
        </div>
        <div>
            <label for="og-expmonth" class="block text-sm font-medium text-gray-700 mb-1">חודש תפוגה *</label>
            <select id="og-expmonth" name="og-expmonth" required class="w-full min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg rtl:text-end focus:border-brand focus:ring-2 focus:ring-brand/50">
                <option value="">חודש</option>
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="og-expyear" class="block text-sm font-medium text-gray-700 mb-1">שנת תפוגה *</label>
            <select id="og-expyear" name="og-expyear" required class="w-full min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg rtl:text-end focus:border-brand focus:ring-2 focus:ring-brand/50">
                <option value="">שנה</option>
                @for($i = 0; $i <= 15; $i++)
                    <option value="{{ (string)(date('Y') + $i) }}">{{ date('Y') + $i }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="og-ccv" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
            <input type="text" id="og-ccv" name="og-ccv" placeholder="•••" maxlength="4" inputmode="numeric" autocomplete="cc-csc" required class="w-full min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg rtl:text-end focus:border-brand focus:ring-2 focus:ring-brand/50">
        </div>

        <div id="checkout-error" class="hidden text-sm text-red-600 mt-1" role="alert"></div>
        <div id="checkout-success" class="hidden text-sm text-green-600 mt-4"></div>

        <button type="submit" id="pay-btn" class="w-full min-h-[44px] px-4 py-3 bg-brand text-white text-base font-medium rounded-lg cursor-pointer hover:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
            שלם {{ number_format($plan->price_cents / 100, 2) }} ₪
        </button>
    </form>

    <script>
(function() {
    var form = document.getElementById('checkout-form');
    var payBtn = document.getElementById('pay-btn');
    var errEl = document.getElementById('checkout-error');
    var okEl = document.getElementById('checkout-success');

    var apiUrl = @json($apiUrl);
    var bearerToken = @json($bearerToken);
    var planId = @json($plan->id);
    var tokenWaitTimeoutMs = 5000;
    var tokenPollIntervalMs = 50;

    function getTokenInput() {
        return form.querySelector('input[name="og-token"]') || form.querySelector('input[data-og="token"]');
    }

    function waitForToken(callback) {
        var deadline = Date.now() + tokenWaitTimeoutMs;
        function check() {
            var input = getTokenInput();
            if (input && String(input.value || '').trim() !== '') {
                callback(null, input.value);
                return;
            }
            if (Date.now() >= deadline) {
                callback(new Error('timeout'));
                return;
            }
            setTimeout(check, tokenPollIntervalMs);
        }
        check();
    }

    function showEl(el) { el.classList.remove('hidden'); }
    function hideEl(el) { el.classList.add('hidden'); }

    if (typeof OfficeGuy === 'undefined' || !OfficeGuy.Payments || !OfficeGuy.Payments.BindFormSubmit) {
        errEl.textContent = 'שגיאה בטעינת מערכת התשלום. נא לרענן את הדף.';
        showEl(errEl);
        return;
    }

    OfficeGuy.Payments.BindFormSubmit({
        CompanyID: @json($companyId),
        APIPublicKey: @json($publicKey)
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        payBtn.disabled = true;
        hideEl(errEl);
        errEl.textContent = '';
        hideEl(okEl);

        waitForToken(function(err, tokenValue) {
            if (err) {
                errEl.textContent = 'לא התקבל אסימון תשלום. נסה שוב.';
                showEl(errEl);
                payBtn.disabled = false;
                return;
            }

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + bearerToken
                },
                body: JSON.stringify({ plan_id: planId, token: tokenValue })
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, status: r.status, data: data }; }); })
            .then(function(res) {
                if (res.ok && res.data.status === 'processing') {
                    okEl.textContent = 'התשלום התקבל, מאושר כעת…';
                    showEl(okEl);
                    form.classList.add('hidden');
                } else {
                    errEl.textContent = res.data.message || res.data.error || 'התשלום נכשל. נסה שוב.';
                    showEl(errEl);
                    payBtn.disabled = false;
                }
            })
            .catch(function() {
                errEl.textContent = 'שגיאת תקשורת. נסה שוב.';
                showEl(errEl);
                payBtn.disabled = false;
            });
        });
    });
})();
    </script>
</body>
</html>
