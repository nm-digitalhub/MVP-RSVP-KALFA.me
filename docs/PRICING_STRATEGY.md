# Pricing Strategy — AI Voice Agent RSVP

> **Document type:** Internal Source of Truth + Investor Reference
> **Product:** AI Voice Agent RSVP (product_id: 1)
> **Currency:** ILS (Israeli New Shekel)
> **Last validated:** 2026-03-25
> **Status:** DRAFT — pending price correction execution

---

## 1. Product Overview

Kalfa.me offers an AI-powered voice RSVP agent for event organizers. Guests receive a phone call from an AI agent (Gemini Live via Twilio Media Streams) that collects their attendance response conversationally. The product replaces manual phone-based RSVP with automated, scalable voice interactions.

**Revenue model:** Monthly subscription with included call capacity + per-call overage billing for usage beyond the included quota.

---

## 2. Product Architecture

### 2.1 Domain Model

```
Product (AI Voice Agent RSVP, slug: ai-voice-agent)
├── ProductEntitlement (voice_rsvp_enabled = true)
├── ProductLimit (average_call_minutes_assumption = 2, direct_cost_usd_per_minute = 0.0911)
├── ProductFeature (voice_transport = twilio-media-streams, ai_provider = gemini-live)
└── ProductPlan (Starter | Growth | Scale)
    ├── ProductPrice (billing_cycle = monthly)  → subscription fee
    ├── ProductPrice (billing_cycle = usage)    → overage fee per call
    │     └── metadata: { price_type: "overage", metric_key, unit }
    └── metadata
        ├── limits: { voice_rsvp_limit, voice_minutes_limit }
        ├── usage_policies: { voice_rsvp_calls: { mode: "hard" } }
        └── commercial: { included_quantity, overage_metric_key, overage_unit, ... }
```

### 2.2 Commercial Flow (End-to-End)

```
Customer                    System                          External
────────                    ──────                          ────────
1. Visit /plan-selection
   ──→ PlanSelection        loads active ProductPlans
                            shows monthly prices
2. Click "Start Trial"
   ──→ SubscriptionService  creates AccountSubscription
                            (status=trial, 14 days)
3. Click "Purchase"
   ──→ CheckoutController   renders PaymentsJS iframe
                            ──→ SUMIT tokenizes card (₪1 auth)
   ──→ PurchaseController   stores OfficeGuyToken
                            creates subscription
                            ──→ SumitBillingProvider
                                creates SUMIT customer
                                charges monthly fee
                            activates subscription
                            grants AccountProduct
                            propagates entitlements
4. Voice call made
   ──→ CallingService       checks voice_rsvp_enabled
                            checks voice_rsvp_limit
                            initiates Twilio call
                            ──→ Twilio → Media Stream → Gemini Live
5. RSVP collected
   ──→ RsvpVoiceController  records RsvpResponse
                            increments AccountFeatureUsage (legacy)
   ──→ UsageMeter           creates UsageRecord
                            checks overage policy
                            if soft → charges via BillingProvider
                                      ──→ SUMIT charges card
```

### 2.3 Feature Resolution Chain

Limits and entitlements are resolved hierarchically by `FeatureResolver` (cached 300s):

| Priority | Source | Example |
|----------|--------|---------|
| 1 | Account override | Manual admin override |
| 2 | Propagated entitlement | From AccountProduct → Product → ProductEntitlement |
| 3 | Plan limit | From active subscription → `ProductPlan.metadata.limits.{key}` |
| 4 | Product default | From AccountProduct → `Product.activeEntitlements` |
| 5 | System default | `config('product-engine.defaults')` |

### 2.4 Pricing Data Flow

```
ProductPrice (usage)  ─── source of truth for overage amount
    ├── UsageMeter reads amount + currency + metric_key
    ├── Admin price editor displays/edits
    └── Plan metadata synced as fallback (transition)

ProductPrice (monthly) ─── source of truth for subscription fee
    ├── PlanSelection shows customer-facing price
    ├── SUMIT Gateway charges recurring fee
    ├── Admin price editor displays/edits
    └── Unit Economics computes revenue/call
```

### 2.5 Known Architectural Issues

**Trial subscriptions bypass limit enforcement.** `FeatureResolver::planLimitValue()` queries `activeSubscriptions()` which filters by `status=active`. Trial subscriptions have `status=trial`, so `voice_rsvp_limit` and `voice_minutes_limit` resolve to `null` during trial — meaning trial users have **unlimited calls**.

