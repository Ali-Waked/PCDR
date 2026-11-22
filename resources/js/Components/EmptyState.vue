<script setup>
defineProps({
    title: { type: String, default: '' },
    body: { type: String, default: '' },
})
</script>

<template>
    <div class="ps-empty">
        <h3 v-if="title">{{ title }}</h3>
        <p v-if="body">{{ body }}</p>
        <slot />
    </div>
</template>
