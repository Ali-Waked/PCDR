<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf, shortText, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    integrations: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    gates: { type: Object, default: () => ({}) },
})

const { locale } = useI18n()

const title = computed(() => label('integrations', locale.value))
const rows = computed(() => rowsOf(props.integrations))

const columns = computed(() => [
    { key: 'key' },
    { key: 'name' },
    { key: 'provider' },
    { key: 'category' },
    { key: 'enabled', boolean: true },
    { key: 'health_status', badge: true },
    { key: 'metadata' },
    { key: 'actions', label: label('actions', locale.value) },
])

const createFields = computed(() => [
    { name: 'key', required: true },
    { name: 'name', required: true },
    { name: 'provider', required: true },
    { name: 'category', type: 'select', options: props.categories ?? [], required: true },
])

function toggle(item) {
    router.post(
        phase5Url('integrations.toggle', { integration: item.id }, locale.value),
        { enabled: !item.enabled },
        { preserveScroll: true },
    )
}

function serialize(value) {
    if (!value || Object.keys(value).length === 0) {
        return '—'
    }

    return shortText(JSON.stringify(value), 100)
}
</script>

<template>
    <Phase5Shell :title="title">
        <div class="notice" role="note">
            {{ locale === 'ar'
                ? 'التكاملات تُسجّل معطّلة افتراضياً؛ لا يُسمح بإرسال بيانات إلى وجهات غير مصرّح بها.'
                : 'Integrations are registered disabled by default; data is never sent to unauthorized destinations.' }}
        </div>

        <Phase5Section :title="label('integrations', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-health_status="{ value }">
                    <Badge :tone="toneFor(value)">{{ label(value, locale, value) }}</Badge>
                </template>
                <template #cell-metadata="{ value }">{{ serialize(value) }}</template>
                <template #cell-actions="{ row }">
                    <button type="button" class="btn btn-ghost btn-small" @click="toggle(row)">
                        {{ row.enabled ? label('disabled_label', locale) : label('enabled_label', locale) }}
                    </button>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="integrations" />
            </div>
        </Phase5Section>

        <Phase5Section :title="label('create', locale) + ' · ' + label('integrations', locale)">
            <Phase5Form
                :action="phase5Url('integrations.store', {}, locale)"
                :fields="createFields"
                :submit-label="label('create', locale)"
            />
        </Phase5Section>
    </Phase5Shell>
</template>
