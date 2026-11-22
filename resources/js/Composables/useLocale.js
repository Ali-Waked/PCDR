import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useLocale() {
    const page = usePage()
    const locale = computed(() => page.props.locale || 'ar')
    const direction = computed(() => page.props.direction || (locale.value === 'ar' ? 'rtl' : 'ltr'))
    const isRtl = computed(() => direction.value === 'rtl')
    const strings = computed(() => page.props.translations?.ui ?? {})

    function t(path, fallback = '') {
        return (
            path.split('.').reduce((value, key) => (value && typeof value === 'object' ? value[key] : undefined), strings.value) ??
            fallback
        )
    }

    function localePath(path = '') {
        const suffix = path.startsWith('/') ? path : `/${path}`
        return `/${locale.value}${suffix}`
    }

    function pathForLocale(target) {
        const current = page.url || `/${locale.value}`
        const segments = current.split('?')[0].split('/').filter(Boolean)
        if (['ar', 'en'].includes(segments[0])) {
            segments[0] = target
        } else {
            segments.unshift(target)
        }
        const query = current.includes('?') ? `?${current.split('?')[1]}` : ''
        return `/${segments.join('/')}${query}`
    }

    return { locale, direction, isRtl, t, localePath, pathForLocale }
}
