<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import Phase5FilterBar from '../../../Components/Phase5/Phase5FilterBar.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    notifications: { type: Object, default: () => ({}) },
    unreadCount: { type: Number, default: 0 },
    categories: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('notifications', locale.value))
const rows = computed(() => rowsOf(props.notifications))

const columns = computed(() => [
    { key: 'title_key' },
    { key: 'preview_key' },
    { key: 'category', badge: true },
    { key: 'priority', badge: true },
    { key: 'created_at', format: 'date' },
    { key: 'read_at' },
    { key: 'actions' },
])

const filterFields = computed(() => [
    { name: 'category', type: 'select', options: props.categories ?? [] },
    { name: 'priority', type: 'select', options: props.priorities ?? [] },
    {
        name: 'unread',
        type: 'select',
        options: [{ value: '1', label: label('unread', locale.value) }],
    },
])

function markRead(id) {
    router.post(phase5Url('notifications.read', { notification: id }, locale.value), {}, { preserveScroll: true })
}

function markAll() {
    router.post(phase5Url('notifications.read-all', {}, locale.value), {}, { preserveScroll: true })
}

function safeTitle(item) {
    return label(item.title_key, locale.value, label(item.category, locale.value, item.title_key))
}

function safePreview(item) {
    return label(item.preview_key, locale.value, '')
}
</script>

<template>
    <Phase5Shell :title="title">
        <template #actions>
            <Link :href="phase5Url('notifications.preferences', {}, locale)" class="btn btn-secondary btn-small">
                {{ label('notification_preferences', locale) }}
            </Link>
            <button v-if="unreadCount" type="button" class="btn btn-primary btn-small" @click="markAll">
                {{ label('mark_all_read', locale) }}
            </button>
        </template>

        <div class="phase5-stats">
            <div class="phase5-stat">
                <span class="phase5-stat__value">{{ unreadCount }}</span>
                <span class="phase5-stat__label">{{ label('unread', locale) }}</span>
            </div>
            <div class="phase5-stat">
                <span class="phase5-stat__value">{{ rows.length }}</span>
                <span class="phase5-stat__label">{{ locale === 'ar' ? 'في هذه الصفحة' : 'On this page' }}</span>
            </div>
        </div>

        <Phase5Section :title="label('filters', locale)">
            <Phase5FilterBar
                :action="phase5Url('notifications.index', {}, locale)"
                :fields="filterFields"
            />
        </Phase5Section>

        <Phase5Section :title="label('notifications', locale)" flush>
            <Phase5Table :columns="columns" :rows="rows" compact>
                <template #cell-title_key="{ row }">
                    <Link v-if="row.url" :href="row.url" class="phase5-inline">{{ safeTitle(row) }}</Link>
                    <span v-else>{{ safeTitle(row) }}</span>
                </template>
                <template #cell-preview_key="{ row }">
                    <span class="phase5-muted">{{ safePreview(row) || '—' }}</span>
                </template>
                <template #cell-category="{ value }">
                    <Badge :tone="toneFor(value)">{{ label(value, locale, value) }}</Badge>
                </template>
                <template #cell-priority="{ value }">
                    <Badge :tone="toneFor(value)">{{ label(value, locale, value) }}</Badge>
                </template>
                <template #cell-read_at="{ value }">
                    <Badge :tone="value ? 'success' : 'warning'">
                        {{ value ? formatDate(value) : label('unread', locale) }}
                    </Badge>
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="!row.read_at"
                        type="button"
                        class="btn btn-ghost btn-small"
                        @click="markRead(row.id)"
                    >
                        {{ label('save', locale) }}
                    </button>
                    <span v-else>—</span>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="notifications" />
            </div>
        </Phase5Section>
    </Phase5Shell>
</template>
