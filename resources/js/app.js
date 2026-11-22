import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import '../css/app.css'

const reducedMotion = typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches

if (typeof document !== 'undefined') {
    document.documentElement.classList.add('js-motion')
}

function syncDocument(props) {
    const locale = props?.locale ?? document.documentElement.lang ?? 'ar'
    const direction = props?.direction ?? (locale === 'ar' ? 'rtl' : 'ltr')

    document.documentElement.lang = locale
    document.documentElement.dir = direction
    document.documentElement.dataset.locale = locale
}

let revealObserver = null

function observer() {
    if (revealObserver || reducedMotion || ! ('IntersectionObserver' in window)) {
        return revealObserver
    }

    revealObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (! entry.isIntersecting) {
                continue
            }

            entry.target.classList.add('is-visible')
            revealObserver.unobserve(entry.target)
        }
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.04 })

    return revealObserver
}

const revealDirective = {
    mounted(el) {
        el.classList.add('reveal')

        if (reducedMotion || ! ('IntersectionObserver' in window)) {
            el.classList.add('is-visible')
            return
        }

        observer()?.observe(el)
    },
    unmounted(el) {
        revealObserver?.unobserve(el)
    },
}

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        syncDocument(props.initialProps)

        router.on('navigate', (event) => {
            syncDocument(event.detail.page.props)
        })

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .directive('reveal', revealDirective)
            .mount(el)
    },
})
