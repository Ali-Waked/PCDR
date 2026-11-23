<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Pagination from '../../../Components/Ui/Pagination.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
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
    <div class="pc-stack pcdr-enter">
        <Head :title="t('audit_page.title')" />
        <div class="pc-page-actions">
            <a v-if="can('audit_logs.export')" :href="localePath('/admin/audit-logs/export')" class="pc-btn pc-btn--secondary">
                {{ t('audit_page.export') }}
            </a>
        </div>
        <Card>
            <form class="pc-form-grid" @submit.prevent="search.get(localePath('/admin/audit-logs'), { preserveState: true })">
                <TextField v-model="search.action" name="action" :label="t('audit_page.action')" />
                <TextField v-model="search.subject" name="subject" :label="t('audit_page.subject')" />
                <div class="pc-field pc-field--full" style="justify-self: start"><Button type="submit" variant="secondary">{{ t('search') }}</Button></div>
            </form>
        </Card>
        <Card>
            <div class="pc-table-wrap">
                <table class="pc-table">
                    <thead>
                        <tr>
                            <th>{{ t('audit_page.time') }}</th>
                            <th>{{ t('audit_page.actor') }}</th>
                            <th>{{ t('audit_page.action') }}</th>
                            <th>{{ t('audit_page.subject') }}</th>
                            <th>Request</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="pc-directional pc-list__meta">{{ log.created_at }}</td>
                            <td class="pc-table__primary">{{ log.actor?.name || 'system' }}</td>
                            <td class="pc-directional">{{ log.action }}</td>
                            <td class="pc-directional">{{ log.subject_type }}:{{ log.subject_id || '—' }}</td>
                            <td class="pc-directional pc-list__meta">{{ log.request_id }}</td>
                        </tr>
                        <tr v-if="!logs.data.length">
                            <td colspan="5"><EmptyState :title="t('no_results')" icon="history" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="logs" />
        </Card>
    </div>
</template>
