<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useInView } from '../../composables/useInView'

const props = defineProps({
    name: { type: String, default: 'olive' },
    image: { type: Object, default: null },
})

const root = ref(null)
const { isInView } = useInView(root)
const reduced = ref(false)
const finePointer = ref(false)
const introDone = ref(false)

const uid = `hm-${Math.random().toString(36).slice(2, 9)}`
const motif = computed(() => {
    const allowed = ['olive', 'sun', 'wheat', 'drop', 'book', 'scales', 'rings', 'hands', 'envelope']
    return allowed.includes(props.name) ? props.name : 'olive'
})

let mqlReduced = null
let mqlPointer = null
let onReduced = null
let onPointer = null
let introTimer = null

function updateReduced() {
    reduced.value = !!(mqlReduced && mqlReduced.matches)
}
function updatePointer() {
    finePointer.value = !!(mqlPointer && mqlPointer.matches)
}

function onMove(e) {
    if (reduced.value || !finePointer.value || !root.value) return
    const hero = root.value.closest('.ps-hero')
    if (!hero) return
    const r = hero.getBoundingClientRect()
    const x = ((e.clientX - r.left) / r.width - 0.5) * 12
    const y = ((e.clientY - r.top) / r.height - 0.5) * 12
    root.value.style.setProperty('--px', `${Math.max(-6, Math.min(6, x))}px`)
    root.value.style.setProperty('--py', `${Math.max(-6, Math.min(6, y))}px`)
}

function onLeave() {
    if (!root.value) return
    root.value.style.setProperty('--px', '0px')
    root.value.style.setProperty('--py', '0px')
}

function markIntroDone() {
    introDone.value = true
    if (!root.value) return
    root.value.querySelectorAll('.hm-draw, .hm-fill, .hm-intro').forEach((el) => {
        el.style.willChange = 'auto'
    })
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        mqlReduced = window.matchMedia('(prefers-reduced-motion: reduce)')
        mqlPointer = window.matchMedia('(hover: hover) and (pointer: fine)')
        updateReduced()
        updatePointer()
        onReduced = () => { updateReduced(); if (reduced.value) markIntroDone() }
        onPointer = () => updatePointer()
        mqlReduced.addEventListener?.('change', onReduced)
        mqlPointer.addEventListener?.('change', onPointer)
        const hero = root.value?.closest('.ps-hero')
        if (hero) {
            hero.addEventListener('pointermove', onMove, { passive: true })
            hero.addEventListener('pointerleave', onLeave, { passive: true })
        }
        // Remove will-change after intro completes
        introTimer = window.setTimeout(markIntroDone, 1800)
    }
})

onBeforeUnmount(() => {
    mqlReduced?.removeEventListener?.('change', onReduced)
    mqlPointer?.removeEventListener?.('change', onPointer)
    const hero = root.value?.closest('.ps-hero')
    if (hero) {
        hero.removeEventListener('pointermove', onMove)
        hero.removeEventListener('pointerleave', onLeave)
    }
    if (introTimer) window.clearTimeout(introTimer)
})

const playState = computed(() => (isInView.value && !reduced.value ? 'running' : 'paused'))
</script>

