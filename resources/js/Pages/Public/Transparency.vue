<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    principles: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    reports: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath, formatDate } = useI18n()
const closingActions = [
    { label: t('nav.publications'), url: localPath('publications'), primary: true },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
]
</script>

<template>
    <PublicLayout :meta="meta" page-class="transparency-page">
        <PublicPageHero
            :title="t('nav.transparency')"
            :lead="t('transparency_intro')"
            :breadcrumbs="[{ label: t('nav.transparency') }]"
            accent="teal"
            motif="scales"
        />

        <section class="ps-section ps-section--tight">
            <div class="ps-container">
                <div class="ps-disclosure">
                    <div v-for="(principle, index) in principles" :key="index" v-reveal>
                        <span class="ps-value-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                        <h2 style="font-size: 1.4rem">{{ principle }}</h2>
                    </div>
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container">
                <div class="ps-grid ps-grid--two">
                    <div>
                        <SectionHeading :title="t('governance_documents')" :body="t('strategic_statement')" />
                        <div v-if="documents.length" class="ps-rows">
                            <a
                                v-for="(item, index) in documents"
                                :key="`${item.url}-${index}`"
                                :href="item.url"
                                class="ps-row"
                                v-reveal
                            >
                                <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.date ? formatDate(item.date) : t('ui.read_more') }}</p>
                            </a>
                        </div>
                        <EmptyState v-else :body="t('no_documents')" />
                    </div>
                    <div>
                        <SectionHeading :title="t('annual_reports')" :body="t('strategic_statement')" />
                        <div v-if="reports.length" class="ps-rows">
                            <a
                                v-for="(item, index) in reports"
                                :key="`${item.url}-${index}`"
                                :href="item.url"
                                class="ps-row"
                                v-reveal
                            >
                                <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.date ? formatDate(item.date) : t('ui.read_more') }}</p>
                            </a>
                        </div>
                        <EmptyState v-else :body="t('no_documents')" />
                    </div>
                </div>
            </div>
        </section>

        <PublicCta :title="t('nav.publications')" :lede="t('transparency_intro')" :actions="closingActions" />
    </PublicLayout>
</template>
