<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    instance: { type: Object, default: () => ({}) },
    steps: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    stepTransitions: { type: Object, default: () => ({}) },
    canTransition: { type: Boolean, default: false },
    notice: { type: String, default: '' },
})

const { locale, formatDate } = useI18n()

const title = computed(() => props.instance?.reference || label('workflows', locale.value))
const subtitle = computed(() => props.instance?.name || '')
const backUrl = computed(() => phase5Url('workflows.index', {}, locale.value))

const instanceFields = computed(() => [
    { key: 'reference' }, { key: 'name' }, { key: 'status' },
    { key: 'definition_key' }, { key: 'version_id' },
    { key: 'current_step_order' }, { key: 'subject_type' },
    { key: 'due_at', date: true }, { key: 'created_at', date: true },
])

const allowedTargets = computed(() => props.stepTransitions?.[props.instance?.status] ?? [])

const transitionFields = computed(() => [
    {
        name: 'to_status',
        type: 'select',
        options: allowedTargets.value,
        placeholder: label('choose', locale.value),
    },
    { name: 'note', type: 'textarea', wide: true },
])

function valueFor(field) {
    const value = props.instance?.[field.key]
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return field.date ? formatDate(value) : label(value, locale.value, String(value))
}

function transitionStep(step) {
    const targets = props.stepTransitions?.[step.status] ?? []
    if (!targets.length) {
        return
    }

    router.post(
        phase5Url('workflows.steps.transition', {
            instance: props.instance.id,
            step: step.id,
        }, locale.value),
        { to_status: targets[0] },
        { preserveScroll: true },
    )
}
</script>

<template>
    <Phase5Shell
        :title="title"
        :subtitle="subtitle"
        :notice="notice"
        :back="backUrl"
        :eyebrow="label('instances', locale)"
    >
        <Phase5Section :title="label('details', locale)">
            <dl class="phase5-kv" style="padding: 1rem 1.25rem">
                <div v-for="field in instanceFields" :key="field.key">
                    <dt>{{ label(field.key, locale) }}</dt>
                    <dd>
                        <Badge v-if="field.key === 'status'" :tone="toneFor(props.instance?.status)">
                            {{ valueFor(field) }}
                        </Badge>
                        <span v-else>{{ valueFor(field) }}</span>
                    </dd>
                </div>
            </dl>
        </Phase5Section>

        <Phase5Section :title="label('steps', locale)">
            <ol v-if="steps.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                <li v-for="step in steps" :key="step.id" class="phase5-timeline__item">
                    <span class="phase5-timeline__marker" aria-hidden="true" />
                    <div style="flex: 1">
                        <div class="phase5-timeline__title">
                            {{ step.ordering }}. {{ step.label || label(step.step_key, locale, step.step_key) }}
                            <Badge :tone="toneFor(step.status)">{{ label(step.status, locale, step.status) }}</Badge>
                        </div>
                        <div class="phase5-timeline__meta">
                            {{ label(step.assignment_type, locale, step.assignment_type) }}
                            <template v-if="step.due_at"> · {{ formatDate(step.due_at) }}</template>
                        </div>
                    </div>
                    <button
                        v-if="canTransition && (stepTransitions?.[step.status] ?? []).length"
                        type="button"
                        class="btn btn-secondary btn-small"
                        @click="transitionStep(step)"
                    >
                        {{ label('transition', locale) }}
                    </button>
                </li>
            </ol>
            <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
        </Phase5Section>

        <div class="phase5-grid">
            <Phase5Section :title="label('history', locale)">
                <ul v-if="history.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                    <li v-for="item in history" :key="item.id" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" aria-hidden="true" />
                        <div>
                            <div class="phase5-timeline__title">
                                {{ label(item.from_status, locale, item.from_status) }}
                                → {{ label(item.to_status, locale, item.to_status) }}
                            </div>
                            <div class="phase5-timeline__meta">{{ formatDate(item.created_at) }}</div>
                        </div>
                    </li>
                </ul>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
            </Phase5Section>

            <Phase5Section v-if="canTransition && allowedTargets.length" :title="label('transition', locale)">
                <div style="padding: 1rem 1.25rem">
                    <Phase5Form
                        :action="phase5Url('workflows.instances.transition', { instance: instance.id }, locale)"
                        :fields="transitionFields"
                        :submit-label="label('transition', locale)"
                        compact
                    />
                </div>
            </Phase5Section>
        </div>
    </Phase5Shell>
</template>
