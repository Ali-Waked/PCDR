<script setup>
import { Link } from '@inertiajs/vue3'
import Breadcrumbs from '../Breadcrumbs.vue'

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    lede: { type: String, default: '' },
    crumbs: { type: Array, default: () => [] },
    variant: { type: String, default: 'navy' },
    index: { type: String, default: '' },
    badge: { type: String, default: '' },
    actions: { type: Array, default: () => [] },
})
</script>

<template>
    <section class="ps-hero" :class="`ps-hero--${variant}`">
        <div class="ps-container ps-hero__inner">
            <div class="ps-hero__copy">
                <Breadcrumbs v-if="crumbs.length" :items="crumbs" />
                <p v-if="eyebrow" class="ps-eyebrow">{{ eyebrow }}</p>
                <h1 class="ps-hero__title">{{ title }}</h1>
                <p v-if="lede" class="ps-lede">{{ lede }}</p>
                <div v-if="actions.length" class="ps-actions">
                    <Link
                        v-for="action in actions"
                        :key="`${action.label}-${action.url}`"
                        :href="action.url"
                        class="ps-btn"
                        :class="action.primary ? 'ps-btn-primary' : 'ps-btn-ghost'"
                    >
                        {{ action.label }}
                    </Link>
                </div>
                <slot />
            </div>

            <div class="ps-hero__art" aria-hidden="true">
                <span class="ps-hero__ring"></span>
                <span class="ps-hero__orbit"><span></span></span>
                <span v-if="index" class="ps-hero__index">{{ index }}</span>
                <span v-if="badge" class="ps-hero__badge">{{ badge }}</span>
            </div>
        </div>
    </section>
</template>
