<script setup>
defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
})
</script>

<template>
    <section class="pc-form-section">
        <div class="pc-form-section__head">
            <div>
                <h2 class="pc-form-section__title">{{ title }}</h2>
                <p v-if="hint" class="pc-form-section__hint">{{ hint }}</p>
            </div>
            <slot name="actions" />
        </div>
        <slot />
    </section>
</template>
