import { computed } from 'vue'
import { useI18n } from './useI18n'

const ITEMS = [
    { key: 'home', path: '', ar: 'الرئيسية', en: 'Home' },
    { key: 'about', path: 'about', ar: 'عن الجمعية', en: 'About' },
    { key: 'pillars', path: 'strategic-areas', ar: 'المحاور الاستراتيجية', en: 'Strategic Axes' },
    { key: 'programs', path: 'programs', ar: 'البرامج والمشاريع', en: 'Programs & Projects' },
    { key: 'news', path: 'news', ar: 'الأخبار والأنشطة', en: 'News & Activities' },
    { key: 'publications', path: 'publications', ar: 'التقارير والإصدارات', en: 'Reports & Publications' },
    { key: 'transparency', path: 'transparency', ar: 'الشفافية والمساءلة', en: 'Transparency & Accountability' },
    { key: 'partnerships', path: 'partnerships', ar: 'الشراكات', en: 'Partnerships' },
    { key: 'contact', path: 'contact', ar: 'اتصل بنا', en: 'Contact' },
]

export function usePublicNavigation() {
    const { locale, localPath } = useI18n()

    const links = computed(() => ITEMS.map((item) => ({
        key: item.key,
        label: locale.value === 'en' ? item.en : item.ar,
        url: localPath(item.path),
        path: item.path,
    })))

    const cta = computed(() => ({
        label: locale.value === 'en' ? 'Join Us' : 'شارك معنا',
        url: localPath('get-involved'),
    }))

    return { links, cta }
}
