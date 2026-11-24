<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import ContentCollectionCard from '../../../Components/Cms/ContentCollectionCard.vue'
import ContentFilterBar from '../../../Components/Cms/ContentFilterBar.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import MetricCard from '../../../Components/Dashboard/MetricCard.vue'
import StatusSelect from '../../../Components/Selection/StatusSelect.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'
import { useAdminNotifications } from '../../../composables/useAdminNotifications'

const props = defineProps({
    resource: { type: String, required: true },
    label: { type: String, required: true },
    routeBase: { type: String, required: true },
    pageTitle: { type: String, default: '' },
    pageDescription: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    filterOptions: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate, page } = useI18n()
const {
    confirmDelete,
    confirmArchive,
    confirmReverse,
    confirmStatus,
    finishConfirmation,
    setConfirmationBusy,
} = useAdminNotifications()

const segments = {
    'cms.pillars': 'pillars',
    'cms.programs': 'programs',
    'cms.projects': 'projects',
    'cms.news': 'news',
    'cms.publications': 'publications',
}

const basePath = computed(() => localPath(`cms/${segments[props.routeBase] || props.resource}`))
const title = computed(() => props.pageTitle || props.label || t('ui.cms_content_page.title'))
const description = computed(() => props.pageDescription || t('ui.cms_content_page.breadcrumb'))
const breadcrumb = computed(() => [
    { label: t('ui.cms_content_page.breadcrumb'), href: localPath('cms') },
    { label: title.value },
])

function buildGroupFilters(source) {
    const values = {}
    for (const group of source.filterOptions ?? []) {
        values[group.key] = source.filters?.[group.key] ?? ''
    }

    return values
}

const searchTerm = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? 'all')
const sortBy = ref(props.filters?.sort ?? 'updated_desc')
const groupFilters = ref(buildGroupFilters(props))
const processing = ref(false)
const transitionBusy = ref(false)
const deleteBusy = ref(false)
let debounceTimer = null

const filtered = computed(() => {
    if (String(searchTerm.value).trim() !== '') {
        return true
    }
    if (statusFilter.value && statusFilter.value !== 'all') {
        return true
    }
    if (sortBy.value && sortBy.value !== 'updated_desc') {
        return true
    }

    return Object.values(groupFilters.value).some((value) => value !== '' && value !== null)
})

const summary = computed(() => translate(t('ui.cms_content_page.showing'), {
    from: props.pagination?.from ?? 0,
    to: props.pagination?.to ?? 0,
    total: props.pagination?.total ?? 0,
}))

const emptyTitle = computed(() => filtered.value
    ? t('ui.cms_content_page.empty_filtered_title')
    : t('ui.cms_content_page.empty_title'))

const emptyMessage = computed(() => filtered.value
    ? t('ui.cms_content_page.empty_filtered_message')
    : t('ui.cms_content_page.empty_message'))

const statIcons = {
    all: 'layers',
    published: 'check',
    pending_review: 'clock',
    approved: 'shield',
    draft: 'file',
    archived: 'inbox',
}

const stats = computed(() => props.statuses.map((status) => ({
    key: status.value,
    label: status.label,
    value: props.counts?.[status.value] ?? 0,
    icon: statIcons[status.value] ?? 'file',
    tone: status.value === 'published' ? 'sand' : 'teal',
})).filter((stat) => stat.key !== 'published' || (props.counts?.published ?? 0) > 0))

const columns = computed(() => [
    { key: 'title', label: t('ui.cms_content_page.title') },
    { key: 'details', label: t('ui.cms_content_page.details') },
    { key: 'status', label: t('ui.cms_content_page.status') },
    { key: 'locales', label: t('ui.cms_content_page.locales') },
    { key: 'updated', label: t('ui.cms_content_page.updated') },
    { key: 'actions', label: t('ui.cms_content_page.actions'), align: 'end' },
])

function translate(value, replacements = {}) {
    let result = String(value ?? '')
    for (const [name, replacement] of Object.entries(replacements)) {
        result = result.replaceAll(`:${name}`, String(replacement ?? ''))
    }

    return result
}

