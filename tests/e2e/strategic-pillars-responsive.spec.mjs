import { expect, test } from '@playwright/test'
import { gotoWithAuth, waitForApp } from './helpers/auth.mjs'

const LOCALE = 'en'
const VIEWPORTS = [
  { name: 'mobile-360', width: 360, height: 800 },
  { name: 'mobile-390', width: 390, height: 844 },
  { name: 'mobile-430', width: 430, height: 932 },
  { name: 'tablet-768', width: 768, height: 1024 },
  { name: 'desktop-1440', width: 1440, height: 900 },
]

async function expectNoHorizontalOverflow(page, label) {
  const metrics = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
  }))

  expect(
    metrics.scrollWidth,
    `${label}: page scrollWidth ${metrics.scrollWidth} exceeds viewport ${metrics.innerWidth}`,
  ).toBeLessThanOrEqual(metrics.innerWidth + 1)
}

test('strategic pillars page title, toolbar and responsive layout', async ({ browser }) => {
  for (const viewport of VIEWPORTS) {
    const context = await browser.newContext({
      viewport: { width: viewport.width, height: viewport.height },
      locale: 'en-US',
    })
    const page = await context.newPage()

    try {
      const result = await gotoWithAuth(page, `/${LOCALE}/cms/pillars`, LOCALE)
      if (result.skipped || result.redirectedToLogin) {
        test.skip(true, 'CMS credentials are required for the responsive verification')
        return
      }

      await waitForApp(page)
      await expect(page.locator('h1.pc-page-title')).toHaveText('Strategic pillars')
      await expect(page.locator('.pc-pillars-toolbar')).toBeVisible()
      await expect(page.locator('.pc-pillars-stats .pc-metric').first()).toBeVisible()
      await expectNoHorizontalOverflow(page, viewport.name)

      if (viewport.width <= 768) {
        await expect(page.locator('.pc-pillars-desktop')).toBeHidden()
        await expect(page.locator('.pc-pillars-mobile')).toBeVisible()
      } else {
        await expect(page.locator('.pc-pillars-desktop')).toBeVisible()
        await expect(page.locator('.pc-pillars-mobile')).toBeHidden()
      }
    } finally {
      await context.close()
    }
  }
})

test('professional select filters backend results and preserves the query string', async ({ page }) => {
  const result = await gotoWithAuth(page, `/${LOCALE}/cms/pillars`, LOCALE)
  if (result.skipped || result.redirectedToLogin) {
    test.skip(true, 'CMS credentials are required for the filter verification')
    return
  }

  await waitForApp(page)

  await page.locator('.pc-selection__trigger').click()
  await expect(page.locator('.pc-selection__panel')).toBeVisible()
  await page.locator('.pc-selection__search-input').fill('Published')
  await page.locator('.pc-selection__option').filter({ hasText: 'Published' }).first().click()

  await expect.poll(() => new URL(page.url()).searchParams.get('status')).toBe('published')
  await expect(page.locator('.pc-selection__value')).toHaveText('Published')
  await expectNoHorizontalOverflow(page, 'filter-applied')

  await page.locator('.pc-pillars-search input').fill('strategic')
  await expect.poll(() => new URL(page.url()).searchParams.get('search')).toBe('strategic')

  await page.locator('.pc-pillars-clear').click()
  await expect.poll(() => new URL(page.url()).searchParams.get('status')).toBeNull()
  await expect.poll(() => new URL(page.url()).searchParams.get('search')).toBeNull()
})
