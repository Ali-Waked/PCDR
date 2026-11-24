<script setup>
import { computed, useAttrs } from 'vue'
import SelectionControl from './SelectionControl.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, default: () => [] },
    label: { type: String, default: '' },
    ariaLabel: { type: String, default: '' },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    emptyText: { type: String, default: '' },
    clearLabel: { type: String, default: '' },
    searchPlaceholder: { type: String, default: '' },
    searchable: { type: Boolean, default: true },
    clearable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    max: { type: Number, default: 0 },
    chipLimit: { type: Number, default: 3 },
    variant: { type: String, default: 'dashboard' },
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue', 'change'])
const attrs = useAttrs()
const forwarded = computed(() => ({ ...props, ...attrs, multiple: true }))
</script>

<template>
    <SelectionControl
        v-bind="forwarded"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
    />
</template>
