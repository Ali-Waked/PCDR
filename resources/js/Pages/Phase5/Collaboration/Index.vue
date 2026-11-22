<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Form from '../../../Components/Phase5/Phase5Form.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf } from '../../../Components/Phase5/phase5'

const props = defineProps({
    resource: { type: Object, default: () => ({}) },
    thread: { type: Object, default: null },
    comments: { type: Object, default: () => ({}) },
    followers: { type: Array, default: () => [] },
    canComment: { type: Boolean, default: false },
    canMention: { type: Boolean, default: false },
    canModerate: { type: Boolean, default: false },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('collaboration', locale.value))
const subtitle = computed(() => `${label(props.resource?.type, locale.value, props.resource?.type ?? '')} #${props.resource?.id ?? ''}`)
const backUrl = computed(() => phase5Url('tasks.index', {}, locale.value))
const comments = computed(() => rowsOf(props.comments))

const commentFields = computed(() => [
    { name: 'body', type: 'textarea', required: true, wide: true, help: props.canMention
        ? (locale.value === 'ar' ? 'يمكن الإشارة إلى المستخدمين المصرّح لهم فقط.' : 'Only authorized users may be mentioned.')
        : (locale.value === 'ar' ? 'الإشارات غير متاحة.' : 'Mentions are not available.') },
    { name: 'parent_id', type: 'number', min: 1 },
])

function toggleFollow() {
    router.post(phase5Url('discussions.follow.toggle', {
        resourceType: props.resource.type,
        resourceId: props.resource.id,
    }, locale.value), {}, { preserveScroll: true })
}
</script>

<template>
    <Phase5Shell :title="title" :subtitle="subtitle" :back="backUrl">
        <Phase5Section :title="label('thread', locale)">
            <div style="padding: 1rem 1.25rem">
                <div class="phase5-inline">
                    <Badge :tone="thread ? 'success' : 'neutral'">
                        {{ thread ? label('active', locale) : label('disabled_label', locale) }}
                    </Badge>
                    <button v-if="canComment || canMention" type="button" class="btn btn-secondary btn-small" @click="toggleFollow">
                        {{ label('follow', locale) }}
                    </button>
                </div>
                <p class="phase5-muted">
                    {{ locale === 'ar'
                        ? 'المتابعة والإشارات لا تمنحان صلاحية الوصول إلى السجل الأساسي.'
                        : 'Following and mentions do not grant access to the underlying record.' }}
                </p>
                <div v-if="followers.length" class="phase5-inline">
                    <Badge v-for="person in followers" :key="person.user_id">{{ person.name }}</Badge>
                </div>
            </div>
        </Phase5Section>

        <Phase5Section :title="label('comments', locale)">
            <ul v-if="comments.length" class="phase5-timeline" style="padding: 1rem 1.25rem">
                <li v-for="comment in comments" :key="comment.id" class="phase5-timeline__item">
                    <span class="phase5-timeline__marker" aria-hidden="true" />
                    <div>
                        <div class="phase5-timeline__title">
                            {{ comment.author_name || label('user', locale) }}
                            <Badge v-if="comment.deleted" tone="danger">{{ label('cancel', locale) }}</Badge>
                        </div>
                        <div class="phase5-muted">{{ comment.body }}</div>
                        <div class="phase5-timeline__meta">{{ formatDate(comment.created_at) }}</div>
                    </div>
                </li>
            </ul>
            <p v-else class="phase5-muted" style="padding: 1rem 1.25rem">{{ label('empty', locale) }}</p>
        </Phase5Section>

        <Phase5Section v-if="canComment" :title="label('create', locale) + ' · ' + label('comments', locale)">
            <div style="padding: 1rem 1.25rem">
                <Phase5Form
                    :action="phase5Url('discussions.comments.store', {
                        resourceType: resource.type,
                        resourceId: resource.id,
                    }, locale)"
                    :fields="commentFields"
                    :submit-label="label('submit', locale)"
                />
            </div>
        </Phase5Section>
    </Phase5Shell>
</template>
