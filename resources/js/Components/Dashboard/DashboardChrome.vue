<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'
import { useLocale } from '../../Composables/useLocale'
import { usePermission } from '../../Composables/usePermission'
import PageHeader from './PageHeader.vue'
import UiIcon from './UiIcon.vue'

const props = defineProps({
    mode: { type: String, default: 'admin' },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
})

const { page, t, localPath, direction, locale } = useI18n()
const { pathForLocale } = useLocale()
const { isPlatformOwner } = usePermission()

const mobileOpen = ref(false)
const collapsed = ref(false)
const userMenuOpen = ref(false)
const navSearch = ref('')
const toggleRef = ref(null)
const drawerRef = ref(null)
const userMenuRef = ref(null)
const isDesktop = ref(true)
let mql = null
let onMql = null

const isCms = computed(() => props.mode === 'cms')
const user = computed(() => page.props.auth?.user ?? {})
const flash = computed(() => page.props.flash ?? {})
const currentPath = computed(() => (page.url || '').split('?')[0])
const initials = computed(() => {
    const name = String(user.value.name || '').trim()
    return name ? name.split(/\s+/).slice(0, 2).map((part) => part[0]?.toUpperCase() || '').join('') : '?'
})
const workspaceLabel = computed(() => t(isCms.value ? 'ui.dashboard_content_studio' : 'ui.dashboard_workspace'))
const pageSubtitle = computed(() => props.subtitle || t('ui.dashboard_technical_note'))
const pageTitle = computed(() => props.title || t('ui.dashboard'))
const homePath = computed(() => isCms.value ? localPath('cms') : localPath('dashboard'))

const iconFor = {
    dashboard: 'layout',
    users: 'users',
    roles: 'shield',
    permissions: 'shield',
    audit_logs: 'history',
    settings: 'settings',
    platform_settings: 'settings',
    cms_dashboard: 'layout',
    pages: 'file',
    pillars: 'layers',
    programs: 'folder',
    news: 'newspaper',
    publications: 'folder',
    media: 'image',
    navigation: 'link',
    homepage: 'home',
    inquiries: 'mail',
    email_templates: 'template',
    cms_settings: 'settings',
    projects: 'folder',
    localization: 'globe',
    seo: 'search',
}

const navigationGroups = computed(() => (page.props.adminNavigation ?? []).map((group) => ({
    ...group,
    items: group.items
        .filter((item) => item.label?.toLowerCase?.().includes(navSearch.value.toLowerCase()))
        .map((item) => {
            const href = isCms.value ? localPath(String(item.path).replace(/^\//, '')) : localPath(String(item.path))
            return {
                ...item,
                href,
                icon: iconFor[item.key] ?? 'file',
                active: currentPath.value === href || (href !== homePath.value && currentPath.value.startsWith(`${href}/`)),
            }
        }),
})).filter((group) => group.items.length))

function hrefFor(path) {
    return isCms.value ? localPath(path.replace(/^\//, '')) : localPath(path)
}

function logout() {
    if (isCms.value) {
        router.post(hrefFor('cms/logout'))
    } else {
        router.post(hrefFor('logout'))
    }
}

function closeNav() {
    mobileOpen.value = false
    requestAnimationFrame(() => toggleRef.value?.focus?.())
}

function focusables() {
    const root = drawerRef.value
    if (! root) {
        return []
    }

    return [...root.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])')]
        .filter((element) => ! element.closest('[inert]'))
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (userMenuOpen.value) {
            userMenuOpen.value = false
            return
        }
        if (mobileOpen.value && ! isDesktop.value) {
            closeNav()
        }
        return
    }

    if (event.key !== 'Tab' || ! mobileOpen.value || isDesktop.value) {
        return
    }

    const items = focusables()
    if (! items.length) {
        event.preventDefault()
        return
    }

    const first = items[0]
    const last = items[items.length - 1]

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
    } else if (! event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
    }
}

function onDocumentClick(event) {
    if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false
    }
}

watch(() => page.url, () => {
    mobileOpen.value = false
    userMenuOpen.value = false
})

