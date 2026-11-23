<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
import StatusPill from '../../Components/StatusPill.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    metrics: { type: Object, required: true },
    inquiries: { type: Object, required: true },
    recentActivity: { type: Array, default: () => [] },
})

const { t, localPath, formatDate } = useI18n()

const metricCards = computed(() => [
    { key: 'pages', label: t('ui.cms_pages'), value: props.metrics.pages, href: localPath('cms/pages') },
    { key: 'published', label: t('ui.published'), value: props.metrics.publishedPages, href: localPath('cms/pages') },
    { key: 'drafts', label: t('ui.draft'), value: props.metrics.draftPages, href: localPath('cms/pages') },
    { key: 'review', label: t('ui.pending_review'), value: props.metrics.pendingReview, href: localPath('cms/pages') },
    { key: 'articles', label: t('ui.articles'), value: props.metrics.articles, href: localPath('cms/news') },
    { key: 'publications', label: t('ui.documents'), value: props.metrics.publications, href: localPath('cms/publications') },
    { key: 'pillars', label: t('ui.cms_pillars'), value: props.metrics.pillars, href: localPath('cms/pillars') },
    { key: 'media', label: t('ui.cms_media'), value: props.metrics.media, href: localPath('cms/media') },
    { key: 'emailTemplates', label: t('ui.cms_email_templates'), value: props.metrics.emailTemplates, href: localPath('cms/email-templates') },
])

const inquiryCards = computed(() => [
    { key: 'contact', label: t('ui.cms_inquiries_contact'), value: props.inquiries.contact },
    { key: 'volunteer', label: t('ui.cms_inquiries_volunteer'), value: props.inquiries.volunteer },
    { key: 'partnership', label: t('ui.cms_inquiries_partnership'), value: props.inquiries.partnership },
])
</script>

<template>
    <CmsLayout :title="t('ui.cms_dashboard')" :subtitle="t('cms_welcome')">
        <div class="alert alert--info">
            {{ t('cms_plan_notice') }}
        </div>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_metrics') }}</h2>
            <div class="cms-metric-grid">
                <Link
                    v-for="metric in metricCards"
                    :key="metric.key"
                    :href="metric.href"
                    class="cms-metric cms-metric--link"
                >
                    <strong>{{ metric.value }}</strong>
                    <span>{{ metric.label }}</span>
                </Link>
            </div>
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('ui.cms_inquiries') }}</h2>
            <div class="cms-metric-grid">
                <div v-for="card in inquiryCards" :key="card.key" class="cms-metric">
                    <strong>{{ card.value }}</strong>
                    <span>{{ card.label }}</span>
                </div>
            </div>
            <Link :href="localPath('cms/inquiries')" class="btn btn-secondary btn-small">
                {{ t('view_all') }}
            </Link>
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_quick_actions') }}</h2>
            <div class="header-actions">
                <Link :href="localPath('cms/pages/create')" class="btn btn-primary btn-small">{{ t('cms_new_page') }}</Link>
                <Link :href="localPath('cms/inquiries')" class="btn btn-secondary btn-small">{{ t('cms_review_inquiries') }}</Link>
                <Link :href="localPath('cms/email-templates')" class="btn btn-secondary btn-small">{{ t('ui.cms_email_templates') }}</Link>
                <Link :href="localPath('cms/settings')" class="btn btn-secondary btn-small">{{ t('ui.cms_settings') }}</Link>
            </div>
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_recent_activity') }}</h2>
            <div v-if="recentActivity.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_title') }}</th>
                            <th>{{ t('cms_status') }}</th>
                            <th>{{ t('cms_actor') }}</th>
                            <th>{{ t('cms_note') }}</th>
                            <th>{{ t('cms_created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in recentActivity" :key="entry.id">
                            <td>{{ entry.title || `#${entry.id}` }}</td>
                            <td><StatusPill :status="entry.status" /></td>
                            <td>{{ entry.actor || '—' }}</td>
                            <td>{{ entry.note || '—' }}</td>
                            <td class="small-caps">{{ formatDate(entry.createdAt) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_revisions')" />
        </section>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}

.cms-card > .btn {
    margin-block-start: 1rem;
}
</style>
