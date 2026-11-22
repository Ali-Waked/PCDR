<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
    inputmode: { type: String, default: null },
    dir: { type: String, default: null },
    disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const generatedId = useId()
const inputId = computed(() => `field-${props.name}-${generatedId}`)
</script>

<template>
    <div>
        <label class="pcdr-label" :for="inputId">
            {{ label }}
            <span v-if="required" aria-hidden="true">*</span>
        </label>
        <input
            :id="inputId"
            :name="name"
            :type="type"
            :value="modelValue"
            :required="required"
            :autocomplete="autocomplete"
            :inputmode="inputmode"
            :dir="dir"
            :disabled="disabled"
            class="pcdr-input disabled:opacity-60"
            :aria-invalid="error ? 'true' : 'false'"
            :aria-describedby="error ? `${inputId}-error` : undefined"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" :id="`${inputId}-error`" class="mt-1.5 text-sm text-danger" role="alert">{{ error }}</p>
    </div>
</template>
