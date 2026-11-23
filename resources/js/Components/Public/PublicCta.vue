<script setup>
import PageCta from './PageCta.vue'

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    lede: { type: String, default: '' },
    actions: { type: Array, default: () => [] },
})
</script>

<template>
    <PageCta :eyebrow="eyebrow" :title="title" :lede="lede" :actions="actions" />
</template>
