<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import SiteHeader from '../Components/Public/SiteHeader.vue'
import SiteFooter from '../Components/Public/SiteFooter.vue'
import FlashMessages from '../Components/FlashMessages.vue'
import { useI18n } from '../composables/useI18n'
import '../../css/public-site.css'

const props = defineProps({
    meta: { type: Object, default: null },
    pageClass: { type: String, default: '' },
})

const root = ref(null)
const { t, site, locale, direction } = useI18n()

const meta = computed(() => props.meta || {})
const fallbacks = computed(() => ({
    title: site.value.name || 'PCDR',
    description: site.value.slogan || '',
    canonical: '',
    locale: 'ar',
    alternates: {},
    og: {},
    twitter: {},
    robots: 'index,follow',
}))

const resolved = computed(() => ({ ...fallbacks.value, ...meta.value }))

onMounted(() => {
    root.value?.classList.add('is-ready')
})
</script>

<template>
    <div
        ref="root"
        class="public-shell ps-animate"
        :class="pageClass"
        :lang="locale"
        :dir="direction"
    >
        <Head>
            <title>{{ resolved.title }}</title>
            <meta name="description" :content="resolved.description">
            <link v-if="resolved.canonical" rel="canonical" :href="resolved.canonical">
            <link
                v-for="(url, code) in resolved.alternates"
                :key="code"
                rel="alternate"
                :hreflang="code"
                :href="url"
            >
            <meta property="og:type" :content="resolved.og?.type || 'website'">
            <meta property="og:title" :content="resolved.og?.title || resolved.title">
            <meta property="og:description" :content="resolved.og?.description || resolved.description">
            <meta property="og:url" :content="resolved.og?.url || resolved.canonical">
            <meta property="og:locale" :content="resolved.og?.locale || resolved.locale">
            <meta property="og:site_name" :content="resolved.og?.site_name || site.shortName">
            <meta v-if="resolved.og?.image" property="og:image" :content="resolved.og.image">
            <meta name="twitter:card" :content="resolved.twitter?.card || 'summary'">
            <meta name="twitter:title" :content="resolved.twitter?.title || resolved.title">
            <meta name="twitter:description" :content="resolved.twitter?.description || resolved.description">
            <meta v-if="resolved.twitter?.image" name="twitter:image" :content="resolved.twitter.image">
            <meta name="robots" :content="resolved.robots || 'index,follow'">
        </Head>

        <a class="skip-link" href="#main">{{ t('ui.skip_to_content') }}</a>
        <SiteHeader />
        <FlashMessages />

        <main id="main" tabindex="-1" style="outline: none">
            <slot />
        </main>

        <SiteFooter />
    </div>
</template>
