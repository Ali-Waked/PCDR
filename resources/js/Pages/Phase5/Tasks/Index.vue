<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5FilterBar from '../../../Components/Phase5/Phase5FilterBar.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    tasks: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
    views: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
})

const { locale } = useI18n()

const title = computed(() => label('tasks', locale.value))
const rows = computed(() => rowsOf(props.tasks))

const columns = computed(() => [
    { key: 'code' },
    { key: 'title' },
    { key: 'status', badge: true },
    { key: 'priority', badge: true },
    { key: 'owner_name' },
    { key: 'due_at', format: 'date' },
    { key: 'progress_percent' },
])

const filterFields = computed(() => [
    { name: 'view', type: 'select', options: props.views ?? [] },
    { name: 'status', type: 'select', options: props.statuses ?? [] },
    { name: 'priority', type: 'select', options: props.priorities ?? [] },
])

const createFields = computed(() => [
    { name: 'title', required: true, wide: true },
    { name: 'description', type: 'textarea', wide: true },
    { name: 'priority', type: 'select', options: props.priorities ?? [] },
    { name: 'status', type: 'select', options: props.statuses ?? [] },
    { name: 'due_at', type: 'datetime-local' },
    { name: 'subject_type', type: 'select', options: ['project', 'activity', 'workflow_instance', 'document_review', 'referral'] },
    { name: 'subject_id', type: 'number', min: 1 },
])

function taskUrl(task) {
    return phase5Url('tasks.show', { task: task.id }, locale.value)
}
</script>

<template>
    <Phase5Shell :title="title">
        <Phase5Section :title="label('filters', locale)">
            <Phase5FilterBar
                :action="phase5Url('tasks.index', {}, locale)"
                :fields="filterFields"
            />
        </Phase5Section>

        <Phase5Section :title="label('tasks', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-code="{ row, value }">
                    <Link :href="taskUrl(row)" class="phase5-code">{{ value }}</Link>
                </template>
                <template #cell-progress_percent="{ value }">
                    <div class="phase5-progress" :aria-label="`${value ?? 0}%`">
                        <span :style="{ inlineSize: `${Math.min(100, Math.max(0, Number(value ?? 0)))}%` }" />
                    </div>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="tasks" />
            </div>
        </Phase5Section>

        <Phase5Section v-if="permissions?.create" :title="label('create', locale)">
            <Phase5Form
                :action="phase5Url('tasks.store', {}, locale)"
                :fields="createFields"
                :submit-label="label('create', locale)"
            />
        </Phase5Section>
    </Phase5Shell>
</template>
