<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import SelectionControl from '../Selection/SelectionControl.vue'
import StatusSelect from '../Selection/StatusSelect.vue'
import { useI18n } from '../../composables/useI18n'
import { label, optionLabel, optionValue } from './phase5'

const props = defineProps({
    action: { type: String, required: true },
    fields: { type: Array, default: () => [] },
    values: { type: Object, default: () => ({}) },
})

const { locale } = useI18n()

const form = reactive(Object.fromEntries(
    props.fields.map((field) => [field.name, props.values[field.name] ?? '']),
))

function apply() {
    const query = Object.fromEntries(
        Object.entries(form).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    )

    router.get(props.action, query, { preserveState: true, preserveScroll: true })
}

function clear() {
    for (const field of props.fields) {
        form[field.name] = ''
    }
    apply()
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
    <form class="pc-form-grid phase5-filters" @submit.prevent="apply">
        <div v-for="field in fields" :key="field.name" class="pc-field">
            <label class="pc-label" :for="`phase5-filter-${field.name}`">{{ field.label || label(field.name, locale) }}</label>
            <StatusSelect
                v-if="field.type === 'select' && isStatusField(field)"
                v-model="form[field.name]"
                :label="field.label || label(field.name, locale)"
                :name="field.name"
                :options="selectOptions(field)"
                :placeholder="label('all', locale)"
                clearable
                :searchable="(field.options ?? []).length > 8"
            />
            <SelectionControl
                v-else-if="field.type === 'select'"
                v-model="form[field.name]"
                :label="field.label || label(field.name, locale)"
                :name="field.name"
                :options="selectOptions(field)"
                :placeholder="label('all', locale)"
                clearable
                :searchable="(field.options ?? []).length > 6"
            />
            <input
                v-else
                :id="`phase5-filter-${field.name}`"
                v-model="form[field.name]"
                class="pc-input"
                :type="field.type || 'text'"
                :placeholder="field.placeholder || ''"
            />
        </div>

        <div class="pc-field pc-field--action phase5-filters__actions">
            <button type="submit" class="pc-btn pc-btn--secondary pc-btn--sm">{{ label('filters', locale) }}</button>
            <button type="button" class="pc-btn pc-btn--ghost pc-btn--sm" @click="clear">{{ label('cancel', locale) }}</button>
        </div>
    </form>
</template>
