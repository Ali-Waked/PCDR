import assert from 'node:assert/strict'
import fs from 'node:fs'
import path from 'node:path'
import test from 'node:test'
import { fileURLToPath } from 'node:url'

import {
    complaintsMailto,
    copyComplaintsEmail,
} from '../resources/js/Support/complaintsEmail.js'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8')

function fakeDocument({ copied = true } = {}) {
    const appended = []
    const removed = []
    const input = {
        value: '',
        style: {},
        attrs: {},
        setAttribute(name, value) {
            this.attrs[name] = value
        },
        select() {
            this.selected = true
        },
        setSelectionRange(start, end) {
            this.selection = [start, end]
        },
        remove() {
            removed.push(this)
        },
    }

    return {
        appended,
        removed,
        input,
        body: {
            appendChild(node) {
                appended.push(node)
            },
        },
        createElement(tag) {
            assert.equal(tag, 'textarea')
            return input
        },
        execCommand(command) {
            assert.equal(command, 'copy')
            return copied
        },
    }
}

test('complaints mailto uses only the backend-provided email', () => {
    assert.equal(complaintsMailto('  complaints@pal-arc.org  '), 'mailto:complaints@pal-arc.org')
    assert.equal(complaintsMailto(''), '')
    assert.equal(complaintsMailto(null), '')
    assert.equal(complaintsMailto(undefined), '')
})

test('copy helper prefers the async clipboard API', async () => {
    const written = []
    const nav = {
        clipboard: {
            async writeText(value) {
                written.push(value)
            },
        },
    }

    const result = await copyComplaintsEmail('  complaints@pal-arc.org ', { navigator: nav })

    assert.deepEqual(result, { ok: true, method: 'clipboard' })
    assert.deepEqual(written, ['complaints@pal-arc.org'])
})

test('copy helper falls back to a hidden readonly textarea when clipboard is blocked', async () => {
    const doc = fakeDocument()
    const nav = {
        clipboard: {
            async writeText() {
                throw new Error('permission denied')
            },
        },
    }

    const result = await copyComplaintsEmail('complaints@pal-arc.org', { navigator: nav, document: doc })

    assert.deepEqual(result, { ok: true, method: 'fallback' })
    assert.deepEqual(doc.appended, [doc.input])
    assert.deepEqual(doc.removed, [doc.input])
    assert.equal(doc.input.value, 'complaints@pal-arc.org')
    assert.equal(doc.input.attrs.readonly, '')
    assert.equal(doc.input.attrs['aria-hidden'], 'true')
    assert.equal(doc.input.style.position, 'fixed')
    assert.deepEqual(doc.input.selection, [0, 'complaints@pal-arc.org'.length])
})

test('copy helper reports clear failures for empty and unavailable clipboard environments', async () => {
    assert.deepEqual(await copyComplaintsEmail('', { navigator: {}, document: fakeDocument() }), {
        ok: false,
        reason: 'empty',
    })
    assert.deepEqual(await copyComplaintsEmail('a@example.test', { navigator: {}, document: null }), {
        ok: false,
        reason: 'unavailable',
    })

    const blocked = fakeDocument({ copied: false })
    assert.deepEqual(await copyComplaintsEmail('a@example.test', { navigator: {}, document: blocked }), {
        ok: false,
        reason: 'blocked',
    })
})

test('complaints page renders backend text, directions, accessible feedback and no intake form', () => {
    const page = read('resources/js/Pages/Public/Complaints.vue')

    for (const marker of [
        "props.complaintsEmail",
        "complaintsMailto(email.value)",
        "copyComplaintsEmail(email.value)",
        "t('nav.complaints')",
        "t('ui.complaints.intro')",
        "t('ui.complaints.email_label')",
        "t('ui.complaints.copy')",
        "t('ui.complaints.copy_failed')",
        "t('ui.complaints.not_configured')",
        "t('ui.complaints.how_body')",
        "t('ui.complaints.guidance_title')",
        "t('ui.complaints.privacy_body')",
        'PublicLayout',
        'PublicPageHero',
        "../../../css/complaints-box.css",
        'role="status"',
        'aria-live="polite"',
        'aria-atomic="true"',
        'dir="ltr"',
        ':disabled="copyBusy"',
        ':aria-busy="String(copyBusy)"',
        'v-if="email"',
        'v-else',
        'v-if="showAlternative"',
    ]) {
        assert.ok(page.includes(marker), `Missing page marker: ${marker}`)
    }

    assert.ok(!page.includes('complaints@pal-arc.org'))
    assert.ok(!page.includes('<form'))
    assert.ok(!page.includes('router.post'))
    assert.ok(!page.includes('localStorage'))
    assert.ok(!page.includes('gtag'))
    assert.equal((page.match(/<h1\b/g) || []).length, 0)
    assert.equal((page.match(/<template>/g) || []).length, 1)
})

