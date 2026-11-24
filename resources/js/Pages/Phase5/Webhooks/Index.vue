<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Phase5Shell from '../../../Components/Phase5/Phase5Shell.vue'
import Phase5Section from '../../../Components/Phase5/Phase5Section.vue'
import Phase5Table from '../../../Components/Phase5/Phase5Table.vue'
import Phase5Pagination from '../../../Components/Phase5/Phase5Pagination.vue'
import MultiSelect from '../../../Components/Selection/MultiSelect.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useI18n } from '../../../composables/useI18n'
import { label, phase5Url, rowsOf, toneFor } from '../../../Components/Phase5/phase5'

const props = defineProps({
    endpoints: { type: Object, default: () => ({}) },
    deliveries: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
    gates: { type: Object, default: () => ({}) },
})

const { locale, formatDate } = useI18n()

const title = computed(() => label('webhooks', locale.value))
const endpointRows = computed(() => rowsOf(props.endpoints))
const deliveryRows = computed(() => rowsOf(props.deliveries))
const enabled = computed(() => props.gates?.webhooks_enabled === true)

const endpointColumns = computed(() => [
    { key: 'name' },
    { key: 'url' },
    { key: 'events' },
    { key: 'active', boolean: true },
    { key: 'last_success_at', format: 'date' },
    { key: 'last_failure_at', format: 'date' },
])

const deliveryColumns = computed(() => [
    { key: 'event' },
    { key: 'status', badge: true },
    { key: 'attempts' },
    { key: 'response_code' },
    { key: 'error_code' },
    { key: 'created_at', format: 'date' },
    { key: 'actions', label: label('actions', locale.value) },
])

const form = useForm({
    integration_id: '',
    name: '',
    url: '',
    secret: '',
    events: [],
    active: false,
})

const eventOptions = computed(() => props.events.map((event) => ({
    value: event,
    label: label(event, locale.value, event),
})))

function submit() {
    form.post(phase5Url('webhooks.endpoints.store', {}, locale.value), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}

function retry(delivery) {
    router.post(
        phase5Url('webhooks.deliveries.retry', { delivery: delivery.id }, locale.value),
        {},
        { preserveScroll: true },
    )
}
</script>

<template>
    <Phase5Shell :title="title">
        <div class="notice" role="note">
            {{ enabled
                ? (locale === 'ar'
                    ? 'الويب هوكس مفعّلة: تُقبل만 الوجهات HTTPS العامة، وتُتحقق التواقيع والطوابع الزمنية.'
                    : 'Webhooks are enabled: only public HTTPS destinations are accepted, with signature and timestamp verification.')
                : (locale === 'ar'
                    ? 'الويب هوكس معطّلة افتراضياً؛ لا يمكن إنشاء نقاط استقبال أو إعادة إرسال حتى يُوثّق القرار.'
                    : 'Webhooks are disabled by default; endpoints and retries are unavailable until formally decided.') }}
        </div>

        <Phase5Section :title="label('endpoints', locale)" flush>
            <Phase5Table :columns="endpointColumns" :rows="endpointRows" compact>
                <template #cell-url="{ value }">
                    <span class="phase5-code">{{ value }}</span>
                </template>
                <template #cell-events="{ value }">
                    <span v-if="Array.isArray(value) && value.length" class="phase5-inline">
                        <Badge v-for="event in value" :key="event">{{ label(event, locale, event) }}</Badge>
                    </span>
                    <span v-else>—</span>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="endpoints" page-key="endpointPage" />
            </div>
        </Phase5Section>

        <Phase5Section :title="label('deliveries', locale)" flush>
            <Phase5Table :columns="deliveryColumns" :rows="deliveryRows" compact>
                <template #cell-status="{ value }">
                    <Badge :tone="toneFor(value)">{{ label(value, locale, value) }}</Badge>
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="enabled && row.status === 'failed'"
                        type="button"
                        class="btn btn-secondary btn-small"
                        @click="retry(row)"
                    >
                        {{ label('retry', locale) }}
                    </button>
                    <span v-else>—</span>
                </template>
            </Phase5Table>
            <div style="padding: 0 1.25rem 1.25rem">
                <Phase5Pagination :paginator="deliveries" page-key="deliveryPage" />
            </div>
        </Phase5Section>

        <Phase5Section v-if="enabled" :title="label('create', locale) + ' · ' + label('endpoints', locale)">
            <form style="padding: 1rem 1.25rem 1.25rem" @submit.prevent="submit">
                <div class="phase5-form__grid">
                    <div class="field">
                        <label for="phase5-webhook-integration">{{ label('integration', locale, label('integrations', locale)) }}</label>
                        <input id="phase5-webhook-integration" v-model="form.integration_id" class="input" type="number" min="1" required />
                    </div>
                    <div class="field">
                        <label for="phase5-webhook-name">{{ label('name', locale) }}</label>
                        <input id="phase5-webhook-name" v-model="form.name" class="input" required />
                    </div>
                    <div class="field is-wide">
                        <label for="phase5-webhook-url">{{ label('url', locale) }}</label>
                        <input id="phase5-webhook-url" v-model="form.url" class="input" type="url" required />
                        <span class="hint">{{ locale === 'ar' ? 'HTTPS فقط؛ تُرفض العناوين الخاصة.' : 'HTTPS only; private addresses are rejected.' }}</span>
                    </div>
                    <div class="field is-wide">
                        <label for="phase5-webhook-secret">{{ locale === 'ar' ? 'السر' : 'Secret' }}</label>
                        <input id="phase5-webhook-secret" v-model="form.secret" class="input" type="password" required />
                    </div>
                </div>

                <div style="margin: 1.25rem 0 0">
                    <MultiSelect
                        v-model="form.events"
                        :label="label('events', locale)"
                        :options="eventOptions"
                        :error="form.errors.events || ''"
                        :empty-text="label('no_results', locale, locale === 'ar' ? 'لا توجد نتائج' : 'No results')"
                        clearable
                    />
                </div>

                <div v-if="Object.keys(form.errors).length" class="notice" role="alert">
                    <span v-for="(message, field) in form.errors" :key="field">{{ message }}</span>
                </div>

                <div class="phase5-form__actions">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing" :aria-busy="form.processing">
                        {{ label('create', locale) }}
                    </button>
                </div>
            </form>
        </Phase5Section>
    </Phase5Shell>
</template>
