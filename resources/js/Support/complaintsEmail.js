/**
 * Copy helper for the public complaints channel.
 *
 * The page never owns the address; callers pass the backend-provided value.
 * Clipboard API failures fall back to a hidden textarea so the action still
 * works in browsers without the async clipboard permission.
 *
 * @param {unknown} value
 * @param {{ navigator?: any, document?: any }} [environment]
 * @returns {Promise<{ ok: boolean, method?: 'clipboard' | 'fallback', reason?: string }>}
 */
export async function copyComplaintsEmail(value, environment = {}) {
    const text = String(value ?? '').trim()
    if (text === '') {
        return { ok: false, reason: 'empty' }
    }

    const nav = environment.navigator
        ?? (typeof navigator !== 'undefined' ? navigator : null)
    const doc = environment.document
        ?? (typeof document !== 'undefined' ? document : null)

    if (nav?.clipboard?.writeText) {
        try {
            await nav.clipboard.writeText(text)

            return { ok: true, method: 'clipboard' }
        } catch (_) {
            // Fall through to the executable-copy fallback below.
        }
    }

    if (! doc?.body || typeof doc.createElement !== 'function' || typeof doc.execCommand !== 'function') {
        return { ok: false, reason: 'unavailable' }
    }

    const input = doc.createElement('textarea')
    input.value = text
    input.setAttribute('readonly', '')
    input.setAttribute('aria-hidden', 'true')
    input.style.position = 'fixed'
    input.style.top = '0'
    input.style.left = '-9999px'
    input.style.opacity = '0'
    doc.body.appendChild(input)

    try {
        input.select?.()
        input.setSelectionRange?.(0, text.length)
        const copied = doc.execCommand('copy') === true

        return copied
            ? { ok: true, method: 'fallback' }
            : { ok: false, reason: 'blocked' }
    } catch (_) {
        return { ok: false, reason: 'blocked' }
    } finally {
        input.remove?.()
    }
}

/** @param {unknown} value */
export function complaintsMailto(value) {
    const email = String(value ?? '').trim()

    return email === '' ? '' : `mailto:${email}`
}
