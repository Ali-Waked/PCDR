<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import MetricCard from '../../../Components/Dashboard/MetricCard.vue'
import SelectionControl from '../../../Components/Selection/SelectionControl.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'
import { useAdminNotifications } from '../../../composables/useAdminNotifications'

const props = defineProps({
    items: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    filters: {
        type: Object,
        default: () => ({ type: '', visibility: '', q: '', sort: 'newest' }),
    },
    stats: { type: Object, default: () => ({ all: 0, images: 0, documents: 0, restricted: 0 }) },
    sortOptions: { type: Array, default: () => [] },
    can: {
        type: Object,
        default: () => ({ view: true, upload: false, update: false, delete: false }),
    },
})

const { t, localPath } = useI18n()
const { confirmDelete, finishConfirmation, notify } = useAdminNotifications()
const basePath = localPath('cms/media')

const searchQuery = ref(props.filters?.q || '')
const selectedType = ref(props.filters?.type || '')
const selectedVisibility = ref(props.filters?.visibility || '')
const selectedSort = ref(props.filters?.sort || 'newest')

const typeOptions = computed(() => [
    { value: 'image', label: t('cms_media_type_image', 'Image') },
    { value: 'document', label: t('cms_media_type_document', 'Document') },
])

const visibilityOptions = computed(() => [
    { value: 'public', label: t('cms_media_visibility_public', 'Public') },
    { value: 'restricted', label: t('cms_media_visibility_restricted', 'Restricted') },
])

const hasFilters = computed(() => Boolean(
    searchQuery.value || selectedType.value || selectedVisibility.value || selectedSort.value !== 'newest',
))

watch(() => props.filters, (filters) => {
    searchQuery.value = filters?.q || ''
    selectedType.value = filters?.type || ''
    selectedVisibility.value = filters?.visibility || ''
    selectedSort.value = filters?.sort || 'newest'
}, { deep: true })

function visitOptions() {
    return { preserveState: true, preserveScroll: true }
}

function submitFilters() {
    router.get(basePath, {
        q: searchQuery.value,
        type: selectedType.value,
        visibility: selectedVisibility.value,
        sort: selectedSort.value,
    }, visitOptions())
}

function clearFilters() {
    searchQuery.value = ''
    selectedType.value = ''
    selectedVisibility.value = ''
    selectedSort.value = 'newest'
    router.get(basePath, {}, visitOptions())
}

const upload = useForm({
    file: null,
    is_restricted: false,
    translations: {
        ar: { alt_text: '', caption: '' },
        en: { alt_text: '', caption: '' },
    },
})

const uploadAccept = computed(() => upload.is_restricted
    ? '.pdf,.doc,.docx,.xls,.xlsx,.txt'
    : '.jpg,.jpeg,.png,.webp')

const fileInput = ref(null)
const editing = ref(null)
const detailsId = ref(null)
const signedLinks = ref({})
const copiedId = ref(null)
const previewItem = ref(null)
const previewUrl = ref('')
const previewDialog = ref(null)
let previousFocus = null

watch(() => upload.is_restricted, () => {
    upload.file = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
})

const editForm = useForm({
    translations: {
        ar: { alt_text: '', caption: '' },
        en: { alt_text: '', caption: '' },
    },
})

function submitUpload() {
    if (! props.can.upload) {
        return
    }

    upload.post(basePath, {
        ...visitOptions(),
        onSuccess: () => {
            upload.reset()
            upload.is_restricted = false
            if (fileInput.value) {
                fileInput.value.value = ''
            }
        },
    })
}

function translatedValue(item, code, field) {
    return item.translations?.[code]?.[field] ?? (field === 'alt_text' ? item.alt : item.caption) ?? ''
}

function toggleDetails(item) {
    detailsId.value = detailsId.value === item.id ? null : item.id
    if (detailsId.value !== item.id) {
        editing.value = null
    }
}

