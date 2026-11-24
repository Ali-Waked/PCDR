import assert from 'node:assert/strict'
import fs from 'node:fs'
import path from 'node:path'
import test from 'node:test'
import { fileURLToPath } from 'node:url'

import {
    buildPageItems,
    buildPageUrl,
    normalizePaginator,
    pageUrlFor,
    plainText,
    renderSummary,
} from '../resources/js/Support/pagination.js'
import {
    LANGUAGE_OPTIONS,
    SUPPORTED_LOCALES,
    isActiveLocale,
    localizedUrl,
    normalizeLocale,
} from '../resources/js/Support/languageSwitcher.js'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8')

function walk(directory, extension, files = []) {
    const absolute = path.join(root, directory)
    if (! fs.existsSync(absolute)) {
        return files
    }

    for (const entry of fs.readdirSync(absolute, { withFileTypes: true })) {
        const relativePath = path.join(directory, entry.name)
        if (entry.isDirectory()) {
            walk(relativePath, extension, files)
        } else if (entry.name.endsWith(extension)) {
            files.push(relativePath)
        }
    }

    return files
}

test('pagination adapter reads full Laravel paginators and preserves linked filters', () => {
    const paginator = normalizePaginator({
        current_page: 3,
        last_page: 9,
        per_page: 15,
        total: 126,
        from: 31,
        to: 45,
        links: [
            { url: '/ar/admin/users?search=omar&page=2', label: '&laquo; Previous', active: false, page: 2 },
            { url: null, label: '&hellip;', active: false },
            { url: '/ar/admin/users?search=omar&page=3', label: '3', active: true, page: 3 },
            { url: '/ar/admin/users?search=omar&page=4', label: '4', active: false, page: 4 },
            { url: '/ar/admin/users?search=omar&page=9', label: '9', active: false, page: 9 },
        ],
    })

    assert.equal(paginator.currentPage, 3)
    assert.equal(paginator.lastPage, 9)
    assert.equal(paginator.perPage, 15)
    assert.equal(paginator.total, 126)
    assert.equal(paginator.previousUrl, '/ar/admin/users?search=omar&page=2')
    assert.equal(pageUrlFor(paginator, 4, '/ar/admin/users'), '/ar/admin/users?search=omar&page=4')
    assert.equal(pageUrlFor(paginator, 6, '/ar/admin/users?search=omar&page=3'), '/ar/admin/users?search=omar&page=6')
})

test('pagination adapter reads camelCase, page-map and nested-meta shapes', () => {
    const camel = normalizePaginator({ currentPage: 2, lastPage: 5, total: 50, perPage: 10, from: 11, to: 20 })

    assert.equal(camel.currentPage, 2)
    assert.equal(camel.from, 11)
    assert.equal(camel.to, 20)

    const map = normalizePaginator({
        currentPage: 2,
        lastPage: 4,
        links: { 1: '/ar/news?page=1', 2: '/ar/news?page=2', 4: '/ar/news?page=4' },
    })

    assert.equal(map.currentPage, 2)
    assert.equal(map.lastPage, 4)
    assert.equal(pageUrlFor(map, 4, '/ar/news'), '/ar/news?page=4')

    const nested = normalizePaginator({
        data: [],
        meta: { current_page: 2, last_page: 7, per_page: 12, total: 80, from: 13, to: 24 },
    })

    assert.equal(nested.currentPage, 2)
    assert.equal(nested.lastPage, 7)
    assert.equal(nested.perPage, 12)
    assert.equal(nested.from, 13)
})

test('buildPageItems adds real ellipses and keeps first and last pages', () => {
    assert.deepEqual(buildPageItems(1, 4), [
        { type: 'page', number: 1 },
        { type: 'page', number: 2 },
        { type: 'page', number: 3 },
        { type: 'page', number: 4 },
    ])

    const items = buildPageItems(5, 12)
    assert.deepEqual(items.map((item) => item.type === 'page' ? item.number : '…'), [1, '…', 4, 5, 6, '…', 12])
    assert.ok(items.some((item) => item.type === 'ellipsis' && item.key.startsWith('ellipsis-')))
})

test('buildPageUrl preserves filters, clears page-one state and honours custom page keys', () => {
    assert.equal(
        buildPageUrl('/ar/operations/phase5/workflows?status=active&definitionPage=3&per_page=25#list', 4, 'definitionPage'),
        '/ar/operations/phase5/workflows?status=active&definitionPage=4&per_page=25#list',
    )

    assert.equal(
        buildPageUrl('/ar/news?category=health&page=2&q=water', 1, 'page'),
        '/ar/news?category=health&q=water',
    )

    assert.equal(
        buildPageUrl('/ar/operations/phase5/workflows?definitionPage=2&instancePage=4&per_page=25', 3, 'definitionPage'),
        '/ar/operations/phase5/workflows?definitionPage=3&instancePage=4&per_page=25',
    )
})

test('summaries use real metadata with locale-aware numbers', () => {
    assert.equal(
        renderSummary('Showing :from–:to of :total', { from: 1234, to: 2000, total: 4567, locale: 'en' }),
        'Showing 1,234–2,000 of 4,567',
    )
    assert.equal(plainText('&laquo; Previous'), '« Previous')
})

