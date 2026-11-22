import { onMounted, ref } from 'vue'

export function useHeroEntrance() {
    const ready = ref(false)

    onMounted(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            ready.value = true
            return
        }

        requestAnimationFrame(() => {
            ready.value = true
        })
    })

    return { ready }
}
