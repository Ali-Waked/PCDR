<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import EmptyState from '../../../Components/EmptyState.vue'
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
    <CmsLayout :title="t('ui.cms_inquiries')" :subtitle="inquiry.reference">
        <div class="header-actions">
            <Link :href="inquiryPath" class="btn btn-secondary btn-small">{{ t('back') }}</Link>
            <span class="pill">{{ statusLabel(inquiry.status) }}</span>
        </div>

        <section class="cms-card">
            <div class="form-grid form-grid--two">
                <form v-if="can.update" @submit.prevent="saveStatus">
                    <label class="field">
                        <span>{{ t('ui.status') }}</span>
                        <select v-model="statusForm.status" class="select">
                            <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                        </select>
                    </label>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-small" type="submit" :disabled="statusForm.processing">{{ t('cms_update_status', t('cms_save')) }}</button>
                    </div>
                </form>

                <form v-if="can.assign" @submit.prevent="saveAssignment">
                    <label class="field">
                        <span>{{ t('cms_assigned_to') }}</span>
                        <select v-model="assignForm.assigned_to" class="select">
                            <option value="">{{ t('cms_unassigned') }}</option>
                            <option v-for="user in inquiry.users || []" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </label>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-small" type="submit" :disabled="assignForm.processing">{{ t('cms_save') }}</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_inquiry_details') }}</h2>
            <dl class="detail-list">
                <div v-for="row in fieldRows" :key="row.key">
                    <dt>{{ row.key }}</dt>
                    <dd>{{ row.value }}</dd>
                </div>
            </dl>
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_internal_notes') }}</h2>
            <form v-if="can.note" class="form-stack" @submit.prevent="addNote">
                <label class="field">
                    <span>{{ t('cms_note') }}</span>
                    <textarea v-model="noteForm.body" class="textarea" rows="3" maxlength="5000" required></textarea>
                </label>
                <div class="form-actions">
                    <button class="btn btn-primary btn-small" type="submit" :disabled="noteForm.processing">{{ t('cms_add_note') }}</button>
                </div>
            </form>
            <div v-if="notes.length" class="timeline">
                <article v-for="note in notes" :key="note.id" class="timeline-item">
                    <p>{{ note.body }}</p>
                    <p class="small-caps">{{ note.author || '—' }} · {{ formatDate(note.createdAt) }}</p>
                </article>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_email_reply') }}</h2>
            <form v-if="can.reply" class="form-stack" @submit.prevent="sendReply">
                <div class="form-grid form-grid--two">
                    <label class="field">
                        <span>{{ t('cms_email_template') }}</span>
                        <select class="select" :value="replyForm.template_id" @change="chooseTemplate">
                            <option value="">{{ t('cms_default_template') }}</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ t('ui.language') }}</span>
                        <select v-model="replyForm.locale" class="select">
                            <option v-for="locale in ['ar', 'en']" :key="locale" :value="locale">{{ locale }}</option>
                        </select>
                    </label>
                </div>
                <label class="field">
                    <span>{{ t('subject') }}</span>
                    <input v-model="replyForm.subject" class="input" type="text" maxlength="255" required>
                </label>
                <label class="field">
                    <span>{{ t('cms_reply_message') }}</span>
                    <textarea v-model="replyForm.message" class="textarea" rows="6" maxlength="5000" required></textarea>
                </label>
                <p class="small-caps">{{ t('cms_allowed_variables') }}: {{ Object.keys(variables).join(', ') }}</p>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" :disabled="replyForm.processing">{{ t('cms_send_reply') }}</button>
                </div>
            </form>
            <div v-if="replies.length" class="timeline">
                <article v-for="reply in replies" :key="reply.id" class="timeline-item">
                    <strong>{{ reply.subject }}</strong>
                    <p>{{ reply.body }}</p>
                    <p class="small-caps">{{ reply.status }} · {{ reply.sender || '—' }} · {{ formatDate(reply.sentAt) }}</p>
                    <p v-if="reply.error" class="error">{{ reply.error }}</p>
                </article>
            </div>
            <EmptyState v-else :body="t('cms_no_replies')" />
        </section>

        <section class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_delivery_log') }}</h2>
            <div v-if="deliveryLog.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_purpose') }}</th>
                            <th>{{ t('ui.status') }}</th>
                            <th>{{ t('ui.language') }}</th>
                            <th>{{ t('cms_sent_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in deliveryLog" :key="log.id">
                            <td>{{ log.purpose }}</td>
                            <td>{{ log.status }}</td>
                            <td>{{ log.locale }}</td>
                            <td class="small-caps">{{ formatDate(log.sentAt || log.failedAt) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
        </section>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}

.detail-list {
    display: grid;
    gap: 0.5rem;
    margin: 0;
}

.detail-list > div {
    display: grid;
    grid-template-columns: minmax(8rem, 12rem) 1fr;
    gap: 1rem;
    padding-block-end: 0.5rem;
    border-block-end: 1px solid var(--border, #e5e7eb);
}

.detail-list dt {
    font-weight: 700;
}

.detail-list dd {
    margin: 0;
    white-space: pre-wrap;
}

.timeline {
    display: grid;
    gap: 0.5rem;
    margin-block-start: 1rem;
}

.timeline-item {
    padding: 0.75rem;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0.5rem;
}

.timeline-item p {
    margin-block: 0.25rem;
}
</style>
