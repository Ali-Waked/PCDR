<script setup>
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
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
    <CmsLayout :title="humanize('operational_reports')" subtitle="Aggregate counts only">
        <div :dir="direction" class="space-y-5 text-ink">
            <div
                v-if="flash?.success || flash?.error"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="flash?.error ? 'border-danger/30 bg-danger/5 text-danger' : 'border-success/30 bg-success/5 text-success'"
                role="status"
            >
                {{ flash?.error || flash?.success }}
            </div>

            <p class="rounded-lg border border-teal-700/20 bg-teal-soft px-4 py-3 text-sm text-teal-deep">
                {{ privacyNotice }}
            </p>

            <form class="rounded-xl border border-border-subtle bg-surface p-5" @submit.prevent="generate">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('generate_report') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('category') }}</span>
                        <select v-model="reportForm.category" name="category" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="">{{ t('ui.filter') }}</option>
                            <option v-for="category in categories" :key="category" :value="category">{{ humanize(category) }}</option>
                        </select>
                        <span v-if="errors.category" class="text-xs text-danger">{{ errorText(errors.category) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('date_from') }}</span>
                        <input v-model="reportForm.date_from" type="date" name="date_from" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                        <span v-if="errors.date_from" class="text-xs text-danger">{{ errorText(errors.date_from) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('date_to') }}</span>
                        <input v-model="reportForm.date_to" type="date" name="date_to" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                        <span v-if="errors.date_to" class="text-xs text-danger">{{ errorText(errors.date_to) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('format') }}</span>
                        <select v-model="reportForm.format" name="format" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="html">{{ humanize('html') }}</option>
                            <option value="csv">{{ humanize('csv') }}</option>
                        </select>
                        <span v-if="errors.format" class="text-xs text-danger">{{ errorText(errors.format) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('program_id') }}</span>
                        <input v-model="reportForm.program_id" type="number" min="1" step="1" name="program_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('project_id') }}</span>
                        <input v-model="reportForm.project_id" type="number" min="1" step="1" name="project_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('location_id') }}</span>
                        <input v-model="reportForm.location_id" type="number" min="1" step="1" name="location_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('status_filter') }}</span>
                        <input v-model="reportForm.status_filter" type="text" name="status_filter" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                    </label>
                </div>

                <p v-if="sourceTables.length" class="mt-4 text-xs text-muted">
                    {{ humanize('whitelisted_sources') }}: <span dir="ltr">{{ sourceTables.join(', ') }}</span>
                </p>

                <button type="submit" class="focus-ring mt-4 rounded-md bg-navy-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60" :disabled="!canCreate || processing">
                    {{ processing ? t('ui.loading', humanize('loading')) : humanize('generate_report') }}
                </button>
                <p v-if="!canCreate" class="mt-2 text-xs text-warning">{{ humanize('no_report_permission') }}</p>
            </form>

            <section v-if="summary" class="rounded-xl border border-border-subtle bg-surface p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">{{ humanize('generated_report') }}</h2>
                    <a
                        v-if="canExport && serverExportUrl(generated)"
                        :href="serverExportUrl(generated)"
                        class="focus-ring rounded-md border border-border-subtle px-3 py-1.5 text-sm"
                    >
                        {{ t('ui.download') }}
                    </a>
                </div>
                <p class="mb-3 text-sm text-muted">{{ humanize('total') }}: {{ formatCount(summary.total, locale) }}</p>
                <div v-if="summaryRows.length" class="overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border-subtle text-start text-muted">
                                <th class="px-3 py-2 text-start font-medium">{{ humanize('table') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ t('ui.cms_status') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ humanize('count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in summaryRows" :key="index" class="border-b border-border-subtle last:border-0">
                                <td class="px-3 py-2" dir="ltr">{{ row.table }}</td>
                                <td class="px-3 py-2">{{ humanize(row.status) }}</td>
                                <td class="px-3 py-2">{{ formatCount(row.count, locale) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-muted">{{ t('ui.no_results') }}</p>
            </section>

            <section class="overflow-hidden rounded-xl border border-border-subtle bg-surface">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('report_history') }}</h2>
                <div v-if="!history.length" class="px-5 pb-5 text-sm text-muted">{{ t('ui.no_results') }}</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <thead>
                            <tr class="border-y border-border-subtle text-start text-muted">
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('code') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('category') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('format') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('generated_at') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ t('ui.actions', humanize('actions')) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in history" :key="report.id" class="border-b border-border-subtle last:border-0">
                                <td class="px-3 py-3" dir="ltr">{{ report.code || `#${report.id}` }}</td>
                                <td class="px-3 py-3">{{ humanize(report.category) }}</td>
                                <td class="px-3 py-3">{{ humanize(report.format) }}</td>
                                <td class="px-3 py-3">{{ formatDate(report.generated_at) }}</td>
                                <td class="px-3 py-3">
                                    <a
                                        v-if="canExport && serverExportUrl(report)"
                                        :href="serverExportUrl(report)"
                                        class="focus-ring rounded border border-border-subtle px-2.5 py-1.5 text-xs"
                                    >
                                        {{ t('ui.download') }}
                                    </a>
                                    <span v-else class="text-xs text-muted">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </CmsLayout>
</template>
