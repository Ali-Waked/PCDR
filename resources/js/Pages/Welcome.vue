<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import BrandMark from '../Components/BrandMark.vue'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

defineProps({
    siteName: { type: String, default: 'PCDR' },
    tagline: { type: String, default: '' },
})

const { t, localePath, direction } = useLocale()
const { isPlatformOwner, canAny, canInstitutionalApprove } = usePermission()
const canEnter = computed(() => isPlatformOwner.value || canAny(['users.view', 'roles.view', 'settings.view', 'audit_logs.view']))
</script>

<template>
    <div class="auth-shell" :dir="direction">
        <Head :title="siteName" />
        <a href="#main" class="skip-link">
            {{ t('skip_to_content') }}
        </a>

        <header class="auth-shell__header">
            <BrandMark />
            <LocaleSwitcher />
        </header>

        <main id="main" class="auth-shell__main" tabindex="-1">
            <section class="welcome-panel pcdr-card pcdr-enter" aria-labelledby="welcome-title">
                <div class="welcome-panel__hero">
                    <p class="admin-eyebrow">{{ t('welcome.badge') }}</p>
                    <h1 id="welcome-title">{{ tagline }}</h1>
                </div>
                <div class="welcome-panel__body">
                    <p>{{ t('welcome.note') }}</p>
                    <div class="welcome-panel__actions">
                        <Link
                            v-if="canEnter"
                            :href="localePath('/dashboard')"
                            class="btn btn-primary"
                        >
                            {{ t('welcome.cta') }}
                        </Link>
                        <Link
                            v-else
                            :href="localePath('/login')"
                            class="btn btn-primary"
                        >
                            {{ t('auth.submit') }}
                        </Link>
                        <span v-if="canInstitutionalApprove" class="admin-role-badge admin-role-badge--authority">
                            {{ t('admin_brand.approval_authority') }}
                        </span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
