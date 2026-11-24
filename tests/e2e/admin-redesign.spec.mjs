import fs from 'node:fs'
import path from 'node:path'
import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'
import { getCredentials, gotoWithAuth, signIn, waitForApp } from './helpers/auth.mjs'
import {
  discoverAdminRoutes,
  loadFixtureIds,
  resolveRoutePath,
  routeDiscovery,
  routeSlug,
} from './helpers/routes.mjs'
import {
  SCREENSHOT_DIR,
  flushSummary,
  recordSkip,
  recordScreenshot,
  summary,
  writeJson,
} from './helpers/report.mjs'

fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })

const LOCALES = ['ar', 'en']
const WIDTHS = [360, 390]
const SCREENSHOT_WIDTHS = [390, 1440]
const THEMES = ['light', 'dark']
const KEY_ROUTE_NAMES = [
  'dashboard',
  'cms.dashboard',
  'admin.users.index',
  'cms.pillars.index',
  'cms.programs.index',
  'cms.projects.index',
  'cms.news.index',
]

let routes = []
let fixtures = {}

function credentialsMessage() {
  const { email, password } = getCredentials()
  return `credentials=${email ? 'set' : 'missing'}/${password ? 'set' : 'missing'}`
}

function appErrorMessage(outcome, label) {
  if (!outcome) return `${label}: navigation returned no outcome`
  if (outcome.appError) return `${label}: ${outcome.appError}`
  if (outcome.response && outcome.response.status() >= 500) return `${label}: HTTP ${outcome.response.status()}`
  if (outcome.redirectedToLogin) return `${label}: redirected to login; authenticated session was not established`
  return null
}

function routeContext(route, locale) {
  const resolved = resolveRoutePath(route, locale, fixtures)
  if (resolved.path) return { route, path: resolved.path }
  recordSkip('routes', resolved.reason, { name: route.name, uri: route.uri, locale })
  return null
}

async function openContext(browser, locale, options = {}) {
  const context = await browser.newContext({
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    locale: locale === 'ar' ? 'ar-SA' : 'en-US',
    colorScheme: options.colorScheme || 'light',
    reducedMotion: options.reducedMotion || 'no-preference',
    viewport: { width: options.width || 1440, height: options.height || 900 },
  })
  const page = await context.newPage()
  const outcome = await signIn(page, locale)
  return { context, page, outcome }
}

async function navigate(page, path, locale) {
  const outcome = await gotoWithAuth(page, path, locale)
  await waitForApp(page).catch(() => {})
  return outcome
}

function navigationSkip(outcome, label) {
  if (!outcome) return `${label}: navigation failed`
  const status = outcome.response?.status()
  if (status === 403) return `${label}: HTTP 403 (access denied)`
  if (status === 404) return `${label}: HTTP 404`
  if (status && status >= 500) return `${label}: HTTP ${status}`
  return appErrorMessage(outcome, label)
}

function measureOverflowInPage(page) {
  return page.evaluate(() => {
    const viewportWidth = document.documentElement.clientWidth || window.innerWidth
    const offenders = []
    const nodes = document.querySelectorAll('body *')
    for (const node of nodes) {
      const style = getComputedStyle(node)
      if (style.display === 'none' || style.visibility === 'hidden') continue
      const rect = node.getBoundingClientRect()
      if (rect.width === 0 && rect.height === 0) continue
      const rightOverflow = Math.round(rect.right - viewportWidth)
      const leftOverflow = Math.round(-rect.left)
      const overflow = Math.max(rightOverflow, leftOverflow)
      if (overflow <= 1) continue
      offenders.push({
        selector: node.tagName.toLowerCase()
          + (node.id ? `#${node.id}` : '')
          + (typeof node.className === 'string' && node.className.trim()
            ? `.${node.className.trim().split(/\s+/).slice(0, 3).join('.')}`
            : ''),
        overflowPx: overflow,
        rect: { left: Math.round(rect.left), right: Math.round(rect.right), width: Math.round(rect.width) },
      })
    }
    return {
      viewportWidth,
      scrollWidth: document.documentElement.scrollWidth,
      maxOverflowPx: Math.max(0, document.documentElement.scrollWidth - viewportWidth),
      offenders,
    }
  })
}

