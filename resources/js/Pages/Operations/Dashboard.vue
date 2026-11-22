<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
import { useI18n } from '../../composables/useI18n'
import { formatCount, humanize, operationRoute } from '../../Components/Operations/operations'

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
})

const { t, locale, direction, flash } = useI18n()

const modules = computed(() => (Array.isArray(props.summary?.modules) ? props.summary.modules : []))
const inventory = computed(() => props.summary?.inventory ?? {})
const gates = computed(() => Object.entries(props.summary?.gates ?? {}))
const privacyNotice = computed(() => props.summary?.privacy_notice
    || 'Counts only; no beneficiary, household, or identifiable records are present.')

function resourceUrl(resource) {
    return operationRoute(`operations.${resource}.index`, {}, locale.value)
}
</script>

<template>
    <CmsLayout :title="t('ui.dashboard', humanize('dashboard'))" :subtitle="humanize('operations_overview')">
        <div :dir="direction" class="space-y-5 text-ink">
            <div
                v-if="flash?.success || flash?.error"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="flash?.error ? 'border-danger/30 bg-danger/5 text-danger' : 'border-success/30 bg-success/5 text-success'"
                role="status"
            >
                {{ flash?.error || flash?.success }}
            </div>

            <p class="rounded-lg border border-teal-700/20 bg-teal-soft px-4 py-3 text-sm text-teal-deep">
                {{ privacyNotice }}
            </p>

            <section class="rounded-xl border border-border-subtle bg-surface p-5">
                <h2 class="mb-4 text-base font-semibold">{{ t('ui.dashboard', humanize('dashboard')) }}</h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-surface-alt p-4">
                        <strong class="block text-xl">{{ formatCount(inventory.on_hand, locale) }}</strong>
                        <span class="text-xs text-muted">{{ humanize('on_hand') }}</span>
                    </div>
                    <div class="rounded-lg bg-surface-alt p-4">
                        <strong class="block text-xl">{{ formatCount(inventory.reserved, locale) }}</strong>
                        <span class="text-xs text-muted">{{ humanize('reserved') }}</span>
                    </div>
                    <div class="rounded-lg bg-surface-alt p-4">
                        <strong class="block text-xl">{{ inventory.valuation_enabled ? humanize('enabled') : humanize('disabled') }}</strong>
                        <span class="text-xs text-muted">{{ humanize('inventory_valuation_enabled') }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border-subtle bg-surface p-5">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('operational_modules') }}</h2>
                <div v-if="modules.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="module in modules" :key="module.module" class="rounded-lg border border-border-subtle p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold">{{ humanize(module.module) }}</h3>
                            <span class="rounded-full bg-navy-100 px-2.5 py-1 text-xs font-medium text-navy-700">
                                {{ formatCount(module.records, locale) }}
                            </span>
                        </div>
                        <ul class="space-y-1.5 text-sm">
                            <li v-for="resource in (module.resources || [])" :key="resource.resource" class="flex items-center justify-between gap-3">
                                <Link
                                    v-if="canManage"
                                    :href="resourceUrl(resource.resource)"
                                    class="text-teal-deep underline decoration-current/30"
                                >
                                    {{ resource.label || humanize(resource.resource) }}
                                </Link>
                                <span v-else>{{ resource.label || humanize(resource.resource) }}</span>
                                <span class="text-muted">{{ formatCount(resource.count, locale) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <p v-else class="text-sm text-muted">{{ t('ui.no_results') }}</p>
            </section>

            <section class="rounded-xl border border-border-subtle bg-surface p-5">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('gates') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="([gate, enabled], index) in gates"
                        :key="index"
                        class="rounded-full px-3 py-1 text-xs font-medium"
                        :class="enabled ? 'bg-success/10 text-success' : 'bg-surface-alt text-muted'"
                    >
                        {{ humanize(gate) }}: {{ enabled ? humanize('enabled') : humanize('disabled') }}
                    </span>
                    <p v-if="!gates.length" class="text-sm text-muted">{{ t('ui.no_results') }}</p>
                </div>
            </section>
        </div>
    </CmsLayout>
</template>
