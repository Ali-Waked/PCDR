<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    items: { type: Array, default: () => [] },
})
</script>

<template>
    <section v-if="items.length" class="ps-section ps-section--tight ps-surface-sand">
        <div class="ps-container">
            <header class="ps-heading">
                <p v-if="eyebrow" class="ps-eyebrow">{{ eyebrow }}</p>
                <h2>{{ title }}</h2>
            </header>
            <div class="ps-rows">
                <Link
                    v-for="(item, index) in items"
                    :key="item.url || `${item.title}-${index}`"
                    :href="item.url"
                    class="ps-row"
                    v-reveal
                >
                    <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                    <h3>{{ item.title }}</h3>
                    <p v-if="item.summary || item.date">{{ item.summary || item.date }}</p>
                </Link>
            </div>
        </div>
    </section>
</template>
