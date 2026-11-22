<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    body: { type: String, default: '' },
    actionLabel: { type: String, default: '' },
    actionUrl: { type: String, default: '' },
})
</script>

<template>
    <header class="section-heading" v-reveal>
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h2 class="section-title">{{ title }}</h2>
        <p v-if="body" class="lede" style="margin-block-start: 0.75rem">{{ body }}</p>
        <Link v-if="actionUrl && actionLabel" :href="actionUrl" class="text-link" style="margin-block-start: 1rem">
            {{ actionLabel }}
        </Link>
    </header>
</template>

<style scoped>
.section-heading {
    margin-block-end: 2rem;
}
</style>
