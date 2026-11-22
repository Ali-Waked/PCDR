<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    focus: { type: String, default: 'overview' },
    vision: { type: String, default: '' },
    mission: { type: String, default: '' },
    values: { type: Array, default: () => [] },
    targetGroups: { type: Array, default: () => [] },
    approach: { type: Array, default: () => [] },
    pillars: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()

const foci = [
    { key: 'overview', label: 'at_a_glance' },
    { key: 'vision', label: 'vision' },
    { key: 'mission', label: 'mission' },
    { key: 'values', label: 'values' },
    { key: 'direction', label: 'strategic_direction' },
    { key: 'groups', label: 'target_groups' },
    { key: 'approach', label: 'approach' },
]

const activeFocus = computed(() => foci.find((item) => item.key === props.focus)?.label || 'at_a_glance')
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.about') }]" />
                <p class="eyebrow">{{ t('navigation.about') }}</p>
                <h1 class="display-2">{{ t('about_intro') }}</h1>
            </div>
        </section>

        <section class="section-block surface-soft">
            <div class="container-public">
                <nav class="tabs" :aria-label="t('breadcrumb')">
                    <Link
                        v-for="item in foci"
                        :key="item.key"
                        :href="localPath(`about/${item.key}`)"
                        class="tab-link"
                        :class="{ 'is-active': focus === item.key }"
                        :aria-current="focus === item.key ? 'page' : undefined"
                    >
                        {{ t(item.label) }}
                    </Link>
                </nav>
                <p class="small-caps">{{ t('at_a_glance') }} · {{ t(activeFocus) }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div id="overview" class="cms-form-grid--split" style="display: grid; gap: 2rem">
                    <article v-reveal>
                        <SectionHeading :eyebrow="t('ui.vision')" :title="t('ui.vision')" />
                        <p class="lede">{{ vision }}</p>
                    </article>
                    <div class="notice" v-reveal>{{ t('planning_targets_notice') }}</div>
                </div>
            </div>
        </section>

        <section id="mission" class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :eyebrow="t('navigation.about')" :title="t('ui.mission')" />
                <p class="lede">{{ mission }}</p>
            </div>
        </section>

        <section id="values" class="section-block">
            <div class="container-public">
                <SectionHeading :eyebrow="t('navigation.about')" :title="t('our_values')" />
                <div class="pillar-grid">
                    <div v-for="value in values" :key="value.number" class="content-card" v-reveal>
                        <span class="pillar-tile__code">{{ value.number }}</span>
                        <h3 style="margin-block: 0.5rem 0">{{ value.label }}</h3>
                    </div>
                </div>
            </div>
        </section>

        <section id="direction" class="section-block surface-soft">
            <div class="container-public">
                <SectionHeading
                    :eyebrow="t('ui.strategic_direction')"
                    :title="t('navigation.pillars')"
                    :body="t('strategic_areas_intro')"
                    :action-label="t('ui.view_all')"
                    :action-url="localPath('strategic-areas')"
                />
                <div class="pillar-grid">
                    <Link v-for="pillar in pillars" :key="pillar.code" :href="pillar.url" class="content-card" v-reveal>
                        <span class="pillar-tile__code">{{ pillar.code }}</span>
                        <h3 style="margin-block: 0.5rem">{{ pillar.title }}</h3>
                    </Link>
                </div>
            </div>
        </section>

        <section id="groups" class="section-block">
            <div class="container-public">
                <SectionHeading :eyebrow="t('navigation.about')" :title="t('ui.target_groups')" />
                <div class="pillar-grid">
                    <div v-for="(group, index) in targetGroups" :key="index" class="list-row" v-reveal>
                        <h3 style="font-size: 1rem; margin: 0">{{ group }}</h3>
                    </div>
                </div>
            </div>
        </section>

        <section id="approach" class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :eyebrow="t('navigation.about')" :title="t('ui.approach')" />
                <div class="list-rows">
                    <article v-for="(step, index) in approach" :key="index" class="list-row list-row--split" v-reveal>
                        <h3 style="margin: 0; font-size: 1rem">{{ step }}</h3>
                        <span class="pillar-tile__code">{{ String(index + 1).padStart(2, '0') }}</span>
                    </article>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
