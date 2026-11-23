<script setup>
import DashboardChrome from '../Components/Dashboard/DashboardChrome.vue'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <DashboardChrome mode="cms" :title="title" :subtitle="subtitle">
        <slot />
    </DashboardChrome>
</template>
