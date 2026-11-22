<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Phase5Shell from '../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../Components/Phase5/Phase5Section.vue'
import Badge from '../../Components/Ui/Badge.vue'
import { useI18n } from '../../composables/useI18n'
import { label, phase5Url, toneFor } from '../../Components/Phase5/phase5'

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    sections: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    gates: { type: Object, default: () => ({}) },
    notice: { type: String, default: '' },
})

const { t, locale, formatDate } = useI18n()

const sections = computed(() => props.sections ?? [])
const activity = computed(() => props.activity ?? [])
const gates = computed(() => Object.entries(props.gates ?? {}))
const privacyNotice = computed(() => t('phase5.privacy_notice', 'Counts and aggregates only; no personal data is shown.'))
const title = computed(() => label('dashboard', locale.value))
const subtitle = computed(() => locale.value === 'ar'
    ? 'طبقة التشغيل المؤسسي — بيانات مجمّعة وآمنة فقط'
    : 'Institutional operating layer — aggregate, non-identifying data only')

const shortcuts = computed(() => [
    { key: 'workflows', url: phase5Url('workflows.index', {}, locale.value) },
    { key: 'tasks', url: phase5Url('tasks.index', {}, locale.value) },
    { key: 'notifications', url: phase5Url('notifications.index', {}, locale.value) },
    { key: 'calendar', url: phase5Url('calendar.index', {}, locale.value) },
])
</script>

<template>
    <Phase5Shell :title="title" :subtitle="subtitle" :notice="notice">
        <div class="phase5-stats">
            <div v-for="section in sections" :key="section.key" class="phase5-stat">
                <span class="phase5-stat__value">{{ section.count ?? 0 }}</span>
                <span class="phase5-stat__label">{{ label(section.key, locale) }}</span>
            </div>
        </div>

        <div class="phase5-grid phase5-grid--wide">
            <Phase5Section :title="label('activity', locale)" :subtitle="privacyNotice">
                <ul v-if="activity.length" class="phase5-timeline">
                    <li v-for="item in activity" :key="`${item.source}-${item.id}`" class="phase5-timeline__item">
                        <span class="phase5-timeline__marker" aria-hidden="true" />
                        <div>
                            <div class="phase5-timeline__title">
                                {{ label(item.action, locale, item.action) }}
                                <Badge v-if="item.status" :tone="toneFor(item.status)" class="phase5-inline">
                                    {{ label(item.status, locale, item.status) }}
                                </Badge>
                            </div>
                            <div class="phase5-timeline__meta">
                                {{ label(item.subject_type, locale, item.subject_type) }} · {{ formatDate(item.occurred_at) }}
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="phase5-muted">{{ label('empty', locale) }}</p>
            </Phase5Section>

            <div class="phase5-page" style="gap: 1rem">
                <Phase5Section :title="label('summary', locale)">
                    <div class="phase5-inline" style="padding: 1rem 1.25rem">
                        <Link
                            v-for="shortcut in shortcuts"
                            :key="shortcut.key"
                            :href="shortcut.url"
                            class="btn btn-secondary btn-small"
                        >
                            {{ label(shortcut.key, locale) }}
                        </Link>
                    </div>
                </Phase5Section>

                <Phase5Section :title="label('gates', locale)">
                    <div style="padding: 0.5rem 1.25rem 1rem">
                        <div v-for="(value, key) in gates" :key="key" class="phase5-gate" :class="{ 'is-off': !value }">
                            <span>{{ label(key, locale, key) }}</span>
                            <Badge :tone="value ? 'success' : 'neutral'">
                                {{ value ? label('enabled_label', locale) : label('disabled_label', locale) }}
                            </Badge>
                        </div>
                        <p v-if="!gates.length" class="phase5-muted">{{ label('empty', locale) }}</p>
                    </div>
                </Phase5Section>
            </div>
        </div>
    </Phase5Shell>
</template>