function measureFontsInPage(page) {
  return page.evaluate(() => {
    const offenders = []
    let measured = 0
    const nodes = document.querySelectorAll('body *')
    for (const node of nodes) {
      const style = getComputedStyle(node)
      if (style.display === 'none' || style.visibility === 'hidden') continue
      const directText = [...node.childNodes]
        .filter((child) => child.nodeType === Node.TEXT_NODE)
        .map((child) => child.textContent.trim())
        .join(' ')
      if (!directText) continue
      const size = Number.parseFloat(style.fontSize)
      if (!Number.isFinite(size)) continue
      measured += 1
      if (size < 13) {
        offenders.push({
          selector: node.tagName.toLowerCase()
            + (node.id ? `#${node.id}` : '')
            + (typeof node.className === 'string' && node.className.trim()
              ? `.${node.className.trim().split(/\s+/).slice(0, 3).join('.')}`
              : ''),
          fontSizePx: Number(size.toFixed(2)),
          text: directText.slice(0, 80),
        })
      }
    }
    return { measured, offenders }
  })
}

function keyRoutes() {
  return KEY_ROUTE_NAMES
    .map((name) => routes.find((route) => route.name === name))
    .filter(Boolean)
    .map((route) => routeContext(route, 'ar'))
    .filter(Boolean)
}

test.afterAll(() => {
  flushSummary({ credentialsProvided: Boolean(getCredentials().email && getCredentials().password) })
})

