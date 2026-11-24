const HTML_ENTITIES = {
    '&laquo;': '«',
    '&raquo;': '»',
    '&nbsp;': ' ',
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
}

/**
 * Convert a Laravel-style link label into plain text. Backend paginator labels
 * contain entities such as `&laquo; Previous`; the renderer supplies its own
 * icons, but the value is still useful for summaries and accessible labels.
 */
export function plainText(value) {
    return String(value ?? '')
        .replace(/<[^>]*>/g, '')
        .replace(/&(?:laquo|raquo|nbsp|amp|lt|gt|quot|#39);/g, (entity) => HTML_ENTITIES[entity] ?? entity)
        .replace(/\s+/g, ' ')
        .trim()
}

function integer(value, fallback = null) {
    if (value === null || value === undefined || value === '') {
        return fallback
    }

    const parsed = Number(value)
    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback
}

/** Format acount using the same locale convention already used by dates. */
export function formatCount(value, locale = 'en') {
    const numeric = integer(value, 0) ?? 0
    return new Intl.NumberFormat(locale === 'ar' ? 'ar-EG' : 'en-GB').format(numeric)
}

/**
 * Build a compact page list with first/last boundaries and ellipses. Ellipsis
 * entries are separate objects so the Vue renderer can mark them aria-hidden.
 */
export function buildPageItems(currentPage, lastPage) {
    const current = Math.max(1, integer(currentPage, 1) ?? 1)
    const last = Math.max(1, integer(lastPage, 1) ?? 1)

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => ({ type: 'page', number: index + 1 }))
    }

    const numbers = new Set([1, last, current - 1, current, current + 1])

    if (current <= 3) {
        [2, 3, 4].forEach((number) => numbers.add(number))
    }
    if (current >= last - 2) {
        [last - 1, last - 2, last - 3].forEach((number) => numbers.add(number))
    }

    const sorted = [...numbers]
        .filter((number) => number >= 1 && number <= last)
        .sort((left, right) => left - right)

    const items = []
    sorted.forEach((number, index) => {
        if (index > 0 && number - sorted[index - 1] > 1) {
            items.push({ type: 'ellipsis', key: `ellipsis-${sorted[index - 1]}-${number}` })
        }
        items.push({ type: 'page', number })
    })

    return items
}

/**
 * Preserve every existing query parameter, including other paginator page keys,
 * then apply the requested page. Page 1 removes only the active page key.
 */
export function buildPageUrl(currentUrl, targetPage, pageKey = 'page', extra = {}) {
    const base = typeof window === 'undefined' ? import.meta.url : window.location.href
    const target = new URL(String(currentUrl || '/'), base)
    const key = String(pageKey || 'page')

    const page = Math.max(1, integer(targetPage, 1) ?? 1)
    if (page > 1) {
        target.searchParams.set(key, String(page))
    } else {
        target.searchParams.delete(key)
    }

    Object.entries(extra).forEach(([name, value]) => {
        if (value === null || value === undefined || value === '') {
            target.searchParams.delete(name)
            return
        }
        target.searchParams.set(name, String(value))
    })

    return `${target.pathname}${target.search}${target.hash}`
}

/** Replace `:from`, `:to` and `:total` in a translated summary template. */
export function renderSummary(template, summary) {
    return String(template ?? '')
        .replaceAll(':total', formatCount(summary.total, summary.locale))
        .replaceAll(':from', formatCount(summary.from, summary.locale))
        .replaceAll(':to', formatCount(summary.to, summary.locale))
}

function linkNumber(link) {
    const direct = integer(link?.page, null)
    if (direct !== null) {
        return direct
    }

    return integer(plainText(link?.label).replace(/[^\d]/g, ''), null)
}

/**
 * Normalize every paginator contract used by PCDR into one shape:
 * full Laravel paginators, camelCase subsets, page-index maps and nested meta.
 */
export function normalizePaginator(source = {}, options = {}) {
    const input = source && typeof source === 'object' ? source : {}
    const meta = input.meta && typeof input.meta === 'object' ? input.meta : {}
    const collection = { ...meta, ...input }
    const rawLinks = input.links ?? meta.links ?? options.links ?? null

    let currentPage = integer(
        collection.current_page ?? collection.currentPage ?? options.currentPage,
        1,
    ) ?? 1
    let lastPage = integer(
        collection.last_page ?? collection.lastPage ?? options.lastPage,
        1,
    ) ?? 1
    const perPage = integer(collection.per_page ?? collection.perPage ?? options.perPage, null)
    const total = integer(collection.total ?? options.total, null)
    let from = integer(collection.from ?? options.from, null)
    let to = integer(collection.to ?? options.to, null)
    const pageKey = String(input.pageName ?? meta.pageName ?? options.pageKey ?? 'page')

    const pageUrls = new Map()
    let previousUrl = input.prev_page_url ?? meta.prev_page_url ?? null
    let nextUrl = input.next_page_url ?? meta.next_page_url ?? null

    if (Array.isArray(rawLinks)) {
        rawLinks.forEach((link) => {
            if (!link || typeof link !== 'object') {
                return
            }
            const number = linkNumber(link)
            if (number !== null && typeof link.url === 'string' && link.url !== '') {
                pageUrls.set(number, link.url)
                lastPage = Math.max(lastPage, number)
            }
            if (link.active === true && number !== null) {
                currentPage = number
            }
            const label = plainText(link.label).toLowerCase()
            if (label.includes('previous') && typeof link.url === 'string') {
                previousUrl = link.url
            }
            if (label.includes('next') && typeof link.url === 'string') {
                nextUrl = link.url
            }
        })
    } else if (rawLinks && typeof rawLinks === 'object') {
        Object.entries(rawLinks).forEach(([number, url]) => {
            const pageNumber = integer(number, null)
            if (pageNumber !== null && typeof url === 'string') {
                pageUrls.set(pageNumber, url)
                lastPage = Math.max(lastPage, pageNumber)
            }
        })
    }

    currentPage = Math.min(Math.max(1, currentPage), Math.max(1, lastPage))

    if (total !== null && from === null) {
        from = total === 0 ? 0 : Math.max(1, (currentPage - 1) * (perPage ?? 15) + 1)
    }
    if (total !== null && to === null) {
        to = perPage === null ? total : Math.min(total, currentPage * perPage)
    }

    return {
        currentPage,
        lastPage,
        perPage,
        total,
        from,
        to,
        pageKey,
        pageUrls,
        previousUrl,
        nextUrl,
        hasPages: lastPage > 1,
    }
}

/** Resolve a page URL from backend links first, then from the current URL. */
export function pageUrlFor(paginator, number, currentUrl) {
    if (paginator.pageUrls.has(number)) {
        return paginator.pageUrls.get(number)
    }

    return buildPageUrl(currentUrl, number, paginator.pageKey)
}
