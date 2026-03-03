# Supplemental Constraints — Architectural Enforcement (Admin Phase 1)

**Purpose:** Non-negotiable architectural constraints for Product Surface Build Phase 1.

---

## 1. NO FILAMENT — ABSOLUTE

- Do **NOT** use Filament Panels.
- Do **NOT** register Filament resources.
- Do **NOT** create Filament pages.
- Do **NOT** rely on Filament components.
- Do **NOT** mount Admin inside Filament.

**Admin Panel must be built entirely with:**
- Blade
- Controllers
- Existing Layouts

Filament remains unused for product surface.

---

## 2. NO PARALLEL DOMAIN LOGIC

**Authoritative classes (do not reimplement):**
- BillingService
- SumitPaymentGateway
- PaymentStatus enum
- EventStatus enum
- EventBillingStatus enum

**Do NOT:**
- Create alternative payment handling logic
- Create new event activation logic
- Add conditional state changes in controllers
- Write status transitions manually

All state transitions must flow through existing services.

---

## 3. CONTROLLER DESIGN RULE

Controllers in this phase must be: **thin**, **readable**, delegating to:
- Models (for simple CRUD)
- Services (for orchestration)

Controllers must **NOT**:
- Contain business rules
- Contain payment logic
- Contain manual transaction management

---

## 4. DATA ACCESS RULE

**Inside Blade:**
- No DB queries
- No raw Eloquent calls
- Only use data injected by controller

**Inside Controllers:**
- Eager load relations
- Prevent N+1
- Use Policies for authorization

---

## 5. ADMIN PANEL IS PRODUCT UI — NOT BACKOFFICE

This panel is for **organizers**. It is not an internal system console.

**Therefore:**
- No developer dashboards
- No raw DB views
- No transaction dumps
- No debugging UI
- No log viewers

Only product-relevant information.

---

## 6. ROUTING CONSISTENCY

**Admin routes:**
- `/dashboard`
- `/dashboard/events/{event}`

**Public routes:**
- `/event/{slug}`
- `/rsvp/{slug}`

Do **NOT** mix with legacy unrelated routes.

---

## 7. PAYMENT FLOW INTEGRITY

UI must reflect: **pending → processing → succeeded/failed**

**Never:**
- Force UI to succeeded
- Infer success from token response
- Activate event without webhook

Polling must read **real** Payment status from DB.

---

## 8. CODE INTEGRATION REQUIREMENT

- New controllers integrate into `routes/web.php` and `routes/api.php` only.
- Do **NOT** create alternative route files.
- Reuse: existing layouts, existing components, existing CSS system.
- Do **NOT** introduce a new design system.

---

## 9. QUALITY BAR

Before marking phase complete, verify:

- A new organizer can complete **full lifecycle via UI**.
- No CLI intervention required.
- No manual DB updates required.
- No state correction required.

If any manual step is needed → implementation incomplete.
