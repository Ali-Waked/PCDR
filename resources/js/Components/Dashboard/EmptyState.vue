<script setup>
import UiIcon from './UiIcon.vue'

defineProps({
    title: { type: String, required: true },
    message: { type: String, default: '' },
    icon: { type: String, default: 'inbox' },
})
</script>

<template>
    <div class="pc-empty" role="status">
        <span class="pc-empty__icon"><UiIcon :name="icon" :size="24" /></span>
        <h3 class="pc-empty__title">{{ title }}</h3>
        <p v-if="message" class="pc-empty__text">{{ message }}</p>
        <div v-if="$slots.default"><slot /></div>
    </div>
</template>
