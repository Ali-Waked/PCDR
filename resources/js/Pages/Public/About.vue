<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import AboutSubnav from '../../Components/Public/AboutSubnav.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    focus: { type: String, default: 'overview' },
    focusTitle: { type: String, default: '' },
    focusDescription: { type: String, default: '' },
    vision: { type: String, default: '' },
    mission: { type: String, default: '' },
    values: { type: Array, default: () => [] },
    targetGroups: { type: Array, default: () => [] },
    approach: { type: Array, default: () => [] },
    pillars: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()

const focusKeys = [
    { key: 'overview', id: 'about-overview', label: 'at_a_glance' },
    { key: 'vision', id: 'about-vision', label: 'ui.vision' },
    { key: 'mission', id: 'about-mission', label: 'ui.mission' },
    { key: 'values', id: 'about-values', label: 'ui.values' },
    { key: 'direction', id: 'about-direction', label: 'ui.strategic_direction' },
    { key: 'groups', id: 'about-groups', label: 'ui.target_groups' },
    { key: 'approach', id: 'about-approach', label: 'ui.approach' },
]

const focusItems = computed(() => focusKeys.map((item) => ({
    key: item.key,
    id: item.id,
    label: t(item.label),
})))

const heroActions = computed(() => [
    { label: t('ui.view_all'), url: localPath('strategic-areas'), primary: true },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
])
const closingActions = computed(() => [
    { label: t('nav.involve'), url: localPath('get-involved'), primary: true },
    { label: t('nav.programs'), url: localPath('programs'), primary: false },
])
</script>

<template>
    <PublicLayout :meta="meta" page-class="about-page about-page--tabs">
        <PublicPageHero
            :title="focusTitle || t('about_intro')"
            :lead="focusDescription || t('about_intro')"
            :breadcrumbs="[{ label: t('nav.about') }]"
            accent="teal"
            motif="olive"
            :cta="heroActions"
        />
        <AboutSubnav :items="focusItems" :initial="focus" :label="t('nav.about')" />

        <section id="about-overview" class="ps-section about-section">
            <div class="ps-container ps-split">
                <div class="ps-figure" v-reveal>
                    <p class="ps-eyebrow">{{ t('ui.vision') }}</p>
                    <p class="ps-statement">{{ vision }}</p>
                </div>
                <div class="ps-panel ps-panel--sand" v-reveal>
                    <p class="ps-eyebrow">{{ t('ui.mission') }}</p>
                    <h2>{{ t('about_intro') }}</h2>
                    <p class="ps-lede">{{ mission }}</p>
                    <p class="ps-notice">{{ t('planning_targets_notice') }}</p>
                    <a href="#about-vision" class="ps-text-link">{{ t('ui.read_more') }}</a>
                </div>
            </div>
        </section>

        <section id="about-vision" class="ps-section ps-surface-sand about-section">
            <div class="ps-container">
                <SectionHeading :eyebrow="t('nav.about')" :title="t('ui.vision')" :body="t('about_intro')" />
                <div class="ps-figure" v-reveal>
                    <p class="ps-eyebrow">{{ t('ui.vision') }}</p>
                    <p class="ps-statement">{{ vision }}</p>
                </div>
            </div>
        </section>

        <section id="about-mission" class="ps-section about-section">
            <div class="ps-container ps-split">
                <div class="ps-figure ps-figure--teal" v-reveal>
                    <p class="ps-eyebrow">{{ t('ui.mission') }}</p>
                    <p class="ps-statement">{{ mission }}</p>
                </div>
                <div v-reveal>
                    <SectionHeading :eyebrow="t('ui.approach')" :title="t('ui.approach')" />
                    <div class="ps-timeline">
                        <article v-for="(step, index) in approach" :key="index" class="ps-timeline__item reveal" v-reveal>
                            <span class="ps-timeline__num">{{ String(index + 1).padStart(2, '0') }}</span>
                            <div><h3>{{ step }}</h3></div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section id="about-values" class="ps-section ps-surface-sand about-section">
            <div class="ps-container">
                <SectionHeading :eyebrow="t('nav.about')" :title="t('our_values')" :body="t('about_intro')" />
                <div class="ps-values-ledger" data-stagger>
                    <article
                        v-for="value in values"
                        :key="value.number"
                        class="ps-value-row reveal"
                        :style="{ '--i': Number(value.number) }"
                        v-reveal
                    >
                        <span class="ps-value-row__num">{{ value.number }}</span>
                        <h3>{{ value.label }}</h3>
                    </article>
                </div>
            </div>
        </section>

        <section id="about-direction" class="ps-section about-section">
            <div class="ps-container">
                <SectionHeading
                    :eyebrow="t('ui.strategic_direction')"
                    :title="t('nav.pillars')"
                    :body="t('strategic_areas_intro')"
                />
                <div class="ps-rows" data-stagger>
                    <Link
                        v-for="(pillar, index) in pillars"
                        :key="pillar.code"
                        :href="pillar.url"
                        class="ps-row reveal"
                        :style="{ '--i': index }"
                        v-reveal
                    >
                        <span class="ps-row__num">{{ pillar.code }}</span>
                        <h3>{{ pillar.title }}</h3>
                        <p>{{ pillar.description }}</p>
                    </Link>
                </div>
                <p class="ps-notice">{{ t('planning_targets_notice') }}</p>
            </div>
        </section>

        <section id="about-groups" class="ps-section ps-surface-sand about-section">
            <div class="ps-container">
                <SectionHeading :eyebrow="t('nav.about')" :title="t('ui.target_groups')" :body="t('about_intro')" />
                <div class="ps-grid" data-stagger>
                    <article
                        v-for="(group, index) in targetGroups"
                        :key="index"
                        class="ps-card reveal"
                        :style="{ '--i': index }"
                        v-reveal
                    >
                        <span class="ps-card__code">{{ String(index + 1).padStart(2, '0') }}</span>
                        <h3>{{ group }}</h3>
                    </article>
                </div>
            </div>
        </section>

        <section id="about-approach" class="ps-section about-section">
            <div class="ps-container ps-split">
                <div v-reveal>
                    <SectionHeading :eyebrow="t('nav.about')" :title="t('ui.approach')" :body="t('path_label')" />
                    <div class="ps-timeline">
                        <article v-for="(step, index) in approach" :key="index" class="ps-timeline__item reveal" v-reveal>
                            <span class="ps-timeline__num">{{ String(index + 1).padStart(2, '0') }}</span>
                            <div><h3>{{ step }}</h3></div>
                        </article>
                    </div>
                </div>
                <div class="ps-figure ps-figure--sand" v-reveal>
                    <p class="ps-eyebrow">{{ t('ui.strategic_direction') }}</p>
                    <p class="ps-statement">{{ t('strategic_areas_intro') }}</p>
                    <Link :href="localPath('strategic-areas')" class="ps-text-link">{{ t('ui.view_all') }}</Link>
                </div>
            </div>
        </section>

        <PublicCta :title="t('explore_direction')" :lede="t('about_intro')" :actions="closingActions" />
    </PublicLayout>
</template>
