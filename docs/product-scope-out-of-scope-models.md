# Product Scope — Out-of-Scope Models (Mark for Removal)

**Authoritative product domains:** Authentication, Organizations, Organization membership, Events, Guests, Tables/seating, Invitations, RSVP, Billing, Payments, Admin dashboard UI, Public event page, Checkout status.

The following app models are **unrelated to event management** and are marked for removal. Do not integrate them into the dashboard or product surface.

| Model / Concept | Location | Reason |
|-----------------|----------|--------|
| **Service** | `app/Models/Service.php` | Appointment/service catalog (name, description, duration_minutes, price). Not part of event/guest/RSVP domain. |
| **Appointment** | (if used only with Service) | Workflow/testbench appointments — not event management. |
| **Order** / **CartItem** | (if e‑commerce only) | Only event billing/checkout is in scope. |

**Action:** Do not use Service, Product, Page, Post, Category, eSim, ContentBlock (or similar) in the admin UI or navbar. When cleaning legacy code, remove or archive these; do not wire them into the product surface.

**Last updated:** Product Scope Correction Directive.
