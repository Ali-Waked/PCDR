<script setup>
defineProps({
    label: { type: String, required: true },
    name: { type: String, required: true },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    full: { type: Boolean, default: false },
})
</script>

<template>
    <div class="pc-field" :class="{ 'pc-field--full': full }">
        <label class="pc-label" :for="name">
            {{ label }}
            <span v-if="!required" class="pc-label__optional">—</span>
            <span v-if="required" class="pc-required" aria-hidden="true">*</span>
        </label>
        <slot :id="name" :error="error" />
        <p v-if="error" class="pc-error" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="pc-hint">{{ hint }}</p>
    </div>
</template>
