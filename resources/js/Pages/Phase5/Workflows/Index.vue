<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    definitions: { type: Object, default: () => ({}) },
    instances: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
    notice: { type: String, default: '' },
})

const { locale } = useI18n()

const title = computed(() => label('workflows', locale.value))
const definitionRows = computed(() => rowsOf(props.definitions))
const instanceRows = computed(() => rowsOf(props.instances))

const definitionColumns = computed(() => [
    { key: 'name' },
    { key: 'key' },
    { key: 'category' },
    { key: 'status', badge: true },
    { key: 'latest_version' },
    { key: 'created_at', format: 'date' },
])

const instanceColumns = computed(() => [
    { key: 'reference' },
    { key: 'name' },
    { key: 'status', badge: true },
    { key: 'current_step_order' },
    { key: 'due_at', format: 'date' },
    { key: 'created_at', format: 'date' },
])

const createFields = computed(() => [
    { name: 'key', required: true },
    { name: 'name', required: true },
    { name: 'description', type: 'textarea', wide: true },
    { name: 'category', type: 'select', options: props.categories ?? [] },
])

function instanceUrl(instance) {
    return phase5Url('workflows.instances.show', { instance: instance.id }, locale.value)
}
</script>

<template>
    <Phase5Shell :title="title" :notice="notice">
        <template #actions>
            <Link :href="phase5Url('workflows.instances.index', {}, locale)" class="btn btn-secondary btn-small">
                {{ label('instances', locale) }}
            </Link>
        </template>

        <Phase5Section :title="label('definitions', locale)" flush>
            <Phase5Table :columns="definitionColumns" :rows="definitionRows" compact />
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="definitions" page-key="definitionPage" />
            </div>
        </Phase5Section>

        <Phase5Section :title="label('instances', locale)" flush>
            <Phase5Table :columns="instanceColumns" :rows="instanceRows" compact>
                <template #cell-reference="{ row, value }">
                    <Link :href="instanceUrl(row)" class="phase5-code">{{ value }}</Link>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="instances" page-key="instancePage" />
            </div>
        </Phase5Section>

        <Phase5Section v-if="permissions?.create" :title="label('create', locale) + ' · ' + label('definitions', locale)">
            <Phase5Form
                :action="phase5Url('definitions.store', {}, locale)"
                :fields="createFields"
                :submit-label="label('create', locale)"
            />
        </Phase5Section>
    </Phase5Shell>
</template>
