<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

const props = defineProps({
    type: { type: String, default: 'contact' },
    areas: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()

const endpoints = {
    contact: 'contact/inquiry',
    volunteer: 'volunteer/inquiry',
    partnership: 'partnership/inquiry',
}

function initialFields() {
    const consent = false

    if (props.type === 'volunteer') {
        return { name: '', email: '', phone: '', area_of_interest: '', availability: '', message: '', consent, website: '' }
    }

    if (props.type === 'partnership') {
        return { organization: '', contact_name: '', email: '', website: '', interest: '', message: '', consent, homepage: '' }
    }

    return { category: 'general', name: '', email: '', subject: '', message: '', consent, website: '' }
}

const form = useForm(initialFields())

const contactCategories = ['general', 'partnership', 'media', 'volunteer', 'complaint']
const availabilityOptions = ['mornings', 'afternoons', 'evenings', 'weekends', 'flexible']

const interestOptions = computed(() => props.areas.length
    ? props.areas.map((area) => ({ value: area.title, label: area.shortTitle || area.title }))
    : [])

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
        <div v-if="type === 'partnership'" class="form-grid form-grid--two">
            <label class="field">
                <span>{{ t('ui.organization') }} <span aria-hidden="true">*</span></span>
                <input v-model="form.organization" class="input" type="text" maxlength="180" autocomplete="organization" :aria-invalid="!! error('organization')" required>
                <span v-if="error('organization')" class="error" role="alert">{{ error('organization') }}</span>
            </label>
            <label class="field">
                <span>{{ t('ui.contact_name') }} <span aria-hidden="true">*</span></span>
                <input v-model="form.contact_name" class="input" type="text" maxlength="120" :aria-invalid="!! error('contact_name')" required>
                <span v-if="error('contact_name')" class="error" role="alert">{{ error('contact_name') }}</span>
            </label>
        </div>

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

        <label v-if="type === 'partnership'" class="field">
            <span>{{ t('ui.email') }} <span aria-hidden="true">*</span></span>
            <input v-model="form.email" class="input" type="email" dir="ltr" inputmode="email" maxlength="180" autocomplete="email" :aria-invalid="!! error('email')" required>
            <span v-if="error('email')" class="error" role="alert">{{ error('email') }}</span>
        </label>

        <label v-if="type === 'partnership'" class="field">
            <span>{{ t('ui.website') }} <span class="hint">{{ t('cms_optional') }}</span></span>
            <input v-model="form.website" class="input" type="url" dir="ltr" inputmode="url" maxlength="255" placeholder="https://" :aria-invalid="!! error('website')">
            <span v-if="error('website')" class="error" role="alert">{{ error('website') }}</span>
        </label>

        <label v-if="type === 'contact'" class="field">
            <span>{{ t('ui.subject') }}</span>
            <input v-model="form.subject" class="input" type="text" maxlength="200" :aria-invalid="!! error('subject')">
            <span v-if="error('subject')" class="error" role="alert">{{ error('subject') }}</span>
        </label>

        <label v-if="type === 'contact'" class="field">
            <span>{{ t('ui.category') }} <span aria-hidden="true">*</span></span>
            <select v-model="form.category" class="select" required>
                <option v-for="category in contactCategories" :key="category" :value="category">{{ t(category) }}</option>
            </select>
            <span v-if="error('category')" class="error" role="alert">{{ error('category') }}</span>
        </label>

        <label v-if="type === 'volunteer'" class="field">
            <span>{{ t('ui.phone') }} <span class="hint">{{ t('cms_optional') }}</span></span>
            <input v-model="form.phone" class="input" type="tel" dir="ltr" inputmode="tel" maxlength="40" autocomplete="tel">
        </label>

        <label v-if="type === 'volunteer'" class="field">
            <span>{{ t('ui.area_of_interest') }} <span aria-hidden="true">*</span></span>
            <select v-model="form.area_of_interest" class="select" required :aria-invalid="!! error('area_of_interest')">
                <option value="">{{ t('select_area') }}</option>
                <option v-for="option in interestOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <span v-if="error('area_of_interest')" class="error" role="alert">{{ error('area_of_interest') }}</span>
        </label>

        <label v-if="type === 'volunteer'" class="field">
            <span>{{ t('ui.availability') }} <span aria-hidden="true">*</span></span>
            <select v-model="form.availability" class="select" required :aria-invalid="!! error('availability')">
                <option value="">{{ t('select_area') }}</option>
                <option v-for="option in availabilityOptions" :key="option" :value="option">{{ t(option) }}</option>
            </select>
            <span v-if="error('availability')" class="error" role="alert">{{ error('availability') }}</span>
        </label>

        <label v-if="type === 'partnership'" class="field">
            <span>{{ t('ui.interest') }} <span aria-hidden="true">*</span></span>
            <select v-model="form.interest" class="select" required :aria-invalid="!! error('interest')">
                <option value="">{{ t('select_area') }}</option>
                <option v-for="option in interestOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <span v-if="error('interest')" class="error" role="alert">{{ error('interest') }}</span>
        </label>

        <label class="field">
            <span>{{ t('ui.message') }} <span v-if="type !== 'volunteer'" aria-hidden="true">*</span></span>
            <textarea v-model="form.message" class="textarea" rows="5" :maxlength="type === 'volunteer' ? 4000 : 5000" :required="type !== 'volunteer'" :aria-invalid="!! error('message')"></textarea>
            <span v-if="error('message')" class="error" role="alert">{{ error('message') }}</span>
        </label>

        <div v-if="type !== 'partnership'" aria-hidden="true" style="position: absolute; inset-inline-start: -9999px">
            <label>Website <input v-model="form.website" type="text" tabindex="-1" autocomplete="off"></label>
        </div>
        <div v-else aria-hidden="true" style="position: absolute; inset-inline-start: -9999px">
            <label>Homepage <input v-model="form.homepage" type="text" tabindex="-1" autocomplete="off"></label>
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
