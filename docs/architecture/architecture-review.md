# Architecture Review — Prioritized Recommendations

> Phase 4 | Based on validated Phases 1–3 | 2026-03-23
> Review method: end-to-end execution path verification only.

---

## 1. Executive Verdict

**The architecture is fundamentally sound.**

The core product (RSVP) is clean. The payment flow uses proper adapter isolation. Multi-tenancy has clear hierarchy (Account → Organization). Bounded contexts are identifiable. Middleware layering is correct.

**Real problems (3):**
1. iOS signing credentials were committed to git history — active security exposure.
2. The billing gate middleware (`EnsureAccountActive`) is runtime-critical but every test that touches it bypasses it with `withoutMiddleware()`. There is zero test coverage of the middleware itself executing its decision logic in a request lifecycle.
3. The webhook endpoint accepts any gateway name without allowlist, and HMAC verification is conditional (only if `BILLING_WEBHOOK_SECRET` is set).

**NOT real problems (despite appearances):**
- `Products/Show.php` at 1,037 lines — admin-only, zero tenant runtime impact.
- `OfficeGuyCustomerSearchService` at 543 lines — self-contained, admin-only.
- `OrganizationContext` coupling — convenience wrapper, not a risk.
- 10.9 GB repo noise — developer experience issue, not architectural.
- 18 OfficeGuy tables without models — vendor-managed by design.

---

## 2. Prioritized Recommendations

### Recommendation 1 — Purge credentials from git history

**Type:** `security remediation`

**Priority:** P0

**Why now:** iOS signing keys (`.p8`, `.p12`, `.key`, `.mobileprovision`, `.cer`, `.csr`) were committed in commit `83afc6eb`. The `.gitignore` now excludes `credentials/`, so no new commits will add them. But the files persist in git history. Anyone who clones the repo (or had access at any point) has the keys.

**End-to-end evidence:**
- Entry point: `git clone` or `git log --all -- credentials/`
- Middleware/gates: none — this is a git history exposure, not a runtime path
- Runtime path: N/A
- External dependency: Apple Developer certificates, iOS App Store signing
- Verified risk: Private key (`ios-private-key.key`), distribution certificate (`.p12`), provisioning profile (`.mobileprovision`), Auth key (`.p8`) are all extractable from git history. These allow signing and distributing iOS apps under your developer account.

**Impact:** High — compromised signing keys allow unauthorized app distribution.
**Effort:** Medium — requires `git filter-repo` or BFG, then force push, then key rotation on Apple Developer portal.
**Confidence:** High

**Action:**
1. Rotate ALL iOS signing credentials on Apple Developer portal immediately (revoke existing, generate new).
2. Run `git filter-repo --path credentials/ --invert-paths` (or BFG Repo-Cleaner).
3. Force push to all remotes.
4. Notify any collaborators to re-clone.
5. Store new credentials in a secrets manager (not git).

**Do not do instead:** Do not just `.gitignore` and move on — the damage is the *history*, not the working tree.

---

### Recommendation 2 — Test the billing gate middleware through execution, not around it

**Type:** `runtime-critical improvement`

**Priority:** P1

**Why now:** `EnsureAccountActive` is the single runtime gate between "user has a paid account" and "user can access tenant features". It runs on every protected route. It has complex multi-source org resolution (route param → event → session) and checks `hasBillingAccess()` with caching.

Every existing test that hits protected routes uses `withoutMiddleware([EnsureAccountActive::class])`. This means the middleware's own decision logic — the most critical access control path in the app — has never been exercised in a test that also validates the downstream controller behavior.

