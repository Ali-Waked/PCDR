<script setup>
/**
 * Renders HTML that has already been sanitised by App\Support\RichText::render()
 * on the server. Never pass unsanitised content to this component.
 */
defineProps({
    html: { type: String, default: '' },
})
</script>

<template>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-html="html" />
</template>
