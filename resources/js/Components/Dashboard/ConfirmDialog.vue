<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import UiIcon from './UiIcon.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, required: true },
    cancelLabel: { type: String, required: true },
    busy: { type: Boolean, default: false },
    danger: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])
const dialog = ref(null)
let previousFocus = null

function focusables() {
    const root = dialog.value
    if (! root) {
        return []
    }

    return [...root.querySelectorAll('button:not([disabled]),[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')]
}

function onKeydown(event) {
    if (! props.open) {
        return
    }

    if (event.key === 'Escape') {
        event.preventDefault()
        emit('close')
        return
    }

    if (event.key !== 'Tab') {
        return
    }

    const items = focusables()
    if (! items.length) {
        event.preventDefault()
        return
    }

    const first = items[0]
    const last = items[items.length - 1]

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
    } else if (! event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
    }
}

function activate() {
    previousFocus = document.activeElement
    document.addEventListener('keydown', onKeydown)
    requestAnimationFrame(() => (focusables()[0] || dialog.value)?.focus?.())
}

function deactivate() {
    document.removeEventListener('keydown', onKeydown)
    previousFocus?.focus?.()
    previousFocus = null
}

watch(() => props.open, (open) => {
    if (open) {
        activate()
    } else {
        deactivate()
    }
})

onMounted(() => {
    if (props.open) {
        activate()
    }
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    previousFocus?.focus?.()
})
</script>

<template>
    <div v-if="open" class="pc-modal-backdrop" @click.self="emit('close')">
        <div ref="dialog" class="pc-modal" role="dialog" aria-modal="true" :aria-label="title" tabindex="-1">
            <div class="pc-modal__head">
                <h2 class="pc-modal__title">{{ title }}</h2>
                <button type="button" class="pc-icon-button" :aria-label="cancelLabel" @click="emit('close')">
                    <UiIcon name="close" :size="16" />
                </button>
            </div>
            <div class="pc-modal__body">
                <p v-if="message" class="pc-page-subtitle">{{ message }}</p>
            </div>
            <div class="pc-modal__foot">
                <button type="button" class="pc-btn pc-btn--secondary" :disabled="busy" @click="emit('close')">
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    class="pc-btn"
                    :class="danger ? 'pc-btn--danger' : 'pc-btn--primary'"
                    :disabled="busy"
                    @click="emit('confirm')"
                >
                    {{ busy ? '…' : confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
