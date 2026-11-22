<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import Breadcrumbs from '../../Components/Breadcrumbs.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import InquiryForm from '../../Components/InquiryForm.vue'
import { useI18n } from '../../composables/useI18n'

defineProps({
    contact: { type: Object, required: true },
    meta: { type: Object, default: null },
})

const { t, localPath, site } = useI18n()
const hasContact = Object.values(site.value.contact || {}).some(Boolean)
</script>

<template>
    <PublicLayout :meta="meta">
        <section class="page-hero">
            <div class="container-public">
                <Breadcrumbs :items="[{ label: t('navigation.contact') }]" />
                <p class="eyebrow">{{ t('navigation.contact') }}</p>
                <h1 class="display-2">{{ t('navigation.contact') }}</h1>
                <p class="lede">{{ t('privacy_notice_short') }}</p>
            </div>
        </section>

        <section class="section-block">
            <div class="container-public cms-form-grid--split" style="display: grid; gap: 2rem">
                <div>
                    <SectionHeading :title="t('contact_details')" />
                    <p v-if="! hasContact" class="notice">{{ t('ui.no_verified_contact') }}</p>
                    <div v-else class="list-rows">
                        <div v-if="contact.email" class="list-row">
                            <span class="small-caps">{{ t('cms_email') }}</span>
                            <a :href="`mailto:${contact.email}`" dir="ltr">{{ contact.email }}</a>
                        </div>
                        <div v-if="contact.phone" class="list-row">
                            <span class="small-caps">{{ t('cms_phone') }}</span>
                            <span dir="ltr">{{ contact.phone }}</span>
                        </div>
                        <div v-if="contact.address" class="list-row">
                            <span class="small-caps">{{ t('cms_address') }}</span>
                            <span>{{ contact.address }}</span>
                        </div>
                        <div v-if="contact.hours" class="list-row">
                            <span class="small-caps">{{ t('cms_hours') }}</span>
                            <span>{{ contact.hours }}</span>
                        </div>
                    </div>

                    <div v-if="site.social?.length" style="margin-block-start: 1.5rem">
                        <h2 style="font-size: 1rem">{{ t('social_links') }}</h2>
                        <div class="meta-row">
                            <a v-for="link in site.social" :key="link.url" :href="link.url" target="_blank" rel="noopener noreferrer" class="pill">{{ link.label }}</a>
                        </div>
                    </div>

                    <div style="margin-block-start: 2rem">
                        <h2 style="font-size: 1rem">{{ t('navigation.involve') }}</h2>
                        <div class="meta-row" style="margin-block-start: 0.5rem">
                            <Link :href="localPath('get-involved')" class="text-link">{{ t('volunteer_form') }}</Link>
                            <Link :href="localPath('get-involved')" class="text-link">{{ t('partnership_form') }}</Link>
                        </div>
                    </div>
                </div>

                <div class="cms-card">
                    <h2 class="section-title" style="font-size: 1.35rem; margin-block-start: 0">{{ t('contact_form') }}</h2>
                    <InquiryForm type="contact" />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
