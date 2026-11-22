<script setup>
import { computed } from 'vue'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    reviews: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
    permissions: { type: Object, default: () => ({}) },
    notice: { type: String, default: '' },
})

const { locale } = useI18n()

const title = computed(() => label('document_reviews', locale.value))
const rows = computed(() => rowsOf(props.reviews))

const columns = computed(() => [
    { key: 'reference' },
    { key: 'title' },
    { key: 'status', badge: true },
    { key: 'version' },
    { key: 'subjectType' },
    { key: 'dueAt', format: 'date' },
    { key: 'createdAt', format: 'date' },
    { key: 'download', label: label('download', locale.value) },
])

const createFields = computed(() => [
    { name: 'title', required: true, wide: true },
    { name: 'description', type: 'textarea', wide: true },
    {
        name: 'subjectType',
        type: 'select',
        options: ['project', 'activity', 'workflow_instance', 'accountability_case', 'referral'],
    },
    { name: 'subjectId', type: 'number', min: 1 },
    { name: 'dueAt', type: 'datetime-local' },
])
</script>

<template>
    <Phase5Shell :title="title" :notice="notice">
        <Phase5Section :title="label('document_reviews', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-reference="{ value }">
                    <span class="phase5-code">{{ value }}</span>
                </template>
                <template #cell-download="{ value }">
                    <a v-if="value" :href="value" class="btn btn-ghost btn-small">{{ label('download', locale) }}</a>
                    <span v-else class="phase5-muted">—</span>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="reviews" />
            </div>
        </Phase5Section>

        <Phase5Section v-if="permissions?.submit" :title="label('create', locale) + ' · ' + label('document_reviews', locale)">
            <div style="padding: 1rem 1.25rem 1.25rem">
                <Phase5Form
                    :action="phase5Url('documents.store', {}, locale)"
                    :fields="createFields"
                    :submit-label="label('submit', locale)"
                />
            </div>
        </Phase5Section>
    </Phase5Shell>
</template>
