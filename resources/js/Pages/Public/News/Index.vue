<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import ArticleCard from '../../../Components/ArticleCard.vue'
import Pagination from '../../../Components/Pagination.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import SelectionControl from '../../../Components/Selection/SelectionControl.vue'
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
const categoryOptions = computed(() => props.categories.map((item) => ({
    value: item.slug,
    label: item.name,
})))
const search = ref(props.filters.search || '')

function submit() {
    router.get(localPath('news'), {
        category: category.value || undefined,
        q: search.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
    <PublicLayout :meta="meta" page-class="news-page">
        <PublicPageHero
            :title="t('nav.news')"
            :lead="t('latest_updates')"
            :breadcrumbs="[{ label: t('nav.news') }]"
            accent="navy"
            motif="drop"
        />

        <section v-if="featured.length" class="ps-section ps-surface-sand">
            <div class="ps-container">
                <SectionHeading :title="t('latest_updates')" />
                <div class="ps-feature">
                    <ArticleCard v-for="article in featured" :key="article.url" :article="article" />
                </div>
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container">
                <form class="ps-toolbar" @submit.prevent="submit">
                    <div class="field">
                        <SelectionControl
                            v-model="category"
                            :label="t('ui.category')"
                            :options="categoryOptions"
                            :placeholder="t('ui.all')"
                            clearable
                            :searchable="categoryOptions.length > 6"
                            variant="public"
                        />
                    </div>
                    <label class="field">
                        <span>{{ t('ui.search') }}</span>
                        <input v-model="search" class="input" type="search" :placeholder="t('ui.search')">
                    </label>
                    <button class="ps-btn ps-btn-primary" type="submit">{{ t('ui.filter') }}</button>
                </form>

                <div v-if="articles.length" class="ps-grid" data-stagger>
                    <ArticleCard v-for="article in articles" :key="article.id" :article="article" />
                </div>
                <EmptyState v-else :body="t('ui.no_articles')" />
                <Pagination :pagination="pagination" />
            </div>
        </section>

        <PublicCta
            :title="t('nav.publications')"
            :lede="t('transparency_intro')"
            :actions="[{ label: t('nav.publications'), url: localPath('publications'), primary: true }]"
        />
    </PublicLayout>
</template>
