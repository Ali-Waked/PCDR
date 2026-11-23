<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import MetricCard from '../Components/Dashboard/MetricCard.vue'
import StatusBadge from '../Components/Dashboard/StatusBadge.vue'
import EmptyState from '../Components/Dashboard/EmptyState.vue'
import UiIcon from '../Components/Dashboard/UiIcon.vue'
import { useLocale } from '../Composables/useLocale'
import { useI18n } from '../composables/useI18n'
import { usePermission } from '../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    statistics: { type: Object, required: true },
    latestEvent: { type: Object, default: null },
})

const page = usePage()
const { t, locale, localePath, direction } = useLocale()
const { t: tWebsite } = useI18n()
const { can, isPlatformOwner, canInstitutionalApprove } = usePermission()

function formatNumber(value) {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB').format(value ?? 0)
}

function formatDate(value) {
    if (! value) {
        return '—'
    }

    return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

const editorialMetrics = computed(() => [
    { key: 'content', label: tWebsite('ui.cms_pages'), value: props.statistics.content_items ?? 0, icon: 'file', hint: tWebsite('ui.cms_pages') },
    { key: 'media', label: tWebsite('ui.cms_media'), value: props.statistics.media_items ?? 0, icon: 'image', tone: 'sand' },
    { key: 'inquiries', label: tWebsite('ui.cms_inquiries'), value: props.statistics.inquiries ?? 0, icon: 'mail' },
    { key: 'templates', label: tWebsite('ui.cms_email_templates'), value: props.statistics.email_templates ?? 0, icon: 'template', tone: 'navy' },
])

const accessMetrics = computed(() => [
    { key: 'users', label: t('users'), value: props.statistics.users ?? 0, icon: 'users' },
    { key: 'active_users', label: t('admin_dashboard.active_users'), value: props.statistics.active_users ?? 0, icon: 'check', tone: 'sand' },
    { key: 'roles', label: t('roles'), value: props.statistics.roles ?? 0, icon: 'shield' },
    { key: 'permissions', label: t('permissions'), value: props.statistics.permissions ?? 0, icon: 'lock', tone: 'navy' },
])

const contentGroup = computed(() => (page.props.adminNavigation ?? []).find((group) => group.key === 'content'))

const moduleLinks = computed(() => (contentGroup.value?.items ?? [])
    .filter((item) => ['pages', 'news', 'publications', 'media', 'inquiries', 'email_templates'].includes(item.key))
    .map((item) => ({ ...item, href: localePath(item.path) })))

const iconForModule = {
    pages: 'file',
    news: 'newspaper',
    publications: 'folder',
    media: 'image',
    inquiries: 'mail',
    email_templates: 'template',
}
</script>

<template>
    <div class="pc-stack pcdr-enter" :dir="direction">
        <Head :title="t('dashboard.title')" />

        <div class="pc-callout pc-callout--sand">
            <UiIcon name="shield" :size="18" />
            <div>
                <strong>{{ isPlatformOwner ? t('users_page.platform_owner') : t('admin_brand.technical_control') }}</strong>
                <p>{{ tWebsite('ui.dashboard_no_approval') }}</p>
            </div>
            <StatusBadge
                :label="canInstitutionalApprove ? t('admin_brand.approval_authority') : t('admin_brand.no_approval_authority')"
                :tone="canInstitutionalApprove ? 'amber' : 'slate'"
            />
        </div>

        <div class="pc-callout">
            <UiIcon name="spark" :size="18" />
            <div>
                <strong>{{ t('dashboard.subtitle') }}</strong>
                <p>{{ t('admin_dashboard.real_time_note') }}</p>
            </div>
        </div>

        <section aria-labelledby="editorial-overview">
            <div class="pc-card__head pc-card__head--plain" style="padding-inline: 0">
                <div>
                    <h2 id="editorial-overview" class="pc-card__title">{{ t('admin_dashboard.module_coverage') }}</h2>
                    <p class="pc-card__subtitle">{{ t('admin_dashboard.module_coverage_note') }}</p>
                </div>
            </div>
            <div class="pc-metric-grid" style="margin-block-start: 14px">
                <MetricCard
                    v-for="metric in editorialMetrics"
                    :key="metric.key"
                    :label="metric.label"
                    :value="formatNumber(metric.value)"
                    :hint="metric.hint"
                    :icon="metric.icon"
                    :tone="metric.tone"
                />
            </div>
        </section>

        <section v-if="can('users.view') || can('roles.view')" aria-labelledby="access-overview">
            <div class="pc-card__head pc-card__head--plain" style="padding-inline: 0">
                <div>
                    <h2 id="access-overview" class="pc-card__title">{{ t('admin_dashboard.platform_overview') }}</h2>
                    <p class="pc-card__subtitle">{{ t('users_page.subtitle') }}</p>
                </div>
            </div>
            <div class="pc-metric-grid" style="margin-block-start: 14px">
                <MetricCard
                    v-for="metric in accessMetrics"
                    :key="metric.key"
                    :label="metric.label"
                    :value="formatNumber(metric.value)"
                    :icon="metric.icon"
                    :tone="metric.tone"
                />
            </div>
        </section>

        <div class="pc-grid pc-grid--content">
            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ t('admin_dashboard.quick_access') }}</h2>
                        <p class="pc-card__subtitle">{{ t('admin_dashboard.module_coverage_note') }}</p>
                    </div>
                </div>
                <div class="pc-list">
                    <Link
                        v-for="item in moduleLinks"
                        :key="item.key"
                        :href="item.href"
                        class="pc-list__item"
                        style="text-decoration: none; color: inherit"
                    >
                        <span style="display: inline-flex; align-items: center; gap: 11px; min-inline-size: 0">
                            <span class="pc-metric__icon"><UiIcon :name="iconForModule[item.key] || 'file'" :size="17" /></span>
                            <span class="pc-list__copy">
                                <span class="pc-list__title">{{ item.label }}</span>
                            </span>
                        </span>
                        <UiIcon name="arrowRight" :size="16" />
                    </Link>
                </div>
            </section>

            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ t('admin_dashboard.latest_event') }}</h2>
                        <p class="pc-card__subtitle">{{ t('admin_dashboard.audit_total') }}: {{ formatNumber(statistics.recent_events ?? 0) }}</p>
                    </div>
                    <UiIcon name="history" :size="18" />
                </div>
                <div class="pc-card__body">
                    <div v-if="latestEvent" class="pc-timeline">
                        <div class="pc-timeline__item">
                            <span class="pc-timeline__dot" aria-hidden="true" />
                            <div class="pc-timeline__title" dir="ltr">{{ latestEvent.action }}</div>
                            <div class="pc-timeline__meta">{{ formatDate(latestEvent.created_at) }}</div>
                        </div>
                    </div>
                    <EmptyState v-else :title="t('no_results')" :message="t('admin_dashboard.real_time_note')" icon="clock" />
                </div>
            </section>
        </div>
    </div>
</template>
