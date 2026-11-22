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
    <header class="ps-heading" v-reveal>
        <p v-if="eyebrow" class="ps-eyebrow">{{ eyebrow }}</p>
        <h2>{{ title }}</h2>
        <p v-if="body" class="ps-lede">{{ body }}</p>
        <Link v-if="actionUrl && actionLabel" :href="actionUrl" class="ps-text-link">
            {{ actionLabel }}
        </Link>
    </header>
</template>
