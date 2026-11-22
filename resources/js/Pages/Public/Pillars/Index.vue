<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import PillarTile from '../../../Components/PillarTile.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    pillars: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.pillars') }]" />
                <p class="eyebrow">{{ t('ui.strategic_direction') }}</p>
                <h1 class="display-2">{{ t('navigation.pillars') }}</h1>
                <p class="lede">{{ t('strategic_areas_intro') }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('at_a_glance')" :body="t('planning_targets_notice')" />
                <div v-if="pillars.length" class="pillar-grid">
                    <PillarTile v-for="pillar in pillars" :key="pillar.code" :pillar="pillar" />
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>
    </PublicLayout>
</template>
