<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import UiIcon from './UiIcon.vue'
import { useAdminNotifications } from '../../composables/useAdminNotifications'

const { confirmation, closeConfirmation, acceptConfirmation, labels } = useAdminNotifications()
const dialog = ref(null)
const confirmButton = ref(null)
let previousFocus = null

const visible = computed(() => confirmation.open)

function focusables() {
    const root = dialog.value
    if (! root) {
        return []
    }
    return [...root.querySelectorAll('button:not([disabled]),[href],input:not([disabled]),[tabindex]:not([tabindex="-1"])')]
}

function onKeydown(event) {
    if (! visible.value) {
        return
    }
    if (event.key === 'Escape') {
        event.preventDefault()
        if (confirmation.busy) {
            return
        }
        closeConfirmation(false)
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
    document.body.style.overflow = 'hidden'
    requestAnimationFrame(() => (confirmButton.value || focusables()[0] || dialog.value)?.focus?.())
}

function deactivate() {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
    previousFocus?.focus?.()
    previousFocus = null
}

watch(visible, (open) => {
    if (open) {
        activate()
    } else {
        deactivate()
    }
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
    previousFocus?.focus?.()
    previousFocus = null
})
</script>

<template>
    <div v-if="visible" class="pc-modal-backdrop" @click.self="! confirmation.busy && closeConfirmation(false)">
        <div
            ref="dialog"
            class="pc-modal pc-modal--confirm"
            :class="{ 'pc-modal--danger': confirmation.danger }"
            role="alertdialog"
            aria-modal="true"
            :aria-labelledby="'pc-confirm-title'"
            :aria-describedby="confirmation.message ? 'pc-confirm-message' : undefined"
            tabindex="-1"
        >
            <div class="pc-modal__head">
                <div class="pc-confirm__heading">
                    <span class="pc-confirm__icon" aria-hidden="true">
                        <UiIcon :name="confirmation.danger ? 'trash' : 'alert'" :size="18" />
                    </span>
                    <h2 id="pc-confirm-title" class="pc-modal__title">{{ confirmation.title }}</h2>
                </div>
                <button
                    type="button"
                    class="pc-icon-button"
                    :aria-label="confirmation.cancelLabel"
                    :disabled="confirmation.busy"
                    @click="closeConfirmation(false)"
                >
                    <UiIcon name="close" :size="16" />
                </button>
            </div>
            <div class="pc-modal__body">
                <p v-if="confirmation.message" id="pc-confirm-message" class="pc-page-subtitle">{{ confirmation.message }}</p>
            </div>
            <div class="pc-modal__foot">
                <button type="button" class="pc-btn pc-btn--secondary" :disabled="confirmation.busy" @click="closeConfirmation(false)">
                    {{ confirmation.cancelLabel }}
                </button>
                <button
                    ref="confirmButton"
                    type="button"
                    class="pc-btn"
                    :class="confirmation.danger ? 'pc-btn--danger' : 'pc-btn--primary'"
                    :disabled="confirmation.busy"
                    :aria-busy="confirmation.busy ? 'true' : 'false'"
                    @click="acceptConfirmation"
                >
                    <span v-if="confirmation.busy" class="pc-toast__spinner" aria-hidden="true" />
                    {{ confirmation.busy ? labels.confirmLoading : confirmation.confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
