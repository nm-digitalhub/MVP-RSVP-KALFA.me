RIGOROUS CODEBASE VERIFICATION & PRODUCT FEATURE DIRECTIVE

Purpose
Verify the accuracy and completeness of CLAUDE.md and expand it into a feature specification tailored to an RSVP + seating SaaS application.

This must result in:
	•	A validated architectural document
	•	Clear feature requirements
	•	A prioritized implementation backlog (PR-sized)
	•	Evidence-backed architectural claims

Hard rules:
	•	No assumptions. Every claim must be backed by code evidence or explicit web research citations.
	•	During Phase 1 (verification): NO CODE CHANGES. Audit only.
	•	Before referencing any file, print @<path> and confirm it was read.

Inputs
	•	Primary doc: @CLAUDE.md
	•	Codebase scope: entire repository (app/, routes/, resources/, database/, tests/)

SECTION A — PHASE 1: CLAUDE.md VERIFICATION (NO CODE CHANGES)

A1. Establish Evidence Mapping
	1.	Read @CLAUDE.md fully. Extract every factual claim (architecture, file locations, features, middleware, policies, billing, etc.).
	2.	For each claim, produce a “Claim → Evidence” table with columns:

	•	Claim (verbatim)
	•	Evidence type: Code / Config / Migration / Test
	•	Evidence file(s) (must be prefixed with @)
	•	Exact location (function name + line span, or grep pattern)
	•	Status: VERIFIED / PARTIAL / INVALID / UNVERIFIED
	•	Notes (what to fix in CLAUDE.md if not verified)

Example row:
Claim: “Multi-tenant context is enforced via OrganizationContext”
Evidence File: @app/Services/OrganizationContext.php
Location: OrganizationContext::current() lines X–Y
Status: VERIFIED
	3.	If a claim cannot be verified, mark it UNVERIFIED and produce a “Required Evidence Step”:

	•	exactly which file(s) must be checked next
	•	what to search for
	•	what would count as proof

A2. Mandatory Read List (minimum set)
Read and cite at least the following, using the @path format before each file:

Bootstrapping & middleware:
	•	@bootstrap/app.php

Routes:
	•	@routes/web.php
	•	@routes/auth.php
	•	@routes/api.php

Tenant:
	•	@app/Services/OrganizationContext.php
	•	@app/Http/Middleware/EnsureOrganizationSelected.php
	•	@app/Models/User.php
	•	@app/Models/Organization.php
	•	@app/Livewire/Dashboard.php
	•	@app/Livewire/Organizations/Index.php

System Admin:
	•	@app/Http/Middleware/EnsureSystemAdmin.php
	•	@app/Http/Middleware/ImpersonationExpiry.php
	•	@app/Services/SystemAuditLogger.php
	•	@app/Models/SystemAuditLog.php
	•	@app/Http/Controllers/System/SystemImpersonationController.php
	•	@app/Livewire/System/Dashboard.php
	•	@app/Livewire/System/Users/Index.php
	•	@app/Livewire/System/Users/Show.php
	•	@app/Livewire/System/Organizations/Index.php
	•	@app/Livewire/System/Organizations/Show.php

Auth:
	•	@app/Http/Controllers/Auth/LoginController.php
	•	@app/Http/Controllers/Auth/LogoutController.php

Policies:
	•	@app/Policies/OrganizationPolicy.php
	•	@app/Policies/EventPolicy.php
	•	@app/Policies/GuestPolicy.php
	•	@app/Policies/PaymentPolicy.php
	•	Additionally list all policy files found under @app/Policies and mark which are referenced in CLAUDE.md.

Navigation/layout:
	•	@resources/views/layouts/app.blade.php
	•	@resources/views/components/dynamic-navbar.blade.php

Billing:
	•	@app/Contracts/PaymentGatewayInterface.php
	•	@app/Services/BillingService.php
	•	@app/Services/SumitPaymentGateway.php
	•	@app/Services/StubPaymentGateway.php
	•	@app/Services/OfficeGuy/SystemBillingService.php
	•	@app/Http/Controllers/Api/WebhookController.php
	•	@app/Http/Requests/InitiateCheckoutRequest.php

Database integrity:
	•	Read all migrations that define organizations/events/guests/invitations/billing/payments/audit logs under @database/migrations (list them explicitly in output).

A3. Normalize Verification Criteria (Pass/Fail rules)
Define strict criteria for:
	•	Middleware registration (must be visible in @bootstrap/app.php and in route middleware stacks)
	•	Tenant source of truth (must be DB-backed; session only mirror)
	•	System isolation (system routes must not include tenant enforcement middleware)
	•	Audit coverage (all system mutating actions must log via SystemAuditLogger)
	•	Billing isolation (system billing must go only through SystemBillingService)

A4. Produce a Gap Report
Produce a gap list with:
	•	Missing / incorrect statements in CLAUDE.md (with corrections)
	•	Missing evidence for any claims (UNVERIFIED)
	•	Missing documentation for “why” decisions (e.g., RESTRICT vs CASCADE)
	•	Missing tests for critical flows (impersonation expiry, disabled login, destructive actions)

Deliverable A: “CLAUDE.md Verification Report” + a “Patch Plan” (doc-only changes to CLAUDE.md, not code)

SECTION B — PHASE 2: DOMAIN-SPECIFIC FEATURE DISCOVERY (WEB RESEARCH REQUIRED)

B1. Competitive baseline research
Research 5–8 credible sources (product pages + guides) for RSVP + guest list + seating/table planning software.
Extract common features into categories:
	•	Invitation Management
	•	Guest Tracking
	•	Message Automation
	•	Seating & Table Planning
	•	Analytics & Reporting
	•	Multi-Event Support
	•	Billing & Subscription (SaaS)
	•	Admin/Audit/Security

Hard rules:
	•	Provide citations/links for each feature cluster.
	•	Do not copy long text; summarize.

Deliverable B: “Feature Baseline Matrix” (Feature → Source → Notes)

SECTION C — PHASE 3: PRODUCT FEATURE SPECIFICATION (NO BIG REFACTOR)

For each feature category:
	1.	Describe the feature in plain language
	2.	Map to current code capabilities (Exists / Partial / Missing)
	3.	Minimal-change implementation approach (extend existing patterns; no redesign)
	4.	Data model impact (new tables/columns? reuse existing?)
	5.	Security/authorization impact (policy/middleware/audit)
	6.	Test plan (unit + integration)

Deliverable C: “Product Spec v1” aligned to the current architecture.

SECTION D — PHASE 4: PR-SIZED IMPLEMENTATION BACKLOG (SMALL TASKS)

Create a prioritized backlog where each item is PR-sized with:
	•	Objective
	•	Files to touch (prefixed with @)
	•	Definition of Done (DoD)
	•	Tests required
	•	Risks

Example backlog item format:
	•	P1: Guest CSV Import
	•	Touch: @app/Http/Controllers/…, @app/Services/…, @resources/views/…
	•	DoD: import works, dedupe rules, audit log entry, tests pass

SECTION E — OUTPUT FORMAT (MANDATORY)

Return exactly:
	1.	Claim → Evidence Table (with VERIFIED/PARTIAL/INVALID/UNVERIFIED)
	2.	Gap Report
	3.	Feature Baseline Matrix (with citations)
	4.	Product Spec v1 (Exists/Partial/Missing mapping)
	5.	PR Backlog (prioritized, small tasks)
	6.	Test Plan summary
