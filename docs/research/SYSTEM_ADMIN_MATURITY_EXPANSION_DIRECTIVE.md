MVP-RSVP
SYSTEM ADMIN MATURITY EXPANSION DIRECTIVE

Laravel 12 + Livewire 4
Post-Structural Expansion Phase
OfficeGuy Integration Ready

⸻

OBJECTIVE

Evolve the current System Admin layer from:

Visibility + Basic Listing

into a:

Full Global Control Layer

Without breaking:

• Tenant isolation
• Middleware separation
• Livewire full-page structure
• DB as source of truth
• No tenant logic leakage

This directive defines phased implementation in the correct architectural order.

No shortcuts. No cross-layer bleed.

⸻

IMPLEMENTATION STRATEGY

Order matters.
	1.	Audit Layer first
	2.	Drill-down structure
	3.	Mutating admin actions
	4.	Filters + search
	5.	Billing integration (OfficeGuy adapter)
	6.	Revenue metrics
	7.	Security hardening

⸻

PHASE 1 — GLOBAL AUDIT LOG LAYER (MANDATORY FIRST STEP)

Before adding destructive system actions.

Create:

database/migrations/create_system_audit_logs_table.php

Columns:
	•	id
	•	actor_id (nullable FK users)
	•	target_type (morph)
	•	target_id
	•	action (string)
	•	metadata (json nullable)
	•	ip_address
	•	user_agent
	•	created_at

Create:

App\Models\SystemAuditLog

Create:

App\Services\SystemAuditLogger

Usage pattern:
SystemAuditLogger::log(
actor: auth()->user(),
action: ‘impersonation.started’,
target: $user,
metadata: […]
);

Log events for:
	•	impersonation start
	•	impersonation end
	•	promote system admin
	•	demote system admin
	•	suspend organization
	•	activate organization
	•	force delete organization
	•	billing actions

System layer without audit = incomplete control layer.

⸻

PHASE 2 — SYSTEM ORGANIZATION DRILL-DOWN

Add route:

/system/organizations/{organization}

Livewire full-page component:

App\Livewire\System\Organizations\Show

Must display:

• Owner
• Members count
• Events list (paginated)
• Created at
• Last activity
• Billing status (placeholder until OfficeGuy ready)
• Plan name
• Subscription status

No OrganizationContext allowed.
Global queries only.

⸻

PHASE 3 — SYSTEM ORGANIZATION ADMIN ACTIONS

Add actions inside Show component:

• suspend()
• activate()
• transferOwnership(userId)
• forceDelete()
• resetData() (danger zone, guarded)

Each action must:
	1.	Require password confirmation (use password.confirm middleware or manual check)
	2.	Log audit entry
	3.	Never depend on tenant middleware

Add boolean column:

organizations.is_suspended (default false, indexed)

Tenant middleware must later respect suspension.

⸻

PHASE 4 — SYSTEM USERS EXPANSION

Add route:

/system/users/{user}

Component:

App\Livewire\System\Users\Show

Display:

• Organizations
• Owned organizations
• Events created
• Registration date
• Last login (add last_login_at column if missing)
• System admin status
• Billing impact (via orgs)

Add actions:

• promoteToSystemAdmin()
• demoteSystemAdmin()
• disableUser()
• forcePasswordReset()
• invalidateSessions()

All actions must:
	•	Log audit
	•	Confirm destructive actions
	•	Not rely on tenant logic

⸻

PHASE 5 — FILTERING + SEARCH (PERFORMANCE SAFE)

Add to:

System\Users\Index
System\Organizations\Index

Capabilities:

Users:
• filter system_admin
• filter no_organization
• filter recent (7/30 days)
• filter suspended

Organizations:
• filter suspended
• filter no_events
• filter no_users
• filter billing_status
• search by name
• search by owner email

Use:
->when()
->withCount()
->paginate()

No N+1 queries allowed.

⸻

PHASE 6 — OFFICEGUY ADAPTER IMPLEMENTATION

Convert:

App\Services\OfficeGuy\SystemBillingService

From placeholder → real adapter.

Responsibilities:

• getOrganizationSubscription($organization)
• cancelSubscription($organization)
• extendTrial($organization, days)
• applyCredit($organization, amount)
• retryPayment($organization)
• getMRR()
• getChurnRate()
• getActiveSubscriptions()

System layer must never directly call OfficeGuy SDK.
Only through this service.

Adapter pattern required.

⸻

PHASE 7 — BILLING METRICS ON SYSTEM DASHBOARD

Expand:

App\Livewire\System\Dashboard

Add:

KPIs:
• Total users
• Total organizations
• Active organizations
• New users (7/30)
• New orgs (7/30)
• Events total
• Events 30d
• RSVP conversion

Health:
• Users without org
• Orgs without events
• Orgs without owner
• System admins count
• Suspended org count

Billing:
• MRR
• Active subscriptions
• Trialing
• Past due
• Cancelled
• Churn

Use aggregate queries only.
No heavy eager loading.

⸻

PHASE 8 — SECURITY HARDENING

Add:
	1.	Require password confirmation for:
	•	destructive org actions
	•	promote/demote admin
	•	billing cancel
	2.	Add impersonation auto-expiry:
	•	store impersonation_started_at
	•	expire after X minutes
	3.	Log impersonation duration in audit.
	4.	Optional:
	•	enforce 2FA for system admins (future)

⸻

STRUCTURAL RULES (NON-NEGOTIABLE)

• System routes remain under prefix(‘system’)
• Middleware: [‘auth’, ‘verified’, ‘system.admin’]
• Never apply ensure.organization to system routes
• Never use OrganizationContext in System layer
• All billing access through SystemBillingService
• All destructive actions logged
• Livewire full-page components only
• Layout remains layouts.app

⸻

MATURITY TARGET

After completion:

Layer	Status
Structural separation	✔
Visibility	✔
Administrative control	✔
Audit	✔
Billing integration	✔
Revenue metrics	✔
Security hardening	✔

Only then is System Admin considered production-grade SaaS control layer.

⸻

If approved, next step is:

PHASE 1 — Global Audit Layer implementation.