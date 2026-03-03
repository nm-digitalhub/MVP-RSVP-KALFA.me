# Auth & Authorization Enforcement — Phase 1 (FINAL)

**Context:** Multi-tenant system. Organizations are the security boundary. Events, Guests, Tables, Invitations, Payments are organization-scoped. This layer is **security-critical**. No architectural refactors.

---

## 1. Authentication Model (Mandatory)

- Use **existing Laravel authentication scaffolding only**.
- **Admin routes:** `middleware(['auth'])` or, if email verification is enabled, `middleware(['auth', 'verified'])`.

**Do NOT:**
- Create custom guards
- Modify Sanctum configuration
- Introduce new authentication systems
- Add parallel session logic

- **Web routes:** session-based authentication.
- **API routes:** Sanctum-based authentication.

No deviation.

---

## 2. Multi-Tenant Organization Scoping (Critical)

Organization is the **data isolation boundary**.

**Rules:**
- A user may only access organizations they belong to.
- A user may only access events belonging to those organizations.
- A user may **never** access cross-organization data.

A user must **NOT** access `/dashboard/events/{event}` if `event.organization_id` is not linked to the user via `organization_users`.

**Controllers MUST enforce scoping via:**
- Policies (preferred), **or**
- Explicit relationship: `$user->organizations()`.

**Never** trust route-model binding alone.

---

## 3. Policy Enforcement (Non-Negotiable)

**Authoritative policies:** `OrganizationPolicy`, `EventPolicy`, `GuestPolicy`, `PaymentPolicy`.

Every controller action must call:
- `$this->authorize('view', $event);`
- `$this->authorize('update', $event);`
- `$this->authorize('delete', $guest);`
- etc.

**Do NOT:**
- Manually compare `user_id`
- Inline permission checks
- Bypass policies
- Implement role checks inside controllers

Authorization logic must exist **only** inside Policies.

---

## 4. Dashboard Access Model

**Route:** `GET /dashboard`

**Behavior:**
- **0 organizations** → redirect to “Create Organization” flow.
- **1 organization** → auto-select and display.
- **Multiple organizations** → display organization switcher.

**Organization switching:**
- Must validate membership before switching.
- Must not allow arbitrary `organization_id` injection.
- If stored in session, it must be validated first.

Cross-tenant leakage must be **impossible**.

---

## 5. Event Access Protection

**Route:** `GET /dashboard/events/{event}`

**Must:**
1. Use route-model binding.
2. Immediately call `$this->authorize('view', $event);`
3. Abort with **403** if unauthorized.

**Never:**
- Show partial data.
- Silently redirect to dashboard.
- Fallback to another organization.

Unauthorized access must fail **clearly**.

---

## 6. Public Routes Security

**Public routes:** `/event/{slug}`, `/rsvp/{slug}` (or API equivalents, e.g. `/api/rsvp/{slug}`).

**Rules:**
- No authentication required.
- Only expose **public-safe** fields.
- **Never** expose:
  - Billing state
  - Payment IDs
  - `gateway_transaction_id`
  - Organization internal metadata
  - Internal database IDs (organization_id, internal keys) where not needed for public UX

**Public controller may only load:**
- Event (active only).
- Invitation (via slug).
- Guest (via invitation relation).

If event is **not active** → show “not available”.

---

## 7. Payment Status API Security

**Endpoint:** `GET /api/payments/{payment}`

**Must:**
- Require auth (Sanctum).
- Verify payment belongs to authenticated user’s organization (via `PaymentPolicy`).
- Return **only** status enum.
- **Not** return raw payload, gateway response, webhook data, or sensitive fields.

---

## 8. Role Extensibility (Future Safe)

Policy layer must remain **role-aware**. Do not hardcode “owner only”; use `OrganizationPolicy` (e.g. `isOwnerOrAdmin`) so roles can be extended.
