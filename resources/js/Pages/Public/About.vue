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
    { key: 'overview', label: 'at_a_glance' },
    { key: 'vision', label: 'ui.vision' },
    { key: 'mission', label: 'ui.mission' },
    { key: 'values', label: 'ui.values' },
    { key: 'direction', label: 'ui.strategic_direction' },
    { key: 'groups', label: 'ui.target_groups' },
    { key: 'approach', label: 'ui.approach' },
]

const focusItems = computed(() => focusKeys.map((item) => ({
    key: item.key,
    label: t(item.label),
    url: item.key === 'overview' ? localPath('about') : localPath(`about/${item.key}`),
})))

const focusIndex = computed(() => String(focusKeys.findIndex((item) => item.key === props.focus) + 1).padStart(2, '0'))
const pageClass = computed(() => `about-page ${props.focus}-page`)
const heroActions = computed(() => [
    { label: t('ui.view_all'), url: localPath('strategic-areas'), primary: props.focus === 'direction' },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
])
const closingActions = computed(() => [
    { label: t('nav.involve'), url: localPath('get-involved'), primary: true },
    { label: t('nav.programs'), url: localPath('programs'), primary: false },
])
</script>

<template>
    <PublicLayout :meta="meta" :page-class="pageClass">
        <PublicPageHero
            :eyebrow="t('nav.about')"
            :title="focusTitle || t('about_intro')"
            :lede="focusDescription || t('about_intro')"
            :crumbs="[{ label: t('nav.about') }]"
            :index="focusIndex"
            :badge="t('path_label')"
            :actions="heroActions"
        />
        <AboutSubnav :items="focusItems" :active="focus" :label="t('nav.about')" />

        <template v-if="focus === 'overview'">
            <section class="ps-section">
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
                        <Link :href="localPath('about/vision')" class="ps-text-link">{{ t('ui.read_more') }}</Link>
                    </div>
                </div>
            </section>

            <section class="ps-section ps-surface-sand">
                <div class="ps-container">
                    <SectionHeading
                        :eyebrow="t('ui.strategic_direction')"
                        :title="t('nav.pillars')"
                        :body="t('strategic_areas_intro')"
                        :action-label="t('ui.view_all')"
                        :action-url="localPath('strategic-areas')"
                    />
                    <div class="ps-rows" data-stagger>
                        <Link
                            v-for="(pillar, index) in pillars.slice(0, 5)"
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
                </div>
            </section>
        </template>

        <template v-else-if="focus === 'vision'">
            <section class="ps-section">
                <div class="ps-container">
                    <div class="ps-figure" v-reveal>
                        <p class="ps-eyebrow">{{ t('ui.vision') }}</p>
                        <p class="ps-statement">{{ vision }}</p>
                    </div>
                    <div class="ps-panels" style="margin-block-start: 2rem">
                        <div class="ps-panel ps-panel--navy" v-reveal>
                            <h2>{{ t('path_label') }}</h2>
                            <p class="ps-lede">{{ t('about_intro') }}</p>
                        </div>
                        <div class="ps-panel ps-panel--sand" v-reveal>
                            <h2>{{ t('nav.pillars') }}</h2>
                            <p class="ps-lede">{{ t('strategic_areas_intro') }}</p>
                            <Link :href="localPath('strategic-areas')" class="ps-text-link">{{ t('ui.view_all') }}</Link>
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <template v-else-if="focus === 'mission'">
            <section class="ps-section">
                <div class="ps-container ps-split">
                    <div class="ps-figure ps-figure--teal" v-reveal>
                        <p class="ps-eyebrow">{{ t('ui.mission') }}</p>
                        <p class="ps-statement">{{ mission }}</p>
                    </div>
                    <div v-reveal>
                        <SectionHeading :eyebrow="t('ui.approach')" :title="t('nav.involve')" />
                        <div class="ps-timeline" v-reveal>
                            <article v-for="(step, index) in approach" :key="index" class="ps-timeline__item reveal">
                                <span class="ps-timeline__num">{{ String(index + 1).padStart(2, '0') }}</span>
                                <div><h3>{{ step }}</h3></div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <template v-else-if="focus === 'values'">
            <section class="ps-section">
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
                            <p>{{ t('planning_targets_notice') }}</p>
                        </article>
                    </div>
                </div>
            </section>
        </template>

        <template v-else-if="focus === 'direction'">
            <section class="ps-section">
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
        </template>

        <template v-else-if="focus === 'groups'">
            <section class="ps-section">
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
        </template>

        <template v-else>
            <section class="ps-section">
                <div class="ps-container ps-split">
                    <div v-reveal>
                        <SectionHeading :eyebrow="t('nav.about')" :title="t('ui.approach')" :body="t('path_label')" />
                        <div class="ps-timeline is-visible" v-reveal>
                            <article v-for="(step, index) in approach" :key="index" class="ps-timeline__item">
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
        </template>

        <PublicCta :title="t('explore_direction')" :lede="t('about_intro')" :actions="closingActions" />
    </PublicLayout>
</template>
