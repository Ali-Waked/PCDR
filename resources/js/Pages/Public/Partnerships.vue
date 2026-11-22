<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()

const principles = [
    'principle_transparency',
    'principle_coordination',
    'principle_no_duplication',
]
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.partnerships') }]" />
                <p class="eyebrow">{{ t('navigation.partnerships') }}</p>
                <h1 class="display-2">{{ t('navigation.partnerships') }}</h1>
                <p class="lede">{{ t('partnership_intro') }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public">
                <SectionHeading :title="t('partnership_principles')" />
                <div class="list-rows">
                    <article v-for="(key, index) in principles" :key="key" class="list-row list-row--split" v-reveal>
                        <h3 style="margin: 0; font-size: 1rem">{{ t(key) }}</h3>
                        <span class="pillar-tile__code">{{ String(index + 1).padStart(2, '0') }}</span>
                    </article>
                </div>
                <div class="notice" style="margin-block-start: 1.5rem">{{ t('planning_targets_notice') }}</div>
            </div>
        </section>

        <section class="section-block surface-sand">
            <div class="container-public">
                <SectionHeading
                    :title="t('partnership_form')"
                    :body="t('privacy_notice_short')"
                    :action-label="t('navigation.contact')"
                    :action-url="localPath('contact')"
                />
                <Link :href="localPath('contact')" class="btn btn-primary">{{ t('partnership_form') }}</Link>
            </div>
        </section>
    </PublicLayout>
</template>
