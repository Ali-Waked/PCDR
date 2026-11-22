import { onBeforeUnmount, onMounted, ref } from 'vue'

let sharedObserver = null
const callbacks = new WeakMap()

function getObserver() {
    if (sharedObserver || typeof IntersectionObserver === 'undefined') return sharedObserver
    sharedObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const cb = callbacks.get(entry.target)
            if (cb) cb(entry.isIntersecting)
        })
    }, { threshold: 0.01 })
    return sharedObserver
}

export function useInView(targetRef) {
    const isInView = ref(true)
    let lastIntersecting = true

    const sync = () => {
        isInView.value = lastIntersecting && !(typeof document !== 'undefined' && document.hidden)
    }
    const onIntersect = (visible) => { lastIntersecting = visible; sync() }
    const onVisibility = () => sync()

    onMounted(() => {
        const el = targetRef.value
        const obs = getObserver()
        if (el && obs) {
            callbacks.set(el, onIntersect)
            obs.observe(el)
        }
        if (typeof document !== 'undefined') document.addEventListener('visibilitychange', onVisibility, { passive: true })
    })

    onBeforeUnmount(() => {
        const el = targetRef.value
        const obs = getObserver()
        if (el && obs) { obs.unobserve(el); callbacks.delete(el) }
        if (typeof document !== 'undefined') document.removeEventListener('visibilitychange', onVisibility)
    })

    return { isInView }
}
