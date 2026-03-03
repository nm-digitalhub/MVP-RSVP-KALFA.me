# PCI/SAQ — Embedded Tokenization (PaymentsJS)

**Scope approval:** Conservative posture. Document and enforce.

---

## Assumption

- **Default:** SAQ-A-EP until formally confirmed as SAQ-A.
- **Hard rule:** Server must **never** receive or store card data; only token + metadata.

---

## Tokenization flow (kalfa.me)

1. **Client:** Checkout page loads SUMIT PaymentsJS (`https://app.sumit.co.il/scripts/payments.js`). Card fields are entered in the browser; SUMIT SDK tokenizes and produces a **single-use token**.
2. **Client → Server:** Only the **token** (e.g. `og-token`) is sent to our API in the request body. No card number, no expiry, no CVV.
3. **Server:** Receives `plan_id` + `token`. Calls SUMIT charge API with `SingleUseToken`; does not log or store card data.
4. **Webhook:** Remains source of truth for redirect flow; for token flow, sync API success may also mark payment succeeded.

---

## Responsibility

- **SUMIT:** Hosted fields / tokenization; PCI scope for the iframe and token generation.
- **kalfa.me:** Never touch raw card data; only pass token to SUMIT and store payment outcome (success/failure, transaction id).

---

## References

- Scope approval: `docs/scope-approval-tokenization.md`
- Configuration: `docs/configuration-governance-sumit.md`
