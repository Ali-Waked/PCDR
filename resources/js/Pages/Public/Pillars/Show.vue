<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import RelatedContent from '../../../Components/Public/RelatedContent.vue'
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
    <PublicLayout :meta="meta" page-class="pillars-page">
        <PublicPageHero
            :title="props.pillar.title"
            :lead="props.pillar.description || ''"
            :breadcrumbs="[
                { label: t('nav.pillars'), url: localPath('strategic-areas') },
                { label: props.pillar.shortTitle || props.pillar.title },
            ]"
            accent="sand"
            motif="sun"
        />

        <section v-if="props.pillar.image" class="ps-section ps-section--tight">
            <div class="ps-container">
                <div class="ps-cover" v-reveal>
                    <img :src="props.pillar.image" :alt="props.pillar.imageAlt || props.pillar.title">
                </div>
            </div>
        </section>

        <section v-if="props.pillar.objectives?.length || props.pillar.targetGroups?.length" class="ps-section ps-surface-sand">
            <div class="ps-container ps-panels">
                <div v-if="props.pillar.objectives?.length" class="ps-panel ps-panel--navy">
                    <p class="ps-eyebrow">{{ t('ui.approach') }}</p>
                    <h2>{{ t('ui.approach') }}</h2>
                    <ul class="ps-mini-list">
                        <li v-for="(item, index) in props.pillar.objectives" :key="index">{{ item }}</li>
                    </ul>
                </div>
                <div v-if="props.pillar.targetGroups?.length" class="ps-panel ps-panel--sand">
                    <p class="ps-eyebrow">{{ t('ui.target_groups') }}</p>
                    <h2>{{ t('ui.target_groups') }}</h2>
                    <ul class="ps-mini-list">
                        <li v-for="(item, index) in props.pillar.targetGroups" :key="index">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container ps-grid ps-grid--two">
                <div>
                    <SectionHeading
                        :title="t('nav.programs')"
                        :action-label="t('ui.view_all')"
                        :action-url="localPath('programs')"
                    />
                    <div v-if="programs.length" class="ps-rows">
                        <Link v-for="(item, index) in programs" :key="index" :href="item.url" class="ps-row" v-reveal>
                            <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.summary }}</p>
                        </Link>
                    </div>
                    <EmptyState v-else :body="t('ui.no_programs')" />
                </div>
                <div>
                    <SectionHeading :title="t('projects')" />
                    <div v-if="projects.length" class="ps-rows">
                        <Link v-for="(item, index) in projects" :key="index" :href="item.url" class="ps-row" v-reveal>
                            <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.status }}</p>
                        </Link>
                    </div>
                    <EmptyState v-else :body="t('ui.no_results')" />
                </div>
            </div>
        </section>

        <RelatedContent :title="t('articles')" :items="articles" />
        <RelatedContent :title="t('documents')" :items="publications" />
        <RelatedContent :title="t('ui.related_content')" :items="otherPillars" />

        <PublicCta
            :title="t('nav.involve')"
            :lede="t('strategic_statement')"
            :actions="[{ label: t('nav.involve'), url: localPath('get-involved'), primary: true }]"
        />
    </PublicLayout>
</template>
