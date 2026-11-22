<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    task: { type: Object, default: () => ({}) },
    checklist: { type: Array, default: () => [] },
    dependencies: { type: Array, default: () => [] },
    collaborators: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    transitions: { type: Object, default: () => ({}) },
})

const { locale, formatDate } = useI18n()

const title = computed(() => props.task?.title || label('tasks', locale.value))
const subtitle = computed(() => props.task?.code || '')
const backUrl = computed(() => phase5Url('tasks.index', {}, locale.value))

const allowedTargets = computed(() => props.transitions?.[props.task?.status] ?? [])

const transitionFields = computed(() => [
    { name: 'to_status', type: 'select', options: allowedTargets.value },
    { name: 'note', type: 'textarea', wide: true },
])

const checklistFields = [
    { name: 'title', required: true, wide: true },
]

const dependencyFields = [
    { name: 'depends_on_task_id', type: 'number', min: 1, required: true },
    { name: 'type', type: 'select', options: ['blocks', 'relates_to'] },
]

const detailFields = computed(() => [
    { key: 'code' }, { key: 'status' }, { key: 'priority' },
    { key: 'owner_name' }, { key: 'creator_name' },
    { key: 'subject_type' }, { key: 'subject_id' },
    { key: 'start_at', date: true }, { key: 'due_at', date: true },
    { key: 'progress_percent' }, { key: 'created_at', date: true },
])

function valueFor(field) {
    const value = props.task?.[field.key]
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return field.date ? formatDate(value) : label(value, locale.value, String(value))
}

function toggleItem(item) {
    router.post(
        phase5Url('tasks.checklist.toggle', { task: props.task.id, item: item.id }, locale.value),
        { is_done: !item.is_done },
        { preserveScroll: true },
    )
}

function discussionUrl() {
    return phase5Url('discussions.index', {
        resourceType: 'task',
        resourceId: props.task.id,
    }, locale.value)
}
</script>

<template>
    <Phase5Shell :title="title" :subtitle="subtitle" :back="backUrl" :eyebrow="label('tasks', locale)">
        <Phase5Section :title="label('details', locale)">
            <div v-if="props.task?.description" class="phase5-muted" style="padding: 1rem 1.25rem 0">
                {{ props.task.description }}
            </div>
            <dl class="phase5-kv" style="padding: 1rem 1.25rem">
                <div v-for="field in detailFields" :key="field.key">
                    <dt>{{ label(field.key, locale) }}</dt>
                    <dd>
                        <Badge v-if="field.key === 'status' || field.key === 'priority'" :tone="toneFor(props.task?.[field.key])">
                            {{ valueFor(field) }}
                        </Badge>
                        <span v-else>{{ valueFor(field) }}</span>
                    </dd>
                </div>
            </dl>
            <div style="padding: 0 1.25rem 1.25rem">
                <Link :href="discussionUrl()" class="btn btn-secondary btn-small">
                    {{ label('discussion', locale, label('collaboration', locale)) }}
                </Link>
            </div>
        </Phase5Section>

        <div class="phase5-grid">
            <Phase5Section :title="label('checklist', locale)">
                <ul v-if="checklist.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                    <li v-for="item in checklist" :key="item.id" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" :class="{ 'is-on': item.is_done }" aria-hidden="true" />
                        <div style="flex: 1">
                            <div class="phase5-timeline__title" :class="{ 'phase5-muted': item.is_done }">
                                {{ item.title }}
                            </div>
                            <div v-if="item.completed_at" class="phase5-timeline__meta">
                                {{ formatDate(item.completed_at) }}
                            </div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-small" @click="toggleItem(item)">
                            {{ item.is_done ? label('cancel', locale) : label('save', locale) }}
                        </button>
                    </li>
                </ul>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
                <div style="padding: 1rem 1.25rem 1.25rem">
                    <Phase5Form
                        :action="phase5Url('tasks.checklist.store', { task: task.id }, locale)"
                        :fields="checklistFields"
                        :submit-label="label('create', locale)"
                        compact
                    />
                </div>
            </Phase5Section>

            <Phase5Section :title="label('dependencies', locale)">
                <ul v-if="dependencies.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                    <li v-for="item in dependencies" :key="item.id" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" aria-hidden="true" />
                        <div>
                            <div class="phase5-timeline__title">{{ item.depends_on_title || item.depends_on_code }}</div>
                            <div class="phase5-timeline__meta">{{ label(item.type, locale, item.type) }}</div>
                        </div>
                    </li>
                </ul>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
                <div style="padding: 1rem 1.25rem 1.25rem">
                    <Phase5Form
                        :action="phase5Url('tasks.dependencies.store', { task: task.id }, locale)"
                        :fields="dependencyFields"
                        :submit-label="label('create', locale)"
                        compact
                    />
                </div>
            </Phase5Section>
        </div>

        <Phase5Section v-if="allowedTargets.length" :title="label('transition', locale)">
            <div style="padding: 1rem 1.25rem">
                <Phase5Form
                    :action="phase5Url('tasks.transition', { task: task.id }, locale)"
                    :fields="transitionFields"
                    :submit-label="label('transition', locale)"
                    compact
                />
            </div>
        </Phase5Section>

        <div class="phase5-grid">
            <Phase5Section :title="label('collaborators', locale)">
                <div v-if="collaborators.length" class="phase5-inline" style="padding: 1rem 1.25rem">
                    <Badge v-for="person in collaborators" :key="person.user_id">{{ person.name }}</Badge>
                </div>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
            </Phase5Section>

            <Phase5Section :title="label('activity', locale)">
                <ul v-if="activity.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                    <li v-for="item in activity" :key="item.id" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" aria-hidden="true" />
                        <div>
                            <div class="phase5-timeline__title">{{ label(item.action, locale, item.action) }}</div>
                            <div class="phase5-timeline__meta">
                                {{ label(item.source, locale, item.source) }} · {{ formatDate(item.created_at) }}
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
            </Phase5Section>
        </div>
    </Phase5Shell>
</template>
