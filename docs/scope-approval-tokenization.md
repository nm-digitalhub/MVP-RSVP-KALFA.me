# Scope Approval — Checkout Mode Change (Redirect → Embedded Tokenization)

**Date:** Approved  
**Mode:** C (Tokenization only for new UI)

---

## 1. Mode selection: C

- **New branded checkout view** will use SUMIT PaymentsJS tokenization on kalfa.me (embedded, no redirect to pay.sumit.co.il).
- **Existing redirect-based entry points** remain temporarily for migration/fallback.
- **Target after stabilization:** Migrate to A (full replacement); remove redirect flow.

---

## 2. BillingService: unchanged

- BillingService remains the **orchestration layer** (Payment/EventBilling creation + status transitions).
- **Webhook remains the source of truth** for redirect flow; for token flow, sync API success may also mark payment succeeded.
- **Only the gateway adapter** is extended to support "charge with token" flow.

---

## 3. PCI: accepted (conservative posture)

- We accept PCI scope change and will document it.
- **Default assumption:** SAQ-A-EP until formally confirmed as SAQ-A.
- **Hard rule:** Server must **never** receive or store card data; only token + metadata.

---

## Next step

Implement PaymentsJS tokenization path end-to-end:

1. Frontend: token generation (PaymentsJS)
2. Backend: charge-with-token (gateway + BillingService)
3. Webhook verification + idempotency (unchanged)
