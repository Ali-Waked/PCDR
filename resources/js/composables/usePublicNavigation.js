import { computed } from 'vue'
import { useI18n } from './useI18n'

const ITEMS = [
    { key: 'home', path: '', ar: 'الرئيسية', en: 'Home', shortAr: 'الرئيسية', shortEn: 'Home' },
    { key: 'about', path: 'about', ar: 'عن الجمعية', en: 'About', shortAr: 'عن الجمعية', shortEn: 'About' },
    { key: 'pillars', path: 'strategic-areas', ar: 'المحاور الاستراتيجية', en: 'Strategic Axes', shortAr: 'المحاور', shortEn: 'Axes' },
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
        items: ['about', 'pillars', 'programs'],
    },
    {
        key: 'knowledge',
        ar: 'المعرفة والمساءلة',
        en: 'Knowledge & Accountability',
        items: ['news', 'publications', 'transparency'],
    },
    {
        key: 'involve',
        ar: 'شارك معنا',
        en: 'Get Involved',
        items: ['partnerships', 'get-involved', 'contact'],
    },
]

const EXTRA_ITEMS = {
    'get-involved': { key: 'get-involved', path: 'get-involved', ar: 'شارك معنا', en: 'Join Us', shortAr: 'شارك معنا', shortEn: 'Join Us' },
}

function resolveItem(key) {
    return ITEMS.find((item) => item.key === key) || EXTRA_ITEMS[key] || null
}

export function usePublicNavigation() {
    const { locale, localPath } = useI18n()
    const isEn = computed(() => locale.value === 'en')

    const links = computed(() => ITEMS.map((item) => ({
        key: item.key,
        label: isEn.value ? item.en : item.ar,
        shortLabel: isEn.value ? item.shortEn : item.shortAr,
        url: localPath(item.path),
        path: item.path,
    })))

    const cta = computed(() => ({
        key: 'get-involved',
        label: isEn.value ? 'Join Us' : 'شارك معنا',
        url: localPath('get-involved'),
    }))

    const footerGroups = computed(() => FOOTER_GROUPS.map((group) => ({
        key: group.key,
        title: isEn.value ? group.en : group.ar,
        items: group.items
            .map(resolveItem)
            .filter(Boolean)
            .map((item) => ({
                key: item.key,
                label: isEn.value ? item.en : item.ar,
                url: localPath(item.path),
                path: item.path,
            })),
    })))

    return { links, cta, footerGroups }
}
