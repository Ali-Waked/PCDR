<script setup>
import CanonicalPagination from '../Pagination.vue'

defineProps({
    pagination: { type: Object, default: () => ({}) },
    summary: { type: String, default: '' },
    pageKey: { type: String, default: 'page' },
})
</script>

<template>
    <CanonicalPagination
        :pagination="pagination"
        :summary="summary"
        :page-key="pageKey"
    />
</template>
