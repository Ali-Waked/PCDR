<script setup>
defineProps({
    tone: { type: String, default: 'neutral' },
})

const tones = {
    neutral: 'bg-navy-100 text-navy-800',
    success: 'bg-teal-100 text-teal-700',
    warning: 'bg-amber-100 text-warning',
    danger: 'bg-red-100 text-danger',
}
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="tones[tone] ?? tones.neutral">
        <slot />
    </span>
</template>
