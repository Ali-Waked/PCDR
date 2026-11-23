<script setup>
import DashboardChrome from '../Components/Dashboard/DashboardChrome.vue'

defineProps({
    title: { type: String, default: '' },
})
</script>

<template>
    <DashboardChrome mode="admin" :title="title">
        <slot />
    </DashboardChrome>
</template>
