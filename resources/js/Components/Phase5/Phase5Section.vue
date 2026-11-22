<script setup>
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    flush: { type: Boolean, default: false },
})
</script>

<template>
    <section class="pcdr-card phase5-section" :class="{ 'is-flush': flush }">
        <header v-if="title || $slots.actions" class="phase5-section__head">
            <div>
                <h2 v-if="title">{{ title }}</h2>
                <p v-if="subtitle" class="phase5-section__subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="phase5-section__actions">
                <slot name="actions" />
            </div>
        </header>
        <slot />
    </section>
</template>
