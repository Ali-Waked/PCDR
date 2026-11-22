<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import PillarTile from '../../../Components/PillarTile.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    pillars: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="pillars-page">
        <PublicPageHero
            :eyebrow="t('ui.strategic_direction')"
            :title="t('nav.pillars')"
            :lede="t('strategic_areas_intro')"
            :crumbs="[{ label: t('nav.pillars') }]"
            index="11"
            :badge="t('at_a_glance')"
            :actions="[{ label: t('nav.about'), url: localPath('about/direction'), primary: true }]"
        />

        <section class="ps-section">
            <div class="ps-container">
                <SectionHeading :title="t('at_a_glance')" :body="t('planning_targets_notice')" />
                <div v-if="pillars.length" class="ps-grid" data-stagger>
                    <PillarTile v-for="pillar in pillars" :key="pillar.code" :pillar="pillar" />
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>

        <PublicCta
            :title="t('nav.programs')"
            :lede="t('programs_intro')"
            :actions="[{ label: t('nav.programs'), url: localPath('programs'), primary: true }]"
        />
    </PublicLayout>
</template>
