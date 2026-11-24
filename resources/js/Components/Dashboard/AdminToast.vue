<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import UiIcon from './UiIcon.vue'
import { useAdminNotifications } from '../../composables/useAdminNotifications'

const props = defineProps({
    item: { type: Object, required: true },
})

const { dismiss, labels } = useAdminNotifications()
const timer = ref(null)
const isPaused = ref(false)

const iconName = computed(() => {
    if (props.item.type === 'success') return 'check'
    if (props.item.type === 'loading') return 'clock'
    if (props.item.type === 'info') return 'info'
    return 'alert'
})

function schedule() {
    clearTimeout(timer.value)
    if (props.item.sticky || ! props.item.duration || isPaused.value) {
        return
    }
    timer.value = window.setTimeout(() => dismiss(props.item.id), props.item.duration)
}

watch(() => [props.item.duration, props.item.sticky, isPaused.value], schedule)
onMounted(schedule)
onBeforeUnmount(() => clearTimeout(timer.value))
</script>

<template>
    <div
        class="pc-toast"
        :class="[`pc-toast--${item.type}`]"
        :data-variant="item.type"
        :role="item.type === 'error' || item.type === 'warning' ? 'alert' : 'status'"
        :aria-label="item.title"
        :aria-busy="item.type === 'loading' ? 'true' : 'false'"
        :aria-atomic="true"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
        @focusin="isPaused = true"
        @focusout="isPaused = false"
    >
        <span class="pc-toast__icon" aria-hidden="true">
            <UiIcon :name="iconName" :size="18" />
        </span>
        <div class="pc-toast__copy">
            <strong class="pc-toast__title">{{ item.title }}</strong>
            <p v-if="item.message" class="pc-toast__message">{{ item.message }}</p>
        </div>
        <span v-if="item.type === 'loading'" class="pc-toast__spinner" aria-hidden="true" />
        <button
            v-if="! item.sticky"
            type="button"
            class="pc-toast__close"
            :aria-label="labels.dismiss"
            @click="dismiss(item.id)"
        >
            <UiIcon name="close" :size="14" />
        </button>
        <span
            v-if="! item.sticky && item.duration"
            class="pc-toast__timer"
            :style="{ animationDuration: `${item.duration}ms`, animationPlayState: isPaused ? 'paused' : 'running' }"
            aria-hidden="true"
        />
    </div>
</template>
