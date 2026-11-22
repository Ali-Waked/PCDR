<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    items: { type: Array, required: true },
    label: { type: String, default: 'About sections' },
    initial: { type: String, default: '' },
})

const active = ref(props.initial || props.items[0]?.key || '')
const navRef = ref(null)
let observer

function scrollTo(key, pushHash = true) {
    const item = props.items.find((i) => i.key === key)
    if (!item) return
    const el = document.getElementById(item.id)
    if (!el) return
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    el.scrollIntoView({ behavior: prefersReduced ? 'auto' : 'smooth', block: 'start' })
    active.value = key
    if (pushHash) history.replaceState(null, '', `#${item.id}`)
    scrollTabIntoView(key)
}

function scrollTabIntoView(key) {
    const btn = navRef.value?.querySelector(`[data-key="${CSS.escape(key)}"]`)
    btn?.scrollIntoView?.({ inline: 'nearest', block: 'nearest', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
}

function onClick(item, event) {
    event.preventDefault()
    scrollTo(item.key)
}

onMounted(() => {
    const hash = window.location.hash.replace('#', '')
    if (hash) {
        const match = props.items.find((i) => i.id === hash)
        if (match) {
            active.value = match.key
            requestAnimationFrame(() => scrollTo(match.key, false))
        }
    }

    const ids = props.items.map((i) => i.id).filter(Boolean)
    const elements = ids.map((id) => document.getElementById(id)).filter(Boolean)
    if (!elements.length) return

    observer = new IntersectionObserver((entries) => {
        const visible = entries
            .filter((e) => e.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]
        if (visible) {
            const match = props.items.find((i) => i.id === visible.target.id)
            if (match && match.key !== active.value) {
                active.value = match.key
                history.replaceState(null, '', `#${match.id}`)
                scrollTabIntoView(match.key)
            }
        }
    }, { rootMargin: '-20% 0px -70% 0px', threshold: [0, 0.1] })

    elements.forEach((el) => observer.observe(el))
})

onBeforeUnmount(() => observer?.disconnect())

watch(() => props.initial, (val) => { if (val) active.value = val })
</script>

<template>
    <nav ref="navRef" class="ps-subnav" :aria-label="label">
        <div class="ps-container ps-subnav__inner">
            <a
                v-for="(item, index) in items"
                :key="item.key"
                :href="`#${item.id}`"
                :data-key="item.key"
                class="ps-subnav__link"
                :class="{ 'is-active': active === item.key }"
                :aria-current="active === item.key ? 'true' : undefined"
                @click="onClick(item, $event)"
            >
                <span class="ps-subnav__num">{{ String(index + 1).padStart(2, '0') }} ·</span>
                <span class="ps-subnav__label">{{ item.label }}</span>
            </a>
        </div>
    </nav>
</template>
