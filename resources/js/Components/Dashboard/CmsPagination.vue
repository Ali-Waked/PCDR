<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    pagination: { type: Object, default: () => ({}) },
    summary: { type: String, default: '' },
})

const { t, page } = useI18n()

const currentPage = computed(() => Math.max(1, Number(props.pagination?.currentPage || 1)))
const lastPage = computed(() => Math.max(1, Number(props.pagination?.lastPage || 1)))
const total = computed(() => Number(props.pagination?.total || 0))

const pageNumbers = computed(() => {
    const start = Math.max(1, currentPage.value - 2)
    const end = Math.min(lastPage.value, start + 4)

    return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function urlFor(number) {
    const target = new URL(page.url || '/', 'https://pcdr.local')
    target.searchParams.set('page', String(number))

    return `${target.pathname}${target.search}`
}
</script>

<template>
    <div v-if="lastPage > 1" class="pc-pagination">
        <span class="pc-pagination__label">
            {{ summary || `${currentPage} / ${lastPage}` }}
        </span>
        <nav class="pc-pagination__links" :aria-label="t('ui.view_all')">
            <Link
                v-if="currentPage > 1"
                :href="urlFor(currentPage - 1)"
                class="pc-page-link"
                preserve-scroll
            >
                {{ t('ui.dashboard_previous') }}
            </Link>
            <Link
                v-for="number in pageNumbers"
                :key="number"
                :href="urlFor(number)"
                class="pc-page-link"
                :class="{ 'is-active': number === currentPage }"
                :aria-current="number === currentPage ? 'page' : undefined"
                preserve-scroll
            >
                {{ number }}
            </Link>
            <Link
                v-if="currentPage < lastPage"
                :href="urlFor(currentPage + 1)"
                class="pc-page-link"
                preserve-scroll
            >
                {{ t('ui.dashboard_next') }}
            </Link>
        </nav>
        <span v-if="total" class="pc-pagination__label">{{ total }}</span>
    </div>
</template>