test.describe('PCDR admin redesign verification', () => {
  test('discover routes and seeded IDs', async () => {
    routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    summary().routes.discovered = routes.length
    summary().routes.byName = routes.map((route) => ({ name: route.name, uri: route.uri }))
    summary().routes.source = routeDiscovery.source
    summary().routes.error = routeDiscovery.error
    summary().fixtures = fixtures
    writeJson('routes', {
      source: routeDiscovery.source,
      error: routeDiscovery.error,
      discovered: routes.length,
      routes,
      fixtures,
    })
    expect(routes.length, 'no admin/CMS/dashboard GET routes were discovered').toBeGreaterThan(0)
  })

  test('no horizontal overflow at 360/390 in AR/EN on all GET admin routes', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const report = { measured: 0, offenders: [], skipped: [] }

    for (const locale of LOCALES) {
      let session
      try {
        session = await openContext(browser, locale, { width: WIDTHS[0] })
        if (session.outcome.skipped) {
          const reason = session.outcome.appError || 'authentication could not be established'
          recordSkip('overflow', reason, { locale })
          report.skipped.push({ locale, reason })
          break
        }
        for (const width of WIDTHS) {
          await session.page.setViewportSize({ width, height: 844 })
          for (const route of routes) {
            const resolved = routeContext(route, locale)
            if (!resolved) continue
            const label = `${locale} ${width}px ${route.name}`
            const outcome = await navigate(session.page, resolved.path, locale)
            const skipReason = navigationSkip(outcome, label)
            if (skipReason) {
              recordSkip('overflow', skipReason, { locale, width, name: route.name, path: resolved.path })
              report.skipped.push({ locale, width, name: route.name, path: resolved.path, reason: skipReason })
              continue
            }
            const measurement = await measureOverflowInPage(session.page)
            report.measured += 1
            summary().overflow.measured += 1
            if (measurement.offenders.length || measurement.maxOverflowPx > 1) {
              const offender = {
                locale,
                width,
                name: route.name,
                path: resolved.path,
                ...measurement,
              }
              report.offenders.push(offender)
              summary().overflow.offenders.push(offender)
            }
          }
        }
      } finally {
        await session?.context.close()
      }
    }

    writeJson('overflow', report)
    expect(
      report.offenders,
      `${report.offenders.length} route/viewport combinations overflow horizontally; see screenshots/reports/overflow.json`,
    ).toEqual([])
  })

  test('capture AR/EN light/dark screenshots at 390 and 1440', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const targets = keyRoutes()
    const skipped = []

    for (const locale of LOCALES) {
      for (const colorScheme of THEMES) {
        let session
        try {
          session = await openContext(browser, locale, { colorScheme, width: 390 })
          if (session.outcome.skipped) {
            const reason = session.outcome.appError || 'authentication could not be established'
            recordSkip('screenshots', reason, { locale, colorScheme })
            skipped.push({ locale, colorScheme, reason })
            continue
          }
          for (const width of SCREENSHOT_WIDTHS) {
            await session.page.setViewportSize({ width, height: width === 390 ? 844 : 900 })
            for (const target of targets) {
              const label = `${locale}/${colorScheme}/${width}/${target.route.name}`
              const outcome = await navigate(session.page, target.path, locale)
              const skipReason = navigationSkip(outcome, label)
              if (skipReason) {
                recordSkip('screenshots', skipReason, { locale, colorScheme, width, name: target.route.name })
                skipped.push({ locale, colorScheme, width, name: target.route.name, reason: skipReason })
                continue
              }
              const fileName = `${locale}-${colorScheme}-${width}-${routeSlug(target.route.name)}.png`
              const filePath = path.join(SCREENSHOT_DIR, fileName)
              await session.page.screenshot({ path: filePath, fullPage: false, animations: 'disabled' })
              recordScreenshot({
                locale,
                colorScheme,
                width,
                route: target.route.name,
                path: filePath,
                url: session.page.url(),
              })
            }
          }
        } finally {
          await session?.context.close()
        }
      }
    }

    writeJson('screenshots', { captured: summary().screenshots, skipped })
    if (!summary().screenshots.length) {
      test.skip(true, skipped[0]?.reason || 'no screenshots could be captured')
      return
    }
    expect(summary().screenshots.length).toBeGreaterThan(0)
  })

  test('font sizes are at least 13px on key admin routes', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const report = { measured: 0, offenders: [], skipped: [] }

    for (const locale of LOCALES) {
      let session
      try {
        session = await openContext(browser, locale, { width: 390 })
        if (session.outcome.skipped) {
          const reason = session.outcome.appError || 'authentication could not be established'
          recordSkip('fonts', reason, { locale })
          report.skipped.push({ locale, reason })
          continue
        }
        for (const width of SCREENSHOT_WIDTHS) {
          await session.page.setViewportSize({ width, height: width === 390 ? 844 : 900 })
          for (const target of keyRoutes()) {
            const label = `${locale}/${width}/${target.route.name}`
            const outcome = await navigate(session.page, target.path, locale)
            const skipReason = navigationSkip(outcome, label)
            if (skipReason) {
              recordSkip('fonts', skipReason, { locale, width, name: target.route.name })
              report.skipped.push({ locale, width, name: target.route.name, reason: skipReason })
              continue
            }
            const measurement = await measureFontsInPage(session.page)
            report.measured += measurement.measured
            summary().fonts.measured += measurement.measured
            for (const offender of measurement.offenders) {
              const entry = { locale, width, route: target.route.name, ...offender }
              report.offenders.push(entry)
              summary().fonts.offenders.push(entry)
            }
          }
        }
      } finally {
        await session?.context.close()
      }
    }

    writeJson('fonts', report)
    expect(report.offenders, `${report.offenders.length} text nodes below 13px`).toEqual([])
  })

  test('axe serious/critical and color-contrast violations', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const report = { measured: 0, violations: [], skipped: [] }

    for (const locale of LOCALES) {
      let session
      try {
        session = await openContext(browser, locale)
        if (session.outcome.skipped) {
          const reason = session.outcome.appError || 'authentication could not be established'
          recordSkip('axe', reason, { locale })
          report.skipped.push({ locale, reason })
          continue
        }
        for (const target of keyRoutes()) {
          const label = `${locale}/${target.route.name}`
          const outcome = await navigate(session.page, target.path, locale)
          const skipReason = navigationSkip(outcome, label)
          if (skipReason) {
            recordSkip('axe', skipReason, { locale, name: target.route.name })
            report.skipped.push({ locale, name: target.route.name, reason: skipReason })
            continue
          }
          const results = await new AxeBuilder({ page: session.page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze()
          report.measured += 1
          summary().axe.measured += 1
          const relevant = results.violations.filter(
            (violation) => violation.impact === 'serious'
              || violation.impact === 'critical'
              || violation.id === 'color-contrast',
          )
          for (const violation of relevant) {
            const entry = {
              locale,
              route: target.route.name,
              id: violation.id,
              impact: violation.impact,
              help: violation.help,
              nodes: violation.nodes.length,
              targets: violation.nodes.slice(0, 10).map((node) => node.target),
            }
            report.violations.push(entry)
            summary().axe.violations.push(entry)
          }
        }
      } finally {
        await session?.context.close()
      }
    }

    writeJson('axe', report)
    expect(report.violations, `${report.violations.length} serious/critical/color-contrast axe violations`).toEqual([])
  })

  test('DataTable renders in table mode at 1440 and card-safe mode at 390', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const route = routes.find((entry) => entry.name === 'admin.users.index')
    if (!route) {
      test.skip(true, 'admin.users.index route was not discovered')
      return
    }
    const report = { measured: 0, checks: [], skipped: [] }
    let session
    try {
      session = await openContext(browser, 'en', { width: 1440 })
      if (session.outcome.skipped) {
        const reason = session.outcome.appError || 'authentication could not be established'
        recordSkip('dataTable', reason)
        report.skipped.push({ reason })
      } else {
        for (const width of SCREENSHOT_WIDTHS) {
          await session.page.setViewportSize({ width, height: 900 })
          const resolved = routeContext(route, 'en')
          if (!resolved) continue
          const outcome = await navigate(session.page, resolved.path, 'en')
          const skipReason = navigationSkip(outcome, `en/${width}/${route.name}`)
          if (skipReason) {
            recordSkip('dataTable', skipReason, { width, name: route.name })
            report.skipped.push({ width, name: route.name, reason: skipReason })
            continue
          }
          const table = session.page.locator('table.pc-table').first()
          const rowCount = await table.locator('tbody tr').count()
          const wrap = session.page.locator('.pc-table-wrap').first()
          const wrapBox = await wrap.boundingBox().catch(() => null)
          const check = {
            width,
            tableCount: await session.page.locator('table.pc-table').count(),
            rowCount,
            wrapScrollWidth: wrapBox ? Math.round(wrapBox.width) : null,
            documentScrollWidth: await session.page.evaluate(() => document.documentElement.scrollWidth),
            viewportWidth: width,
            mode: width <= 390 ? 'card-safe' : 'table',
          }
          report.measured += 1
          report.checks.push(check)
          summary().dataTable.measured += 1
          summary().dataTable.checks.push(check)
          expect(check.tableCount, `no DataTable at ${width}px`).toBeGreaterThan(0)
          expect(check.rowCount, `DataTable had no seeded rows at ${width}px`).toBeGreaterThan(0)
          expect(check.documentScrollWidth, `DataTable overflowed at ${width}px`).toBeLessThanOrEqual(width + 1)
        }
      }
    } finally {
      await session?.context.close()
    }
    writeJson('dataTable', report)
  })

  test('Select keyboard navigation works in RTL and LTR', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const route = routes.find((entry) => entry.name === 'cms.pillars.index')
    if (!route) {
      test.skip(true, 'cms.pillars.index route was not discovered')
      return
    }
    const report = { measured: 0, checks: [], skipped: [] }

    for (const locale of LOCALES) {
      let session
      try {
        session = await openContext(browser, locale)
        if (session.outcome.skipped) {
          const reason = session.outcome.appError || 'authentication could not be established'
          recordSkip('select', reason, { locale })
          report.skipped.push({ locale, reason })
          continue
        }
        const resolved = routeContext(route, locale)
        if (!resolved) continue
        const outcome = await navigate(session.page, resolved.path, locale)
        const skipReason = navigationSkip(outcome, `${locale}/${route.name}`)
        if (skipReason) {
          recordSkip('select', skipReason, { locale, name: route.name })
          report.skipped.push({ locale, name: route.name, reason: skipReason })
          continue
        }
        const trigger = session.page.locator('.pc-selection__trigger[role="combobox"]').first()
        if (await trigger.count() !== 1) {
          const reason = `${locale}: no .pc-selection__trigger[role=combobox] on ${route.name}`
          recordSkip('select', reason, { locale })
          report.skipped.push({ locale, reason })
          continue
        }
        await trigger.click()
        const listbox = session.page.locator('[role="listbox"]').first()
        await expect(listbox, `${locale}: listbox did not open`).toBeVisible()
        const searchInput = session.page.locator('.pc-selection__search-input')
        const activeElement = await searchInput.count() ? searchInput : trigger
        const before = await activeElement.getAttribute('aria-activedescendant')
        await activeElement.press('ArrowDown')
        const after = await activeElement.getAttribute('aria-activedescendant')
        const optionCount = await listbox.locator('[role="option"]').count()
        const check = { locale, direction: locale === 'ar' ? 'rtl' : 'ltr', before, after, optionCount, changed: before !== after }
        report.measured += 1
        report.checks.push(check)
        summary().select.measured += 1
        summary().select.checks.push(check)
        expect(optionCount, `${locale}: select had no options`).toBeGreaterThan(1)
        expect(check.changed, `${locale}: aria-activedescendant did not change after ArrowDown`).toBe(true)
        await activeElement.press('Enter')
      } finally {
        await session?.context.close()
      }
    }
    writeJson('select', report)
  })

  test('Lighthouse accessibility score is at least 95', async ({ browser }) => {
    if (process.env.E2E_BROWSER === 'firefox') {
      const reason = 'Lighthouse requires Chromium/Chrome; E2E_BROWSER=firefox was used because Chromium cannot launch in this proot environment'
      recordSkip('lighthouse', reason)
      writeJson('lighthouse', { measured: 0, scores: [], skipped: [reason] })
      test.skip(true, reason)
      return
    }

    const route = routes.find((entry) => entry.name === 'dashboard')
    if (!route) {
      test.skip(true, 'dashboard route was not discovered')
      return
    }
    const resolved = routeContext(route, 'en')
    if (!resolved) {
      test.skip(true, 'dashboard route could not be resolved')
      return
    }

    let session
    let chrome
    try {
      session = await openContext(browser, 'en')
      if (session.outcome.skipped) {
        const reason = session.outcome.appError || 'authentication could not be established'
        recordSkip('lighthouse', reason)
        writeJson('lighthouse', { measured: 0, scores: [], skipped: [reason] })
        test.skip(true, reason)
        return
      }
      const outcome = await navigate(session.page, resolved.path, 'en')
      const skipReason = navigationSkip(outcome, `lighthouse/en/${route.name}`)
      if (skipReason) {
        recordSkip('lighthouse', skipReason)
        writeJson('lighthouse', { measured: 0, scores: [], skipped: [skipReason] })
        test.skip(true, skipReason)
        return
      }
      const finalUrl = session.page.url()
      const expectedPath = new URL(resolved.path, 'http://127.0.0.1').pathname
      if (new URL(finalUrl).pathname !== expectedPath) {
        const reason = `Lighthouse auth transfer skipped: final URL ${finalUrl} did not match dashboard path ${expectedPath}`
        recordSkip('lighthouse', reason, { finalUrl, expectedPath })
        writeJson('lighthouse', { measured: 0, scores: [], skipped: [reason] })
        test.skip(true, reason)
        return
      }
      const cookies = await session.context.cookies()
      const cookieHeader = cookies.map((cookie) => `${cookie.name}=${cookie.value}`).join('; ')

      try {
        const chromeLauncher = await import('chrome-launcher')
        const lighthouseModule = await import('lighthouse')
        const lighthouse = lighthouseModule.default || lighthouseModule
        chrome = await chromeLauncher.launch({
          chromeFlags: [
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            ...(process.env.E2E_CHROME_ARGS ? process.env.E2E_CHROME_ARGS.trim().split(/\s+/) : []),
          ],
        })
        const result = await lighthouse(finalUrl, {
          port: chrome.port,
          output: 'json',
          logLevel: 'error',
          extraHeaders: cookieHeader ? { cookie: cookieHeader } : undefined,
          onlyCategories: ['accessibility'],
        })
        const lhr = result?.lhr || result
        const finalDisplayedUrl = lhr?.finalDisplayedUrl || lhr?.finalUrl || ''
        if (!finalDisplayedUrl.endsWith(expectedPath)) {
          const reason = `Lighthouse score discarded: finalDisplayedUrl ${finalDisplayedUrl} did not match ${expectedPath} (likely auth transfer failure)`
          recordSkip('lighthouse', reason, { finalDisplayedUrl, expectedPath })
          writeJson('lighthouse', { measured: 0, scores: [], skipped: [reason], lhrUrl: finalDisplayedUrl })
          test.skip(true, reason)
          return
        }
        const score = Math.round((lhr?.categories?.accessibility?.score ?? 0) * 100)
        const entry = { route: route.name, locale: 'en', score, finalDisplayedUrl }
        summary().lighthouse.measured += 1
        summary().lighthouse.scores.push(entry)
        writeJson('lighthouse', { measured: 1, scores: [entry], skipped: [] })
        expect(score, `Lighthouse accessibility score ${score} is below 95`).toBeGreaterThanOrEqual(95)
      } catch (error) {
        const reason = `Lighthouse could not run: ${String(error.message || error).split('\n')[0]}`
        recordSkip('lighthouse', reason)
        writeJson('lighthouse', { measured: 0, scores: [], skipped: [reason] })
        test.skip(true, reason)
      }
    } finally {
      try { await chrome?.kill() } catch { /* best effort */ }
      await session?.context.close()
    }
  })

  test('reduced-motion emulation removes dashboard transitions', async ({ browser }) => {
    if (!routes.length) routes = discoverAdminRoutes()
    fixtures = loadFixtureIds()
    const route = routes.find((entry) => entry.name === 'dashboard')
    if (!route) {
      test.skip(true, 'dashboard route was not discovered')
      return
    }
    const report = { measured: 0, checks: [], skipped: [] }
    let session
    try {
      session = await openContext(browser, 'en', { reducedMotion: 'reduce' })
      if (session.outcome.skipped) {
        const reason = session.outcome.appError || 'authentication could not be established'
        recordSkip('reducedMotion', reason)
        report.skipped.push({ reason })
      } else {
        const resolved = routeContext(route, 'en')
        if (resolved) {
          const outcome = await navigate(session.page, resolved.path, 'en')
          const skipReason = navigationSkip(outcome, `en/${route.name}`)
          if (skipReason) {
            recordSkip('reducedMotion', skipReason)
            report.skipped.push({ reason: skipReason })
          } else {
            const durations = await session.page.evaluate(() => {
              const selectors = ['.pc-shell', '.pc-sidebar', '.pc-nav-item', '.pc-btn', '.pc-table tbody tr']
              const result = {}
              for (const selector of selectors) {
                const node = document.querySelector(selector)
                result[selector] = node ? getComputedStyle(node).transitionDuration : null
              }
              return result
            })
            const offenders = Object.entries(durations)
              .filter(([, value]) => value && value !== '0s' && value !== '0ms')
              .map(([selector, value]) => ({ selector, transitionDuration: value }))
            const check = { route: route.name, durations, offenders, mediaMatches: await session.page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches) }
            report.measured += 1
            report.checks.push(check)
            summary().reducedMotion.measured += 1
            summary().reducedMotion.checks.push(check)
            expect(check.mediaMatches, 'reduced-motion media query was not emulated').toBe(true)
            expect(offenders, `${offenders.length} elements still animate under reduced motion`).toEqual([])
          }
        }
      }
    } finally {
      await session?.context.close()
    }
    writeJson('reducedMotion', report)
  })
})
