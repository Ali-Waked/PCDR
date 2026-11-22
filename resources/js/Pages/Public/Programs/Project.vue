<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    project: { type: Object, required: true },
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
                    { label: project.title },
                ]" />
                <div class="meta-row" style="margin-block-end: 0.75rem">
                    <span v-if="project.pillar" class="pill">{{ project.pillar }}</span>
                    <span v-if="project.program" class="pill">{{ project.program }}</span>
                    <span v-if="project.status" class="pill">{{ project.status }}</span>
                </div>
                <h1 class="display-2">{{ project.title }}</h1>
                <p v-if="project.summary" class="lede">{{ project.summary }}</p>
            </div>
        </section>

        <section v-if="project.image" class="section-block section-block--tight">
            <div class="container-public">
                <div class="media-cover" style="max-inline-size: 800px">
                    <img :src="project.image" :alt="project.title">
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <article>
                        <SectionHeading :title="t('ui.cms_description')" />
                        <div class="prose-editorial" style="white-space: pre-line">{{ richText(project.description) }}</div>
                    </article>
                    <aside class="cms-card" style="align-self: start">
                        <h2 style="font-size: 1rem; margin-block-start: 0">{{ t('cms_basic_data') }}</h2>
                        <dl style="display: grid; gap: 0.75rem; margin: 0">
                            <div v-if="project.startDate || project.endDate">
                                <dt class="small-caps">{{ t('dates') }}</dt>
                                <dd style="margin: 0">{{ formatDate(project.startDate) }} — {{ formatDate(project.endDate) }}</dd>
                            </div>
                            <div v-if="project.coverage">
                                <dt class="small-caps">{{ t('governance') }}</dt>
                                <dd style="margin: 0">{{ project.coverage }}</dd>
                            </div>
                        </dl>
                        <div class="notice" style="margin-block-start: 1rem; font-size: 0.8rem">{{ t('planning_targets_notice') }}</div>
                    </aside>
                </div>
            </div>
        </section>

        <section v-if="project.objectives?.length || project.targetGroups?.length" class="section-block surface-sand">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div v-if="project.objectives?.length">
                        <SectionHeading :title="t('ui.approach')" />
                        <ul><li v-for="(item, index) in project.objectives" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li></ul>
                    </div>
                    <div v-if="project.targetGroups?.length">
                        <SectionHeading :title="t('ui.target_groups')" />
                        <ul><li v-for="(item, index) in project.targetGroups" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li></ul>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
