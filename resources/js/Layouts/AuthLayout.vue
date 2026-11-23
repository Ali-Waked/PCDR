<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BrandMark from '../Components/BrandMark.vue'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import Alert from '../Components/Ui/Alert.vue'
import { useLocale } from '../Composables/useLocale'

const { t, direction } = useLocale()
const page = usePage()
const flash = computed(() => page.props.flash ?? {})
</script>

<template>
    <div class="auth-shell" :dir="direction">
        <a href="#main" class="skip-link">
            {{ t('skip_to_content') }}
        </a>

        <header class="auth-shell__header">
            <BrandMark />
            <LocaleSwitcher />
        </header>

        <main id="main" class="auth-shell__main" tabindex="-1">
            <section class="auth-shell__intro pcdr-enter" aria-labelledby="auth-intro-title">
                <p class="admin-eyebrow">{{ t('welcome.badge') }}</p>
                <h1 id="auth-intro-title">{{ t('admin_brand.secure_gateway') }}</h1>
                <p>{{ t('welcome.note') }}</p>
                <div class="auth-shell__accent" aria-hidden="true" />
            </section>

            <section class="auth-shell__panel pcdr-card" :aria-label="t('admin_brand.control_center')">
                <Alert v-if="flash.success" type="success" :message="flash.success" />
                <Alert v-if="flash.error" type="error" :message="flash.error" />
                <slot />
            </section>
        </main>
    </div>
</template>
