<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import ContentMediaField from '../../../Components/Cms/ContentMediaField.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useAdminNotifications } from '../../../composables/useAdminNotifications'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    view: { type: String, default: 'partners' },
    items: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    media: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    form: { type: Object, default: () => ({}) },
    editing: { type: [Number, String], default: null },
    can: { type: Object, default: () => ({}) },
})

const { t, localPath } = useI18n()
const { confirm, finishConfirmation, setConfirmationBusy } = useAdminNotifications()

function clone(value) {
    return JSON.parse(JSON.stringify(value ?? {}))
}

function formState(value) {
    const state = clone(value)
    state.translations = {
        ar: { ...(state.translations?.ar ?? {}) },
        en: { ...(state.translations?.en ?? {}) },
    }

    return state
}

const form = useForm(formState(props.form))
const basePath = computed(() => localPath('cms/partner-directory'))
const categoriesPath = computed(() => `${basePath.value}/categories`)
const isCategories = computed(() => props.view === 'categories')
const isEditing = computed(() => props.editing !== null && props.editing !== 'new')

const breadcrumb = computed(() => [
    { label: t('ui.cms_dashboard'), href: localPath('cms') },
    { label: t('ui.partner_directory.cms_page_title') },
])

const partnerColumns = computed(() => [
    { key: 'name', label: t('ui.partner_directory.name') },
    { key: 'category', label: t('ui.partner_directory.category') },
    { key: 'status', label: t('ui.partner_directory.status') },
    { key: 'year', label: t('ui.partner_directory.year') },
    { key: 'actions', label: t('ui.partner_directory.actions'), align: 'end' },
])

const categoryColumns = computed(() => [
    { key: 'name', label: t('ui.partner_directory.name') },
    { key: 'status', label: t('ui.partner_directory.status') },
    { key: 'partners', label: t('ui.partner_directory.partners_tab') },
    { key: 'order', label: t('ui.partner_directory.order') },
    { key: 'actions', label: t('ui.partner_directory.actions'), align: 'end' },
])

const statusOptions = computed(() => [
    { value: 'all', label: t('ui.partner_directory.all_statuses') },
    { value: 'draft', label: t('draft') },
    { value: 'published', label: t('published') },
    { value: 'archived', label: t('archived') },
])

const categoryFilterOptions = computed(() => [
    { value: '', label: t('ui.partner_directory.all_categories') },
    ...props.categories.map((category) => ({ value: category.slug, label: category.label })),
])

const statusChips = computed(() => [
    { key: 'all', label: t('ui.all'), value: props.counts?.all ?? 0 },
    { key: 'published', label: t('published'), value: props.counts?.published ?? 0 },
    { key: 'draft', label: t('draft'), value: props.counts?.draft ?? 0 },
    { key: 'archived', label: t('archived'), value: props.counts?.archived ?? 0 },
])

const summary = computed(() => {
    let value = String(t('ui.cms_content_page.showing') ?? '')
    for (const [name, replacement] of Object.entries({
        from: props.pagination?.from ?? 0,
        to: props.pagination?.to ?? 0,
        total: props.pagination?.total ?? 0,
    })) {
        value = value.replaceAll(`:${name}`, String(replacement))
    }

    return value
})

const searchTerm = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? 'all')
const categoryFilter = ref(props.filters?.category ?? '')
const processing = ref(false)
const actionBusy = ref(false)
const formOpen = ref(props.editing !== null)
const reorderDrafts = ref({})
let debounceTimer = null

watch(() => props.form, (value) => {
    Object.assign(form, formState(value))
}, { deep: true })

watch(() => props.editing, (value) => {
    formOpen.value = value !== null
}, { immediate: true })

watch(() => props.filters, (filters) => {
    searchTerm.value = filters?.search ?? ''
    statusFilter.value = filters?.status ?? 'all'
    categoryFilter.value = filters?.category ?? ''
}, { deep: true })

watch(() => props.items, (items) => {
    const drafts = {}
    for (const item of items) {
        drafts[item.id] = item.displayOrder
    }
    reorderDrafts.value = drafts
}, { deep: true, immediate: true })

function fieldError(path) {
    return form.errors?.[path] ?? ''
}

