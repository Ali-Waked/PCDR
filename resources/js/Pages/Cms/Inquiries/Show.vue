<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import DataTable from '../../../Components/Dashboard/DataTable.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    type: { type: String, required: true },
    inquiry: { type: Object, required: true },
    notes: { type: Array, default: () => [] },
    replies: { type: Array, default: () => [] },
    deliveryLog: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    variables: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate } = useI18n()
const inquiryPath = localPath('cms/inquiries')
const detailPath = `${inquiryPath}/${props.type}/${props.inquiry.id}`

const statusForm = useForm({ status: props.inquiry.status || 'new' })
const assignForm = useForm({ assigned_to: props.inquiry.assignedTo ?? '' })
const noteForm = useForm({ body: '' })
const replyForm = useForm({
    template_id: '',
    subject: `${t('cms_reply_prefix')} ${props.inquiry.reference}`,
    message: '',
    locale: props.inquiry.locale || 'ar',
})

const statusLabels = {
    new: 'cms_status_new',
    in_progress: 'cms_status_in_progress',
    resolved: 'cms_status_resolved',
    closed: 'cms_status_closed',
}

function statusLabel(status) {
    return statusLabels[status] ? t(statusLabels[status], status) : status
}

const fieldRows = computed(() => Object.entries(props.inquiry)
    .filter(([key, value]) => !['id', 'type', 'reference', 'status', 'locale', 'assignedTo', 'assignedToName', 'createdAt', 'users'].includes(key)
        && value !== null && value !== '')
    .map(([key, value]) => ({ key, value: String(value) })))

const logColumns = computed(() => [
    { key: 'purpose', label: t('cms_purpose') },
    { key: 'status', label: t('ui.status') },
    { key: 'locale', label: t('ui.language') },
    { key: 'sent', label: t('cms_sent_at') },
])

function chooseTemplate(event) {
    const template = props.templates.find((item) => String(item.id) === String(event.target.value))
    if (!template) {
        return
    }
    replyForm.subject = template.subject || replyForm.subject
    if (!replyForm.message) {
        replyForm.message = ''
    }
}

function saveStatus() {
    statusForm.put(`${detailPath}/status`, { preserveScroll: true })
}

function saveAssignment() {
    assignForm.put(`${detailPath}/assign`, { preserveScroll: true })
}

