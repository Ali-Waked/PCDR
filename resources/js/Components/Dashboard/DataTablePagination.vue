<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: { type: Array, default: () => [] },
    from: { type: [Number, null], default: null },
    to: { type: [Number, null], default: null },
    total: { type: [Number, null], default: null },
    summary: { type: String, default: '' },
})
</script>

<template>
    <div v-if="links.length > 3 || (total !== null && total > 0)" class="pc-pagination">
        <span class="pc-pagination__label">{{ summary }}</span>
        <nav v-if="links.length" class="pc-pagination__links" aria-label="Pagination">
            <template v-for="(link, index) in links" :key="`${link.label}-${index}`">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="pc-page-link"
                    :class="{ 'is-active': link.active, 'is-disabled': link.disabled }"
                    :aria-current="link.active ? 'page' : undefined"
                    v-html="link.label"
                />
                <span v-else class="is-disabled" v-html="link.label" />
            </template>
        </nav>
    </div>
</template>
