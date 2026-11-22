<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../../Components/Public/PublicCta.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import PublicationRow from '../../../Components/PublicationRow.vue'
import Pagination from '../../../Components/Pagination.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    publications: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ category: '', search: '' }) },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
const category = ref(props.filters.category || '')
const search = ref(props.filters.search || '')

function submit() {
    router.get(localPath('publications'), {
        category: category.value || undefined,
        q: search.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
    <PublicLayout :meta="meta" page-class="publications-page">
        <PublicPageHero
            :title="t('nav.publications')"
            :lead="t('transparency_intro')"
            :breadcrumbs="[{ label: t('nav.publications') }]"
            accent="sand"
            motif="book"
        />

        <section class="ps-section">
            <div class="ps-container">
                <SectionHeading :title="t('documents')" :body="t('planning_targets_notice')" />
                <form class="ps-toolbar" @submit.prevent="submit">
                    <label class="field">
                        <span>{{ t('ui.category') }}</span>
                        <select v-model="category" class="select">
                            <option value="">{{ t('ui.all') }}</option>
                            <option v-for="item in categories" :key="item.key" :value="item.key">{{ item.label }}</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ t('ui.search') }}</span>
                        <input v-model="search" class="input" type="search" :placeholder="t('ui.search')">
                    </label>
                    <button class="ps-btn ps-btn-primary" type="submit">{{ t('ui.filter') }}</button>
                </form>

                <div v-if="publications.length" class="ps-rows">
                    <PublicationRow v-for="publication in publications" :key="publication.id" :publication="publication" />
                </div>
                <EmptyState v-else :body="t('no_documents')" />
                <Pagination :pagination="pagination" />
            </div>
        </section>

        <PublicCta
            :title="t('nav.transparency')"
            :lede="t('transparency_intro')"
            :actions="[{ label: t('nav.transparency'), url: localPath('transparency'), primary: true }]"
        />
    </PublicLayout>
</template>
