<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import LanguageSwitcher from '../LanguageSwitcher.vue'
import { useI18n } from '../../composables/useI18n'
import { usePublicNavigation } from '../../composables/usePublicNavigation'

const props = defineProps({
    transparent: { type: Boolean, default: false },
})

const { page, t, site, localPath } = useI18n()
const { links, cta } = usePublicNavigation()

const logo = '/assets/pcdr-homepage/logo/pcdr-logo.png'
const open = ref(false)
const scrolled = ref(false)
const navListRef = ref(null)
const ink = ref({ x: 0, w: 0, visible: false, instant: false })
const hoveredKey = ref(null)
const reducedMotion = ref(false)

const currentPath = computed(() => (page.url || '/').split('?')[0])
const origin = typeof window === 'undefined' ? '' : window.location.origin
const isTransparent = computed(() => props.transparent && !scrolled.value && !open.value)

function pathOf(url) {
    try { return new URL(url, origin).pathname } catch { return String(url) }
}

function isActive(url) {
    if (!url) return false
    const path = pathOf(url)
    const home = localPath()
    if (path === home) return currentPath.value === path
    return currentPath.value === path || currentPath.value.startsWith(path + '/')
}

const activeKey = computed(() => links.value.find((l) => isActive(l.url))?.key || null)
const inkTargetKey = computed(() => hoveredKey.value || activeKey.value)

function closeMenu() { open.value = false }

function onScroll() { scrolled.value = window.scrollY > 8 }

const NAV_BP = 1280

function updateInk(instant = false) {
    if (typeof window === 'undefined') return
    const list = navListRef.value
    if (!list || window.innerWidth < NAV_BP) { ink.value = { ...ink.value, visible: false, instant }; return }
    const key = inkTargetKey.value
    if (!key) { ink.value = { ...ink.value, visible: false, instant }; return }
    const link = list.querySelector(`[data-nav-key="${key}"]`)
    if (!link || !link.offsetParent) { ink.value = { ...ink.value, visible: false, instant }; return }
    const listRect = list.getBoundingClientRect()
    const rect = link.getBoundingClientRect()
    const x = rect.left - listRect.left
    ink.value = { x, w: rect.width, visible: true, instant: instant || reducedMotion.value }
}

function onHover(key) { hoveredKey.value = key; updateInk() }
function onLeave() { hoveredKey.value = null; updateInk() }

let ro = null
let mql = null
let onMql = null
let onResize = null

function onKeydown(event) {
    if (event.key === 'Escape' && open.value) {
        open.value = false
        document.querySelector('.ps-burger')?.focus()
    }
}

watch(() => page.url, () => { closeMenu(); nextTick(() => updateInk(true)) })
watch(open, (value) => {
    if (typeof document !== 'undefined') document.body.classList.toggle('ps-menu-open', value)
    if (!value) nextTick(() => updateInk(true))
})
watch([activeKey, () => links.value], () => nextTick(() => updateInk(true)), { deep: true })

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    document.addEventListener('keydown', onKeydown)
    mql = window.matchMedia('(prefers-reduced-motion: reduce)')
    reducedMotion.value = mql.matches
    onMql = (e) => { reducedMotion.value = e.matches; updateInk(true) }
    mql.addEventListener?.('change', onMql)
    nextTick(() => updateInk(true))
    onResize = () => updateInk(true)
    if (typeof ResizeObserver !== 'undefined' && navListRef.value) {
        ro = new ResizeObserver(() => updateInk(true))
        ro.observe(navListRef.value)
    }
    window.addEventListener('resize', onResize, { passive: true })
    if (document.fonts?.ready) document.fonts.ready.then(() => updateInk(true))
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
    document.removeEventListener('keydown', onKeydown)
    window.removeEventListener('resize', onResize)
    ro?.disconnect()
    mql?.removeEventListener?.('change', onMql)
    if (typeof document !== 'undefined') document.body.classList.remove('ps-menu-open')
})
</script>

<template>
    <header class="ps-header" :class="{ 'is-scrolled': scrolled, 'is-transparent': isTransparent, 'is-open': open }">
        <div class="ps-container ps-header__inner">
            <Link :href="localPath()" class="ps-brand" :aria-label="site.name || 'PCDR'" @click="closeMenu">
                <img :src="logo" width="48" height="48" :alt="site.shortName || 'PCDR'">
                <span class="ps-brand__text">
                    <strong>{{ site.shortName || 'PCDR' }}</strong>
                    <span>{{ site.name }}</span>
                </span>
            </Link>

            <nav id="public-nav" class="ps-nav" :class="{ 'is-open': open }" :aria-label="t('ui.main_navigation', t('cms_menu_primary'))">
                <ul ref="navListRef" class="ps-nav__list" @mouseleave="onLeave">
                    <span
                        class="ps-nav__ink"
                        :class="{ 'is-visible': ink.visible, 'is-instant': ink.instant }"
                        :style="{ transform: `translateX(${ink.x}px)`, width: `${ink.w}px` }"
                        aria-hidden="true"
                    ></span>
                    <li v-for="(item, i) in links" :key="item.key" class="ps-nav__item" :style="{ '--nav-i': i }">
                        <a
                            v-if="item.external"
                            :href="item.url"
                            class="ps-nav__link"
                            :data-nav-key="item.key"
                            target="_blank"
                            rel="noopener noreferrer"
                            @mouseenter="onHover(item.key)"
                            @focus="onHover(item.key)"
                            @blur="onLeave"
                            @click="closeMenu"
                        >
                            <span class="ps-nav__label ps-nav__label--full">{{ item.label }}</span>
                            <span class="ps-nav__label ps-nav__label--short" aria-hidden="true">{{ item.shortLabel }}</span>
                            <span class="ps-nav__active-dot" aria-hidden="true"></span>
                            <span class="ps-nav__mobile-bar" aria-hidden="true"></span>
                            <span class="ps-nav__mobile-dot" aria-hidden="true"></span>
                        </a>
                        <Link
                            v-else
                            :href="item.url"
                            class="ps-nav__link"
                            :data-nav-key="item.key"
                            :aria-current="isActive(item.url) ? 'page' : undefined"
                            @mouseenter="onHover(item.key)"
                            @focus="onHover(item.key)"
                            @blur="onLeave"
                            @click="closeMenu"
                        >
                            <span class="ps-nav__label ps-nav__label--full">{{ item.label }}</span>
                            <span class="ps-nav__label ps-nav__label--short" aria-hidden="true">{{ item.shortLabel }}</span>
                            <span class="ps-nav__active-dot" aria-hidden="true"></span>
                            <span class="ps-nav__mobile-bar" aria-hidden="true"></span>
                            <span class="ps-nav__mobile-dot" aria-hidden="true"></span>
                        </Link>
                    </li>
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
