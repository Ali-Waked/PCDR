<script setup>
import { computed, ref, watch } from 'vue'
import SelectionControl from '../Selection/SelectionControl.vue'
import StatusSelect from '../Selection/StatusSelect.vue'
import UiIcon from '../Dashboard/UiIcon.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    search: { type: String, default: '' },
    status: { type: String, default: 'all' },
    sort: { type: String, default: '' },
    statuses: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    hasFilters: { type: Boolean, default: false },
    processing: { type: Boolean, default: false },
    labels: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:search', 'update:status', 'update:sort', 'update:group', 'clear'])

const { t } = useI18n()
const term = ref(props.search)

watch(() => props.search, (value) => {
    if (String(value ?? '') !== term.value) {
        term.value = String(value ?? '')
    }
})

const statusOptions = computed(() => [
    {
        value: 'all',
        label: props.labels.allStatuses || t('ui.cms_content_page.all_statuses'),
        description: '',
    },
    ...props.statuses.map((status) => ({
        value: status.value,
        label: status.label,
        description: status.description || '',
    })),
])

const labels = computed(() => ({
    search: props.labels.search || t('ui.cms_content_page.search'),
    status: props.labels.status || t('ui.cms_content_page.status_filter'),
    sort: props.labels.sort || t('ui.cms_content_page.sort'),
    clear: props.labels.clear || t('ui.cms_content_page.clear_filters'),
    empty: props.labels.empty || t('ui.no_results'),
}))

function valueFor(key) {
    return props.filters?.[key] ?? ''
}

function onSearch(event) {
    term.value = event.target.value
    emit('update:search', term.value)
}
</script>

<template>
    <div class="pc-content-filters" role="search">
        <label class="pc-content-filters__search">
            <span class="sr-only">{{ labels.search }}</span>
            <UiIcon name="search" :size="17" />
            <input
                :value="term"
                type="search"
                autocomplete="off"
                :placeholder="labels.search"
                :disabled="processing"
                @input="onSearch"
            >
            <span v-if="processing" class="pc-content-filters__spinner" aria-hidden="true" />
        </label>

        <div class="pc-content-filters__select">
            <StatusSelect
                :model-value="status"
                :label="labels.status"
                :options="statusOptions"
                :searchable="true"
                :loading="processing"
                :empty-text="labels.empty"
                @update:model-value="emit('update:status', $event)"
            />
        </div>

        <div v-for="group in groups" :key="group.key" class="pc-content-filters__select">
            <SelectionControl
                :model-value="valueFor(group.key)"
                :label="group.label"
                :options="group.options"
                :placeholder="labels.empty"
                :empty-text="labels.empty"
                :clear-label="labels.clear"
                :searchable="group.options.length > 6"
                :loading="processing"
                clearable
                @update:model-value="emit('update:group', { key: group.key, value: $event })"
            />
        </div>

        <div class="pc-content-filters__select">
            <SelectionControl
                :model-value="sort"
                :label="labels.sort"
                :options="sortOptions"
                :searchable="sortOptions.length > 6"
                :loading="processing"
                @update:model-value="emit('update:sort', $event)"
            />
        </div>

        <button
            type="button"
            class="pc-btn pc-btn--secondary pc-content-filters__clear"
            :disabled="!hasFilters || processing"
            @click="emit('clear')"
        >
            <UiIcon name="refresh" :size="15" />
            {{ labels.clear }}
        </button>
    </div>
</template>
