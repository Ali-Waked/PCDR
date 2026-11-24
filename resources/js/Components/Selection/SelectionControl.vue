<script setup>
import { autoUpdate, computePosition, flip, shift } from '@floating-ui/dom'
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    useId,
    watch,
} from 'vue'
import UiIcon from '../Dashboard/UiIcon.vue'
import { statusTone } from '../../Support/selectionStatuses'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Array, Object], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, default: '' },
    ariaLabel: { type: String, default: '' },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    emptyText: { type: String, default: '' },
    clearLabel: { type: String, default: '' },
    loadingText: { type: String, default: '' },
    searchPlaceholder: { type: String, default: '' },
    searchable: { type: Boolean, default: true },
    clearable: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
    multiple: { type: Boolean, default: false },
    status: { type: Boolean, default: false },
    chips: { type: Boolean, default: true },
    max: { type: Number, default: 0 },
    chipLimit: { type: Number, default: 3 },
    emptyValue: { type: [String, Number, Boolean], default: '' },
    variant: { type: String, default: 'dashboard' },
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue', 'change', 'open', 'close'])

const { t, direction } = useI18n()

const root = ref(null)
const trigger = ref(null)
const panel = ref(null)
const searchInput = ref(null)
const open = ref(false)
const query = ref('')
const activeIndex = ref(0)
const panelStyle = ref({})
const generatedId = useId()
const baseId = `selection-${String(generatedId).replace(/[^a-zA-Z0-9_-]/g, '')}`
const listId = `${baseId}-list`
const labelId = `${baseId}-label`
const errorId = `${baseId}-error`

let stopAutoUpdate = null

function sameValue(first, second) {
    return String(first ?? '') === String(second ?? '')
}

function normalizeOption(raw) {
    if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
        const hasValue = Object.prototype.hasOwnProperty.call(raw, 'value')
        const value = hasValue
            ? raw.value
            : (raw.id ?? raw.key ?? raw.code ?? raw.slug ?? raw.name ?? raw.label ?? '')
        const label = raw.label ?? raw.name ?? raw.title ?? raw.text ?? raw.key ?? value

        return {
            ...raw,
            value,
            label: String(label ?? ''),
            description: raw.description ?? '',
            badge: raw.badge ?? '',
            icon: raw.icon ?? '',
            group: raw.group ?? raw.module ?? '',
            disabled: Boolean(raw.disabled),
            tone: raw.tone || (props.status ? statusTone(value) : ''),
        }
    }

    return {
        value: raw,
        label: String(raw ?? ''),
        description: '',
        badge: '',
        icon: '',
        group: '',
        disabled: false,
        tone: props.status ? statusTone(raw) : '',
    }
}

const normalizedOptions = computed(() => props.options.map(normalizeOption))

function optionSearchText(option) {
    return `${option.label} ${option.description} ${option.group}`.toLocaleLowerCase()
}

const filteredOptions = computed(() => {
    const term = query.value.trim().toLocaleLowerCase()
    if (!term) {
        return normalizedOptions.value
    }

    return normalizedOptions.value.filter((option) => optionSearchText(option).includes(term))
})

const navigableOptions = computed(() => filteredOptions.value.filter((option) => !option.disabled))

const optionRows = computed(() => {
    const rows = []
    let previousGroup = ''

    filteredOptions.value.forEach((option, index) => {
        const group = String(option.group ?? '')
        if (group && group !== previousGroup) {
            rows.push({ type: 'group', key: `${baseId}-group-${group}-${index}`, label: group })
            previousGroup = group
        }

        rows.push({ type: 'option', key: `${baseId}-option-${index}`, option, index })
    })

    return rows
})

const selectedValues = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

function isSelected(option) {
    if (props.multiple) {
        return selectedValues.value.some((value) => sameValue(value, option.value))
    }

    return sameValue(props.modelValue, option.value)
}

const selectedOption = computed(() => {
    if (props.multiple) {
        return null
    }

    return normalizedOptions.value.find(isSelected) ?? null
})

