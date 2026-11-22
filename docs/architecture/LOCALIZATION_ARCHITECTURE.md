# PCDR — Localization Architecture (Arabic / English)

**Status:** Design only. No locale middleware, route group, language file, translation table, or config change is created in this phase.

---

## 1. Verified baseline

**[VERIFIED]**

- `config/app.php`: `locale = env('APP_LOCALE', 'en')`, `fallback_locale = env('APP_FALLBACK_LOCALE', 'en')`, timezone UTC.
- `.env.example` and active `.env` set `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`.
- `resources/views/app.blade.php` hardcodes `lang="{{ str_replace('_', '-', app()->getLocale()) }}"` and has no `dir` attribute.
- No `lang/` directory, locale middleware, localized routes, or translation tables exist.
- PHP `intl` extension is enabled locally **[VERIFIED]**; JavaScript `Intl` is available in modern browsers.

**[DECISION REQUIRED]** Arabic becomes the initial default locale (per project context). This changes the current verified default of `en` in a later implementation phase. OQ-31 must be answered before Phase 2a localization plumbing; no default change is made in Phase 1.

---

## 2. Route convention

**[RECOMMENDATION] Locale-prefixed public routes:**

```text
/                 → redirect to /ar (initial default)
/ar               → homepage (Arabic, RTL)
/ar/about
/ar/projects
/ar/projects/{slug}
/en
/en/about
/en/projects
```

**Administration:** the back office uses the same prefix (`/ar/admin/...`, `/en/admin/...`) so a user's working language is explicit and shareable. **[DECISION REQUIRED]** Whether admin should instead be locale-neutral with a user preference; the provisional default is prefixed for consistency and SEO simplicity. OQ-20 must be answered before Phase 2a routing is finalized; no answer is assumed here.

```mermaid
flowchart TD
    A[Request /ar/projects] --> B[SetLocale middleware]
    B --> C{locale in enabled locales?}
    C -- no --> D[404 or redirect to default locale]
    C -- yes --> E[app()->setLocale + setFallbackLocale]
    E --> F[Share locale, direction, availableLocales to Inertia]
    F --> G[Controller returns localized props]
    G --> H[app.blade.php renders dir=rtl|ltr]
```

---

## 3. Locale resolution and switching

**[RECOMMENDATION]**

| Situation | Resolution order |
|---|---|
| Public route | Locale segment in URL (authoritative) |
| Root `/` | Redirect to configured default (`ar`) |
| Unknown/invalid segment | 404 (do not silently rewrite content language) |
| Admin user preference | Stored on user (`locale` column), applied when generating links/redirects; URL prefix still authoritative |
| Unauthenticated `/login` | Redirect to `/{default}/login` |
| Language switch | Replace the first path segment while preserving query string and remaining path; if target translation is unavailable, stay on the same route and show the fallback notice in admin preview only |

**Rules**

- Locale is never taken from a cookie or `Accept-Language` for public content URLs; that would create duplicate-content and cache ambiguity.
- `Accept-Language` may be used only to choose the initial redirect target at `/` **[DECISION REQUIRED]**; the provisional default is to always redirect to Arabic. OQ-31 governs this and must be answered before the root redirect is implemented in Phase 2a.
- Enabled locales come from a cached settings service, validated against a code allowlist (`['ar', 'en']` initially). Unknown DB values are ignored.
- Switching language must not change authorization or workflow state.

---

## 4. Content translation model

**[RECOMMENDATION] Per-entity translation tables**, not a single generic translations table:

```text
projects            project_translations            (project_id, locale, title, slug, summary, body, meta_*)
pages               page_translations               (page_id, locale, title, slug, body, meta_*)
news_articles       news_article_translations       ...
partners            partner_translations            ...
settings            setting_translations            (setting_id, locale, value)
media               media_translations              (media_id, locale, alt, title, caption)
email_templates     email_template_translations     ...
```

**Rules**

- Unique (`owner_id`, `locale`) per translation table.
- Parent entity owns lifecycle; translations cascade-delete with the parent only when the parent is truly deleted.
- Missing translation → fallback chain: requested locale → Arabic → English → empty placeholder for editorial preview. **Never synthesize content.**
- Slugs are stored per locale and unique per locale per entity type; URL generation uses the active locale's slug.
- User-submitted text (complaints, applications) is stored once with a `locale` marker recording the language of submission; no invented translation.
- Closed code-owned sets (statuses, activity types) use lang files, not database rows.
- Editor-managed taxonomies (categories, service types) use their own translation tables.

**Full localization audit of content types:** see `DATABASE_ARCHITECTURE.md` §5.

---

## 5. UI strings and validation

**[RECOMMENDATION]**

