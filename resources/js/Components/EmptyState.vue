<script setup>
defineProps({
    title: { type: String, default: '' },
    body: { type: String, default: '' },
})
</script>

<template>
    <div class="empty-state">
        <p v-if="title" style="font-weight: 700; color: var(--brand-navy-deep); margin: 0 0 0.35rem">{{ title }}</p>
        <p v-if="body" style="margin-inline: auto">{{ body }}</p>
        <slot />
    </div>
</template>
