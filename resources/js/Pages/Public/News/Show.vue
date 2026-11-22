<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    article: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    shareUrl: { type: String, default: '' },
    meta: { type: Object, default: null },
})

const { t, formatDate, richText, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <article>
            <section class="page-hero">
                <div class="container-public">
                    <Breadcrumbs :items="[
                        { label: t('navigation.news'), url: localPath('news') },
                        { label: article.title },
                    ]" />
                    <div class="meta-row" style="margin-block-end: 0.8rem">
                        <span v-if="article.category" class="pill">{{ article.category }}</span>
                        <span v-if="article.publishedAt">{{ t('published_on') }} {{ formatDate(article.publishedAt) }}</span>
                        <span v-if="article.author">{{ article.author }}</span>
                    </div>
                    <h1 class="display-2">{{ article.title }}</h1>
                    <p v-if="article.excerpt" class="lede">{{ article.excerpt }}</p>
                </div>
            </section>

            <section class="section-block">
                <div class="container-public">
                    <div v-if="article.image" class="media-cover" style="max-inline-size: 900px; margin-block-end: 2rem">
                        <img :src="article.image" :alt="article.imageAlt || article.title">
                    </div>
                    <div class="prose-editorial" style="white-space: pre-line; max-inline-size: 72ch">{{ richText(article.body) }}</div>

                    <div v-if="article.tags?.length" class="meta-row" style="margin-block-start: 2rem">
                        <span v-for="(tag, index) in article.tags" :key="index" class="pill">{{ tag }}</span>
                    </div>

                    <div v-if="shareUrl" class="meta-row" style="margin-block-start: 1.5rem">
                        <span class="small-caps">{{ t('ui.share') }}</span>
                        <a :href="`mailto:?subject=${encodeURIComponent(article.title)}&body=${encodeURIComponent(shareUrl)}`" class="text-link">{{ t('ui.email') }}</a>
                        <a :href="shareUrl" class="text-link" target="_blank" rel="noopener noreferrer">{{ t('open_original') }}</a>
                    </div>
                </div>
            </section>
        </article>

        <section v-if="related.length" class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :title="t('ui.related_content')" />
                <div class="list-rows">
                    <Link v-for="item in related" :key="item.url" :href="item.url" class="list-row list-row--split" v-reveal>
                        <h3 style="margin: 0; font-size: 1rem">{{ item.title }}</h3>
                        <span v-if="item.publishedAt" class="small-caps">{{ formatDate(item.publishedAt) }}</span>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
