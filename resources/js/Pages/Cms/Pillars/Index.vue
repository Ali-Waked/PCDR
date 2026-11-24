<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import MetricCard from '../../../Components/Dashboard/MetricCard.vue'
import StatusSelect from '../../../Components/Selection/StatusSelect.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import WorkflowAlert from '../../../Components/Dashboard/WorkflowAlert.vue'
import WorkflowTransitionDialog from '../../../Components/Dashboard/WorkflowTransitionDialog.vue'
import { useI18n } from '../../../composables/useI18n'
import { useAdminNotifications } from '../../../composables/useAdminNotifications'

const props = defineProps({
    resource: { type: String, default: 'pillars' },
    label: { type: String, default: '' },
    routeBase: { type: String, default: 'cms.pillars' },
    pageTitle: { type: String, default: '' },
    pageDescription: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({ search: '', status: 'all' }) },
    statuses: { type: Array, default: () => [] },
    workflow: { type: Object, default: () => ({ actions: {}, paths: {}, statusLabels: {} }) },
    can: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate, flash, page } = useI18n()
const { confirmDelete, finishConfirmation } = useAdminNotifications()

const basePath = computed(() => localPath('cms/pillars'))
const title = computed(() => props.pageTitle || props.label || t('ui.cms_pillars'))
const description = computed(() => props.pageDescription || t('ui.cms_pillars_page.description'))
const breadcrumb = computed(() => [
    { label: t('ui.cms_pillars_page.breadcrumb'), href: localPath('cms') },
    { label: title.value },
])

const searchTerm = ref(props.filters?.search ?? '')
const selectedStatus = ref(props.filters?.status ?? 'all')
const processing = ref(false)
const transitionBusy = ref(false)
const deleteBusy = ref(false)
const workflowOpen = ref(false)
const workflowItem = ref(null)
const selectedTarget = ref('')
let debounceTimer = null

const hasFilters = computed(() => Boolean(searchTerm.value.trim()) || selectedStatus.value !== 'all')
const filtered = computed(() => Boolean(props.filters?.search) || props.filters?.status !== 'all')

const statusOptions = computed(() => [
    { value: 'all', label: t('ui.cms_pillars_page.all_statuses'), description: '', tone: 'slate' },
    ...props.statuses.map((status) => ({
        value: status.value,
        label: status.label,
        description: status.description,
    })),
])

const stats = computed(() => [
    { key: 'all', label: t('ui.all'), value: props.counts?.all ?? 0, icon: 'layers', tone: 'teal' },
    { key: 'published', label: t('ui.published'), value: props.counts?.published ?? 0, icon: 'check', tone: 'teal' },
    { key: 'pending_review', label: t('ui.pending_review'), value: props.counts?.pending_review ?? 0, icon: 'clock', tone: 'sand' },
    { key: 'approved', label: t('ui.approved'), value: props.counts?.approved ?? 0, icon: 'shield', tone: 'navy' },
    { key: 'draft', label: t('ui.draft'), value: props.counts?.draft ?? 0, icon: 'file', tone: 'teal' },
    { key: 'archived', label: t('ui.archived'), value: props.counts?.archived ?? 0, icon: 'inbox', tone: 'navy' },
])

const columns = computed(() => [
    { key: 'code', label: t('ui.cms_pillars_page.code') },
    { key: 'pillar', label: t('ui.cms_pillars_page.pillar') },
    { key: 'status', label: t('ui.cms_pillars_page.status') },
    { key: 'locales', label: t('ui.cms_pillars_page.locales') },
    { key: 'updated', label: t('ui.cms_pillars_page.updated') },
    { key: 'actions', label: t('ui.cms_actions'), align: 'end' },
])

const summary = computed(() => translate('ui.cms_pillars_page.showing', {
    from: props.pagination?.from ?? 0,
    to: props.pagination?.to ?? 0,
    total: props.pagination?.total ?? 0,
}))

const emptyTitle = computed(() => filtered.value
    ? t('ui.cms_pillars_page.empty_filtered_title')
    : t('ui.cms_pillars_page.empty_title'))

