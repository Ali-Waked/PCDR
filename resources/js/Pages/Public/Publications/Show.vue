<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SafeRichText from '../../../Components/Public/SafeRichText.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    publication: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, localPath, formatDate } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="publications-page">
        <PublicPageHero
            :title="props.publication.title"
            :lead="props.publication.descriptionText || ''"
            :breadcrumbs="[
                { label: t('nav.publications'), url: localPath('publications') },
                { label: props.publication.title },
            ]"
            accent="sand"
            motif="book"
        >
            <div class="ps-meta">
                <span v-if="props.publication.date">{{ formatDate(props.publication.date) }}</span>
                <span v-if="props.publication.version">v{{ props.publication.version }}</span>
                <span v-if="props.publication.restricted" class="ps-pill">{{ t('document_restricted') }}</span>
            </div>
        </PublicPageHero>

        <section class="ps-section">
            <div class="ps-container">
                <div class="ps-form-card" style="max-inline-size: 860px">
                    <p class="ps-eyebrow">{{ t('nav.publications') }}</p>
                    <h2>{{ t('documents') }}</h2>
                    <SafeRichText :html="props.publication.descriptionHtml" class="ps-article ps-lede" style="white-space: pre-line" />
                    <div class="ps-rows">
                        <div v-if="props.publication.pillar" class="ps-row">
                            <span class="ps-row__num">01</span>
                            <h3>{{ t('ui.strategic_direction') }}</h3>
                            <p>{{ props.publication.pillar }}</p>
                        </div>
                        <div v-if="props.publication.date" class="ps-row">
                            <span class="ps-row__num">02</span>
                            <h3>{{ t('published_on') }}</h3>
                            <p>{{ formatDate(props.publication.date) }}</p>
                        </div>
                    </div>

                    <div class="ps-actions">
                        <a v-if="props.publication.downloadUrl" class="ps-btn ps-btn-primary" :href="props.publication.downloadUrl" download>
                            {{ t('download_document') }}
                        </a>
                        <div v-else-if="props.publication.restricted" class="ps-notice">
                            {{ t('restricted_notice') }}
                            <Link :href="localPath('contact')" class="ps-text-link">{{ t('nav.contact') }}</Link>
                        </div>
                        <p v-else class="ps-notice">{{ t('file_not_available') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <PublicCta
            :title="t('ui.back_to_list')"
            :lede="t('transparency_intro')"
            :actions="[
                { label: t('ui.back_to_list'), url: localPath('publications'), primary: true },
                { label: t('nav.transparency'), url: localPath('transparency'), primary: false },
            ]"
        />
    </PublicLayout>
</template>
