<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    cases: { type: Object, default: () => ({}) },
    enabledCategories: { type: Object, default: () => ({}) },
    disabledCategories: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    gates: { type: Object, default: () => ({}) },
    notice: { type: String, default: '' },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('accountability', locale.value))
const rows = computed(() => rowsOf(props.cases))
const enabled = computed(() => Object.keys(props.enabledCategories ?? {}))
const disabled = computed(() => Object.keys(props.disabledCategories ?? {}))

const columns = computed(() => [
    { key: 'reference' },
    { key: 'category' },
    { key: 'channel' },
    { key: 'status', badge: true },
    { key: 'sensitive', boolean: true },
    { key: 'createdAt', format: 'date' },
])

const createFields = computed(() => [
    { name: 'category', type: 'select', options: enabled.value, required: true },
    { name: 'channel', type: 'select', options: ['internal', 'website'], required: true },
    { name: 'summary', type: 'textarea', required: true, wide: true },
])

function caseUrl(item) {
    return phase5Url('accountability.show', { case: item.id }, locale.value)
}
</script>

<template>
    <Phase5Shell :title="title" :notice="notice">
        <div v-if="disabled.length" class="notice" role="note">
            {{ locale === 'ar'
                ? 'الفئات المعلّقة لا تُقبل حالياً لحين صدور قرار حوكمة وخصوصية:'
                : 'Pending categories are not accepted until a governance and privacy decision is documented:' }}
            <strong class="phase5-inline">
                <span v-for="category in disabled" :key="category">{{ label(category, locale, category) }}</span>
            </strong>
        </div>

        <Phase5Section :title="label('summary', locale)">
            <div class="phase5-grid">
                <div class="phase5-stat">
                    <span class="phase5-stat__value">{{ rows.length }}</span>
                    <span class="phase5-stat__label">{{ label('cases', locale, label('accountability', locale)) }}</span>
                </div>
                <div class="phase5-stat">
                    <span class="phase5-stat__value">{{ enabled.length }}</span>
                    <span class="phase5-stat__label">{{ locale === 'ar' ? 'فئات متاحة' : 'Available categories' }}</span>
                </div>
                <div class="phase5-gate" :class="{ 'is-off': !props.gates?.complaint_public_tracking_enabled }">
                    <span>{{ label('complaint_public_tracking_enabled', locale) }}</span>
                    <Badge :tone="props.gates?.complaint_public_tracking_enabled ? 'success' : 'neutral'">
                        {{ props.gates?.complaint_public_tracking_enabled ? label('enabled_label', locale) : label('disabled_label', locale) }}
                    </Badge>
                </div>
            </div>
        </Phase5Section>

        <Phase5Section :title="label('accountability', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-reference="{ row, value }">
                    <Link :href="caseUrl(row)" class="phase5-code">{{ value }}</Link>
                </template>
                <template #cell-createdAt="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-sensitive="{ value }">
                    <Badge :tone="value ? 'warning' : 'neutral'">
                        {{ value ? label('sensitive', locale) : label('no', locale) }}
                    </Badge>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="cases" />
            </div>
        </Phase5Section>

        <Phase5Section v-if="enabled.length" :title="label('create', locale) + ' · ' + label('accountability', locale)">
            <Phase5Form
                :action="phase5Url('accountability.store', {}, locale)"
                :fields="createFields"
                :submit-label="label('submit', locale)"
            />
        </Phase5Section>
    </Phase5Shell>
</template>
