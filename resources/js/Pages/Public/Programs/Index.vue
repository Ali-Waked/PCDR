<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import ProgramCard from '../../../Components/ProgramCard.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    programs: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.programs') }]" />
                <p class="eyebrow">{{ t('navigation.programs') }}</p>
                <h1 class="display-2">{{ t('ui.cms_programs') }}</h1>
                <p class="lede">{{ t('programs_intro') }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('ui.cms_programs')" />
                <div v-if="programs.length" class="pillar-grid">
                    <ProgramCard v-for="(program, index) in programs" :key="index" :program="program" />
                </div>
                <EmptyState v-else :body="t('ui.no_programs')" />
            </div>
        </section>

        <section class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :title="t('projects')" :body="t('planning_targets_notice')" />
                <div v-if="projects.length" class="list-rows">
                    <article v-for="(project, index) in projects" :key="index" class="list-row list-row--split" v-reveal>
                        <div>
                            <p v-if="project.program" class="small-caps">{{ project.program }}</p>
                            <h3 style="margin-block: 0.4rem">{{ project.title }}</h3>
                            <p v-if="project.summary" style="font-size: 0.9rem">{{ project.summary }}</p>
                        </div>
                        <div style="display: grid; gap: 0.5rem; justify-items: start">
                            <span class="pill">{{ project.status }}</span>
                            <a :href="project.url" class="text-link">{{ t('ui.read_more') }}</a>
                        </div>
                    </article>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>
    </PublicLayout>
</template>
