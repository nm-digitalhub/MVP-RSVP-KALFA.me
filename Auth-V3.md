
‏Authentication UX Enhancement — Multilingual Login System

‏Phase 2: UX Polishing and Language-Aware Authentication Flow

‏You are tasked with performing the next phase improvement of the authentication system in a Laravel production application.

‏Phase 1 has already been completed successfully:
‏	•	Automatic Passkey prompts were removed.
‏	•	Passkey authentication now starts only after explicit user action.
‏	•	Password login and backend authentication endpoints remain unchanged.

‏Your task now is to enhance the login UX to production-grade quality, similar to identity systems such as my.gov.il, while ensuring full multilingual support across the authentication interface.

‏This phase must focus on UX clarity, internationalization (i18n), accessibility, and graceful authentication states.

⸻

‏Step 1 — Scan Current Language Infrastructure

‏Perform a repository scan to identify how localization currently works.

‏Check for:
‏	•	Laravel localization files (lang/)
‏	•	JSON translation files
‏	•	Blade @lang usage
‏	•	__() helpers
‏	•	Alpine / JS strings
‏	•	Livewire components containing text
‏	•	Hardcoded Hebrew or English strings

‏Map:
‏	1.	All authentication-related UI text
‏	2.	Passkey button text
‏	3.	Error messages
‏	4.	Success messages
‏	5.	Instructional text
‏	6.	Browser compatibility warnings
‏	7.	Passkey-specific prompts

‏Detect any hardcoded strings that bypass localization.

⸻

‏Step 2 — Normalize Authentication UI Language

‏Ensure that every visible string in the login flow is translatable.

‏This includes:
‏	•	Login page title
‏	•	Email placeholder
‏	•	Password placeholder
‏	•	Passkey button
‏	•	Error messages
‏	•	Cancelled passkey flow messages
‏	•	Unsupported browser messages
‏	•	Help text explaining passkeys

‏Replace any hardcoded strings with translation helpers:

‏Example pattern:

‏__('auth.sign_in_with_passkey')

‏Add missing translation keys where necessary.

⸻

‏Step 3 — Design Language-Aware Login UX

‏Refactor the login interface so that it clearly supports multiple languages.

‏Target structure:

‏Login Page

‏Sign In

‏[ Email + Password ]

‏or

‏[ Sign in with Passkey ]

‏Add a short explanatory line for Passkeys:

‏Example concept (language dependent):

‏“Use your device biometrics or security key to sign in securely.”

‏Ensure this text exists in all supported languages.

⸻

‏Step 4 — Improve Passkey UX Messaging

‏Handle the following cases cleanly and silently where possible:

‏User cancels biometric prompt
‏Browser does not support passkeys
‏No credential exists for the site
‏Authentication fails due to server validation

‏Requirements:

‏User cancellation must not show a scary red error.

‏Instead:
‏	•	Silent cancellation
‏	•	Neutral helper message
‏	•	Optional small hint

‏Example concept:

‏“Passkey login cancelled.”

‏Ensure this message is localized.

⸻

‏Step 5 — Mobile UX Adjustments

‏Verify behavior for:

‏Safari iOS
‏Android Chrome
‏Desktop Chrome

‏Ensure:
‏	•	Passkey button is visible and clear
‏	•	Login layout remains stable
‏	•	No layout shift when biometric prompt appears
‏	•	Touch targets are accessible

⸻

‏Step 6 — Accessibility Improvements

‏Ensure the login page meets basic accessibility guidelines:
‏	•	Buttons have accessible labels
‏	•	Inputs have labels
‏	•	Screen readers can read login methods
‏	•	Error messages are properly announced
‏	•	Focus state is preserved after passkey cancellation

⸻

‏Step 7 — Language-Aware Error Handling in JS

‏Ensure any JavaScript handling WebAuthn errors does not hardcode English text.

‏Instead:

‏Expose translations to JS via:

‏Blade data attributes
‏Laravel localization JSON
‏or a global translation object.

‏Example concept:

‏window.translations = {
‏  passkey_cancelled: "...",
‏  passkey_not_supported: "...",
};

‏Use these messages when handling WebAuthn errors.

⸻

‏Step 8 — Verification Checklist

‏Confirm the following behavior:

‏Opening the login page shows no biometric popup

‏User can login with password normally

‏User clicking the passkey button triggers WebAuthn

‏Cancelling passkey does not show a scary error

‏All UI text changes correctly when switching language

‏All authentication messages are localized

‏Safari and Chrome behave consistently

⸻

‏Step 9 — Output

‏Provide:
‏	1.	List of all authentication strings
‏	2.	New translation keys added
‏	3.	Files modified
‏	4.	UX changes made
‏	5.	Multilingual verification report

‏Do not change backend authentication logic.

‏Focus only on UX, localization, and user interaction flow.

‏The final login experience must be:

‏Clear
‏Multilingual
‏User-initiated
‏Non-intrusive
‏Accessible
