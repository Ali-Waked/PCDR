<script setup>
import CanonicalPagination from '../Pagination.vue'

defineProps({
    links: { type: Array, default: () => [] },
    from: { type: [Number, String], default: null },
    to: { type: [Number, String], default: null },
    total: { type: [Number, String], default: null },
    summary: { type: String, default: '' },
})
</script>

<template>
    <CanonicalPagination
        :links="links"
        :from="from"
        :to="to"
        :total="total"
        :summary="summary"
    />
</template>
