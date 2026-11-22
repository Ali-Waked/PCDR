<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import LanguageSwitcher from '../LanguageSwitcher.vue'
import { useI18n } from '../../composables/useI18n'
import { usePublicNavigation } from '../../composables/usePublicNavigation'

const { page, t, site, localPath, locale } = useI18n()
const { links, cta } = usePublicNavigation()

const logo = '/assets/pcdr-homepage/logo/pcdr-logo.png'
const open = ref(false)
const moreOpen = ref(false)
const scrolled = ref(false)
const moreRef = ref(null)
const currentPath = computed(() => (page.url || '/').split('?')[0])
const origin = typeof window === 'undefined' ? 'http://localhost' : window.location.origin

const visibleLinks = computed(() => links.value.slice(0, 6))
const moreLinks = computed(() => links.value.slice(6))
const moreActive = computed(() => moreLinks.value.some((item) => isActive(item.url)))

function pathOf(url) {
    try {
        return new URL(url, origin).pathname
    } catch {
        return String(url)
    }
}

function isActive(url) {
    if (!url) return false
    const path = pathOf(url)
    const home = localPath()
    if (path === home) return currentPath.value === path
    return currentPath.value === path || currentPath.value.startsWith(path + '/')
}

function closeMenu() {
    open.value = false
    moreOpen.value = false
}

function toggleMore() {
    moreOpen.value = !moreOpen.value
}

function onScroll() {
    scrolled.value = window.scrollY > 8
}

function onDocClick(event) {
    if (moreRef.value && !moreRef.value.contains(event.target)) {
        moreOpen.value = false
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (moreOpen.value) {
            moreOpen.value = false
            moreRef.value?.querySelector('button')?.focus()
            return
        }
        if (open.value) {
            open.value = false
            document.querySelector('.ps-burger')?.focus()
        }
    }
}

watch(() => page.url, closeMenu)
watch(open, (value) => {
    if (typeof document !== 'undefined') document.body.classList.toggle('ps-menu-open', value)
    if (!value) moreOpen.value = false
})

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    document.addEventListener('click', onDocClick)
    document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
    document.removeEventListener('click', onDocClick)
    document.removeEventListener('keydown', onKeydown)
    if (typeof document !== 'undefined') document.body.classList.remove('ps-menu-open')
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

            <nav id="public-nav" class="ps-nav" :class="{ 'is-open': open }" :aria-label="t('ui.main_navigation', t('cms_menu_primary'))">
                <ul class="ps-nav__list">
                    <li v-for="item in visibleLinks" :key="item.key" class="ps-nav__item">
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
                    <li v-if="moreLinks.length" ref="moreRef" class="ps-nav__item ps-nav__more">
                        <button
                            type="button"
                            class="ps-nav__link ps-nav__more-btn"
                            :class="{ 'is-active': moreActive }"
                            :aria-expanded="String(moreOpen)"
                            aria-haspopup="true"
                            @click="toggleMore"
                        >
                            {{ t('ui.more', locale === 'en' ? 'More' : 'المزيد') }}
                            <svg class="ps-nav__more-caret" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <ul v-show="moreOpen" class="ps-nav__more-menu" role="menu">
                            <li v-for="item in moreLinks" :key="item.key" role="none">
                                <Link
                                    :href="item.url"
                                    class="ps-nav__more-link"
                                    :class="{ 'is-active': isActive(item.url) }"
                                    role="menuitem"
                                    :aria-current="isActive(item.url) ? 'page' : undefined"
                                    @click="closeMenu"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </li>
                    <!-- mobile-only: show all links in panel -->
                    <template v-for="item in moreLinks" :key="`m-${item.key}`">
                        <li class="ps-nav__item ps-nav__item--mobile-only">
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
                    </template>
                </ul>
                <div class="ps-nav__extra">
                    <Link :href="cta.url" class="ps-btn ps-btn-primary" @click="closeMenu">{{ cta.label }}</Link>
                    <div class="ps-nav__lang-row">
                        <span>{{ t('ui.switch_language', locale === 'en' ? 'Language' : 'تغيير اللغة') }}</span>
                        <LanguageSwitcher />
                    </div>
                </div>
            </nav>

            <div class="ps-header__actions">
                <LanguageSwitcher class="ps-header__lang" />
                <Link :href="cta.url" class="ps-btn ps-btn-primary ps-header__cta">{{ cta.label }}</Link>
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
