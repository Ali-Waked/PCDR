<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import EmptyState from '../../Components/Dashboard/EmptyState.vue'
import Pagination from '../../Components/Pagination.vue'
import SelectionControl from '../../Components/Selection/SelectionControl.vue'
import StatusSelect from '../../Components/Selection/StatusSelect.vue'
import { statusTone as semanticStatusTone } from '../../Support/selectionStatuses'
import { useI18n } from '../../composables/useI18n'
import { humanize, operationRoute } from '../../Components/Operations/operations'

const props = defineProps({
    resource: { type: String, required: true },
    definition: { type: Object, default: () => ({}) },
    records: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    fields: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
    notices: { type: Array, default: () => [] },
})

const { t, locale, direction, page, flash } = useI18n()

const indexUrl = computed(() => operationRoute(`operations.${props.resource}.index`, { resource: props.resource }, locale.value))
const createUrl = computed(() => operationRoute(`operations.${props.resource}.create`, { resource: props.resource }, locale.value))
const rows = computed(() => (Array.isArray(props.records) ? props.records : []))
const loading = computed(() => props.records == null)
const fieldNames = computed(() => props.fields?.length ? props.fields : Object.keys(props.definition?.fields ?? {}))
const sortable = computed(() => Array.isArray(props.definition?.sort) && props.definition.sort.length
    ? props.definition.sort
    : fieldNames.value)
const filterNames = computed(() => (Array.isArray(props.filters) ? props.filters : []))
const statusOptions = computed(() => (Array.isArray(props.statuses) ? props.statuses : []))
const sortOptions = computed(() => sortable.value.map((field) => ({ value: field, label: humanize(field) })))
const directionOptions = [
    { value: 'asc', label: humanize('ascending') },
    { value: 'desc', label: humanize('descending') },
]

function transitionOptions(record) {
    return statusOptions.value
        .filter((status) => status !== record.status)
        .map((status) => ({ value: status, label: humanize(status) }))
}

function readQuery() {
    const raw = (page.url || '').includes('?')
        ? (page.url || '').split('?')[1]
        : (typeof window === 'undefined' ? '' : window.location.search.replace(/^\?/, ''))
    return new URLSearchParams(raw || '')
}

const initialQuery = readQuery()
const search = ref(initialQuery.get('q') || '')
const sort = ref(sortable.value.includes(initialQuery.get('sort')) ? initialQuery.get('sort') : 'id')
const sortDirection = ref(initialQuery.get('direction') === 'asc' ? 'asc' : 'desc')
const perPage = ref(initialQuery.get('per_page') || '20')
const filterValues = ref(Object.fromEntries(filterNames.value.map((field) => [field, initialQuery.get(field) || ''])))

const transitionRecordId = ref(null)
const transitionTarget = ref('')

function applyFilters() {
    const data = { q: search.value, sort: sort.value, direction: sortDirection.value, per_page: perPage.value }
    for (const [field, value] of Object.entries(filterValues.value)) {
        if (value !== '') {
            data[field] = value
        }
    }

    router.get(indexUrl.value, data, { preserveState: true, preserveScroll: true })
}

function toggleSort(field) {
    if (!sortable.value.includes(field)) {
        return
    }
    sortDirection.value = sort.value === field && sortDirection.value === 'asc' ? 'desc' : 'asc'
    sort.value = field
    applyFilters()
}

function displayValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—'
    }
    return typeof value === 'object' ? '' : String(value)
}

function statusTone(status) {
    return `pc-badge--${semanticStatusTone(status)}`
}

function editUrl(record) {
    return operationRoute(`operations.${props.resource}.edit`, { resource: props.resource, record: record.id }, locale.value)
}

function transitionUrl(record) {
    return operationRoute(`operations.${props.resource}.transition`, { resource: props.resource, record: record.id }, locale.value)
}

function openTransition(record) {
    transitionRecordId.value = record.id
    transitionTarget.value = statusOptions.value.find((status) => status !== record.status) || ''
}

function submitTransition(record) {
    if (!transitionTarget.value) {
        return
    }
    router.post(transitionUrl(record), { to_status: transitionTarget.value }, {
        preserveScroll: true,
        onFinish: () => { transitionRecordId.value = null },
    })
}
</script>

