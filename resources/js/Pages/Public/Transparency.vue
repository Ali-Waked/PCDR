<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    principles: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    reports: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, formatDate } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.transparency') }]" />
                <p class="eyebrow">{{ t('governance') }}</p>
                <h1 class="display-2">{{ t('navigation.transparency') }}</h1>
                <p class="lede">{{ t('transparency_intro') }}</p>
            </div>
        </section>

        <section class="section-block surface-soft">
            <div class="container-public">
                <SectionHeading :title="t('our_values')" />
                <div class="pillar-grid">
                    <div v-for="(principle, index) in principles" :key="index" class="content-card" v-reveal>
                        <span class="pillar-tile__code">{{ String(index + 1).padStart(2, '0') }}</span>
                        <h3 style="margin-block: 0.5rem 0">{{ principle }}</h3>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="form-grid form-grid--two">
                    <div>
                        <SectionHeading :title="t('governance_documents')" />
                        <div v-if="documents.length" class="list-rows">
                            <article v-for="(item, index) in documents" :key="index" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                                <span class="small-caps" v-if="item.date">{{ formatDate(item.date) }}</span>
                                <a :href="item.url" class="text-link">{{ t('ui.read_more') }}</a>
                            </article>
                        </div>
                        <EmptyState v-else :body="t('no_documents')" />
                    </div>
                    <div>
                        <SectionHeading :title="t('annual_reports')" />
                        <div v-if="reports.length" class="list-rows">
                            <article v-for="(item, index) in reports" :key="index" class="list-row" v-reveal>
                                <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                                <span class="small-caps" v-if="item.date">{{ formatDate(item.date) }}</span>
                                <a :href="item.url" class="text-link">{{ t('ui.read_more') }}</a>
                            </article>
                        </div>
                        <EmptyState v-else :body="t('no_documents')" />
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
