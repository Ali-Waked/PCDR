import { test, expect } from '@playwright/test'

const routes = ['/ar','/en','/ar/about','/ar/strategic-areas','/ar/programs','/ar/news','/ar/publications','/ar/transparency','/ar/partnerships','/ar/get-involved','/ar/contact','/en/about','/en/strategic-areas','/en/programs','/en/news','/en/publications','/en/transparency','/en/partnerships','/en/get-involved','/en/contact']

function normalizeFooter(html) {
  return html
    .replace(/\s+/g, ' ')
    .replace(/lang="[^"]+"/g, '')
    .replace(/dir="[^"]+"/g, '')
    .replace(/aria-label="[^"]+"/g, '')
    .replace(/>[^<]+</g, (m) => />\s*</.test(m) ? '><' : m)
    .trim()
}

test('footer is identical across public routes', async ({ page }) => {
  let baseline = null
  let baselineHrefs = null
  for (const route of routes) {
    await page.goto(route, { waitUntil: 'networkidle' })
    const footers = page.locator('footer.ps-footer')
    await expect(footers).toHaveCount(1)
    const hrefs = await footers.locator('a[href]').evaluateAll((as) => as.map(a => a.getAttribute('href')).filter(Boolean).sort())
    const html = normalizeFooter(await footers.first().evaluate(el => el.outerHTML))
    if (!baseline) { baseline = html; baselineHrefs = hrefs }
    expect(hrefs, route).toEqual(baselineHrefs)
    expect(html, route).toBe(baseline)
  }
})
