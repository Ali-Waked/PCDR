<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import Pagination from '../../../Components/Pagination.vue'
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

function remove(item) {
    router.delete(`${basePath}/${item.id}`, {
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
    <CmsLayout :title="t('ui.cms_media')" :subtitle="t('cms_upload_hint')">
        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_upload') }}</h2>
            <form class="form-grid" @submit.prevent="submitUpload">
                <label class="field">
                    <span>{{ t('cms_upload') }} <span aria-hidden="true">*</span></span>
                    <input ref="fileInput" class="input" type="file" :accept="'.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt'" required @change="upload.file = $event.target.files[0]">
                    <span v-if="upload.errors.file" class="error" role="alert">{{ upload.errors.file }}</span>
                </label>
                <label class="checkbox-row"><input v-model="upload.is_restricted" type="checkbox"><span>{{ t('cms_restricted_file') }}</span></label>
                <label v-for="code in ['ar', 'en']" :key="`upload-alt-${code}`" class="field">
                    <span>{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.alt_text') }}</span>
                    <input v-model="upload.translations[code].alt_text" class="input" type="text" maxlength="255">
                </label>
                <div class="form-grid form-grid--two">
                    <label v-for="code in ['ar', 'en']" :key="`upload-${code}`" class="field">
                        <span>{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.cms_description') }}</span>
                        <input v-model="upload.translations[code].caption" class="input" type="text" maxlength="500">
                    </label>
                </div>
                <p class="hint" style="margin: 0">{{ t('cms_upload_hint') }}</p>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" :disabled="upload.processing" :aria-busy="upload.processing">{{ t('cms_upload') }}</button>
                </div>
            </form>
        </section>

        <section class="cms-card">
            <div class="cms-card__head">
                <h2 style="font-size: 1.05rem; margin: 0">{{ t('ui.cms_media') }}</h2>
                <span class="small-caps">{{ pagination.total }} {{ t('documents') }}</span>
            </div>

            <div v-if="previewUrl" class="cms-card" role="dialog" aria-modal="true" :aria-label="t('cms_preview')" style="margin-block-end: 1rem">
                <div class="cms-card__head">
                    <h3 style="font-size: 1rem; margin: 0">{{ previewItem?.name }}</h3>
                    <button class="btn btn-ghost btn-small" type="button" @click="closePreview">{{ t('cms_close', t('ui.cancel')) }}</button>
                </div>
                <img v-if="previewItem?.type === 'image'" :src="previewUrl" :alt="previewItem?.alt || previewItem?.name" style="display: block; max-inline-size: 100%; max-block-size: 70vh; margin-inline: auto">
                <a v-else class="btn btn-secondary" :href="previewUrl" target="_blank" rel="noopener noreferrer">{{ t('open_original') }}</a>
            </div>

            <form class="form-grid form-grid--two" style="margin-block-end: 1rem" @submit.prevent="submitFilters">
                <label class="field">
                    <span>{{ t('ui.search') }}</span>
                    <input v-model="searchQuery" class="input" type="search" name="q">
                </label>
                <label class="field">
                    <span>{{ t('cms_type', t('ui.status')) }}</span>
                    <select v-model="selectedType" class="select" name="type">
                        <option value="">{{ t('ui.all') }}</option>
                        <option value="image">{{ t('cms_media_type_image', 'image') }}</option>
                        <option value="document">{{ t('cms_media_type_document', 'document') }}</option>
                    </select>
                </label>
                <div class="form-actions">
                    <button class="btn btn-primary btn-small" type="submit">{{ t('ui.search') }}</button>
                </div>
            </form>

            <div v-if="items.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_title') }}</th>
                            <th>{{ t('category') }}</th>
                            <th>{{ t('cms_status') }}</th>
                            <th>{{ t('cms_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem">
                                    <div v-if="item.type === 'image' && item.url" class="media-cover" style="inline-size: 72px; flex: none"><img :src="item.url" :alt="item.alt || item.name" loading="lazy"></div>
                                    <div>
                                        <strong>{{ item.name }}</strong>
                                        <div class="small-caps">{{ item.size }} · {{ item.mime }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ item.type }}</td>
                            <td><span class="pill" :class="{ 'pill--archived': item.restricted }">{{ item.restricted ? t('cms_restricted_file') : t('ui.published') }}</span></td>
                            <td>
                                <div class="header-actions">
                                    <button class="btn btn-secondary btn-small" type="button" @click="openEditor(item)">{{ t('cms_edit') }}</button>
                                    <button class="btn btn-ghost btn-small" type="button" :aria-label="t('cms_preview')" @click="openPreview(item)">{{ t('cms_preview') }}</button>
                                    <a v-if="item.url" class="btn btn-ghost btn-small" :href="item.url" target="_blank" rel="noopener noreferrer">{{ t('open_original') }}</a>
                                    <button v-else class="btn btn-ghost btn-small" type="button" @click="signedLink(item)">{{ t('cms_signed_link') }}</button>
                                    <button class="btn btn-danger btn-small" type="button" @click="confirmId = item.id">{{ t('cms_delete') }}</button>
                                </div>
                                <div v-if="signedLinks[item.id]" style="margin-block-start: 0.5rem">
                                    <input class="input" type="text" dir="ltr" readonly :value="signedLinks[item.id]" @focus="$event.target.select()">
                                </div>
                                <form v-if="editing === item.id" style="margin-block-start: 0.6rem; display: grid; gap: 0.5rem" @submit.prevent="saveEditor">
                                    <label v-for="code in ['ar', 'en']" :key="`edit-alt-${code}`" class="field">
                                        <span>{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.alt_text') }}</span>
                                        <input v-model="editForm.translations[code].alt_text" class="input" type="text" maxlength="255">
                                    </label>
                                    <label v-for="code in ['ar', 'en']" :key="`edit-${code}`" class="field">
                                        <span>{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }} — {{ t('ui.cms_description') }}</span>
                                        <input v-model="editForm.translations[code].caption" class="input" type="text" maxlength="500">
                                    </label>
                                    <div class="header-actions">
                                        <button class="btn btn-primary btn-small" type="submit">{{ t('cms_save') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" @click="editing = null">×</button>
                                    </div>
                                </form>
                                <div v-if="confirmId === item.id" class="alert alert--error" style="margin-block-start: 0.6rem" role="alert">
                                    <span>{{ t('cms_confirm_delete') }}</span>
                                    <div class="header-actions">
                                        <button class="btn btn-danger btn-small" type="button" @click="remove(item)">{{ t('cms_delete') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" @click="confirmId = null">{{ t('cms_close', t('ui.cancel')) }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
            <Pagination :pagination="pagination" />
        </section>
    </CmsLayout>
</template>
