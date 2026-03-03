# Controlled Legacy Cleanup — Read-Only Audit & Classification

**Objective:** Align with Event SaaS domain without breaking core functionality. No deletion in this document.

---

## PHASE 1 — Global Audit (Structured)

### 1. Filament / PanelProvider / FilamentUser / getFilamentName / getFilamentAvatarUrl

| File | Line | Usage | Referenced in |
|------|------|--------|----------------|
| `app/Services/SumitPaymentGateway.php` | 17 | Comment: "no Filament" | — |
| `bootstrap/cache/packages.php` | 45–108 | Filament package providers (cached) | config/bootstrap |
| `bootstrap/cache/services.php` | 31–83 | Filament service providers (cached) | config/bootstrap |
| `config/officeguy.php` | 195 | Comment: "Filament notification panel" | config |
| `public/js/filament/notifications/notifications.js` | 1 | Filament notifications bundle | public asset |
| `resources/js/app.js` | 3 | Comment: "Filament v4 compatible" | layout |
| `resources/views/public/home.blade.php` | 430 | Comment: "Filament v4 Package Showcase" | view |
| `resources/views/public/filamentor-page.blade.php` | 8, 138 | Comments: Filamentor rendering | view |
| `public/js/filamentor/filamentor.js` | 858 | String "Filamentor" | public asset |
| `public/js/pboivin/filament-peek/*.js` | 1 | Filament peek tool | public asset |
| `public/js/cardcom-lowprofile.js` | 3, 65, 127, 152 | Comments / Filament form integration | public asset |
| `public/js/cardcom-lowprofile-advanced.js` | 9, 195–254 | Filament Forms integration | public asset |
| `public/js/cardcom-hosted-fields.js` | 1 | Comment: "for Filament" | public asset |
| `public/js/toggle-enhancements.js` | 2, 298 | Comment: "Filament eSIM Settings" | public asset |
| `public/js/sumit-payment-form.js` | 4 | Comment: "Filament Client Panel" | public asset |
| `resources/lang/vendor/officeguy/*/officeguy.php` | multiple | Filament mentions in package lang | vendor |
| `resources/js/mobile-sidebar-fix.js` | 2 | Comment: "Filament Client Panel" | layout |
| `resources/js/admin-mobile-sidebar-fix.js` | 2 | Comment: "Filament Admin Panel" | layout |
| `resources/js/filament/rich-content-plugins/*.js` | multiple | Filament RichEditor plugins | layout |
| `resources/js/filament/grapesjs-editor.js` | 2, 117, 512 | Filament v4 integration | layout |
| `resources/views/pages/contact.blade.php` | 74 | Text: "Filament Shield" | view |
| `resources/views/components/user-avatar-menu.blade.php` | 13 | **getFilamentAvatarUrl()** on User | blade (component) |
| `resources/views/components/admin-navigation-enhancement.blade.php` | 2 | Comment: "Filament Admin Panel" | blade |
| **`bootstrap/providers.php`** | **5** | **App\Providers\Filament\AdminPanelProvider::class** | **service container** |

---

### 2. support-chatbot / Chatbot

| File | Line | Usage | Referenced in |
|------|------|--------|----------------|
| `storage/framework/views/*.php` | 126 | Compiled blade for component | (generated) |
| **`resources/views/layouts/app.blade.php`** | **101–102** | **`<livewire:support-chatbot />`** | **layout (admin)** |
| **`resources/views/layouts/client.blade.php`** | **95–96** | **`<livewire:support-chatbot />`** | **layout** |
| **`resources/views/layouts/public.blade.php`** | **91–92** | **`<livewire:support-chatbot />`** | **layout** |

No `SupportChatbot` or `support-chatbot` Livewire class found under `app/Livewire`. No Chatbot component in vendor search. The three layout references to `<livewire:support-chatbot />` will resolve to a Livewire component name `support-chatbot`; if no such class is registered, rendering those layouts can throw. **Removing the tag from the three layouts is safe** and recommended (removes a likely missing component).

---

### 3. MenuService / SiteSettings

| File | Line | Usage | Referenced in |
|------|------|--------|----------------|
| **`app/Services/MenuService.php`** | 7 | Class definition | — |
| **`app/Settings/SiteSettings.php`** | 5 | Class definition | — |
| `app/Services/MenuService.php` | 18 | Static menu item "eSIM" in default menu | (navbar no longer uses it) |
| `resources/views/public/legal/simple.blade.php` | 4 | **app(SiteSettings::class)** | blade |
| `resources/views/public/legal/layout.blade.php` | 4 | **app(SiteSettings::class)** | blade |
| `resources/views/public/contact.blade.php` | 7–8 | **SiteSettings** | blade |
| `resources/views/public/components/footer.blade.php` | 2–3 | **SiteSettings** | blade |
| `resources/views/public/HomePage-BladeWind.blade.php` | 24 | Comment: "Dynamic from SiteSettings" | blade |
| `resources/views/cellular-orders/success.blade.php` | 311 | **app(SiteSettings::class)** | blade |
| `resources/views/domain/purchase/form.blade.php` | 2 | **app(SiteSettings::class)** | blade |
| `resources/views/auth/login.blade.php` | 7–8 | **SiteSettings** (root auth view) | blade |
| `resources/views/components/navigation-topbar-OLD-BACKUP.blade.php` | 6–7 | SiteSettings | blade (backup) |
| `resources/views/components/footer.blade.php` | 2–3 | SiteSettings | blade |
| `resources/views/components/navigation-topbar.blade.php` | 4–5 | SiteSettings | blade |
| `resources/views/layouts/public.blade.php` | 19–20 | SiteSettings | layout |

