<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    data: { type: Object, required: true },
})

function label(text) {
    const doc = new DOMParser().parseFromString(text ?? '', 'text/html')
    return doc.body.textContent ?? ''
}
</script>

<template>
    <nav v-if="data.links?.length > 1" class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
        <template v-for="(link, index) in data.links" :key="index">
            <span v-if="!link.url" class="rounded-md px-3 py-2 text-sm text-muted" aria-disabled="true">{{ label(link.label) }}</span>
            <Link
                v-else
                :href="link.url"
                class="focus-ring rounded-md px-3 py-2 text-sm"
                :class="link.active ? 'bg-navy-700 text-white' : 'border border-subtle bg-surface text-ink hover:bg-surface-alt'"
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ label(link.label) }}
            </Link>
        </template>
    </nav>
</template>
