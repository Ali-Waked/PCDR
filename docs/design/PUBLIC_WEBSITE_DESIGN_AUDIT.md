# PCDR — Public Website Design Consistency Audit

**Status:** Audit complete for the isolated design-consistency worktree. No governance status, approval status, or institutional decision is changed by this audit.

---

## 1. Scope and base

| Item | Value |
|---|---|
| Worktree | `/root/PCDR/pcdr-design-consistency` |
| Branch | `phase-public-website-design-consistency` |
| Base commit | `eff5fa9123aae3cd8167eff16c9a15f771255dd4` (`phase-4-operational-modules`) |
| Main worktree | `/root/PCDR/pcdr` — read-only; not modified |
| Main branch state | `master` has since received homepage responsiveness commit `8003ee9`; Phase 4 commit `eff5fa9` remains **unmerged**. This worktree does not claim an integrated `master` base |
| Frontend | Existing Vue 3 + Inertia v3 + Laravel stack; no second frontend and no new dependency |

The audit compared the approved homepage baseline with every public inner-page route, the shared shell, CMS navigation, translations, and public regression tests. It is a technical design-consistency audit, not an institutional approval of content.

---

## 2. Approved homepage baseline

The following files were copied from the approved homepage source at the start of isolation. Three are hash-locked by tests; `Home.vue` has one documented exception.

| File | Approved baseline SHA-256 | Worktree SHA-256 | State |
|---|---|---|---|
| `resources/css/pcdr-home.css` | `aaae95b5935177d05b68e52fba22e4b18956c9fb51f67187a300046b1f7513d3` | identical | Hash-locked, unchanged |
| `resources/views/app.blade.php` | `87c820c2c7ef7e5ac864df593c3af0065fef53f8c6e8e6f0c5336ba37f8b94cf` | identical | Hash-locked, unchanged |
| `resources/js/Components/LanguageSwitcher.vue` | `d5182c3b3a9e7f8007b2ffbbdc3fa58b7c3c4c7b6754623ba7c2a9624dda03f1` | identical | Hash-locked, unchanged |
| `resources/js/Pages/Public/Home.vue` | `bfb72fdeccf6d36b338844f287377f3552e41cd47f7649841ce011a2f4c53b53` | `2af8a2a43ab31d08178cde4af5e167b9902b1e6a3c50ccd0ada5085ea618b484` | Intentional href exception only |

**Intentional `Home.vue` exception:** a direct byte comparison against the approved source shows only the addition of `routeFor()` and replacement of static anchor `href` values with bound real-destination URLs. The skip link `href="#main"` is the only remaining in-page anchor. Section order, bilingual copy, DOM structure, tokens, typography, imagery, animations, and responsive hooks were not changed.

**Source drift observed:** during isolation the approved homepage CSS in the main repository changed to SHA-256 `71a6d55b8fa9c425876697dc07772826ace57132ef02beb1e16824f9cdff5b40` and was subsequently committed on `master` as `8003ee9` (`fix: homepage nav/header responsiveness and locale switcher`). That post-capture edit was **not** pulled into this isolated worktree because it was not part of the verified baseline when isolation began. This is recorded as a limitation in §7.

---

## 3. Findings and resolution

| # | Finding before this work | Evidence | Resolution |
|---:|---|---|---|
| 1 | Homepage preview links pointed to anchors such as `#about` and `#areas`, which are dead ends once the homepage section context is left. | Approved `Home.vue` source | Added `routeFor()` mapping every preview anchor to a real locale-prefixed route; only the skip link remains an anchor. |
| 2 | Inner pages did not share one visual shell, so typography, header, breadcrumbs, cards, CTA, and footer varied by page. | Public page components before refactor | All inner pages now render through `PublicLayout.vue` with `.public-shell` and `public-site.css`. |
| 3 | The isolated base registered the homepage twice on the same URI; the duplicate `welcome` name dropped `public.home` from the route collection. | `php artisan route:list` in the base worktree | Removed the duplicate same-URI route; `public.home` resolves again. Matches current main-repo behavior. |
| 4 | Shared header and footer read `navigation.header`, but the CMS seeds `primary` and `footer`. | `WebsiteSeeder.php`, `SiteHeader.vue`, `SiteFooter.vue` | Both components now prefer `navigation.primary`, with a legacy `header` fallback. Regression assertion added. |
| 5 | Several rewritten templates used `navigation.*` instead of the shared `nav.*` namespace. | `useI18n.js` and page sources | Replaced with `nav.involve`, `nav.pillars`, and `nav.programs`. |
| 6 | Two program/project templates requested a missing `dates` translation key. | `lang/ar/website.php`, `lang/en/website.php` | Replaced with existing `start` / `end` keys. |
| 7 | About was a single static treatment rather than a navigable multi-page experience. | `AboutController.php`, `About.vue` | Added seven whitelisted focus pages with distinct titles, descriptions, canonical URLs, and `AboutSubnav`. |
| 8 | Empty states, related content, notices, and forms were inconsistent across archives. | Public page components | Reused `EmptyState`, `RelatedContent`, `PublicCta`, `SectionHeading`, and shared form/notice styles. |

