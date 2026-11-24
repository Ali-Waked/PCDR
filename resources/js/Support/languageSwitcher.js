export const SUPPORTED_LOCALES = ['ar', 'en']

export const LANGUAGE_OPTIONS = [
    { code: 'ar', label: 'ع', ariaLabel: 'العربية', lang: 'ar' },
    { code: 'en', label: 'EN', ariaLabel: 'English', lang: 'en' },
]

export function normalizeLocale(locale) {
    const value = String(locale || '').toLowerCase()
    return SUPPORTED_LOCALES.includes(value) ? value : null
}

/** The active locale is never a navigation target. */
export function isActiveLocale(currentLocale, targetLocale) {
    return normalizeLocale(currentLocale) === normalizeLocale(targetLocale)
}

/**
 * Preserve the current localized route, query and fragment while switching
 * locale. Returning null is the explicit "do nothing" signal for an active
 * language click; callers must not visit it.
 */
export function localizedUrl(currentUrl, currentLocale, targetLocale, fallback = '/ar') {
    const target = normalizeLocale(targetLocale)
    if (target === null || isActiveLocale(currentLocale, target)) {
        return null
    }

    const raw = String(currentUrl || fallback)
    const [beforeHash, hash = ''] = raw.split('#')
    const [pathPart, query = ''] = beforeHash.split('?')
    const segments = pathPart.split('/').filter(Boolean)

    if (SUPPORTED_LOCALES.includes(segments[0])) {
        segments[0] = target
    } else {
        segments.unshift(target)
    }

    const suffix = segments.join('/')
    const querySuffix = query ? `?${query}` : ''
    const hashSuffix = hash ? `#${hash}` : ''

    return `/${suffix}${querySuffix}${hashSuffix}`
}