<template>
    <AppLayout :title="humanize(resource)" :subtitle="definition?.label || ''">
        <div class="pc-stack">

            <p v-for="(notice, index) in notices" :key="index" class="pc-callout pc-callout--warning">
                {{ notice }}
            </p>

            <form class="pc-form-grid pc-card pc-card--padded" @submit.prevent="applyFilters">
                <label class="pc-field">
                    {{ t('ui.search') }}
                    <input v-model="search" type="search" name="q" class="pc-input" :placeholder="t('ui.search')" />
                </label>
                <div class="pc-field">
                    <SelectionControl
                        v-model="sort"
                        :label="humanize('sort')"
                        name="sort"
                        :options="sortOptions"
                    />
                </div>
                <div class="pc-field">
                    <SelectionControl
                        v-model="sortDirection"
                        :label="humanize('direction')"
                        name="direction"
                        :options="directionOptions"
                        :searchable="false"
                    />
                </div>
                <label v-for="field in filterNames" :key="field" class="pc-field">
                    {{ humanize(field) }}
                    <input
                        v-model="filterValues[field]"
                        type="text"
                        :name="field"
                        class="pc-input"
                        :placeholder="t('ui.filter')"
                    />
                </label>
                <div class="pc-field pc-field--full pc-form-actions">
                    <button type="submit" class="pc-btn pc-btn--primary">
                        {{ t('ui.filter') }}
                    </button>
                    <Link v-if="permissions.create" :href="createUrl" class="pc-btn pc-btn--secondary">
                        {{ t('ui.cms_create') }}
                    </Link>
                </div>
            </form>

            <section class="pc-card">
                <div v-if="loading" class="pc-card__body pc-stack animate-pulse" role="status">
                    <span class="sr-only">{{ t('ui.loading', humanize('loading')) }}</span>
                    <div v-for="index in 5" :key="index" class="pc-skeleton"></div>
                </div>

                <EmptyState v-else-if="!rows.length" :title="t('ui.no_results')" :message="t('ui.no_results')" />

                <div v-else class="pc-table-wrap">
                    <table class="pc-table">
                        <thead>
                            <tr>
                                <th
                                    v-for="field in fieldNames"
                                    :key="field"
                                    class="whitespace-nowrap px-3 py-3 text-start font-medium"
                                    :aria-sort="sort === field ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined"
                                >
                                    <button
                                        v-if="sortable.includes(field)"
                                        type="button"
                                        class="pc-btn pc-btn--ghost pc-btn--sm"
                                        @click="toggleSort(field)"
                                    >
                                        {{ humanize(field) }}
                                    </button>
                                    <span v-else>{{ humanize(field) }}</span>
                                </th>
                                <th class="whitespace-nowrap px-3 py-3 text-start font-medium">{{ t('ui.status', humanize('status')) }}</th>
                                <th class="whitespace-nowrap px-3 py-3 text-start font-medium">{{ t('ui.actions', humanize('actions')) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in rows" :key="record.id" >
                                <td v-for="field in fieldNames" :key="field" class="max-w-60 truncate px-3 py-3" :dir="['code', 'reference', 'source_reference'].includes(field) ? 'ltr' : undefined">
                                    {{ displayValue(record[field]) }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        v-if="record.status"
                                        class="pc-badge"
                                        :class="statusTone(record.status)"
                                    >
                                        {{ humanize(record.status) }}
                                    </span>
                                    <span v-else class="pc-hint">—</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="pc-table__actions">
                                        <Link v-if="permissions.update" :href="editUrl(record)" class="pc-btn pc-btn--secondary pc-btn--sm">
                                            {{ t('ui.cms_edit') }}
                                        </Link>
                                        <button
                                            v-if="permissions.transition"
                                            type="button"
                                            class="pc-btn pc-btn--secondary pc-btn--sm"
                                            @click="openTransition(record)"
                                        >
                                            {{ humanize('transition') }}
                                        </button>
                                    </div>
                                    <div v-if="transitionRecordId === record.id" class="pc-table__actions">
                                        <StatusSelect
                                            v-model="transitionTarget"
                                            :aria-label="humanize('to_status')"
                                            :options="transitionOptions(record)"
                                            :placeholder="humanize('to_status')"
                                            size="sm"
                                        />
                                        <button type="button" class="pc-btn pc-btn--primary pc-btn--sm" @click="submitTransition(record)">
                                            {{ humanize('apply') }}
                                        </button>
                                        <button type="button" class="pc-btn pc-btn--ghost pc-btn--sm" @click="transitionRecordId = null">
                                            {{ t('ui.cancel', humanize('cancel')) }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <Pagination
                :pagination="pagination"
                v-model:per-page="perPage"
                :per-page-options="[10, 20, 50, 100]"
            />
        </div>
    </AppLayout>
</template>
