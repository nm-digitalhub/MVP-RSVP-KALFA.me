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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://app.sumit.co.il/scripts/payments.js"></script>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 2rem auto; padding: 0 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: 500; margin-bottom: 0.25rem; }
        input, select { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; }
        button { width: 100%; padding: 0.75rem; background: #4f46e5; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .error { color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem; }
        .success { color: #059669; margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>תשלום אירוע</h1>
    <p>{{ $event->name }} — {{ number_format($plan->price_cents / 100, 2) }} ₪</p>

    <form id="checkout-form" data-og="form" method="post" action="#">
        @csrf
        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

        <div class="form-group">
            <label for="og-ccnum">מספר כרטיס *</label>
            <input type="text" id="og-ccnum" name="og-ccnum" placeholder="•••• •••• •••• ••••" maxlength="19" inputmode="numeric" autocomplete="cc-number" required>
        </div>
        <div class="form-group">
            <label for="og-expmonth">חודש תפוגה *</label>
            <select id="og-expmonth" name="og-expmonth" required>
                <option value="">חודש</option>
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group">
            <label for="og-expyear">שנת תפוגה *</label>
            <select id="og-expyear" name="og-expyear" required>
                <option value="">שנה</option>
                @for($i = 0; $i <= 15; $i++)
                    <option value="{{ (string)(date('Y') + $i) }}">{{ date('Y') + $i }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group">
            <label for="og-ccv">CVV *</label>
            <input type="text" id="og-ccv" name="og-ccv" placeholder="•••" maxlength="4" inputmode="numeric" autocomplete="cc-csc" required>
        </div>

        <div id="checkout-error" class="error" role="alert" style="display: none;"></div>
        <div id="checkout-success" class="success" style="display: none;"></div>

        <button type="submit" id="pay-btn">שלם {{ number_format($plan->price_cents / 100, 2) }} ₪</button>
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

    if (typeof OfficeGuy === 'undefined' || !OfficeGuy.Payments || !OfficeGuy.Payments.BindFormSubmit) {
        errEl.textContent = 'שגיאה בטעינת מערכת התשלום. נא לרענן את הדף.';
        errEl.style.display = 'block';
        return;
    }

    OfficeGuy.Payments.BindFormSubmit({
        CompanyID: @json($companyId),
        APIPublicKey: @json($publicKey)
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        payBtn.disabled = true;
        errEl.style.display = 'none';
        errEl.textContent = '';
        okEl.style.display = 'none';

        waitForToken(function(err, tokenValue) {
            if (err) {
                errEl.textContent = 'לא התקבל אסימון תשלום. נסה שוב.';
                errEl.style.display = 'block';
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
                    okEl.style.display = 'block';
                    form.style.display = 'none';
                } else {
                    errEl.textContent = res.data.message || res.data.error || 'התשלום נכשל. נסה שוב.';
                    errEl.style.display = 'block';
                    payBtn.disabled = false;
                }
            })
            .catch(function() {
                errEl.textContent = 'שגיאת תקשורת. נסה שוב.';
                errEl.style.display = 'block';
                payBtn.disabled = false;
            });
        });
    });
})();
    </script>
</body>
</html>
