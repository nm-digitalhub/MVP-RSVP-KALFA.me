# Dependency Stabilization Audit

**Date:** 2026-02-27  
**Objective:** Validate OfficeGuy version, Livewire 4 compatibility, Filament dependency, and production lock before Composer hard lock.

---

## PHASE 1 — OfficeGuy Version

| Item | Value |
|------|--------|
| **Installed** | `officeguy/laravel-sumit-gateway` **v2.6.3** (released 2026-02-04) |
| **Latest stable** | **v2.7.0** (released 2026-02-21) |
| **Version delta** | One minor behind (2.6.3 → 2.7.0). Safe to upgrade in a separate step. |

**Direct dependencies (required):**

- `bezhansalleh/filament-plugin-essentials` ^1.1  
- `bytexr/filament-queueable-bulk-actions` ^4.0  
- **`filament/filament` ^4.0**  
- `guzzlehttp/guzzle` ^7.0  
- `laravel/framework` ^12.0  
- `php` ^8.2  
- `saloonphp/saloon` ^3.14  

**OfficeGuy does not declare `livewire/livewire`** in its `composer.json`; Livewire is pulled in via Filament.

---

## PHASE 2 — OfficeGuy / Filament Dependency

**`composer why filament/filament`:**

- **officeguy/laravel-sumit-gateway** v2.6.3 **requires** `filament/filament` (^4.0)  
- bezhansalleh/filament-plugin-essentials 1.1.0 requires filament/filament (^4.0|^5.0)  
- bytexr/filament-queueable-bulk-actions 4.0.0 requires filament/filament (^4.0)  

**Conclusion:** Filament is a **hard, required** dependency of OfficeGuy (and its plugins). It is not optional. The package ships a **ClientPanelProvider** that registers a Filament panel at path `/client`; the app does not use this panel for the product UI (dashboard is Blade/Livewire).

**Runtime:** Filament is loaded and a client panel is available; the app does not mount its admin UI in Filament. Optional use: `PublicCheckoutController` can resolve the current user via `Filament::auth()->user()` when present.

---

## PHASE 3 — Livewire Compatibility

| Item | Value |
|------|--------|
| **Current Livewire** | **v3.7.11** (released 2026-02-26) |
| **Project constraint** | `^3.5` in `composer.json` |

**Who requires Livewire:**

- **laravel/laravel** (this app): `livewire/livewire` ^3.5  
- **filament/support** v4.8.0: **`livewire/livewire` ^3.5**  

**Conclusion:** Filament 4.x (and thus OfficeGuy) **pins Livewire to ^3.5**. There is no Filament 4 requirement for Livewire 4. Upgrading to `livewire/livewire:^4.0` would conflict with `filament/support` (^3.5).

**OfficeGuy** does not restrict Livewire in its own `composer.json`; the restriction comes from Filament’s dependency tree.

---

## PHASE 4 — Upgrade Order Decision

| Condition | Result |
|-----------|--------|
| OfficeGuy latest stable | v2.7.0 exists; current is v2.6.3. |
| OfficeGuy ↔ Livewire 4 | **Incompatible.** Filament 4 requires Livewire ^3.5. |
| OfficeGuy ↔ Livewire 4 in future | Depends on Filament (or OfficeGuy) declaring support for Livewire 4. |

**Decision (current state):**

- **IF OfficeGuy incompatible with Livewire 4** → **Freeze Livewire at 3.x** for now; keep UI layer on Blade/Livewire 3; wait for Filament/OfficeGuy to support Livewire 4.  
- **Do not run** `composer require livewire/livewire:^4.0` until the stack supports it; it would break the dependency tree.

**Recommended sequence when Livewire 4 is supported:**

1. Upgrade OfficeGuy to latest stable (e.g. v2.7.0), run test suite and billing/checkout checks.  
2. When Filament/OfficeGuy allow Livewire 4: upgrade Livewire to ^4.0, then remove volt/workflows if still present and hard-lock.

---

## PHASE 5 — Production Lock (Applied Only Where Safe)

**Already in place:**

- `composer.json`: `"minimum-stability": "stable"`, `"prefer-stable": true"`.  
- **livewire/volt** and **pixelworxio/livewire-workflows** are **not** in `composer.json` (already removed).

**Not applied (would break resolve):**

- `composer require livewire/livewire:^4.0` — **not run**; Filament 4 requires Livewire ^3.5.  
- `composer update` for the purpose of moving to Livewire 4 — **deferred** until stack is compatible.

**Optional next step (non-breaking):**  
- `composer update officeguy/laravel-sumit-gateway` to move from v2.6.3 to v2.7.0, then re-test billing and checkout.

---

## Summary Table

| Package | Installed | Latest | Constraint | Notes |
|---------|-----------|--------|------------|------|
| officeguy/laravel-sumit-gateway | v2.6.3 | v2.7.0 | ^2.6 | Upgrade to ^2.7 optional; requires Filament ^4.0. |
| filament/filament | v4.8.0 | — | ^4.0 (via OfficeGuy) | Hard dependency; brings Livewire ^3.5. |
| livewire/livewire | v3.7.11 | — | ^3.5 | Keep at 3.x until Filament/OfficeGuy support Livewire 4. |

**Stability:** All referenced versions are stable; no beta packages required for this stack.

**Production lock:** Stable minimum-stability and prefer-stable are set. Livewire remains at ^3.5 by design until compatibility is announced.
