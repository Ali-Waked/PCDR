<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../Layouts/CmsDashboardLayout.vue'
import MetricCard from '../../Components/Dashboard/MetricCard.vue'
import StatusBadge from '../../Components/Dashboard/StatusBadge.vue'
import EmptyState from '../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    metrics: { type: Object, required: true },
    inquiries: { type: Object, required: true },
    recentActivity: { type: Array, default: () => [] },
})

const { t, localPath, formatDate } = useI18n()

const primaryMetrics = computed(() => [
    { key: 'pages', label: t('ui.cms_pages'), value: props.metrics.pages, icon: 'file', href: localPath('cms/pages') },
    { key: 'published', label: t('ui.published'), value: props.metrics.publishedPages, icon: 'check', href: localPath('cms/pages') },
    { key: 'drafts', label: t('ui.draft'), value: props.metrics.draftPages, icon: 'edit', tone: 'sand', href: localPath('cms/pages') },
    { key: 'review', label: t('ui.pending_review'), value: props.metrics.pendingReview, icon: 'clock', tone: 'navy', href: localPath('cms/pages') },
])

const libraryMetrics = computed(() => [
    { key: 'articles', label: t('ui.articles'), value: props.metrics.articles, icon: 'newspaper', href: localPath('cms/news') },
    { key: 'publications', label: t('ui.documents'), value: props.metrics.publications, icon: 'folder', href: localPath('cms/publications') },
    { key: 'pillars', label: t('ui.cms_pillars'), value: props.metrics.pillars, icon: 'layers', tone: 'sand', href: localPath('cms/pillars') },
    { key: 'media', label: t('ui.cms_media'), value: props.metrics.media, icon: 'image', tone: 'navy', href: localPath('cms/media') },
])

const inquiryMetrics = computed(() => [
    { key: 'contact', label: t('ui.cms_inquiries_contact'), value: props.inquiries.contact, icon: 'mail' },
    { key: 'volunteer', label: t('ui.cms_inquiries_volunteer'), value: props.inquiries.volunteer, icon: 'users', tone: 'sand' },
    { key: 'partnership', label: t('ui.cms_inquiries_partnership'), value: props.inquiries.partnership, icon: 'link', tone: 'navy' },
])

const quickActions = computed(() => [
    { label: t('ui.cms_new_page'), href: localPath('cms/pages/create'), icon: 'plus' },
    { label: t('ui.cms_review_inquiries'), href: localPath('cms/inquiries'), icon: 'mail' },
    { label: t('ui.cms_email_templates'), href: localPath('cms/email-templates'), icon: 'template' },
    { label: t('ui.cms_settings'), href: localPath('cms/settings'), icon: 'settings' },
])
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_dashboard')" :subtitle="t('cms_welcome')">
        <div class="pc-stack pcdr-enter">
            <div class="pc-callout pc-callout--sand">
                <UiIcon name="info" :size="17" />
                <span>{{ t('cms_plan_notice') }}</span>
            </div>

            <section aria-labelledby="cms-content-overview">
                <div class="pc-card__head pc-card__head--plain" style="padding-inline: 0">
                    <div>
                        <h2 id="cms-content-overview" class="pc-card__title">{{ t('cms_metrics') }}</h2>
                        <p class="pc-card__subtitle">{{ t('cms_welcome') }}</p>
                    </div>
                </div>
                <div class="pc-metric-grid" style="margin-block-start: 14px">
                    <Link v-for="metric in primaryMetrics" :key="metric.key" :href="metric.href" style="text-decoration: none; color: inherit">
                        <MetricCard :label="metric.label" :value="metric.value" :icon="metric.icon" :tone="metric.tone" />
                    </Link>
                </div>
            </section>

            <section aria-labelledby="cms-library-overview">
                <div class="pc-card__head pc-card__head--plain" style="padding-inline: 0">
                    <div>
                        <h2 id="cms-library-overview" class="pc-card__title">{{ t('cms_quick_actions') }}</h2>
                        <p class="pc-card__subtitle">{{ t('ui.cms_media') }} · {{ t('ui.cms_email_templates') }}</p>
                    </div>
                </div>
                <div class="pc-metric-grid" style="margin-block-start: 14px">
                    <Link v-for="metric in libraryMetrics" :key="metric.key" :href="metric.href" style="text-decoration: none; color: inherit">
                        <MetricCard :label="metric.label" :value="metric.value" :icon="metric.icon" :tone="metric.tone" />
                    </Link>
                </div>
            </section>

            <div class="pc-grid pc-grid--content">
                <section class="pc-card">
                    <div class="pc-card__head">
                        <div>
                            <h2 class="pc-card__title">{{ t('cms_recent_activity') }}</h2>
                            <p class="pc-card__subtitle">{{ t('cms_note') }}</p>
                        </div>
                        <UiIcon name="history" :size="18" />
                    </div>
                    <div v-if="recentActivity.length" class="pc-table-wrap">
                        <table class="pc-table">
                            <thead>
                                <tr>
                                    <th>{{ t('cms_title') }}</th>
                                    <th>{{ t('cms_status') }}</th>
                                    <th>{{ t('cms_actor') }}</th>
                                    <th>{{ t('cms_created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in recentActivity" :key="entry.id">
                                    <td class="pc-table__primary">{{ entry.title || `#${entry.id}` }}</td>
                                    <td><StatusBadge :label="entry.status || '—'" :tone="entry.status === 'published' ? 'green' : 'amber'" /></td>
                                    <td>{{ entry.actor || '—' }}</td>
                                    <td class="pc-list__meta">{{ formatDate(entry.createdAt) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else :title="t('cms_no_revisions')" :message="t('cms_welcome')" icon="history" />
                </section>

                <div class="pc-stack">
                    <section class="pc-card">
                        <div class="pc-card__head">
                            <div>
                                <h2 class="pc-card__title">{{ t('ui.cms_inquiries') }}</h2>
                                <p class="pc-card__subtitle">{{ t('cms_inquiry_handling_note') }}</p>
                            </div>
                        </div>
                        <div class="pc-list">
                            <div v-for="card in inquiryMetrics" :key="card.key" class="pc-list__item">
                                <span style="display: inline-flex; align-items: center; gap: 10px">
                                    <span class="pc-metric__icon"><UiIcon :name="card.icon" :size="16" /></span>
                                    <span class="pc-list__copy"><span class="pc-list__title">{{ card.label }}</span></span>
                                </span>
                                <strong class="pc-directional">{{ card.value }}</strong>
                            </div>
                        </div>
                        <div class="pc-card__foot">
                            <Link :href="localPath('cms/inquiries')" class="pc-btn pc-btn--secondary pc-btn--sm">{{ t('view_all') }}</Link>
                        </div>
                    </section>

                    <section class="pc-card">
                        <div class="pc-card__head">
                            <div>
                                <h2 class="pc-card__title">{{ t('cms_quick_actions') }}</h2>
                            </div>
                        </div>
                        <div class="pc-card__body" style="display: grid; gap: 8px">
                            <Link v-for="action in quickActions" :key="action.href" :href="action.href" class="pc-menu__item">
                                <UiIcon :name="action.icon" :size="16" /> {{ action.label }}
                            </Link>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </CmsDashboardLayout>
</template>