**End-to-end evidence:**
- Entry point: any route under `ensure.account_active` (dashboard/events/*, team, etc.)
- Middleware/gates: `auth → verified → ensure.organization → ensure.account_active`
- Runtime path: `EnsureAccountActive::handle()` → resolves org via match → loads `$org->account` → calls `hasBillingAccess()` (cached 60s) → denies or passes
- External dependency: none (pure DB check + cache)
- Verified risk: If `hasBillingAccess()` has a bug, or if the cache TTL creates a stale-pass scenario, or if the org-resolution match falls through incorrectly, tenants could access features without paying. Conversely, a false denial blocks paid users. Neither scenario is tested.

**Impact:** High — billing access is the monetization gate.
**Effort:** Low — 5-7 tests covering: active account passes, expired trial blocks, no account blocks, org-from-route vs org-from-session, impersonation bypass, system admin bypass.
**Confidence:** High

**Action:**
Write a dedicated `EnsureAccountActiveMiddlewareTest` that:
1. Creates Account + Organization + User + Event fixtures
2. Hits actual protected routes WITHOUT `withoutMiddleware()`
3. Asserts HTTP 402 (JSON) or redirect (web) when account has no billing access
4. Asserts HTTP 200 when account has active product/subscription/trial
5. Asserts bypass for system admin and impersonation
6. Tests org resolution from route param vs session fallback

**Do not do instead:** Do not add `hasBillingAccess()` unit tests alone — the risk is in the middleware orchestration (org resolution + cache + denial response), not the boolean check.

---

### Recommendation 3 — Harden the webhook endpoint

**Type:** `runtime-critical improvement`

**Priority:** P1

**Why now:** `POST /api/webhooks/{gateway}` is an unauthenticated public endpoint that triggers payment state transitions (PendingPayment → Active). Two issues:

**Issue A — Open gateway parameter:** The `{gateway}` route parameter accepts any string. If `$gateway !== 'sumit'`, the HMAC verification is entirely skipped. The handler still runs: resolves `PaymentGatewayInterface` from the container (which is always the configured gateway regardless of the URL segment) and calls `handleWebhook()`. This means an attacker can call `/api/webhooks/anything` to skip HMAC verification.

**Issue B — Optional HMAC:** Even for `sumit`, HMAC verification only runs if `BILLING_WEBHOOK_SECRET` is set in `.env`. If it's unset or empty, all webhooks are accepted without signature validation. There's no test or config validation ensuring this is set in production.

**End-to-end evidence:**
- Entry point: `POST /api/webhooks/{gateway}` — unauthenticated, throttled
- Middleware/gates: throttle only
- Runtime path: `WebhookController::handle()` → optional HMAC check → `PaymentGatewayInterface::handleWebhook()` → `BillingService::markPaymentSucceeded()` → Event.status = Active
- External dependency: SUMIT webhook callback
- Verified risk: Forged webhook could mark a payment as succeeded, activating an unpaid event. Idempotency check only prevents re-processing already-terminal payments — it does not prevent the FIRST forged success.

**Impact:** High — payment bypass creates revenue loss.
**Effort:** Low — 3 changes.
**Confidence:** High

**Action:**
1. Allowlist the gateway parameter: reject any value other than configured gateways.
```php
$allowed = ['sumit'];
if (!in_array($gateway, $allowed, true)) {
    return response()->json(['error' => 'Unknown gateway'], 404);
}
```
2. Make HMAC verification mandatory in production: fail if `BILLING_WEBHOOK_SECRET` is not set when `BILLING_GATEWAY !== 'stub'`.
3. Write a test that confirms: (a) unknown gateway → 404, (b) missing signature → 403, (c) invalid signature → 403, (d) valid signature + valid payload → 200 + state transition.

**Do not do instead:** Do not add IP allowlisting as primary control — SUMIT webhook source IPs may change. HMAC is the correct control.

---

### Recommendation 4 — Separate non-product directories from repo

**Type:** `repo hygiene`

**Priority:** P2

**Why now:** 10.9 GB of non-application content (AI tooling, Obsidian vault, archives) in the repo impacts:
- Clone time (minutes → seconds after cleanup)
- GitIngest digest quality
- Agent context confusion (tools index 11GB before finding 6MB of app)
- CI/CD pipeline efficiency (if any)

This is NOT a runtime risk. It is a developer experience and operational efficiency issue.

**End-to-end evidence:**
- Entry point: `git clone`, CI pipeline, GitIngest scan, agent analysis
- Middleware/gates: N/A
- Runtime path: N/A — none of these directories are referenced by PHP autoload, Vite config, or deployment scripts
- External dependency: none
- Verified risk: No runtime risk. DX degradation only.

**Impact:** Medium — DX improvement, faster clones, cleaner analysis.
**Effort:** Low — `git rm -r --cached` for gitignored dirs, move others to separate repos.
**Confidence:** High

**Action:**
1. Verify these directories have no runtime references (already verified in Phase 2): `documentation/`, `obsidian-hub/`, `obsidian-claude-pkm/`, `archive/`, `nativephp/`, `var/`.
2. Add to `.gitignore` (if not already): `documentation/`, `obsidian-hub/`, `obsidian-claude-pkm/`, `archive/`, `nativephp/`, `var/`.
3. `git rm -r --cached` for each.
4. Commit and push.
5. Optionally: archive to a separate repo/branch for reference.

**Do not do instead:** Do not use `git filter-repo` to purge history for these — it's unnecessary for non-sensitive content. Just stop tracking them.

---

### Recommendation 5 — Define the Product Engine extraction boundary

**Type:** `bounded-context isolation`

**Priority:** P2

**Why now:** Product Engine has the cleanest bounded context in the codebase (13 models, 9 services, 4 commands, 8 enums, 12 tables) with a single coupling point (Account model). If any future refactoring, team scaling, or modularization happens, this is the highest-value extraction target.

However, this is NOT an immediate action — it's a preparedness step. The goal is to define the contract boundary NOW so that future changes don't accidentally tighten the coupling.

**End-to-end evidence:**
- Entry point: System admin panel (Livewire) + artisan commands + `EnsureAccountActive` middleware (via `hasBillingAccess()`)
- Middleware/gates: `system.admin` for admin panel; `ensure.account_active` for tenant gate
- Runtime path: `EnsureAccountActive` → `Account::hasBillingAccess()` → queries `account_products`, `account_subscriptions` → cached 60s. Admin panel: `System/Products/*` Livewire → direct model operations.
- External dependency: none (pure internal domain)
- Verified risk: No current risk. The risk is future: without an explicit boundary, new features will add more cross-domain calls through Account, making eventual extraction harder.

**Impact:** Medium — future modularity.
**Effort:** Low (boundary definition only, not extraction).
**Confidence:** Medium — depends on whether extraction actually happens. If the team stays small and Product Engine doesn't grow, internal modularization may be sufficient.

**Action:**
1. Create `app/Contracts/ProductEngine/BillingAccessChecker.php` interface with `hasBillingAccess(Account): bool`.
2. Extract `Account::hasBillingAccess()` logic into a service implementing that interface.
3. `EnsureAccountActive` middleware depends on interface, not Account model directly.
4. This creates a clean seam without moving any files.

**Do not do instead:** Do not extract into `packages/` yet — the overhead of cross-package migrations and autoload config is not justified until there's a second team or independent deploy need.

---

## 3. Recommendations Explicitly NOT Prioritized

### A. Decompose `System/Products/Show.php` (1,037 lines)

**Why not now:** Admin-only component behind `system.admin` middleware. Zero tenant-facing runtime impact. No user can trigger this code without system admin access. Decomposing it improves developer experience when editing the admin panel, but does not reduce risk.

**Before it becomes actionable:** Only worth doing when the admin panel is actively being extended with new product management features.

---

### B. Add local Eloquent models for OfficeGuy CRM tables

**Why not now:** The 18 `officeguy_*` tables are vendor-managed. Adding local models creates a maintenance burden (must track vendor schema changes). The single service that queries them (`OfficeGuyCustomerSearchService`) is self-contained and admin-only.

**Before it becomes actionable:** Only if CRM features expand beyond admin search into tenant-facing functionality, or if you need Eloquent policies/relationships for access control.

---

### C. Extract `voice-bridge/` to a separate repository

**Why not now:** It's already protocol-isolated (HTTP + WebSocket). It has its own Dockerfile, package.json, and src/. The only coupling is 2 config values. It's 16 MB — negligible compared to the 10.9 GB of AI tooling.

**Before it becomes actionable:** Only if voice-bridge gets its own CI/CD pipeline, its own team, or needs independent versioning.

---

### D. Rename `OrganizationContext` or refactor its API

**Why not now:** It's a convenience wrapper. The middleware doesn't use it. 12 of 14 usages are read-only (`current()`). Renaming or restructuring it has zero runtime impact.

**Before it becomes actionable:** Only if the tenancy model changes (e.g., account-level context replaces org-level context).

---

### E. Increase general test coverage

**Why not now:** Test coverage is sparse (~35 test files for ~150 runtime files), but the *right* response is not "write more tests everywhere." The critical gap is specific: billing gate middleware and webhook security (covered in Recommendations 2 and 3). Adding tests for admin panel components or UI consistency is quality work, not risk reduction.

**Before it becomes actionable:** After Recommendations 2 and 3 are complete. Then prioritize tests for: public RSVP flow, payment initiation flow, and organization switching.

---

### F. Remove stub services (`DocumentService`, `SystemBillingService`)

**Why not now:** All methods return stub values. They are not called from any runtime path except admin panel display. Removing them is cleanup, not risk reduction. However, removing them requires a human product decision: is OfficeGuy document generation still planned?

**Before it becomes actionable:** Product decision on OfficeGuy document/billing integration roadmap.

---

## 4. Suggested Sequence

```
Week 1:
  1. Security fix — rotate iOS credentials + purge from git history     [P0]
  2. Webhook hardening — allowlist + mandatory HMAC                     [P1]

Week 2:
  3. Billing gate tests — EnsureAccountActive middleware test suite      [P1]

Week 3:
  4. Repo hygiene — stop tracking non-product directories               [P2]

When ready (no time pressure):
  5. Product Engine boundary — interface extraction for hasBillingAccess [P2]
```

Recommendations 1 and 2 can run in parallel (security + webhook are independent changes).
Recommendation 3 should follow 2 (tests validate the hardened webhook path too).
Recommendation 4 is independent and can happen anytime.
Recommendation 5 is preparedness, not urgency.

---

## 5. Review Constraints

### Must not be changed blindly
- `Account` model — runtime billing gate dependency, 14 relationships
- `EnsureAccountActive` middleware — every protected route depends on it
- `routes/api.php` — public API contract, mobile app depends on it
- `PaymentGatewayInterface` binding in `AppServiceProvider` — payment flow pivot

### Requires additional tests FIRST
- Any change to `EnsureAccountActive` org-resolution logic (the match expression)
- Any change to `Account::hasBillingAccess()` caching behavior
- Any change to webhook signature verification

### Requires human decision FIRST
- Product Engine: extract to package vs internal modularization
- OfficeGuy CRM: add models or keep vendor-only
- Stub services: implement or remove
- voice-bridge: separate repo or keep in monorepo
- Repo cleanup strategy: aggressive (force push) vs gradual (gitignore)