---

## 4. What was preserved

- The approved `.pcdr-home` root, its CSS file, `app.blade.php`, and `LanguageSwitcher.vue` byte-for-byte.
- Homepage section order, static bilingual copy, logo assets, hero art, split-text treatment, scroll progress, marquee, count-up statistics, and reduced-motion behavior.
- Arabic as the default locale with real `/ar` and `/en` routes, correct `lang`, `dir`, and `data-locale`, and locale-aware canonical/hreflang metadata.
- Published-only public reads, signed restricted downloads, inquiry privacy checks, CMS authorization, and all OQ/ADR statuses.
- Existing route names and CMS navigation keys; no new navigation key was invented.

---

## 5. What was added for inner pages

- `resources/css/public-site.css`: an inner-page design system scoped entirely under `.public-shell`.
- Shared components: `PublicPageHero.vue`, `AboutSubnav.vue`, `PublicCta.vue`, `RelatedContent.vue`, plus upgraded header, footer, breadcrumbs, section headings, cards, and empty states.
- Distinct compositions for About focus pages, strategic areas, programs/projects, news, publications, transparency, partnerships, get involved, contact, and CMS pages.
- RTL/LTR logical properties, responsive behavior at `1280px`, `860px`, and `560px`, focus states, and reduced-motion overrides.
- Regression tests for routes, locale shell, About SEO, navigation props, baseline hashes, preview links, and shell menu mapping.

See `docs/design/PUBLIC_WEBSITE_DESIGN_SYSTEM.md`, `docs/design/PUBLIC_PAGE_LAYOUT_GUIDE.md`, `docs/design/PUBLIC_WEBSITE_MOTION_GUIDE.md`, and `docs/design/PUBLIC_WEBSITE_NAVIGATION_MAP.md`.

---

## 6. Verification performed

| Check | Command | Result |
|---|---|---|
| Public regression suite | `php artisan test --compact tests/Feature/PublicWebsiteTest.php` | **28 tests, 1,385 assertions, passed** |
| Full suite | `php artisan test --compact` | **103 tests, 2,093 assertions, passed** |
| Production build | `npm run build` | **Passed**, 672 modules transformed; optional `fontaine` warning only |
| PHP formatting | `vendor/bin/pint --dirty --format agent` | **Passed** |
| Route destination | `php artisan route:list \| grep public.home` | Resolves `GET\|HEAD {locale} .. public.home › Public\HomeController` |
| Baseline hashes | Hash-locked feature test | **Passed** for CSS, Blade root, and language switcher |
| Whitespace | `git diff --check` | **Clean** |
| Isolation | `git status --short` in main worktree | Main worktree left untouched; ignored local `.env` and `vendor` copy not staged |

---

## 7. Limitations

- **Visual verification:** no headless-browser binary or screenshot workflow is available here. Composition, spacing, responsive behavior, and RTL/LTR behavior were verified through source/CSS inspection, route/HTML assertions, and a successful production build, not live screenshots.
- **Moving baseline:** the main worktree's `pcdr-home.css` changed after baseline capture. The isolated worktree retains the verified hash `aaae95…`; no post-capture edit was silently incorporated.
- **Base governance:** Phase 4 commit `eff5fa9…` is the verified base, but it is not merged into `master`. No claim is made that this work sits on an integrated `master`.
- **Automated a11y:** semantic markup, focus states, skip link, and reduced-motion rules were reviewed manually; no axe/Lighthouse audit was run.
- **Fonts:** the approved page keeps the CDN font dependency and the optional `fontaine` fallback warning; no dependency or font hosting change was made.
- **Content:** no achievements, beneficiaries, donors, partners, staff, dates, certifications, funding, statistics, or institutional decisions were invented.

**Audit verdict:** `PASS WITH DOCUMENTED LIMITATIONS`.
