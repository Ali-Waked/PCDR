<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    pillar: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<template>
    <article class="pillar-tile" v-reveal>
        <span class="pillar-tile__code">{{ pillar.code }}</span>
        <h3>{{ pillar.shortTitle || pillar.title }}</h3>
        <p v-if="pillar.description">{{ pillar.description }}</p>
        <ul v-if="pillar.objectives?.length" style="padding-inline-start: 1.1rem; color: var(--muted); font-size: 0.86rem">
            <li v-for="(objective, index) in pillar.objectives.slice(0, 2)" :key="index">{{ objective }}</li>
        </ul>
        <Link :href="pillar.url" class="text-link">{{ t('ui.read_more') }}</Link>
    </article>
</template>
