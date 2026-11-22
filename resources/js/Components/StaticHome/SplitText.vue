<script setup>
import { computed } from 'vue'

const props = defineProps({
    text: { type: String, required: true },
})

/**
 * Reproduces fx.js split() without injecting HTML: every word receives the
 * same .w > span structure and --k stagger index used by the approved CSS.
 */
const lines = computed(() => {
    let index = 0

    return String(props.text)
        .split('\n')
        .map((line) => line.trim().split(/\s+/).filter(Boolean).map((word) => ({
            word,
            key: index++,
        })))
})
</script>

<template>
    <template v-for="(line, lineIndex) in lines" :key="`line-${lineIndex}`">
        <br v-if="lineIndex">
        <template v-for="item in line" :key="item.key">
            <span class="w"><span :style="{ '--k': item.key }">{{ item.word }}</span></span>{{ ' ' }}
        </template>
    </template>
</template>
