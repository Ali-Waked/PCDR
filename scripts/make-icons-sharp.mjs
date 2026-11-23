#!/usr/bin/env node
import fs from 'node:fs/promises'
import path from 'node:path'
import crypto from 'node:crypto'
import sharp from 'sharp'

const ROOT = process.cwd()
const PUB = path.join(ROOT, 'public')
const MASTER = [
  'public/assets/pcdr-homepage/logo/pcdr-logo-lg.png',
  'public/assets/pcdr-homepage/logo/pcdr-logo.png',
].map((p) => path.join(ROOT, p))

async function findMaster() {
  for (const p of MASTER) {
    try { await fs.access(p); return p } catch {}
  }
  throw new Error('Master logo not found')
}

function pngToIco(images) {
  // images: [{size, buf(png)}]
  const count = images.length
  const header = Buffer.alloc(6)
  header.writeUInt16LE(0, 0)
  header.writeUInt16LE(1, 2)
  header.writeUInt16LE(count, 4)
  const entries = Buffer.alloc(16 * count)
  let offset = 6 + 16 * count
  const parts = [header, entries]
  images.forEach((img, i) => {
    const s = img.size >= 256 ? 0 : img.size
    const base = i * 16
    entries.writeUInt8(s, base)
    entries.writeUInt8(s, base + 1)
    entries.writeUInt8(0, base + 2)
    entries.writeUInt8(0, base + 3)
    entries.writeUInt16LE(1, base + 4)
    entries.writeUInt16LE(32, base + 6)
    entries.writeUInt32LE(img.buf.length, base + 8)
    entries.writeUInt32LE(offset, base + 12)
    offset += img.buf.length
    parts.push(img.buf)
  })
  return Buffer.concat(parts)
}

async function emblem(master, size) {
  // Tight crop of inner emblem: center 54% of the logo, placed on transparent then masked to circle
  const meta = await sharp(master).metadata()
  const dim = Math.min(meta.width, meta.height)
  const cropSize = Math.round(dim * 0.54)
  const left = Math.round((meta.width - cropSize) / 2)
  const top = Math.round((meta.height - cropSize) / 2)
  const inner = Math.round(size * 0.76)
  const pad = Math.round((size - inner) / 2)
  const ring = Math.max(1, Math.round(size * 0.06))

  const cropped = await sharp(master)
    .extract({ left, top, width: cropSize, height: cropSize })
    .resize(inner, inner, { fit: 'cover' })
    .png()
    .toBuffer()

  // circle mask for inner emblem
  const circleMask = Buffer.from(`<svg width="${inner}" height="${inner}"><circle cx="${inner/2}" cy="${inner/2}" r="${inner/2}" fill="white"/></svg>`)
  const emblemCircle = await sharp(cropped)
    .composite([{ input: circleMask, blend: 'dest-in' }])
    .png()
    .toBuffer()

  // white disc background + navy ring
  const bg = Buffer.from(`<svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
    <circle cx="${size/2}" cy="${size/2}" r="${size/2 - ring/2}" fill="white" stroke="#3E5172" stroke-width="${ring}"/>
  </svg>`)

  return sharp(bg)
    .composite([{ input: emblemCircle, left: pad, top: pad }])
    .png({ compressionLevel: 9, palette: false })
    .toBuffer()
}

async function fullIcon(master, size, { bg = null, scale = 1, opaque = false } = {}) {
  const inner = Math.round(size * scale)
  const pad = Math.round((size - inner) / 2)
  const resized = await sharp(master).resize(inner, inner, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).png().toBuffer()
  if (bg) {
    const bgSvg = Buffer.from(`<svg width="${size}" height="${size}"><rect width="${size}" height="${size}" fill="${bg}"/></svg>`)
    return sharp(bgSvg).composite([{ input: resized, left: pad, top: pad }]).png({ compressionLevel: 9 }).toBuffer()
  }
  // transparent square canvas
  return sharp({ create: { width: size, height: size, channels: 4, background: { r: 0, g: 0, b: 0, alpha: 0 } } })
    .composite([{ input: resized, left: pad, top: pad }])
    .png({ compressionLevel: 9 })
    .toBuffer()
}

async function writeFile(file, buf) {
  await fs.mkdir(path.dirname(file), { recursive: true })
  await fs.writeFile(file, buf)
  return buf
}

async function main() {
  const master = await findMaster()
  console.log('Master:', path.relative(ROOT, master))

  const out = {}
  const sizes = [16, 32, 48]
  const favPngs = []
  for (const s of sizes) {
    const buf = await emblem(master, s)
    const name = `favicon-${s}x${s}.png`
    await writeFile(path.join(PUB, name), buf)
    favPngs.push({ size: s, buf })
    out[name] = { size: s, bytes: buf.length }
  }
  const ico = pngToIco(favPngs)
  await writeFile(path.join(PUB, 'favicon.ico'), ico)
  out['favicon.ico'] = { size: '16,32,48', bytes: ico.length }

  const icon192 = await fullIcon(master, 192, { scale: 1 })
  const icon512 = await fullIcon(master, 512, { scale: 1 })
  const maskable = await fullIcon(master, 512, { bg: '#F7F2EA', scale: 0.70 })
  const apple = await fullIcon(master, 180, { bg: '#FFFFFF', scale: 0.78 })
  await writeFile(path.join(PUB, 'icon-192.png'), icon192)
  await writeFile(path.join(PUB, 'icon-512.png'), icon512)
  await writeFile(path.join(PUB, 'icon-maskable-512.png'), maskable)
  await writeFile(path.join(PUB, 'apple-touch-icon.png'), apple)
  out['icon-192.png'] = { size: 192, bytes: icon192.length }
  out['icon-512.png'] = { size: 512, bytes: icon512.length }
  out['icon-maskable-512.png'] = { size: 512, bytes: maskable.length, bg: '#F7F2EA', scale: 0.70 }
  out['apple-touch-icon.png'] = { size: 180, bytes: apple.length, bg: '#FFFFFF', scale: 0.78 }

  // manifest
  const manifest = {
    name: 'PCDR',
    short_name: 'PCDR',
    lang: 'ar',
    dir: 'rtl',
    start_url: '/',
    display: 'standalone',
    theme_color: '#25334B',
    background_color: '#F7F2EA',
    icons: [
      { src: '/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
      { src: '/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
      { src: '/icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
    ],
  }
  await writeFile(path.join(PUB, 'site.webmanifest'), Buffer.from(JSON.stringify(manifest, null, 2) + '\n'))

  // version hash
  const hash = crypto.createHash('sha256')
  for (const f of ['favicon.ico','favicon-16x16.png','favicon-32x32.png','favicon-48x48.png','icon-192.png','icon-512.png','icon-maskable-512.png','apple-touch-icon.png','site.webmanifest']) {
    hash.update(await fs.readFile(path.join(PUB, f)))
  }
  const version = hash.digest('hex').slice(0, 12)
  await writeFile(path.join(PUB, 'icons-version.txt'), Buffer.from(version))

  console.table(out)
  console.log('Version:', version)
  console.log('Note: favicon.svg/safari-pinned-tab.svg not generated (requires verified vector emblem extraction).')
}

main().catch((e) => { console.error(e); process.exit(1) })