function filterPayload() {
    const query = {}
    const search = String(searchTerm.value).trim()
    const status = statusFilter.value

    if (search !== '') {
        query.search = search
    }
    if (status && status !== 'all') {
        query.status = status
    }
    if (sortBy.value && sortBy.value !== 'updated_desc') {
        query.sort = sortBy.value
    }

    for (const [key, value] of Object.entries(groupFilters.value)) {
        if (value !== '' && value !== null) {
            query[key] = value
        }
    }

    return query
}

function applyFilters(resetPage = true) {
    window.clearTimeout(debounceTimer)

    const query = filterPayload()
    if (!resetPage && Number(props.pagination?.currentPage ?? 1) > 1) {
        query.page = Number(props.pagination.currentPage)
    }

    router.get(basePath.value, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { processing.value = true },
        onFinish: () => { processing.value = false },
    })
}

function queueSearch() {
    window.clearTimeout(debounceTimer)
    debounceTimer = window.setTimeout(() => applyFilters(true), 350)
}

function applyGroup({ key, value }) {
    groupFilters.value[key] = value ?? ''
    applyFilters(true)
}

function clearFilters() {
    searchTerm.value = ''
    statusFilter.value = 'all'
    sortBy.value = 'updated_desc'
    groupFilters.value = buildGroupFilters({ filterOptions: props.filterOptions, filters: {} })
    applyFilters(true)
}

function actionsFor(item) {
    return props.transitions?.[item.status] ?? []
}

function statusOptionsFor(item) {
    const current = (props.statuses ?? []).find((status) => status.value === item.status)

    return [
        current ? { ...current, description: '', disabled: true } : null,
        ...actionsFor(item).map((action) => ({
            value: action.value,
            label: action.label,
            description: action.description,
        })),
    ].filter(Boolean)
}

function itemMeta(item) {
    const values = [item.pillar, item.program, item.category, item.projectStatusLabel, item.version]
        .filter((value) => value && String(value).trim() !== '')

    if (item.isFeatured) {
        values.push(t('ui.featured_image'))
    }
    if (item.hasDocument) {
        values.push(t('cms_media_type_document'))
    }
    if (item.isRestricted) {
        values.push(t('cms_restricted_file'))
    }

    return values.join(' · ')
}

function dateRange(item) {
    if (!item.startDate && !item.endDate) {
        return ''
    }

    return [item.startDate, item.endDate].filter(Boolean).map((date) => formatDate(date)).join(' — ')
}

function previewPath(item) {
    return localPath(`cms/preview/${segments[props.routeBase] || props.resource}/${item.id}`)
}

async function changeStatus(item, value) {
    if (!value || value === item.status || transitionBusy.value) {
        return
    }

    const action = actionsFor(item).find((entry) => entry.value === value)
    if (!action) {
        return
    }

    const subject = item.title || `#${item.id}`
    if (action.confirmation === 'archive'
        && ! await confirmArchive(subject, { message: translate(t('ui.cms_content_page.delete_confirm'), { title: subject }) })) {
        return
    }
    if (action.confirmation === 'publish'
        && ! await confirmStatus(subject, { message: translate(t('ui.cms_content_page.delete_confirm'), { title: subject }) })) {
        return
    }
    if (action.confirmation === 'reverse'
        && ! await confirmReverse(subject, { message: translate(t('ui.cms_content_page.delete_confirm'), { title: subject }) })) {
        return
    }

    transitionBusy.value = true
    setConfirmationBusy(true)
    router.post(`${basePath.value}/${item.id}/status`, {
        status: value,
        filters: { ...filterPayload(), page: props.pagination?.currentPage ?? 1 },
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            transitionBusy.value = false
            finishConfirmation()
        },
    })
}

