<script setup>
import { Link } from '@inertiajs/vue3'
import CmsStatusBadge from '../Dashboard/CmsStatusBadge.vue'

defineProps({
    item: { type: Object, required: true },
    basePath: { type: String, required: true },
    labels: { type: Object, default: () => ({}) },
})
</script>

<template>
    <article class="pc-content-card">
        <div class="pc-content-card__head">
            <div>
                <span class="pc-content-card__id pc-directional">#{{ item.id }}</span>
                <Link :href="`${basePath}/${item.id}/edit`" class="pc-content-card__title">
                    {{ item.title || `#${item.id}` }}
                </Link>
            </div>
            <CmsStatusBadge :status="item.status" />
        </div>

        <p v-if="item.summary" class="pc-content-card__summary">{{ item.summary }}</p>

        <div class="pc-content-card__meta">
            <slot name="meta" />
        </div>

        <div class="pc-content-card__actions">
            <slot name="actions" />
        </div>
    </article>
</template>
