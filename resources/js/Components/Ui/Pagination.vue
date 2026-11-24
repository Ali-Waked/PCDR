<script setup>
import CanonicalPagination from '../Pagination.vue'

defineProps({
    data: { type: Object, required: true },
})
</script>

<template>
    <CanonicalPagination :data="data" />
</template>