<template>
    <div
        ref="root"
        class="hero-motif"
        :class="[`hero-motif--${motif}`, { 'is-reduced': reduced, 'is-intro-done': introDone }]"
        :style="{ '--hm-play': playState }"
        aria-hidden="true"
    >
        <figure v-if="image?.src" class="hero-motif__photo">
            <img :src="image.src" :alt="image.alt || ''" loading="eager" decoding="async">
        </figure>

        <svg
            v-else
            class="hero-motif__svg"
            viewBox="0 0 200 200"
            fill="none"
            aria-hidden="true"
            focusable="false"
        >
            <!-- ABOUT: olive branch, 7 leaf pairs, sand tip -->
            <g v-if="motif === 'olive'" class="hm-art hm-art--olive">
                <g class="hm-idle-sway" style="transform-origin:100px 180px">
                    <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M100 178 C100 150 100 120 100 44" />
                    <!-- 7 pairs bottom to top -->
                    <g class="hm-leaf hm-intro" style="--i:1;transform-origin:100px 158px"><path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M100 158 C84 148 72 136 68 122 C82 124 92 132 100 142" /><path class="hm-draw hm-stroke" style="--i:2" pathLength="1" d="M100 158 C116 148 128 136 132 122 C118 124 108 132 100 142" /></g>
                    <g class="hm-leaf hm-intro" style="--i:2;transform-origin:100px 142px"><path class="hm-draw hm-stroke" style="--i:3" pathLength="1" d="M100 142 C85 133 74 122 70 110 C83 112 93 120 100 130" /><path class="hm-draw hm-stroke" style="--i:4" pathLength="1" d="M100 142 C115 133 126 122 130 110 C117 112 107 120 100 130" /></g>
                    <g class="hm-leaf hm-intro" style="--i:3;transform-origin:100px 126px"><path class="hm-draw hm-stroke" style="--i:5" pathLength="1" d="M100 126 C86 118 76 108 73 96 C85 98 94 105 100 114" /><path class="hm-draw hm-stroke" style="--i:6" pathLength="1" d="M100 126 C114 118 124 108 127 96 C115 98 106 105 100 114" /></g>
                    <g class="hm-leaf hm-intro" style="--i:4;transform-origin:100px 110px"><path class="hm-draw hm-stroke" style="--i:7" pathLength="1" d="M100 110 C87 103 78 94 75 84 C86 86 94 92 100 100" /><path class="hm-draw hm-stroke" style="--i:8" pathLength="1" d="M100 110 C113 103 122 94 125 84 C114 86 106 92 100 100" /></g>
                    <g class="hm-leaf hm-intro" style="--i:5;transform-origin:100px 94px"><path class="hm-draw hm-stroke" style="--i:9" pathLength="1" d="M100 94 C88 88 80 80 78 72 C88 74 95 79 100 86" /><path class="hm-draw hm-stroke" style="--i:10" pathLength="1" d="M100 94 C112 88 120 80 122 72 C112 74 105 79 100 86" /></g>
                    <g class="hm-leaf hm-intro" style="--i:6;transform-origin:100px 78px"><path class="hm-draw hm-stroke" style="--i:11" pathLength="1" d="M100 78 C90 73 84 66 82 58 C90 60 96 65 100 71" /><path class="hm-draw hm-stroke" style="--i:12" pathLength="1" d="M100 78 C110 73 116 66 118 58 C110 60 104 65 100 71" /></g>
                    <!-- tip pair (7th) - accent sand -->
                    <g class="hm-leaf hm-intro hm-accent-sand" style="--i:7;transform-origin:100px 62px">
                        <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:13" pathLength="1" d="M100 62 C92 56 87 49 86 42 C93 44 97 49 100 55" />
                        <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:14" pathLength="1" d="M100 62 C108 56 113 49 114 42 C107 44 103 49 100 55" />
                        <path class="hm-fill hm-fill--sand" d="M100 55 C96 50 92 46 86 42 C88 48 92 54 100 62 C108 54 112 48 114 42 C108 46 104 50 100 55 Z" />
                    </g>
                </g>
            </g>

            <!-- AXES: sun semicircle with exactly 11 rays across 180°, sand body -->
            <g v-else-if="motif === 'sun'" class="hm-art hm-art--sun">
                <g class="hm-sun-rise hm-intro">
                    <path class="hm-fill hm-fill--sand" d="M52 132 A48 48 0 0 1 148 132 Z" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:12" pathLength="1" d="M52 132 A48 48 0 0 1 148 132" />
                </g>
                <line class="hm-draw hm-stroke" style="--i:0" pathLength="1" x1="24" y1="132" x2="176" y2="132" />
                <g class="hm-rays">
                    <!-- 11 rays, evenly spaced across 180 degrees: 0,18,36,54,72,90,108,126,144,162,180 -->
                    <line v-for="(a, idx) in [180,162,144,126,108,90,72,54,36,18,0]" :key="idx" class="hm-draw hm-stroke hm-ray" :style="{'--i': idx + 1, '--ray': idx}" pathLength="1"
                        :x1="100 + Math.cos(a*Math.PI/180)*54" :y1="132 - Math.sin(a*Math.PI/180)*54"
                        :x2="100 + Math.cos(a*Math.PI/180)*82" :y2="132 - Math.sin(a*Math.PI/180)*82" />
                </g>
            </g>

            <!-- PROGRAMS: wheat stalk with 9 grains alternating sides, wheat accent -->
            <g v-else-if="motif === 'wheat'" class="hm-art hm-art--wheat">
                <g class="hm-idle-sway" style="transform-origin:100px 178px">
                    <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M100 178 L100 34" />
                    <g v-for="(g, idx) in [
                        {y:48, dir:1}, {y:62, dir:-1}, {y:76, dir:1}, {y:90, dir:-1}, {y:104, dir:1},
                        {y:118, dir:-1}, {y:132, dir:1}, {y:146, dir:-1}, {y:160, dir:1}
                    ]" :key="idx" class="hm-grain hm-intro" :style="{'--i': idx, transformOrigin: `100px ${g.y}px`}">
                        <path class="hm-draw hm-stroke hm-stroke--wheat" :style="{'--i': idx + 1}" pathLength="1"
                            :d="`M100 ${g.y} C${100+g.dir*12} ${g.y-3} ${100+g.dir*20} ${g.y-10} ${100+g.dir*18} ${g.y-20} C${100+g.dir*11} ${g.y-18} ${100+g.dir*5} ${g.y-12} 100 ${g.y-8}`" />
                        <path class="hm-fill hm-fill--wheat" :d="`M100 ${g.y} C${100+g.dir*11} ${g.y-3} ${100+g.dir*19} ${g.y-10} ${100+g.dir*18} ${g.y-20} C${100+g.dir*11} ${g.y-17} ${100+g.dir*5} ${g.y-11} 100 ${g.y-7} Z`" />
                    </g>
                </g>
            </g>

            <!-- NEWS: water drop with two ripples, teal fill -->
            <g v-else-if="motif === 'drop'" class="hm-art hm-art--drop">
                <g class="hm-drop-fall hm-intro">
                    <path class="hm-fill hm-fill--teal" d="M100 34 C100 34 62 86 62 110 C62 131 79 148 100 148 C121 148 138 131 138 110 C138 86 100 34 100 34 Z" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:0" pathLength="1" d="M100 34 C100 34 62 86 62 110 C62 131 79 148 100 148 C121 148 138 131 138 110 C138 86 100 34 100 34 Z" />
                </g>
                <path class="hm-draw hm-stroke hm-ripple hm-ripple--1" style="--i:1" pathLength="1" d="M58 160 C72 152 86 152 100 160 C114 168 128 168 142 160" />
                <path class="hm-draw hm-stroke hm-ripple hm-ripple--2" style="--i:2" pathLength="1" d="M46 174 C64 164 82 164 100 174 C118 184 136 184 154 174" />
            </g>

            <!-- REPORTS: open book, 4 lines/page, teal highlight -->
            <g v-else-if="motif === 'book'" class="hm-art hm-art--book">
                <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M100 52 L100 156" />
                <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M100 56 C82 46 58 46 38 52 L38 150 C58 144 82 144 100 156" />
                <g class="hm-page-turn hm-intro" style="transform-origin:100px 100px">
                    <path class="hm-draw hm-stroke" style="--i:2" pathLength="1" d="M100 56 C118 46 142 46 162 52 L162 150 C142 144 118 144 100 156" />
                    <path class="hm-draw hm-stroke hm-book-line" style="--i:7" pathLength="1" d="M112 78 L150 78" />
                    <path class="hm-draw hm-stroke hm-stroke--accent hm-book-line" style="--i:8" pathLength="1" d="M112 92 L150 92" />
                    <path class="hm-draw hm-stroke hm-book-line" style="--i:9" pathLength="1" d="M112 106 L146 106" />
                    <path class="hm-draw hm-stroke hm-book-line" style="--i:10" pathLength="1" d="M112 120 L142 120" />
                </g>
                <path class="hm-draw hm-stroke hm-book-line" style="--i:3" pathLength="1" d="M50 78 L88 78" />
                <path class="hm-draw hm-stroke hm-book-line" style="--i:4" pathLength="1" d="M50 92 L88 92" />
                <path class="hm-draw hm-stroke hm-book-line" style="--i:5" pathLength="1" d="M50 106 L84 106" />
                <path class="hm-draw hm-stroke hm-book-line" style="--i:6" pathLength="1" d="M50 120 L80 120" />
            </g>

            <!-- TRANSPARENCY: balanced scale + teal check in left pan -->
            <g v-else-if="motif === 'scales'" class="hm-art hm-art--scales">
                <g class="hm-balance hm-intro" style="transform-origin:100px 44px">
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M44 62 L156 62" />
                    <path class="hm-draw hm-stroke" style="--i:3" pathLength="1" d="M44 62 L30 104 C30 116 40 124 52 124 C64 124 74 116 74 104 Z" />
                    <path class="hm-draw hm-stroke" style="--i:4" pathLength="1" d="M156 62 L142 104 C142 116 152 124 164 124 C176 124 186 116 186 104 Z" />
                    <path class="hm-draw hm-stroke hm-stroke--accent hm-check" style="--i:7" pathLength="1" d="M44 110 L50 116 L62 102" />
                </g>
                <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M100 44 L100 154" />
                <circle class="hm-draw hm-stroke" style="--i:2" pathLength="1" cx="100" cy="44" r="5" />
                <path class="hm-draw hm-stroke" style="--i:5" pathLength="1" d="M76 154 L124 154 L116 164 L84 164 Z" />
                <path class="hm-draw hm-stroke" style="--i:6" pathLength="1" d="M30 104 L74 104 M142 104 L186 104" />
            </g>

            <!-- PARTNERSHIPS: interlocking chain rings + teal drop -->
            <g v-else-if="motif === 'rings'" class="hm-art hm-art--rings">
                <defs>
                    <mask :id="`${uid}-weave`">
                        <rect x="0" y="0" width="200" height="200" fill="white" />
                        <!-- 6-unit gap: ring A passes under ring B at bottom crossing -->
                        <rect x="92" y="116" width="16" height="14" fill="black" />
                    </mask>
                </defs>
                <!-- Ring B (right) - full, ~92x64 double contour, 10-unit thickness -->
                <g class="hm-ring-right hm-intro">
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M108 68 H156 A22 22 0 0 1 178 90 V110 A22 22 0 0 1 156 132 H108 A22 22 0 0 1 86 110 V90 A22 22 0 0 1 108 68 Z" />
                    <path class="hm-draw hm-stroke" style="--i:2" pathLength="1" d="M108 78 H156 A12 12 0 0 1 168 90 V110 A12 12 0 0 1 156 122 H108 A12 12 0 0 1 96 110 V90 A12 12 0 0 1 108 78 Z" />
                </g>
                <!-- Ring A (left) - masked at bottom crossing for weave -->
                <g class="hm-ring-left hm-intro" :mask="`url(#${uid}-weave)`">
                    <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M44 68 H92 A22 22 0 0 1 114 90 V110 A22 22 0 0 1 92 132 H44 A22 22 0 0 1 22 110 V90 A22 22 0 0 1 44 68 Z" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M44 78 H92 A12 12 0 0 1 104 90 V110 A12 12 0 0 1 92 122 H44 A12 12 0 0 1 32 110 V90 A12 12 0 0 1 44 78 Z" />
                </g>
                <!-- Accent: teal drop (12x16) centered at link + highlight + ripple -->
                <g class="hm-rings-drop hm-intro">
                    <path class="hm-fill hm-fill--teal" d="M100 92 C100 92 94 98.5 94 102.5 C94 105.8 96.7 108 100 108 C103.3 108 106 105.8 106 102.5 C106 98.5 100 92 100 92 Z" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:3" pathLength="1" d="M100 92 C100 92 94 98.5 94 102.5 C94 105.8 96.7 108 100 108 C103.3 108 106 105.8 106 102.5 C106 98.5 100 92 100 92 Z" />
                    <path class="hm-draw hm-stroke" style="--i:4;stroke:rgba(255,255,255,0.85);stroke-width:1.5" pathLength="1" d="M97.5 103 C98 104.5 98.8 105.3 100 105.5" />
                </g>
                <circle class="hm-draw hm-stroke hm-stroke--accent hm-rings-ripple" style="--i:5" pathLength="1" cx="100" cy="100" r="10" />
            </g>

            <!-- JOIN: two cupped hands holding a seedling -->
            <g v-else-if="motif === 'hands'" class="hm-art hm-art--hands">
                <!-- Seedling: stem + leaves + optional wheat sun dot -->
                <g class="hm-seedling-sway hm-intro" style="transform-origin:100px 138px">
                    <path class="hm-draw hm-stroke hm-seedling-stem" style="--i:2" pathLength="1" d="M100 138 L100 74" />
                    <g class="hm-leaf-l hm-intro" style="transform-origin:100px 96px">
                        <path class="hm-fill hm-fill--sand" d="M100 96 C90 92 80 84 78 74 C88 76 95 84 100 92 Z" />
                        <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:3" pathLength="1" d="M100 96 C90 92 80 84 78 74 C88 76 95 84 100 92 Z" />
                    </g>
                    <g class="hm-leaf-r hm-intro" style="transform-origin:100px 96px">
                        <path class="hm-fill hm-fill--sand" d="M100 96 C110 92 120 84 122 74 C112 76 105 84 100 92 Z" />
                        <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:4" pathLength="1" d="M100 96 C110 92 120 84 122 74 C112 76 105 84 100 92 Z" />
                    </g>
                    <circle class="hm-fill hm-fill--sand hm-sun-dot hm-intro" cx="100" cy="62" r="3" />
                </g>

                <!-- Left hand: cupped upward, wrist lower outside, fingers curve up/inward -->
                <g class="hm-hand-left hm-intro">
                    <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M32 158 C28 148 30 138 38 130 C46 122 58 118 70 116 C80 114 88 116 95 122 L95 138 C86 136 76 136 66 138 C54 141 44 148 40 156 Z" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M44 128 C48 120 54 114 62 110 C70 106 78 106 86 110" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M50 122 C54 114 60 108 68 106 C74 104 80 105 86 108" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M38 136 C42 128 50 122 60 119" />
                    <path class="hm-draw hm-stroke" style="--i:2" pathLength="1" d="M72 114 C74 106 78 100 84 98 C88 100 90 104 90 110" />
                </g>

                <!-- Right hand: mirror -->
                <g class="hm-hand-right hm-intro">
                    <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M168 158 C172 148 170 138 162 130 C154 122 142 118 130 116 C120 114 112 116 105 122 L105 138 C114 136 124 136 134 138 C146 141 156 148 160 156 Z" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M156 128 C152 120 146 114 138 110 C130 106 122 106 114 110" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M150 122 C146 114 140 108 132 106 C126 104 120 105 114 108" />
                    <path class="hm-draw hm-stroke" style="--i:1" pathLength="1" d="M162 136 C158 128 150 122 140 119" />
                    <path class="hm-draw hm-stroke" style="--i:2" pathLength="1" d="M128 114 C126 106 122 100 116 98 C112 100 110 104 110 110" />
                </g>
            </g>

            <!-- CONTACT: envelope with letter, teal lines -->
            <g v-else-if="motif === 'envelope'" class="hm-art hm-art--envelope">
                <g class="hm-letter hm-intro">
                    <rect class="hm-draw hm-stroke" style="--i:1" pathLength="1" x="58" y="44" width="84" height="64" rx="2" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:2" pathLength="1" d="M70 60 L130 60" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:3" pathLength="1" d="M70 72 L122 72" />
                    <path class="hm-draw hm-stroke hm-stroke--accent" style="--i:4" pathLength="1" d="M70 84 L114 84" />
                </g>
                <path class="hm-draw hm-stroke" style="--i:0" pathLength="1" d="M34 86 L34 156 L166 156 L166 86 Z" />
                <path class="hm-draw hm-stroke" style="--i:5" pathLength="1" d="M34 156 L82 118 M166 156 L118 118" />
                <g class="hm-flap hm-intro" style="transform-origin:100px 86px">
                    <path class="hm-draw hm-stroke" style="--i:6" pathLength="1" d="M34 86 L100 132 L166 86" />
                </g>
            </g>
        </svg>
    </div>
</template>