async function remove(item) {
    if (deleteBusy.value) {
        return
    }

    const subject = item.title || `#${item.id}`
    if (! await confirmDelete(subject, {
        message: translate(t('ui.cms_content_page.delete_confirm'), { title: subject }),
    })) {
        return
    }

    deleteBusy.value = true
    setConfirmationBusy(true)
    router.delete(`${basePath.value}/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteBusy.value = false
            finishConfirmation()
        },
    })
}

watch(() => props.filters, (filters) => {
    searchTerm.value = filters?.search ?? ''
    statusFilter.value = filters?.status ?? 'all'
    sortBy.value = filters?.sort ?? 'updated_desc'
    groupFilters.value = buildGroupFilters({ filterOptions: props.filterOptions, filters })
})

onBeforeUnmount(() => window.clearTimeout(debounceTimer))
</script>

<template>
    <CmsDashboardLayout
        :title="title"
        :subtitle="description"
        :breadcrumb="breadcrumb"
        :eyebrow="t('ui.cms_content_page.breadcrumb')"
    >
        <template #actions>
            <Link v-if="can.create" :href="`${basePath}/create`" class="pc-btn pc-btn--primary">
                <UiIcon name="plus" :size="16" />
                {{ t('ui.cms_content_page.new_item') }}
            </Link>
        </template>

        <div class="pc-stack pc-content-page pcdr-enter">
            <section class="pc-content-stats" :aria-label="t('ui.cms_metrics')">
                <MetricCard
                    v-for="stat in stats"
                    :key="stat.key"
                    :label="stat.label"
                    :value="stat.value"
                    :icon="stat.icon"
                    :tone="stat.tone"
                />
            </section>

            <ContentFilterBar
                :search="searchTerm"
                :status="statusFilter"
                :sort="sortBy"
                :statuses="statuses"
                :sort-options="sortOptions"
                :groups="filterOptions"
                :filters="groupFilters"
                :has-filters="filtered"
                :processing="processing || page.processing"
                @update:search="searchTerm = $event; queueSearch()"
                @update:status="statusFilter = $event; applyFilters(true)"
                @update:sort="sortBy = $event; applyFilters(true)"
                @update:group="applyGroup"
                @clear="clearFilters"
            />

            <section class="pc-card pc-content-collection">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ title }}</h2>
                        <p class="pc-card__subtitle">{{ description }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ pagination.total ?? items.length }}</span>
                </div>

                <div v-if="processing" class="pc-content-loading" role="status">
                    <span class="pc-btn__spinner" aria-hidden="true" />
                    {{ t('ui.cms_content_page.loading') }}
                </div>

                <template v-if="items.length">
                    <div class="pc-content-desktop">
                        <DataTable :columns="columns">
                            <tr v-for="item in items" :key="item.id">
                                <td class="pc-table__primary">
                                    <Link :href="`${basePath}/${item.id}/edit`" class="pc-link">
                                        {{ item.title || `#${item.id}` }}
                                    </Link>
                                    <span v-if="item.summary" class="pc-table__secondary">{{ item.summary }}</span>
                                </td>
                                <td>
                                    <div class="pc-table__actions">
                                        <span v-if="item.isRestricted" class="pc-badge pc-badge--sand">
                                            <UiIcon name="lock" :size="12" />
                                            {{ t('cms_restricted_file') }}
                                        </span>
                                        <span v-if="item.hasDocument" class="pc-badge pc-badge--teal">
                                            <UiIcon name="file" :size="12" />
                                            {{ t('cms_media_type_document') }}
                                        </span>
                                    </div>
                                    <span v-if="itemMeta(item)" class="pc-content-meta">{{ itemMeta(item) }}</span>
                                    <span v-if="dateRange(item)" class="pc-table__secondary pc-directional" dir="ltr">{{ dateRange(item) }}</span>
                                    <span v-if="item.tags?.length" class="pc-table__secondary">{{ item.tags.join(' · ') }}</span>
                                </td>
                                <td><CmsStatusBadge :status="item.status" /></td>
                                <td>
                                    <div class="pc-table__actions">
                                        <span v-for="code in item.locales" :key="code" class="pc-badge pc-badge--slate">{{ String(code).toUpperCase() }}</span>
                                    </div>
                                </td>
                                <td class="pc-table__secondary">{{ formatDate(item.updatedAt) }}</td>
                                <td>
                                    <div class="pc-table__actions pc-content-actions">
                                        <div v-if="actionsFor(item).length" class="pc-content-actions__status">
                                            <StatusSelect
                                                :model-value="item.status"
                                                :aria-label="`${t('ui.cms_content_page.workflow')}: ${item.title || item.id}`"
                                                :options="statusOptionsFor(item)"
                                                :disabled="transitionBusy || page.processing"
                                                size="sm"
                                                @update:model-value="changeStatus(item, $event)"
                                            />
                                        </div>
                                        <Link :href="previewPath(item)" class="pc-btn pc-btn--ghost pc-btn--sm">
                                            <UiIcon name="eye" :size="14" />
                                            {{ t('ui.cms_content_page.preview') }}
                                        </Link>
                                        <Link
                                            v-if="can.update"
                                            :href="`${basePath}/${item.id}/edit`"
                                            class="pc-btn pc-btn--ghost pc-btn--sm"
                                        >
                                            <UiIcon name="edit" :size="14" />
                                        </Link>
                                        <button
                                            v-if="can.delete"
                                            type="button"
                                            class="pc-btn pc-btn--danger pc-btn--sm"
                                            :disabled="deleteBusy || page.processing"
                                            :aria-label="`${t('ui.cms_content_page.delete')}: ${item.title || item.id}`"
                                            @click="remove(item)"
                                        >
                                            <UiIcon name="trash" :size="14" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </DataTable>
                    </div>

                    <div class="pc-content-mobile">
                        <ContentCollectionCard
                            v-for="item in items"
                            :key="item.id"
                            :item="item"
                            :base-path="basePath"
                        >
                            <template #meta>
                                <span v-if="itemMeta(item)">{{ itemMeta(item) }}</span>
                                <span v-if="dateRange(item)" dir="ltr">{{ dateRange(item) }}</span>
                                <span>{{ t('ui.cms_content_page.locales') }}: {{ item.locales.map((code) => String(code).toUpperCase()).join(' · ') }}</span>
                                <span>{{ t('ui.cms_content_page.updated') }}: {{ formatDate(item.updatedAt) }}</span>
                            </template>
                            <template #actions>
                                <div v-if="actionsFor(item).length" class="pc-content-actions__status">
                                    <StatusSelect
                                        :model-value="item.status"
                                        :aria-label="`${t('ui.cms_content_page.workflow')}: ${item.title || item.id}`"
                                        :options="statusOptionsFor(item)"
                                        :disabled="transitionBusy || page.processing"
                                        size="sm"
                                        @update:model-value="changeStatus(item, $event)"
                                    />
                                </div>
                                <Link :href="previewPath(item)" class="pc-btn pc-btn--ghost pc-btn--sm">
                                    <UiIcon name="eye" :size="14" />
                                    {{ t('ui.cms_content_page.preview') }}
                                </Link>
                                <Link v-if="can.update" :href="`${basePath}/${item.id}/edit`" class="pc-btn pc-btn--ghost pc-btn--sm">
                                    <UiIcon name="edit" :size="14" />
                                    {{ t('ui.cms_content_page.edit') }}
                                </Link>
                                <button
                                    v-if="can.delete"
                                    type="button"
                                    class="pc-btn pc-btn--danger pc-btn--sm"
                                    :disabled="deleteBusy || page.processing"
                                    @click="remove(item)"
                                >
                                    <UiIcon name="trash" :size="14" />
                                    {{ t('ui.cms_content_page.delete') }}
                                </button>
                            </template>
                        </ContentCollectionCard>
                    </div>

                    <CmsPagination :pagination="pagination" :summary="summary" />
                </template>

                <EmptyState v-else :title="emptyTitle" :message="emptyMessage" icon="layers">
                    <button v-if="filtered" type="button" class="pc-btn pc-btn--secondary" @click="clearFilters">
                        {{ t('ui.cms_content_page.clear_filters') }}
                    </button>
                    <Link v-else-if="can.create" :href="`${basePath}/create`" class="pc-btn pc-btn--primary">
                        <UiIcon name="plus" :size="16" />
                        {{ t('ui.cms_content_page.new_item') }}
                    </Link>
                </EmptyState>
            </section>
        </div>
    </CmsDashboardLayout>
</template>
