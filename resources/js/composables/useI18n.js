import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { localizedUrl } from '../Support/languageSwitcher'

export function useI18n() {
    const page = usePage()

    const locale = computed(() => page.props.locale || document.documentElement.lang || 'ar')
    const direction = computed(() => page.props.direction || (locale.value === 'ar' ? 'rtl' : 'ltr'))
    const isRtl = computed(() => direction.value === 'rtl')
    const ui = computed(() => page.props.ui || {})
    const nav = computed(() => page.props.nav || {})

    function t(key, fallback = '') {
        if (typeof key !== 'string' || key === '') {
            return fallback
        }

        const parts = key.split('.')
        let value

        if (parts[0] === 'ui') {
            value = parts.length > 2
                ? parts.slice(1).reduce((carry, part) => carry?.[part], ui.value)
                : ui.value[parts.slice(1).join('.')]
        } else if (parts[0] === 'nav') {
            value = parts.length > 2
                ? parts.slice(1).reduce((carry, part) => carry?.[part], nav.value)
                : nav.value[parts.slice(1).join('.')]
        } else if (parts.length > 1) {
            value = parts.reduce((carry, part) => carry?.[part], page.props)
        } else {
            value = ui.value[key]
        }

        return value ?? fallback ?? key
    }

    function localize(value) {
        if (value == null) {
            return ''
        }
        if (typeof value === 'string') {
            return value
        }

        return value[locale.value] ?? value.ar ?? value.en ?? ''
    }

    function localPath(path = '') {
        const clean = String(path).replace(/^\/+/, '')
        return `/${locale.value}${clean ? `/${clean}` : ''}`
    }

    function alternateUrl(code) {
        const raw = page.url || '/'
        return localizedUrl(raw, locale.value, code, '/') ?? raw
    }

    function richText(value) {
        if (! value) {
            return ''
        }

        return String(value)
            .replace(/<\s*br\s*\/?\s*>/gi, '\n')
            .replace(/<li[^>]*>/gi, '• ')
            .replace(/<\/(p|div|h[1-6]|li|blockquote|tr)>/gi, '\n\n')
            .replace(/<[^>]+>/g, '')
            .replace(/&nbsp;/gi, ' ')
            .replace(/&amp;/gi, '&')
            .replace(/&lt;/gi, '<')
            .replace(/&gt;/gi, '>')
            .replace(/&quot;/gi, '"')
            .replace(/&#39;/gi, "'")
            .replace(/\n{3,}/g, '\n\n')
            .trim()
    }

    function formatDate(value, options = {}) {
        if (! value) {
            return ''
        }

        const date = new Date(value)
        if (Number.isNaN(date.getTime())) {
            return String(value)
        }

        const defaults = locale.value === 'ar'
            ? { day: 'numeric', month: 'long', year: 'numeric', numberingSystem: 'arab' }
            : { day: 'numeric', month: 'short', year: 'numeric' }

        return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB', { ...defaults, ...options })
            .format(date)
    }

    const site = computed(() => page.props.site || {})
    const flash = computed(() => page.props.flash || {})

    return {
        page,
        locale,
        direction,
        isRtl,
        ui,
        nav,
        site,
        flash,
        t,
        localize,
        localPath,
        alternateUrl,
        formatDate,
        richText,
    }
}
