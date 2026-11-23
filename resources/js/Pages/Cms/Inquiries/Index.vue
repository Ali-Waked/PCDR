<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import Pagination from '../../../Components/Pagination.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    inquiries: { type: Object, default: () => ({}) },
    pagination: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({ status: '' }) },
    statuses: { type: Array, default: () => ['new', 'in_progress', 'resolved', 'closed'] },
})

const { t, localPath, formatDate } = useI18n()
const inquiriesPath = localPath('cms/inquiries')

const groups = computed(() => [
    { key: 'contact', label: t('ui.cms_inquiries_contact'), rows: props.inquiries?.contact || [] },
    { key: 'volunteer', label: t('ui.cms_inquiries_volunteer'), rows: props.inquiries?.volunteer || [] },
    { key: 'partnership', label: t('ui.cms_inquiries_partnership'), rows: props.inquiries?.partnership || [] },
])

const availableStatuses = computed(() => props.statuses?.length ? props.statuses : ['new', 'in_progress', 'resolved', 'closed'])
const statusFilter = ref(props.filters?.status || '')

watch(() => props.filters, (filters) => {
    statusFilter.value = filters?.status || ''
}, { deep: true })

function submitStatusFilter() {
    router.get(inquiriesPath, { status: statusFilter.value }, { preserveScroll: true })
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

const statusOverrides = ref({})
const statusDrafts = ref({})

function rowKey(groupKey, row) {
    return `${groupKey}:${row.id}`
}

function currentStatus(groupKey, row) {
    const key = rowKey(groupKey, row)
    return statusOverrides.value[key] ?? row.status ?? 'new'
}

function draftStatus(groupKey, row) {
    const key = rowKey(groupKey, row)
    return statusDrafts.value[key] ?? currentStatus(groupKey, row)
}

function setDraftStatus(groupKey, row, status) {
    statusDrafts.value[rowKey(groupKey, row)] = status
}

function saveStatus(groupKey, row) {
    const key = rowKey(groupKey, row)
    const status = draftStatus(groupKey, row)

    router.put(`${inquiriesPath}/${groupKey}/${row.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => { statusOverrides.value[key] = status },
    })
}

function subject(row, key) {
    if (key === 'contact') {
        return row.subject || row.category
    }
    if (key === 'volunteer') {
        return row.area_of_interest
    }

    return row.interest
}

function organization(row, key) {
    return key === 'partnership' ? row.organization : row.name
}
</script>

<template>
    <CmsLayout :title="t('ui.cms_inquiries')" :subtitle="t('privacy_notice')">
        <form class="form-grid form-grid--two" style="margin-block-end: 1rem" @submit.prevent="submitStatusFilter">
            <label class="field">
                <span>{{ t('ui.status') }}</span>
                <select v-model="statusFilter" class="select" name="status">
                    <option value="">{{ t('ui.all') }}</option>
                    <option v-for="status in availableStatuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary btn-small" type="submit">{{ t('ui.filter') }}</button>
            </div>
        </form>

        <section v-for="group in groups" :key="group.key" class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ group.label }}</h2>

            <div v-if="group.rows.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_reference') }}</th>
                            <th>{{ t('institution') }}</th>
                            <th>{{ t('ui.email') }}</th>
                            <th>{{ t('subject') }}</th>
                            <th>{{ t('cms_status') }}</th>
                            <th>{{ t('cms_created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in group.rows" :key="row.id">
                            <td dir="ltr">{{ row.reference }}</td>
                            <td>{{ organization(row, group.key) }}</td>
                            <td dir="ltr">{{ row.email }}</td>
                            <td>{{ subject(row, group.key) }}</td>
                            <td>
                                <span class="pill">{{ statusLabel(currentStatus(group.key, row)) }}</span>
                                <div class="header-actions" style="margin-block-start: 0.35rem">
                                    <select
                                        class="select"
                                        style="inline-size: auto; min-block-size: 36px; font-size: 0.8rem"
                                        :value="draftStatus(group.key, row)"
                                        :aria-label="`${t('cms_status')}: ${row.reference}`"
                                        @change="setDraftStatus(group.key, row, $event.target.value)"
                                    >
                                        <option v-for="status in availableStatuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                                    </select>
                                    <button class="btn btn-secondary btn-small" type="button" :aria-label="t('cms_update_status', t('cms_save'))" @click="saveStatus(group.key, row)">{{ t('cms_update_status', t('cms_save')) }}</button>
                                </div>
                            </td>
                            <td class="small-caps">{{ formatDate(row.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
            <Pagination :pagination="pagination[group.key] || { currentPage: 1, lastPage: 1, total: 0 }" />
        </section>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}
</style>
