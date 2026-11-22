<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    program: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, formatDate, richText, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[
                    { label: t('navigation.programs'), url: localPath('programs') },
                    { label: program.title },
                ]" />
                <p v-if="program.pillar" class="eyebrow">{{ program.pillar }}</p>
                <h1 class="display-2">{{ program.title }}</h1>
                <p v-if="program.summary" class="lede">{{ program.summary }}</p>
            </div>
        </section>

        <section v-if="program.image" class="section-block section-block--tight">
            <div class="container-public">
                <div class="media-cover" style="max-inline-size: 800px">
                    <img :src="program.image" :alt="program.title">
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <article>
                        <SectionHeading :title="t('ui.cms_description')" />
                        <div class="prose-editorial" style="white-space: pre-line">{{ richText(program.description) }}</div>
                    </article>
                    <aside class="cms-card" style="align-self: start">
                        <h2 style="font-size: 1rem; margin-block-start: 0">{{ t('cms_basic_data') }}</h2>
                        <dl style="display: grid; gap: 0.75rem; margin: 0">
                            <div v-if="program.startDate || program.endDate">
                                <dt class="small-caps">{{ t('dates') }}</dt>
                                <dd style="margin: 0">{{ formatDate(program.startDate) }} — {{ formatDate(program.endDate) }}</dd>
                            </div>
                            <div v-if="program.coverage">
                                <dt class="small-caps">{{ t('governance') }}</dt>
                                <dd style="margin: 0">{{ program.coverage }}</dd>
                            </div>
                        </dl>
                    </aside>
                </div>
            </div>
        </section>

        <section v-if="program.objectives?.length || program.targetGroups?.length" class="section-block surface-sand">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div v-if="program.objectives?.length">
                        <SectionHeading :title="t('ui.approach')" />
                        <ul><li v-for="(item, index) in program.objectives" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li></ul>
                    </div>
                    <div v-if="program.targetGroups?.length">
                        <SectionHeading :title="t('ui.target_groups')" />
                        <ul><li v-for="(item, index) in program.targetGroups" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li></ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('projects')" />
                <div v-if="projects.length" class="list-rows">
                    <Link v-for="(project, index) in projects" :key="index" :href="project.url" class="list-row list-row--split" v-reveal>
                        <div>
                            <h3 style="margin: 0 0 0.3rem; font-size: 1rem">{{ project.title }}</h3>
                            <p v-if="project.summary" style="font-size: 0.88rem">{{ project.summary }}</p>
                        </div>
                        <span class="pill">{{ project.status }}</span>
                    </Link>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>
    </PublicLayout>
</template>
