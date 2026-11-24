<script setup>
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import RelatedContent from '../../../Components/Public/RelatedContent.vue'
import SafeRichText from '../../../Components/Public/SafeRichText.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    article: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    shareUrl: { type: String, default: '' },
    meta: { type: Object, default: null },
})

const { t, localPath, formatDate } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="news-page">
        <PublicPageHero
            :title="props.article.title"
            :lead="props.article.excerpt || ''"
            :breadcrumbs="[
                { label: t('nav.news'), url: localPath('news') },
                { label: props.article.title },
            ]"
            accent="navy"
            motif="drop"
        >
            <div class="ps-meta">
                <span v-if="props.article.category" class="ps-pill">{{ props.article.category }}</span>
                <span v-if="props.article.publishedAt">{{ t('published_on') }} {{ formatDate(props.article.publishedAt) }}</span>
                <span v-if="props.article.author">{{ props.article.author }}</span>
            </div>
        </PublicPageHero>

        <section class="ps-section">
            <div class="ps-container">
                <SafeRichText :html="props.article.bodyHtml" class="ps-article ps-lede" style="white-space: pre-line" />

                <div v-if="props.article.image" class="ps-cover">
                    <img :src="props.article.image" :alt="props.article.imageAlt || props.article.title">
                </div>

                <div v-if="props.article.tags?.length" class="ps-actions">
                    <span v-for="(tag, index) in props.article.tags" :key="index" class="ps-pill">{{ tag }}</span>
                </div>

                <div v-if="shareUrl" class="ps-meta" style="margin-block-start: 2rem">
                    <span>{{ t('ui.share') }}</span>
                    <a :href="`mailto:?subject=${encodeURIComponent(props.article.title)}&body=${encodeURIComponent(shareUrl)}`" class="ps-text-link">{{ t('ui.email') }}</a>
                    <a :href="shareUrl" class="ps-text-link" target="_blank" rel="noopener noreferrer">{{ t('open_original') }}</a>
                </div>
            </div>
        </section>

        <RelatedContent :title="t('ui.related_content')" :items="props.related" />

        <PublicCta
            :title="t('nav.news')"
            :lede="t('latest_updates')"
            :actions="[
                { label: t('ui.back_to_list'), url: localPath('news'), primary: true },
                { label: t('nav.publications'), url: localPath('publications'), primary: false },
            ]"
        />
    </PublicLayout>
</template>
