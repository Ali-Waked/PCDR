#!/usr/bin/env node
/**
 * Scans Vue/Blade/PHP files for translation keys and fails when a key is
 * missing from ar/en lang files.
 *
 * Usage: node scripts/check-translations.mjs
 * Exit 0: no missing keys. Exit 1: missing keys found.
 */
import fs from 'node:fs'
import path from 'node:path'
import { spawnSync } from 'node:child_process'

const root = process.cwd()
const langDir = ['lang', 'resources/lang'].map((d) => path.join(root, d)).find(fs.existsSync)
if (!langDir) {
  console.error('No lang directory found.')
  process.exit(2)
}

function parsePhpArray(src) {
  // Minimal PHP array literal parser for Laravel lang files.
  let i = 0
  const len = src.length
  function ws() { while (i < len && /\s/.test(src[i])) i++ }
  function str() {
    const q = src[i++]
    let out = ''
    while (i < len) {
      const c = src[i++]
      if (c === '\\') { out += src[i++] ?? ''; continue }
      if (c === q) return out
      out += c
    }
    return out
  }
  function value() {
    ws()
    if (src.startsWith('[', i)) return arr()
    if (src.startsWith('array(', i) || src.startsWith('array (', i)) { i = src.indexOf('(', i) + 1; const v = arrBody(')'); return v }
    if (src[i] === "'" || src[i] === '"') return str()
    // number, bool, null, const - consume token
    let t = ''
    while (i < len && !/[,\]\)\s]/.test(src[i])) t += src[i++]
    if (t === 'true') return true
    if (t === 'false') return false
    if (t === 'null') return null
    if (/^-?\d+(\.\d+)?$/.test(t)) return Number(t)
    return t
  }
  function arrBody(end) {
    const items = []
    let isAssoc = false
    while (i < len) {
      ws()
      if (src[i] === end) { i++; break }
      if (src.startsWith('/*', i)) { i = src.indexOf('*/', i) + 2; continue }
      if (src.startsWith('//', i) || src.startsWith('#', i)) { i = src.indexOf('\n', i); if (i < 0) i = len; continue }
      const v = value()
      ws()
      if (src.startsWith('=>', i)) {
        isAssoc = true
        i += 2
        const val = value()
        items.push([v, val])
      } else {
        items.push([items.length, v])
      }
      ws()
      if (src[i] === ',') i++
    }
    if (!isAssoc) return items.map((x) => x[1])
    const obj = {}
    for (const [k, v] of items) obj[String(k)] = v
    return obj
  }
  function arr() { i++; return arrBody(']') }
  // Find return
  const ret = src.indexOf('return')
  if (ret >= 0) i = ret + 6
  ws()
  return value()
}

function loadLang(locale) {
  const dir = path.join(langDir, locale)
  const files = fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => f.endsWith('.php')) : []
  const map = {}
  for (const f of files) {
    const name = f.replace(/\.php$/, '')
    const file = path.join(dir, f)
    // Try php first for accuracy
    const php = `echo json_encode(require ${JSON.stringify(file)}, JSON_UNESCAPED_UNICODE);`
    const res = spawnSync('php', ['-r', php], { cwd: root, encoding: 'utf8', maxBuffer: 10 * 1024 * 1024, timeout: 15000 })
    if (res.status === 0 && res.stdout) {
      try { map[name] = JSON.parse(res.stdout); continue } catch {}
    }
    // Fallback: pure-node parser
    try { map[name] = parsePhpArray(fs.readFileSync(file, 'utf8')) }
    catch (e) { console.error(`Failed to parse ${locale}/${f}: ${e.message}`); process.exit(2) }
  }
  return map
}

function hasKey(lang, file, keyPath) {
  const obj = lang[file]
  if (obj == null) return false
  let cur = obj
  for (const p of keyPath.split('.')) {
    if (cur && typeof cur === 'object' && !Array.isArray(cur) && Object.prototype.hasOwnProperty.call(cur, p)) cur = cur[p]
    else return false
  }
  return true
}

function walk(dir, exts, out = []) {
  if (!fs.existsSync(dir)) return out
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (['node_modules', 'vendor', '.git', 'storage', 'public/build', 'bootstrap/cache'].includes(entry.name)) continue
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) walk(full, exts, out)
    else if (exts.some((e) => entry.name.endsWith(e))) out.push(full)
  }
  return out
}

function lineOf(src, idx) { return src.slice(0, idx).split('\n').length }

