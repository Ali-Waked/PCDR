<script setup>
import { computed } from 'vue'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    caseData: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
    canViewSensitive: { type: Boolean, default: false },
    notice: { type: String, default: '' },
})

const { locale, formatDate } = useI18n()

const title = computed(() => props.caseData?.reference || label('accountability', locale.value))
const backUrl = computed(() => phase5Url('accountability.index', {}, locale.value))
const allowedTargets = computed(() => props.transitions?.[props.caseData?.status] ?? [])

const transitionFields = computed(() => [
    { name: 'to_status', type: 'select', options: allowedTargets.value, required: true },
    { name: 'note', type: 'textarea', wide: true },
])

const detailFields = computed(() => [
    { key: 'reference' }, { key: 'category' }, { key: 'channel' }, { key: 'status' },
    { key: 'isSensitive' }, { key: 'createdAt', date: true },
])

function valueFor(field) {
    const value = props.caseData?.[field.key]
    if (value === null || value === undefined || value === '') {
        return '—'
    }
    if (field.date) {
        return formatDate(value)
    }
    if (typeof value === 'boolean') {
        return value ? label('yes', locale.value) : label('no', locale.value)
    }

    return label(value, locale.value, String(value))
}
</script>

<template>
    <Phase5Shell
        :title="title"
        :notice="notice"
        :back="backUrl"
        :eyebrow="label('accountability', locale)"
    >
        <div v-if="caseData?.isSensitive && canViewSensitive" class="notice" role="note">
            {{ locale === 'ar'
                ? 'سجل حساس: لا تعرض تفاصيله خارج القنوات المصرّح بها.'
                : 'Sensitive record: do not share its details outside authorized channels.' }}
        </div>

        <Phase5Section :title="label('details', locale)">
            <dl class="phase5-kv" style="padding: 1rem 1.25rem">
                <div v-for="field in detailFields" :key="field.key">
                    <dt>{{ label(field.key, locale) }}</dt>
                    <dd>
                        <Badge v-if="field.key === 'status'" :tone="toneFor(props.caseData?.status)">
                            {{ valueFor(field) }}
                        </Badge>
                        <span v-else>{{ valueFor(field) }}</span>
                    </dd>
                </div>
            </dl>
            <div v-if="caseData?.summary" class="phase5-muted" style="padding: 0 1.25rem 1.25rem">
                {{ caseData.summary }}
            </div>
        </Phase5Section>

        <div class="phase5-grid">
            <Phase5Section :title="label('events', locale)">
                <ul v-if="events.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                    <li v-for="event in events" :key="event.id" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" aria-hidden="true" />
                        <div>
                            <div class="phase5-timeline__title">
                                {{ label(event.action, locale, event.action) }}
                                <Badge v-if="event.isSensitive" tone="warning">{{ label('sensitive', locale) }}</Badge>
                            </div>
                            <div class="phase5-timeline__meta">
                                {{ label(event.fromStatus, locale, event.fromStatus) }}
                                → {{ label(event.toStatus, locale, event.toStatus) }}
                                · {{ formatDate(event.createdAt) }}
                            </div>
                            <div v-if="event.note" class="phase5-muted">{{ event.note }}</div>
                        </div>
                    </li>
                </ul>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
            </Phase5Section>

            <Phase5Section v-if="allowedTargets.length" :title="label('transition', locale)">
                <div style="padding: 1rem 1.25rem">
                    <Phase5Form
                        :action="phase5Url('accountability.transition', { case: caseData.id }, locale)"
                        :fields="transitionFields"
                        :submit-label="label('transition', locale)"
                        compact
                    />
                </div>
            </Phase5Section>
        </div>
    </Phase5Shell>
</template>
