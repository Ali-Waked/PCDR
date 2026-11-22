<script setup>
import { computed } from 'vue'
import { useI18n } from '../composables/useI18n'

const props = defineProps({
    status: { type: String, default: '' },
})

const { t } = useI18n()

const label = computed(() => t(props.status) || props.status.replaceAll('_', ' '))
</script>

<template>
    <span class="pill" :class="`pill--${status}`">{{ label }}</span>
</template>
