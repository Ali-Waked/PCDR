<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    program: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<template>
    <article class="ps-card" v-reveal>
        <span v-if="program.pillar" class="ps-card__code">{{ program.pillar }}</span>
        <h3>{{ program.title }}</h3>
        <p v-if="program.summary">{{ program.summary }}</p>
        <span v-if="program.status" class="ps-pill">{{ program.status }}</span>
        <Link :href="program.url" class="ps-text-link">{{ t('ui.read_more') }}</Link>
    </article>
</template>
