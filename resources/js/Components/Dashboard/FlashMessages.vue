<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import UiIcon from './UiIcon.vue'

const props = defineProps({
    messages: { type: Object, default: null },
})

const page = usePage()
const activeMessages = computed(() => props.messages ?? page.props.flash ?? {})

const visible = computed(() => Object.entries(activeMessages.value)
    .filter(([key, value]) => ['success', 'error', 'warning', 'info'].includes(key) && value)
    .map(([type, message]) => ({ type, message })))
</script>

<template>
    <div class="pc-toast-stack" aria-live="polite" aria-atomic="true">
        <div
            v-for="item in visible"
            :key="item.type"
            class="pc-toast"
            :class="{ 'pc-toast--error': item.type === 'error', 'pc-toast--warning': item.type === 'warning' }"
        >
            <UiIcon class="pc-toast__icon" :name="item.type === 'error' ? 'alert' : 'check'" :size="17" />
            <span>{{ item.message }}</span>
        </div>
    </div>
</template>
