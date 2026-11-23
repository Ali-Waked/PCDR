<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    inquiries: { type: Object, default: () => ({}) },
    pagination: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({ status: '', search: '', assigned: '' }) },
    statuses: { type: Array, default: () => ['new', 'in_progress', 'resolved', 'closed'] },
})

const { t, localPath, formatDate } = useI18n()
const inquiriesPath = localPath('cms/inquiries')

const groups = computed(() => [
    { key: 'contact', label: t('ui.cms_inquiries_contact'), rows: props.inquiries?.contact || [] },
    { key: 'volunteer', label: t('ui.cms_inquiries_volunteer'), rows: props.inquiries?.volunteer || [] },
    { key: 'partnership', label: t('ui.cms_inquiries_partnership'), rows: props.inquiries?.partnership || [] },
])

const filters = ref({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
    assigned: props.filters?.assigned || '',
})

watch(() => props.filters, (value) => {
    filters.value = {
        status: value?.status || '',
        search: value?.search || '',
        assigned: value?.assigned || '',
    }
}, { deep: true })

function submitFilters() {
    router.get(inquiriesPath, { ...filters.value }, { preserveScroll: true })
}

const statusLabels = {
    new: 'cms_status_new',
    in_progress: 'cms_status_in_progress',
    resolved: 'cms_status_resolved',
    closed: 'cms_status_closed',
}

function statusLabel(status) {
    return statusLabels[status] ? t(statusLabels[status], status) : status
}

const columns = computed(() => [
    { key: 'reference', label: t('cms_reference') },
    { key: 'institution', label: t('institution') },
    { key: 'email', label: t('ui.email') },
    { key: 'subject', label: t('subject') },
    { key: 'assigned', label: t('cms_assigned_to') },
    { key: 'status', label: t('cms_status') },
    { key: 'created', label: t('cms_created_at') },
    { key: 'actions', label: t('cms_actions'), align: 'end' },
])

const statusDrafts = ref({})

function rowKey(groupKey, row) {
    return `${groupKey}:${row.id}`
}

function setDraftStatus(groupKey, row, status) {
    statusDrafts.value[rowKey(groupKey, row)] = status
}

function saveStatus(groupKey, row) {
    const status = statusDrafts.value[rowKey(groupKey, row)] ?? row.status
    router.put(`${inquiriesPath}/${groupKey}/${row.id}/status`, { status }, { preserveScroll: true })
}
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_inquiries')" :subtitle="t('cms_inquiry_handling_note')">
        <div class="pc-stack pcdr-enter">
            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ t('ui.filter') }}</h2>
                        <p class="pc-card__subtitle">{{ t('cms_inquiry_handling_note') }}</p>
                    </div>
                </div>
                <form class="pc-card__body pc-form-grid" @submit.prevent="submitFilters">
                    <label class="pc-field">
                        <span class="pc-label">{{ t('ui.status') }}</span>
                        <select v-model="filters.status" class="pc-select">
                            <option value="">{{ t('ui.all') }}</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                        </select>
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_assignment') }}</span>
                        <select v-model="filters.assigned" class="pc-select">
                            <option value="">{{ t('ui.all') }}</option>
                            <option value="mine">{{ t('cms_assigned_to_me') }}</option>
                            <option value="unassigned">{{ t('cms_unassigned') }}</option>
                        </select>
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_search') }}</span>
                        <input v-model="filters.search" class="pc-input" type="search" maxlength="120">
                    </label>
                    <div class="pc-field pc-field--action">
                        <button class="pc-btn pc-btn--primary" type="submit">
                            <UiIcon name="search" :size="16" />
                            {{ t('ui.filter') }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-for="group in groups" :key="group.key" class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ group.label }}</h2>
                        <p class="pc-card__subtitle">{{ group.rows.length }} {{ t('ui.cms_inquiries') }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ group.rows.length }}</span>
                </div>

                <template v-if="group.rows.length">
                    <DataTable :columns="columns">
                        <tr v-for="row in group.rows" :key="row.id">
                            <td class="pc-directional" dir="ltr">{{ row.reference }}</td>
                            <td>{{ row.label }}</td>
                            <td class="pc-directional" dir="ltr">{{ row.email }}</td>
                            <td class="pc-table__primary">{{ row.label }}</td>
                            <td>{{ row.assignedTo || '—' }}</td>
                            <td>
                                <div class="pc-stack" style="gap: 8px">
                                    <CmsStatusBadge :status="row.status" :label="statusLabel(row.status)" />
                                    <div class="pc-table__actions">
                                        <select
                                            class="pc-select"
                                            :value="statusDrafts[rowKey(group.key, row)] ?? row.status"
                                            :aria-label="`${t('cms_status')}: ${row.reference}`"
                                            @change="setDraftStatus(group.key, row, $event.target.value)"
                                        >
                                            <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                                        </select>
                                        <button
                                            class="pc-btn pc-btn--secondary pc-btn--sm"
                                            type="button"
                                            :aria-label="t('cms_update_status', t('cms_save'))"
                                            @click="saveStatus(group.key, row)"
                                        >
                                            {{ t('cms_update_status', t('cms_save')) }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="pc-table__secondary">{{ formatDate(row.createdAt) }}</td>
                            <td>
                                <Link :href="`${inquiriesPath}/${group.key}/${row.id}`" class="pc-btn pc-btn--secondary pc-btn--sm">
                                    <UiIcon name="eye" :size="14" />
                                    {{ t('ui.view') }}
                                </Link>
                            </td>
                        </tr>
                    </DataTable>
                    <CmsPagination :pagination="pagination[group.key] || { currentPage: 1, lastPage: 1, total: 0 }" />
                </template>
                <EmptyState v-else :title="t('cms_no_records')" icon="mail" />
            </section>
        </div>
    </CmsDashboardLayout>
</template>