const emptyMessage = computed(() => filtered.value
    ? t('ui.cms_pillars_page.empty_filtered_message')
    : t('ui.cms_pillars_page.empty_message'))

function translate(key, replacements = {}) {
    let value = String(t(key) ?? '')
    for (const [name, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${name}`, String(replacement ?? ''))
    }

    return value
}

function filterPayload() {
    const status = props.filters?.status ?? 'all'

    return {
        search: props.filters?.search ?? '',
        status: status === 'all' ? 'all' : status,
        page: props.pagination?.currentPage ?? 1,
    }
}

function applyFilters(resetPage = true) {
    window.clearTimeout(debounceTimer)

    const query = {}
    const search = searchTerm.value.trim()
    const status = selectedStatus.value

    if (search !== '') {
        query.search = search
    }
    if (status && status !== 'all') {
        query.status = status
    }
    if (! resetPage && Number(props.pagination?.currentPage ?? 1) > 1) {
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

function changeStatus(value) {
    selectedStatus.value = value ?? 'all'
    applyFilters(true)
}

function clearFilters() {
    searchTerm.value = ''
    selectedStatus.value = 'all'
    applyFilters(true)
}

function actionsFor(item) {
    return props.workflow?.actions?.[item.status] ?? []
}

function openWorkflow(item, target = '') {
    if (transitionBusy.value) {
        return
    }

    const actions = actionsFor(item)
    workflowItem.value = item
    selectedTarget.value = target && actions.some((action) => action.value === target)
        ? target
        : (actions[0]?.value ?? '')
    workflowOpen.value = true
}

function closeWorkflow() {
    if (transitionBusy.value) {
        return
    }
    workflowOpen.value = false
    workflowItem.value = null
    selectedTarget.value = ''
}

function submitTransition(target) {
    if (!workflowItem.value || !target || transitionBusy.value) {
        return
    }

    transitionBusy.value = true
    router.post(`${basePath.value}/${workflowItem.value.id}/status`, {
        status: target,
        filters: filterPayload(),
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            transitionBusy.value = false
            workflowOpen.value = false
            workflowItem.value = null
            selectedTarget.value = ''
        },
    })
}

async function remove(item) {
    if (! (await confirmDelete(item.title || `#${item.id}`, {
        message: t('ui.cms_pillars_page.delete_confirm'),
    }))) {
        return
    }

    deleteBusy.value = true
    router.delete(`${basePath.value}/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteBusy.value = false
            finishConfirmation()
        },
    })
}

watch(() => [props.filters?.search, props.filters?.status], ([search, status]) => {
    searchTerm.value = search ?? ''
    selectedStatus.value = status ?? 'all'
})

onBeforeUnmount(() => window.clearTimeout(debounceTimer))
</script>

<template>
    <CmsDashboardLayout
        :title="title"
        :subtitle="description"
        :breadcrumb="breadcrumb"
        :eyebrow="t('ui.cms_pillars_page.breadcrumb')"
    >
        <div class="pc-stack pc-pillars-page pcdr-enter">
            <WorkflowAlert :workflow="flash.workflow || {}" compact />

            <section class="pc-pillars-stats" :aria-label="t('ui.cms_pillars_page.workflow')">
                <MetricCard
                    v-for="stat in stats"
                    :key="stat.key"
                    :label="stat.label"
                    :value="stat.value"
                    :icon="stat.icon"
                    :tone="stat.tone"
                />
            </section>

            <div class="pc-pillars-toolbar">
                <label class="pc-pillars-search">
                    <span class="sr-only">{{ t('ui.cms_pillars_page.search') }}</span>
                    <UiIcon name="search" :size="17" />
                    <input
                        v-model="searchTerm"
                        type="search"
                        autocomplete="off"
                        :placeholder="t('ui.cms_pillars_page.search')"
                        :disabled="processing || page.processing"
                        @input="queueSearch"
                    >
                    <span v-if="processing" class="pc-pillars-search__spinner" aria-hidden="true" />
                </label>

                <StatusSelect
                    v-model="selectedStatus"
                    :label="t('ui.cms_pillars_page.status_filter')"
                    :options="statusOptions"
                    :placeholder="t('ui.cms_pillars_page.all_statuses')"
                    :empty-text="t('ui.no_results')"
                    :clear-label="t('ui.cms_pillars_page.clear_filters')"
                    :searchable="true"
                    clearable
                    :loading="processing"
                    :disabled="page.processing"
                    @update:model-value="changeStatus"
                />

                <button
                    type="button"
                    class="pc-btn pc-btn--secondary pc-pillars-clear"
                    :disabled="!hasFilters || processing || page.processing"
                    @click="clearFilters"
                >
                    <UiIcon name="refresh" :size="15" />
                    {{ t('ui.cms_pillars_page.clear_filters') }}
                </button>

                <Link v-if="can.create" :href="`${basePath}/create`" class="pc-btn pc-btn--primary pc-pillars-create">
                    <UiIcon name="plus" :size="16" />
                    {{ t('ui.cms_pillars_page.new_pillar') }}
                </Link>
            </div>

            <section class="pc-card pc-card--pillars">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ title }}</h2>
                        <p class="pc-card__subtitle">{{ description }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ pagination.total ?? items.length }}</span>
                </div>

                <div v-if="processing" class="pc-pillars-loading" role="status">
                    <span class="pc-btn__spinner" aria-hidden="true" />
                    {{ t('ui.cms_pillars_page.loading') }}
                </div>

                <template v-if="items.length">
                    <div class="pc-pillars-desktop">
                        <DataTable :columns="columns">
                            <tr v-for="item in items" :key="item.id">
                                <td class="pc-directional pc-table__secondary">{{ item.code }}</td>
                                <td class="pc-table__primary">
                                    <Link :href="`${basePath}/${item.id}/edit`" class="pc-link">{{ item.title || `#${item.id}` }}</Link>
                                    <span v-if="item.description" class="pc-table__secondary">{{ item.description }}</span>
                                    <span v-if="item.isFeatured" class="pc-badge pc-badge--sand pc-pillars-featured">
                                        {{ t('ui.featured_image') }}
                                    </span>
                                </td>
                                <td><CmsStatusBadge :status="item.status" /></td>
                                <td>
                                    <div class="pc-table__actions pc-pillars-locales">
                                        <span v-for="code in item.locales" :key="code" class="pc-badge pc-badge--slate">{{ String(code).toUpperCase() }}</span>
                                    </div>
                                </td>
                                <td class="pc-table__secondary">{{ formatDate(item.updatedAt) }}</td>
                                <td>
                                    <div class="pc-table__actions pc-pillars-actions">
                                        <button
                                            v-if="item.nextAction"
                                            type="button"
                                            class="pc-btn pc-btn--secondary pc-btn--sm"
                                            :disabled="transitionBusy || page.processing"
                                            @click="openWorkflow(item, item.nextAction.value)"
                                        >
                                            <UiIcon name="refresh" :size="14" />
                                            {{ item.nextAction.label }}
                                        </button>
                                        <button
                                            type="button"
                                            class="pc-btn pc-btn--ghost pc-btn--sm"
                                            :disabled="transitionBusy || page.processing"
                                            :aria-label="t('ui.cms_pillars_page.workflow')"
                                            @click="openWorkflow(item)"
                                        >
                                            <UiIcon name="layers" :size="14" />
                                        </button>
                                        <Link
                                            v-if="can.update"
                                            :href="`${basePath}/${item.id}/edit`"
                                            class="pc-btn pc-btn--ghost pc-btn--sm"
                                            :aria-label="t('ui.cms_pillars_page.edit')"
                                        >
                                            <UiIcon name="edit" :size="14" />
                                        </Link>
                                        <button
                                            v-if="can.delete"
                                            type="button"
                                            class="pc-btn pc-btn--danger pc-btn--sm"
                                            :disabled="deleteBusy || page.processing"
                                            :aria-label="t('ui.cms_pillars_page.delete')"
                                            @click="remove(item)"
                                        >
                                            <UiIcon name="trash" :size="14" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </DataTable>
                    </div>

                    <div class="pc-pillars-mobile">
                        <article v-for="item in items" :key="item.id" class="pc-pillars-card">
                            <div class="pc-pillars-card__head">
                                <div>
                                    <span class="pc-pillars-card__code pc-directional">{{ item.code }}</span>
                                    <Link :href="`${basePath}/${item.id}/edit`" class="pc-pillars-card__title">{{ item.title || `#${item.id}` }}</Link>
                                </div>
                                <CmsStatusBadge :status="item.status" />
                            </div>
                            <p v-if="item.description" class="pc-pillars-card__description">{{ item.description }}</p>
                            <div class="pc-pillars-card__meta">
                                <span>{{ t('ui.cms_pillars_page.locales') }}: {{ item.locales.map((code) => String(code).toUpperCase()).join(' · ') }}</span>
                                <span>{{ t('ui.cms_pillars_page.updated') }}: {{ formatDate(item.updatedAt) }}</span>
                            </div>
                            <div class="pc-pillars-card__actions">
                                <button
                                    v-if="item.nextAction"
                                    type="button"
                                    class="pc-btn pc-btn--secondary pc-btn--sm"
                                    :disabled="transitionBusy || page.processing"
                                    @click="openWorkflow(item, item.nextAction.value)"
                                >
                                    <UiIcon name="refresh" :size="14" />
                                    {{ item.nextAction.label }}
                                </button>
                                <button
                                    type="button"
                                    class="pc-btn pc-btn--ghost pc-btn--sm"
                                    :disabled="transitionBusy || page.processing"
                                    @click="openWorkflow(item)"
                                >
                                    <UiIcon name="layers" :size="14" />
                                    {{ t('ui.cms_pillars_page.workflow') }}
                                </button>
                                <Link
                                    v-if="can.update"
                                    :href="`${basePath}/${item.id}/edit`"
                                    class="pc-btn pc-btn--ghost pc-btn--sm"
                                >
                                    <UiIcon name="edit" :size="14" />
                                    {{ t('ui.cms_pillars_page.edit') }}
                                </Link>
                                <button
                                    v-if="can.delete"
                                    type="button"
                                    class="pc-btn pc-btn--danger pc-btn--sm"
                                    :disabled="deleteBusy || page.processing"
                                    @click="remove(item)"
                                >
                                    <UiIcon name="trash" :size="14" />
                                    {{ t('ui.cms_pillars_page.delete') }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <CmsPagination :pagination="pagination" :summary="summary" />
                </template>

                <EmptyState
                    v-else
                    :title="emptyTitle"
                    :message="emptyMessage"
                    icon="layers"
                >
                    <button v-if="filtered" type="button" class="pc-btn pc-btn--secondary" @click="clearFilters">
                        {{ t('ui.cms_pillars_page.clear_filters') }}
                    </button>
                    <Link v-else-if="can.create" :href="`${basePath}/create`" class="pc-btn pc-btn--primary">
                        <UiIcon name="plus" :size="16" />
                        {{ t('ui.cms_pillars_page.new_pillar') }}
                    </Link>
                </EmptyState>
            </section>
        </div>

        <WorkflowTransitionDialog
            :open="workflowOpen"
            :item="workflowItem"
            :actions="workflowItem ? actionsFor(workflowItem) : []"
            :paths="workflow.paths || {}"
            :status-labels="workflow.statusLabels || {}"
            :selected="selectedTarget"
            :busy="transitionBusy"
            :confirm-label="t('ui.cms_pillars_page.confirm')"
            :cancel-label="t('ui.cancel')"
            :title-label="t('ui.cms_pillars_page.workflow')"
            :sequence-label="workflow.sequenceLabel || t('ui.cms_pillars_page.workflow_sequence')"
            :forbidden-hint="workflow.forbiddenHint || t('ui.cms_pillars_page.forbidden_hint')"
            :no-action-label="t('ui.cms_pillars_page.no_next_action')"
            @close="closeWorkflow"
            @update:selected="selectedTarget = $event"
            @confirm="submitTransition"
        />
    </CmsDashboardLayout>
</template>
