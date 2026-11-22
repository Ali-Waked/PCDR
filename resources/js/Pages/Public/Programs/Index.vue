<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import ProgramCard from '../../../Components/ProgramCard.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    programs: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="programs-page">
        <PublicPageHero
            :title="t('ui.cms_programs')"
            :lead="t('programs_intro')"
            :breadcrumbs="[{ label: t('nav.programs') }]"
            accent="teal"
            motif="wheat"
        />

        <section class="ps-section">
            <div class="ps-container">
                <SectionHeading :title="t('ui.cms_programs')" :body="t('planning_targets_notice')" />
                <div v-if="programs.length" class="ps-grid" data-stagger>
                    <ProgramCard v-for="(program, index) in programs" :key="index" :program="program" />
                </div>
                <EmptyState v-else :body="t('ui.no_programs')" />
            </div>
        </section>

        <section class="ps-section ps-surface-sand">
            <div class="ps-container">
                <SectionHeading :title="t('projects')" :body="t('planning_targets_notice')" />
                <div v-if="projects.length" class="ps-rows">
                    <article v-for="(project, index) in projects" :key="index" class="ps-row" v-reveal>
                        <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                        <div>
                            <p class="ps-card__code">{{ project.program }}</p>
                            <h3>{{ project.title }}</h3>
                            <p>{{ project.summary }}</p>
                        </div>
                        <div class="ps-actions">
                            <span class="ps-pill">{{ project.status }}</span>
                            <a :href="project.url" class="ps-text-link">{{ t('ui.read_more') }}</a>
                        </div>
                    </article>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>

        <PublicCta
            :title="t('nav.pillars')"
            :lede="t('strategic_areas_intro')"
            :actions="[{ label: t('nav.pillars'), url: localPath('strategic-areas'), primary: true }]"
        />
    </PublicLayout>
</template>
