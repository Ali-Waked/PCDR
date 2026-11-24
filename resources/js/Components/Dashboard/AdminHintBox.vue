<script setup>
import UiIcon from './UiIcon.vue'

defineProps({
    text: { type: String, default: '' },
})
</script>

<template>
    <div
        role="note"
        class="admin-hint flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm leading-relaxed text-blue-800"
    >
        <UiIcon name="info" :size="17" class="mt-0.5 shrink-0 text-blue-600" />
        <span><slot>{{ text }}</slot></span>
    </div>
</template>
