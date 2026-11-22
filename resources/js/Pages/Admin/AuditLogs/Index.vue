<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Pagination from '../../../Components/Ui/Pagination.vue'
import { useLocale } from '../../../Composables/useLocale'
import { usePermission } from '../../../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const { t, localePath } = useLocale()
const { can } = usePermission()
const search = useForm({ action: props.filters.action ?? '', subject: props.filters.subject ?? '' })
</script>

<template>
    <div class="pcdr-enter">
        <Head :title="t('audit_page.title')" />
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <p class="text-sm text-muted">{{ t('audit_page.subtitle') }}</p>
            <a v-if="can('audit_logs.export')" :href="localePath('/admin/audit-logs/export')" class="focus-ring rounded-md border border-subtle px-4 py-2.5 text-sm font-semibold text-ink">
                {{ t('audit_page.export') }}
            </a>
        </div>
        <Card>
            <form class="mb-5 grid gap-3 sm:grid-cols-2" @submit.prevent="search.get(localePath('/admin/audit-logs'), { preserveState: true })">
                <TextField v-model="search.action" name="action" :label="t('audit_page.action')" />
                <TextField v-model="search.subject" name="subject" :label="t('audit_page.subject')" />
                <div><Button type="submit" variant="secondary">{{ t('search') }}</Button></div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead><tr class="border-b border-subtle text-muted"><th class="py-3 text-start">{{ t('audit_page.time') }}</th><th class="py-3 text-start">{{ t('audit_page.actor') }}</th><th class="py-3 text-start">{{ t('audit_page.action') }}</th><th class="py-3 text-start">{{ t('audit_page.subject') }}</th><th class="py-3 text-start">Request</th></tr></thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id" class="border-b border-subtle last:border-0">
                            <td class="py-3 text-muted" dir="ltr">{{ log.created_at }}</td>
                            <td class="py-3 text-ink">{{ log.actor?.name || 'system' }}</td>
                            <td class="py-3 text-ink" dir="ltr">{{ log.action }}</td>
                            <td class="py-3 text-muted" dir="ltr">{{ log.subject_type }}:{{ log.subject_id || '—' }}</td>
                            <td class="py-3 text-muted" dir="ltr">{{ log.request_id }}</td>
                        </tr>
                        <tr v-if="!logs.data.length"><td colspan="5" class="py-8 text-center text-muted">{{ t('no_results') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="logs" />
        </Card>
    </div>
</template>
