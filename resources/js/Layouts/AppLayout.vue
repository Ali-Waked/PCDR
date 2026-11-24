<script setup>
import DashboardChrome from '../Components/Dashboard/DashboardChrome.vue'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
})
</script>

<template>
    <DashboardChrome mode="admin" :title="title" :subtitle="subtitle" :eyebrow="eyebrow">
        <template v-if="$slots.actions" #actions>
            <slot name="actions" />
        </template>
        <slot />
    </DashboardChrome>
</template>
