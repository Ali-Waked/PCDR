<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import ConfirmDialog from '../../../Components/Dashboard/ConfirmDialog.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import MetricCard from '../../../Components/Dashboard/MetricCard.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    resource: { type: String, required: true },
    label: { type: String, required: true },
    routeBase: { type: String, required: true },
    items: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate } = useI18n()

const segments = {
    'cms.pages': 'pages',
    'cms.pillars': 'pillars',
    'cms.programs': 'programs',
    'cms.projects': 'projects',
    'cms.news': 'news',
    'cms.publications': 'publications',
}

const basePath = computed(() => localPath(`cms/${segments[props.routeBase] || props.resource}`))
const confirmId = ref(null)
const statuses = ['draft', 'pending_review', 'approved', 'published', 'archived']

const columns = computed(() => [
    { key: 'title', label: t('cms_title') },
    { key: 'status', label: t('cms_status') },
    { key: 'locales', label: t('available_options') },
    { key: 'updated', label: t('updated_on') },
    { key: 'actions', label: t('cms_actions'), align: 'end' },
])

function changeStatus(id, status) {
    router.post(`${basePath.value}/${id}/status`, { status }, { preserveScroll: true })
}

function remove() {
    if (!confirmId.value) {
        return
    }

    router.delete(`${basePath.value}/${confirmId.value}`, {
        preserveScroll: true,
        onFinish: () => { confirmId.value = null },
    })
}
</script>

<template>
    <CmsDashboardLayout :title="label" :subtitle="t('ui.cms_publish_status')">
        <div class="pc-stack pcdr-enter">
            <div class="pc-metric-grid">
                <MetricCard :label="t('ui.all')" :value="counts.all || 0" icon="layers" />
                <MetricCard :label="t('ui.published')" :value="counts.published || 0" icon="check" tone="sand" />
                <MetricCard :label="t('ui.draft')" :value="counts.draft || 0" icon="file" tone="navy" />
            </div>

            <div class="pc-page-actions">
                <Link :href="`${basePath}/create`" class="pc-btn pc-btn--primary">
                    <UiIcon name="plus" :size="16" />
                    {{ t('cms_new_item') }}
                </Link>
            </div>

            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ label }}</h2>
                        <p class="pc-card__subtitle">{{ t('ui.dashboard_technical_note') }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ items.length }}</span>
                </div>

                <template v-if="items.length">
                    <DataTable :columns="columns">
                        <tr v-for="item in items" :key="item.id">
                            <td class="pc-table__primary">
                                <Link :href="`${basePath}/${item.id}/edit`" class="pc-link">
                                    {{ item.title || `#${item.id}` }}
                                </Link>
                            </td>
                            <td><CmsStatusBadge :status="item.status" /></td>
                            <td>
                                <div class="pc-table__actions">
                                    <span v-for="code in item.locales" :key="code" class="pc-badge pc-badge--slate">{{ code.toUpperCase() }}</span>
                                </div>
                            </td>
                            <td class="pc-table__secondary">{{ formatDate(item.updatedAt) }}</td>
                            <td>
                                <div class="pc-table__actions">
                                    <Link :href="`${basePath}/${item.id}/edit`" class="pc-btn pc-btn--secondary pc-btn--sm">
                                        <UiIcon name="edit" :size="14" />
                                        {{ t('ui.edit') }}
                                    </Link>
                                    <label class="pc-field">
                                        <span class="sr-only">{{ `${t('cms_status')}: ${item.title}` }}</span>
                                        <select
                                            class="pc-select"
                                            :value="item.status"
                                            @change="changeStatus(item.id, $event.target.value)"
                                        >
                                            <option v-for="status in statuses" :key="status" :value="status">{{ t(status) }}</option>
                                        </select>
                                    </label>
                                    <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="confirmId = item.id">
                                        <UiIcon name="trash" :size="14" />
                                        {{ t('ui.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </DataTable>
                    <CmsPagination :pagination="pagination" />
                </template>

                <EmptyState
                    v-else
                    :title="t('cms_no_records')"
                    :message="t('ui.dashboard_content_studio')"
                    icon="file"
                >
                    <Link :href="`${basePath}/create`" class="pc-btn pc-btn--primary">
                        <UiIcon name="plus" :size="16" />
                        {{ t('cms_new_item') }}
                    </Link>
                </EmptyState>
            </section>
        </div>

        <ConfirmDialog
            :open="confirmId !== null"
            :title="t('ui.delete')"
            :message="t('cms_confirm_delete')"
            :confirm-label="t('ui.delete')"
            :cancel-label="t('ui.cancel')"
            danger
            @close="confirmId = null"
            @confirm="remove"
        />
    </CmsDashboardLayout>
</template>