function openEditor(item) {
    detailsId.value = item.id
    editing.value = item.id
    editForm.translations = {
        ar: {
            alt_text: translatedValue(item, 'ar', 'alt_text'),
            caption: translatedValue(item, 'ar', 'caption'),
        },
        en: {
            alt_text: translatedValue(item, 'en', 'alt_text'),
            caption: translatedValue(item, 'en', 'caption'),
        },
    }
}

function saveEditor() {
    editForm.put(`${basePath}/${editing.value}`, {
        ...visitOptions(),
        onSuccess: () => { editing.value = null },
    })
}

async function remove(item) {
    if (! (await confirmDelete(item.name || `#${item.id}`))) {
        return
    }

    router.delete(`${basePath}/${item.id}`, {
        ...visitOptions(),
        onFinish: () => {
            finishConfirmation()
            if (detailsId.value === item.id) {
                detailsId.value = null
            }
        },
    })
}

function signedLink(item) {
    if (signedLinks.value[item.id]) {
        return Promise.resolve(signedLinks.value[item.id])
    }

    return fetch(`${basePath}/${item.id}/signed`, { headers: { Accept: 'application/json' } })
        .then((response) => response.ok ? response.json() : Promise.reject(new Error('failed')))
        .then((data) => {
            signedLinks.value[item.id] = data.url
            return data.url
        })
        .catch(() => {
            signedLinks.value[item.id] = ''
            notify.error(t('ui.notification.server_message'), t('ui.notification.server_title'))
            return ''
        })
}

async function copyUrl(item) {
    const url = item.url || await signedLink(item)
    if (! url) {
        return
    }

    try {
        await navigator.clipboard.writeText(url)
        copiedId.value = item.id
        notify.success(t('cms_media_copied'))
    } catch {
        copiedId.value = null
        notify.error(t('cms_media_copy_failed'))
    }
}

function previewFocusables() {
    const root = previewDialog.value
    if (! root) {
        return []
    }

    return [...root.querySelectorAll('button:not([disabled]),[href],input:not([disabled]),[tabindex]:not([tabindex="-1"])')]
}

function onPreviewKeydown(event) {
    if (! previewUrl.value) {
        return
    }

    if (event.key === 'Escape') {
        event.preventDefault()
        closePreview()
        return
    }

    if (event.key !== 'Tab') {
        return
    }

    const items = previewFocusables()
    if (! items.length) {
        event.preventDefault()
        return
    }

    const first = items[0]
    const last = items[items.length - 1]

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
    } else if (! event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
    }
}

watch(previewUrl, (url) => {
    if (url) {
        previousFocus = document.activeElement
        document.addEventListener('keydown', onPreviewKeydown)
        requestAnimationFrame(() => (previewFocusables()[0] || previewDialog.value)?.focus?.())
        return
    }

    document.removeEventListener('keydown', onPreviewKeydown)
    previousFocus?.focus?.()
    previousFocus = null
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onPreviewKeydown)
    previousFocus?.focus?.()
})

async function openPreview(item) {
    if (! item.restricted && item.url) {
        previewItem.value = item
        previewUrl.value = item.url
        return
    }

    const url = await signedLink(item)
    if (! url) {
        return
    }

    previewItem.value = item
    previewUrl.value = url
}

function closePreview() {
    previewItem.value = null
    previewUrl.value = ''
}

function detailItem() {
    return props.items.find((item) => item.id === detailsId.value) || null
}
</script>

