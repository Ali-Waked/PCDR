<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'
import { formatCount, humanize, operationRoute } from '../../Components/Operations/operations'

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

function pageUrl(number) {
    const data = { q: search.value, sort: sort.value, direction: sortDirection.value, per_page: perPage.value, page: number }
    for (const [field, value] of Object.entries(filterValues.value)) {
        if (value !== '') {
            data[field] = value
        }
    }
    const query = new URLSearchParams(data)
    return `${indexUrl.value}?${query.toString()}`
}

const pageNumbers = computed(() => {
    const last = Number(props.pagination?.lastPage || 1)
    const current = Number(props.pagination?.currentPage || 1)
    const start = Math.max(1, current - 2)
    const end = Math.min(last, start + 4)
    return Array.from({ length: Math.max(0, end - start + 1) }, (_, index) => start + index)
})

function displayValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—'
    }
    return typeof value === 'object' ? '' : String(value)
}

function statusTone(status) {
    if (['active', 'generated', 'verified', 'reconciled', 'completed', 'published'].includes(status)) {
        return 'bg-success/10 text-success'
    }
    if (['archived', 'cancelled', 'rejected', 'closed'].includes(status)) {
        return 'bg-surface-alt text-muted'
    }
    if (['draft', 'planned', 'new'].includes(status)) {
        return 'bg-navy-100 text-navy-700'
    }
    return 'bg-warning/10 text-warning'
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
    <CmsLayout :title="humanize(resource)" :subtitle="definition?.label || ''">
        <div :dir="direction" class="space-y-5 text-ink">
            <div
                v-if="flash?.success || flash?.error"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="flash?.error ? 'border-danger/30 bg-danger/5 text-danger' : 'border-success/30 bg-success/5 text-success'"
                role="status"
            >
                {{ flash?.error || flash?.success }}
            </div>

            <p v-for="(notice, index) in notices" :key="index" class="rounded-lg bg-warning/10 px-4 py-3 text-sm text-warning">
                {{ notice }}
            </p>

            <form class="grid gap-3 rounded-xl border border-border-subtle bg-surface p-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyFilters">
                <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                    {{ t('ui.search') }}
                    <input v-model="search" type="search" name="q" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm text-ink" :placeholder="t('ui.search')" />
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                    {{ humanize('sort') }}
                    <select v-model="sort" name="sort" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm text-ink">
                        <option v-for="field in sortable" :key="field" :value="field">{{ humanize(field) }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                    {{ humanize('direction') }}
                    <select v-model="sortDirection" name="direction" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm text-ink">
                        <option value="asc">{{ humanize('ascending') }}</option>
                        <option value="desc">{{ humanize('descending') }}</option>
                    </select>
                </label>
                <label v-for="field in filterNames" :key="field" class="flex flex-col gap-1 text-xs font-medium text-muted">
                    {{ humanize(field) }}
                    <input
                        v-model="filterValues[field]"
                        type="text"
                        :name="field"
                        class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm text-ink"
                        :placeholder="t('ui.filter')"
                    />
                </label>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit" class="focus-ring rounded-md bg-navy-700 px-4 py-2 text-sm font-semibold text-white">
                        {{ t('ui.filter') }}
                    </button>
                    <Link v-if="permissions.create" :href="createUrl" class="focus-ring rounded-md border border-border-subtle px-4 py-2 text-sm font-semibold">
                        {{ t('ui.cms_create') }}
                    </Link>
                </div>
            </form>

            <section class="overflow-hidden rounded-xl border border-border-subtle bg-surface">
                <div v-if="loading" class="animate-pulse space-y-3 p-5" role="status">
                    <span class="sr-only">{{ t('ui.loading', humanize('loading')) }}</span>
                    <div v-for="index in 5" :key="index" class="h-8 rounded bg-surface-alt"></div>
                </div>

                <EmptyState v-else-if="!rows.length" :title="t('ui.no_results')" :body="t('ui.no_results')" />

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border-subtle text-start text-muted">
                                <th
                                    v-for="field in fieldNames"
                                    :key="field"
                                    class="whitespace-nowrap px-3 py-3 text-start font-medium"
                                    :aria-sort="sort === field ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined"
                                >
                                    <button
                                        v-if="sortable.includes(field)"
                                        type="button"
                                        class="focus-ring rounded"
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
                            <tr v-for="record in rows" :key="record.id" class="border-b border-border-subtle last:border-0">
                                <td v-for="field in fieldNames" :key="field" class="max-w-60 truncate px-3 py-3" :dir="['code', 'reference', 'source_reference'].includes(field) ? 'ltr' : undefined">
                                    {{ displayValue(record[field]) }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        v-if="record.status"
                                        class="inline-block rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusTone(record.status)"
                                    >
                                        {{ humanize(record.status) }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link v-if="permissions.update" :href="editUrl(record)" class="focus-ring rounded border border-border-subtle px-2.5 py-1.5 text-xs">
                                            {{ t('ui.cms_edit') }}
                                        </Link>
                                        <button
                                            v-if="permissions.transition"
                                            type="button"
                                            class="focus-ring rounded border border-border-subtle px-2.5 py-1.5 text-xs"
                                            @click="openTransition(record)"
                                        >
                                            {{ humanize('transition') }}
                                        </button>
                                    </div>
                                    <div v-if="transitionRecordId === record.id" class="mt-2 flex flex-wrap items-center gap-2">
                                        <select v-model="transitionTarget" class="rounded-md border border-border-subtle bg-surface px-2 py-1 text-xs" :aria-label="humanize('to_status')">
                                            <option v-for="status in statusOptions.filter((entry) => entry !== record.status)" :key="status" :value="status">
                                                {{ humanize(status) }}
                                            </option>
                                        </select>
                                        <button type="button" class="focus-ring rounded bg-navy-700 px-3 py-1.5 text-xs text-white" @click="submitTransition(record)">
                                            {{ humanize('apply') }}
                                        </button>
                                        <button type="button" class="focus-ring rounded px-2 py-1.5 text-xs text-muted" @click="transitionRecordId = null">
                                            {{ t('ui.cancel', humanize('cancel')) }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted">
                <span>{{ humanize('total') }}: {{ formatCount(pagination?.total, locale) }}</span>
                <nav v-if="pageNumbers.length > 1" class="flex flex-wrap gap-1" :aria-label="humanize('pagination')">
                    <Link
                        v-for="number in pageNumbers"
                        :key="number"
                        :href="pageUrl(number)"
                        preserve-scroll
                        class="focus-ring rounded-md border border-border-subtle px-3 py-1.5 text-sm"
                        :class="number === Number(pagination?.currentPage) ? 'bg-navy-700 text-white' : 'bg-surface text-ink'"
                        :aria-current="number === Number(pagination?.currentPage) ? 'page' : undefined"
                    >
                        {{ number }}
                    </Link>
                </nav>
            </div>
        </div>
    </CmsLayout>
</template>
