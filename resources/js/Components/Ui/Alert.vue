<script setup>
defineProps({
    type: { type: String, default: 'success' },
    message: { type: String, default: '' },
})

const styles = {
    success: 'border-success/30 bg-teal-100 text-navy-900',
    error: 'border-danger/30 bg-red-50 text-danger',
    warning: 'border-warning/30 bg-amber-50 text-warning',
}
</script>

<template>
    <div v-if="message" class="rounded-md border px-4 py-3 text-sm" :class="styles[type] ?? styles.success" :role="type === 'error' ? 'alert' : 'status'">
        {{ message }}
    </div>
</template>