const selectedOptions = computed(() => (props.multiple
    ? normalizedOptions.value.filter(isSelected)
    : (selectedOption.value ? [selectedOption.value] : [])))

const hasSelection = computed(() => (props.multiple
    ? selectedValues.value.length > 0
    : (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '')))

const isEmpty = computed(() => (props.multiple
    ? selectedValues.value.length === 0
    : !hasSelection.value))

const activeOption = computed(() => navigableOptions.value[activeIndex.value] ?? null)

const displayLabel = computed(() => {
    if (!hasSelection.value) {
        return props.placeholder
    }

    if (props.multiple) {
        if (!selectedOptions.value.length) {
            return selectedValues.value.map(String).join(', ')
        }

        const labels = selectedOptions.value.map((option) => option.label)
        const first = labels.slice(0, props.chipLimit).join(', ')
        const extra = labels.length - Math.min(labels.length, props.chipLimit)
        return extra > 0 ? `${first} +${extra}` : first
    }

    return selectedOption.value?.label ?? String(props.modelValue)
})

const activeTone = computed(() => selectedOption.value?.tone || statusTone(props.modelValue))

const resolvedEmptyText = computed(() => props.emptyText || t('ui.no_results', 'No results'))
const resolvedClearLabel = computed(() => props.clearLabel || t('ui.clear', 'Clear'))
const resolvedLoadingText = computed(() => props.loadingText || t('ui.loading', 'Loading…'))
const resolvedSearchPlaceholder = computed(() => props.searchPlaceholder || props.label || t('ui.search', 'Search'))

const ariaDescribedBy = computed(() => (props.error ? errorId : undefined))

const formValue = computed(() => {
    if (props.multiple) {
        return selectedValues.value.map((value) => String(value ?? '')).join(',')
    }

    return props.modelValue === null || props.modelValue === undefined ? '' : String(props.modelValue)
})

function highlightedParts(text) {
    const value = String(text ?? '')
    const term = query.value.trim()
    if (!term) {
        return [{ text: value, marked: false }]
    }

    const parts = []
    const haystack = value.toLocaleLowerCase()
    const needle = term.toLocaleLowerCase()
    let cursor = 0
    let index = haystack.indexOf(needle)

    while (index !== -1) {
        if (index > cursor) {
            parts.push({ text: value.slice(cursor, index), marked: false })
        }
        parts.push({ text: value.slice(index, index + needle.length), marked: true })
        cursor = index + needle.length
        index = haystack.indexOf(needle, cursor)
    }

    if (cursor < value.length) {
        parts.push({ text: value.slice(cursor), marked: false })
    }

    return parts
}

function optionDomId(index) {
    return `${baseId}-option-dom-${index}`
}

function focusTrigger() {
    trigger.value?.focus()
}

function closePanel(restoreFocus = true) {
    if (!open.value) {
        return
    }

    open.value = false
    query.value = ''
    stopAutoUpdate?.()
    stopAutoUpdate = null
    emit('close')
    if (restoreFocus) {
        nextTick(focusTrigger)
    }
}

async function updatePanelPosition() {
    if (!trigger.value || !panel.value || typeof window === 'undefined') {
        return
    }

    const placement = direction.value === 'rtl' ? 'bottom-end' : 'bottom-start'
    const { x, y } = await computePosition(trigger.value, panel.value, {
        placement,
        strategy: 'fixed',
        middleware: [flip({ padding: 12 }), shift({ padding: 12 })],
    })
    const triggerWidth = trigger.value.getBoundingClientRect().width
    panelStyle.value = {
        position: 'fixed',
        top: `${y}px`,
        left: `${x}px`,
        width: `${Math.max(200, Math.min(triggerWidth, window.innerWidth - 24))}px`,
    }
}

