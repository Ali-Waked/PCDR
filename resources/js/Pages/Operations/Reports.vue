<script setup>
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import SelectionControl from '../../Components/Selection/SelectionControl.vue'
import { useI18n } from '../../composables/useI18n'
import { formatCount, humanize, operationRoute } from '../../Components/Operations/operations'

const props = defineProps({
    categories: { type: Array, default: () => [] },
    sources: { type: Object, default: () => ({}) },
    reports: { type: Array, default: () => [] },
    generated: { type: Object, default: null },
    canCreate: { type: Boolean, default: false },
})

const { t, locale, direction, flash, page, formatDate } = useI18n()

const reportForm = reactive({
    category: '',
    date_from: '',
    date_to: '',
    program_id: '',
    project_id: '',
    location_id: '',
    status_filter: '',
    format: 'html',
})

const processing = ref(false)
const errors = ref({})

const categories = computed(() => (Array.isArray(props.categories) ? props.categories : []))
const categoryOptions = computed(() => categories.value.map((category) => ({
    value: category,
    label: humanize(category),
})))
const formatOptions = [
    { value: 'html', label: 'html' },
    { value: 'csv', label: 'csv' },
].map((option) => ({ ...option, label: humanize(option.label) }))
const history = computed(() => (Array.isArray(props.reports) ? props.reports : []))
const sourceTables = computed(() => {
    const tables = props.sources?.[reportForm.category]
    return Array.isArray(tables) ? tables : []
})

const authPermissions = computed(() => [
    ...(Array.isArray(page.props.auth?.permissions) ? page.props.auth.permissions : []),
    ...(Array.isArray(page.props.auth?.cmsPermissions) ? page.props.auth.cmsPermissions : []),
])
const canExport = computed(() => authPermissions.value.includes('reports.export'))

const summary = computed(() => props.generated?.summary ?? props.generated?.data ?? props.generated ?? null)
const summaryRows = computed(() => (Array.isArray(summary.value?.rows) ? summary.value.rows : []))
const privacyNotice = computed(() => summary.value?.privacy
    || 'Aggregate counts only. No identifiers or free text are included.')

function serverExportUrl(report) {
    const url = report?.exportUrl || report?.export_url || report?.export
    return typeof url === 'string' && url !== '' ? url : null
}

function generate() {
    if (!props.canCreate || processing.value) {
        return
    }

    router.post(operationRoute('operations.reports.generate', {}, locale.value), { ...reportForm }, {
        preserveScroll: true,
        onStart: () => { processing.value = true; errors.value = {} },
        onFinish: () => { processing.value = false },
        onError: (validationErrors) => { errors.value = validationErrors || {} },
    })
}

function errorText(error) {
    return Array.isArray(error) ? error.join(', ') : String(error ?? '')
}
</script>

<template>
    <AppLayout :title="humanize('operational_reports')" subtitle="Aggregate counts only">
        <div class="pc-stack">

            <p class="pc-callout">
                {{ privacyNotice }}
            </p>

            <form class="pc-card pc-card--padded" @submit.prevent="generate">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('generate_report') }}</h2>
                <div class="pc-form-grid">
                    <div class="pc-field">
                        <SelectionControl
                            v-model="reportForm.category"
                            :label="humanize('category')"
                            name="category"
                            :options="categoryOptions"
                            :placeholder="t('ui.filter')"
                            :error="errorText(errors.category)"
                            :searchable="categoryOptions.length > 6"
                        />
                    </div>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('date_from') }}</span>
                        <input v-model="reportForm.date_from" type="date" name="date_from" class="pc-input" />
                        <span v-if="errors.date_from" class="pc-error">{{ errorText(errors.date_from) }}</span>
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('date_to') }}</span>
                        <input v-model="reportForm.date_to" type="date" name="date_to" class="pc-input" />
                        <span v-if="errors.date_to" class="pc-error">{{ errorText(errors.date_to) }}</span>
                    </label>
                    <div class="pc-field">
                        <SelectionControl
                            v-model="reportForm.format"
                            :label="humanize('format')"
                            name="format"
                            :options="formatOptions"
                            :searchable="false"
                            :error="errorText(errors.format)"
                        />
                    </div>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('program_id') }}</span>
                        <input v-model="reportForm.program_id" type="number" min="1" step="1" name="program_id" class="pc-input" />
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('project_id') }}</span>
                        <input v-model="reportForm.project_id" type="number" min="1" step="1" name="project_id" class="pc-input" />
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('location_id') }}</span>
                        <input v-model="reportForm.location_id" type="number" min="1" step="1" name="location_id" class="pc-input" />
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('status_filter') }}</span>
                        <input v-model="reportForm.status_filter" type="text" name="status_filter" class="pc-input" />
                    </label>
                </div>

                <p v-if="sourceTables.length" class="pc-hint">
                    {{ humanize('whitelisted_sources') }}: <span dir="ltr">{{ sourceTables.join(', ') }}</span>
                </p>

                <button type="submit" class="pc-btn pc-btn--primary" :disabled="!canCreate || processing">
                    {{ processing ? t('ui.loading', humanize('loading')) : humanize('generate_report') }}
                </button>
                <p v-if="!canCreate" class="pc-hint">{{ humanize('no_report_permission') }}</p>
            </form>

            <section v-if="summary" class="pc-card pc-card--padded">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">{{ humanize('generated_report') }}</h2>
                    <a
                        v-if="canExport && serverExportUrl(generated)"
                        :href="serverExportUrl(generated)"
                        class="pc-btn pc-btn--secondary pc-btn--sm"
                    >
                        {{ t('ui.download') }}
                    </a>
                </div>
                <p class="pc-hint">{{ humanize('total') }}: {{ formatCount(summary.total, locale) }}</p>
                <div v-if="summaryRows.length" class="pc-table-wrap">
                    <table class="pc-table">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-start font-medium">{{ humanize('table') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ t('ui.cms_status') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ humanize('count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in summaryRows" :key="index" >
                                <td class="px-3 py-2" dir="ltr">{{ row.table }}</td>
                                <td class="px-3 py-2">{{ humanize(row.status) }}</td>
                                <td class="px-3 py-2">{{ formatCount(row.count, locale) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="pc-hint">{{ t('ui.no_results') }}</p>
            </section>

            <section class="pc-card pc-card--padded">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('report_history') }}</h2>
                <div v-if="!history.length" class="pc-card__body pc-hint">{{ t('ui.no_results') }}</div>
                <div v-else class="pc-table-wrap">
                    <table class="pc-table">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('code') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('category') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('format') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('generated_at') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ t('ui.actions', humanize('actions')) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in history" :key="report.id" >
                                <td class="px-3 py-3" dir="ltr">{{ report.code || `#${report.id}` }}</td>
                                <td class="px-3 py-3">{{ humanize(report.category) }}</td>
                                <td class="px-3 py-3">{{ humanize(report.format) }}</td>
                                <td class="px-3 py-3">{{ formatDate(report.generated_at) }}</td>
                                <td class="px-3 py-3">
                                    <a
                                        v-if="canExport && serverExportUrl(report)"
                                        :href="serverExportUrl(report)"
                                        class="pc-btn pc-btn--secondary pc-btn--sm"
                                    >
                                        {{ t('ui.download') }}
                                    </a>
                                    <span v-else class="pc-hint">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
