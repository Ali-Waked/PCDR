import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import '../css/app.css'

function applyDocumentLocale(props = {}) {
    const locale = props.locale || 'ar'
    const direction = props.direction || (locale === 'ar' ? 'rtl' : 'ltr')
    document.documentElement.lang = locale
    document.documentElement.dir = direction
}

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        applyDocumentLocale(props.initialPage?.props)
        router.on('after', (event) => applyDocumentLocale(event.detail?.page?.props))

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
