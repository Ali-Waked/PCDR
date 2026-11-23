<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BrandMark from '../Components/BrandMark.vue'
import FlashMessages from '../Components/FlashMessages.vue'
import { useI18n } from '../composables/useI18n'
import { useLocale } from '../Composables/useLocale'
import { usePermission } from '../Composables/usePermission'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})

const { page, t: siteT, localPath, alternateUrl, direction, locale } = useI18n()
const { t } = useLocale()
const { canInstitutionalApprove } = usePermission()
const mobileNavigationOpen = ref(false)

const user = computed(() => page.props.auth?.user || null)
const currentPath = computed(() => (page.url || '/').split('?')[0])

const navigationGroups = computed(() => (page.props.adminNavigation ?? []).map((group) => ({
    ...group,
    items: group.items.map((item) => {
        const href = localPath(item.path.replace(/^\//, ''))

        return {
            ...item,
            href,
            active: currentPath.value === href || (href !== localPath('cms') && currentPath.value.startsWith(`${href}/`)),
        }
    }),
})))

function hrefFor(path) {
    return localPath(path.replace(/^\//, ''))
}

function logout() {
    router.post(hrefFor('cms/logout'))
}

watch(() => page.url, () => { mobileNavigationOpen.value = false })
</script>

<template>
    <div class="admin-shell cms-admin-shell" :dir="direction">
        <a href="#main" class="skip-link">
            {{ siteT('ui.skip_to_content', t('skip_to_content')) }}
        </a>

        <header class="admin-topbar">
            <div class="admin-topbar__inner">
                <button
                    type="button"
                    class="admin-nav-toggle"
                    :aria-expanded="mobileNavigationOpen"
                    aria-controls="cms-navigation"
                    :aria-label="t('admin_nav.toggle')"
                    @click="mobileNavigationOpen = !mobileNavigationOpen"
                >
                    <span aria-hidden="true">☰</span>
                </button>

                <Link :href="hrefFor('cms')" class="admin-brand-link focus-ring">
                    <BrandMark />
                </Link>

                <div class="admin-topbar__context" aria-hidden="true">
                    <span class="admin-topbar__divider" />
                    <span>{{ t('admin_brand.content_studio') }}</span>
                </div>

                <div class="admin-topbar__actions">
                    <span v-if="canInstitutionalApprove" class="admin-role-badge admin-role-badge--authority">
                        {{ t('admin_brand.approval_authority') }}
                    </span>
                    <div class="lang-switch" :aria-label="siteT('ui.switch_language', t('language'))">
                        <Link
                            v-for="code in ['ar', 'en']"
                            :key="code"
                            :href="alternateUrl(code)"
                            :class="{ 'is-active': code === locale }"
                        >
                            {{ code === 'ar' ? 'ع' : 'EN' }}
                        </Link>
                    </div>
                    <div class="admin-user">
                        <strong>{{ user?.name }}</strong>
                        <small>{{ siteT('ui.cms_welcome', t('admin_brand.content_studio')) }}</small>
                    </div>
                    <button class="btn btn-ghost btn-small" type="button" @click="logout">
                        {{ siteT('ui.cms_logout', t('logout')) }}
                    </button>
                </div>
            </div>
        </header>

        <div class="admin-shell__body">
            <aside
                id="cms-navigation"
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

                <div class="admin-sidebar__footer">
                    <Link :href="hrefFor('')" class="admin-nav-link">
                        {{ siteT('cms_preview', t('admin_brand.preview_site')) }}
                    </Link>
                    <p class="admin-sidebar__note" role="note">
                        {{ t('admin_brand.authority_note') }}
                    </p>
                </div>
            </aside>

            <div v-if="mobileNavigationOpen" class="admin-nav-scrim" aria-hidden="true" @click="mobileNavigationOpen = false" />

            <main id="main" class="admin-main" tabindex="-1">
                <header class="admin-page-heading pcdr-enter">
                    <p class="admin-eyebrow">{{ subtitle || t('admin_brand.content_studio') }}</p>
                    <h1>{{ title }}</h1>
                </header>
                <FlashMessages />
                <slot />
            </main>
        </div>
    </div>
</template>
