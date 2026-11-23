<script setup>
import UiIcon from './UiIcon.vue'

defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    hint: { type: String, default: '' },
    icon: { type: String, default: 'file' },
    tone: { type: String, default: 'teal' },
})
</script>

<template>
    <article class="pc-metric" :class="{ 'pc-metric--sand': tone === 'sand', 'pc-metric--navy': tone === 'navy' }">
        <div class="pc-metric__head">
            <span class="pc-metric__label">{{ label }}</span>
            <span class="pc-metric__icon"><UiIcon :name="icon" :size="18" /></span>
        </div>
        <div class="pc-metric__value">{{ value }}</div>
        <div v-if="hint" class="pc-metric__hint">{{ hint }}</div>
    </article>
</template>
