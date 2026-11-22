<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    program: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath, formatDate, richText } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="programs-page">
        <PublicPageHero
            :eyebrow="props.program.pillar || t('nav.programs')"
            :title="props.program.title"
            :lede="props.program.summary || ''"
            :crumbs="[
                { label: t('nav.programs'), url: localPath('programs') },
                { label: props.program.title },
            ]"
            index="03"
            :badge="t('ui.cms_programs')"
        />

        <section v-if="props.program.image" class="ps-section ps-section--tight">
            <div class="ps-container">
                <div class="ps-cover" v-reveal>
                    <img :src="props.program.image" :alt="props.program.title">
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container ps-split">
                <article>
                    <SectionHeading :eyebrow="t('nav.programs')" :title="t('ui.cms_description')" />
                    <div class="ps-article ps-lede" style="white-space: pre-line">{{ richText(props.program.description) }}</div>
                </article>
                <aside class="ps-form-card" v-reveal>
                    <p class="ps-eyebrow">{{ t('cms_basic_data') }}</p>
                    <h2>{{ t('cms_basic_data') }}</h2>
                    <div class="ps-rows">
                        <div v-if="props.program.startDate || props.program.endDate" class="ps-row">
                            <span class="ps-row__num">01</span>
                            <h3>{{ t('start') }} / {{ t('end') }}</h3>
                            <p>{{ formatDate(props.program.startDate) }} — {{ formatDate(props.program.endDate) }}</p>
                        </div>
                        <div v-if="props.program.coverage" class="ps-row">
                            <span class="ps-row__num">02</span>
                            <h3>{{ t('governance') }}</h3>
                            <p>{{ props.program.coverage }}</p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section v-if="props.program.objectives?.length || props.program.targetGroups?.length" class="ps-section ps-surface-sand">
            <div class="ps-container ps-grid ps-grid--two">
                <div v-if="props.program.objectives?.length">
                    <SectionHeading :title="t('ui.approach')" />
                    <ul class="ps-mini-list"><li v-for="(item, index) in props.program.objectives" :key="index">{{ item }}</li></ul>
                </div>
                <div v-if="props.program.targetGroups?.length">
                    <SectionHeading :title="t('ui.target_groups')" />
                    <ul class="ps-mini-list"><li v-for="(item, index) in props.program.targetGroups" :key="index">{{ item }}</li></ul>
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container">
                <SectionHeading :title="t('projects')" />
                <div v-if="projects.length" class="ps-rows">
                    <Link v-for="(project, index) in projects" :key="index" :href="project.url" class="ps-row" v-reveal>
                        <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                        <div>
                            <h3>{{ project.title }}</h3>
                            <p>{{ project.summary }}</p>
                        </div>
                        <span class="ps-pill">{{ project.status }}</span>
                    </Link>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>

        <PublicCta
            :title="t('ui.back_to_list')"
            :lede="t('programs_intro')"
            :actions="[{ label: t('ui.back_to_list'), url: localPath('programs'), primary: true }]"
        />
    </PublicLayout>
</template>