function translate(key, replacements = {}) {
    let value = String(t(key) ?? '')
    for (const [name, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${name}`, String(replacement ?? ''))
    }

    return value
}

function applyFilters() {
    if (isCategories.value) {
        return
    }

    window.clearTimeout(debounceTimer)

    const query = {}
    const search = String(searchTerm.value).trim()
    if (search !== '') {
        query.search = search
    }
    if (statusFilter.value && statusFilter.value !== 'all') {
        query.status = statusFilter.value
    }
    if (categoryFilter.value) {
        query.category = categoryFilter.value
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
    debounceTimer = window.setTimeout(() => applyFilters(), 350)
}

function clearFilters() {
    searchTerm.value = ''
    statusFilter.value = 'all'
    categoryFilter.value = ''
    applyFilters()
}

function submitForm() {
    if (isCategories.value) {
        const url = isEditing.value
            ? `${basePath.value}/categories/${props.editing}`
            : `${basePath.value}/categories`
        form[isEditing.value ? 'put' : 'post'](url, {
            preserveScroll: true,
            onSuccess: () => { formOpen.value = false },
        })

        return
    }

    const url = isEditing.value
        ? `${basePath.value}/partners/${props.editing}`
        : `${basePath.value}/partners`
    form[isEditing.value ? 'put' : 'post'](url, {
        preserveScroll: true,
        onSuccess: () => { formOpen.value = false },
    })
}

function setPartnerStatus(row, status) {
    actionBusy.value = true
    router.post(`${basePath.value}/partners/${row.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => { actionBusy.value = false },
    })
}

function setCategoryStatus(row) {
    actionBusy.value = true
    router.post(`${basePath.value}/categories/${row.id}/status`, {
        is_active: ! row.isActive,
    }, {
        preserveScroll: true,
        onFinish: () => { actionBusy.value = false },
    })
}

function updateOrder(row) {
    const segment = isCategories.value ? 'categories' : 'partners'
    actionBusy.value = true
    router.post(`${basePath.value}/${segment}/reorder`, {
        id: row.id,
        display_order: Number(reorderDrafts.value[row.id] ?? row.displayOrder),
    }, {
        preserveScroll: true,
        onFinish: () => { actionBusy.value = false },
    })
}

function requestDelete(row) {
    const message = isCategories.value
        ? t('ui.partner_directory.confirm_delete_category')
        : t('ui.partner_directory.confirm_delete_partner')

    confirm({ message, danger: true }).then((accepted) => {
        if (! accepted) {
            return
        }

        setConfirmationBusy(true)
        actionBusy.value = true
        const segment = isCategories.value ? 'categories' : 'partners'
        router.delete(`${basePath.value}/${segment}/${row.id}`, {
            preserveScroll: true,
            onFinish: () => {
                actionBusy.value = false
                setConfirmationBusy(false)
                finishConfirmation()
            },
        })
    })
}

onBeforeUnmount(() => window.clearTimeout(debounceTimer))
</script>

<template>
    <CmsDashboardLayout
        :title="t('ui.partner_directory.cms_page_title')"
        :subtitle="t('ui.partner_directory.cms_page_description')"
        :eyebrow="t('ui.dashboard_content_studio')"
        :breadcrumb="breadcrumb"
    >
        <div class="pc-stack pdd-page pcdr-enter">
            <section class="pc-card pdd-notice">
                <div class="pdd-notice__icon" aria-hidden="true">
                    <UiIcon name="info" :size="18" />
                </div>
                <p>{{ t('ui.partner_directory.cms_disclaimer') }}</p>
            </section>

            <section class="pc-card">
                <div class="pc-card__head pdd-head">
                    <div>
                        <h2 class="pc-card__title">{{ t('ui.partner_directory.cms_page_title') }}</h2>
                        <p class="pc-card__subtitle">{{ summary }}</p>
                    </div>

                    <div class="pdd-head__actions">
                        <Link
                            v-if="can.create"
                            :href="`${basePath}/partners/create`"
                            class="pc-btn pc-btn--primary pc-btn--sm"
                        >
                            <UiIcon name="plus" :size="14" />
                            {{ t('ui.partner_directory.new_partner') }}
                        </Link>
                        <Link
                            v-if="can.create"
                            :href="`${basePath}/categories/create`"
                            class="pc-btn pc-btn--secondary pc-btn--sm"
                        >
                            <UiIcon name="layers" :size="14" />
                            {{ t('ui.partner_directory.new_category') }}
                        </Link>
                    </div>
                </div>

                <div class="pdd-tabs" role="tablist" :aria-label="t('ui.partner_directory.cms_page_title')">
                    <Link
                        :href="basePath"
                        class="pdd-tab"
                        :class="{ 'is-active': !isCategories }"
                        :aria-current="!isCategories ? 'page' : undefined"
                        role="tab"
                        :aria-selected="!isCategories ? 'true' : 'false'"
                    >
                        {{ t('ui.partner_directory.partners_tab') }}
                    </Link>
                    <Link
                        :href="categoriesPath"
                        class="pdd-tab"
                        :class="{ 'is-active': isCategories }"
                        :aria-current="isCategories ? 'page' : undefined"
                        role="tab"
                        :aria-selected="isCategories ? 'true' : 'false'"
                    >
                        {{ t('ui.partner_directory.categories_tab') }}
                    </Link>
                </div>

                <template v-if="!isCategories">
                    <div class="pc-toolbar pdd-toolbar">
                        <label class="pc-search">
                            <span class="sr-only">{{ t('ui.partner_directory.search') }}</span>
                            <UiIcon name="search" :size="16" />
                            <input
                                v-model="searchTerm"
                                type="search"
                                autocomplete="off"
                                :placeholder="t('ui.partner_directory.search')"
                                :disabled="processing || actionBusy || form.processing"
                                @input="queueSearch"
                            >
                        </label>

                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.partner_directory.status') }}</span>
                            <select
                                v-model="statusFilter"
                                class="pc-select pc-select--sm"
                                :disabled="processing || actionBusy || form.processing"
                                @change="applyFilters"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.partner_directory.category') }}</span>
                            <select
                                v-model="categoryFilter"
                                class="pc-select pc-select--sm"
                                :disabled="processing || actionBusy || form.processing"
                                @change="applyFilters"
                            >
                                <option v-for="option in categoryFilterOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <button
                            v-if="searchTerm || statusFilter !== 'all' || categoryFilter"
                            class="pc-btn pc-btn--ghost pc-btn--sm"
                            type="button"
                            :disabled="processing || actionBusy || form.processing"
                            @click="clearFilters"
                        >
                            <UiIcon name="refresh" :size="14" />
                            {{ t('ui.clear') }}
                        </button>
                    </div>

                    <div class="pdd-chips" :aria-label="t('ui.partner_directory.status')">
                        <span
                            v-for="chip in statusChips"
                            :key="chip.key"
                            class="pc-badge"
                            :class="{ 'pc-badge--teal': chip.key === 'published' }"
                        >
                            {{ chip.label }} · {{ chip.value }}
                        </span>
                    </div>
                </template>

                <div v-if="processing || actionBusy || form.processing" class="pdd-loading" role="status">
                    <span class="pc-btn__spinner" aria-hidden="true" />
                    {{ t('ui.loading') }}
                </div>

                <template v-if="!isCategories">
                    <DataTable
                        v-if="items.length"
                        :columns="partnerColumns"
                        :empty-title="t('ui.partner_directory.no_partners')"
                    >
                        <tr v-for="row in items" :key="row.id">
                            <td>
                                <div class="pdd-identity">
                                    <span class="pdd-thumb">
                                        <img v-if="row.logoUrl" :src="row.logoUrl" :alt="row.name" loading="lazy">
                                        <UiIcon v-else name="image" :size="16" />
                                    </span>
                                    <span>
                                        <span class="pc-table__primary">{{ row.name }}</span>
                                        <span class="pc-table__secondary" dir="auto">{{ row.slug || `partner-${row.id}` }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>{{ row.category || t('ui.partner_directory.other_partners') }}</td>
                            <td><CmsStatusBadge :status="row.status" /></td>
                            <td>{{ row.year || '—' }}</td>
                            <td>
                                <div class="pc-table__actions pdd-actions">
                                    <Link
                                        v-if="can.update"
                                        :href="`${basePath}/partners/${row.id}/edit`"
                                        class="pc-btn pc-btn--secondary pc-btn--sm"
                                    >
                                        <UiIcon name="edit" :size="14" />
                                        <span class="pdd-action-label">{{ t('ui.edit') }}</span>
                                    </Link>
                                    <button
                                        v-if="can.publish && row.status !== 'published'"
                                        class="pc-btn pc-btn--ghost pc-btn--sm"
                                        type="button"
                                        :disabled="actionBusy"
                                        @click="setPartnerStatus(row, 'published')"
                                    >
                                        <UiIcon name="eye" :size="14" />
                                        {{ t('ui.partner_directory.publish') }}
                                    </button>
                                    <button
                                        v-if="can.unpublish && row.status === 'published'"
                                        class="pc-btn pc-btn--ghost pc-btn--sm"
                                        type="button"
                                        :disabled="actionBusy"
                                        @click="setPartnerStatus(row, 'draft')"
                                    >
                                        <UiIcon name="eyeOff" :size="14" />
                                        {{ t('ui.partner_directory.unpublish') }}
                                    </button>
                                    <form class="pdd-order" @submit.prevent="updateOrder(row)">
                                        <label class="sr-only" :for="`partner-order-${row.id}`">
                                            {{ t('ui.partner_directory.order') }}
                                        </label>
                                        <input
                                            :id="`partner-order-${row.id}`"
                                            v-model.number="reorderDrafts[row.id]"
                                            class="pc-input pdd-order__input"
                                            type="number"
                                            min="0"
                                            max="100000"
                                            :disabled="!can.update || actionBusy"
                                        >
                                        <button
                                            class="pc-btn pc-btn--ghost pc-btn--sm"
                                            type="submit"
                                            :disabled="!can.update || actionBusy"
                                        >
                                            {{ t('ui.partner_directory.reorder') }}
                                        </button>
                                    </form>
                                    <button
                                        v-if="can.delete"
                                        class="pc-btn pc-btn--danger pc-btn--sm"
                                        type="button"
                                        :disabled="actionBusy"
                                        @click="requestDelete(row)"
                                    >
                                        <UiIcon name="trash" :size="14" />
                                        <span class="pdd-action-label">{{ t('ui.delete') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState
                        v-else
                        :title="t('ui.partner_directory.no_partners')"
                        :message="t('ui.partner_directory.search')"
                        icon="image"
                    />
                </template>

                <template v-else>
                    <DataTable
                        v-if="items.length"
                        :columns="categoryColumns"
                        :empty-title="t('ui.partner_directory.no_categories')"
                    >
                        <tr v-for="row in items" :key="row.id">
                            <td>
                                <span class="pc-table__primary">{{ row.name }}</span>
                                <span class="pc-table__secondary" dir="auto">{{ row.slug }}</span>
                            </td>
                            <td>
                                <CmsStatusBadge
                                    :status="row.isActive ? 'active' : 'inactive'"
                                    :label="row.isActive ? t('ui.partner_directory.active') : t('inactive')"
                                />
                            </td>
                            <td>{{ row.partnerCount }}</td>
                            <td>{{ row.displayOrder }}</td>
                            <td>
                                <div class="pc-table__actions pdd-actions">
                                    <Link
                                        v-if="can.update"
                                        :href="`${basePath}/categories/${row.id}/edit`"
                                        class="pc-btn pc-btn--secondary pc-btn--sm"
                                    >
                                        <UiIcon name="edit" :size="14" />
                                        <span class="pdd-action-label">{{ t('ui.edit') }}</span>
                                    </Link>
                                    <button
                                        v-if="can.update"
                                        class="pc-btn pc-btn--ghost pc-btn--sm"
                                        type="button"
                                        :disabled="actionBusy"
                                        @click="setCategoryStatus(row)"
                                    >
                                        <UiIcon :name="row.isActive ? 'eyeOff' : 'eye'" :size="14" />
                                        {{ row.isActive ? t('ui.partner_directory.deactivate') : t('ui.partner_directory.activate') }}
                                    </button>
                                    <form class="pdd-order" @submit.prevent="updateOrder(row)">
                                        <label class="sr-only" :for="`category-order-${row.id}`">
                                            {{ t('ui.partner_directory.order') }}
                                        </label>
                                        <input
                                            :id="`category-order-${row.id}`"
                                            v-model.number="reorderDrafts[row.id]"
                                            class="pc-input pdd-order__input"
                                            type="number"
                                            min="0"
                                            max="100000"
                                            :disabled="!can.update || actionBusy"
                                        >
                                        <button
                                            class="pc-btn pc-btn--ghost pc-btn--sm"
                                            type="submit"
                                            :disabled="!can.update || actionBusy"
                                        >
                                            {{ t('ui.partner_directory.reorder') }}
                                        </button>
                                    </form>
                                    <button
                                        v-if="can.delete"
                                        class="pc-btn pc-btn--danger pc-btn--sm"
                                        type="button"
                                        :disabled="actionBusy || row.partnerCount > 0"
                                        :title="row.partnerCount > 0 ? t('ui.partner_directory.category_has_partners') : undefined"
                                        @click="requestDelete(row)"
                                    >
                                        <UiIcon name="trash" :size="14" />
                                        <span class="pdd-action-label">{{ t('ui.delete') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState
                        v-else
                        :title="t('ui.partner_directory.no_categories')"
                        :message="t('ui.partner_directory.cms_page_description')"
                        icon="layers"
                    />
                </template>

                <CmsPagination
                    :pagination="pagination"
                    :summary="summary"
                    page-key="page"
                />
            </section>

            <section v-if="formOpen" class="pc-card pdd-form" :aria-label="isCategories ? t('ui.partner_directory.edit_category') : t('ui.partner_directory.edit_partner')">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">
                            {{ isCategories ? t('ui.partner_directory.edit_category') : t('ui.partner_directory.edit_partner') }}
                        </h2>
                        <p class="pc-card__subtitle">{{ t('ui.partner_directory.cms_disclaimer') }}</p>
                    </div>
                    <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="formOpen = false">
                        <UiIcon name="close" :size="14" />
                        {{ t('cms_close') }}
                    </button>
                </div>

                <form class="pdd-form__body" @submit.prevent="submitForm">
                    <template v-if="!isCategories">
                        <div class="pdd-form__grid">
                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.category') }}</span>
                                <select
                                    v-model="form.directory_partner_category_id"
                                    class="pc-select"
                                    :aria-invalid="fieldError('directory_partner_category_id') ? 'true' : undefined"
                                >
                                    <option value="">{{ t('ui.partner_directory.all_categories') }}</option>
                                    <option v-for="category in categories" :key="category.value" :value="category.value">
                                        {{ category.label }}
                                    </option>
                                </select>
                                <span v-if="fieldError('directory_partner_category_id')" class="pc-hint pc-hint--error">
                                    {{ fieldError('directory_partner_category_id') }}
                                </span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.status') }}</span>
                                <select v-model="form.status" class="pc-select">
                                    <option v-for="option in statusOptions.slice(1)" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <span v-if="fieldError('status')" class="pc-hint pc-hint--error">{{ fieldError('status') }}</span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.website') }}</span>
                                <input
                                    v-model="form.website_url"
                                    class="pc-input"
                                    type="url"
                                    autocomplete="off"
                                    dir="ltr"
                                    :placeholder="t('ui.partner_directory.website_hint')"
                                    :aria-invalid="fieldError('website_url') ? 'true' : undefined"
                                >
                                <span v-if="fieldError('website_url')" class="pc-hint pc-hint--error">{{ fieldError('website_url') }}</span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.year') }}</span>
                                <input
                                    v-model="form.partnership_year"
                                    class="pc-input"
                                    type="number"
                                    min="1900"
                                    :max="new Date().getFullYear() + 1"
                                    :placeholder="t('ui.partner_directory.year_hint')"
                                    :aria-invalid="fieldError('partnership_year') ? 'true' : undefined"
                                >
                                <span v-if="fieldError('partnership_year')" class="pc-hint pc-hint--error">{{ fieldError('partnership_year') }}</span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.order') }}</span>
                                <input v-model.number="form.display_order" class="pc-input" type="number" min="0" max="100000">
                                <span class="pc-hint">{{ t('ui.partner_directory.order_hint') }}</span>
                            </label>

                            <label v-if="form.status === 'published'" class="pc-field">
                                <span class="pc-label">{{ t('published_on') }}</span>
                                <input v-model="form.published_at" class="pc-input" type="date">
                            </label>
                        </div>

                        <ContentMediaField
                            v-model="form.logo_media_id"
                            :label="t('ui.partner_directory.logo')"
                            :options="media"
                            :labels="{
                                select: t('ui.partner_directory.logo'),
                                change: t('ui.edit'),
                                remove: t('cms_remove_item'),
                                title: t('cms_media_picker'),
                                insert: t('ui.select'),
                                empty: t('cms_select_media'),
                                close: t('cms_close'),
                            }"
                        />
                        <p class="pc-hint">{{ t('ui.partner_directory.logo_hint') }}</p>
                    </template>

                    <template v-else>
                        <div class="pdd-form__grid">
                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.slug') }}</span>
                                <input
                                    v-model="form.slug"
                                    class="pc-input"
                                    dir="ltr"
                                    autocomplete="off"
                                    :aria-invalid="fieldError('slug') ? 'true' : undefined"
                                >
                                <span class="pc-hint">{{ t('ui.partner_directory.slug_hint') }}</span>
                                <span v-if="fieldError('slug')" class="pc-hint pc-hint--error">{{ fieldError('slug') }}</span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.partner_directory.order') }}</span>
                                <input v-model.number="form.display_order" class="pc-input" type="number" min="0" max="100000">
                                <span class="pc-hint">{{ t('ui.partner_directory.order_hint') }}</span>
                            </label>
                        </div>

                        <label class="pc-check">
                            <input v-model="form.is_active" type="checkbox">
                            <span>{{ t('ui.partner_directory.active') }}</span>
                        </label>
                    </template>

                    <div class="pdd-translations">
                        <div
                            v-for="locale in ['ar', 'en']"
                            :key="locale"
                            class="pdd-translation"
                            :lang="locale"
                            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
                        >
                            <h3 class="pc-card__title">{{ locale === 'ar' ? t('ui.partner_directory.arabic_name') : t('ui.partner_directory.english_name') }}</h3>

                            <label class="pc-field">
                                <span class="pc-label">
                                    {{ locale === 'ar' ? t('ui.partner_directory.arabic_name') : t('ui.partner_directory.english_name') }}
                                </span>
                                <input
                                    v-model="form.translations[locale].name"
                                    class="pc-input"
                                    :aria-invalid="fieldError(`translations.${locale}.name`) ? 'true' : undefined"
                                >
                                <span v-if="fieldError(`translations.${locale}.name`)" class="pc-hint pc-hint--error">
                                    {{ fieldError(`translations.${locale}.name`) }}
                                </span>
                            </label>

                            <label v-if="!isCategories" class="pc-field">
                                <span class="pc-label">
                                    {{ locale === 'ar' ? t('ui.partner_directory.arabic_slug') : t('ui.partner_directory.english_slug') }}
                                </span>
                                <input
                                    v-model="form.translations[locale].slug"
                                    class="pc-input"
                                    dir="ltr"
                                    autocomplete="off"
                                    :aria-invalid="fieldError(`translations.${locale}.slug`) ? 'true' : undefined"
                                >
                                <span v-if="fieldError(`translations.${locale}.slug`)" class="pc-hint pc-hint--error">
                                    {{ fieldError(`translations.${locale}.slug`) }}
                                </span>
                            </label>

                            <label class="pc-field">
                                <span class="pc-label">
                                    {{ locale === 'ar' ? t('ui.partner_directory.arabic_description') : t('ui.partner_directory.english_description') }}
                                </span>
                                <textarea
                                    v-model="form.translations[locale].description"
                                    class="pc-input"
                                    rows="3"
                                    :aria-invalid="fieldError(`translations.${locale}.description`) ? 'true' : undefined"
                                />
                                <span v-if="fieldError(`translations.${locale}.description`)" class="pc-hint pc-hint--error">
                                    {{ fieldError(`translations.${locale}.description`) }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="pdd-form__footer">
                        <button class="pc-btn pc-btn--primary pc-btn--sm" type="submit" :disabled="form.processing">
                            <span v-if="form.processing" class="pc-btn__spinner" aria-hidden="true" />
                            {{ form.processing ? t('ui.partner_directory.saving') : t('cms_save') }}
                        </button>
                        <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="formOpen = false">
                            {{ t('ui.cancel') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </CmsDashboardLayout>
</template>
