<script setup>
import { computed } from 'vue'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5FilterBar from '../../../Components/Phase5/Phase5FilterBar.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    activity: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('activity', locale.value))
const rows = computed(() => rowsOf(props.activity))

const columns = computed(() => [
    { key: 'action' },
    { key: 'subject_type' },
    { key: 'subject_id' },
    { key: 'status', badge: true },
    { key: 'source' },
    { key: 'occurred_at', format: 'date' },
])

const filterFields = computed(() => [
    { name: 'action', type: 'select', options: props.filters?.actions ?? [] },
    { name: 'subject_type', type: 'select', options: props.filters?.subject_types ?? [] },
])
</script>

<template>
    <Phase5Shell :title="title">
        <Phase5Section :title="label('filters', locale)">
            <Phase5FilterBar
                :action="phase5Url('activity.index', {}, locale)"
                :fields="filterFields"
            />
        </Phase5Section>

        <Phase5Section :title="label('audit', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-action="{ value }">
                    <span class="phase5-inline">{{ label(value, locale, value) }}</span>
                </template>
                <template #cell-subject_type="{ value }">
                    {{ label(value, locale, value) }}
                </template>
                <template #cell-status="{ value }">
                    <Badge v-if="value" :tone="toneFor(value)">{{ label(value, locale, value) }}</Badge>
                    <span v-else>—</span>
                </template>
                <template #cell-source="{ value }">
                    {{ label(value, locale, value) }}
                </template>
            </Phase5Table>
            <p class="phase5-muted" style="padding: 1rem 1.25rem">
                {{ locale === 'ar'
                    ? 'يعرض النشاط مفاتيح الإجراءات والمعرّفات فقط؛ لا يتضمن بيانات شخصية أو نصوص شكاوى.'
                    : 'The activity log shows action keys and identifiers only; no personal data or complaint narratives.' }}
            </p>
        </Phase5Section>
    </Phase5Shell>
</template>
