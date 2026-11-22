<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../../Components/Breadcrumbs.vue'
import SectionHeading from '../../../Components/SectionHeading.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    publication: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, formatDate, richText, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[
                    { label: t('navigation.publications'), url: localPath('publications') },
                    { label: publication.title },
                ]" />
                <div class="meta-row" style="margin-block-end: 0.8rem">
                    <span v-if="publication.category" class="pill">{{ publication.category }}</span>
                    <span v-if="publication.date">{{ formatDate(publication.date) }}</span>
                    <span v-if="publication.version">v{{ publication.version }}</span>
                    <span v-if="publication.restricted" class="pill pill--restricted">{{ t('document_restricted') }}</span>
                </div>
                <h1 class="display-2">{{ publication.title }}</h1>
                <p v-if="publication.description" class="lede">{{ publication.description }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="cms-card" style="max-inline-size: 800px">
                    <SectionHeading :title="t('documents')" />
                    <p v-if="publication.description" class="prose-editorial" style="white-space: pre-line">{{ richText(publication.description) }}</p>
                    <dl style="display: grid; gap: 0.75rem; margin-block: 1rem">
                        <div v-if="publication.pillar">
                            <dt class="small-caps">{{ t('ui.strategic_direction') }}</dt>
                            <dd style="margin: 0">{{ publication.pillar }}</dd>
                        </div>
                        <div v-if="publication.date">
                            <dt class="small-caps">{{ t('published_on') }}</dt>
                            <dd style="margin: 0">{{ formatDate(publication.date) }}</dd>
                        </div>
                    </dl>
                    <a v-if="publication.downloadUrl" class="btn btn-primary" :href="publication.downloadUrl" download>
                        {{ t('download_document') }}
                    </a>
                    <div v-else-if="publication.restricted" class="notice">
                        {{ t('restricted_notice') }}
                        <Link :href="localPath('contact')" class="text-link">{{ t('navigation.contact') }}</Link>
                    </div>
                    <p v-else class="notice">{{ t('file_not_available') }}</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
