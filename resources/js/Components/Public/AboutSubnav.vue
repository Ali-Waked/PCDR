<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    items: { type: Array, required: true },
    active: { type: String, default: 'overview' },
    label: { type: String, default: 'About sections' },
})
</script>

<template>
    <nav class="ps-subnav" :aria-label="label">
        <div class="ps-container ps-subnav__inner">
            <Link
                v-for="(item, index) in items"
                :key="item.key"
                :href="item.url"
                class="ps-subnav__link"
                :class="{ 'is-active': active === item.key }"
                :aria-current="active === item.key ? 'page' : undefined"
            >
                <small>{{ String(index + 1).padStart(2, '0') }}</small>
                {{ item.label }}
            </Link>
        </div>
    </nav>
</template>