async function openPanel() {
    if (props.disabled || open.value) {
        return
    }

    open.value = true
    const selectedIndex = navigableOptions.value.findIndex(isSelected)
    activeIndex.value = selectedIndex >= 0 ? selectedIndex : 0

    await nextTick()
    await updatePanelPosition()

    if (trigger.value && panel.value) {
        stopAutoUpdate = autoUpdate(trigger.value, panel.value, updatePanelPosition)
    }

    await nextTick()
    if (props.searchable) {
        searchInput.value?.focus()
    } else {
        focusTrigger()
    }

    emit('open')
}

function togglePanel() {
    if (open.value) {
        closePanel()
    } else {
        openPanel()
    }
}

function move(delta) {
    const total = navigableOptions.value.length
    if (!total) {
        return
    }

    activeIndex.value = (activeIndex.value + delta + total) % total
    const optionIndex = filteredOptions.value.indexOf(navigableOptions.value[activeIndex.value])
    nextTick(() => {
        document.getElementById(optionDomId(optionIndex))?.scrollIntoView({ block: 'nearest' })
    })
}

function emitValue(value) {
    emit('update:modelValue', value)
    emit('change', value)
}

function choose(option) {
    if (props.disabled || option?.disabled) {
        return
    }

    if (props.multiple) {
        const current = [...selectedValues.value]
        const index = current.findIndex((value) => sameValue(value, option.value))
        if (index >= 0) {
            current.splice(index, 1)
        } else {
            if (props.max > 0 && current.length >= props.max) {
                return
            }
            current.push(option.value)
        }
        emitValue(current)
        query.value = ''
        nextTick(() => (props.searchable ? searchInput.value : trigger.value)?.focus())
        return
    }

    emitValue(option.value)
    closePanel()
}

function removeValue(value) {
    if (props.disabled) {
        return
    }

    emitValue(selectedValues.value.filter((item) => !sameValue(item, value)))
}

function clearSelection() {
    if (props.disabled) {
        return
    }

    emitValue(props.multiple ? [] : props.emptyValue)
    query.value = ''
    focusTrigger()
}

function clearSearch() {
    query.value = ''
    activeIndex.value = 0
    searchInput.value?.focus()
}

function onKeydown(event) {
    if (props.disabled) {
        return
    }

    const key = event.key === 'Spacebar' ? ' ' : event.key

    if (!open.value) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(key)) {
            event.preventDefault()
            openPanel()
        }
        return
    }

    if (key === 'Escape') {
        event.preventDefault()
        closePanel()
        return
    }

    if (key === 'Tab') {
        closePanel(false)
        return
    }

    if (key === 'ArrowDown') {
        event.preventDefault()
        move(1)
        return
    }

    if (key === 'ArrowUp') {
        event.preventDefault()
        move(-1)
        return
    }

    if (key === 'Home') {
        event.preventDefault()
        activeIndex.value = 0
        return
    }

    if (key === 'End') {
        event.preventDefault()
        activeIndex.value = Math.max(0, navigableOptions.value.length - 1)
        return
    }

    if ((key === 'Enter' || key === ' ') && !(key === ' ' && event.target === searchInput.value)) {
        event.preventDefault()
        if (activeOption.value) {
            choose(activeOption.value)
        }
    }
}

function onPointerDown(event) {
    if (!open.value) {
        return
    }

    const target = event.target
    if (root.value?.contains(target) || trigger.value?.contains(target) || panel.value?.contains(target)) {
        return
    }

    closePanel(false)
}

watch(query, () => {
    activeIndex.value = 0
})

watch(filteredOptions, () => {
    if (activeIndex.value >= navigableOptions.value.length) {
        activeIndex.value = 0
    }
})

watch(() => props.modelValue, () => {
    if (!props.multiple && open.value) {
        const selectedIndex = navigableOptions.value.findIndex(isSelected)
        activeIndex.value = selectedIndex >= 0 ? selectedIndex : 0
    }
})

onMounted(() => document.addEventListener('pointerdown', onPointerDown))

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown)
    stopAutoUpdate?.()
})
</script>

