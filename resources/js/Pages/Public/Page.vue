<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import SafeRichText from '../../Components/Public/SafeRichText.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    page: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, localPath, site } = useI18n()
const closingActions = [
    { label: t('back_home'), url: localPath(), primary: true },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
]
</script>

<template>
    <PublicLayout :meta="meta" page-class="static-page">
        <PublicPageHero
            :title="props.page.title"
            :lead="props.page.summary || ''"
            :breadcrumbs="[{ label: props.page.title }]"
            accent="navy"
            motif="book"
        />

        <section class="ps-section">
            <div class="ps-container">
                <SafeRichText :html="props.page.bodyHtml" class="ps-article ps-lede" style="white-space: pre-line" />
            </div>
        </section>

        <PublicCta :title="t('back_home')" :lede="site.slogan" :actions="closingActions" />
    </PublicLayout>
</template>
