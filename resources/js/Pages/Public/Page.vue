<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    page: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, localPath, richText, site } = useI18n()
const closingActions = [
    { label: t('back_home'), url: localPath(), primary: true },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
]
</script>

<template>
    <PublicLayout :meta="meta" page-class="static-page">
        <PublicPageHero
            variant="sand"
            :eyebrow="t('institution')"
            :title="props.page.title"
            :lede="props.page.summary || ''"
            :crumbs="[{ label: props.page.title }]"
            index="∞"
            :badge="t('at_a_glance')"
        />

        <section class="ps-section">
            <div class="ps-container">
                <article class="ps-article ps-lede" style="white-space: pre-line">{{ richText(props.page.body) }}</article>
            </div>
        </section>

        <PublicCta :title="t('back_home')" :lede="site.slogan" :actions="closingActions" />
    </PublicLayout>
</template>