**MenuService:** Only referenced in its own class file. **Not** referenced in routes, layouts, or the current navbar (product-scope navbar has no MenuService).

**SiteSettings:** Referenced in multiple **public** and **auth** views and one **footer** component. Used for company/site config (logo, name). Removing would break those views unless replaced with `config('app.name')` and static logo.

---

### 4. eSIM (routes, views, content)

| File | Line | Usage | Referenced in |
|------|------|--------|----------------|
| `resources/views/components/dynamic-navbar.blade.php` | 1 | Comment: "no eSIM" | blade (current product navbar) |
| `app/Services/MenuService.php` | 18 | Default menu item "eSIM" | MenuService (unused by navbar) |
| **`routes/web.php`** | **57–63** | **Route::prefix('esim')** (esim.index, esim.packages, esim.checkout) | **routes** |
| `resources/views/public/services-flowbite.blade.php` | 28–416 | eSIM service card, links to /esim/packages | view |
| `resources/views/public/packages-index-flowbite.blade.php` | 4–456 | eSIM packages page content | view |
| `resources/views/public/legal/*.php` | multiple | eSIM in terms/refund/privacy | view |
| `resources/views/public/order/cancel.blade.php` | 35 | route('esim.index') | view |
| `resources/views/public/home-enhanced.blade.php` | 8–203 | eSIM meta, URLs, FAQ | view |
| `resources/views/public/layouts/app.blade.php` | 14–15, 206–262 | eSIM meta, nav link, JSON-LD | layout |
| `resources/views/public/home-bladewind.blade.php` | 167–771 | eSIM content, routes | view |
| `resources/views/public/esim-checkout.blade.php` | 10–1221 | eSIM checkout form | view |
| `resources/views/public/contact-flowbite.blade.php` | 135, 252 | eSIM option/link | view |
| `resources/views/public/about-flowbite.blade.php` | 12, 45 | eSIM text | view |
| `resources/views/public/components/*.blade.php` | multiple | eSIM in CTA, steps, pricing, FAQ, footer, hero, navbar | views |
| `resources/views/public/HomePage-BladeWind.blade.php` | 395 | eSIM product text | view |
| `resources/views/public/HomePage-NMDigital.blade.php` | 760–897 | eSIM section, routes | view |
| `resources/lang/vendor/officeguy/*/public.php` | multiple | eSIM strings | vendor lang |

**Conclusion:** eSIM is present in **routes** and many **public** views/layouts. Removing requires route removal and view/layout edits; not used by **admin** product surface (dashboard, organizations, events).

---

### 5. Service (model) / Product / Category / Page / Post / CMS / Blog

| File | Line | Usage | Referenced in |
|------|------|--------|----------------|
| **`app/Models/Service.php`** | 8 | **class Service** (name, description, duration_minutes, price) | model |
| `app/Models/Appointment.php` | 12 | **service_id** FK | model |
| `app/Livewire/Appointments/ServiceSelection.php` | 19, 26–29 | **service_id** | Livewire (workflow) |
| `app/Livewire/Appointments/ConfirmationStep.php` | 23, 53, 86–87 | **service_id**, **Service::find()** | Livewire (workflow) |
| `app/Guards/Appointments/ServiceNotSelectedGuard.php` | 11–12, 27 | **service_id** in session | guard |
| `app/Guards/Appointments/TimeSlotNotConfirmedGuard.php` | 32–44 | **service_id** in query | guard |
| `app/Livewire/Checkout/CartReview.php` | 90 | **addDemoProducts()** (word "Products") | Livewire (checkout demo) |
| `docs/product-scope-out-of-scope-models.md` | 9 | Service listed as out-of-scope | doc |

