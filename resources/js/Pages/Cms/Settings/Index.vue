<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    settings: { type: Array, default: () => [] },
    locales: { type: Array, default: () => ['ar', 'en'] },
})

const { t, localPath } = useI18n()

const rows = computed(() => props.settings.map((setting, index) => ({
    ...setting,
    index,
})))

const groups = computed(() => [...new Set(rows.value.map((setting) => setting.group))])

const form = useForm({
    settings: props.settings.map((setting) => ({
        key: setting.key,
        value: setting.value || '',
        translations: Object.fromEntries(props.locales.map((locale) => [locale, setting.translations?.[locale] || ''])),
    })),
})

function save() {
    form.put(localPath('cms/settings'), { preserveScroll: true })
}

function valueError(setting, locale = null) {
    const prefix = `settings.${setting.index}.`
    const key = locale ? `${prefix}translations.${locale}` : `${prefix}value`

    return form.errors[key]
}

function inputType(setting) {
    if (setting.type === 'url') {
        return 'url'
    }
    if (setting.type === 'email') {
        return 'email'
    }
    if (setting.type === 'textarea') {
        return 'textarea'
    }
    if (setting.type === 'locale') {
        return 'locale'
    }

    return 'text'
}
</script>

<template>
    <CmsLayout :title="t('ui.cms_settings')" :subtitle="t('cms_settings_note')">
        <div class="alert alert--info">{{ t('cms_protected_settings_note') }}</div>

        <form @submit.prevent="save">
            <section v-for="group in groups" :key="group" class="cms-card">
                <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ group }}</h2>
                <div class="form-grid form-grid--two">
                    <template v-for="setting in rows.filter((item) => item.group === group)" :key="setting.key">
                        <label v-if="!setting.localized" class="field">
                            <span>{{ setting.label }}</span>
                            <select
                                v-if="inputType(setting) === 'locale'"
                                v-model="form.settings[setting.index].value"
                                class="select"
                            >
                                <option v-for="locale in locales" :key="locale" :value="locale">{{ locale }}</option>
                            </select>
                            <textarea
                                v-else-if="inputType(setting) === 'textarea'"
                                v-model="form.settings[setting.index].value"
                                class="textarea"
                                rows="3"
                                maxlength="1000"
                            ></textarea>
                            <input
                                v-else
                                v-model="form.settings[setting.index].value"
                                class="input"
                                :type="inputType(setting)"
                                maxlength="1000"
                                :dir="['url', 'email'].includes(inputType(setting)) ? 'ltr' : undefined"
                            >
                            <span v-if="valueError(setting)" class="error" role="alert">{{ valueError(setting) }}</span>
                        </label>

                        <fieldset v-else class="field localized-field">
                            <legend>{{ setting.label }}</legend>
                            <label v-for="locale in locales" :key="locale" class="field">
                                <span>{{ locale }}</span>
                                <textarea
                                    v-if="inputType(setting) === 'textarea'"
                                    v-model="form.settings[setting.index].translations[locale]"
                                    class="textarea"
                                    rows="2"
                                    maxlength="1000"
                                ></textarea>
                                <input
                                    v-else
                                    v-model="form.settings[setting.index].translations[locale]"
                                    class="input"
                                    type="text"
                                    maxlength="1000"
                                >
                                <span v-if="valueError(setting, locale)" class="error" role="alert">{{ valueError(setting, locale) }}</span>
                            </label>
                        </fieldset>
                    </template>
                </div>
            </section>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                    {{ t('cms_save') }}
                </button>
            </div>
        </form>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}

.localized-field {
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0.5rem;
    padding: 0.75rem;
    display: grid;
    gap: 0.5rem;
}
</style>
