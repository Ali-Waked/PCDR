<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    lede: { type: String, default: '' },
    actions: { type: Array, default: () => [] },
})
</script>

<template>
    <section class="ps-cta">
        <div class="ps-container">
            <p v-if="eyebrow" class="ps-eyebrow">{{ eyebrow }}</p>
            <h2>{{ title }}</h2>
            <p v-if="lede" class="ps-lede">{{ lede }}</p>
            <div v-if="actions.length" class="ps-actions">
                <Link
                    v-for="action in actions"
                    :key="`${action.label}-${action.url}`"
                    :href="action.url"
                    class="ps-btn"
                    :class="action.primary ? 'ps-btn-primary' : 'ps-btn-ghost'"
                >
                    {{ action.label }}
                </Link>
            </div>
        </div>
    </section>
</template>
