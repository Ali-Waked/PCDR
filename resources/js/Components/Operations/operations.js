/**
 * Small helpers shared by the operational pages. No UI kit or extra packages.
 */

export function humanize(value) {
    return String(value ?? '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) => character.toUpperCase())
}

export function operationRoute(name, params = {}, locale = 'ar') {
    const args = { ...params }
    const activeLocale = args.locale || locale || 'ar'
    delete args.locale
    const identifier = args.record ?? args.id ?? args.movement ?? args.report ?? args.document ?? args.key

    // Use the application's named-route generator when one is available.
    if (typeof globalThis.route === 'function') {
        try {
            return globalThis.route(name, { locale: activeLocale, ...args })
        } catch (error) {
            // Fall back to the predictable operational URL layout below.
        }
    }

    const base = `/${activeLocale}/operations`

    if (name === 'operations.dashboard') {
        return `${base}/dashboard`
    }
    if (name === 'operations.inventory') {
        return `${base}/inventory`
    }
    if (name === 'operations.reports') {
        return `${base}/reports`
    }
    if (name.startsWith('operations.inventory.movements.')) {
        return name.endsWith('.reverse') && identifier
            ? `${base}/inventory/movements/${identifier}/reverse`
            : `${base}/inventory/movements`
    }
    if (name.startsWith('operations.reports.')) {
        if (name.endsWith('.generate')) {
            return `${base}/reports/generate`
        }
        if (name.endsWith('.export') && identifier) {
            return `${base}/reports/${identifier}/export`
        }
        return `${base}/reports`
    }

    const match = /^operations\.([^.]+)\.(index|create|store|edit|update|destroy|transition)$/.exec(name)
    if (!match) {
        return base
    }

    const [, resource, action] = match
    const resourceBase = `${base}/${resource}`
    if (action === 'index' || action === 'store') {
        return resourceBase
    }
    if (action === 'create') {
        return `${resourceBase}/create`
    }
    if (action === 'edit') {
        return `${resourceBase}/${identifier}/edit`
    }
    if (action === 'transition') {
        return `${resourceBase}/${identifier}/status`
    }
    return `${resourceBase}/${identifier}`
}

export function ruleList(rule) {
    if (Array.isArray(rule)) {
        return rule.filter((entry) => typeof entry === 'string')
    }

    return typeof rule === 'string' ? rule.split('|') : []
}

/** Decide which input to render from a Laravel validation rule. */
export function fieldInput(name, rule) {
    const rules = ruleList(rule)
    const inRule = rules.find((entry) => entry.startsWith('in:'))
    const hint = String(name).endsWith('_media_id') ? 'Enter an existing media ID from the media library.' : ''

    if (String(name).endsWith('_media_id')) {
        return { type: 'number', step: '1', options: [], hint }
    }
    if (inRule) {
        return { type: 'select', options: inRule.slice(3).split(',').filter(Boolean), step: '', hint }
    }
    if (rules.some((entry) => entry.includes('date'))) {
        return { type: 'date', options: [], step: '', hint }
    }
    if (rules.some((entry) => entry.includes('integer'))) {
        return { type: 'number', step: '1', options: [], hint }
    }
    if (rules.some((entry) => entry.includes('numeric'))) {
        return { type: 'number', step: 'any', options: [], hint }
    }
    if (rules.some((entry) => entry.includes('email'))) {
        return { type: 'email', options: [], step: '', hint }
    }
    if (rules.some((entry) => entry.includes('url'))) {
        return { type: 'url', options: [], step: '', hint }
    }
    if (rules.some((entry) => entry.includes('boolean'))) {
        return { type: 'select', options: ['true', 'false'], step: '', hint }
    }

    return { type: 'text', options: [], step: '', hint }
}

export function formatCount(value, locale = 'ar') {
    const numeric = Number(value ?? 0)
    if (!Number.isFinite(numeric)) {
        return String(value ?? '0')
    }

    return new Intl.NumberFormat(locale === 'ar' ? 'ar-EG' : 'en-GB', { maximumFractionDigits: 3 }).format(numeric)
}
