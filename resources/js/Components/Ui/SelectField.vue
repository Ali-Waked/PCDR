<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    name: { type: String, required: true },
    options: { type: Array, required: true },
    error: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const generatedId = useId()
const selectId = computed(() => `field-${props.name}-${generatedId}`)
</script>

<template>
    <div>
        <label class="pcdr-label" :for="selectId">{{ label }}</label>
        <select
            :id="selectId"
            :name="name"
            :value="modelValue"
            class="pcdr-input"
            :aria-invalid="error ? 'true' : 'false'"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <p v-if="error" class="mt-1.5 text-sm text-danger" role="alert">{{ error }}</p>
    </div>
</template>
