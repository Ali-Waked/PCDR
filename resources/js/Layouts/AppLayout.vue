<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import BrandMark from '../Components/BrandMark.vue'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import Alert from '../Components/Ui/Alert.vue'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

const props = defineProps({ title: { type: String, default: '' } })

const page = usePage()
const { t, localePath, direction } = useLocale()
const { isPlatformOwner, canInstitutionalApprove } = usePermission()
const mobileNavigationOpen = ref(false)

const user = computed(() => page.props.auth?.user ?? {})
const flash = computed(() => page.props.flash ?? {})
const currentPath = computed(() => (page.url || '').split('?')[0])
const pageTitle = computed(() => props.title || t('dashboard.title'))

const navigationGroups = computed(() => (page.props.adminNavigation ?? []).map((group) => ({
    ...group,
    items: group.items.map((item) => {
        const href = localePath(item.path)

        return {
            ...item,
            href,
            active: currentPath.value === href || (href !== localePath('/dashboard') && currentPath.value.startsWith(`${href}/`)),
        }
    }),
})))

watch(() => page.url, () => { mobileNavigationOpen.value = false })
</script>

<template>
    <div class="admin-shell" :dir="direction">
        <Head :title="pageTitle" />
        <a href="#main" class="skip-link">
            {{ t('skip_to_content') }}
        </a>

        <header class="admin-topbar">
            <div class="admin-topbar__inner">
                <button
                    type="button"
                    class="admin-nav-toggle"
                    :aria-expanded="mobileNavigationOpen"
                    aria-controls="admin-navigation"
                    :aria-label="t('admin_nav.toggle')"
                    @click="mobileNavigationOpen = !mobileNavigationOpen"
                >
                    <span aria-hidden="true">☰</span>
                </button>

                <Link :href="localePath('/dashboard')" class="admin-brand-link focus-ring">
                    <BrandMark />
                </Link>

                <div class="admin-topbar__context" aria-hidden="true">
                    <span class="admin-topbar__divider" />
                    <span>{{ t('admin_brand.control_center') }}</span>
                </div>

                <div class="admin-topbar__actions">
                    <span v-if="isPlatformOwner" class="admin-role-badge">{{ t('users_page.platform_owner') }}</span>
                    <span v-if="canInstitutionalApprove" class="admin-role-badge admin-role-badge--authority">
                        {{ t('admin_brand.approval_authority') }}
                    </span>
                    <LocaleSwitcher />
                    <div class="admin-user">
                        <strong>{{ user.name }}</strong>
                        <small>{{ user.is_platform_owner ? t('users_page.platform_owner') : user.email }}</small>
                    </div>
                    <Link
                        :href="localePath('/logout')"
                        method="post"
                        as="button"
                        class="btn btn-ghost btn-small"
                    >
                        {{ t('logout') }}
                    </Link>
                </div>
            </div>
        </header>

        <div class="admin-shell__body">
            <aside
                id="admin-navigation"
                class="admin-sidebar"
                :class="{ 'is-open': mobileNavigationOpen }"
                :aria-label="t('admin_nav.title')"
            >
                <nav>
                    <div v-for="group in navigationGroups" :key="group.key" class="admin-sidebar__group">
                        <p class="admin-sidebar__label">{{ group.label }}</p>
                        <ul>
                            <li v-for="item in group.items" :key="item.key">
                                <Link
                                    :href="item.href"
                                    class="admin-nav-link"
                                    :class="{ 'is-active': item.active }"
                                    :aria-current="item.active ? 'page' : undefined"
                                >
                                    <span>{{ item.label }}</span>
                                    <span v-if="item.active" class="admin-nav-link__marker" aria-hidden="true" />
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>

                <p class="admin-sidebar__note" role="note">
                    {{ t('admin_brand.authority_note') }}
                </p>
            </aside>

            <div v-if="mobileNavigationOpen" class="admin-nav-scrim" aria-hidden="true" @click="mobileNavigationOpen = false" />

            <main id="main" class="admin-main" tabindex="-1">
                <Alert v-if="flash.success" type="success" :message="flash.success" class="mb-5" />
                <Alert v-if="flash.error" type="error" :message="flash.error" class="mb-5" />

                <header class="admin-page-heading pcdr-enter">
                    <p class="admin-eyebrow">{{ t('admin_brand.control_center') }}</p>
                    <h1>{{ pageTitle }}</h1>
                </header>

                <slot />
            </main>
        </div>
    </div>
</template>
