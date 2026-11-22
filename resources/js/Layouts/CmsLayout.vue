<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from '../composables/useI18n'
import FlashMessages from '../Components/FlashMessages.vue'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})

const { page, t, locale, localPath, alternateUrl } = useI18n()

const user = computed(() => page.props.auth?.user || null)
const permissions = computed(() => user.value?.permissions || [])

function can(permission) {
    return permissions.value.includes(permission) || permissions.value.includes('dashboard')
}

const groups = computed(() => [
    {
        label: t('cms_metrics'),
        links: [
            { label: t('cms_dashboard'), url: localPath('cms'), permission: 'pages.view' },
        ],
    },
    {
        label: t('ui.cms_pages'),
        links: [
            { label: t('ui.cms_pages'), url: localPath('cms/pages'), permission: 'pages.view' },
            { label: t('ui.cms_pillars'), url: localPath('cms/pillars'), permission: 'pillars.view' },
            { label: t('ui.cms_programs'), url: localPath('cms/programs'), permission: 'programs.view' },
            { label: t('projects'), url: localPath('cms/projects'), permission: 'programs.view' },
            { label: t('ui.cms_news'), url: localPath('cms/news'), permission: 'news.view' },
            { label: t('ui.cms_publications'), url: localPath('cms/publications'), permission: 'publications.view' },
        ],
    },
    {
        label: t('cms_welcome'),
        links: [
            { label: t('ui.cms_media'), url: localPath('cms/media'), permission: 'media.view' },
            { label: t('ui.cms_navigation'), url: localPath('cms/navigation'), permission: 'navigation.view' },
            { label: t('ui.cms_homepage'), url: localPath('cms/homepage'), permission: 'homepage.view' },
            { label: t('ui.cms_settings'), url: localPath('cms/settings'), permission: 'settings.view' },
            { label: t('ui.cms_inquiries'), url: localPath('cms/inquiries'), permission: 'inquiries.view' },
        ],
    },
])

const currentPath = computed(() => (page.url || '/').split('?')[0])

function isActive(url) {
    return currentPath.value === url || (url !== localPath('cms') && currentPath.value.startsWith(`${url}/`))
}

function logout() {
    router.post(localPath('cms/logout'))
}
</script>

<template>
    <div class="cms-shell">
        <div class="cms-grid">
            <aside class="cms-sidebar">
                <Link :href="localPath('cms')" class="cms-sidebar__brand">
                    <span aria-hidden="true">◆</span>
                    <span>{{ t('cms_welcome') }}</span>
                </Link>

                <nav :aria-label="t('cms_welcome')">
                    <div v-for="group in groups" :key="group.label" class="cms-sidebar__group">
                        <span>{{ group.label }}</span>
                        <Link
                            v-for="link in group.links.filter((item) => can(item.permission))"
                            :key="link.url"
                            :href="link.url"
                            class="cms-nav-link"
                            :class="{ 'is-active': isActive(link.url) }"
                            :aria-current="isActive(link.url) ? 'page' : undefined"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </nav>

                <div class="cms-sidebar__group">
                    <span>{{ t('navigation.home') }}</span>
                    <Link :href="localPath()" class="cms-nav-link">{{ t('cms_preview') }}</Link>
                </div>
            </aside>

            <div class="cms-main">
                <header class="cms-topbar">
                    <div>
                        <strong>{{ title || t('cms_welcome') }}</strong>
                        <span v-if="subtitle" class="small-caps" style="margin-inline-start: 0.6rem">{{ subtitle }}</span>
                    </div>
                    <div class="header-actions">
                        <div class="lang-switch" :aria-label="t('ui.switch_language')">
                            <Link
                                v-for="code in ['ar', 'en']"
                                :key="code"
                                :href="alternateUrl(code)"
                                :class="{ 'is-active': code === locale }"
                            >
                                {{ code === 'ar' ? 'ع' : 'EN' }}
                            </Link>
                        </div>
                        <span class="small-caps">{{ user?.name }}</span>
                        <button class="btn btn-ghost btn-small" type="button" @click="logout">{{ t('ui.cms_logout') }}</button>
                    </div>
                </header>

                <div class="cms-content">
                    <FlashMessages />
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
