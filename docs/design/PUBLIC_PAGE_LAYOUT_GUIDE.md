# PCDR — Public Page Layout Guide

**Status:** Implementation guide for all public inner pages. Follow the approved homepage only for visual reference; do not import or modify its stylesheet.

---

## 1. Mandatory page shape

Every public inner page is an Inertia/Vue page with a single root element:

```vue
<script setup>
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import PublicCta from '../../Components/Public/PublicCta.vue'
import { useI18n } from '../../composables/useI18n'

const { t, localPath } = useI18n()
</script>

<template>
    <PublicLayout :meta="meta" page-class="example-page">
        <PublicPageHero
            :eyebrow="t('ui.institution')"
            :title="t('example_title')"
            :lede="t('example_intro')"
            :crumbs="[{ label: t('nav.contact'), url: localPath('contact') }, { label: t('example_title') }]"
            :actions="[{ label: t('nav.about'), url: localPath('about'), primary: true }]"
            index="01"
            :badge="t('at_a_glance')"
        />

        <section class="ps-section">
            <div class="ps-container">
                <!-- page-specific composition -->
            </div>
        </section>

        <PublicCta
            :title="t('explore_direction')"
            :lede="t('volunteer_intro')"
            :actions="[{ label: t('nav.involve'), url: localPath('get-involved'), primary: true }]"
        />
    </PublicLayout>
</template>
```

Rules:

- `PublicLayout` owns `<Head>`, `<main id="main">`, the skip link, `SiteHeader`, `FlashMessages`, and `SiteFooter`.
- Import `public-site.css` only in `PublicLayout.vue`.
- Pass controller-provided `meta` to `PublicLayout`; do not duplicate `<Head>` in a page.
- Use `page-class` for narrow page hooks such as `about-page vision-page`.
- One `PublicPageHero` per page. One `PublicCta` when a closing action is warranted.
- Use `<Link>` for internal navigation and `localPath()` for locale-prefixed paths.

---

## 2. Shared component contract

| Component | Required props | Optional props | Use |
|---|---|---|---|
| `PublicLayout` | — | `meta`, `pageClass` | Outer shell, SEO, skip link, header, footer |
| `PublicPageHero` | `title` | `eyebrow`, `lede`, `crumbs`, `variant`, `index`, `badge`, `actions`, default slot | Page opening and breadcrumbs |
| `AboutSubnav` | `items` | `active`, `label` | The seven About focus pages |
| `SectionHeading` | `title` | `eyebrow`, `lede`, `align` | In-section headings |
| `PublicCta` | `title` | `eyebrow`, `lede`, `actions` | Closing call to action |
| `RelatedContent` | `title`, `items` | `eyebrow` | Related pages/articles/publications |
| `Breadcrumbs` | `items` | — | Rendered by `PublicPageHero`; last item is current page |
| `EmptyState` | `title` | `body`, `action` | Published-but-empty states |
| `ArticleCard`, `PillarTile`, `ProgramCard`, `PublicationRow` | page-specific model props | — | Archive and relationship lists |

`PublicPageHero` variants are `navy`, `sand`, and `paper`; use `navy` for institutional overviews, `sand` for strategy and programs, and `paper` for publication/article detail pages.

---

## 3. Page compositions

| Destination | Composition | Notes |
|---|---|---|
| `Public/Home` | Approved `.pcdr-home` composition | Exception: only anchor-to-route href changes; never wrap in `PublicLayout` |
| About overview / vision / mission / values / direction / groups / approach | Hero + `AboutSubnav` + focus narrative + value/path/timeline/ledger + related content + CTA | Seven whitelisted focus values; all share `Public/About` component but have unique SEO and props |
| Strategic areas index | Hero + pillar rows/tiles + direction link | Uses published pillars only |
| Strategic area detail | Hero + statement + programs/projects + publications + related articles + other pillars | Distinguish lifecycle `status` from workflow `workflow_status` for projects |
| Programs index | Hero + grouped program panels + project links | No invented targets |
| Program detail | Hero + overview + projects + timeline/notice + related content | Program data comes from published records |
| Project detail | Hero + metadata + program relationship + dates + related content | Use `start` / `end` translation keys |
| News index | Hero + toolbar/search + featured article + article grid + pagination | Filters use existing `categories` and `filters` props |
| News detail | Paper hero + article body + share/meta + related content | `shareUrl` is controller-provided |
| Publications index | Hero + filters + publication rows + empty state + pagination | Restricted rows expose no download URL |
| Publication detail | Paper hero + metadata + document status + related publications | Restricted documents show the restriction notice, not a link |
| Transparency | Navy hero + principles + governance documents + disclosure panels | Only verified documents and notices |
| Partnerships | Hero + principles + areas + partnership form + CTA | Categories are aspirational unless CMS records say otherwise |
| Get involved | Hero + tabbed forms/intro + CTA | Tabs use `aria-selected`; inquiries use existing endpoints |
| Contact | Hero + verified contact details + contact form + CTA | If no verified details, show the explicit empty-state notice |
| CMS page | Paper hero + title/summary/body + CTA | Published-only; no editorial invention |

---

## 4. Content and translation rules

- Use `useI18n()` in Vue. `t('ui.key')` reads `website.ui`, `t('nav.key')` reads `website.navigation`, and bare keys read `website.ui`.
- Do **not** use the legacy `navigation.*` namespace in Vue; it has no matching shared prop. Use `nav.involve`, `nav.pillars`, `nav.programs`, and so on.
- Do **not** request a `dates` key; use existing `start` and `end` keys.
- Do not use `LocaleSwitcher.vue` or `Composables/useLocale.js` on public pages; the approved `LanguageSwitcher.vue` is hash-locked.
- Controller copy uses `__('website.*', [], $locale)` with explicit locale.
- All public reads go through `ContentQuery`; drafts and scheduled records must remain hidden.
- Never invent achievements, beneficiaries, donors, partners, staff, dates, certifications, funding, statistics, or institutional decisions. Planning figures remain labelled as planning targets.

---

## 5. Responsive and RTL checklist

Before considering a page complete:

- [ ] One `.ps-hero`; all other sections use `.ps-section` or a documented variant.
- [ ] Contents live inside `.ps-container`; no full-bleed text.
- [ ] Grids use `minmax(0, 1fr)` where needed; no horizontal overflow at `1280px`, `860px`, or `560px`.
- [ ] Collapsed navigation, subnav, and footer remain usable in both directions.
- [ ] Spacing and positioning use logical properties (`inline`, `block`, `start`, `end`).
- [ ] Arabic and English strings both exist; `lang`, `dir`, canonical, and hreflang come from `meta`/shared props.
- [ ] Empty published lists use `EmptyState`; restricted records use the restriction notice.
- [ ] Reveal animations use `v-reveal` and therefore respect reduced motion.
- [ ] Focus order starts with the shared skip link and reaches every control.

---

## 6. Change control

- New page-specific styles go under an existing page hook in `public-site.css`, still inside `.public-shell`.
- New reusable primitives must be added to both this guide and `PUBLIC_WEBSITE_DESIGN_SYSTEM.md`.
- New destinations require an update to `PUBLIC_WEBSITE_NAVIGATION_MAP.md`, a real controller route, and a regression test.
- A homepage change requires a fresh baseline capture and explicit approval; it must not be made incidentally through an inner-page fix.
