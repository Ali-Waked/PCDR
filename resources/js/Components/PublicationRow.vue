<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    publication: { type: Object, required: true },
})

const { t, formatDate } = useI18n()
</script>

<template>
    <article class="ps-row ps-publications-row" v-reveal>
        <span class="ps-row__num">{{ publication.category ? '•' : '' }}</span>
        <div>
            <div class="ps-meta">
                <span v-if="publication.category" class="ps-pill">{{ publication.category }}</span>
                <span v-if="publication.date">{{ formatDate(publication.date) }}</span>
                <span v-if="publication.version">v{{ publication.version }}</span>
                <span v-if="publication.restricted" class="ps-pill">{{ t('document_restricted') }}</span>
            </div>
            <h3>{{ publication.title }}</h3>
            <p v-if="publication.description">{{ publication.description }}</p>
        </div>
        <div class="ps-actions">
            <Link :href="publication.url" class="ps-btn ps-btn-ghost">{{ t('ui.read_more') }}</Link>
            <a v-if="publication.downloadUrl" :href="publication.downloadUrl" class="ps-btn ps-btn-primary" download>
                {{ t('ui.download') }}
            </a>
        </div>
    </article>
</template>