<template>
    <div
        ref="root"
        class="pc-selection"
        :class="[
            `pc-selection--${variant}`,
            `pc-selection--${size}`,
            {
                'is-open': open,
                'is-disabled': disabled,
                'is-invalid': Boolean(error),
                'is-multiple': multiple,
                'is-status': status,
            },
        ]"
    >
        <input
            v-if="name && !multiple"
            type="text"
            class="pc-selection__native"
            :name="name"
            :value="formValue"
            :required="required && isEmpty"
            tabindex="-1"
            aria-hidden="true"
            @invalid="focusTrigger"
        >
        <template v-if="name && multiple">
            <input
                v-for="(value, index) in (Array.isArray(modelValue) ? modelValue : [])"
                :key="`${name}-${index}`"
                type="hidden"
                :name="`${name}[]`"
                :value="String(value ?? '')"
            >
        </template>
        <input
            v-if="!name && required"
            type="text"
            class="pc-selection__native"
            :value="formValue"
            :required="isEmpty"
            tabindex="-1"
            aria-hidden="true"
            @invalid="focusTrigger"
        >

        <span v-if="label" :id="labelId" class="pc-selection__label">
            {{ label }}
            <span v-if="required" aria-hidden="true">*</span>
        </span>

        <div class="pc-selection__control">
            <div
                ref="trigger"
                class="pc-selection__trigger"
                :class="{ 'is-disabled': disabled }"
                :aria-label="!label ? (ariaLabel || placeholder || t('ui.select', 'Select')) : undefined"
                :aria-labelledby="label ? labelId : undefined"
                :aria-describedby="ariaDescribedBy"
                :aria-expanded="open ? 'true' : 'false'"
                :aria-haspopup="'listbox'"
                :aria-controls="listId"
                :aria-activedescendant="open && activeOption && !searchable ? optionDomId(filteredOptions.indexOf(activeOption)) : undefined"
                :aria-invalid="error ? 'true' : 'false'"
                :aria-required="required ? 'true' : undefined"
                :aria-disabled="disabled ? 'true' : undefined"
                role="combobox"
                tabindex="0"
                @click="togglePanel"
                @keydown="onKeydown"
            >
                <span class="pc-selection__value" :class="{ 'is-placeholder': !hasSelection }">
                    <template v-if="multiple && selectedOptions.length">
                        <span
                            v-for="option in selectedOptions.slice(0, chipLimit)"
                            :key="`chip-${String(option.value)}`"
                            class="pc-selection__chip"
                        >
                            <span class="pc-selection__chip-label">{{ option.label }}</span>
                            <button
                                type="button"
                                class="pc-selection__chip-remove"
                                :aria-label="`${resolvedClearLabel}: ${option.label}`"
                                :disabled="disabled"
                                @click.stop="removeValue(option.value)"
                                @keydown.stop
                            >
                                <UiIcon name="close" :size="11" />
                            </button>
                        </span>
                        <span v-if="selectedOptions.length > chipLimit" class="pc-selection__more">
                            +{{ selectedOptions.length - chipLimit }}
                        </span>
                    </template>
                    <span v-else-if="status && hasSelection" class="sel-status" :class="`sel-status--${activeTone}`">
                        <span class="sel-status__dot" aria-hidden="true" />
                        <span>{{ displayLabel }}</span>
                    </span>
                    <span v-else class="pc-selection__text">{{ displayLabel }}</span>
                </span>
            </div>

            <span v-if="loading" class="pc-selection__spinner" aria-hidden="true" />
            <button
                v-else-if="clearable && hasSelection"
                type="button"
                class="pc-selection__clear"
                :aria-label="resolvedClearLabel"
                :disabled="disabled"
                @click="clearSelection"
            >
                <UiIcon name="close" :size="13" />
            </button>
            <UiIcon class="pc-selection__chevron" name="chevronDown" :size="15" aria-hidden="true" />
        </div>

        <p v-if="error" :id="errorId" class="pc-selection__error" role="alert">{{ error }}</p>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panel"
                class="pc-selection__panel"
                :class="`pc-selection__panel--${variant}`"
                :style="panelStyle"
                :dir="direction"
            >
                <label v-if="searchable" class="pc-selection__search">
                    <UiIcon name="search" :size="15" />
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="search"
                        autocomplete="off"
                        class="pc-selection__search-input"
                        :placeholder="resolvedSearchPlaceholder"
                        :aria-label="resolvedSearchPlaceholder"
                        :aria-activedescendant="activeOption ? optionDomId(filteredOptions.indexOf(activeOption)) : undefined"
                        @keydown="onKeydown"
                    >
                    <button
                        v-if="query"
                        type="button"
                        class="pc-selection__search-clear"
                        :aria-label="resolvedClearLabel"
                        @click="clearSearch"
                    >
                        <UiIcon name="close" :size="13" />
                    </button>
                </label>

                <ul
                    :id="listId"
                    class="pc-selection__list"
                    :class="{ 'is-multiple': multiple }"
                    role="listbox"
                    :aria-multiselectable="multiple ? 'true' : undefined"
                    :aria-labelledby="label ? labelId : undefined"
                    :aria-label="!label ? (ariaLabel || placeholder || t('ui.select', 'Select')) : undefined"
                >
                    <template v-for="row in optionRows" :key="row.key">
                        <li v-if="row.type === 'group'" class="pc-selection__group" role="presentation">
                            {{ row.label }}
                        </li>
                        <li
                            v-else
                            :id="optionDomId(row.index)"
                            :data-testid="row.option.testId || undefined"
                            :data-key="row.option.dataKey || undefined"
                            class="pc-selection__option"
                            :class="{
                                'is-active': row.index === filteredOptions.indexOf(activeOption),
                                'is-selected': isSelected(row.option),
                                'is-disabled': row.option.disabled,
                            }"
                            role="option"
                            :aria-selected="isSelected(row.option) ? 'true' : 'false'"
                            :aria-disabled="row.option.disabled ? 'true' : undefined"
                            @mousemove="activeIndex = navigableOptions.indexOf(row.option) >= 0 ? navigableOptions.indexOf(row.option) : activeIndex"
                            @click="choose(row.option)"
                        >
                            <span class="pc-selection__option-main">
                                <span
                                    v-if="status"
                                    class="sel-status__dot"
                                    :class="`sel-status__dot--${row.option.tone || 'slate'}`"
                                    aria-hidden="true"
                                />
                                <UiIcon
                                    v-else-if="row.option.icon"
                                    class="pc-selection__option-icon"
                                    :name="row.option.icon"
                                    :size="15"
                                />
                                <span class="pc-selection__option-copy">
                                    <span class="pc-selection__option-label">
                                        <span
                                            v-for="(part, partIndex) in highlightedParts(row.option.label)"
                                            :key="partIndex"
                                            :class="{ 'is-marked': part.marked }"
                                        >{{ part.text }}</span>
                                    </span>
                                    <span v-if="row.option.description" class="pc-selection__option-description">
                                        {{ row.option.description }}
                                    </span>
                                </span>
                                <span v-if="row.option.badge" class="pc-selection__option-badge">{{ row.option.badge }}</span>
                                <UiIcon
                                    v-if="isSelected(row.option)"
                                    class="pc-selection__option-check"
                                    name="check"
                                    :size="14"
                                />
                            </span>
                        </li>
                    </template>

                    <li v-if="loading && !filteredOptions.length" class="pc-selection__empty" role="status">
                        <span class="pc-selection__spinner" aria-hidden="true" />
                        {{ resolvedLoadingText }}
                    </li>
                    <li v-else-if="!filteredOptions.length" class="pc-selection__empty" role="status">
                        {{ resolvedEmptyText }}
                    </li>
                </ul>

                <div v-if="multiple && selectedOptions.length" class="pc-selection__footer">
                    <span>{{ selectedValues.length }} {{ t('ui.selected', 'selected') }}</span>
                    <button type="button" class="pc-selection__footer-action" @click="clearSelection">
                        {{ resolvedClearLabel }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
