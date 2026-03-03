# SUMIT Production Cutover Checklist

## Purpose

Checklist for switching from sandbox to production (or from stub to SUMIT), credential rotation, webhook URL change, and rollback. Ensures production never silently falls back to the stub gateway.

---

## Production safety gate (already implemented)

- When **`BILLING_GATEWAY=sumit`** and the environment is **production**, the app validates required SUMIT config at **boot** in `AppServiceProvider::validateSumitConfig()`.
- **Required:** `OFFICEGUY_COMPANY_ID`, `OFFICEGUY_PRIVATE_KEY`, `BILLING_SUMIT_SUCCESS_URL`, `BILLING_SUMIT_CANCEL_URL`. If any are missing or blank, the app throws **`RuntimeException`** with a message listing the missing env key names.
- **Do NOT allow silent fallback to stub in production:** There is no fallback. If SUMIT is selected and config is incomplete, the application fails to boot. The stub is used only when `BILLING_GATEWAY=stub` (or unset, default). See [sumit-config-validation.md](sumit-config-validation.md).

---

## 1) Sandbox → production switch steps

1. **Obtain production credentials** from SUMIT (production company ID, API keys). Do not commit them; use `.env` (or secure secret store) only.
2. **Set production env keys** (names only; set values in `.env` on the server):
   - `BILLING_GATEWAY=sumit`
   - `OFFICEGUY_ENVIRONMENT` (e.g. `www` for production per SUMIT docs)
   - `OFFICEGUY_COMPANY_ID` — production company ID
   - `OFFICEGUY_PRIVATE_KEY` — production API private key
   - `OFFICEGUY_PUBLIC_KEY` — if required
   - `BILLING_SUMIT_SUCCESS_URL` — production success redirect URL (e.g. `https://your-app.example/checkout/success`)
   - `BILLING_SUMIT_CANCEL_URL` — production cancel redirect URL
   - `BILLING_WEBHOOK_SECRET` — production webhook secret (recommended)
3. **Configure SUMIT production** to send payment webhooks to your production webhook URL: `https://your-domain/api/webhooks/sumit`.
4. **Deploy** and restart the app. If any required key is missing, the app will throw at first request/command; fix `.env` and restart.
5. **Smoke test:** Initiate one checkout in production (test event), complete payment, and confirm webhook is received and event becomes active. Optionally run the validation tests against production DB (e.g. with a test DB copy) if you use PostgreSQL for tests.

---

## 2) Credential rotation procedure

1. **Generate new keys** in SUMIT (new API key or rotated secret). Do not remove the old key until the new one is verified.
2. **Update `.env`** with the new values (e.g. `OFFICEGUY_PRIVATE_KEY`, and/or `BILLING_WEBHOOK_SECRET` if rotating webhook secret).
3. **Restart** the application so it picks up the new env.
4. **Verify:** One test checkout and one test webhook (e.g. with curl and valid signature).
5. **Revoke or deactivate** the old key in SUMIT after confirming the new key works.

---

## 3) Webhook URL change procedure

1. **Add new URL** in SUMIT dashboard (e.g. new endpoint or new domain). Keep the old URL active until the new one is verified.
2. **Deploy** the new endpoint if needed (e.g. new domain or path).
3. **Send test webhooks** from SUMIT to the new URL (or simulate with curl). Confirm 200 and correct DB updates.
4. **Switch SUMIT** to send production webhooks only to the new URL.
5. **Remove or disable** the old webhook URL in SUMIT after a short overlap period.

---

## 4) Rollback plan (switch back to stub)

If you need to stop using SUMIT in production without changing code:

1. **Set in `.env`:** `BILLING_GATEWAY=stub`.
2. **Restart** the application. The app will bind `StubPaymentGateway`; no SUMIT credentials are required.
3. **Effect:** New checkouts will use the stub (no real payment; you can still test flow). Existing events that are already `active` remain unchanged. Events in `pending_payment` will not become active until you either run a manual process (e.g. mark payment succeeded for testing) or switch back to SUMIT and receive a real webhook.
4. **Optional:** In SUMIT dashboard, disable or remove the webhook URL for this app to avoid stray webhooks hitting your endpoint. Our endpoint will still accept them but will not find matching payments if you are not creating new SUMIT payments.

**Note:** There is no automatic or silent fallback from SUMIT to stub. Rollback is an explicit config change (`BILLING_GATEWAY=stub`) and restart.
