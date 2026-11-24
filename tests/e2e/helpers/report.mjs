import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const HERE = path.dirname(fileURLToPath(import.meta.url))
export const SCREENSHOT_DIR = path.resolve(HERE, '..', 'screenshots')
export const REPORT_DIR = path.resolve(SCREENSHOT_DIR, 'reports')
const SUMMARY_PATH = path.join(REPORT_DIR, 'summary.json')

fs.mkdirSync(REPORT_DIR, { recursive: true })

const summaryState = {
  generatedAt: null,
  credentialsProvided: null,
  env: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    browser: process.env.E2E_BROWSER === 'firefox' ? 'firefox' : 'chromium',
    e2eChromeArgs: process.env.E2E_CHROME_ARGS || '',
    e2eHeaded: process.env.E2E_HEADED === '1',
  },
  routes: { discovered: 0, byName: [], skipped: [] },
  overflow: { measured: 0, offenders: [], skipped: [] },
  screenshots: [],
  fonts: { measured: 0, offenders: [], skipped: [] },
  axe: { measured: 0, violations: [], skipped: [] },
  dataTable: { measured: 0, checks: [], skipped: [] },
  select: { measured: 0, checks: [], skipped: [] },
  lighthouse: { measured: 0, scores: [], skipped: [] },
  reducedMotion: { measured: 0, checks: [], skipped: [] },
}

export function writeJson(name, value) {
  const filePath = path.join(REPORT_DIR, name.endsWith('.json') ? name : `${name}.json`)
  fs.mkdirSync(path.dirname(filePath), { recursive: true })
  fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`, 'utf8')
  return filePath
}

export function summary() {
  return summaryState
}

export function recordSkip(group, reason, detail = {}) {
  const bucket = summaryState[group]?.skipped
  if (!bucket) return
  bucket.push({ reason, ...detail })
}

export function recordScreenshot(entry) {
  summaryState.screenshots.push({
    path: path.relative(SCREENSHOT_DIR, entry.path).split(path.sep).join('/'),
    absolutePath: entry.path,
    ...entry,
  })
}

export function flushSummary(extra = {}) {
  summaryState.generatedAt = new Date().toISOString()
  Object.assign(summaryState, extra)
  return writeJson('summary', summaryState)
}

export function reportPath(name) {
  return path.join(REPORT_DIR, name.endsWith('.json') ? name : `${name}.json`)
}

export function screenshotPath(...parts) {
  return path.join(SCREENSHOT_DIR, ...parts)
}
