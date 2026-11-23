<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    tone: { type: String, default: 'slate' },
})

const allowedTones = ['teal', 'green', 'amber', 'red', 'slate']
const badgeTone = computed(() => allowedTones.includes(props.tone) ? props.tone : 'slate')
</script>

<template>
    <span class="pc-badge" :class="`pc-badge--${badgeTone}`">{{ label }}</span>
</template>
