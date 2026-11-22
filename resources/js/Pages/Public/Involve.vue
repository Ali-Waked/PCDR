<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import InquiryForm from '../../Components/InquiryForm.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    areas: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()
const mode = ref('volunteer')
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.involve') }]" />
                <p class="eyebrow">{{ t('navigation.involve') }}</p>
                <h1 class="display-2">{{ t('navigation.involve') }}</h1>
                <p class="lede">{{ t('volunteer_intro') }}</p>
            </div>
        </section>

        <section class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading :title="t('ui.cms_areas')" />
                <div v-if="areas.length" class="pillar-grid">
                    <Link v-for="area in areas" :key="area.code" :href="area.url" class="pillar-tile" v-reveal>
                        <span class="pillar-tile__code">{{ area.code }}</span>
                        <h3>{{ area.title }}</h3>
                    </Link>
                </div>
                <EmptyState v-else :body="t('ui.no_results')" />
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <div class="tabs" role="tablist" :aria-label="t('navigation.involve')">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'volunteer'"
                        :class="{ 'is-active': mode === 'volunteer' }"
                        @click="mode = 'volunteer'"
                    >
                        {{ t('volunteer_form') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'partnership'"
                        :class="{ 'is-active': mode === 'partnership' }"
                        @click="mode = 'partnership'"
                    >
                        {{ t('partnership_form') }}
                    </button>
                </div>

                <div class="cms-form-grid--split" style="display: grid; gap: 2rem; max-inline-size: 960px">
                    <div>
                        <h2 class="section-title">{{ mode === 'volunteer' ? t('volunteer_form') : t('partnership_form') }}</h2>
                        <p class="lede" style="font-size: 1rem">{{ t('privacy_notice_short') }}</p>
                    </div>
                    <div class="cms-card">
                        <InquiryForm :key="mode" :type="mode" :areas="areas" />
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
