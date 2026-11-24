<script setup>
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    flush: { type: Boolean, default: false },
})
</script>

<template>
    <section class="pc-card phase5-section">
        <header v-if="title || $slots.actions" class="pc-card__head">
            <div>
                <h2 v-if="title" class="pc-card__title">{{ title }}</h2>
                <p v-if="subtitle" class="pc-card__subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="pc-page-actions">
                <slot name="actions" />
            </div>
        </header>
        <div class="pc-card__body" :class="{ 'pc-card__body--flush': flush }">
            <slot />
        </div>
    </section>
</template>
