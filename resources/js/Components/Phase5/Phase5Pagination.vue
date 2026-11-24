<script setup>
import CanonicalPagination from '../Pagination.vue'

defineProps({
    paginator: { type: Object, default: null },
    pageKey: { type: String, default: 'page' },
})
</script>

<template>
    <CanonicalPagination
        v-if="paginator"
        :paginator="paginator"
        :page-key="pageKey"
    />
</template>
