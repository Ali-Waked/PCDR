<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import Alert from '../Components/Ui/Alert.vue'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

defineProps({ title: { type: String, default: '' } })

const page = usePage()
const { t, localePath } = useLocale()
const { can } = usePermission()
const user = computed(() => page.props.auth?.user ?? {})
const flash = computed(() => page.props.flash ?? {})

const navItems = computed(() => [
    { label: t('dashboard'), href: localePath('/dashboard'), show: true },
    { label: t('users'), href: localePath('/admin/users'), show: can('users.view') },
    { label: t('roles'), href: localePath('/admin/roles'), show: can('roles.view') },
    { label: t('permissions'), href: localePath('/admin/permissions'), show: can('permissions.view') },
    { label: t('audit_logs'), href: localePath('/admin/audit-logs'), show: can('audit_logs.view') },
    { label: t('settings'), href: localePath('/admin/settings'), show: can('settings.view') },
].filter((item) => item.show))
</script>

<template>
    <div class="min-h-screen bg-surface-alt">
        <Head :title="title || t('app_name')" />
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded focus:bg-navy-700 focus:px-4 focus:py-2 focus:text-white">
            {{ t('skip_to_content') }}
        </a>

        <header class="sticky top-0 z-30 border-b border-subtle bg-surface">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-5 py-3">
                <Link :href="localePath('/dashboard')" class="focus-ring flex items-center gap-3 rounded-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-navy-700 text-sm font-bold text-white">PCDR</span>
                    <span class="hidden font-semibold text-ink sm:block">{{ t('app_name') }}</span>
                </Link>
                <div class="flex items-center gap-3">
                    <LocaleSwitcher />
                    <div class="hidden text-end sm:block">
                        <p class="text-sm font-medium text-ink">{{ user.name }}</p>
                        <p class="text-xs text-muted">{{ user.is_platform_owner ? t('users_page.platform_owner') : user.email }}</p>
                    </div>
                    <Link :href="localePath('/logout')" method="post" as="button" class="focus-ring rounded-md border border-subtle px-3 py-2 text-sm text-ink hover:bg-surface-alt">
                        {{ t('logout') }}
                    </Link>
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-[1600px]">
            <nav class="hidden w-64 shrink-0 border-e border-subtle bg-surface p-4 lg:block" :aria-label="t('dashboard')">
                <ul class="space-y-1">
                    <li v-for="item in navItems" :key="item.href">
                        <Link
                            :href="item.href"
                            class="focus-ring block rounded-md px-3 py-2.5 text-sm font-medium hover:bg-surface-alt"
                            :class="page.url.startsWith(item.href) ? 'bg-navy-100 text-navy-900' : 'text-muted'"
                        >
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <main id="main" class="min-w-0 flex-1 p-5 lg:p-8">
                <Alert v-if="flash.success" type="success" :message="flash.success" class="mb-5" />
                <Alert v-if="flash.error" type="error" :message="flash.error" class="mb-5" />
                <div class="mb-6 pcdr-enter">
                    <h1 class="text-2xl font-semibold text-navy-900">{{ title }}</h1>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
