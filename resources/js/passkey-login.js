/**
 * Passkey (WebAuthn) login handler — loaded only on the login page via Vite.
 */
import Webpass from '@laragear/webpass'

/**
 * Returns true when the error should be handled silently (no red banner).
 *
 * Accepts either a thrown Error object or a string returned by Webpass
 * as { success: false, error: '...' } — Webpass does not always throw on
 * cancellation; it may return the cancel signal as a string instead.
 *
 * - AbortError      → clean user cancel or programmatic abort
 * - NotAllowedError → user cancel, timeout, security policy, missing gesture
 *                     (covers all iOS/WebKit triage cases)
 *
 * Fragment fallback handles the small subset of WebAuthn implementations
 * that surface errors only through `message` / the Webpass return string.
 * Fragments are kept deliberately narrow to avoid masking real failures.
 */
function isSilentCancel(error) {
    const name    = typeof error === 'string' ? '' : (error?.name ?? '')
    const message = typeof error === 'string'
        ? error.toLowerCase()
        : (error?.message ?? '').toLowerCase()

    if (name === 'AbortError' || name === 'NotAllowedError') {
        return true
    }

    return message.includes('assertioncancelled') || message.includes('not completed')
}

/**
 * Attempt a Webpass assertion, hide the button while in-flight, and handle
 * cancels silently. Returns true on success (caller should redirect).
 */
async function attemptAssertion(btn, errorEl, opts = {}) {
    btn.disabled = true
    errorEl.classList.add('hidden')

    const failedMsg = btn.dataset.msgFailed ?? 'Passkey login failed. Please try again.'
    const errorMsg  = btn.dataset.msgError  ?? 'Authentication error — please try again.'

    try {
        const { success, error } = await Webpass.assert(
            '/webauthn/login/options',
            '/webauthn/login',
            opts
        )

        if (success) return true

        // Webpass returns cancellation as { success: false, error: '...' } rather
        // than throwing — check the string before showing a red banner.
        if (error && isSilentCancel(error)) {
            console.warn('[passkey] silent cancel (response)', error)
            return false
        }

        errorEl.textContent = error ?? failedMsg
        errorEl.classList.remove('hidden')
    } catch (e) {
        // Session expired → server returns 419. Reload to obtain a fresh CSRF token
        // so the next login attempt works without requiring a manual page refresh.
        if (e?.response?.status === 419) {
            window.location.reload()
            return false
        }

        if (isSilentCancel(e)) {
            console.warn('[passkey] silent cancel', { name: e.name, message: e.message })
        } else {
            errorEl.textContent = errorMsg
            errorEl.classList.remove('hidden')
        }
    } finally {
        btn.disabled = false
        btn.focus()
    }

    return false
}

document.addEventListener('DOMContentLoaded', () => {
    const passkeyBtn = document.getElementById('passkey-login-btn')
    const passkeyError = document.getElementById('passkey-error')

    if (!passkeyBtn) return

    if (Webpass.isUnsupported()) {
        passkeyBtn.style.display = 'none'
        return
    }

    const redirect = passkeyBtn.dataset.redirect ?? '/dashboard'

    // ── Button click ─────────────────────────────────────────────────────────
    passkeyBtn.addEventListener('click', async () => {
        const email = document.getElementById('email')?.value.trim() || undefined
        const success = await attemptAssertion(passkeyBtn, passkeyError, email ? { email } : {})
        if (success) window.location.href = redirect
    })
})
