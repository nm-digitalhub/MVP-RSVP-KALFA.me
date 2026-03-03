# Checkout Mode Change — Redirect → Embedded Tokenization (Scope Clarification)

**Objective:** Replace SUMIT Hosted Redirect with client-side Tokenization (PaymentsJS) so checkout stays on kalfa.me; no redirect to pay.sumit.co.il.

**Status:** Scope clarification — confirm before implementation.

---

## 1. Current state

| Item | State |
|------|--------|
| BillingService / gateway | `PaymentService::processCharge(..., redirectMode: true)` |
| Checkout response | `redirect_url` (SUMIT hosted page) |
| Flow | Hosted Payment Page (user leaves kalfa.me) |
| Webhook | Source of truth for payment success |

---

## 2. Target state

| Item | Target |
|------|--------|
| Client | PaymentsJS on kalfa.me; user enters card in iframe/JS; client gets single-use **token** (og-token or equivalent). |
| Server | Receives **token only** (no raw card data). Charge performed server-side via secret key + token. |
| Checkout response | No `redirect_url`; success/failure after server charge (or async + webhook). |
| Webhook | **Remains** source of truth for final payment status (where SUMIT supports it for tokenized charges). |

---

## 3. Implementation impact (summary)

- **Frontend:** New checkout UI using PaymentsJS; collect token, send to backend.
- **Gateway layer:** New or extended flow: accept token, call SUMIT “charge with token” (no redirect flow).
- **Redirect:** `redirect_url` no longer used for this flow; redirect URLs in config may become optional or legacy.
- **Validation:** New server-side validation for token (e.g. `og-token` or field name from SUMIT docs).
- **PCI:** Responsibility model changes (SAQ-A with iframe vs SAQ-A-EP depending on what runs on your domain); must be explicitly chosen and documented.

---

## 4. Confirmations required before implementation

### Q1: Do we replace redirect mode entirely?

**Options:**

- **A) Full replacement:** Only tokenization flow; remove redirect flow from production. Redirect URLs and redirect-only code paths become unused (or removed).
- **B) Dual mode:** Support both redirect and tokenization (e.g. config or feature flag). Keeps fallback and migration path.
- **C) Tokenization only for new UI:** New checkout uses tokenization; old/legacy entry points keep redirect until deprecated.

**Recommendation for “production checkout mode change” as stated:**  
Treat as **full replacement (A)** unless you need a temporary dual mode for migration. Confirm which option you want.

---

### Q2: Are we keeping BillingService architecture unchanged?

**Proposed:** **Yes.**

- **Keep:** `BillingService::initiateEventPayment(Event, Plan)` as the single entry point; event transitions (draft → pending_payment); creation of `EventBilling` and `Payment`; webhook-driven transition to active.
- **Change only:** *How* the gateway is invoked:
  - **Today:** `createOneTimePayment()` returns `redirect_url`; user pays on SUMIT; webhook confirms.
  - **Target:** New path (e.g. `createOneTimePaymentWithToken(..., $token)`) or extended `createOneTimePayment` that accepts optional token; server performs charge with token; webhook still confirms (if SUMIT sends it for tokenized charges).
- **Keep:** Same DB model (payments, events_billing, events); same idempotency and webhook handling; no change to “webhook is source of truth.”

**Confirmation:** BillingService stays the orchestration layer; only the **gateway adapter** (and possibly a second entry point for “charge with token”) changes. No redesign of BillingService architecture.

---

### Q3: Are we prepared to assume PCI scope changes?

**Reality:**

- **Redirect (current):** SAQ-A (or similar); you don’t touch card data; SUMIT hosts the form.
- **Tokenization (PaymentsJS):** Card data is in SUMIT’s iframe/JS; you only handle tokens. Typically still **SAQ-A** if you never see card data and SUMIT is PCI-compliant for the iframe. If your JS or server logic goes beyond “pass token to SUMIT,” scope can shift (e.g. SAQ-A-EP).

**You must:**

- Decide and **document** which SAQ (and PCI scope) you accept.
- Ensure **no card data** touches your server (only token + metadata).
- Follow SUMIT’s integration guide for PaymentsJS and token-based charge (allowed domains, iframe, etc.).

**Confirmation:** Before implementation, product/security must explicitly accept the chosen PCI scope (SAQ-A vs SAQ-A-EP) and any change relative to current redirect flow.

---

## 5. Summary table

| Question | Answer (to confirm) |
|----------|----------------------|
| Replace redirect entirely? | **Choose: A / B / C** (see Q1). |
| BillingService architecture unchanged? | **Yes** — only gateway invocation and optional new “token” path. |
| Prepared for PCI scope changes? | **Yes** — once SAQ/scope is chosen and documented. |

---

## 6. Recommended next steps (after confirmation)

1. **You confirm:** Q1 (A/B/C), Q2 (yes), Q3 (yes + chosen SAQ).
2. **Technical:** Implement gateway “charge with token” path; add validation for token; frontend: PaymentsJS → send token to backend.
3. **Config:** Decide whether to keep redirect URLs in config for fallback or remove; document PCI/SAQ in `docs/`.

---

*Document: Checkout mode change — scope clarification. No code changes until confirmations above are given.*
