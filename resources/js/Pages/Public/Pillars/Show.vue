<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    pillar: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    articles: { type: Array, default: () => [] },
    publications: { type: Array, default: () => [] },
    otherPillars: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[
                    { label: t('navigation.pillars'), url: localPath('strategic-areas') },
                    { label: pillar.shortTitle || pillar.title },
                ]" />
                <p class="eyebrow">{{ pillar.code }} · {{ t('ui.strategic_direction') }}</p>
                <h1 class="display-2">{{ pillar.title }}</h1>
                <p v-if="pillar.description" class="lede">{{ pillar.description }}</p>
            </div>
        </section>

        <section v-if="pillar.image" class="section-block section-block--tight">
            <div class="container-public">
                <div class="media-cover" style="max-inline-size: 720px">
                    <img :src="pillar.image" :alt="pillar.imageAlt || pillar.title">
                </div>
            </div>
        </section>

        <section v-if="pillar.objectives?.length || pillar.targetGroups?.length" class="section-block surface-soft">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div v-if="pillar.objectives?.length">
                        <SectionHeading :title="t('ui.approach')" />
                        <ul>
                            <li v-for="(item, index) in pillar.objectives" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li>
                        </ul>
                    </div>
                    <div v-if="pillar.targetGroups?.length">
                        <SectionHeading :title="t('ui.target_groups')" />
                        <ul>
                            <li v-for="(item, index) in pillar.targetGroups" :key="index" style="margin-block-end: 0.5rem">{{ item }}</li>
                        </ul>
                    </div>
                </div>
                <div class="notice" style="margin-block-start: 1.5rem">{{ t('planning_targets_notice') }}</div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div>
                        <SectionHeading :title="t('navigation.programs')" />
                        <div v-if="programs.length" class="list-rows">
                            <Link v-for="(item, index) in programs" :key="index" :href="item.url" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                                <p v-if="item.summary" style="font-size: 0.86rem">{{ item.summary }}</p>
                            </Link>
                        </div>
                        <EmptyState v-else :body="t('ui.no_programs')" />
                    </div>
                    <div>
                        <SectionHeading :title="t('projects')" />
                        <div v-if="projects.length" class="list-rows">
                            <Link v-for="(item, index) in projects" :key="index" :href="item.url" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                                <span class="pill" :class="`pill--${item.status.toLowerCase().replaceAll(' ', '_')}`">{{ item.status }}</span>
                            </Link>
                        </div>
                        <EmptyState v-else :body="t('ui.no_results')" />
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block surface-sand">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div>
                        <SectionHeading :title="t('articles')" :action-label="t('ui.view_all')" :action-url="localPath('news')" />
                        <div v-if="articles.length" class="list-rows">
                            <Link v-for="(item, index) in articles" :key="index" :href="item.url" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                            </Link>
                        </div>
                        <EmptyState v-else :body="t('ui.no_articles')" />
                    </div>
                    <div>
                        <SectionHeading :title="t('documents')" :action-label="t('ui.view_all')" :action-url="localPath('publications')" />
                        <div v-if="publications.length" class="list-rows">
                            <Link v-for="(item, index) in publications" :key="index" :href="item.url" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                            </Link>
                        </div>
                        <EmptyState v-else :body="t('no_documents')" />
                    </div>
                </div>
            </div>
        </section>

        <section v-if="otherPillars.length" class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('ui.related_content')" />
                <div class="pillar-grid">
                    <Link v-for="item in otherPillars" :key="item.code" :href="item.url" class="pillar-tile" v-reveal>
                        <span class="pillar-tile__code">{{ item.code }}</span>
                        <h3>{{ item.title }}</h3>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