**Product / Category / Page / Post / CMS / Blog:** No **app/** model or controller matches (Product, Category, Page, Post, CMS, Blog) in this codebase; only "Product" appears in method name and docs. No dedicated CMS/Blog models found under `app/`.

---

## PHASE 2 — Classification

### Filament / PanelProvider / getFilamentName / getFilamentAvatarUrl

| Item | Classification | Reason |
|------|----------------|--------|
| **AdminPanelProvider** in `bootstrap/providers.php` | **LEGACY & UNUSED** (for product UI) | Product surface does not use Filament; admin is Blade/controllers. Unregistering stops Filament from loading; verify no other code depends on Filament. |
| **getFilamentAvatarUrl** in `user-avatar-menu.blade.php` | **BREAKING IF REMOVED** (without fix) | Direct call on User; User may not have that method. Replace with `auth()->user()->name` and optional avatar URL or remove component usage. |
| Filament in **vendor**, **public/js**, **config**, **comments** | **LEGACY BUT SAFE** | Comments and vendor/cache/config; no removal needed for product surface. Can be cleaned in a later phase. |

---

### support-chatbot

| Item | Classification | Reason |
|------|----------------|--------|
| **livewire:support-chatbot** in **app.blade.php**, **client.blade.php**, **public.blade.php** | **LEGACY BUT SAFE** or **BREAKING IF REMOVED** | If the Livewire component exists (app or package), removing the tag removes the widget. If the component does **not** exist, the tag may already cause a runtime error; then removing it is **safe** and fixes the error. Verify whether `SupportChatbot` or equivalent exists before deleting. |

---

### MenuService / SiteSettings

| Item | Classification | Reason |
|------|----------------|--------|
| **MenuService** | **LEGACY & UNUSED** (for product UI) | Not referenced in routes, current navbar, or admin layouts. Only self-reference. Safe to delete **after** confirming no other view or route uses it. |
| **SiteSettings** | **LEGACY BUT SAFE** (or **BREAKING IF REMOVED** if deleted) | Used in **public** and **auth** views (legal, contact, footer, login, navigation-topbar, public layout). Replacing with `config('app.name')` and static logo in each view would allow removal. Until then, keep or document for future phase. |

---

### eSIM (routes + views)

| Item | Classification | Reason |
|------|----------------|--------|
| **routes/web.php** esim prefix | **LEGACY BUT SAFE** (for Event SaaS) | Not used by admin/dashboard/events. Removing would 404 existing eSIM URLs; document or remove in a dedicated eSIM cleanup. |
| **Public views** (esim, packages, home, legal, etc.) | **LEGACY BUT SAFE** | Marketing/content; not part of Event SaaS product surface. Remove in content cleanup phase if desired. |

---

### Service model (App\Models\Service)

| Item | Classification | Reason |
|------|----------------|--------|
| **Service** model | **LEGACY BUT SAFE** (for Event SaaS) | Used by **appointment workflow** (book-appointment, ServiceSelection, ConfirmationStep, Guards). Not used by Events/Guests/RSVP. Deleting would break appointment booking; keep or document for separate workflow removal. |
| **Appointment**, **ServiceSelection**, **ServiceNotSelectedGuard**, **TimeSlotNotConfirmedGuard** | **LEGACY BUT SAFE** | Part of same workflow; do not delete without a dedicated workflow cleanup. |

---

### Product / Category / Page / Post / CMS / Blog

| Item | Classification | Reason |
|------|----------------|--------|
| **Product / Category / Page / Post / CMS / Blog** (as models) | **N/A** | No such models or core usage found in **app/**. Nothing to delete. |

---

## PHASE 3 — Remove Only Confirmed Unused (Deferred)

**No deletions in this audit.** Before any deletion:

- Run dependency check (routes, layouts, controllers, blade, config, composer).
- Remove in small batches; after each: `php artisan optimize:clear`, `composer dump-autoload`.
- Manually verify: `/login`, `/dashboard`, `/dashboard/organizations/create`.

**Candidates for future removal (after verification):**

1. **MenuService** — no references in product surface.
2. **livewire:support-chatbot** — only if component is missing or confirmed unused.
3. **AdminPanelProvider** — only after confirming no code resolves Filament panels or Filament-specific APIs.

**Do not remove without verification:**

- SiteSettings (used in multiple views).
- Service model and appointment workflow (used by book-appointment).
- eSIM routes and views (until explicit eSIM removal phase).

---

## PHASE 4 — Layout Cleanup (Deferred)

- **support-chatbot:** Remove from layouts only after confirming component exists or that removal does not cause errors.
- **Marketing footer / CMS blocks:** Audit layouts for explicit “marketing” or “CMS” partials; remove only when confirmed unused.

---

## PHASE 5 — Domain Validation

**Authoritative domains:** Auth, Organizations, Events, Guests, Tables, Invitations, RSVP, Billing, Payments, Admin Dashboard, Public Event Page.

Everything else (Filament admin UI, eSIM, appointment workflow, SiteSettings in public views, support-chatbot) is either:

- **To be removed** in a later, verified phase, or  
- **Explicitly documented** in this audit for future phase.

---

**Document version:** 1.0 — Read-only audit, no deletions.  
**Last updated:** Controlled Legacy Cleanup Directive.
