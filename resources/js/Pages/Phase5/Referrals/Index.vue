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
    referrals: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
    sourceTypes: { type: Array, default: () => [] },
    destinationTypes: { type: Array, default: () => [] },
    notice: { type: String, default: '' },
})

const { locale } = useI18n()

const title = computed(() => label('referrals', locale.value))
const rows = computed(() => rowsOf(props.referrals))

const columns = computed(() => [
    { key: 'reference' },
    { key: 'status', badge: true },
    { key: 'sourceType' },
    { key: 'destinationType' },
    { key: 'dueAt', format: 'date' },
    { key: 'createdAt', format: 'date' },
])

const createFields = computed(() => [
    { name: 'sourceType', type: 'select', options: props.sourceTypes ?? [], required: true },
    { name: 'sourceId', type: 'number', min: 1, required: true },
    { name: 'destinationType', type: 'select', options: props.destinationTypes ?? [], required: true },
    { name: 'destinationId', type: 'number', min: 1 },
    { name: 'dueAt', type: 'datetime-local' },
    { name: 'reason', type: 'textarea', required: true, wide: true },
])
</script>

<template>
    <Phase5Shell :title="title" :notice="notice">
        <Phase5Section :title="label('referrals', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-reference="{ value }">
                    <span class="phase5-code">{{ value }}</span>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="referrals" />
            </div>
        </Phase5Section>

        <Phase5Section :title="label('create', locale) + ' · ' + label('referrals', locale)">
            <p class="phase5-muted" style="padding: 1rem 1.25rem 0">
                {{ locale === 'ar'
                    ? 'تُنشأ الإحالة برقم مرجعي آمن دون بيانات شخصية أو تفاصيل حساسة.'
                    : 'Referrals are created with a safe reference and no personal or sensitive details.' }}
            </p>
            <div style="padding: 1rem 1.25rem 1.25rem">
                <Phase5Form
                    :action="phase5Url('referrals.store', {}, locale)"
                    :fields="createFields"
                    :submit-label="label('create', locale)"
                />
            </div>
        </Phase5Section>
    </Phase5Shell>
</template>
