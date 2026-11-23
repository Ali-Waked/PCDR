<script setup>
import { computed } from 'vue'
import StatusBadge from './StatusBadge.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    status: { type: String, default: '' },
    label: { type: String, default: '' },
})

const { t } = useI18n()

const statusKeys = {
    draft: 'draft',
    pending_review: 'pending_review',
    approved: 'approved',
    published: 'published',
    archived: 'archived',
    new: 'cms_status_new',
    in_progress: 'cms_status_in_progress',
    resolved: 'cms_status_resolved',
    closed: 'cms_status_closed',
    active: 'active',
    inactive: 'inactive',
}

const toneMap = {
    draft: 'slate',
    pending_review: 'amber',
    approved: 'teal',
    published: 'green',
    archived: 'slate',
    new: 'teal',
    in_progress: 'amber',
    resolved: 'green',
    closed: 'slate',
    active: 'green',
    inactive: 'slate',
}

const displayLabel = computed(() => {
    if (props.label) {
        return props.label
    }

    if (statusKeys[props.status]) {
        return t(statusKeys[props.status])
    }

    return String(props.status || '').replaceAll('_', ' ')
})

const tone = computed(() => toneMap[props.status] ?? 'slate')
</script>

<template>
    <StatusBadge :label="displayLabel" :tone="tone" />
</template>
