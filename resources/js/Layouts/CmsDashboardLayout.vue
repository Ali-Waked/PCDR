<script setup>
import DashboardChrome from '../Components/Dashboard/DashboardChrome.vue'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    hint: { type: String, default: '' },
    breadcrumb: { type: Array, default: () => [] },
})
</script>

<template>
    <DashboardChrome
        mode="cms"
        :title="title"
        :subtitle="subtitle"
        :eyebrow="eyebrow"
        :hint="hint"
        :breadcrumb="breadcrumb"
    >
        <template v-if="$slots.actions" #actions>
            <slot name="actions" />
        </template>
        <slot />
    </DashboardChrome>
</template>