function extractKeys(file) {
  const src = fs.readFileSync(file, 'utf8')
  const rel = path.relative(root, file)
  const usesLocale = /useLocale\s*\(/.test(src) || /from\s+['"][^'"]*useLocale/.test(src)
  const usesI18n = /useI18n\s*\(/.test(src) || /from\s+['"][^'"]*useI18n/.test(src)
  const found = []
  const add = (kind, key, idx) => {
    if (!key || key.includes('${') || key.includes('{{') || key.includes('}')) return
    found.push({ kind, key, file: rel, line: lineOf(src, idx), usesLocale, usesI18n })
  }
  for (const re of [/\bt\(\s*['"]([^'"`]+)['"]/g, /\$t\(\s*['"]([^'"`]+)['"]/g]) {
    let m; while ((m = re.exec(src))) add('t', m[1], m.index)
  }
  for (const re of [/__\(\s*['"]([^'"`]+)['"]/g, /\btrans\(\s*['"]([^'"`]+)['"]/g, /@lang\(\s*['"]([^'"`]+)['"]/g, /\btrans_choice\(\s*['"]([^'"`]+)['"]/g]) {
    let m; while ((m = re.exec(src))) add('php', m[1], m.index)
  }
  return found
}

function resolveTKey(f) {
  const key = f.key
  if (key.startsWith('ui.')) {
    // In useI18n, ui.* looks in website.ui; in useLocale, ui.* is ambiguous but usually means ui.php key
    if (f.usesI18n && !f.usesLocale) return [{ file: 'website', keyPath: `ui.${key.slice(3)}` }]
    if (f.usesLocale && !f.usesI18n) return [{ file: 'ui', keyPath: key.slice(3) }]
    return [{ file: 'website', keyPath: `ui.${key.slice(3)}` }, { file: 'ui', keyPath: key.slice(3), anyOf: true }]
  }
  if (key.startsWith('nav.')) return [{ file: 'website', keyPath: `navigation.${key.slice(4)}` }]
  if (key.includes('.')) return [] // page.props or dynamic - skip
  if (f.usesLocale && !f.usesI18n) return [{ file: 'ui', keyPath: key }]
  if (f.usesI18n && !f.usesLocale) return [{ file: 'website', keyPath: `ui.${key}` }]
  // Unknown composable: accept if in either
  return [{ file: 'website', keyPath: `ui.${key}` }, { file: 'ui', keyPath: key, anyOf: true }]
}

function resolvePhpKey(key) {
  if (!key.includes('.')) return []
  const [file, ...rest] = key.split('.')
  return [{ file, keyPath: rest.join('.') }]
}

const ar = loadLang('ar')
const en = loadLang('en')
const locales = { ar, en }

const scanDirs = ['resources/js', 'resources/views', 'app', 'routes']
const files = scanDirs.flatMap((d) => walk(path.join(root, d), ['.js', '.vue', '.ts', '.php']))

const missing = []
const seen = new Set()
for (const file of files) {
  for (const f of extractKeys(file)) {
    const candidates = f.kind === 't' ? resolveTKey(f) : resolvePhpKey(f.key)
    if (!candidates.length) continue
    const anyOf = candidates.length > 1 && candidates.every((c) => c.anyOf)
    const primary = candidates[0]
    const id = `${f.kind}|${f.key}|${primary.file}|${primary.keyPath}|${f.file}`
    if (seen.has(id)) continue
    seen.add(id)
    for (const [loc, lang] of Object.entries(locales)) {
      const ok = anyOf
        ? candidates.some((c) => hasKey(lang, c.file, c.keyPath))
        : hasKey(lang, primary.file, primary.keyPath)
      if (!ok) {
        missing.push({ ...f, locale: loc, resolved: primary, anyOf })
      }
    }
  }
}

if (missing.length) {
  console.error(`Missing translation keys: ${missing.length}`)
  for (const m of missing.slice(0, 300)) {
    const target = m.anyOf ? `one of [${m.resolved.file}.${m.resolved.keyPath}, ui.${m.resolved.keyPath}]` : `${m.resolved.file}.${m.resolved.keyPath}`
    console.error(`- [${m.locale}] ${m.file}:${m.line} ${m.kind}(${m.key}) -> ${target}`)
  }
  if (missing.length > 300) console.error(`... and ${missing.length - 300} more`)
  process.exit(1)
}
console.log(`Translation key check passed (${files.length} files scanned, ${seen.size} keys checked).`)
