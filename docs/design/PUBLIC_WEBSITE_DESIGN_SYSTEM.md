# PCDR — Public Website Design System

**Status:** Implemented in the isolated design-consistency worktree. This document specifies the inner-page shell only; the approved homepage design remains the source of truth and is hash-locked.

---

## 1. Isolation principle

The approved homepage uses `.pcdr-home` and `resources/css/pcdr-home.css`. Inner pages use `.public-shell` and `resources/css/public-site.css`.

- Every selector in `public-site.css` contains `.public-shell` or `.js-motion .public-shell`.
- Tokens are declared on `.public-shell`, never on `:root`.
- Dashboards render neither root class, so neither public stylesheet can reach them.
- Only `PublicLayout.vue` imports `public-site.css`; individual pages do not import it.
- The global `.skip-link` rule remains in `app.css`; all other inner-page styling is scoped.

**Result:** the homepage DOM, visual identity, and motion layer are untouched. Inner pages inherit the same palette and editorial rhythm without sharing its animation vocabulary.

---

## 2. Tokens

| Token | Value | Role |
|---|---|---|
| `--ps-navy-900` | `#25334b` | Headings, primary dark surfaces |
| `--ps-navy-800` | `#30415e` | Secondary navy surface |
| `--ps-navy` | `#3e5172` | Primary brand / actions |
| `--ps-navy-100` | `#e9edf2` | Hairlines, pale navy surface |
| `--ps-teal` | `#71bcb7` | Accent fill |
| `--ps-teal-700` | `#397f7b` | Accessible teal text and focus ring |
| `--ps-teal-100` | `#eaf5f4` | Pale teal surface |
| `--ps-sand` | `#dac3a3` | Warm accent |
| `--ps-wheat` | `#d7c5a1` | Secondary warm accent |
| `--ps-sand-100` | `#f7f2ea` | Warm page surface |
| `--ps-ink` | `#1d2736` | Body text |
| `--ps-white` | `#fff` | Base surface |
| `--ps-muted` | `#4b5668` | Secondary text |
| `--ps-line` | `#d9dfe7` | Borders and dividers |
| `--ps-pad` | `clamp(1.25rem, 5vw, 4rem)` | Inline page padding |
| `--ps-max` | `1240px` | Maximum content width |
| `--ps-sec` | `clamp(4rem, 9vw, 8rem)` | Section rhythm |
| `--ps-hh` | `76px` | Header height |
| `--ps-r` | `4px` | Control and cover radius |
| `--ps-fast` | `0.2s` | Micro-interactions |
| `--ps-normal` | `0.7s` | Reveal duration |
| `--ps-stagger` | `0.09s` | Stagger step |
| `--ps-dist` | `28px` | Reveal travel distance |
| `--ps-ease` | `cubic-bezier(0.4, 0, 0.2, 1)` | Standard easing |
| `--ps-ease-em` | `cubic-bezier(0.16, 1, 0.3, 1)` | Emphasized entrance easing |
| `--ps-font-ar` | `"IBM Plex Sans Arabic", "Tajawal", "Cairo", system-ui, sans-serif` | Arabic text |
| `--ps-font-en` | `"Inter", system-ui, sans-serif` | English text |

These mirror the homepage palette and spacing values with a `--ps-` prefix. There is no inner-page `--m-slow` token; the cinematic 1.2s motion belongs to the homepage only. Shadows (`0 8px 24px rgba(37,51,75,0.08)`, `0 14px 30px rgba(37,51,75,0.1)`, `0 18px 40px rgba(37,51,75,0.06)`) and z-index levels (`50` header, `30` subnav, `1` content) are intentionally literal because they are component-local.

---

## 3. Typography

| Role | Rule |
|---|---|
| Body | `400 1.0625rem/1.9 var(--ps-font-ar)` |
| English body | `[lang="en"]` uses `var(--ps-font-en)` and line-height `1.7` |
| Headings | Weight `700`, line-height `1.3`, `text-wrap: balance`, `--ps-navy-900` |
| `h1` | `clamp(2.2rem, 5.4vw, 4.4rem)/1.2`; English tightens tracking to `-0.02em` |
| `h2` | `clamp(1.7rem, 3.6vw, 2.9rem)`, `max-width: 20ch` |
| `h3` | `1.2rem` |
| Small text | `.ps-eyebrow`, `.ps-note`, `.ps-lede`, `.ps-meta` and `.ps-pill` |

Do not use Latin letter-spacing on Arabic text. Do not replace the approved fonts or introduce a second font-loading mechanism.

---

## 4. Layout primitives

