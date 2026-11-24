<script setup>
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    badge: { type: [String, Number], default: '' },
})
</script>

<template>
    <section class="pc-card pc-content-section">
        <div class="pc-card__head">
            <div>
                <h2 class="pc-card__title">{{ title }}</h2>
                <p v-if="description" class="pc-card__subtitle">{{ description }}</p>
            </div>
            <span v-if="badge !== '' && badge !== null" class="pc-badge pc-badge--slate">{{ badge }}</span>
            <slot name="actions" />
        </div>
        <div class="pc-card__body pc-stack">
            <slot />
        </div>
    </section>
</template>
