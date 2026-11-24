<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import CmsStatusBadge from './CmsStatusBadge.vue'
import UiIcon from './UiIcon.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    open: { type: Boolean, default: false },
    item: { type: Object, default: null },
    actions: { type: Array, default: () => [] },
    paths: { type: Object, default: () => ({}) },
    statusLabels: { type: Object, default: () => ({}) },
    selected: { type: String, default: '' },
    busy: { type: Boolean, default: false },
    confirmLabel: { type: String, default: '' },
    cancelLabel: { type: String, default: '' },
    titleLabel: { type: String, default: '' },
    sequenceLabel: { type: String, default: '' },
    forbiddenHint: { type: String, default: '' },
    noActionLabel: { type: String, default: '' },
})

const emit = defineEmits(['close', 'confirm', 'update:selected'])

const { t } = useI18n()

const dialog = ref(null)
let previousFocus = null

const currentStatus = computed(() => props.item?.status ?? '')
const selectedAction = computed(() => props.actions.find((action) => action.value === props.selected) ?? null)

const sequence = computed(() => {
    const path = props.paths?.[currentStatus.value]?.[props.selected] ?? []
    return path.map((value) => props.statusLabels?.[value] ?? value)
})

const displayTitle = computed(() => (
    props.titleLabel || `${t('ui.cms_pillars_page.workflow')}: ${props.item?.title || `#${props.item?.id ?? ''}`}`
))

function translate(key, replacements = {}) {
    let value = String(t(key) ?? '')
    for (const [name, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${name}`, String(replacement ?? ''))
    }

    return value
}

function focusables() {
    const root = dialog.value
    if (! root) {
        return []
    }

    return [...root.querySelectorAll('button:not([disabled]),[href],input:not([disabled]),[tabindex]:not([tabindex="-1"])')]
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
        <div ref="dialog" class="pc-modal pc-workflow-dialog" role="dialog" aria-modal="true" :aria-label="displayTitle" tabindex="-1">
            <div class="pc-modal__head">
                <div>
                    <h2 class="pc-modal__title">{{ displayTitle }}</h2>
                    <p class="pc-workflow-dialog__subtitle">
                        <span>{{ t('ui.cms_pillars_page.alert.current_status') }}</span>
                        <CmsStatusBadge :status="currentStatus" />
                    </p>
                </div>
                <button type="button" class="pc-icon-button" :aria-label="cancelLabel || t('ui.cancel')" :disabled="busy" @click="emit('close')">
                    <UiIcon name="close" :size="16" />
                </button>
            </div>

            <div class="pc-modal__body">
                <template v-if="actions.length">
                    <fieldset class="pc-workflow-dialog__options">
                        <legend class="sr-only">{{ t('ui.cms_pillars_page.workflow') }}</legend>
                        <label
                            v-for="action in actions"
                            :key="action.value"
                            class="pc-workflow-dialog__option"
                            :class="{ 'is-selected': selected === action.value }"
                        >
                            <input
                                type="radio"
                                name="pc-workflow-target"
                                :value="action.value"
                                :checked="selected === action.value"
                                :disabled="busy"
                                @change="emit('update:selected', action.value)"
                            >
                            <span class="pc-workflow-dialog__option-copy">
                                <strong>{{ action.label }}</strong>
                                <small>{{ action.description }}</small>
                            </span>
                            <CmsStatusBadge :status="action.value" />
                        </label>
                    </fieldset>

                    <div v-if="sequence.length" class="pc-workflow-dialog__sequence">
                        <span class="pc-workflow-dialog__sequence-label">
                            <UiIcon name="info" :size="14" />
                            {{ sequenceLabel || t('ui.cms_pillars_page.workflow_sequence') }}
                        </span>
                        <ol>
                            <li v-for="(status, index) in sequence" :key="`${status}-${index}`" :class="{ 'is-current': index === 0 }">
                                <span>{{ status }}</span>
                            </li>
                        </ol>
                    </div>

                    <p v-if="forbiddenHint" class="pc-workflow-dialog__hint">{{ forbiddenHint }}</p>
                    <p v-if="selectedAction" class="pc-workflow-dialog__confirm-copy">
                        {{ translate('ui.cms_pillars_page.confirm_transition', {
                            title: item?.title || `#${item?.id}`,
                            from: statusLabels?.[currentStatus] || currentStatus,
                            to: selectedAction.label,
                        }) }}
                    </p>
                </template>

                <div v-else class="pc-workflow-dialog__empty">
                    <UiIcon name="lock" :size="24" />
                    <p>{{ noActionLabel || t('ui.cms_pillars_page.no_next_action') }}</p>
                </div>
            </div>

            <div class="pc-modal__foot">
                <button type="button" class="pc-btn pc-btn--secondary" :disabled="busy" @click="emit('close')">
                    {{ cancelLabel || t('ui.cancel') }}
                </button>
                <button
                    v-if="actions.length"
                    type="button"
                    class="pc-btn pc-btn--primary"
                    :disabled="busy || !selected"
                    @click="emit('confirm', selected)"
                >
                    <span v-if="busy" class="pc-btn__spinner" aria-hidden="true" />
                    {{ busy ? t('ui.cms_pillars_page.loading') : (confirmLabel || t('ui.cms_pillars_page.confirm')) }}
                </button>
            </div>
        </div>
    </div>
</template>
