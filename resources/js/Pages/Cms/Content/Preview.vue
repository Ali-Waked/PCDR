<script setup>
import { Link } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import StatusPill from '../../../Components/StatusPill.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    resource: { type: String, required: true },
    title: { type: String, required: true },
    summary: { type: String, default: '' },
    body: { type: String, default: '' },
    status: { type: String, required: true },
    previewLocale: { type: String, required: true },
})

const { t, localPath } = useI18n()
</script>

<template>
    <CmsLayout :title="t('ui.cms_preview_title')" :subtitle="t('ui.cms_preview_subtitle')">
        <div class="alert alert--info">
            {{ t('ui.cms_preview_notice') }}
        </div>

        <article class="pcdr-card cms-preview" :lang="props.previewLocale" :dir="props.previewLocale === 'ar' ? 'rtl' : 'ltr'">
            <div class="cms-preview__meta">
                <StatusPill :status="props.status" />
                <span class="small-caps">{{ props.previewLocale.toUpperCase() }}</span>
            </div>
            <h1>{{ props.title }}</h1>
            <p v-if="props.summary" class="cms-preview__summary">{{ props.summary }}</p>
            <!-- Server-sanitized by App\Support\RichText before it reaches the client. -->
            <div class="ps-article" v-html="props.body" />
            <Link :href="localPath(`cms/${props.resource}`)" class="btn btn-secondary btn-small">
                {{ t('back') }}
            </Link>
        </article>
    </CmsLayout>
</template>

<style scoped>
.cms-preview {
    max-width: 58rem;
    margin-inline: auto;
}

.cms-preview__meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-block-end: 1rem;
}

.cms-preview__summary {
    color: var(--pcdr-muted, #54617a);
    font-size: 1.05rem;
}

.cms-preview > .btn {
    margin-block-start: 1.5rem;
}
</style>
