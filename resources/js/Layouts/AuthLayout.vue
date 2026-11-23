<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'
import { useLocale } from '../Composables/useLocale'
import UiIcon from '../Components/Dashboard/UiIcon.vue'

const { t, direction } = useI18n()
const { pathForLocale } = useLocale()
const page = usePage()
</script>

<template>
    <div class="pc-auth" :dir="direction">
        <a href="#pc-auth-main" class="skip-link">{{ t('ui.skip_to_content') }}</a>

        <section class="pc-auth__brand" aria-labelledby="pc-auth-intro">
            <div class="pc-auth__logo">
                <img src="/assets/pcdr-homepage/logo/pcdr-logo.png" alt="" aria-hidden="true" width="56" height="56">
                <span class="pc-auth__logo-copy">
                    <strong>PCDR</strong>
                    <span>{{ t('ui.dashboard_organization') }}</span>
                </span>
            </div>

            <div class="pc-auth__statement">
                <p class="pc-auth__eyebrow">{{ t('ui.dashboard_secure_workspace') }}</p>
                <h1 id="pc-auth-intro" class="pc-auth__title">{{ t('ui.dashboard_auth_title') }}</h1>
                <p class="pc-auth__text">{{ t('ui.dashboard_auth_text') }}</p>
            </div>

            <div class="pc-auth__footer">
                <span>{{ t('ui.dashboard_humanitarian') }}</span>
                <span aria-hidden="true">•</span>
                <span>{{ t('ui.dashboard_editorial') }}</span>
            </div>
        </section>

        <section class="pc-auth__panel">
            <div class="pc-auth__panel-top">
                <div class="pc-locale-switch" role="group" :aria-label="t('ui.language')">
                    <Link :href="pathForLocale('ar')" :class="{ 'is-active': page.props.locale === 'ar' }" :aria-current="page.props.locale === 'ar' ? 'page' : undefined">ع</Link>
                    <Link :href="pathForLocale('en')" :class="{ 'is-active': page.props.locale === 'en' }" :aria-current="page.props.locale === 'en' ? 'page' : undefined">EN</Link>
                </div>
            </div>
            <div class="pc-auth__form-wrap">
                <main id="pc-auth-main" class="pc-auth__form" tabindex="-1">
                    <div v-if="page.props.flash?.success" class="pc-callout" role="status">
                        <UiIcon name="check" :size="17" />
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                    <div v-if="page.props.flash?.error" class="pc-callout pc-callout--warning" role="alert">
                        <UiIcon name="alert" :size="17" />
                        <span>{{ page.props.flash.error }}</span>
                    </div>
                    <slot />
                </main>
            </div>
        </section>
    </div>
</template>
