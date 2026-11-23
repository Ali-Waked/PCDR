<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import EmptyState from '../../../Components/EmptyState.vue'
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
    <CmsLayout :title="t('ui.cms_email_templates')" :subtitle="t('cms_email_templates_note')">
        <section class="cms-card">
            <div class="header-actions">
                <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_templates') }}</h2>
                <button class="btn btn-secondary btn-small" type="button" @click="reset">{{ t('cms_new_template') }}</button>
            </div>
            <div v-if="templates.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_template_key') }}</th>
                            <th>{{ t('cms_name') }}</th>
                            <th>{{ t('ui.status') }}</th>
                            <th>{{ t('cms_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="template in templates" :key="template.id">
                            <td dir="ltr">{{ template.key }}</td>
                            <td>{{ template.translations?.[selectedLocale]?.name || template.key }}</td>
                            <td><span class="pill">{{ template.active ? t('ui.active') : t('ui.inactive') }}</span></td>
                            <td>
                                <div class="header-actions">
                                    <button class="btn btn-secondary btn-small" type="button" @click="edit(template)">{{ t('edit') }}</button>
                                    <button class="btn btn-danger btn-small" type="button" @click="remove(template)">{{ t('delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ editingId ? t('edit') : t('cms_new_template') }}</h2>
            <form class="form-stack" @submit.prevent="save">
                <div class="form-grid form-grid--two">
                    <label v-if="!editingId" class="field">
                        <span>{{ t('cms_template_key') }}</span>
                        <input v-model="form.key" class="input" type="text" maxlength="96" dir="ltr" required>
                        <span v-if="form.errors.key" class="error" role="alert">{{ form.errors.key }}</span>
                    </label>
                    <label class="field">
                        <span>{{ t('ui.status') }}</span>
                        <select v-model="form.is_active" class="select">
                            <option :value="true">{{ t('ui.active') }}</option>
                            <option :value="false">{{ t('ui.inactive') }}</option>
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span>{{ t('ui.language') }}</span>
                    <select v-model="selectedLocale" class="select">
                        <option v-for="locale in locales" :key="locale" :value="locale">{{ locale }}</option>
                    </select>
                </label>

                <label class="field">
                    <span>{{ t('cms_name') }}</span>
                    <input v-model="activeTranslation.name" class="input" type="text" maxlength="160" required>
                </label>
                <label class="field">
                    <span>{{ t('cms_description') }}</span>
                    <input v-model="activeTranslation.description" class="input" type="text" maxlength="500">
                </label>
                <label class="field">
                    <span>{{ t('subject') }}</span>
                    <input v-model="activeTranslation.subject" class="input" type="text" maxlength="255" required>
                </label>
                <label class="field">
                    <span>{{ t('cms_body') }}</span>
                    <textarea v-model="activeTranslation.body" class="textarea" rows="8" maxlength="10000" required></textarea>
                </label>

                <div class="variable-list">
                    <span v-for="variable in variableList" :key="variable.key" class="pill" dir="ltr">{{ variable.token }} — {{ variable.label }}</span>
                </div>

                <div class="form-actions">
                    <button class="btn btn-secondary" type="button" :disabled="form.processing" @click="renderPreview">{{ t('cms_preview') }}</button>
                    <button class="btn btn-primary" type="submit" :disabled="form.processing">{{ t('cms_save') }}</button>
                </div>
            </form>

            <div v-if="preview" class="preview-panel">
                <strong>{{ preview.subject }}</strong>
                <p>{{ preview.body }}</p>
            </div>
            <p v-if="previewError" class="error" role="alert">{{ previewError }}</p>
        </section>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}

.form-stack {
    display: grid;
    gap: 0.75rem;
}

.variable-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.preview-panel {
    margin-block-start: 1rem;
    padding: 1rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0.5rem;
    white-space: pre-wrap;
}
</style>
