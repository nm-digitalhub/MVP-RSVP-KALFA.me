# SUMIT Transaction Safety Review

## Scope

Review of `App\Services\BillingService` and the SUMIT payment flow for transaction boundaries, lock duration, and partial-state risk.

---

## DB::transaction wrapping

| Method | Wrapped in DB::transaction? | Notes |
|--------|-----------------------------|--------|
| `initiateEventPayment()` | **Yes** | Entire method body runs inside one closure. |
| `markPaymentSucceeded()` | **Yes** | Single closure: payment update + payable/event updates. |
| `markPaymentFailed()` | **Yes** | Single closure: payment status update. |

All three methods that mutate payment/event state are correctly wrapped.

---

## External HTTP call inside transaction

**Finding:** In `initiateEventPayment()`, the gateway call is **inside** the same DB transaction:

```php
return DB::transaction(function () use ($event, $plan) {
    // ... event + EventBilling + Payment created ...
    $result = $this->gateway->createOneTimePayment(...);  // ← HTTP to SUMIT
    $payment->update([...]);
    return array_merge($result, [...]);
});
```

- **Risk:** The transaction stays open for the duration of `createOneTimePayment()` (SUMIT `/billing/payments/beginredirect/`). If SUMIT is slow or times out, the connection holds row locks (e.g. on the new payment and event) longer.
- **Partial state:** If the gateway throws **after** the transaction has committed, there is no such case here: the gateway is called inside the transaction, and any exception rolls back the whole transaction (event stays draft, no EventBilling/Payment rows). So **no** partial state where payment is created but event not updated.
- **Conclusion:** Acceptable for current scale. Document as a known trade-off. Optional future improvement: start transaction only for reads/creates, commit, then call gateway and run a second short transaction to update payment with gateway response (reduces lock time but adds complexity). **No change required** unless lock contention is observed.

---

## No partial state if gateway call fails

- If `createOneTimePayment()` throws or returns failure (and our adapter throws), the exception propagates and the transaction is rolled back. Event remains draft; no EventBilling or Payment rows are persisted.
- If the gateway returns success but the subsequent `$payment->update()` fails, the transaction rolls back; again no partial state.
- **Conclusion:** No partial state possible when the gateway call fails or throws.

---

## Summary

| Check | Result |
|-------|--------|
| DB::transaction wrapping | Confirmed for all three methods. |
| HTTP inside transaction | Yes in initiateEventPayment; lock held during SUMIT call. Documented; acceptable for now. |
| Partial state on gateway failure | None; transaction rollback on exception or early return. |

**Recommendation:** No refactor. Keep current design; revisit only if production shows long lock waits or timeouts.
