<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import SelectionControl from './Selection/SelectionControl.vue'
import { useI18n } from '../composables/useI18n'

const props = defineProps({
    type: { type: String, default: 'contact' },
    areas: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()

const endpoints = {
    contact: 'contact/inquiry',
    volunteer: 'volunteer/inquiry',
}

function initialFields() {
    const consent = false

    if (props.type === 'volunteer') {
        return { name: '', email: '', phone: '', area_of_interest: '', availability: '', message: '', consent, website: '' }
    }

    return { category: 'general', name: '', email: '', subject: '', message: '', consent, website: '' }
}

const form = useForm(initialFields())

const contactCategories = ['general', 'partnership', 'media', 'volunteer', 'complaint']
const availabilityOptions = ['mornings', 'afternoons', 'evenings', 'weekends', 'flexible']

const interestOptions = computed(() => props.areas.length
    ? props.areas.map((area) => ({ value: area.title, label: area.shortTitle || area.title }))
    : [])

const contactCategoryOptions = computed(() => contactCategories.map((category) => ({
    value: category,
    label: t(category),
})))

const availabilitySelectOptions = computed(() => availabilityOptions.map((option) => ({
    value: option,
    label: t(option),
})))

const endpoint = computed(() => endpoints[props.type] ?? endpoints.contact)

function error(field) {
    return form.errors[field]
}

function submit() {
    form.post(localPath(endpoint.value), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <form class="form-grid" novalidate @submit.prevent="submit">
        <div v-if="type === 'contact' || type === 'volunteer'" class="form-grid form-grid--two">
            <label class="field">
                <span>{{ t('ui.name') }} <span aria-hidden="true">*</span></span>
                <input v-model="form.name" class="input" type="text" maxlength="120" autocomplete="name" :aria-invalid="!! error('name')" required>
                <span v-if="error('name')" class="error" role="alert">{{ error('name') }}</span>
            </label>
            <label class="field">
                <span>{{ t('ui.email') }} <span aria-hidden="true">*</span></span>
                <input v-model="form.email" class="input" type="email" dir="ltr" inputmode="email" maxlength="180" autocomplete="email" :aria-invalid="!! error('email')" required>
                <span v-if="error('email')" class="error" role="alert">{{ error('email') }}</span>
            </label>
        </div>

        <label v-if="type === 'contact'" class="field">
            <span>{{ t('ui.subject') }}</span>
            <input v-model="form.subject" class="input" type="text" maxlength="200" :aria-invalid="!! error('subject')">
            <span v-if="error('subject')" class="error" role="alert">{{ error('subject') }}</span>
        </label>

        <div v-if="type === 'contact'" class="field">
            <SelectionControl
                v-model="form.category"
                :label="t('ui.category')"
                :options="contactCategoryOptions"
                :error="error('category') || ''"
                :searchable="false"
                required
                variant="public"
            />
        </div>

        <label v-if="type === 'volunteer'" class="field">
            <span>{{ t('ui.phone') }} <span class="hint">{{ t('cms_optional') }}</span></span>
            <input v-model="form.phone" class="input" type="tel" dir="ltr" inputmode="tel" maxlength="40" autocomplete="tel">
        </label>

        <div v-if="type === 'volunteer'" class="field">
            <SelectionControl
                v-model="form.area_of_interest"
                :label="t('ui.area_of_interest')"
                :options="interestOptions"
                :placeholder="t('select_area')"
                :error="error('area_of_interest') || ''"
                :searchable="interestOptions.length > 6"
                required
                variant="public"
            />
        </div>

        <div v-if="type === 'volunteer'" class="field">
            <SelectionControl
                v-model="form.availability"
                :label="t('ui.availability')"
                :options="availabilitySelectOptions"
                :placeholder="t('select_area')"
                :error="error('availability') || ''"
                :searchable="false"
                required
                variant="public"
            />
        </div>

        <label class="field">
            <span>{{ t('ui.message') }} <span v-if="type !== 'volunteer'" aria-hidden="true">*</span></span>
            <textarea v-model="form.message" class="textarea" rows="5" :maxlength="type === 'volunteer' ? 4000 : 5000" :required="type !== 'volunteer'" :aria-invalid="!! error('message')"></textarea>
            <span v-if="error('message')" class="error" role="alert">{{ error('message') }}</span>
        </label>

        <div aria-hidden="true" style="position: absolute; inset-inline-start: -9999px">
            <label>Website <input v-model="form.website" type="text" tabindex="-1" autocomplete="off"></label>
        </div>

        <label class="checkbox-row">
            <input v-model="form.consent" type="checkbox" :aria-invalid="!! error('consent')" required>
            <span>{{ t('ui.consent') }} <span aria-hidden="true">*</span></span>
        </label>
        <span v-if="error('consent')" class="error" role="alert">{{ error('consent') }}</span>

        <p class="hint" style="margin: 0">{{ t('ui.privacy_notice') }}</p>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                {{ t('ui.send') }}
            </button>
            <span v-if="form.processing" class="small-caps">{{ t('sending') }}</span>
        </div>
    </form>
</template>
