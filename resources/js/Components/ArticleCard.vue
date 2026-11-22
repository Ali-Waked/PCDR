<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    article: { type: Object, required: true },
})

const { t, formatDate } = useI18n()
</script>

<template>
    <article class="ps-card" v-reveal>
        <div v-if="article.image" class="ps-cover">
            <img :src="article.image" :alt="article.title" loading="lazy">
        </div>
        <div class="ps-meta">
            <span v-if="article.category" class="ps-pill">{{ article.category }}</span>
            <span v-if="article.publishedAt">{{ formatDate(article.publishedAt) }}</span>
        </div>
        <h3>{{ article.title }}</h3>
        <p v-if="article.excerpt">{{ article.excerpt }}</p>
        <Link :href="article.url" class="ps-text-link">{{ t('ui.read_more') }}</Link>
    </article>
</template>
