You are a senior Laravel authentication, WebAuthn, frontend UX, and production refactoring engineer.

Your task is to perform a FULL repository-wide audit of the current authentication flow in a production Laravel application and then implement a SAFE refactor of the login UX so it behaves like the Israeli government identity system at my.gov.il:

Core UX principle:
- Authentication must NEVER start automatically on page load.
- The user must FIRST choose an authentication method.
- Only AFTER explicit user interaction may WebAuthn / Passkey authentication begin.

The current problem:
- When the login page loads, a biometric / passkey popup appears immediately.
- This is poor UX, especially on iPhone / Safari and mobile browsers.
- We want to refactor the system so it follows the my.gov.il-style interaction model:
  Page loads normally -> user sees available sign-in methods -> user clicks "Sign in with Passkey" -> only then the passkey flow begins.

Important constraints:
- Preserve all existing backend auth behavior.
- Do not break password login.
- Do not break passkey registration.
- Do not break passkey login.
- Do not break Laravel sessions, redirects, or intended redirects.
- Do not break Safari / iOS support.
- Do not break existing WebAuthn backend endpoints.
- Do not modify unrelated business logic.
- Keep the refactor minimal, safe, and production-ready.

==================================================
STEP 1 — FULL AUTHENTICATION ARCHITECTURE SCAN
==================================================

Perform a complete scan of the repository and identify ALL files, routes, controllers, views, scripts, components, and dependencies related to authentication and passkeys.

Search for and inspect all occurrences of:
- WebAuthn
- Passkey
- Webpass
- laragear/webauthn
- laragear/webpass
- navigator.credentials.get
- navigator.credentials.create
- PublicKeyCredential
- isConditionalMediationAvailable
- mediation: 'conditional'
- mediation: "conditional"
- DOMContentLoaded
- load event listeners
- login forms
- passkey buttons
- password login
- redirect()->intended
- session flash related to auth
- Breeze auth flow
- auth middleware
- guest middleware

You must inspect at minimum:
- resources/js/app.js
- resources/js/passkey-login.js
- resources/js/bootstrap.js
- all auth Blade views
- any Livewire components related to login/profile/passkeys
- routes/web.php
- route files related to auth
- WebAuthnLoginController
- WebAuthnRegisterController
- LoginController
- any custom auth controllers
- layout files that may inject passkey scripts
- Vite entry points
- package.json
- composer.json

Output a complete map of:
1. All authentication-related entry points
2. Where passkey code is initialized
3. Where passkey code is triggered
4. Which scripts run on page load
5. Which UI elements are involved
6. Which backend endpoints are used
7. Which redirects happen after success/failure
8. Which flows are password-based vs passkey-based
9. Any mobile/browser-specific logic already present

==================================================
STEP 2 — DETECT CURRENT UX ANTI-PATTERNS
==================================================

Identify every code path that causes authentication or passkey prompts to start automatically.

Flag and explain all anti-patterns, including but not limited to:
- Webpass.assert() called during page load
- navigator.credentials.get() called automatically
- conditional mediation triggered automatically
- passkey flow started without explicit user action
- autofill / conditional UI implemented in a way that causes intrusive popups
- duplicated initialization across multiple JS files
- scripts attached globally when they should be scoped to login only

For each anti-pattern, explain:
- exact file
- exact function / line area
- why it causes poor UX
- why it differs from the my.gov.il behavior
- risk if left unchanged

==================================================
STEP 3 — ANALYZE MY.GOV.IL-STYLE UX TARGET
==================================================

Model the target UX after the Israeli government login pattern:

Desired behavior:
- Login page loads quietly
- No biometric or passkey prompt appears automatically
- User sees available login methods clearly
- User explicitly chooses the login method
- Only after clicking the passkey option should the passkey flow begin

Target structure:
- Email + Password login form
- Passkey login button
- Optional explanatory text for passkey
- No automatic popup
- No automatic modal
- No silent credential request on page load

Create a "current UX vs target UX" comparison.

==================================================
STEP 4 — IMPLEMENT SAFE REFACTOR
==================================================

Refactor the current login flow so that passkey authentication is triggered ONLY after explicit user action.

Required implementation behavior:
- Remove any automatic passkey initialization that starts the auth request on page load
- Remove any automatic Webpass.assert() execution
- Remove or disable any page-load conditional mediation that opens prompts immediately
- Keep passkey login available through a clear button
- Trigger passkey auth only on click / tap
- Keep password login fully unchanged

Do NOT change backend endpoint contracts unless absolutely necessary.

Preserve these endpoints:
- /webauthn/login/options
- /webauthn/login
- /webauthn/register/options
- /webauthn/register

If conditional mediation is present, evaluate whether it should be:
- removed completely, or
- gated behind safer conditions
But the final UX must not produce an intrusive popup on page load.

==================================================
STEP 5 — HARDEN THE UX
==================================================

Improve the login page so it feels deliberate and clear.

Required UX goals:
- Standard password login remains the primary flow
- Passkey is offered as a secondary explicit option
- Error messages for passkey failures are user-friendly
- User cancellation should not feel like a hard error
- Silent cancellation should not spam scary red messages
- Mobile Safari experience must be smooth

If the current implementation shows raw errors like:
- AssertionCancelled
- The credentials request was not completed
replace them with proper UX handling.

Distinguish between:
- user cancelled
- no credential available
- unsupported browser
- real server failure

==================================================
STEP 6 — KEEP BACKWARD COMPATIBILITY
==================================================

Ensure the refactor does not break:
- password login
- remember me
- forgot password
- email verification
- session regeneration
- redirect()->intended()
- passkey registration
- passkey login
- profile passkey management
- Safari/iOS compatibility
- current Vite asset loading

If any code depends on:
- session flash
- auth guards
- middleware
- Laravel Breeze conventions
document it and preserve it.

==================================================
STEP 7 — VERIFY BROWSER / DEVICE BEHAVIOR
==================================================

After implementation, verify behavior for:
- iPhone Safari
- Desktop Chrome
- Android Chrome

Explicitly confirm:
1. Opening the login page does NOT show a biometric/passkey popup
2. Clicking the passkey button DOES start the passkey flow
3. Password login still works
4. Redirects still work
5. No broken console errors exist
6. User cancellation is handled gracefully
7. No intrusive modal appears without user action

==================================================
STEP 8 — OUTPUT FORMAT
==================================================

Provide the result in this exact structure:

1. Executive summary
2. Current authentication architecture map
3. Current passkey trigger map
4. UX anti-pattern findings
5. Target my.gov.il-style UX design
6. Exact files to change
7. Code changes made
8. Why each change is safe
9. Verification checklist
10. Residual risks / follow-up recommendations

==================================================
IMPORTANT IMPLEMENTATION RULES
==================================================

- Do not make assumptions without scanning the codebase
- Do not skip files
- Do not provide a generic answer
- Be repository-specific
- Minimize code changes
- Optimize for zero regression
- Do not rewrite the whole auth system
- Only refactor what is necessary to align with the target UX
- Keep the backend stable
- Focus on the real issue: page-load-triggered passkey UX

Final rule:
The correct authentication flow must always be:

User action -> authentication starts

and never:

Page load -> authentication popup