function addNote() {
    noteForm.post(`${detailPath}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() })
}

function sendReply() {
    replyForm.post(`${detailPath}/reply`, { preserveScroll: true, onSuccess: () => replyForm.reset('message') })
}
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_inquiries')" :subtitle="inquiry.reference">
        <div class="pc-stack pcdr-enter">
            <div class="pc-page-actions">
                <Link :href="inquiryPath" class="pc-btn pc-btn--secondary">
                    <UiIcon name="arrowRight" :size="16" />
                    {{ t('back') }}
                </Link>
                <CmsStatusBadge :status="inquiry.status" :label="statusLabel(inquiry.status)" />
            </div>

            <div v-if="can.update || can.assign" class="pc-grid pc-grid--2">
                <form v-if="can.update" class="pc-card" @submit.prevent="saveStatus">
                    <div class="pc-card__head">
                        <h2 class="pc-card__title">{{ t('ui.status') }}</h2>
                    </div>
                    <div class="pc-card__body pc-form-grid">
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_status') }}</span>
                            <select v-model="statusForm.status" class="pc-select">
                                <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                            </select>
                        </label>
                        <div class="pc-field pc-field--action">
                            <button class="pc-btn pc-btn--primary" type="submit" :disabled="statusForm.processing">
                                <UiIcon name="check" :size="16" />
                                {{ t('cms_update_status', t('cms_save')) }}
                            </button>
                        </div>
                    </div>
                </form>

                <form v-if="can.assign" class="pc-card" @submit.prevent="saveAssignment">
                    <div class="pc-card__head">
                        <h2 class="pc-card__title">{{ t('cms_assignment') }}</h2>
                    </div>
                    <div class="pc-card__body pc-form-grid">
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_assigned_to') }}</span>
                            <select v-model="assignForm.assigned_to" class="pc-select">
                                <option value="">{{ t('cms_unassigned') }}</option>
                                <option v-for="user in inquiry.users || []" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </label>
                        <div class="pc-field pc-field--action">
                            <button class="pc-btn pc-btn--primary" type="submit" :disabled="assignForm.processing">
                                <UiIcon name="check" :size="16" />
                                {{ t('cms_save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <section class="pc-card">
                <div class="pc-card__head">
                    <h2 class="pc-card__title">{{ t('cms_inquiry_details') }}</h2>
                    <span class="pc-badge pc-badge--slate" dir="ltr">{{ inquiry.reference }}</span>
                </div>
                <div class="pc-card__body">
                    <dl class="pc-detail-list">
                        <div v-for="row in fieldRows" :key="row.key" class="pc-detail-list__item">
                            <dt>{{ row.key }}</dt>
                            <dd>{{ row.value }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="pc-card">
                <div class="pc-card__head">
                    <h2 class="pc-card__title">{{ t('cms_internal_notes') }}</h2>
                    <span class="pc-badge pc-badge--teal">{{ notes.length }}</span>
                </div>
                <div class="pc-card__body pc-stack">
                    <form v-if="can.note" class="pc-form-grid" @submit.prevent="addNote">
                        <label class="pc-field pc-field--full">
                            <span class="pc-label">{{ t('cms_note') }}</span>
                            <textarea v-model="noteForm.body" class="pc-textarea" rows="3" maxlength="5000" required></textarea>
                        </label>
                        <div class="pc-form-actions">
                            <button class="pc-btn pc-btn--primary" type="submit" :disabled="noteForm.processing">
                                <UiIcon name="plus" :size="16" />
                                {{ t('cms_add_note') }}
                            </button>
                        </div>
                    </form>

                    <div v-if="notes.length" class="pc-timeline">
                        <article v-for="note in notes" :key="note.id" class="pc-timeline__item">
                            <span class="pc-timeline__dot" aria-hidden="true"></span>
                            <p class="pc-timeline__title">{{ note.author || '—' }}</p>
                            <p class="pc-timeline__meta">{{ formatDate(note.createdAt) }}</p>
                            <p class="pc-timeline__body">{{ note.body }}</p>
                        </article>
                    </div>
                    <EmptyState v-else :title="t('cms_no_records')" icon="inbox" />
                </div>
            </section>

            <section class="pc-card">
                <div class="pc-card__head">
                    <h2 class="pc-card__title">{{ t('cms_email_reply') }}</h2>
                    <span class="pc-badge pc-badge--teal">{{ replies.length }}</span>
                </div>
                <div class="pc-card__body pc-stack">
                    <form v-if="can.reply" class="pc-form-grid" @submit.prevent="sendReply">
                        <label class="pc-field">
                            <span class="pc-label">{{ t('cms_email_template') }}</span>
                            <select class="pc-select" :value="replyForm.template_id" @change="chooseTemplate">
                                <option value="">{{ t('cms_default_template') }}</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                            </select>
                        </label>
                        <label class="pc-field">
                            <span class="pc-label">{{ t('ui.language') }}</span>
                            <select v-model="replyForm.locale" class="pc-select">
                                <option v-for="locale in ['ar', 'en']" :key="locale" :value="locale">{{ locale }}</option>
                            </select>
                        </label>
                        <label class="pc-field pc-field--full">
                            <span class="pc-label">{{ t('subject') }}</span>
                            <input v-model="replyForm.subject" class="pc-input" type="text" maxlength="255" required>
                        </label>
                        <label class="pc-field pc-field--full">
                            <span class="pc-label">{{ t('cms_reply_message') }}</span>
                            <textarea v-model="replyForm.message" class="pc-textarea" rows="6" maxlength="5000" required></textarea>
                        </label>
                        <div class="pc-field pc-field--full">
                            <span class="pc-label">{{ t('cms_allowed_variables') }}</span>
                            <div class="pc-token-list">
                                <span v-for="variable in Object.keys(variables)" :key="variable" class="pc-token">{{ variable }}</span>
                            </div>
                        </div>
                        <div class="pc-form-actions">
                            <button class="pc-btn pc-btn--primary" type="submit" :disabled="replyForm.processing">
                                <UiIcon name="mail" :size="16" />
                                {{ t('cms_send_reply') }}
                            </button>
                        </div>
                    </form>

                    <div v-if="replies.length" class="pc-timeline">
                        <article v-for="reply in replies" :key="reply.id" class="pc-timeline__item">
                            <span class="pc-timeline__dot" aria-hidden="true"></span>
                            <p class="pc-timeline__title">{{ reply.subject }}</p>
                            <p class="pc-timeline__meta">{{ reply.status }} · {{ reply.sender || '—' }} · {{ formatDate(reply.sentAt) }}</p>
                            <p class="pc-timeline__body">{{ reply.body }}</p>
                            <p v-if="reply.error" class="pc-error" role="alert">{{ reply.error }}</p>
                        </article>
                    </div>
                    <EmptyState v-else :title="t('cms_no_replies')" icon="mail" />
                </div>
            </section>

            <section class="pc-card">
                <div class="pc-card__head">
                    <h2 class="pc-card__title">{{ t('cms_delivery_log') }}</h2>
                    <span class="pc-badge pc-badge--slate">{{ deliveryLog.length }}</span>
                </div>
                <template v-if="deliveryLog.length">
                    <DataTable :columns="logColumns">
                        <tr v-for="log in deliveryLog" :key="log.id">
                            <td>{{ log.purpose }}</td>
                            <td><CmsStatusBadge :status="String(log.status || '').toLowerCase()" :label="log.status" /></td>
                            <td dir="ltr">{{ log.locale }}</td>
                            <td class="pc-table__secondary">{{ formatDate(log.sentAt || log.failedAt) }}</td>
                        </tr>
                    </DataTable>
                </template>
                <EmptyState v-else :title="t('cms_no_records')" icon="history" />
            </section>
        </div>
    </CmsDashboardLayout>
</template>