watch([locale, direction], ([loc, dir]) => {
    document.documentElement.lang = loc === 'ar' ? 'ar' : 'en'
    document.documentElement.dir = dir
    document.documentElement.dataset.locale = loc
}, { immediate: true })

watch(mobileOpen, (open) => {
    document.body.style.overflow = open && ! isDesktop.value ? 'hidden' : ''
    if (open && ! isDesktop.value) {
        requestAnimationFrame(() => {
            const items = focusables()
            ;(items[0] || drawerRef.value)?.focus?.()
        })
    }
})

onMounted(() => {
    mql = window.matchMedia('(min-width: 1100px)')
    isDesktop.value = mql.matches
    onMql = (event) => {
        isDesktop.value = event.matches
        if (event.matches) mobileOpen.value = false
        document.body.style.overflow = mobileOpen.value && ! event.matches ? 'hidden' : ''
    }
    mql.addEventListener?.('change', onMql)
    document.addEventListener('keydown', onKeydown)
    document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
    mql?.removeEventListener?.('change', onMql)
    document.removeEventListener('keydown', onKeydown)
    document.removeEventListener('click', onDocumentClick)
    document.body.style.overflow = ''
})
</script>

<template>
    <div class="pc-app" :class="{ 'is-collapsed': collapsed && isDesktop }" :dir="direction">
        <Head :title="pageTitle" />
        <a href="#pc-main" class="skip-link">{{ t('ui.skip_to_content') }}</a>

        <div class="pc-shell">
            <button v-if="mobileOpen" class="pc-scrim" type="button" :aria-label="t('ui.dashboard_close_navigation')" @click="closeNav" />

            <aside
                id="pc-navigation"
                ref="drawerRef"
                class="pc-sidebar"
                :class="{ 'is-open': mobileOpen }"
                :role="mobileOpen && !isDesktop ? 'dialog' : undefined"
                :aria-modal="mobileOpen && !isDesktop ? 'true' : undefined"
                :aria-label="t('ui.dashboard_navigation')"
                :tabindex="mobileOpen && !isDesktop ? -1 : undefined"
            >
                <Link :href="homePath" class="pc-brand" @click="closeNav">
                    <img class="pc-brand__logo" src="/assets/pcdr-homepage/logo/pcdr-logo.png" alt="" aria-hidden="true" width="46" height="46">
                    <span class="pc-brand__copy">
                        <span class="pc-brand__name">PCDR</span>
                        <span class="pc-brand__tagline">{{ workspaceLabel }}</span>
                    </span>
                </Link>

                <div class="pc-sidebar__scroll">
                    <label class="pc-nav-find">
                        <span class="sr-only">{{ t('ui.dashboard_search_navigation') }}</span>
                        <UiIcon name="search" :size="16" />
                        <input v-model="navSearch" type="search" :placeholder="t('ui.dashboard_search_navigation')" autocomplete="off">
                    </label>

                    <div v-for="group in navigationGroups" :key="group.key" class="pc-nav-section">
                        <p class="pc-nav-label">{{ group.label }}</p>
                        <nav class="pc-nav-list" :aria-label="group.label">
                            <Link
                                v-for="item in group.items"
                                :key="item.key"
                                :href="item.href"
                                class="pc-nav-item"
                                :class="{ 'is-active': item.active }"
                                :aria-current="item.active ? 'page' : undefined"
                                @click="closeNav"
                            >
                                <span class="pc-nav-item__icon"><UiIcon :name="item.icon" :size="18" /></span>
                                <span class="pc-nav-item__label">{{ item.label }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <div class="pc-sidebar__foot">
                    <div class="pc-sidebar__note">
                        <UiIcon name="info" :size="15" />
                        <span>{{ isPlatformOwner ? t('ui.dashboard_owner_note') : t('ui.dashboard_technical_note') }}</span>
                    </div>
                </div>
            </aside>

            <div class="pc-main">
                <header class="pc-topbar" :inert="mobileOpen && !isDesktop">
                    <div class="pc-topbar__lead">
                        <button
                            ref="toggleRef"
                            type="button"
                            class="pc-icon-button"
                            :aria-label="mobileOpen ? t('ui.dashboard_close_navigation') : t('ui.dashboard_open_navigation')"
                            :aria-expanded="mobileOpen ? 'true' : 'false'"
                            aria-controls="pc-navigation"
                            @click="mobileOpen = !mobileOpen"
                        >
                            <UiIcon name="menu" :size="19" />
                        </button>
                        <button
                            v-if="isDesktop"
                            type="button"
                            class="pc-icon-button"
                            :aria-label="collapsed ? t('ui.dashboard_expand') : t('ui.dashboard_collapse')"
                            @click="collapsed = !collapsed"
                        >
                            <UiIcon name="panel" :size="18" />
                        </button>
                        <div class="pc-topbar__context">
                            <strong>{{ pageTitle }}</strong>
                            <span>{{ workspaceLabel }}</span>
                        </div>
                    </div>

                    <div class="pc-topbar__spacer" />

                    <div class="pc-topbar__actions">
                        <div class="pc-locale-switch" role="group" :aria-label="t('ui.language')">
                            <Link :href="pathForLocale('ar')" :class="{ 'is-active': locale === 'ar' }" :aria-current="locale === 'ar' ? 'page' : undefined">ع</Link>
                            <Link :href="pathForLocale('en')" :class="{ 'is-active': locale === 'en' }" :aria-current="locale === 'en' ? 'page' : undefined">EN</Link>
                        </div>

                        <div ref="userMenuRef" class="pc-user">
                            <button
                                type="button"
                                class="pc-user__trigger"
                                :aria-expanded="userMenuOpen ? 'true' : 'false'"
                                aria-haspopup="menu"
                                :aria-label="user.name || t('ui.dashboard_profile')"
                                @click="userMenuOpen = !userMenuOpen"
                            >
                                <span class="pc-avatar" aria-hidden="true">{{ initials }}</span>
                                <span class="pc-user__meta">
                                    <strong>{{ user.name }}</strong>
                                    <span>{{ user.email }}</span>
                                </span>
                                <UiIcon name="chevronDown" :size="15" />
                            </button>
                            <div v-if="userMenuOpen" class="pc-menu" role="menu">
                                <div class="pc-menu__head">
                                    <strong>{{ user.name }}</strong>
                                    <span>{{ user.email }}</span>
                                </div>
                                <Link v-if="!isCms" :href="hrefFor('admin/audit-logs')" class="pc-menu__item" role="menuitem">
                                    <UiIcon name="history" :size="16" /> {{ t('ui.audit_logs') }}
                                </Link>
                                <Link v-if="isCms" :href="hrefFor('')" class="pc-menu__item" role="menuitem">
                                    <UiIcon name="eye" :size="16" /> {{ t('ui.cms_preview') }}
                                </Link>
                                <p class="pc-menu__note">{{ t('ui.dashboard_no_approval') }}</p>
                                <button type="button" class="pc-menu__item pc-menu__item--danger" role="menuitem" @click="logout">
                                    <UiIcon name="logout" :size="16" /> {{ t('ui.logout') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                <main id="pc-main" class="pc-content" tabindex="-1" :inert="mobileOpen && !isDesktop">
                    <PageHeader
                        :eyebrow="props.eyebrow || workspaceLabel"
                        :title="pageTitle"
                        :subtitle="pageSubtitle"
                    >
                        <template v-if="$slots.actions" #actions>
                            <slot name="actions" />
                        </template>
                    </PageHeader>

                    <div v-if="flash.success || flash.error" class="pc-stack" style="margin-block-end: 20px">
                        <div v-if="flash.success" class="pc-callout" role="status">
                            <UiIcon name="check" :size="17" />
                            <span>{{ flash.success }}</span>
                        </div>
                        <div v-if="flash.error" class="pc-callout pc-callout--warning" role="alert">
                            <UiIcon name="alert" :size="17" />
                            <span>{{ flash.error }}</span>
                        </div>
                    </div>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
