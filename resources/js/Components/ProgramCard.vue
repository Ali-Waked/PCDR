<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    program: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<template>
    <article class="content-card" v-reveal>
        <p v-if="program.pillar" class="small-caps">{{ program.pillar }}</p>
        <h3 style="margin-block: 0.45rem">{{ program.title }}</h3>
        <p v-if="program.summary">{{ program.summary }}</p>
        <div v-if="program.status" style="margin-block-start: 0.75rem">
            <span class="pill" :class="`pill--${program.status}`">{{ program.status }}</span>
        </div>
        <Link :href="program.url" class="text-link">{{ t('ui.read_more') }}</Link>
    </article>
</template>
