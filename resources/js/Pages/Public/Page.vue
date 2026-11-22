<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    page: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, richText } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: page.title }]" />
                <h1 class="display-2">{{ page.title }}</h1>
                <p v-if="page.summary" class="lede">{{ page.summary }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('institution')" />
                <div class="prose-editorial" style="white-space: pre-line; max-inline-size: 72ch">{{ richText(page.body) }}</div>
            </div>
        </section>
    </PublicLayout>
</template>
