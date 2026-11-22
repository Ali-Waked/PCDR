<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    settings: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()

const form = useForm({
    settings: props.settings.map((setting) => ({
        key: setting.key,
        value: setting.value || '',
    })),
})

const indexedSettings = computed(() => form.settings.map((setting, index) => ({ ...setting, index })))
const contactSettings = computed(() => indexedSettings.value.filter((item) => item.key.startsWith('contact.')))
const socialSettings = computed(() => indexedSettings.value.filter((item) => item.key.startsWith('social.')))

function save() {
    form.put(localPath('cms/settings'), { preserveScroll: true })
}
</script>

<template>
    <CmsLayout :title="t('ui.cms_settings')" :subtitle="t('cms_auth_note')">
        <form @submit.prevent="save">
            <section class="cms-card">
                <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('contact_details') }}</h2>
                <div class="form-grid form-grid--two">
                    <label v-for="setting in contactSettings" :key="setting.key" class="field">
                        <span>{{ t(`ui.cms_${setting.key.split('.')[1]}`) }}</span>
                        <input
                            v-model="form.settings[setting.index].value"
                            class="input"
                            type="text"
                            maxlength="500"
                            :dir="['contact.email', 'contact.phone'].includes(setting.key) ? 'ltr' : undefined"
                            :aria-invalid="!! form.errors[`settings.${setting.index}.value`]"
                        >
                        <span
                            v-if="form.errors[`settings.${setting.index}.value`]"
                            class="error"
                            role="alert"
                        >{{ form.errors[`settings.${setting.index}.value`] }}</span>
                    </label>
                </div>
            </section>

            <section class="cms-card">
                <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_social') }}</h2>
                <div class="form-grid form-grid--two">
                    <label v-for="setting in socialSettings" :key="setting.key" class="field">
                        <span>{{ setting.key.split('.')[1] }}</span>
                        <input
                            v-model="form.settings[setting.index].value"
                            class="input"
                            type="url"
                            dir="ltr"
                            maxlength="500"
                            placeholder="https://"
                            :aria-invalid="!! form.errors[`settings.${setting.index}.value`]"
                        >
                        <span
                            v-if="form.errors[`settings.${setting.index}.value`]"
                            class="error"
                            role="alert"
                        >{{ form.errors[`settings.${setting.index}.value`] }}</span>
                    </label>
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
</style>
