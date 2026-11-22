<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
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
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.publications') }]" />
                <p class="eyebrow">{{ t('navigation.publications') }}</p>
                <h1 class="display-2">{{ t('navigation.publications') }}</h1>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('documents')" :body="t('planning_targets_notice')" />

                <form class="cms-toolbar" @submit.prevent="submit">
                    <label class="field" style="min-inline-size: 200px">
                        <span>{{ t('ui.category') }}</span>
                        <select v-model="category" class="select">
                            <option value="">{{ t('ui.all') }}</option>
                            <option v-for="item in categories" :key="item.key" :value="item.key">{{ item.label }}</option>
                        </select>
                    </label>
                    <label class="field" style="flex: 1; min-inline-size: 220px">
                        <span>{{ t('ui.search') }}</span>
                        <input v-model="search" class="input" type="search" :placeholder="t('ui.search')">
                    </label>
                    <button class="btn btn-primary" type="submit" style="align-self: end">{{ t('ui.filter') }}</button>
                </form>

                <div v-if="publications.length" class="list-rows">
                    <PublicationRow v-for="publication in publications" :key="publication.id" :publication="publication" />
                </div>
                <EmptyState v-else :body="t('no_documents')" />

                <Pagination :pagination="pagination" />
            </div>
        </section>
    </PublicLayout>
</template>