- Laravel lang files: `lang/ar/*.php`, `lang/en/*.php`, grouped by concern (`auth.php`, `validation.php`, `emails.php`, `notifications.php`, `workflows.php`, `common.php`).
- Validation messages use Laravel's translator with locale-aware attributes; field names are translated, not raw column keys.
- Vue receives only the strings needed for the current page via a shared `translations` prop for a small `ui` namespace; large content strings come from props, not client-side translation files.
- Date, time, number, and currency formatting: server uses PHP `Intl` (ext enabled); client uses `Intl` with the active locale. All timestamps stored in UTC and formatted at the edge.
- **[DECISION REQUIRED]** Digit system (OQ-32): Latin digits are the provisional default; Arabic-Indic digits require an explicit product decision and consistent server/client configuration before Phase 3 formatting work.

---

## 6. Direction propagation

**[RECOMMENDATION]**

- `app.blade.php` renders `<html lang="{{ $locale }}" dir="{{ $direction }}">` on the first response from shared props (not hardcoded).
- On Inertia navigation, a small root-level effect updates `document.documentElement.lang` and `dir` when the shared props change.
- CSS uses logical properties and RTL-aware utilities; directional icons (arrows, chevrons) mirror via CSS transforms or `rtl:` variants.
- Charts, maps, and tables are direction-aware: number columns remain LTR-aligned while labels follow document direction.
- Motion direction (slide-in from the "start" edge) follows `dir` via logical transforms; see `MOTION_DESIGN_SYSTEM.md`.

---

## 7. SEO and metadata

**[RECOMMENDATION]**

- Each localized page emits:
  - `<link rel="canonical" href=".../{locale}/...">`
  - `<link rel="alternate" hreflang="ar" href=".../ar/...">`
  - `<link rel="alternate" hreflang="en" href=".../en/...">`
  - `x-default` alternate pointing to the default locale.
- `title`, `meta description`, Open Graph title/description/image come from the active translation, with settings-based defaults.
- `@inertiaHead` in the root view renders per-page head updates through Inertia's head manager.
- Slugs are locale-specific; never translate URLs at runtime without redirect support.
- `robots.txt`, sitemap generation, and default OG image are settings-driven **[DECISION REQUIRED]**; the sitemap/section decision must be recorded before Phase 3 public metadata work.
- Search UI is **conditional**: OQ-39 must be resolved before the CMS schema is frozen and before any search input is built. No full-text index or external search service is assumed. See `DATABASE_ARCHITECTURE.md` §4.2 and `MODULE_MAP.md` A3 (F-13).

---

## 8. Localized email and notifications

**[RECOMMENDATION]**

- Recipient locale resolution order: user preference → submission locale (for applicant-facing messages) → default locale (`ar`).
- Email templates exist per locale; subject and body are both translated; fallback follows §4. For **critical** account-security mail (verification, reset), a missing DB translation falls back to the code-owned `lang/` key and is logged — the send is never skipped (see `COMMUNICATION_ARCHITECTURE.md` §3.1/§9).
- Notification content is localized at dispatch time using the recipient's locale, not at render time in the browser only.
- Queue jobs carry the resolved locale explicitly so a queued message is not rendered with a worker's default locale.
- Dates/amounts in messages use the recipient locale.

---

## 9. Admin translation workflow

**[RECOMMENDATION]**

- Editing screens show both locales side by side with explicit completeness indicators; no auto-translation integration is assumed.
- Publishing guard: a page cannot be published if required translations are missing, unless partial publication is approved under OQ-38 **[DECISION REQUIRED]**. The exception must be bound to a named permission in the catalog (a `*.publish_partial` capability) and audited; "an authorized editor" is not an unnamed blanket exception.
- Audit records include locale and translation changes (before/after).
- Role permissions for translation editing follow the resource's update permission; no separate translation role is proposed.

---

## 9.1 Gate summary (F-14)

**[RECOMMENDATION]** The following questions must be answered before the stated localization/public-content work, not merely before release:

| Gate | Questions | Before |
|---|---|---|
| Routing/locale plumbing | OQ-31 (Arabic default, root redirect, `Accept-Language`), OQ-20 (admin prefix) | Phase 2a localization and route work |
| Public content | OQ-01–OQ-06 (verified institutional claims, named content owner) | First public content entry or editor grant in Phase 3 |
| Assets/typography | OQ-33 (fonts licensed + self-hosted), OQ-34 (conditional: consent/publication rights only before actual institutional imagery), OQ-35 (logo/brand motion) | Phase 3 asset and motion work; neutral design assets may proceed without an OQ-34 answer |
| Search/metadata | OQ-39 (search mode), OQ-32 (digits), OQ-38 (partial publication) | Phase 3 CMS schema and first search UI |

No answer is inferred from this table; each question remains open in `OPEN_QUESTIONS.md`.

---

## 10. Migration path from current baseline

**[RECOMMENDATION]** Ordered future work:

1. Add `lang/ar` and `lang/en` with core UI/validation strings.
2. Introduce `SetLocale` middleware and locale-prefixed route groups.
3. Update root view to emit `lang`/`dir` from shared props.
4. Add translation tables with the first content module.
5. Add locale fields to users and email/notification pipelines.
6. Add SEO alternates and canonical URLs to public pages.

No step is performed in Phase 1.
