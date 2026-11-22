<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import ArticleCard from '../../../Components/ArticleCard.vue'
import Pagination from '../../../Components/Pagination.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    articles: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ category: '', search: '' }) },
    featured: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
const category = ref(props.filters.category || '')
const search = ref(props.filters.search || '')

function submit() {
    router.get(localPath('news'), {
        category: category.value || undefined,
        q: search.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.news') }]" />
                <p class="eyebrow">{{ t('navigation.news') }}</p>
                <h1 class="display-2">{{ t('navigation.news') }}</h1>
                <p class="lede">{{ t('latest_updates') }}</p>
            </div>
        </section>

        <section v-if="featured.length" class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :title="t('latest_updates')" />
                <div class="pillar-grid">
                    <ArticleCard v-for="article in featured" :key="article.url" :article="article" />
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <form class="cms-toolbar" @submit.prevent="submit">
                    <label class="field" style="min-inline-size: 200px">
                        <span>{{ t('ui.category') }}</span>
                        <select v-model="category" class="select">
                            <option value="">{{ t('ui.all') }}</option>
                            <option v-for="item in categories" :key="item.slug" :value="item.slug">{{ item.name }}</option>
                        </select>
                    </label>
                    <label class="field" style="flex: 1; min-inline-size: 220px">
                        <span>{{ t('ui.search') }}</span>
                        <input v-model="search" class="input" type="search" :placeholder="t('ui.search')">
                    </label>
                    <button class="btn btn-primary" type="submit" style="align-self: end">{{ t('ui.filter') }}</button>
                </form>

                <div v-if="articles.length" class="pillar-grid">
                    <ArticleCard v-for="article in articles" :key="article.id" :article="article" />
                </div>
                <EmptyState v-else :body="t('ui.no_articles')" />

                <Pagination :pagination="pagination" />
            </div>
        </section>
    </PublicLayout>
</template>
