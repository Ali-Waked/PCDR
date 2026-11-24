<script setup>
import { computed, useAttrs } from 'vue'
import SelectionControl from '../Selection/SelectionControl.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Array, Object], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, required: true },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    emptyText: { type: String, default: 'No results' },
    clearLabel: { type: String, default: 'Clear' },
    searchable: { type: Boolean, default: true },
    clearable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'change'])
const attrs = useAttrs()
const forwarded = computed(() => ({ ...props, ...attrs, emptyValue: null }))
</script>

<template>
    <SelectionControl
        v-bind="forwarded"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
    />
</template>
