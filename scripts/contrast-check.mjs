#!/usr/bin/env node
/**
 * Automated WCAG contrast check for PCDR public pages.
 * Usage: node scripts/contrast-check.mjs [baseUrl]
 * Requires: playwright installed
 */
import { chromium } from 'playwright';

const base = process.argv[2] || 'http://127.0.0.1:8000';
const locales = ['ar', 'en'];
const routes = ['', 'about', 'strategic-areas', 'programs', 'news', 'publications', 'transparency', 'partnerships', 'get-involved', 'contact'];
const viewports = [{ width: 390, height: 844, name: '390x844' }, { width: 1440, height: 900, name: '1440x900' }];

function luminance([r, g, b]) {
  const a = [r, g, b].map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
  return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
}
function contrast(fg, bg) {
  const L1 = luminance(fg), L2 = luminance(bg);
  const lighter = Math.max(L1, L2), darker = Math.min(L1, L2);
  return (lighter + 0.05) / (darker + 0.05);
}
function parseColor(str) {
  const m = str.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  return m ? [+m[1], +m[2], +m[3]] : null;
}

const browser = await chromium.launch({ headless: true });
const failures = [];
let total = 0;
for (const vp of viewports) {
  for (const loc of locales) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    for (const route of routes) {
      const url = `${base}/${loc}${route ? '/' + route : ''}`;
      try { await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 }); }
      catch { try { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 }); } catch(e){ console.error('NAV FAIL', url, e.message); continue; } }
      await page.waitForTimeout(400);
      const checks = await page.evaluate(() => {
        const items = [];
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
          const text = node.textContent.trim();
          if (!text) continue;
          const el = node.parentElement;
          if (!el) continue;
          const style = getComputedStyle(el);
          if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) continue;
          const rect = el.getBoundingClientRect();
          if (rect.width === 0 || rect.height === 0) continue;
          const fontSize = parseFloat(style.fontSize);
          const fontWeight = Number(style.fontWeight) || 400;
          const large = fontSize >= 24 || (fontSize >= 18.66 && fontWeight >= 700);
          let bgEl = el, bg = 'rgba(0,0,0,0)';
          while (bgEl) {
            const bgs = getComputedStyle(bgEl).backgroundColor;
            const m = bgs.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (m && (m[4] === undefined || Number(m[4]) > 0.01)) { bg = bgs; break; }
            bgEl = bgEl.parentElement;
          }
          if (bg === 'rgba(0,0,0,0)') bg = 'rgb(255,255,255)';
          items.push({ text: text.slice(0,40), fg: style.color, bg, fontSize, fontWeight, large, tag: el.tagName, cls: (el.className||'').toString().slice(0,60) });
        }
        return items;
      });
      for (const c of checks) {
        const fg = parseColor(c.fg), bg = parseColor(c.bg);
        if (!fg || !bg) continue;
        const ratio = contrast(fg, bg);
        const required = c.large ? 3 : 4.5;
        total++;
        if (ratio < required) failures.push({ ...c, url, viewport: vp.name, ratio: ratio.toFixed(2), required });
      }
    }
    await context.close();
  }
}
await browser.close();
console.log(`Checked ${total} text nodes, ${failures.length} failures`);
for (const f of failures.slice(0, 200)) console.log(`FAIL ${f.ratio}:1 < ${f.required}:1 ${f.viewport} ${f.url} <${f.tag} class="${f.cls}"> "${f.text}" fg=${f.fg} bg=${f.bg}`);
if (failures.length > 200) console.log(`... and ${failures.length - 200} more`);
process.exit(failures.length ? 1 : 0);
