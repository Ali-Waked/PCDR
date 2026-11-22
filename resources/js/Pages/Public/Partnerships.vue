<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
const principles = ['principle_transparency', 'principle_coordination', 'principle_no_duplication']
const closingActions = [
    { label: t('partnership_form'), url: localPath('get-involved'), primary: true },
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
]
</script>

<template>
    <PublicLayout :meta="meta" page-class="partnerships-page">
        <PublicPageHero
            :title="t('nav.partnerships')"
            :lead="t('partnership_intro')"
            :breadcrumbs="[{ label: t('nav.partnerships') }]"
            accent="navy"
            motif="rings"
            :cta="[{ label: t('partnership_form'), url: localPath('get-involved'), primary: true }]"
        />

        <section class="ps-section">
            <div class="ps-container">
                <SectionHeading
                    :eyebrow="t('nav.partnerships')"
                    :title="t('partnership_principles')"
                    :body="t('planning_targets_notice')"
                />
                <div class="ps-rows" data-stagger>
                    <article
                        v-for="(key, index) in principles"
                        :key="key"
                        class="ps-row reveal"
                        :style="{ '--i': index }"
                        v-reveal
                    >
                        <span class="ps-row__num">{{ String(index + 1).padStart(2, '0') }}</span>
                        <h3>{{ t(key) }}</h3>
                        <p>{{ t('partnership_intro') }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ps-section ps-surface-sand">
            <div class="ps-container ps-split">
                <div class="ps-figure ps-figure--sand" v-reveal>
                    <p class="ps-eyebrow">{{ t('nav.partnerships') }}</p>
                    <p class="ps-statement">{{ t('partnership_principles') }}</p>
                </div>
                <div v-reveal>
                    <SectionHeading :title="t('partnership_form')" :body="t('privacy_notice_short')" />
                    <p class="ps-notice">{{ t('planning_targets_notice') }}</p>
                    <div class="ps-actions">
                        <Link :href="localPath('get-involved')" class="ps-btn ps-btn-primary">{{ t('partnership_form') }}</Link>
                        <Link :href="localPath('contact')" class="ps-btn ps-btn-ghost">{{ t('nav.contact') }}</Link>
                    </div>
                </div>
            </div>
        </section>

        <PublicCta :title="t('nav.involve')" :lede="t('partnership_intro')" :actions="closingActions" />
    </PublicLayout>
</template>
