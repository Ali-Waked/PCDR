<script setup>
import { ref, watch } from 'vue'
import { useI18n } from '../composables/useI18n'

const { flash } = useI18n()
const visible = ref({ success: true, error: true, warning: true })

watch(flash, () => {
    visible.value = { success: true, error: true, warning: true }
})
</script>

<template>
    <div v-if="flash.success || flash.error || flash.warning" class="toast-stack" aria-live="polite">
        <div v-if="flash.success && visible.success" class="alert alert--success" role="status">
            <span aria-hidden="true">✓</span>
            <span class="flex-1">{{ flash.success }}</span>
            <button type="button" class="btn btn-ghost btn-small" @click="visible.success = false">×</button>
        </div>
        <div v-if="flash.error && visible.error" class="alert alert--error" role="alert">
            <span aria-hidden="true">!</span>
            <span class="flex-1">{{ flash.error }}</span>
            <button type="button" class="btn btn-ghost btn-small" @click="visible.error = false">×</button>
        </div>
        <div v-if="flash.warning && visible.warning" class="alert alert--warning" role="status">
            <span aria-hidden="true">△</span>
            <span class="flex-1">{{ flash.warning }}</span>
            <button type="button" class="btn btn-ghost btn-small" @click="visible.warning = false">×</button>
        </div>
    </div>
</template>
