<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

defineProps({
    siteName: { type: String, default: 'PCDR' },
    tagline: { type: String, default: '' },
})

const { t, localePath } = useLocale()
const { isPlatformOwner, canAny } = usePermission()
const canEnter = computed(() => isPlatformOwner.value || canAny(['users.view', 'roles.view', 'settings.view', 'audit_logs.view']))
</script>

<template>
    <div class="min-h-screen bg-surface-alt">
        <Head :title="siteName" />
        <header class="mx-auto flex max-w-[1200px] items-center justify-between px-5 py-6">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-md bg-navy-700 text-base font-bold text-white">PCDR</span>
                <div>
                    <p class="text-lg font-semibold text-navy-900">{{ siteName }}</p>
                    <p class="text-xs text-muted">Gaza, Palestine</p>
                </div>
            </div>
            <LocaleSwitcher />
        </header>

        <main class="mx-auto max-w-[1200px] px-5 py-16">
            <section class="pcdr-card overflow-hidden pcdr-enter">
                <div class="border-b border-subtle bg-navy-700 px-8 py-10 text-white">
                    <p class="text-sm font-semibold uppercase tracking-wide text-teal-100">{{ t('welcome.badge') }}</p>
                    <h1 class="mt-4 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl">{{ tagline }}</h1>
                </div>
                <div class="flex flex-col gap-5 px-8 py-8 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ t('welcome.note') }}</p>
                    <Link
                        v-if="canEnter"
                        :href="localePath('/dashboard')"
                        class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-navy-900"
                    >
                        {{ t('welcome.cta') }}
                    </Link>
                    <Link
                        v-else
                        :href="localePath('/login')"
                        class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-navy-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-navy-800"
                    >
                        {{ t('auth.submit') }}
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
