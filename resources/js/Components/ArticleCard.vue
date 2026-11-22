<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    article: { type: Object, required: true },
})

const { t, formatDate } = useI18n()
</script>

<template>
    <article class="article-card" v-reveal>
        <div v-if="article.image" class="article-card__media">
            <img :src="article.image" :alt="article.title" loading="lazy">
        </div>
        <div class="meta-row" style="margin-block-start: 1rem">
            <span v-if="article.category" class="pill">{{ article.category }}</span>
            <span v-if="article.publishedAt">{{ formatDate(article.publishedAt) }}</span>
        </div>
        <h3 style="margin-block: 0.6rem 0.4rem">{{ article.title }}</h3>
        <p v-if="article.excerpt">{{ article.excerpt }}</p>
        <Link :href="article.url" class="text-link">{{ t('ui.read_more') }}</Link>
    </article>
</template>
