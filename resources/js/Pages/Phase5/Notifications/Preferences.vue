<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, optionLabel, optionValue, phase5Url } from '../../../Components/Phase5/phase5'

const props = defineProps({
    preferences: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    channels: { type: Array, default: () => [] },
    digestOptions: { type: Array, default: () => [] },
    mandatoryCategories: { type: Array, default: () => [] },
})

const { locale } = useI18n()

const title = computed(() => label('notification_preferences', locale.value))
const backUrl = computed(() => phase5Url('notifications.index', {}, locale.value))

const rows = computed(() => (props.categories ?? []).flatMap((category) => (props.channels ?? []).map((channel) => {
    const saved = props.preferences?.[category]?.[channel] ?? {}

    return {
        category,
        channel,
        enabled: saved.enabled === true,
        digest: saved.digest || 'instant',
        quiet_start: saved.quiet_start || '',
        quiet_end: saved.quiet_end || '',
        mandatory: (props.mandatoryCategories ?? []).includes(category),
    }
})))

const form = useForm({ preferences: rows.value })

function isMandatory(category) {
    return (props.mandatoryCategories ?? []).includes(category)
}

function submit() {
    form.transform((data) => ({
        preferences: data.preferences.map((item) => ({
            ...item,
            enabled: isMandatory(item.category) ? true : item.enabled,
            quiet_start: item.quiet_start || null,
            quiet_end: item.quiet_end || null,
        })),
    })).put(phase5Url('notifications.preferences.update', {}, locale.value), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Phase5Shell :title="title" :back="backUrl">
        <Phase5Section :title="label('settings', locale)">
            <p class="phase5-muted" style="padding: 1rem 1.25rem 0">
                {{ locale === 'ar'
                    ? 'القنوات الخارجية معطّلة افتراضياً؛ لا يُادّعى تسليم خارجي دون تأكيد المزوّد.'
                    : 'External channels are disabled by default; no external delivery is claimed without provider confirmation.' }}
            </p>

            <form style="padding: 1rem 1.25rem 1.25rem" @submit.prevent="submit">
                <div class="phase5-table-scroll">
                    <table class="phase5-table is-compact">
                        <thead>
                            <tr>
                                <th>{{ label('category', locale) }}</th>
                                <th>{{ label('channel', locale) }}</th>
                                <th>{{ label('enabled_label', locale) }}</th>
                                <th>{{ label('digest', locale) }}</th>
                                <th>{{ label('quiet_hours', locale) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.preferences" :key="`${item.category}-${item.channel}`">
                                <td>
                                    <Badge v-if="item.mandatory" tone="warning">{{ label('mandatory', locale) }}</Badge>
                                    {{ label(item.category, locale, item.category) }}
                                </td>
                                <td>{{ label(item.channel, locale, item.channel) }}</td>
                                <td>
                                    <label class="checkbox-row">
                                        <input v-model="form.preferences[index].enabled" type="checkbox" :disabled="item.mandatory" />
                                        <span>{{ item.enabled ? label('yes', locale) : label('no', locale) }}</span>
                                    </label>
                                </td>
                                <td>
                                    <select v-model="form.preferences[index].digest" class="select">
                                        <option v-for="option in digestOptions" :key="optionValue(option)" :value="optionValue(option)">
                                            {{ optionLabel(option, locale) }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <div class="phase5-inline">
                                        <input v-model="form.preferences[index].quiet_start" class="input" type="time" :aria-label="locale === 'ar' ? 'بداية' : 'Start'" />
                                        <input v-model="form.preferences[index].quiet_end" class="input" type="time" :aria-label="locale === 'ar' ? 'نهاية' : 'End'" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="Object.keys(form.errors).length" class="notice" role="alert">
                    <span v-for="(message, field) in form.errors" :key="field">{{ message }}</span>
                </div>

                <div class="phase5-form__actions">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing" :aria-busy="form.processing">
                        {{ label('save', locale) }}
                    </button>
                    <Link :href="backUrl" class="btn btn-ghost">{{ label('cancel', locale) }}</Link>
                </div>
            </form>
        </Phase5Section>
    </Phase5Shell>
</template>
