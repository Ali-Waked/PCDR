<script setup>
import { computed, useAttrs } from 'vue'
import SelectionControl from './SelectionControl.vue'
import { withStatusTone } from '../../Support/selectionStatuses'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Array, Object], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, default: '' },
    ariaLabel: { type: String, default: '' },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    emptyText: { type: String, default: '' },
    clearLabel: { type: String, default: '' },
    searchable: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
    multiple: { type: Boolean, default: false },
    chipLimit: { type: Number, default: 3 },
    variant: { type: String, default: 'dashboard' },
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue', 'change'])
const attrs = useAttrs()
const statusOptions = computed(() => withStatusTone(props.options))
const forwarded = computed(() => ({ ...props, ...attrs, options: statusOptions.value, status: true }))
</script>

<template>
    <SelectionControl
        v-bind="forwarded"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
    />
</template>
