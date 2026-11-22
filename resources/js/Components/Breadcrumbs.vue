<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    items: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()
</script>

<template>
    <nav class="ps-breadcrumbs" :aria-label="t('breadcrumb') || 'Breadcrumb'">
        <Link :href="localPath()">{{ t('navigation.home') }}</Link>
        <template v-for="item in items" :key="`${item.label}-${item.url || ''}`">
            <span aria-hidden="true">/</span>
            <Link v-if="item.url" :href="item.url">{{ item.label }}</Link>
            <span v-else aria-current="page">{{ item.label }}</span>
        </template>
    </nav>
</template>
