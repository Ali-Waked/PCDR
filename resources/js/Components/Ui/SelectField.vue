<script setup>
import { computed } from 'vue'
import SelectionControl from '../Selection/SelectionControl.vue'

const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    label: { type: String, required: true },
    name: { type: String, required: true },
    options: { type: Array, required: true },
    error: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    searchable: { type: Boolean, default: null },
    variant: { type: String, default: 'dashboard' },
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue'])

const shouldSearch = computed(() => (
    props.searchable === null ? props.options.length > 6 : props.searchable
))
</script>

<template>
    <SelectionControl
        :model-value="modelValue"
        :label="label"
        :name="name"
        :options="options"
        :error="error"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :searchable="shouldSearch"
        :variant="variant"
        :size="size"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
