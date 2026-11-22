<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Breadcrumbs from '../Breadcrumbs.vue'
import HeroMotif from './HeroMotif.vue'

const props = defineProps({
    title: { type: String, required: true },
    lead: { type: String, default: '' },
    lede: { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
    crumbs: { type: Array, default: () => [] },
    eyebrow: { type: String, default: '' },
    accent: { type: String, default: '' },
    motif: { type: String, default: '' },
    image: { type: Object, default: null },
    cta: { type: Array, default: () => [] },
    actions: { type: Array, default: () => [] },
    // legacy props kept for compatibility
    variant: { type: String, default: 'sand' },
    index: { type: String, default: '' },
    badge: { type: String, default: '' },
})

const resolvedLead = computed(() => props.lead || props.lede || '')
const resolvedCrumbs = computed(() => (props.breadcrumbs?.length ? props.breadcrumbs : props.crumbs) || [])
const resolvedCta = computed(() => (props.cta?.length ? props.cta : props.actions) || [])
const accentColor = computed(() => ({ teal: '#71BCB7', sand: '#DAC3A3', navy: '#3E5172' }[props.accent] || '#71BCB7'))
const motifKey = computed(() => props.motif || 'olive')
const titleWords = computed(() => String(props.title || '').trim().split(/\s+/).filter(Boolean))
</script>

<template>
    <section class="ps-hero ps-hero--editorial">
        <div class="ps-container ps-hero__grid">
            <div class="ps-hero__visual" aria-hidden="true">
                <HeroMotif :name="motifKey" :image="image" />
            </div>

            <div class="ps-hero__copy">
                <div class="ps-hero__crumbs hm-intro" style="--hm-delay:0s">
                    <Breadcrumbs v-if="resolvedCrumbs.length" :items="resolvedCrumbs" />
                </div>
                <div class="ps-hero__accent hm-intro hm-accent-bar" :style="{ '--accent': accentColor, '--hm-delay': '.10s' }" aria-hidden="true"></div>
                <h1 class="ps-hero__title">
                    <span
                        v-for="(word, i) in titleWords"
                        :key="`${i}-${word}`"
                        class="ps-hero__word hm-intro"
                        :style="{ '--hm-delay': `${0.20 + i * 0.06}s` }"
                    ><span class="ps-hero__word-inner">{{ word }}</span></span>
                </h1>
                <p v-if="resolvedLead" class="ps-hero__lead hm-intro" style="--hm-delay:.55s">{{ resolvedLead }}</p>
                <div v-if="resolvedCta.length" class="ps-hero__actions hm-intro" style="--hm-delay:.55s">
                    <Link
                        v-for="action in resolvedCta"
                        :key="`${action.label}-${action.url}`"
                        :href="action.url"
                        class="ps-btn"
                        :class="action.primary ? 'ps-btn-primary' : 'ps-btn-ghost'"
                    >
                        {{ action.label }}
                    </Link>
                </div>
                <slot />
            </div>
        </div>
    </section>
</template>
