<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
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
    <CmsDashboardLayout :title="t('ui.cms_settings')" :subtitle="t('cms_settings_note')">
        <div class="pc-stack pcdr-enter">
            <div class="pc-callout pc-callout--sand" role="note">
                <UiIcon name="lock" :size="17" />
                <span>{{ t('cms_protected_settings_note') }}</span>
            </div>

            <div class="pc-page-actions">
                <button class="pc-btn pc-btn--primary" type="submit" form="settings-form" :disabled="form.processing">
                    <UiIcon name="check" :size="16" />
                    {{ t('cms_save') }}
                </button>
            </div>

            <form id="settings-form" class="pc-stack" @submit.prevent="save">
                <section v-for="group in groups" :key="group" class="pc-card">
                    <div class="pc-card__head">
                        <h2 class="pc-card__title">{{ group }}</h2>
                        <span class="pc-badge pc-badge--slate">{{ rows.filter((item) => item.group === group).length }}</span>
                    </div>
                    <div class="pc-card__body pc-form-grid">
                        <template v-for="setting in rows.filter((item) => item.group === group)" :key="setting.key">
                            <label v-if="!setting.localized" class="pc-field">
                                <span class="pc-label">{{ setting.label }}</span>
                                <select
                                    v-if="inputType(setting) === 'locale'"
                                    v-model="form.settings[setting.index].value"
                                    class="pc-select"
                                >
                                    <option v-for="locale in locales" :key="locale" :value="locale">{{ locale }}</option>
                                </select>
                                <textarea
                                    v-else-if="inputType(setting) === 'textarea'"
                                    v-model="form.settings[setting.index].value"
                                    class="pc-textarea"
                                    rows="3"
                                    maxlength="1000"
                                ></textarea>
                                <input
                                    v-else
                                    v-model="form.settings[setting.index].value"
                                    class="pc-input"
                                    :type="inputType(setting)"
                                    maxlength="1000"
                                    :dir="['url', 'email'].includes(inputType(setting)) ? 'ltr' : undefined"
                                >
                                <span v-if="valueError(setting)" class="pc-error" role="alert">{{ valueError(setting) }}</span>
                            </label>

                            <fieldset v-else class="pc-field pc-picker">
                                <legend class="pc-label">{{ setting.label }}</legend>
                                <label v-for="locale in locales" :key="locale" class="pc-field">
                                    <span class="pc-label">{{ locale }}</span>
                                    <textarea
                                        v-if="inputType(setting) === 'textarea'"
                                        v-model="form.settings[setting.index].translations[locale]"
                                        class="pc-textarea"
                                        rows="2"
                                        maxlength="1000"
                                    ></textarea>
                                    <input
                                        v-else
                                        v-model="form.settings[setting.index].translations[locale]"
                                        class="pc-input"
                                        type="text"
                                        maxlength="1000"
                                    >
                                    <span v-if="valueError(setting, locale)" class="pc-error" role="alert">{{ valueError(setting, locale) }}</span>
                                </label>
                            </fieldset>
                        </template>
                    </div>
                </section>

                <div class="pc-form-actions">
                    <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                        <UiIcon name="check" :size="16" />
                        {{ t('cms_save') }}
                    </button>
                </div>
            </form>
        </div>
    </CmsDashboardLayout>
</template>
