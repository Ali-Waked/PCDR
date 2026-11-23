<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsPagination from '../../../Components/Dashboard/CmsPagination.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import ConfirmDialog from '../../../Components/Dashboard/ConfirmDialog.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    items: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({ type: '', q: '' }) },
})

const { t, localPath } = useI18n()
const basePath = localPath('cms/media')

const searchQuery = ref(props.filters?.q || '')
const selectedType = ref(props.filters?.type || '')

watch(() => props.filters, (filters) => {
    searchQuery.value = filters?.q || ''
    selectedType.value = filters?.type || ''
}, { deep: true })

function submitFilters() {
    router.get(basePath, { q: searchQuery.value, type: selectedType.value }, { preserveScroll: true })
}

const upload = useForm({
    file: null,
    is_restricted: false,
    translations: {
        ar: { alt_text: '', caption: '' },
        en: { alt_text: '', caption: '' },
    },
})

const fileInput = ref(null)
const editing = ref(null)
const confirmId = ref(null)
const signedLinks = ref({})
const previewItem = ref(null)
const previewUrl = ref('')
const previewDialog = ref(null)
let previousFocus = null

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

const editForm = useForm({
    translations: {
        ar: { alt_text: '', caption: '' },
        en: { alt_text: '', caption: '' },
    },
})

function submitUpload() {
    upload.post(basePath, {
        preserveScroll: true,
        onSuccess: () => {
            upload.reset()
            if (fileInput.value) {
                fileInput.value.value = ''
            }
        },
    })
}

function translatedValue(item, code, field) {
    return item.translations?.[code]?.[field] ?? (field === 'alt_text' ? item.alt : item.caption) ?? ''
}

function openEditor(item) {
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
        preserveScroll: true,
        onSuccess: () => { editing.value = null },
    })
}

function remove() {
    if (!confirmId.value) {
        return
    }

    router.delete(`${basePath}/${confirmId.value}`, {
        preserveScroll: true,
        onSuccess: () => { confirmId.value = null },
    })
}

function signedLink(item) {
    return fetch(`${basePath}/${item.id}/signed`, { headers: { Accept: 'application/json' } })
        .then((response) => response.ok ? response.json() : Promise.reject(new Error('failed')))
        .then((data) => {
            signedLinks.value[item.id] = data.url
            return data.url
        })
        .catch(() => {
            signedLinks.value[item.id] = ''
            return ''
        })
}

async function openPreview(item) {
    if (!item.restricted && item.url) {
        previewItem.value = item
        previewUrl.value = item.url
        return
    }

    const url = await signedLink(item)
    if (!url) {
        return
    }

    previewItem.value = item
    previewUrl.value = url
}

function closePreview() {
    previewItem.value = null
    previewUrl.value = ''
}
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_media')" :subtitle="t('cms_upload_hint')">
        <div class="pc-stack pcdr-enter">
            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ t('cms_upload') }}</h2>
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
                            :accept="'.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt'"
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

            <section class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ t('ui.cms_media') }}</h2>
                        <p class="pc-card__subtitle">{{ pagination.total }} {{ t('documents') }}</p>
                    </div>
                    <span class="pc-badge pc-badge--teal">{{ items.length }}</span>
                </div>

                <div class="pc-card__body pc-stack">
                    <form class="pc-form-grid" @submit.prevent="submitFilters">
                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.search') }}</span>
                            <input v-model="searchQuery" class="pc-input" type="search" name="q">
                        </label>
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_type', t('ui.status')) }}</span>
                            <select v-model="selectedType" class="pc-select" name="type">
                                <option value="">{{ t('ui.all') }}</option>
                                <option value="image">{{ t('cms_media_type_image', 'image') }}</option>
                                <option value="document">{{ t('cms_media_type_document', 'document') }}</option>
                            </select>
                        </label>
                        <div class="pc-field pc-field--action">
                            <button class="pc-btn pc-btn--secondary" type="submit">
                                <UiIcon name="search" :size="16" />
                                {{ t('ui.search') }}
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
                                    <CmsStatusBadge
                                        :status="item.restricted ? 'archived' : 'published'"
                                        :label="item.restricted ? t('cms_restricted_file') : t('ui.published')"
                                    />
                                </div>

                                <div class="pc-table__actions">
                                    <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="openEditor(item)">
                                        <UiIcon name="edit" :size="14" />
                                        {{ t('ui.edit') }}
                                    </button>
                                    <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :aria-label="t('cms_preview')" @click="openPreview(item)">
                                        <UiIcon name="eye" :size="14" />
                                        {{ t('cms_preview') }}
                                    </button>
                                    <a v-if="item.url" class="pc-btn pc-btn--ghost pc-btn--sm" :href="item.url" target="_blank" rel="noopener noreferrer">
                                        <UiIcon name="download" :size="14" />
                                        {{ t('open_original') }}
                                    </a>
                                    <button v-else class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="signedLink(item)">
                                        <UiIcon name="link" :size="14" />
                                        {{ t('cms_signed_link') }}
                                    </button>
                                    <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="confirmId = item.id">
                                        <UiIcon name="trash" :size="14" />
                                        {{ t('ui.delete') }}
                                    </button>
                                </div>

                                <div v-if="signedLinks[item.id]" class="pc-field">
                                    <input
                                        class="pc-input"
                                        type="text"
                                        dir="ltr"
                                        readonly
                                        :value="signedLinks[item.id]"
                                        @focus="$event.target.select()"
                                    >
                                </div>

                                <form v-if="editing === item.id" class="pc-form-grid pc-form-grid--2 pc-picker" @submit.prevent="saveEditor">
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
                        </article>
                    </div>
                    <EmptyState v-else :title="t('cms_no_records')" :message="t('cms_upload_hint')" icon="image" />

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
