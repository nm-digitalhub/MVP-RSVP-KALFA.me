
MVP-RSVP

SYSTEM ADMIN PHASE 1 — GLOBAL CONTROL PANEL DIRECTIVE

Laravel 12 + Livewire 4
Deterministic SaaS Architecture
OfficeGuy Integration Awareness

⸻

OBJECTIVE

Build the first operational layer of the System Admin panel.

Scope:
	1.	Global System Dashboard (real metrics)
	2.	Global Organizations Management
	3.	Global Users Management
	4.	Basic Impersonation
	5.	Structural readiness for OfficeGuy integration

No tenant coupling.
No duplication of tenant logic.
No global scopes leakage.

⸻

ARCHITECTURAL RULES
	1.	System Layer must never depend on ensure.organization.
	2.	System queries must ignore tenant scoping.
	3.	No reuse of tenant Livewire components.
	4.	System components must live under:
App\Livewire\System*
	5.	OfficeGuy logic must remain isolated and service-based (never inline).

⸻

PHASE 1 — System Dashboard (Livewire)

Create component:

php artisan make:livewire System/Dashboard

Class:
App\Livewire\System\Dashboard

View:
resources/views/livewire/system/dashboard.blade.php

⸻

Metrics to compute in render():

Use raw global queries (no tenant filters).

Example:

$totalOrganizations = Organization::count();
$totalUsers = User::count();
$totalEvents = Event::count();
$totalGuests = Guest::count();

$recentOrganizations = Organization::latest()->limit(5)->get();
$recentUsers = User::latest()->limit(5)->get();

DO NOT use:
	•	currentOrganization()
	•	OrganizationContext
	•	where(‘organization_id’, …)

This is a global overview.

⸻

Dashboard UI should contain:
	•	4 metric cards
	•	Recent organizations list
	•	Recent users list
	•	Placeholder block for OfficeGuy status

Add:

OfficeGuy Health Status (placeholder)
→ This prepares for integration later.

⸻

PHASE 2 — System Organizations Index

Create:

php artisan make:livewire System/Organizations/Index

Purpose:

Global organizations table.

Query:

$organizations = Organization::withCount([
‘users’,
‘events’
])->latest()->paginate(15);

Display:
	•	Name
	•	Owner (resolve via relationship)
	•	Users count
	•	Events count
	•	Created at
	•	Status (active/suspended placeholder)
	•	Action buttons

Add Action:

Impersonate

Button:

POST /system/impersonate/{organization}

⸻

PHASE 3 — System Users Index

Create:

php artisan make:livewire System/Users/Index

Query:

$users = User::withCount(‘organizations’)
->latest()
->paginate(20);

Columns:
	•	Name
	•	Email
	•	is_system_admin
	•	Organizations count
	•	Created at

Add toggle:

Promote / Demote System Admin

Method:

public function toggleAdmin($userId)

Never allow:

System Admin to demote themselves accidentally (guard it).

⸻

PHASE 4 — Impersonation (Basic)

Add route:

POST /system/impersonate/{organization}

Controller:

SystemImpersonationController

Logic:
	1.	Store original_admin_id in session
	2.	Set current_organization_id to target org
	3.	Redirect to tenant dashboard

Add route:

POST /system/impersonation/exit

Restore original context.

Never modify is_system_admin during impersonation.

⸻

PHASE 5 — OfficeGuy Integration Awareness

OfficeGuy introduces:
	•	officeguy_documents
	•	subscriptions
	•	billing logic
	•	background processes
	•	DB index checks (like the SHOW INDEX error you saw earlier)

System Admin must have:

Future module:

System → Billing

Where you will:
	•	View all subscriptions
	•	View OfficeGuy documents
	•	Monitor subscription_id relationships
	•	See system-level billing status

IMPORTANT:

All OfficeGuy queries must:
	1.	Be isolated in a Service class
	2.	Not mix with tenant logic
	3.	Not rely on SQLite-specific SQL in tests

Create placeholder service:

app/Services/OfficeGuy/SystemBillingService.php

Even if empty now.

This ensures:

Clear separation between:
Core SaaS logic
Billing/OfficeGuy logic

⸻

PHASE 6 — Route Structure

Inside:

Route::prefix(‘system’)
->middleware([‘auth’,‘verified’,‘system.admin’])

Mount Livewire routes:

Route::get(’/dashboard’, SystemDashboard::class)->name(‘system.dashboard’);
Route::get(’/organizations’, SystemOrganizationsIndex::class)->name(‘system.organizations.index’);
Route::get(’/users’, SystemUsersIndex::class)->name(‘system.users.index’);

⸻

PHASE 7 — Verification Checklist

Confirm:
	1.	No ensure.organization on system routes
	2.	System Livewire components do not reference OrganizationContext
	3.	No tenant scoping in queries
	4.	System admin can impersonate
	5.	Regular user gets 403 on /system/*
	6.	Dashboard shows real metrics

⸻

EXPECTED RESULT

You now have:

Layer 1 — Public
Layer 2 — Authenticated Tenant
Layer 3 — System Admin (Global Control Panel)
Layer 4 — Future Billing (OfficeGuy)

Architecture becomes:

Core SaaS
	•	Global Governance
	•	Isolated Billing Domain

No drift.
No duplicated logic.
No cross-layer leakage.
