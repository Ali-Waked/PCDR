<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    templates: { type: Array, default: () => [] },
    locales: { type: Array, default: () => ['ar', 'en'] },
    variables: { type: Object, default: () => ({}) },
})

const { t, localPath } = useI18n()
const templatesPath = localPath('cms/email-templates')
const selectedLocale = ref(props.locales?.[0] || 'ar')
const preview = ref(null)
const previewError = ref('')
const editingId = ref(null)

function blankForm() {
    return {
        key: '',
        is_active: true,
        sort_order: 0,
        translations: Object.fromEntries(props.locales.map((locale) => [locale, {
            name: '', description: '', subject: '', body: '',
        }])),
    }
}

const form = useForm(blankForm())

const activeTranslation = computed(() => form.translations[selectedLocale.value] || { name: '', description: '', subject: '', body: '' })
const variableList = computed(() => Object.entries(props.variables || {}).map(([key, label]) => ({
    key,
    label,
    token: '{{ ' + key + ' }}',
})))

const columns = computed(() => [
    { key: 'key', label: t('cms_template_key') },
    { key: 'name', label: t('cms_name') },
    { key: 'status', label: t('ui.status') },
    { key: 'actions', label: t('cms_actions'), align: 'end' },
])

function edit(template) {
    editingId.value = template.id
    preview.value = null
    form.reset({
        key: template.key,
        is_active: template.active,
        sort_order: template.sortOrder,
        translations: Object.fromEntries(props.locales.map((locale) => [locale, {
            name: template.translations?.[locale]?.name || '',
            description: template.translations?.[locale]?.description || '',
            subject: template.translations?.[locale]?.subject || '',
            body: template.translations?.[locale]?.body || '',
        }])),
    })
}

function reset() {
    editingId.value = null
    preview.value = null
    previewError.value = ''
    form.reset(blankForm())
}

function save() {
    const options = { preserveScroll: true, onSuccess: () => reset() }
    if (editingId.value) {
        form.put(`${templatesPath}/${editingId.value}`, options)
    } else {
        form.post(templatesPath, options)
    }
}

function remove(template) {
    form.delete(`${templatesPath}/${template.id}`, { preserveScroll: true, onSuccess: () => {
        if (editingId.value === template.id) {
            reset()
        }
    } })
}

function csrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)

    return match ? decodeURIComponent(match[1]) : ''
}

async function renderPreview() {
    previewError.value = ''
    try {
        const response = await fetch(`${templatesPath}/preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                subject: activeTranslation.value.subject || '',
                body: activeTranslation.value.body || '',
            }),
        })

        if (!response.ok) {
            throw new Error(String(response.status))
        }
        preview.value = await response.json()
    } catch {
        previewError.value = t('cms_preview_failed')
    }
}
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_email_templates')" :subtitle="t('cms_email_templates_note')">
        <div class="pc-stack pcdr-enter">
            <div class="pc-grid pc-grid--content">
                <section class="pc-card">
                    <div class="pc-card__head">
                        <div>
                            <h2 class="pc-card__title">{{ t('cms_templates') }}</h2>
                            <p class="pc-card__subtitle">{{ t('ui.dashboard_content_studio') }}</p>
                        </div>
                        <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="reset">
                            <UiIcon name="plus" :size="15" />
                            {{ t('cms_new_template') }}
                        </button>
                    </div>

                    <template v-if="templates.length">
                        <DataTable :columns="columns">
                            <tr v-for="template in templates" :key="template.id">
                                <td class="pc-directional" dir="ltr">{{ template.key }}</td>
                                <td class="pc-table__primary">{{ template.translations?.[selectedLocale]?.name || template.key }}</td>
                                <td>
                                    <CmsStatusBadge :label="template.active ? t('ui.active') : t('ui.inactive')" :status="template.active ? 'active' : 'inactive'" />
                                </td>
                                <td>
                                    <div class="pc-table__actions">
                                        <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="edit(template)">
                                            <UiIcon name="edit" :size="14" />
                                            {{ t('ui.edit') }}
                                        </button>
                                        <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="remove(template)">
                                            <UiIcon name="trash" :size="14" />
                                            {{ t('ui.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </DataTable>
                    </template>
                    <EmptyState v-else :title="t('cms_no_records')" icon="mail" />
                </section>

                <section class="pc-card">
                    <div class="pc-card__head">
                        <div>
                            <h2 class="pc-card__title">{{ editingId ? t('ui.edit') : t('cms_new_template') }}</h2>
                            <p class="pc-card__subtitle">{{ selectedLocale.toUpperCase() }}</p>
                        </div>
                    </div>
                    <form class="pc-card__body pc-form-grid" @submit.prevent="save">
                        <label v-if="!editingId" class="pc-field">
                            <span class="pc-label">{{ t('cms_template_key') }}</span>
                            <input v-model="form.key" class="pc-input" type="text" maxlength="96" dir="ltr" required>
                            <span v-if="form.errors.key" class="pc-error" role="alert">{{ form.errors.key }}</span>
                        </label>

                        <div class="pc-form-grid pc-form-grid--2">
                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.status') }}</span>
                                <select v-model="form.is_active" class="pc-select">
                                    <option :value="true">{{ t('ui.active') }}</option>
                                    <option :value="false">{{ t('ui.inactive') }}</option>
                                </select>
                            </label>
                            <label class="pc-field">
                                <span class="pc-label">{{ t('ui.language') }}</span>
                                <select v-model="selectedLocale" class="pc-select">
                                    <option v-for="locale in locales" :key="locale" :value="locale">{{ locale }}</option>
                                </select>
                            </label>
                        </div>

                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_name') }}</span>
                            <input v-model="activeTranslation.name" class="pc-input" type="text" maxlength="160" required>
                        </label>
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_description') }}</span>
                            <input v-model="activeTranslation.description" class="pc-input" type="text" maxlength="500">
                        </label>
                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.subject') }}</span>
                            <input v-model="activeTranslation.subject" class="pc-input" type="text" maxlength="255" required>
                        </label>
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_body') }}</span>
                            <textarea v-model="activeTranslation.body" class="pc-textarea" rows="8" maxlength="10000" required></textarea>
                        </label>

                        <div class="pc-field">
                            <span class="pc-label">{{ t('cms_allowed_variables') }}</span>
                            <div class="pc-token-list">
                                <span v-for="variable in variableList" :key="variable.key" class="pc-token">
                                    <span>{{ variable.token }}</span>
                                    <span>{{ variable.label }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="pc-form-actions">
                            <button class="pc-btn pc-btn--secondary" type="button" :disabled="form.processing" @click="renderPreview">
                                <UiIcon name="eye" :size="16" />
                                {{ t('cms_preview') }}
                            </button>
                            <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing">
                                <UiIcon name="check" :size="16" />
                                {{ t('cms_save') }}
                            </button>
                        </div>
                    </form>

                    <div v-if="preview || previewError" class="pc-card__foot">
                        <div v-if="preview" class="pc-preview-panel">
                            <strong>{{ preview.subject }}</strong>
                            <p>{{ preview.body }}</p>
                        </div>
                        <p v-if="previewError" class="pc-error" role="alert">{{ previewError }}</p>
                    </div>
                </section>
            </div>
        </div>
    </CmsDashboardLayout>
</template>
