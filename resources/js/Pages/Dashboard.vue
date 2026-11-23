<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    statistics: { type: Object, required: true },
    latestEvent: { type: Object, default: null },
})

const page = usePage()
const { t, locale, localePath, direction } = useLocale()
const { isPlatformOwner, canInstitutionalApprove } = usePermission()

function formatNumber(value) {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB').format(value ?? 0)
}

function formatDate(value) {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

const primaryStats = computed(() => [
    { key: 'users', label: t('dashboard.users'), value: props.statistics.users ?? 0 },
    { key: 'active_users', label: t('admin_dashboard.active_users'), value: props.statistics.active_users ?? 0 },
    { key: 'roles', label: t('dashboard.roles'), value: props.statistics.roles ?? 0 },
    { key: 'permissions', label: t('dashboard.permissions'), value: props.statistics.permissions ?? 0 },
])

const contentGroup = computed(() => (page.props.adminNavigation ?? []).find((group) => group.key === 'content'))

const moduleCounts = computed(() => ({
    cms_dashboard: props.statistics.content_items ?? 0,
    pages: props.statistics.content_items ?? 0,
    news: props.statistics.content_items ?? 0,
    publications: props.statistics.content_items ?? 0,
    media: props.statistics.media_items ?? 0,
    inquiries: props.statistics.inquiries ?? 0,
    email_templates: props.statistics.email_templates ?? 0,
}))

const moduleStats = computed(() => (contentGroup.value?.items ?? [])
    .filter((item) => item.key in moduleCounts.value)
    .map((item) => ({
        key: item.key,
        label: item.label,
        value: moduleCounts.value[item.key],
        href: localePath(item.path),
    })))

const quickLinks = computed(() => {
    const accessGroup = (page.props.adminNavigation ?? []).find((group) => group.key === 'access')

    return [...(contentGroup.value?.items ?? []).slice(0, 4), ...(accessGroup?.items ?? []).slice(0, 2)]
        .map((item) => ({ ...item, href: localePath(item.path) }))
})
</script>

<template>
    <div class="admin-dashboard" :dir="direction">
        <Head :title="t('dashboard.title')" />

        <section class="dashboard-hero pcdr-enter" aria-labelledby="dashboard-hero-title">
            <div>
                <p class="admin-eyebrow">{{ t('dashboard.subtitle') }}</p>
                <h2 id="dashboard-hero-title">{{ t('admin_dashboard.hero_title') }}</h2>
                <p class="dashboard-hero__lede">{{ t('admin_dashboard.hero_text') }}</p>
            </div>
            <div class="dashboard-hero__badges">
                <span v-if="isPlatformOwner" class="admin-role-badge">{{ t('users_page.platform_owner') }}</span>
                <span class="admin-role-badge admin-role-badge--technical">
                    {{ t('admin_brand.technical_control') }}
                </span>
                <span
                    class="admin-role-badge"
                    :class="canInstitutionalApprove ? 'admin-role-badge--authority' : 'admin-role-badge--muted'"
                >
                    {{ canInstitutionalApprove ? t('admin_brand.approval_authority') : t('admin_brand.no_approval_authority') }}
                </span>
            </div>
        </section>

        <section class="dashboard-section" aria-labelledby="platform-stats-title">
            <div class="dashboard-section__head">
                <h2 id="platform-stats-title">{{ t('admin_dashboard.platform_overview') }}</h2>
                <p>{{ t('admin_dashboard.real_time_note') }}</p>
            </div>
            <div class="dashboard-stat-grid">
                <article v-for="stat in primaryStats" :key="stat.key" class="dashboard-stat pcdr-enter">
                    <p>{{ stat.label }}</p>
                    <strong>{{ formatNumber(stat.value) }}</strong>
                </article>
            </div>
        </section>

        <section class="dashboard-section" aria-labelledby="module-stats-title">
            <div class="dashboard-section__head">
                <h2 id="module-stats-title">{{ t('admin_dashboard.module_coverage') }}</h2>
                <p>{{ t('admin_dashboard.module_coverage_note') }}</p>
            </div>
            <div class="dashboard-module-grid">
                <article v-for="module in moduleStats" :key="module.key" class="dashboard-module pcdr-enter">
                    <div>
                        <p class="dashboard-module__label">{{ module.label }}</p>
                        <strong>{{ formatNumber(module.value) }}</strong>
                    </div>
                    <Link :href="module.href" class="btn btn-ghost btn-small">
                        {{ t('view') }}
                        <span aria-hidden="true">→</span>
                    </Link>
                </article>
            </div>
        </section>

        <section class="dashboard-section dashboard-section--split">
            <div class="pcdr-card dashboard-quick">
                <h2>{{ t('admin_dashboard.quick_access') }}</h2>
                <ul>
                    <li v-for="link in quickLinks" :key="link.key">
                        <Link :href="link.href" class="admin-nav-link">
                            <span>{{ link.label }}</span>
                            <span aria-hidden="true">←</span>
                        </Link>
                    </li>
                </ul>
            </div>

            <div class="pcdr-card dashboard-audit">
                <h2>{{ t('admin_dashboard.latest_event') }}</h2>
                <template v-if="latestEvent">
                    <p class="dashboard-audit__action">{{ latestEvent.action }}</p>
                    <p class="dashboard-audit__meta">{{ formatDate(latestEvent.created_at) }}</p>
                </template>
                <p v-else class="dashboard-audit__empty">{{ t('no_results') }}</p>
                <p class="dashboard-audit__count">
                    {{ t('admin_dashboard.audit_total') }}: {{ formatNumber(statistics.recent_events ?? 0) }}
                </p>
            </div>
        </section>
    </div>
</template>
