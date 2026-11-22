<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Breadcrumbs from '../Breadcrumbs.vue'

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
    // legacy props kept for compatibility, ignored in new design
    variant: { type: String, default: 'sand' },
    index: { type: String, default: '' },
    badge: { type: String, default: '' },
})

const resolvedLead = computed(() => props.lead || props.lede || '')
const resolvedCrumbs = computed(() => (props.breadcrumbs?.length ? props.breadcrumbs : props.crumbs) || [])
const resolvedCta = computed(() => (props.cta?.length ? props.cta : props.actions) || [])

const accentColor = computed(() => {
    const map = { teal: '#71BCB7', sand: '#DAC3A3', navy: '#3E5172' }
    return map[props.accent] || map.teal
})

const motifKey = computed(() => props.motif || 'olive')
</script>

<template>
    <section class="ps-hero ps-hero--editorial">
        <div class="ps-container ps-hero__grid">
            <div class="ps-hero__copy">
                <Breadcrumbs v-if="resolvedCrumbs.length" :items="resolvedCrumbs" />
                <div class="ps-hero__accent" :style="{ '--accent': accentColor }" aria-hidden="true"></div>
                <h1 class="ps-hero__title">{{ title }}</h1>
                <p v-if="resolvedLead" class="ps-hero__lead">{{ resolvedLead }}</p>
                <div v-if="resolvedCta.length" class="ps-hero__actions">
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

            <div class="ps-hero__visual" aria-hidden="true">
                <figure v-if="image?.src" class="ps-hero__photo">
                    <img :src="image.src" :alt="image.alt || ''" loading="eager" decoding="async">
                    <figcaption v-if="image.caption" class="ps-hero__caption">{{ image.caption }}</figcaption>
                </figure>
                <svg v-else class="ps-hero__motif" viewBox="0 0 420 420" fill="none" stroke="#25334B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <g v-if="motifKey === 'olive'">
                        <path d="M210 360 C210 300 210 240 210 120" /><path d="M210 290 C170 270 140 240 130 210 C160 215 185 230 210 250" /><path d="M210 250 C250 230 275 215 290 210 C280 240 250 270 210 290" /><path d="M210 210 C175 190 150 165 145 140 C170 145 190 160 210 175" /><path d="M210 175 C230 160 250 145 275 140 C270 165 245 190 210 210" /><path d="M210 140 C190 125 175 105 175 85 C195 95 205 110 210 125 C215 110 225 95 245 85 C245 105 230 125 210 140" />
                    </g>
                    <g v-else-if="motifKey === 'sun'">
                        <circle cx="210" cy="210" r="52" />
                        <g v-for="i in 11" :key="i" :transform="`rotate(${(i-1)*32.727} 210 210)`"><line x1="210" y1="40" x2="210" y2="120" /></g>
                    </g>
                    <g v-else-if="motifKey === 'wheat'">
                        <path d="M210 360 L210 80" /><path d="M210 110 C190 100 180 85 185 70 C200 75 210 90 210 110 C210 90 220 75 235 70 C240 85 230 100 210 110" /><path d="M210 170 C185 160 170 145 175 125 C195 130 205 145 210 160 C215 145 225 130 245 125 C250 145 235 160 210 170" /><path d="M210 230 C185 220 170 205 175 185 C195 190 205 205 210 220 C215 205 225 190 245 185 C250 205 235 220 210 230" /><path d="M210 290 C185 280 170 265 175 245 C195 250 205 265 210 280 C215 265 225 250 245 245 C250 265 235 280 210 290" />
                    </g>
                    <g v-else-if="motifKey === 'drop'">
                        <path d="M210 70 C210 70 120 190 120 250 C120 300 160 340 210 340 C260 340 300 300 300 250 C300 190 210 70 210 70 Z" /><path d="M120 370 C150 355 180 355 210 370 C240 385 270 385 300 370" /><path d="M140 395 C165 385 185 385 210 395 C235 405 255 405 280 395" />
                    </g>
                    <g v-else-if="motifKey === 'book'">
                        <path d="M210 100 L210 330" /><path d="M210 110 C170 90 120 90 80 100 L80 320 C120 310 170 310 210 330" /><path d="M210 110 C250 90 300 90 340 100 L340 320 C300 310 250 310 210 330" /><path d="M110 150 L185 150 M110 180 L185 180 M110 210 L175 210" /><path d="M235 150 L310 150 M235 180 L310 180 M245 210 L310 210" />
                    </g>
                    <g v-else-if="motifKey === 'scales'">
                        <path d="M210 80 L210 340" /><path d="M120 120 L300 120" /><circle cx="210" cy="80" r="10" /><path d="M210 340 L160 360 L260 360 Z" /><path d="M120 120 L90 200 C90 225 110 240 135 240 C160 240 180 225 180 200 Z" /><path d="M300 120 L270 200 C270 225 290 240 315 240 C340 240 360 225 360 200 Z" />
                    </g>
                    <g v-else-if="motifKey === 'rings'">
                        <circle cx="170" cy="210" r="90" /><circle cx="250" cy="210" r="90" />
                    </g>
                    <g v-else-if="motifKey === 'hands'">
                        <path d="M80 260 C100 230 130 220 160 230 L200 250 L200 320 L150 340 C120 350 95 340 80 320 Z" /><path d="M340 260 C320 230 290 220 260 230 L220 250 L220 320 L270 340 C300 350 325 340 340 320 Z" /><path d="M160 230 L160 170 C160 155 170 145 185 145 C200 145 210 155 210 170 L210 250" /><path d="M260 230 L260 170 C260 155 250 145 235 145 C220 145 210 155 210 170" />
                    </g>
                    <g v-else-if="motifKey === 'envelope'">
                        <rect x="70" y="120" width="280" height="190" rx="4" /><path d="M70 120 L210 230 L350 120" /><path d="M70 310 L170 210" /><path d="M350 310 L250 210" />
                    </g>
                    <g v-else>
                        <path d="M210 360 C210 300 210 240 210 120" /><path d="M210 290 C170 270 140 240 130 210 C160 215 185 230 210 250" /><path d="M210 250 C250 230 275 215 290 210 C280 240 250 270 210 290" />
                    </g>
                </svg>
            </div>
        </div>
    </section>
</template>
