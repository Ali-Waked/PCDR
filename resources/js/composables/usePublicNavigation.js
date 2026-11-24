import { computed } from 'vue'
import { useI18n } from './useI18n'

const ITEMS = [
    { key: 'home', path: '', ar: 'الرئيسية', en: 'Home', shortAr: 'الرئيسية', shortEn: 'Home' },
    { key: 'about', path: 'about', ar: 'عن الجمعية', en: 'About', shortAr: 'عن الجمعية', shortEn: 'About' },
    { key: 'pillars', path: 'strategic-areas', ar: 'المحاور الاستراتيجية', en: 'Strategic Areas', shortAr: 'المحاور', shortEn: 'Areas' },
    { key: 'programs', path: 'programs', ar: 'البرامج والمشاريع', en: 'Programs & Projects', shortAr: 'البرامج', shortEn: 'Programs' },
    { key: 'news', path: 'news', ar: 'الأخبار والأنشطة', en: 'News & Activities', shortAr: 'الأخبار', shortEn: 'News' },
    { key: 'publications', path: 'publications', ar: 'التقارير والإصدارات', en: 'Reports & Publications', shortAr: 'التقارير', shortEn: 'Reports' },
    { key: 'transparency', path: 'transparency', ar: 'الشفافية والمساءلة', en: 'Transparency & Accountability', shortAr: 'الشفافية', shortEn: 'Transparency' },
    { key: 'partnerships', path: 'partnerships', ar: 'الشراكات', en: 'Partnerships', shortAr: 'الشراكات', shortEn: 'Partners' },
    { key: 'contact', path: 'contact', ar: 'اتصل بنا', en: 'Contact', shortAr: 'اتصل بنا', shortEn: 'Contact' },
]

const FOOTER_GROUPS = [
    {
        key: 'about',
        ar: 'عن الجمعية',
        en: 'About',
        items: [
            { key: 'about' },
            { key: 'pillars' },
            { key: 'programs' },
        ],
    },
    {
        key: 'knowledge',
        ar: 'المعرفة والمساءلة',
        en: 'Knowledge & Accountability',
        items: [
            { key: 'news' },
            { key: 'publications' },
            { key: 'transparency' },
            { key: 'complaints' },
        ],
    },
    {
        key: 'involve',
        ar: 'شارك معنا',
        en: 'Get Involved',
        items: [
            { key: 'partnerships' },
            { key: 'involve' },
            { key: 'contact', ar: 'تواصل معنا', en: 'Contact Us' },
        ],
    },
]

const EXTRA_ITEMS = {
    involve: { key: 'involve', path: 'get-involved', ar: 'شارك معنا', en: 'Get Involved', shortAr: 'شارك معنا', shortEn: 'Get Involved' },
    'get-involved': { key: 'get-involved', path: 'get-involved', ar: 'شارك معنا', en: 'Get Involved', shortAr: 'شارك معنا', shortEn: 'Get Involved' },
    complaints: { key: 'complaints', path: 'complaints', ar: 'صندوق الشكاوى', en: 'Complaints Box', shortAr: 'صندوق الشكاوى', shortEn: 'Complaints Box' },
}

function resolveItem(entry) {
    const key = typeof entry === 'string' ? entry : entry?.key
    const base = ITEMS.find((item) => item.key === key) || EXTRA_ITEMS[key] || null
    if (!base) return null
    if (typeof entry === 'string' || !entry) return base
    return { ...base, ...entry }
}

const origin = typeof window === 'undefined' ? '' : window.location.origin

function pathOf(url) {
    try {
        return new URL(String(url || ''), origin).pathname.replace(/\/+$/, '') || '/'
    } catch (_) {
        return String(url || '')
    }
}

export function usePublicNavigation() {
    const { page, locale, localPath } = useI18n()
    const isEn = computed(() => locale.value === 'en')

    /**
     * CMS-seeded menus are the source of truth. The compiled ITEMS/FOOTER_GROUPS
     * below remain as a deterministic fallback when the site has not been seeded.
     */
    const seededNavigation = computed(() => page.props.navigation || {})

    function seededLinks(menu) {
        const items = menu && Array.isArray(menu) ? menu : []

        return items
            .filter((item) => item && item.url)
            .map((item, index) => ({
                key: pathOf(item.url).split('/').filter(Boolean).pop() || `seeded-${index}`,
                label: item.label || '',
                shortLabel: item.label || '',
                url: item.url,
                external: !!item.external,
                path: pathOf(item.url),
            }))
            .filter((item) => item.label)
    }

    const seededPrimary = computed(() => seededLinks(seededNavigation.value.primary))
    const seededFooter = computed(() => seededLinks(seededNavigation.value.footer))

    const links = computed(() => {
        if (seededPrimary.value.length > 0) {
            return seededPrimary.value
        }

        return ITEMS.map((item) => ({
            key: item.key,
            label: isEn.value ? item.en : item.ar,
            shortLabel: isEn.value ? item.shortEn : item.shortAr,
            url: localPath(item.path),
            path: item.path,
        }))
    })

    const cta = computed(() => {
        const seeded = seededPrimary.value.find((item) => item.path === pathOf(localPath('get-involved')))
        if (seeded) {
            return { key: 'get-involved', label: seeded.label, url: seeded.url }
        }

        return {
            key: 'get-involved',
            label: isEn.value ? 'Join Us' : 'شارك معنا',
            url: localPath('get-involved'),
        }
    })

    const footerGroups = computed(() => {
        return FOOTER_GROUPS.map((group) => {
            const items = group.items
                .map(resolveItem)
                .filter(Boolean)
                .map((item) => {
                    const url = localPath(item.path)
                    const label = (isEn.value ? item.en : item.ar || '').trim()

                    return {
                        key: item.key,
                        label,
                        url,
                        external: false,
                    }
                })
                .filter((item) => item.label && item.url)

            return {
                key: group.key,
                title: isEn.value ? group.en : group.ar,
                items,
            }
        }).filter((group) => group.items.length > 0)
    })

    return { links, cta, footerGroups }
}
