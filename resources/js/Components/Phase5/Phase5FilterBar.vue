<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
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
</script>

<template>
    <form class="phase5-filters" @submit.prevent="apply">
        <div v-for="field in fields" :key="field.name" class="field">
            <label :for="`phase5-filter-${field.name}`">{{ field.label || label(field.name, locale) }}</label>
            <select
                v-if="field.type === 'select'"
                :id="`phase5-filter-${field.name}`"
                v-model="form[field.name]"
                class="select"
            >
                <option value="">{{ label('all', locale) }}</option>
                <option
                    v-for="option in field.options ?? []"
                    :key="optionValue(option)"
                    :value="optionValue(option)"
                >
                    {{ optionLabel(option, locale) }}
                </option>
            </select>
            <input
                v-else
                :id="`phase5-filter-${field.name}`"
                v-model="form[field.name]"
                class="input"
                :type="field.type || 'text'"
                :placeholder="field.placeholder || ''"
            />
        </div>

        <div class="phase5-filters__actions">
            <button type="submit" class="btn btn-secondary btn-small">{{ label('filters', locale) }}</button>
            <button type="button" class="btn btn-ghost btn-small" @click="clear">{{ label('cancel', locale) }}</button>
        </div>
    </form>
</template>
