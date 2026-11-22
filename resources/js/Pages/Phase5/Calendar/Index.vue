<script setup>
import { computed } from 'vue'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5FilterBar from '../../../Components/Phase5/Phase5FilterBar.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    events: { type: Array, default: () => [] },
    views: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
    timezone: { type: String, default: '' },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('calendar', locale.value))
const rows = computed(() => rowsOf(props.events))

const columns = computed(() => [
    { key: 'title_key' },
    { key: 'category' },
    { key: 'date', format: 'date' },
    { key: 'source' },
    { key: 'timezone' },
])

const filterFields = computed(() => [
    { name: 'view', type: 'select', options: props.views ?? [] },
    {
        name: 'sources[]',
        type: 'select',
        options: (props.sources ?? []).map((source) => ({
            value: source.key,
            label: label(source.label_key, locale.value, source.key),
        })),
    },
])
</script>

<template>
    <Phase5Shell :title="title">
        <Phase5Section :title="label('filters', locale)">
            <Phase5FilterBar
                :action="phase5Url('calendar.index', {}, locale)"
                :fields="filterFields"
            />
        </Phase5Section>

        <Phase5Section
            :title="label('calendar', locale)"
            :subtitle="`${locale === 'ar' ? 'المنطقة الزمنية' : 'Timezone'}: ${timezone}`"
            flush
        >
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-title_key="{ row }">
                    {{ label(row.title_key, locale, label(row.category, locale, row.source)) }}
                </template>
                <template #cell-category="{ value }">
                    <Badge>{{ label(value, locale, value) }}</Badge>
                </template>
                <template #cell-source="{ value }">
                    {{ label(value, locale, value) }}
                </template>
            </Phase5Table>
            <p class="phase5-muted" style="padding: 1rem 1.25rem">
                {{ locale === 'ar'
                    ? 'التقويم يقرأ التواريخ من السجلات المصرّح بها ولا ينشئ متجر تواريخ منفصلًا.'
                    : 'The calendar reads dates from authorized records and does not create a second date store.' }}
            </p>
        </Phase5Section>
    </Phase5Shell>
</template>
