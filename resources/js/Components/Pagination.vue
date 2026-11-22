<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

const props = defineProps({
    pagination: { type: Object, required: true },
})

const { t, page } = useI18n()

const pages = computed(() => {
    if (props.pagination.links) {
        return Object.entries(props.pagination.links).map(([number, url]) => ({ number: Number(number), url }))
    }

    const total = Number(props.pagination.lastPage || 1)
    const start = Math.max(1, Number(props.pagination.currentPage || 1) - 2)
    const end = Math.min(total, start + 4)

    return Array.from({ length: end - start + 1 }, (_, index) => start + index).map((number) => {
        const url = new URL(page.url || window.location.href, window.location.origin)
        url.searchParams.set('page', String(number))
        return { number, url: `${url.pathname}${url.search}` }
    })
})
</script>

<template>
    <nav v-if="pagination.lastPage > 1" class="pagination" :aria-label="t('ui.view_all')">
        <Link
            v-for="entry in pages"
            :key="entry.number"
            :href="entry.url"
            preserve-scroll
            class="page-link"
            :class="{ 'is-current': entry.number === pagination.currentPage }"
            :aria-current="entry.number === pagination.currentPage ? 'page' : undefined"
        >
            {{ entry.number }}
        </Link>
    </nav>
</template>