**Entitlement propagation requires AccountProduct.** `voice_rsvp_enabled` is only propagated when `AccountProduct` is granted (via `account->grantProduct()`). Trial-only accounts without an explicit product grant have no `voice_rsvp_enabled` entitlement. (Account #4/Starter trial is affected.)

**Usage policies set to "hard" prevent overage billing.** All plans have `usage_policies.voice_rsvp_calls.mode = "hard"`. The `billOverageIfRequired()` method returns early without charging when mode is not `"soft"`. **No overage revenue will be generated until policies are changed to "soft".**

**Voice minutes limit is informational only.** `voice_minutes_limit` is defined in plan metadata but no code enforces minute-level tracking. Only `voice_rsvp_calls` (call count) is tracked.

---

## 3. Cost Basis

All pricing derives from a bottom-up cost model anchored to the direct cost of a single AI voice call.

### 3.1 Cost Components (per minute)

| Component | Vendor | Cost (USD/min) | Source |
|-----------|--------|----------------|--------|
| Voice telephony (IL mobile, from IL) | Twilio | $0.0646 | [Twilio Voice Pricing IL](https://www.twilio.com/en-us/voice/pricing/il) |
| Media Streams (real-time audio) | Twilio | $0.0040 | Twilio Media Streams pricing |
| Gemini Live Audio (AI agent) | Google | $0.0225 | [Vertex AI Generative AI Pricing](https://cloud.google.com/vertex-ai/generative-ai/pricing) |
| **Total direct cost** | | **$0.0911/min** | |

### 3.2 Per-Call Cost

| Parameter | Value |
|-----------|-------|
| Assumed average call duration | 2 minutes |
| Direct cost per call | $0.1822 USD |
| Direct cost per call (ILS) | ~0.656 ILS (at 3.60 USD/ILS) |

### 3.3 Cost Sensitivity

- **FX rate (USD/ILS):** A 10% depreciation of ILS increases per-call cost by ~0.066 ILS
- **Vendor pricing changes:** Twilio and Google may adjust rates quarterly

**Recommendation:** Review cost basis every quarter or when vendor pricing changes are announced.

---

## 4. Pricing Principles

These principles are **binding constraints** — any pricing change must satisfy all of them.

### Principle 1: Minimum Margin Floor

> Every price point must achieve at least the **target gross margin** on a per-unit basis.

| Parameter | Value |
|-----------|-------|
| Target gross margin | **18%** |
| Cost per call (ILS) | 0.656 |
| **Minimum price per call** | **0.800 ILS** |

Formula: `min_price = cost / (1 - target_margin)` = 0.656 / 0.82 = **0.800 ILS**

### Principle 2: Overage Must Exceed Included Rate

> The per-call overage price must always be **higher** than the effective per-call rate of the included quota.

**Rationale:** Overage is unplanned usage. It should cost more because:
- It creates unpredictable infrastructure load
- It is not covered by the upfront subscription commitment
- Cheaper overage incentivizes gaming (buy small plan, rely on overage)

Formula: `overage_price > monthly_price / included_calls`

### Principle 3: Margin Must Not Decay with Scale

> Higher-tier plans must not have lower gross margins than lower-tier plans.

**Rationale:** Larger customers create more operational complexity. If margin decreases with scale, growth becomes unprofitable.

### Principle 4: Single Source of Truth

> `ProductPrice` (billing_cycle=usage) is the source of truth for overage pricing. Plan metadata is synced as fallback during transition.

---

## 5. Current Pricing (Pre-Correction)

### 5.1 Plan Summary

| Plan | Monthly (ILS) | Included Calls | Revenue/Call | Overage/Call | Gross Margin |
|------|--------------|----------------|-------------|-------------|-------------|
| **Starter** | 99 | 120 | 0.825 | 0.75 | 20.5% |
| **Growth** | 299 | 400 | 0.748 | 0.69 | 12.3% |
| **Scale** | 849 | 1,200 | 0.708 | 0.65 | 7.3% |

### 5.2 Failure Analysis

#### Issue 1: Scale overage priced below cost

Scale plan overage is **0.65 ILS/call** against a cost of **0.656 ILS/call**. Every overage call generates a **loss of 0.006 ILS**. A Scale customer exceeding quota by 500 calls/month loses the business 3 ILS/month in direct costs alone.

**Violated principle:** #1 (Minimum Margin Floor)

#### Issue 2: Inverted overage pricing across all plans

| Plan | Included Rate | Overage Rate | Delta |
|------|--------------|-------------|-------|
| Starter | 0.825 | 0.75 | -9.1% |
| Growth | 0.748 | 0.69 | -7.8% |
| Scale | 0.708 | 0.65 | -8.2% |

A rational customer would buy the smallest plan and rely on overage, since overage is cheaper per call than the subscription. This undermines the subscription model.

**Violated principle:** #2 (Overage Must Exceed Included Rate)

#### Issue 3: Margin decay across tiers

| Plan | Gross Margin | Target |
|------|-------------|--------|
| Starter | 20.5% | 18% |
| Growth | 12.3% | 18% |
| Scale | 7.3% | 18% |

The most successful customers (Scale) are the least profitable. Growth becomes unprofitable at scale.

**Violated principle:** #3 (Margin Must Not Decay with Scale)

---

## 6. Corrected Pricing

### 6.1 Methodology

1. **Monthly price:** Set so that `monthly / included_calls >= 0.800 ILS` (Principle 1)
2. **Overage price:** Set above the included rate (Principle 2) and above 0.800 ILS floor (Principle 1)
3. **Volume discount:** Higher tiers get a modest per-call discount on subscription, but overage stays penalizing

### 6.2 Recommended Prices

| Plan | Monthly (ILS) | Included Calls | Revenue/Call | Overage/Call | Gross Margin | Overage Margin |
|------|--------------|----------------|-------------|-------------|-------------|---------------|
| **Starter** | 99 | 120 | 0.825 | **0.95** | 20.5% | 31.0% |
| **Growth** | **329** | 400 | 0.823 | **0.90** | 20.2% | 27.1% |
| **Scale** | **999** | 1,200 | 0.833 | **0.85** | 21.3% | 22.8% |

### 6.3 Validation Against Principles

| Principle | Starter | Growth | Scale | Status |
|-----------|---------|--------|-------|--------|
| 1. Margin floor (>= 18%) | 20.5% | 20.2% | 21.3% | PASS |
| 2. Overage > included | 0.95 > 0.825 | 0.90 > 0.823 | 0.85 > 0.833 | PASS |
| 3. No margin decay | 20.5% → 20.2% → 21.3% | | | PASS |
| 4. Source of truth | ProductPrice | ProductPrice | ProductPrice | PASS |

### 6.4 Price Changes Summary

| Plan | Field | Current | Proposed | Change |
|------|-------|---------|----------|--------|
| Starter | Monthly | 99 | 99 | — |
| Starter | Overage | 0.75 | **0.95** | +26.7% |
| Growth | Monthly | 299 | **329** | +10.0% |
| Growth | Overage | 0.69 | **0.90** | +30.4% |
| Scale | Monthly | 849 | **999** | +17.7% |
| Scale | Overage | 0.65 | **0.85** | +30.8% |

---

## 7. Unit Economics at Full Capacity

### 7.1 Per-Plan Profitability (Corrected Prices)

| Plan | Revenue | COGS | Gross Profit | Gross Margin |
|------|---------|------|-------------|-------------|
| **Starter** (120 calls) | 99 ILS | 79 ILS | 20 ILS | 20.5% |
| **Growth** (400 calls) | 329 ILS | 262 ILS | 67 ILS | 20.2% |
| **Scale** (1,200 calls) | 999 ILS | 787 ILS | 212 ILS | 21.3% |

### 7.2 Overage Contribution (assuming 20% overage)

| Plan | Overage Calls | Overage Revenue | Overage COGS | Overage Profit |
|------|--------------|----------------|-------------|---------------|
| Starter | 24 | 22.80 | 15.74 | 7.06 |
| Growth | 80 | 72.00 | 52.48 | 19.52 |
| Scale | 240 | 204.00 | 157.44 | 46.56 |

### 7.3 Blended Monthly Revenue (subscription + 20% overage)

| Plan | Subscription | Overage | Total | Blended Margin |
|------|-------------|---------|-------|---------------|
| Starter | 99 | 22.80 | 121.80 | 22.3% |
| Growth | 329 | 72.00 | 401.00 | 21.5% |
| Scale | 999 | 204.00 | 1,203.00 | 21.5% |

---

## 8. Competitive Positioning

### 8.1 Value Anchor

- **Starter:** Best for small events (up to 120 calls/month)
- **Growth:** Best for recurring events (up to 400 calls/month) — *Most Popular*
- **Scale:** Best for high-volume operations (up to 1,200 calls/month)

### 8.2 Pricing Psychology

- All prices end in 9 (99, 329, 999) — standard SaaS anchoring
- Growth is positioned as "most popular" to drive mid-tier adoption
- 14-day free trial removes purchase friction

### 8.3 Plan Upgrade Breakpoints

| Scenario | Breakpoint |
|----------|-----------|
| Starter → Growth | ~60 overage calls make Growth cheaper than Starter + overage |
| Growth → Scale | ~223 overage calls make Scale cheaper than Growth + overage |

---

## 9. Implementation Notes

### 9.1 Database Values (Minor Units)

All prices are stored in **agorot** (1/100 ILS):

| Plan | Monthly (DB) | Usage (DB) |
|------|-------------|-----------|
| Starter | 9900 | 95 |
| Growth | 32900 | 90 |
| Scale | 99900 | 85 |

### 9.2 SUMIT Gateway Sync

When prices are updated via the admin panel:
- `savePlan()` → syncs name, SKU, description + updates usage `ProductPrice`
- `savePrice()` → syncs price to SUMIT via `Accounting_Price` + syncs to plan metadata

### 9.3 Existing Subscribers

| Account | Plan | Status | Trial Ends |
|---------|------|--------|-----------|
| Eventra (#1) | Growth | Trial | 2026-04-05 |
| Kalfaaaa (#4) | Starter | Trial | 2026-04-05 |

Trial users should be grandfathered at original price for one billing cycle after trial conversion.

### 9.4 Prerequisites Before Overage Revenue

Before overage billing can generate revenue, three issues must be resolved:

1. **Change usage_policies mode from "hard" to "soft"** — currently all plans block overage instead of charging
2. **Fix FeatureResolver for trial subscriptions** — trial users don't have limits enforced
3. **Ensure AccountProduct is granted on trial start** — needed for entitlement propagation

---

## 10. Review Schedule

| Trigger | Action |
|---------|--------|
| Quarterly | Review cost basis against current vendor pricing |
| USD/ILS moves > 5% | Recalculate ILS cost per call and validate margins |
| New plan tier added | Validate against all 4 pricing principles |
| Vendor pricing change | Update cost components and cascade to margins |
| 50+ active subscribers | Review overage utilization data for pricing optimization |

---

## Appendix A: Pricing Principle Validation Formula

```
For any plan P with:
  M = monthly subscription price (ILS)
  Q = included call quantity
  O = overage price per call (ILS)
  C = direct cost per call (ILS) = 0.656
  T = target margin = 0.18

Constraints:
  1. M / Q >= C / (1 - T)           → included rate meets margin floor
  2. O > M / Q                       → overage exceeds included rate
  3. O >= C / (1 - T)               → overage meets margin floor
  4. margin(P_n) >= margin(P_n-1)   → no margin decay across tiers
```

## Appendix B: FX Sensitivity

| USD/ILS Rate | Cost/Call (ILS) | Min Price/Call (18%) | Current Starter Rate | Buffer |
|-------------|----------------|---------------------|---------------------|--------|
| 3.40 | 0.619 | 0.755 | 0.825 | +9.3% |
| 3.50 | 0.638 | 0.778 | 0.825 | +6.0% |
| **3.60** | **0.656** | **0.800** | **0.825** | **+3.1%** |
| 3.70 | 0.674 | 0.822 | 0.825 | +0.4% |
| 3.80 | 0.692 | 0.844 | 0.825 | **-2.2%** |

FX break-even for Starter plan: **USD/ILS 3.80**. Above this, Starter's included rate falls below the margin floor.

## Appendix C: SUMIT Entity IDs

| Plan | SKU | SUMIT Entity ID |
|------|-----|----------------|
| Starter | AI-VOICE-AGENT_STARTER | 1771203457 |
| Growth | AI-VOICE-AGENT_GROWTH | 1771202872 |
| Scale | AI-VOICE-AGENT_SCALE | 1771203551 |
