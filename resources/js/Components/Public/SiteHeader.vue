<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import LanguageSwitcher from '../LanguageSwitcher.vue'
import { useI18n } from '../../composables/useI18n'

const { page, t, site, localPath } = useI18n()

const logo = '/assets/pcdr-homepage/logo/pcdr-logo.png'
const open = ref(false)
const scrolled = ref(false)
const primary = computed(() => page.props.navigation?.primary || page.props.navigation?.header || [])
const currentPath = computed(() => (page.url || '/').split('?')[0])
const origin = typeof window === 'undefined' ? 'http://localhost' : window.location.origin

function pathOf(url) {
    try {
        return new URL(url, origin).pathname
    } catch {
        return String(url)
    }
}

function isActive(url) {
    if (! url) {
        return false
    }

    const path = pathOf(url)
    if (path === localPath()) {
        return currentPath.value === path
    }

    return currentPath.value.startsWith(path)
}

function closeMenu() {
    open.value = false
}

function onScroll() {
    scrolled.value = window.scrollY > 8
}

watch(() => page.url, closeMenu)

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
})
</script>

<template>
    <header class="ps-header" :class="{ 'is-scrolled': scrolled }">
        <div class="ps-container ps-header__inner">
            <Link :href="localPath()" class="ps-brand" :aria-label="site.name || 'PCDR'" @click="closeMenu">
                <img :src="logo" width="48" height="48" :alt="site.shortName || 'PCDR'">
                <span class="ps-brand__text">
                    <strong>{{ site.shortName || 'PCDR' }}</strong>
                    <span>{{ site.name }}</span>
                </span>
            </Link>

            <nav id="public-nav" class="ps-nav" :class="{ 'is-open': open }" :aria-label="t('cms_menu_primary')">
                <ul class="ps-nav__list">
                    <li v-for="item in primary" :key="item.url">
                        <Link
                            :href="item.url"
                            class="ps-nav__link"
                            :class="{ 'is-active': isActive(item.url) }"
                            :aria-current="isActive(item.url) ? 'page' : undefined"
                            @click="closeMenu"
                        >
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
                <div class="ps-nav__extra">
                    <Link :href="localPath('get-involved')" class="ps-btn ps-btn-primary" @click="closeMenu">
                        {{ t('nav.involve') }}
                    </Link>
                    <div class="ps-nav__lang-row">
                        <span>{{ t('ui.switch_language') }}</span>
                        <LanguageSwitcher />
                    </div>
                </div>
            </nav>

            <div class="ps-header__actions">
                <LanguageSwitcher class="ps-header__lang" />
                <Link :href="localPath('get-involved')" class="ps-btn ps-btn-primary ps-header__cta">
                    {{ t('nav.involve') }}
                </Link>
                <button
                    class="ps-burger"
                    type="button"
                    :aria-expanded="String(open)"
                    aria-controls="public-nav"
                    :aria-label="open ? t('ui.close_menu') : t('ui.open_menu')"
                    @click="open = !open"
                >
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
</template>
