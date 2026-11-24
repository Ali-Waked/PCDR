<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import InquiryForm from '../../Components/InquiryForm.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    areas: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
const closingActions = computed(() => [
    { label: t('nav.contact'), url: localPath('contact'), primary: true },
    { label: t('nav.partnerships'), url: localPath('partnerships'), primary: false },
])
</script>

<template>
    <PublicLayout :meta="meta" page-class="involve-page">
        <PublicPageHero
            :title="t('nav.involve')"
            :lead="t('volunteer_intro')"
            :breadcrumbs="[{ label: t('nav.involve') }]"
            accent="sand"
            motif="hands"
        />

        <section class="ps-section ps-surface-sand">
            <div class="ps-container">
                <SectionHeading
                    :eyebrow="t('ui.strategic_direction')"
                    :title="t('ui.cms_areas')"
                    :body="t('strategic_areas_intro')"
                />
                <div v-if="areas.length" class="ps-grid" data-stagger>
                    <Link
                        v-for="(area, index) in areas"
                        :key="area.code"
                        :href="area.url"
                        class="ps-card reveal"
                        :style="{ '--i': index }"
                        v-reveal
                    >
                        <span class="ps-card__code">{{ area.code }}</span>
                        <h3>{{ area.title }}</h3>
                    </Link>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>

        <section class="ps-section">
            <div class="ps-container">
                <div class="ps-split">
                    <div v-reveal>
                        <p class="ps-eyebrow">{{ t('nav.involve') }}</p>
                        <h2>{{ t('volunteer_form') }}</h2>
                        <p class="ps-lede">{{ t('privacy_notice_short') }}</p>
                        <p class="ps-notice">{{ t('strategic_statement') }}</p>
                    </div>
                    <div class="ps-form-card" v-reveal>
                        <InquiryForm type="volunteer" :areas="areas" />
                    </div>
                </div>
            </div>
        </section>

        <PublicCta :title="t('nav.contact')" :lede="t('contact_details')" :actions="closingActions" />
    </PublicLayout>
</template>
