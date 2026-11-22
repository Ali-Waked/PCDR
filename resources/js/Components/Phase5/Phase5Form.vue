<script setup>
import { useForm } from '@inertiajs/vue3'
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
</script>

<template>
    <form class="phase5-form" :class="{ 'is-compact': compact }" @submit.prevent="submit">
        <div class="phase5-form__grid">
            <div v-for="field in fields" :key="field.name" class="field" :class="{ 'is-wide': field.wide }">
                <label :for="fieldId(field)">{{ field.label || label(field.name, locale) }}</label>

                <select
                    v-if="field.type === 'select'"
                    :id="fieldId(field)"
                    v-model="form[field.name]"
                    class="select"
                >
                    <option value="">{{ field.placeholder || label('choose', locale) }}</option>
                    <option
                        v-for="option in field.options ?? []"
                        :key="optionValue(option)"
                        :value="optionValue(option)"
                    >
                        {{ optionLabel(option, locale) }}
                    </option>
                </select>

                <textarea
                    v-else-if="field.type === 'textarea'"
                    :id="fieldId(field)"
                    v-model="form[field.name]"
                    class="textarea"
                    :placeholder="field.placeholder || ''"
                    :maxlength="field.maxlength"
                    rows="4"
                />

                <label v-else-if="field.type === 'checkbox'" class="checkbox-row" :for="fieldId(field)">
                    <input
                        :id="fieldId(field)"
                        v-model="form[field.name]"
                        type="checkbox"
                        class="input"
                    />
                    <span>{{ field.hint || field.label || label(field.name, locale) }}</span>
                </label>

                <input
                    v-else
                    :id="fieldId(field)"
                    v-model="form[field.name]"
                    class="input"
                    :type="field.type || 'text'"
                    :placeholder="field.placeholder || ''"
                    :maxlength="field.maxlength"
                    :min="field.min"
                    :step="field.step"
                />

                <span v-if="field.help && field.type !== 'checkbox'" class="hint">{{ field.help }}</span>
                <span v-if="form.errors[field.name]" class="error" role="alert">{{ form.errors[field.name] }}</span>
            </div>
        </div>

        <div class="phase5-form__actions">
            <button type="submit" class="btn btn-primary" :disabled="form.processing" :aria-busy="form.processing">
                {{ submitLabel || label('save', locale) }}
            </button>
            <slot name="actions" />
        </div>
    </form>
</template>
