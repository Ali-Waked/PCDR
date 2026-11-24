import { spawnSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { PROJECT_ROOT } from './auth.mjs'

const ADMIN_NAME = /^(admin\.|cms\.|operations\.|phase5\.)/
const ADMIN_ROUTE = (name) => ADMIN_NAME.test(name) || name === 'dashboard' || name.endsWith('.dashboard')
let fixtureCache = null

export const routeDiscovery = {
  source: null,
  error: null,
}

function normalizeUri(...parts) {
  return parts
    .flatMap((part) => String(part).split('/'))
    .filter(Boolean)
    .join('/')
}

/** Last-resort static discovery when the application cannot boot route:list. */
function discoverRoutesFromFiles() {
  const files = ['routes/web.php', 'routes/operations.php', 'routes/phase5.php']
  const resourceKeys = []
  const operationsConfig = path.join(PROJECT_ROOT, 'config', 'operations.php')
  if (fs.existsSync(operationsConfig)) {
    const config = fs.readFileSync(operationsConfig, 'utf8')
    const resourcesBlock = config.match(/'resources'\s*=>\s*\[([\s\S]*?)\n\s*\],/)?.[1] || ''
    for (const match of resourcesBlock.matchAll(/^\s{8}'([a-z0-9_-]+)'\s*=>\s*\[/gm)) {
      resourceKeys.push(match[1])
    }
  }

  const routes = []
  for (const file of files) {
    const fullPath = path.join(PROJECT_ROOT, file)
    if (!fs.existsSync(fullPath)) continue
    const lines = fs.readFileSync(fullPath, 'utf8').split(/\r?\n/)
    const groups = []
    let depth = 0
    let statement = ''
    let statementStart = 0

    const stripStrings = (line) => line.replace(/'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"/g, '')
    const active = () => groups.filter((group) => group.opensAt <= depth)

    for (let index = 0; index < lines.length; index += 1) {
      const line = lines[index]
      const clean = stripStrings(line)

      if (!statement && (/Route::get\s*\(/.test(line) || /Route::match\s*\(\s*\[[^\]]*['"]GET['"]/.test(line))) {
        statement = line.trim()
        statementStart = index + 1
      } else if (statement) {
        statement += ` ${line.trim()}`
      }

      const prefixes = [...line.matchAll(/(?:Route::|->)?prefix\s*\(\s*'([^']+)'\s*\)/g)].map((match) => match[1])
      const asNames = [...line.matchAll(/(?:Route::|->)?as\s*\(\s*'([^']+)'\s*\)/g)].map((match) => match[1])
      const groupNames = [...line.matchAll(/->name\s*\(\s*'([^']+)'\s*\)/g)].map((match) => match[1])
      const looksLikeGroup = /->group\s*\(/.test(line) || /Route::prefix\s*\(/.test(line)
      if (looksLikeGroup && (prefixes.length || asNames.length || groupNames.length)) {
        groups.push({ opensAt: depth + 1, prefixes, names: [...asNames, ...groupNames] })
      }

      const previousDepth = depth
      depth += (clean.match(/\{/g) || []).length - (clean.match(/\}/g) || []).length
      if (depth < previousDepth) {
        while (groups.length && groups[groups.length - 1].opensAt > depth) groups.pop()
      }

      if (statement && (line.includes(';') || index === lines.length - 1)) {
        const localName = statement.match(/->name\s*\(\s*'([^']+)'\s*\)/)?.[1]
          || statement.match(/->name\s*\(\s*"([^"]+)"\s*\)/)?.[1]
        const uriMatch = statement.match(/Route::get\s*\(\s*'([^']+)'/)
          || statement.match(/Route::get\s*\(\s*"([^"]+)"\s*/)
        const dynamicMatch = statement.match(/Route::get\s*\(\s*\$path(?:\s*\.\s*'([^']*)')?/)
        const groupInfo = active()
        const prefixParts = groupInfo.flatMap((group) => group.prefixes)
        const namePrefix = groupInfo.flatMap((group) => group.names).join('')
        if (localName && dynamicMatch && resourceKeys.length) {
          const suffix = dynamicMatch[1] || ''
          for (const resource of resourceKeys) {
            routes.push({
              name: `${namePrefix}${localName.replace(/\{\$resource\}/g, resource)}`,
              uri: normalizeUri(...prefixParts, resource, suffix),
              line: statementStart,
            })
          }
        } else if (localName && uriMatch) {
          routes.push({
            name: `${namePrefix}${localName}`,
            uri: normalizeUri(...prefixParts, uriMatch[1]),
            line: statementStart,
          })
        }
        statement = ''
      }
    }
  }

  return routes
    .filter((route) => ADMIN_ROUTE(route.name))
    .sort((a, b) => a.uri.localeCompare(b.uri))
}

/** Discover GET admin/CMS/dashboard routes from Laravel, with static fallback. */
export function discoverAdminRoutes() {
  const result = spawnSync('php', ['artisan', 'route:list', '--json'], {
    cwd: PROJECT_ROOT,
    encoding: 'utf8',
    timeout: 30_000,
  })
  if (result.status === 0) {
    try {
      const routes = JSON.parse(result.stdout)
        .filter((route) => {
          const name = String(route.name || '')
          const methods = String(route.method || '').split('|').map((method) => method.trim())
          return ADMIN_ROUTE(name) && methods.some((method) => method === 'GET' || method === 'HEAD')
        })
        .map((route) => ({ name: String(route.name), uri: String(route.uri) }))
        .sort((a, b) => a.uri.localeCompare(b.uri))
      routeDiscovery.source = 'php artisan route:list --json'
      routeDiscovery.error = null
      if (routes.length) return routes
    } catch (error) {
      routeDiscovery.error = `route:list JSON parse failed: ${error.message}`
    }
  } else {
    routeDiscovery.error = `php artisan route:list --json failed (${result.status}): ${(result.stderr || result.stdout).trim()}`
  }
  const routes = discoverRoutesFromFiles()
  routeDiscovery.source = 'static route files fallback'
  return routes
}

/** Read existing record IDs with a read-only artisan tinker query. */
export function loadFixtureIds() {
  if (fixtureCache) return fixtureCache
  const tables = [
    'users', 'roles', 'permissions', 'media',
    'articles', 'strategic_pillars', 'programs', 'projects', 'publications',
    'contact_messages', 'volunteer_inquiries', 'partnership_inquiries',
  ]
  const php = [
    '$ids = [];',
    `foreach (${JSON.stringify(tables)} as $table) {`,
    '  try { $ids[$table] = \\Illuminate\\Support\\Facades\\DB::table($table)->min(\'id\'); }',
    '  catch (\\Throwable $e) { $ids[$table] = null; }',
    '}',
    'echo "PCDR_FIXTURES=".json_encode($ids);',
  ].join('\n')
  const result = spawnSync('php', ['artisan', 'tinker', '--execute', php], {
    cwd: PROJECT_ROOT,
    encoding: 'utf8',
    timeout: 30_000,
  })
  if (result.status !== 0) {
    fixtureCache = {}
    return fixtureCache
  }
  const match = result.stdout.match(/PCDR_FIXTURES=(\{[^\r\n]*\})/)
  if (!match) {
    fixtureCache = {}
    return fixtureCache
  }
  try {
    fixtureCache = JSON.parse(match[1])
  } catch {
    fixtureCache = {}
  }
  return fixtureCache
}

const CONTENT_TABLES = {
  'cms.news': 'articles',
  'cms.pillars': 'strategic_pillars',
  'cms.programs': 'programs',
  'cms.projects': 'projects',
  'cms.publications': 'publications',
}

const PREVIEW_RESOURCES = [
  { resource: 'news', table: 'articles' },
  { resource: 'programs', table: 'programs' },
  { resource: 'projects', table: 'projects' },
  { resource: 'pillars', table: 'strategic_pillars' },
  { resource: 'publications', table: 'publications' },
]

const PARAM_TABLES = {
  user: 'users',
  role: 'roles',
  permission: 'permissions',
  media: 'media',
  inquiry: 'contact_messages',
}

/** Replace route placeholders with real seeded IDs; never fabricate an ID. */
export function resolveRoutePath(route, locale, fixtureIds) {
  const state = { previewTable: null }
  const path = route.uri.replace(/\{([^}?]+)\??\}/g, (token, rawName) => {
    const name = String(rawName)
    if (name === 'locale') return locale
    if (name === 'type') return 'contact'

    if (name === 'resource') {
      const choice = PREVIEW_RESOURCES.find((entry) => fixtureIds[entry.table])
      if (!choice) return token
      state.previewTable = choice.table
      return choice.resource
    }

    let table = PARAM_TABLES[name]
    if (name === 'content') {
      table = state.previewTable || CONTENT_TABLES[route.name] || null
    }
    const id = table ? fixtureIds[table] : null
    return id ? String(id) : token
  })

  if (/\{[^}]+\}/.test(path)) {
    return { path: null, reason: 'no seeded ID available for route parameter (refusing to invent data)' }
  }
  return { path: `/${path}` }
}

export function routeSlug(name) {
  return String(name).replace(/[^a-zA-Z0-9]+/g, '-').replace(/^-|-$/g, '')
}