<template>
    <CmsDashboardLayout
        :title="t('ui.cms_media')"
        :subtitle="t('cms_upload_hint')"
        :eyebrow="t('ui.dashboard_editorial')"
    >
        <div class="pc-stack pcdr-enter">
            <div class="pc-metric-grid">
                <MetricCard :label="t('ui.all')" :value="stats.all ?? 0" icon="folder" tone="sand" />
                <MetricCard :label="t('cms_media_type_image')" :value="stats.images ?? 0" icon="image" />
                <MetricCard :label="t('cms_media_type_document')" :value="stats.documents ?? 0" icon="file" />
                <MetricCard :label="t('cms_restricted_file')" :value="stats.restricted ?? 0" icon="lock" tone="navy" />
            </div>

            <section v-if="can.upload" class="pc-card" aria-labelledby="media-upload-title">
                <div class="pc-card__head">
                    <div>
                        <h2 id="media-upload-title" class="pc-card__title">{{ t('cms_upload') }}</h2>
                        <p class="pc-card__subtitle">{{ t('cms_upload_hint') }}</p>
                    </div>
                </div>
                <form class="pc-card__body pc-form-grid" @submit.prevent="submitUpload">
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_upload') }} <span class="pc-required" aria-hidden="true">*</span></span>
                        <input
                            ref="fileInput"
                            class="pc-input"
                            type="file"
                            :accept="uploadAccept"
                            required
                            @change="upload.file = $event.target.files[0]"
                        >
                        <span v-if="upload.errors.file" class="pc-error" role="alert">{{ upload.errors.file }}</span>
                    </label>
                    <label class="pc-checkbox pc-field--action">
                        <input v-model="upload.is_restricted" type="checkbox">
                        <span>{{ t('cms_restricted_file') }}</span>
                    </label>
                    <label v-for="code in ['ar', 'en']" :key="`upload-alt-${code}`" class="pc-field">
                        <span class="pc-label">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.alt_text') }}</span>
                        <input v-model="upload.translations[code].alt_text" class="pc-input" type="text" maxlength="255">
                    </label>
                    <label v-for="code in ['ar', 'en']" :key="`upload-${code}`" class="pc-field">
                        <span class="pc-label">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.cms_description') }}</span>
                        <input v-model="upload.translations[code].caption" class="pc-input" type="text" maxlength="500">
                    </label>
                    <div class="pc-form-actions">
                        <button class="pc-btn pc-btn--primary" type="submit" :disabled="upload.processing" :aria-busy="upload.processing">
                            <UiIcon name="upload" :size="16" />
                            {{ t('cms_upload') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="pc-card" aria-labelledby="media-library-title">
                <div class="pc-card__head">
                    <div>
                        <h2 id="media-library-title" class="pc-card__title">{{ t('ui.cms_media') }}</h2>
                        <p class="pc-card__subtitle">{{ pagination.total ?? 0 }} {{ t('documents') }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ items.length }}</span>
                </div>

                <div class="pc-card__body pc-stack">
                    <form class="pc-form-grid" role="search" @submit.prevent="submitFilters">
                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.search') }}</span>
                            <input v-model="searchQuery" class="pc-input" type="search" name="q">
                        </label>
                        <div class="pc-field">
                            <SelectionControl
                                v-model="selectedType"
                                :label="t('cms_type', t('ui.status'))"
                                name="type"
                                :options="typeOptions"
                                :placeholder="t('ui.all')"
                                clearable
                                :searchable="false"
                            />
                        </div>
                        <div class="pc-field">
                            <SelectionControl
                                v-model="selectedVisibility"
                                :label="t('cms_media_visibility', 'Visibility')"
                                name="visibility"
                                :options="visibilityOptions"
                                :placeholder="t('ui.all')"
                                clearable
                                :searchable="false"
                            />
                        </div>
                        <div class="pc-field">
                            <SelectionControl
                                v-model="selectedSort"
                                :label="t('ui.cms_content_page.sort', '')"
                                name="sort"
                                :options="sortOptions"
                                :searchable="false"
                            />
                        </div>
                        <div class="pc-field pc-field--action pc-table__actions">
                            <button class="pc-btn pc-btn--secondary" type="submit">
                                <UiIcon name="search" :size="16" />
                                {{ t('ui.search') }}
                            </button>
                            <button class="pc-btn pc-btn--ghost" type="button" :disabled="!hasFilters" @click="clearFilters">
                                <UiIcon name="refresh" :size="16" />
                                {{ t('ui.cms_content_page.clear_filters', '') }}
                            </button>
                        </div>
                    </form>

                    <div v-if="items.length" class="pc-media-grid">
                        <article v-for="item in items" :key="item.id" class="pc-media-tile">
                            <div class="pc-media-tile__thumb">
                                <img
                                    v-if="item.type === 'image' && item.url"
                                    :src="item.url"
                                    :alt="item.alt || item.name"
                                    loading="lazy"
                                >
                                <UiIcon v-else :name="item.restricted ? 'lock' : 'file'" :size="30" />
                            </div>
                            <div class="pc-media-tile__body">
                                <span class="pc-media-tile__name">{{ item.name }}</span>
                                <span class="pc-list__meta" dir="ltr">{{ item.size }} · {{ item.mime }}</span>
                                <div class="pc-table__actions">
                                    <span
                                        class="pc-badge"
                                        :class="item.restricted ? 'pc-badge--sand' : 'pc-badge--teal'"
                                    >
                                        <UiIcon v-if="item.restricted" name="lock" :size="12" />
                                        {{ item.restricted ? t('cms_restricted_file') : t('ui.published') }}
                                    </span>
                                    <span class="pc-badge pc-badge--slate">{{ t(`cms_media_type_${item.type}`, item.type) }}</span>
                                </div>

                                <div class="pc-table__actions">
                                    <button
                                        class="pc-btn pc-btn--secondary pc-btn--sm"
                                        type="button"
                                        :aria-expanded="detailsId === item.id ? 'true' : 'false'"
                                        @click="toggleDetails(item)"
                                    >
                                        <UiIcon name="info" :size="14" />
                                        {{ t('cms_media_details', 'Details') }}
                                    </button>
                                    <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="openPreview(item)">
                                        <UiIcon name="eye" :size="14" />
                                        {{ t('cms_preview') }}
                                    </button>
                                    <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="copyUrl(item)">
                                        <UiIcon name="copy" :size="14" />
                                        {{ t('cms_media_copy_url', 'Copy URL') }}
                                    </button>
                                    <button
                                        v-if="can.delete"
                                        class="pc-btn pc-btn--danger pc-btn--sm"
                                        type="button"
                                        @click="remove(item)"
                                    >
                                        <UiIcon name="trash" :size="14" />
                                        {{ t('ui.delete') }}
                                    </button>
                                </div>
                                <span v-if="copiedId === item.id" class="pc-hint" role="status">{{ t('cms_media_copied', '') }}</span>
                            </div>
                        </article>
                    </div>
                    <EmptyState v-else :title="t('cms_no_records')" :message="t('cms_upload_hint')" icon="image" />

                    <section
                        v-if="detailItem()"
                        :key="detailItem().id"
                        class="pc-card pc-media-details"
                        :aria-label="t('cms_media_details', 'File details')"
                    >
                        <div class="pc-card__head">
                            <div>
                                <h2 class="pc-card__title">{{ detailItem().name }}</h2>
                                <p class="pc-card__subtitle" dir="ltr">{{ detailItem().mime }}</p>
                            </div>
                            <button class="pc-icon-button" type="button" :aria-label="t('cms_close', t('ui.cancel'))" @click="detailsId = null">
                                <UiIcon name="close" :size="16" />
                            </button>
                        </div>
                        <div class="pc-card__body pc-stack">
                            <dl class="pc-detail-list">
                                <div class="pc-detail-list__item">
                                    <dt>{{ t('cms_type', t('ui.status')) }}</dt>
                                    <dd>{{ t(`cms_media_type_${detailItem().type}`, detailItem().type) }}</dd>
                                </div>
                                <div class="pc-detail-list__item">
                                    <dt>{{ t('cms_media_visibility', 'Visibility') }}</dt>
                                    <dd>{{ detailItem().restricted ? t('cms_media_visibility_restricted', 'Restricted') : t('cms_media_visibility_public', 'Public') }}</dd>
                                </div>
                                <div class="pc-detail-list__item">
                                    <dt>{{ t('ui.alt_text') }}</dt>
                                    <dd>{{ detailItem().alt || '—' }}</dd>
                                </div>
                                <div class="pc-detail-list__item">
                                    <dt>{{ t('ui.cms_description') }}</dt>
                                    <dd>{{ detailItem().caption || '—' }}</dd>
                                </div>
                                <div v-if="detailItem().width && detailItem().height" class="pc-detail-list__item">
                                    <dt>{{ t('cms_media_details', 'Dimensions') }}</dt>
                                    <dd dir="ltr">{{ detailItem().width }} × {{ detailItem().height }}</dd>
                                </div>
                            </dl>

                            <div class="pc-table__actions">
                                <a v-if="detailItem().url" class="pc-btn pc-btn--secondary pc-btn--sm" :href="detailItem().url" target="_blank" rel="noopener noreferrer">
                                    <UiIcon name="download" :size="14" />
                                    {{ t('open_original') }}
                                </a>
                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="copyUrl(detailItem())">
                                    <UiIcon name="copy" :size="14" />
                                    {{ t('cms_media_copy_url', 'Copy URL') }}
                                </button>
                                <button v-if="can.update" class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="openEditor(detailItem())">
                                    <UiIcon name="edit" :size="14" />
                                    {{ t('ui.edit') }}
                                </button>
                            </div>

                            <input
                                v-if="signedLinks[detailItem().id]"
                                class="pc-input"
                                type="text"
                                dir="ltr"
                                readonly
                                :value="signedLinks[detailItem().id]"
                                :aria-label="t('cms_signed_link')"
                                @focus="$event.target.select()"
                            >

                            <form v-if="can.update && editing === detailItem().id" class="pc-form-grid pc-form-grid--2 pc-picker" @submit.prevent="saveEditor">
                                <label v-for="code in ['ar', 'en']" :key="`edit-alt-${code}`" class="pc-field">
                                    <span class="pc-label">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.alt_text') }}</span>
                                    <input v-model="editForm.translations[code].alt_text" class="pc-input" type="text" maxlength="255">
                                </label>
                                <label v-for="code in ['ar', 'en']" :key="`edit-${code}`" class="pc-field">
                                    <span class="pc-label">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.cms_description') }}</span>
                                    <input v-model="editForm.translations[code].caption" class="pc-input" type="text" maxlength="500">
                                </label>
                                <div class="pc-form-actions">
                                    <button class="pc-btn pc-btn--primary pc-btn--sm" type="submit">
                                        <UiIcon name="check" :size="14" />
                                        {{ t('cms_save') }}
                                    </button>
                                    <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="editing = null">{{ t('cms_close', t('ui.cancel')) }}</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <CmsPagination :pagination="pagination" />
                </div>
            </section>
        </div>

        <div v-if="previewUrl" class="pc-modal-backdrop" @click.self="closePreview">
            <div ref="previewDialog" class="pc-modal pc-modal--wide" role="dialog" aria-modal="true" :aria-label="t('cms_preview')" tabindex="-1">
                <div class="pc-modal__head">
                    <h2 class="pc-modal__title">{{ previewItem?.name }}</h2>
                    <button class="pc-icon-button" type="button" :aria-label="t('cms_close', t('ui.cancel'))" @click="closePreview">
                        <UiIcon name="close" :size="16" />
                    </button>
                </div>
                <div class="pc-modal__body">
                    <img
                        v-if="previewItem?.type === 'image'"
                        class="pc-media-preview-image"
                        :src="previewUrl"
                        :alt="previewItem?.alt || previewItem?.name"
                    >
                    <a v-else class="pc-btn pc-btn--secondary" :href="previewUrl" target="_blank" rel="noopener noreferrer">
                        <UiIcon name="download" :size="16" />
                        {{ t('open_original') }}
                    </a>
                </div>
            </div>
        </div>
    </CmsDashboardLayout>
</template>