| Family | Classes | Purpose |
|---|---|---|
| Shell | `.ps-container`, `.ps-section`, `.ps-section--tight`, `.ps-surface-sand`, `.ps-surface-teal`, `.ps-surface-navy` | Width, rhythm, and section surfaces |
| Headers | `.ps-eyebrow`, `.ps-heading`, `.ps-lede`, `.ps-note`, `.ps-actions`, `.ps-text-link` | Editorial section openings and actions |
| Split layouts | `.ps-split`, `.ps-figure`, `.ps-statement` | Two-column narrative and featured statements |
| Navigation | `.ps-header`, `.ps-nav`, `.ps-subnav`, `.ps-burger`, `.ps-header__cta` | Shared site navigation |
| Hero | `.ps-hero`, `.ps-hero__inner`, `.ps-hero__copy`, `.ps-hero__art`, `.ps-ring`, `.ps-orbit`, `.ps-badge` | Distinct inner-page hero with restrained derived geometry |
| Breadcrumbs | `.ps-breadcrumbs` | Page hierarchy with `aria-current="page"` |
| Content | `.ps-rows`, `.ps-row`, `.ps-grid`, `.ps-card`, `.ps-panels`, `.ps-panel` | Index and overview compositions |
| Narrative | `.ps-timeline`, `.ps-quote`, `.ps-values-ledger`, `.ps-value-row`, `.ps-path` | Approach, values, and direction pages |
| Notices | `.ps-notice`, `.ps-empty`, `.ps-meta`, `.ps-pill` | Restrictions, empty states, metadata |
| Archives | `.ps-toolbar`, `.ps-article`, `.ps-cover`, `.ps-mini-list`, `.pagination` | News, publications, and article pages |
| Forms | `.ps-form-card`, `.field` | Contact, volunteer, and partnership inquiries |
| Closing | `.ps-cta`, `.ps-footer`, `.ps-footer__grid`, `.ps-footer__bottom` | Shared closing action and footer |
| Page hooks | `.about-page`, `.pillars-page`, `.programs-page`, `.news-page`, `.publications-page`, `.transparency-page`, `.partnerships-page`, `.involve-page` | Page-specific variation inside the shared shell |

Page classes such as `.vision-page` through `.approach-page` are applied by `About.vue` for focus-specific accents only.

---

## 5. Responsive behavior

| Breakpoint | Behavior |
|---|---|
| `≤1280px` | Header CTA hides; burger appears; nav becomes a scrollable dropdown under the header; hero collapses to one column and art reorders; two-column option for collapsed nav list at `601px–1280px` |
| `≤860px` | Splits, grids, panels, footer, and disclosure layouts become one column; hero art hides; rows and value rows indent text under a number; timeline spine hides; path becomes two columns |
| `≤560px` | Hero top padding accounts for the header; actions become full width; brand subtitle, publication rows, and path collapse; footer bottom stacks |
| `prefers-reduced-motion: reduce` | All animation and transition is removed; revealed content is forced visible |

Overflow containment uses `overflow-x: clip` on `.public-shell`, `overflow-x: clip` on hero/figure/cover/CTA, scrollable subnav, `env(safe-area-inset-*)` padding, and `minmax(0, 1fr)` grid tracks.

---

## 6. RTL and LTR

- Direction is driven by the root `lang`/`dir` attributes set from Inertia props and kept in sync by `resources/js/app.js`.
- The layout relies on logical properties: `margin-inline-*`, `padding-inline-*`, `inset-inline-*`, `inline-size`, `max-inline-size`, `block-size`, `border-inline-start`, and `border-block-*`.
- Only three explicit RTL overrides are needed: text-link arrow direction, timeline spine side, and even value-row gradient direction.
- `nav` and `footer` links, CTA actions, breadcrumbs, and cards mirror automatically.
- Do not use left/right values for new spacing or positioning; use logical properties.

---

## 7. Accessibility and interaction

- `:focus-visible` uses a `3px` teal-700 outline with `3px` offset and `2px` radius.
- Minimum interactive sizes: buttons `48px`, header CTA `44px`, burger `44×44px`, pagination `42×42px`.
- Active navigation uses `.is-active` and `aria-current="page"`; mobile menu uses `aria-expanded` and `aria-controls`; tabs use `aria-selected`.
- The shared skip link remains the first focusable element and targets `<main id="main">`.
- Empty states are explicit, not blank containers; restricted documents expose no download URL.

---

## 8. Rules

**Do**

- Compose pages from the shared primitives and one `.ps-hero` per page.
- Keep page-specific meaning in page classes, not in new global selectors.
- Reuse the existing card, breadcrumb, CTA, and empty-state components.
- Use logical properties and existing `--ps-*` tokens.

**Do not**

- Import `pcdr-home.css` into an inner page or add `.pcdr-home` to a dashboard.
- Add `:root` tokens, a second font stack, or a new CSS framework.
- Invent statistics, partners, beneficiaries, dates, or institutional approvals.
- Animate decorative elements without a reduced-motion fallback.
