<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import EmptyState from '../../Components/Dashboard/EmptyState.vue'
import MetricCard from '../../Components/Dashboard/MetricCard.vue'
import StatusBadge from '../../Components/Dashboard/StatusBadge.vue'
import { useI18n } from '../../composables/useI18n'
import { formatCount, humanize, operationRoute } from '../../Components/Operations/operations'

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
})

const { t, locale } = useI18n()

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
    <AppLayout :title="t('ui.dashboard', humanize('dashboard'))" :subtitle="humanize('operations_overview')">
        <div class="pc-stack">
            <div class="pc-callout pc-callout--sand" role="note">
                <span>{{ privacyNotice }}</span>
            </div>

            <section class="pc-card">
                <header class="pc-card__head">
                    <h2 class="pc-card__title">{{ t('ui.dashboard', humanize('dashboard')) }}</h2>
                </header>
                <div class="pc-card__body">
                    <div class="pc-metric-grid">
                        <MetricCard
                            :label="humanize('on_hand')"
                            :value="formatCount(inventory.on_hand, locale)"
                            icon="folder"
                        />
                        <MetricCard
                            :label="humanize('reserved')"
                            :value="formatCount(inventory.reserved, locale)"
                            icon="layers"
                            tone="sand"
                        />
                        <MetricCard
                            :label="humanize('inventory_valuation_enabled')"
                            :value="inventory.valuation_enabled ? humanize('enabled') : humanize('disabled')"
                            icon="settings"
                            tone="navy"
                        />
                    </div>
                </div>
            </section>

            <section class="pc-card">
                <header class="pc-card__head">
                    <h2 class="pc-card__title">{{ humanize('operational_modules') }}</h2>
                </header>
                <div class="pc-card__body">
                    <div v-if="modules.length" class="pc-grid pc-grid--3">
                        <article v-for="module in modules" :key="module.module" class="pc-card">
                            <header class="pc-card__head">
                                <h3 class="pc-card__title">{{ humanize(module.module) }}</h3>
                                <StatusBadge :label="String(formatCount(module.records, locale))" tone="teal" />
                            </header>
                            <div class="pc-card__body">
                                <ul class="pc-list">
                                    <li v-for="resource in (module.resources || [])" :key="resource.resource" class="pc-list__item">
                                        <div class="pc-list__copy">
                                            <Link
                                                v-if="canManage"
                                                :href="resourceUrl(resource.resource)"
                                                class="pc-link"
                                            >
                                                {{ resource.label || humanize(resource.resource) }}
                                            </Link>
                                            <span v-else>{{ resource.label || humanize(resource.resource) }}</span>
                                        </div>
                                        <span class="pc-list__meta">{{ formatCount(resource.count, locale) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </article>
                    </div>
                    <EmptyState v-else :title="t('ui.no_results')" />
                </div>
            </section>

            <section class="pc-card">
                <header class="pc-card__head">
                    <h2 class="pc-card__title">{{ humanize('gates') }}</h2>
                </header>
                <div class="pc-card__body">
                    <div v-if="gates.length" class="pc-token-list">
                        <span
                            v-for="([gate, enabled], index) in gates"
                            :key="index"
                            class="pc-token"
                        >
                            {{ humanize(gate) }}:
                            <StatusBadge
                                :label="enabled ? humanize('enabled') : humanize('disabled')"
                                :tone="enabled ? 'green' : 'slate'"
                            />
                        </span>
                    </div>
                    <EmptyState v-else :title="t('ui.no_results')" />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
