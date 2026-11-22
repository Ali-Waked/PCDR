<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    processing: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const variants = {
    primary: 'bg-navy-700 text-white hover:bg-navy-800',
    secondary: 'bg-surface text-ink border border-subtle hover:bg-surface-alt',
    accent: 'bg-teal-700 text-white hover:bg-navy-900',
    destructive: 'bg-danger text-white hover:opacity-90',
    ghost: 'bg-transparent text-ink hover:bg-surface-alt',
}

const classes = computed(() => [
    'focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-colors duration-[140ms]',
    variants[props.variant] ?? variants.primary,
    (props.disabled || props.processing) && 'cursor-not-allowed opacity-60',
])
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled || processing" :aria-busy="processing">
        <slot />
    </button>
</template>
