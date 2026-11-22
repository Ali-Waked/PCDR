<script setup>
import { useI18n } from '../../composables/useI18n'
import Badge from '../Ui/Badge.vue'
import EmptyState from '../EmptyState.vue'
import { asBoolean, label, shortText, toneFor } from './phase5'

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    empty: { type: String, default: '' },
    compact: { type: Boolean, default: false },
})

const { locale, formatDate } = useI18n()

function display(row, column) {
    const value = row?.[column.key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }
    if (column.format === 'date') {
        return formatDate(value)
    }
    if (column.format === 'short') {
        return shortText(value, column.max ?? 120)
    }
    if (typeof value === 'boolean') {
        return value
            ? label('enabled_label', locale.value)
            : label('disabled_label', locale.value)
    }
    if (Array.isArray(value)) {
        return value.length === 0 ? '—' : value.map((item) => label(item, locale.value)).join('، ')
    }
    if (typeof value === 'object') {
        return shortText(JSON.stringify(value), 80)
    }

    return String(value)
}
</script>

<template>
    <div class="phase5-table-wrap">
        <div v-if="rows.length" class="phase5-table-scroll">
            <table class="phase5-table" :class="{ 'is-compact': compact }">
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.key" :scope="'col'">
                            {{ column.label || label(column.key, locale) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in rows" :key="row?.id ?? row?.reference ?? index">
                        <td v-for="column in columns" :key="column.key" :data-label="column.label || label(column.key, locale)">
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="row?.[column.key]"
                            >
                                <Badge v-if="column.badge" :tone="toneFor(row?.[column.key])">
                                    {{ column.format === 'date' ? display(row, column) : label(row?.[column.key], locale, display(row, column)) }}
                                </Badge>
                                <span v-else-if="column.boolean" :class="asBoolean(row?.[column.key]) ? 'phase5-flag is-on' : 'phase5-flag'">
                                    {{ display(row, column) }}
                                </span>
                                <span v-else>{{ display(row, column) }}</span>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else :title="empty || label('empty', locale)" />
    </div>
</template>
