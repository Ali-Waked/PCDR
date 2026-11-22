<script setup>
import { computed, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'

const { page, t, site, locale, localPath, alternateUrl } = useI18n()
const open = ref(false)

const primary = computed(() => page.props.navigation?.header || [])
const currentPath = computed(() => (page.url || '/').split('?')[0])

watch(() => page.url, () => {
    open.value = false
})

function isActive(url) {
    if (! url) {
        return false
    }

    const path = new URL(url, window.location.origin).pathname
    if (path === localPath()) {
        return currentPath.value === path
    }

    return currentPath.value.startsWith(path)
}
</script>

<template>
    <header class="site-header">
        <div class="container-public site-header__inner">
            <Link :href="localPath()" class="wordmark" :aria-label="site.name">
                <span class="wordmark__mark" aria-hidden="true">P</span>
                <span class="wordmark__text">
                    <strong>{{ site.shortName || 'PCDR' }}</strong>
                    <span>{{ site.name }}</span>
                </span>
            </Link>

            <nav class="primary-nav" :aria-label="t('cms_menu_primary')">
                <Link
                    v-for="item in primary"
                    :key="item.url"
                    :href="item.url"
                    class="nav-link"
                    :class="{ 'is-active': isActive(item.url) }"
                    :aria-current="isActive(item.url) ? 'page' : undefined"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="header-actions">
                <div class="lang-switch" :aria-label="t('ui.switch_language')">
                    <Link
                        v-for="code in ['ar', 'en']"
                        :key="code"
                        :href="alternateUrl(code)"
                        :class="{ 'is-active': code === locale }"
                        :aria-current="code === locale ? 'true' : undefined"
                        :hreflang="code"
                    >
                        {{ code === 'ar' ? 'ع' : 'EN' }}
                    </Link>
                </div>
                <button
                    class="menu-toggle"
                    type="button"
                    :aria-expanded="open"
                    :aria-label="open ? t('ui.close_menu') : t('ui.open_menu')"
                    @click="open = !open"
                >
                    <span aria-hidden="true">{{ open ? '×' : '☰' }}</span>
                </button>
            </div>
        </div>

        <div v-if="open" class="container-public mobile-nav">
            <Link
                v-for="item in primary"
                :key="`mobile-${item.url}`"
                :href="item.url"
                class="mobile-link"
                :class="{ 'is-active': isActive(item.url) }"
            >
                {{ item.label }}
            </Link>
        </div>
    </header>
</template>
