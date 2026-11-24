<script setup>
import { useForm } from '@inertiajs/vue3'
import SelectionControl from '../Selection/SelectionControl.vue'
import StatusSelect from '../Selection/StatusSelect.vue'
import { useI18n } from '../../composables/useI18n'
import { label, optionLabel, optionValue } from './phase5'

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'post' },
    fields: { type: Array, required: true },
    submitLabel: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    resetOnSuccess: { type: Boolean, default: true },
})

const { locale } = useI18n()

const initialValues = () => Object.fromEntries(
    props.fields.map((field) => [
        field.name,
        field.value ?? (field.type === 'checkbox' ? false : ''),
    ]),
)

const form = useForm(initialValues())

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => {
            if (props.resetOnSuccess) {
                form.reset()
            }
        },
    })
}

function fieldId(field) {
    return `phase5-${props.action.replace(/[^a-zA-Z0-9]+/g, '-')}-${field.name}`
}

function isStatusField(field) {
    return /status/i.test(field.name) || Boolean(field.status)
}

function selectOptions(field) {
    return (field.options ?? []).map((option) => ({
        ...(option && typeof option === 'object' ? option : {}),
        value: optionValue(option),
        label: optionLabel(option, locale.value),
    }))
}
</script>

<template>
    <form class="pc-form-grid phase5-form" :class="{ 'is-compact': compact }" @submit.prevent="submit">
        <div v-for="field in fields" :key="field.name" class="pc-field" :class="{ 'pc-field--full': field.wide }">
            <label v-if="field.type !== 'checkbox'" class="pc-label" :for="fieldId(field)">
                {{ field.label || label(field.name, locale) }}
            </label>

            <StatusSelect
                v-if="field.type === 'select' && isStatusField(field)"
                v-model="form[field.name]"
                :label="field.label || label(field.name, locale)"
                :name="field.name"
                :options="selectOptions(field)"
                :placeholder="field.placeholder || label('choose', locale)"
                :required="Boolean(field.required)"
                :error="form.errors[field.name] || ''"
                :searchable="(field.options ?? []).length > 8"
            />
            <SelectionControl
                v-else-if="field.type === 'select'"
                v-model="form[field.name]"
                :label="field.label || label(field.name, locale)"
                :name="field.name"
                :options="selectOptions(field)"
                :placeholder="field.placeholder || label('choose', locale)"
                :searchable="(field.options ?? []).length > 6"
                :required="Boolean(field.required)"
                :error="form.errors[field.name] || ''"
            />

            <textarea
                v-else-if="field.type === 'textarea'"
                :id="fieldId(field)"
                v-model="form[field.name]"
                class="pc-textarea"
                :placeholder="field.placeholder || ''"
                :maxlength="field.maxlength"
                rows="4"
            />

            <label v-else-if="field.type === 'checkbox'" class="pc-checkbox" :for="fieldId(field)">
                <input
                    :id="fieldId(field)"
                    v-model="form[field.name]"
                    type="checkbox"
                />
                <span>{{ field.hint || field.label || label(field.name, locale) }}</span>
            </label>

            <input
                v-else
                :id="fieldId(field)"
                v-model="form[field.name]"
                class="pc-input"
                :type="field.type || 'text'"
                :placeholder="field.placeholder || ''"
                :maxlength="field.maxlength"
                :min="field.min"
                :step="field.step"
            />

            <span v-if="field.help && field.type !== 'checkbox'" class="pc-hint">{{ field.help }}</span>
            <span v-if="form.errors[field.name]" class="pc-error" role="alert">{{ form.errors[field.name] }}</span>
        </div>

        <div class="pc-form-actions phase5-form__actions">
            <button type="submit" class="pc-btn pc-btn--primary" :disabled="form.processing" :aria-busy="form.processing">
                {{ submitLabel || label('save', locale) }}
            </button>
            <slot name="actions" />
        </div>
    </form>
</template>