test('language helper treats the active locale as a true no-op', () => {
    assert.deepEqual(SUPPORTED_LOCALES, ['ar', 'en'])
    assert.equal(normalizeLocale('EN'), 'en')
    assert.equal(normalizeLocale('fr'), null)
    assert.equal(isActiveLocale('ar', 'ar'), true)
    assert.equal(isActiveLocale('ar', 'en'), false)

    assert.equal(localizedUrl('/ar/admin/users?search=omar&page=2#list', 'ar', 'ar'), null)
    assert.equal(localizedUrl('/ar/admin/users?search=omar&page=2#list', 'ar', 'de'), null)

    assert.equal(
        localizedUrl('/ar/admin/users?search=omar&page=2#list', 'ar', 'en'),
        '/en/admin/users?search=omar&page=2#list',
    )
    assert.equal(localizedUrl('/login', '', 'ar'), '/ar/login')
    assert.equal(LANGUAGE_OPTIONS.map((option) => option.code).join(','), 'ar,en')
})

test('notifications render through one opaque, semantic, accessible surface', () => {
    const css = read('resources/css/dashboard.css')
    const toast = read('resources/js/Components/Dashboard/AdminToast.vue')
    const provider = read('resources/js/Components/Dashboard/AdminToastProvider.vue')
    const store = read('resources/js/composables/useAdminNotifications.js')

    for (const variant of ['success', 'error', 'warning', 'info', 'loading']) {
        assert.match(css, new RegExp(`\\.pc-toast--${variant}\\b`), `${variant} needs a semantic toast surface`)
    }

    assert.match(css, /\.pc-toast\s*\{[^}]*background:\s*var\(--pc-toast-surface\)/)
    assert.match(css, /--pc-toast-surface:\s*#ffffff/)
    assert.match(css, /\.pc-modal\s*\{[^}]*background:\s*#ffffff/)
    assert.match(css, /:root,\s*\.pc-app,\s*\.pc-auth/)
    assert.match(css, /z-index:\s*1300/)
    assert.match(css, /@media \(prefers-reduced-motion: reduce\)/)

    assert.match(toast, /:data-variant="item\.type"/)
    assert.match(toast, /:aria-busy="item\.type === 'loading' \? 'true' : 'false'"/)
    assert.match(provider, /:aria-label="labels\.notificationsTitle"/)
    assert.match(provider, /aria-atomic="false"/)
    assert.match(store, /notificationsTitle:/)

    const MAX_VISIBLE = store.match(/const MAX_VISIBLE = (\d+)/)?.[1]
    assert.equal(MAX_VISIBLE, '5')
})

test('language switchers share one guarded component and localized URL helper', () => {
    const chrome = read('resources/js/Components/Dashboard/DashboardChrome.vue')
    const auth = read('resources/js/Layouts/AuthLayout.vue')
    const shared = read('resources/js/Components/LanguageSwitcher.vue')
    const i18n = read('resources/js/composables/useI18n.js')

    for (const source of [chrome, auth]) {
        assert.match(source, /<LanguageSwitcher \/>/)
        assert.doesNotMatch(source, /pc-locale-switch/)
        assert.doesNotMatch(source, /pathForLocale/)
    }

    assert.match(shared, /if \(code === locale\.value\) return/)
    assert.match(i18n, /localizedUrl/)
    assert.match(i18n, /locale\.value, code/)
})

test('all pagination renderers delegate to the canonical component', () => {
    const wrappers = [
        'resources/js/Components/Ui/Pagination.vue',
        'resources/js/Components/Dashboard/CmsPagination.vue',
        'resources/js/Components/Dashboard/DataTablePagination.vue',
        'resources/js/Components/Phase5/Phase5Pagination.vue',
    ]

    for (const wrapper of wrappers) {
        const source = read(wrapper)
        assert.match(source, /import CanonicalPagination from '(?:\.\.\/)+Pagination\.vue'/)
        assert.doesNotMatch(source, /@inertiajs\/vue3/)
    }

    const vueFiles = walk('resources/js', '.vue')
    const customRenderers = vueFiles.filter((file) => {
        const source = read(file)
        return source.includes('aria-label="Pagination"') && ! source.includes('CanonicalPagination')
    })

    assert.deepEqual(customRenderers, [])
})

test('dual paginators use distinct page keys and automations avoid prop shadowing', () => {
    const workflows = read('resources/js/Pages/Phase5/Workflows/Index.vue')
    const webhooks = read('resources/js/Pages/Phase5/Webhooks/Index.vue')
    const automations = read('resources/js/Pages/Phase5/Automations/Index.vue')

    assert.match(workflows, /page-key="definitionPage"/)
    assert.match(workflows, /page-key="instancePage"/)
    assert.match(webhooks, /page-key="endpointPage"/)
    assert.match(webhooks, /page-key="deliveryPage"/)
    assert.match(automations, /page-key="definitionPage"/)
    assert.match(automations, /page-key="executionPage"/)
    assert.match(automations, /const definitionRows = computed/)
    assert.match(automations, /const executionRows = computed/)
    assert.doesNotMatch(automations, /:paginator="definitions"/)
    assert.doesNotMatch(automations, /:paginator="executions"/)
})

test('pagination translations are present in both locales', () => {
    const english = read('lang/en/website.php')
    const arabic = read('lang/ar/website.php')

    for (const key of ['label', 'showing', 'previous', 'next', 'page', 'per_page', 'ellipsis']) {
        assert.match(english, new RegExp(`'${key}' =>`))
        assert.match(arabic, new RegExp(`'${key}' =>`))
    }

    assert.match(english, /'notifications_title' => 'Notifications'/)
    assert.match(arabic, /'notifications_title' => 'الإشعارات'/)
})
