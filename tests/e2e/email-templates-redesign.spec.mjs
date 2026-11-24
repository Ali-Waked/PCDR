import { test, expect } from '@playwright/test'
import { getCredentials, gotoWithAuth, waitForApp } from './helpers/auth.mjs'

const CASES = [
    { locale: 'ar', width: 1440 },
    { locale: 'ar', width: 390 },
    { locale: 'en', width: 1440 },
    { locale: 'en', width: 390 },
]

for (const { locale, width } of CASES) {
    test(`email templates redesign renders at ${locale}/${width}px`, async ({ browser }) => {
        const { email, password } = getCredentials()
        test.skip(!email || !password, 'Set E2E_EMAIL and E2E_PASSWORD to verify the authenticated CMS page.')

        const context = await browser.newContext({
            viewport: { width, height: 900 },
            locale: locale === 'ar' ? 'ar-SA' : 'en-US',
        })
        const page = await context.newPage()

        try {
            const outcome = await gotoWithAuth(page, `/${locale}/cms/email-templates`, locale)
            await waitForApp(page)

            test.skip(Boolean(outcome.skipped), outcome.appError || 'The CMS session could not be established.')
            test.skip(outcome.response?.status() === 403, 'The seeded CMS user lacks email_templates.view.')

            expect(outcome.response?.status() ?? 500).toBeLessThan(500)
            await expect(page.locator('.et-page')).toBeVisible()
            await expect(page.locator('.pc-metric')).toHaveCount(4)

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
            expect(overflow).toBeLessThanOrEqual(1)

            await page.getByTestId('email-templates-guide').click()
            await expect(page.getByTestId('template-guide')).toBeVisible()
            await page.keyboard.press('Escape')
            await expect(page.getByTestId('template-guide')).toHaveCount(0)

            const createButton = page.getByTestId('email-templates-create')
            if (await createButton.isVisible()) {
                await createButton.click()
                await expect(page.getByTestId('template-wizard')).toBeVisible()
                await page.keyboard.press('Escape')
                await expect(page.getByTestId('template-wizard')).toHaveCount(0)
            }

            const previewTestId = width < 600 ? 'email-templates-preview-mobile' : 'email-templates-preview'
            const previewButton = page.getByTestId(previewTestId).first()
            if (await previewButton.count()) {
                await previewButton.click()
                await expect(page.getByTestId('email-preview')).toBeVisible()
                await expect(page.getByTestId('email-preview').locator('iframe')).toHaveAttribute('sandbox', '')
                await page.keyboard.press('Escape')
                await expect(page.getByTestId('email-preview')).toHaveCount(0)
            }
        } finally {
            await context.close()
        }
    })
}