test('footer discovery link is compiled once and does not clutter the primary navigation', () => {
    const nav = read('resources/js/composables/usePublicNavigation.js')
    const footer = read('resources/js/Components/Public/SiteFooter.vue')

    const itemsBlock = nav.slice(nav.indexOf('const ITEMS = ['), nav.indexOf('const FOOTER_GROUPS = ['))
    assert.ok(!itemsBlock.includes('complaints'))

    assert.ok(nav.includes("{ key: 'complaints' },"))
    assert.ok(nav.includes("complaints: { key: 'complaints', path: 'complaints'"))
    assert.equal((nav.match(/path: 'complaints'/g) || []).length, 1)

    assert.equal((footer.match(/L\('complaints'/g) || []).length, 1)
    assert.ok(footer.includes("L('complaints','صندوق الشكاوى','Complaints Box')"))
    assert.ok(!footer.includes("hasRoute('complaints')"))
})

test('CMS settings form renders localized descriptions for the complaints email', () => {
    const controller = read('app/Http/Controllers/Cms/SettingController.php')
    const catalog = read('app/Support/SiteSettingsCatalog.php')
    const form = read('resources/js/Pages/Cms/Settings/Index.vue')
    const en = read('lang/en/website.php')
    const ar = read('lang/ar/website.php')

    assert.ok(catalog.includes("'contact.complaints_email' => ['group' => 'contact', 'localized' => false, 'type' => 'email']"))
    assert.ok(controller.includes("'description' => Lang::has($descriptionKey, $locale)"))
    assert.ok(form.includes('v-if="setting.description"'))
    assert.ok(form.includes('class="pc-hint"'))

    assert.ok(en.includes("'contact_complaints_email' => 'Complaints Box Email'"))
    assert.ok(en.includes("The official email address displayed to visitors for submitting complaints."))
    assert.ok(ar.includes("'contact_complaints_email' => 'البريد الإلكتروني لصندوق الشكاوى'"))
    assert.ok(ar.includes('البريد الإلكتروني الرسمي الذي يظهر للزوار للتواصل مع الجمعية بشأن الشكاوى.'))
})

test('both locales provide every visible complaints string', () => {
    const keys = [
        'seo_title',
        'meta_description',
        'intro',
        'channel_label',
        'email_label',
        'panel_note',
        'copy',
        'copied',
        'copy_failed',
        'not_configured',
        'how_title',
        'how_body',
        'guidance_title',
        'guidance_note',
        'subject',
        'description',
        'date_location',
        'contact_info',
        'attachments',
        'privacy_title',
        'privacy_body',
        'alternative_title',
        'alternative_note',
    ]

    for (const locale of ['en', 'ar']) {
        const source = read(`lang/${locale}/website.php`)
        assert.ok(source.includes("'complaints' => ["), `${locale} complaints block is missing`)

        for (const key of keys) {
            assert.ok(source.includes(`'${key}' =>`), `${locale} missing ${key}`)
        }
    }

    const en = read('lang/en/website.php')
    const ar = read('lang/ar/website.php')
    assert.ok(en.includes("'seo_title' => 'Complaints Box | PCDR'"))
    assert.ok(ar.includes("'seo_title' => 'صندوق الشكاوى | جمعية العمل التشاركي في الأزمات والكوارث'"))
    assert.ok(en.includes("'complaints' => 'Complaints Box'"))
    assert.ok(ar.includes("'complaints' => 'صندوق الشكاوى'"))
})

test('complaints styling stays scoped to the approved palette without gradients or animation', () => {
    const css = read('resources/css/complaints-box.css')
    const shared = read('resources/css/public-site.css')

    for (const color of [
        '#3e5172', '#71bcb7', '#dac3a3', '#d7c5a1', '#ffffff',
        '#25334b', '#30415e', '#e9edf2', '#397f7b', '#eaf5f4', '#f7f2ea',
    ]) {
        assert.ok(shared.toLowerCase().includes(color) || css.toLowerCase().includes(color), `Missing palette color ${color}`)
    }

    assert.ok(css.includes('.public-shell.complaints-page'))
    assert.ok(css.includes('@media (max-width: 900px)'))
    assert.ok(css.includes('@media (max-width: 640px)'))
    assert.ok(!css.includes('linear-gradient'))
    assert.ok(!css.includes('animation:'))
})
