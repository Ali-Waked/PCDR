<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'

defineProps({
    publication: { type: Object, required: true },
})

const { t, formatDate } = useI18n()
</script>

<template>
    <article class="list-row list-row--split" v-reveal>
        <div>
            <div class="meta-row">
                <span v-if="publication.category" class="pill">{{ publication.category }}</span>
                <span v-if="publication.date">{{ formatDate(publication.date) }}</span>
                <span v-if="publication.version">v{{ publication.version }}</span>
                <span v-if="publication.restricted" class="pill pill--restricted">{{ t('document_restricted') }}</span>
            </div>
            <h3 style="margin-block: 0.5rem 0.3rem">{{ publication.title }}</h3>
            <p v-if="publication.description" style="font-size: 0.9rem">{{ publication.description }}</p>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem">
            <Link :href="publication.url" class="btn btn-secondary btn-small">{{ t('ui.read_more') }}</Link>
            <a v-if="publication.downloadUrl" :href="publication.downloadUrl" class="btn btn-primary btn-small" download>
                {{ t('ui.download') }}
            </a>
        </div>
    </article>
</template>
