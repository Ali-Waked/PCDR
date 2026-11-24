import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { test, expect } from '@playwright/test'

const HERE = path.dirname(fileURLToPath(import.meta.url))
export const PROJECT_ROOT = path.resolve(HERE, '..', '..', '..')
const ENV_FILE = process.env.E2E_ENV_FILE || path.join(PROJECT_ROOT, '.env')

/** Read KEY=value pairs from a local dotenv file without executing app code. */
function readDotEnv(file) {
  const values = {}
  if (!fs.existsSync(file)) return values
  for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*(?:export\s+)?([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/)
    if (!match) continue
    let value = match[2]
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1)
    }
    values[match[1]] = value
  }
  return values
}

/** Credentials come only from env or an already-seeded local identity. */
export function getCredentials() {
  const file = readDotEnv(ENV_FILE)
  const email = process.env.E2E_EMAIL || file.CMS_BOOTSTRAP_EMAIL || file.PCDR_OWNER_EMAIL || ''
  let password = process.env.E2E_PASSWORD || file.CMS_LOCAL_PASSWORD || file.PCDR_OWNER_PASSWORD || ''
  if (!password && email && file.CMS_BOOTSTRAP_EMAIL && file.CMS_LOCAL_AUTH === 'true') {
    password = 'password'
  }
  return { email: email.trim(), password }
}

export function credentialsMissingReason() {
  return (
    'No E2E credentials. Set E2E_EMAIL and E2E_PASSWORD for an already-seeded admin/CMS user ' +
    '(or CMS_BOOTSTRAP_EMAIL + CMS_LOCAL_PASSWORD in local .env). Tests skip rather than invent data.'
  )
}

/** Wait until the Inertia/Vue root has mounted and fonts have settled. */
export async function waitForApp(page) {
  await page.waitForLoadState('domcontentloaded')
  await expect.poll(
    () => page.evaluate(() => {
      const appRoot = document.querySelector('#app')
      if (appRoot) return Boolean(appRoot.__vue_app__)
      return document.body.innerText.trim().length > 0
    }),
    { message: 'Vue app root did not mount', timeout: 20_000 },
  ).toBe(true)
  await page.evaluate(async () => {
    try { await document.fonts?.ready } catch { /* fonts are best effort */ }
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))
  })
}

/**
 * Sign in through the localized /{locale}/login form (resources/js/Pages/Auth/Login.vue:
 * named inputs `email` and `password`, submit button type="submit").
 */
export async function signIn(page, locale = 'ar') {
  const { email, password } = getCredentials()
  if (!email || !password) {
    test.skip(true, credentialsMissingReason())
    return { skipped: true }
  }

  const loginResponse = await page.goto(`/${locale}/login`, { waitUntil: 'domcontentloaded' })
  await waitForApp(page)
  if (loginResponse && loginResponse.status() >= 500) {
    return { skipped: true, appError: `GET /${locale}/login returned HTTP ${loginResponse.status()}` }
  }

  if (!/\/login(?:\?|$)/.test(new URL(page.url()).pathname)) {
    return { skipped: false, alreadyAuthenticated: true }
  }

  const emailInput = page.locator('form input[name="email"]').first()
  const passwordInput = page.locator('form input[name="password"]').first()
  if (await emailInput.count() !== 1 || await passwordInput.count() !== 1) {
    return {
      skipped: true,
      appError: `localized /${locale}/login form is missing input[name=email] or input[name=password]`,
    }
  }
  await emailInput.fill(email)
  await passwordInput.fill(password)

  // Inertia follows the post-login redirect automatically. The admin landing
  // page is unrelated to this suite's targets and can be expensive on a seeded
  // local database, so stop it here and navigate explicitly instead.
  const abortLanding = (route) => route.abort()
  await page.route(`**/${locale}/dashboard`, abortLanding)

  const loginRequest = page.waitForResponse(
    (response) => response.request().method() === 'POST'
      && new URL(response.url()).pathname === `/${locale}/login`,
    { timeout: 20_000 },
  )
  await page.locator('form button[type="submit"]').first().click()
  const authResponse = await loginRequest
  await page.unroute(`**/${locale}/dashboard`, abortLanding).catch(() => {})

  const location = authResponse.headers()['location'] || ''
  const redirectedToLogin = Boolean(location) && /\/login(?:\?|$)/.test(new URL(location, page.url()).pathname)
  if (authResponse.status() >= 500 || ![302, 303].includes(authResponse.status()) || redirectedToLogin) {
    const body = (await page.locator('body').innerText().catch(() => '')).slice(0, 300).replace(/\s+/g, ' ')
    return {
      skipped: true,
      appError: `login with ${email} was not accepted (HTTP ${authResponse.status()}, location ${location || 'none'}) | body: ${body}`,
    }
  }

  // The session cookie is already established; callers navigate directly to
  // the page under test instead of waiting for the incidental landing page.
  return { skipped: false, alreadyAuthenticated: false }
}

/** CMS uses a separate auth session (Pages/Cms/Login.vue, same named fields). */
export async function signInCms(page, locale = 'ar') {
  const { email, password } = getCredentials()
  if (!email || !password) {
    test.skip(true, credentialsMissingReason())
    return false
  }
  await page.goto(`/${locale}/cms/login`, { waitUntil: 'domcontentloaded' })
  await waitForApp(page)
  if (!/\/cms\/login(?:\?|$)/.test(new URL(page.url()).pathname)) return true

  const emailInput = page.locator('form input[name="email"]').first()
  const passwordInput = page.locator('form input[name="password"]').first()
  if (await emailInput.count() !== 1 || await passwordInput.count() !== 1) return false
  await emailInput.fill(email)
  await passwordInput.fill(password)
  await page.locator('form button[type="submit"]').first().click()
  await expect.poll(
    () => new URL(page.url()).pathname,
    { message: `CMS login with ${email} did not redirect`, timeout: 20_000 },
  ).not.toMatch(/\/cms\/login(?:\?|$)/)
  await waitForApp(page)
  return true
}

const platformSessions = new WeakSet()
const cmsSessions = new WeakSet()

/** Navigate as an authenticated user, retrying once after the relevant login redirect. */
export async function gotoWithAuth(page, path, locale = 'ar') {
  const context = page.context()
  if (!platformSessions.has(context)) {
    const result = await signIn(page, locale)
    if (result.skipped) return { response: null, redirectedToLogin: true, skipped: true, appError: result.appError }
    platformSessions.add(context)
  }

  let response = await page.goto(path, { waitUntil: 'domcontentloaded' })
  await waitForApp(page)
  let currentPath = new URL(page.url()).pathname

  if (/\/cms\/login(?:\?|$)/.test(currentPath)) {
    if (!cmsSessions.has(context)) {
      if (!await signInCms(page, locale)) return { response, redirectedToLogin: true, cmsLoginFailed: true }
      cmsSessions.add(context)
    }
    response = await page.goto(path, { waitUntil: 'domcontentloaded' })
    await waitForApp(page)
    currentPath = new URL(page.url()).pathname
  }

  if (/^\/(ar|en)\/login(?:\/|$)/.test(currentPath)) {
    platformSessions.delete(context)
    const result = await signIn(page, locale)
    if (result.skipped) return { response, redirectedToLogin: true, skipped: true, appError: result.appError }
    platformSessions.add(context)
    response = await page.goto(path, { waitUntil: 'domcontentloaded' })
    await waitForApp(page)
  }

  return { response, redirectedToLogin: /\/login(?:\?|$)/.test(new URL(page.url()).pathname) }
}

export function localeOf(locale) {
  return locale === 'ar' ? 'ar' : 'en'
}
