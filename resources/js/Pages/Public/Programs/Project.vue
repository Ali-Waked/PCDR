<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import SafeRichText from '../../../Components/Public/SafeRichText.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    project: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, localPath, formatDate } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="programs-page">
        <PublicPageHero
            :title="props.project.title"
            :lead="props.project.summary || ''"
            :breadcrumbs="[
                { label: t('nav.programs'), url: localPath('programs') },
                { label: props.project.title },
            ]"
            accent="teal"
            motif="wheat"
        >
            <div class="ps-meta">
                <span v-if="props.project.pillar" class="ps-pill">{{ props.project.pillar }}</span>
                <span v-if="props.project.program" class="ps-pill">{{ props.project.program }}</span>
                <span v-if="props.project.status" class="ps-pill">{{ props.project.status }}</span>
            </div>
        </PublicPageHero>

        <section v-if="props.project.image" class="ps-section ps-section--tight">
            <div class="ps-container">
                <div class="ps-cover" v-reveal>
                    <img :src="props.project.image" :alt="props.project.title">
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container ps-split">
                <article>
                    <SectionHeading :eyebrow="t('projects')" :title="t('ui.cms_description')" />
                    <SafeRichText :html="props.project.descriptionHtml" class="ps-article ps-lede" style="white-space: pre-line" />
                </article>
                <aside class="ps-form-card" v-reveal>
                    <p class="ps-eyebrow">{{ t('cms_basic_data') }}</p>
                    <h2>{{ t('cms_basic_data') }}</h2>
                    <div class="ps-rows">
                        <div v-if="props.project.startDate || props.project.endDate" class="ps-row">
                            <span class="ps-row__num">01</span>
                            <h3>{{ t('start') }} / {{ t('end') }}</h3>
                            <p>{{ formatDate(props.project.startDate) }} — {{ formatDate(props.project.endDate) }}</p>
                        </div>
                        <div v-if="props.project.coverage" class="ps-row">
                            <span class="ps-row__num">02</span>
                            <h3>{{ t('governance') }}</h3>
                            <p>{{ props.project.coverage }}</p>
                        </div>
                    </div>
                    <p class="ps-notice">{{ t('strategic_statement') }}</p>
                </aside>
            </div>
        </section>

        <section v-if="props.project.objectives?.length || props.project.targetGroups?.length" class="ps-section ps-surface-sand">
            <div class="ps-container ps-grid ps-grid--two">
                <div v-if="props.project.objectives?.length">
                    <SectionHeading :title="t('ui.approach')" />
                    <ul class="ps-mini-list"><li v-for="(item, index) in props.project.objectives" :key="index">{{ item }}</li></ul>
                </div>
                <div v-if="props.project.targetGroups?.length">
                    <SectionHeading :title="t('ui.target_groups')" />
                    <ul class="ps-mini-list"><li v-for="(item, index) in props.project.targetGroups" :key="index">{{ item }}</li></ul>
                </div>
            </div>
        </section>

        <PublicCta
            :title="t('ui.back_to_list')"
            :lede="t('programs_intro')"
            :actions="[{ label: t('ui.back_to_list'), url: localPath('programs'), primary: true }]"
        />
    </PublicLayout>
</template>
