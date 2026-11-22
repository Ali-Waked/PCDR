<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, required: true },
    name: { type: String, required: true },
})

defineEmits(['update:modelValue'])

const generatedId = useId()
const inputId = computed(() => `field-${props.name}-${generatedId}`)
</script>

<template>
    <div class="flex items-center gap-2">
        <input
            :id="inputId"
            :name="name"
            type="checkbox"
            class="h-5 w-5 rounded border-subtle text-teal-700 focus-ring"
            :checked="modelValue"
            @change="$emit('update:modelValue', $event.target.checked)"
        />
        <label :for="inputId" class="text-sm text-ink">{{ label }}</label>
    </div>
</template>
