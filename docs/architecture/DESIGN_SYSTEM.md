# PCDR — Design System

**Status:** Design specification only. No CSS, Tailwind configuration, component, font, or asset is changed in this phase.

---

## 1. Design direction

**[RECOMMENDATION] "Humanitarian Editorial Design + Institutional Excellence + Modern Digital Experience."**

The public website should read like a credible institutional publication: strong typographic hierarchy, generous whitespace, restrained color, purposeful photography, and clear information architecture. The dashboard should be dense and task-focused but built from the same tokens, so the two surfaces feel related without looking identical.

**[VERIFIED]** Current UI is the Laravel placeholder page and a Tailwind v4 theme using Instrument Sans. No design tokens or component library exist.

**Avoid:** generic admin-template layouts, repetitive card grids, decorative gradients, stock-style icon rows, excessive borders/shadows, and animation for its own sake.

---

## 2. Brand palette

**[RECOMMENDATION] The four logo-derived colors are immutable. Do not alter them.**

| Token | Hex | Role |
|---|---|---|
| `--color-navy` | `#3E5172` | Primary brand, headings, primary actions, dark surfaces |
| `--color-teal` | `#71BCB7` | Accent, highlights, active indicators, decorative fills |
| `--color-sand` | `#DAC3A3` | Warm neutral, section backgrounds, dividers |
| `--color-wheat` | `#D7C5A1` | Secondary warm neutral, supporting surfaces |
| `--color-white` | `#FFFFFF` | Base surface |

**Derived supporting tokens** (proposed, subject to contrast verification):

| Token | Suggested value | Purpose |
|---|---|---|
| `--color-ink` | `#1F2A3C` | Body text on light surfaces |
| `--color-muted` | `#5C6B82` | Secondary text |
| `--color-teal-deep` | `#2F6F6A` | Teal text on light surfaces (darker for AA contrast) |
| `--color-navy-soft` | `#EEF1F6` | Subtle tinted surface |
| `--color-border` | `#D9DFE7` | Hairline borders |
| `--color-danger` | `#9B2C2C` | Destructive/error text and borders |
| `--color-success` | `#2F6F4E` | Success text |
| `--color-warning` | `#8A6116` | Warning text |

**[RECOMMENDATION] Contrast guidance**

- Navy on white ≈ 8:1 — safe for text and primary buttons.
- Raw teal on white is roughly 2.2:1 — **do not use for normal-size text**; use as fill, accent, or background with navy text.
- Navy on teal is roughly 4:1 — use for large text or UI elements; use `--color-teal-deep` for normal-size teal text on white.
- Sand/wheat on navy ≈ 4.5:1 — acceptable for large text; verify with tooling.

**[RECOMMENDATION]** All ratios must be verified with an automated contrast checker during implementation; the numbers above are approximate design guidance, not certification. Derived semantic tokens are **not approved** until that verification is recorded in Phase 3. WCAG 2.2 AA target: 4.5:1 normal text, 3:1 large text and UI components, 3:1 non-text contrast for focus indicators and control boundaries.

**Semantic tokens** (consumed by components, not raw hex): `bg-surface`, `bg-surface-alt`, `bg-brand`, `text-ink`, `text-muted`, `text-on-brand`, `border-subtle`, `focus-ring`, `status-*`.

---

## 3. Typography

**[RECOMMENDATION] Candidate pairing: IBM Plex Sans Arabic + Inter.**

| Role | Arabic | Latin | Fallback |
|---|---|---|---|
| Display/headings | IBM Plex Sans Arabic (weights 500–700) | Inter (600–700) | system UI, Noto Sans Arabic |
| Body | IBM Plex Sans Arabic (400–500) | Inter (400–500) | system UI |
| UI/controls | IBM Plex Sans Arabic | Inter | system UI |
| Numeric/data | tabular figures | Inter tabular nums | system mono for references |

**Rules**

- Load fonts with `font-display: swap`, subset where possible, self-host if hosting permits (no external font CDN dependency assumed).
- Never mix the two fonts inside a single word; choose per text run.
- Use relative units (`rem`) and a modular scale: `text-xs 0.75`, `sm 0.875`, `base 1`, `lg 1.125`, `xl 1.25`, `2xl 1.5`, `3xl 2`, `4xl 2.5`, `5xl 3` rem.
- Line heights: body 1.6–1.7 (Arabic needs generous leading), headings 1.15–1.25.
- Arabic letter-spacing: 0 or slight positive; never apply Latin tracking values to Arabic.
- Maximum measure: ~65–75 characters for body text.

**[DECISION REQUIRED]** Font licensing/hosting method and whether the current Instrument Sans configuration is retained for Latin only (OQ-33). The blueprint recommends replacing it with the pairing above; no change is made now. Before Phase 3 typography work, licensing for self-hosted files must be approved and the impact on the existing Vite/fontaine setup checked. Until then the verified Instrument Sans baseline remains unchanged.

---

## 4. Spacing, grid, and layout

| Token | Value |
|---|---|
| Space scale | `0, 1=4px, 2=8px, 3=12px, 4=16px, 5=20px, 6=24px, 8=32px, 10=40px, 12=48px, 16=64px, 20=80px, 24=96px` |
| Container | Public: `max-width 1200px`, centered, `24px` gutters (16px mobile) |
| Dashboard container | Fluid with `max-width 1600px`, `24px` padding |
| Grid | 12 columns on `xl`, 8 on `lg`, 4 on `md`, 1 on small screens |
| Breakpoints | `sm 640`, `md 768`, `lg 1024`, `xl 1280`, `2xl 1536` |
| Section rhythm | Public sections `80–96px` vertical desktop, `48–64px` mobile |
| Logical properties | Use `ps-*`, `pe-*`, `ms-*`, `me-*` so RTL mirrors automatically |

---

## 5. Borders, radii, shadows, elevation

| Token | Value / guidance |
|---|---|
| Border widths | `1px` hairline default, `2px` for active states; no heavy frames |
| Radii | `sm 4px`, `md 8px`, `lg 12px`, `xl 16px`, `full` for avatars/pills |
| Shadows | `none` by default; `shadow-sm` for raised cards, `shadow-md` for dialogs; avoid layered decorative shadows |
| Surfaces | White base; navy for hero/footer; sand/wheat for alternating editorial bands; navy-soft for dashboard panels |
| Elevation order | Base → sticky header → dropdown → dialog → toast |

---

## 6. Component standards

### 6.1 Buttons

| Variant | Use | Style |
|---|---|---|
| Primary | Main action | Navy fill, white text, hover darken, focus ring |
| Secondary | Alternative action | White with navy border and navy text |
| Accent | Sparse positive emphasis | Teal-deep text on light teal tint; never raw teal text on white |
| Ghost | Low-emphasis/toolbar | Transparent, navy text, subtle hover surface |
| Destructive | Deletion, irreversible actions | Danger token, confirmation required |

**States:** default, hover, active, focus-visible, disabled, loading (spinner + `aria-busy`). Minimum touch target 44×44px. Loading state must not shift surrounding layout.

### 6.2 Forms

- Persistent visible labels; placeholder text is never a label.
- Helper text with `aria-describedby`; errors with `role="alert"` and `aria-invalid`.
- Locale-aware inputs: Arabic text fields use `dir="rtl"` automatically, numeric/date/reference fields remain LTR with `inputmode` hints.
- Grouped checkboxes/radios use `fieldset`/`legend`.
- Required indicators are textual/aria, never color alone.
- Disabled and read-only states are visually distinct.
- File inputs show name, size, type, and removal control.

### 6.3 Navigation

- Public header: logo/wordmark, primary sections, language switch, and a search entry **only if OQ-39 is resolved in favour of a search feature**; sticky behavior with shadow on scroll.
- Footer: organized columns, contact/legal links, social links from settings, no invented organizational details.
- Dashboard sidebar: grouped by module, permission-filtered, collapsible on desktop, drawer on mobile.
- Active state uses teal indicator + weight change, not color alone.
- Mega menus (if used) are keyboard reachable, `Escape`-closable, and never rely on hover only.

### 6.4 Cards

- Used sparingly for distinct entities (article teaser, project summary), not as a universal layout grid.
- Structure: optional media, category/eyebrow, title, summary, metadata, action link.
- Whole-card links must be reachable by keyboard with clear focus outline.
- Content clamped consistently; no uneven card heights within a row.

### 6.5 Tables

- Dashboard tables: sticky header, zebra or separated rows, compact density, horizontally scrollable on mobile.
- Column alignment: text follows direction; numeric and reference columns remain LTR with tabular figures.
- Sortable headers expose `aria-sort`; bulk actions explicit with selection count.
- Empty, loading, and error states are part of the table component contract.

### 6.6 Dialogs and alerts

- Dialogs: focus trap, labelled title, `Escape` close, backdrop click only for non-destructive dialogs, restore focus.
- Alerts: `role="status"` for informational, `role="alert"` for errors; icon + text + color; never color-only meaning.
- Confirmation dialogs state exactly what will change and whether it is reversible.

### 6.7 Status indicators

- Status tokens: `draft`, `submitted`, `in_review`, `changes_requested`, `pending_approval`, `approved`, `published`, `archived`, `rejected` (see `BACKEND_ARCHITECTURE.md`). Canonical keys are code/config identifiers; AR/EN labels are localized display text.
- Each status uses label + shape/icon + color; text remains readable in both locales.
- Do not rely solely on green/red; pair with text for color-vision accessibility.

### 6.8 Loading, empty, and error states

| State | Requirement |
|---|---|
| Loading | Skeleton matching final layout; `aria-busy`; no layout shift; motion respects reduced-motion |
| Empty | Explain why empty and offer the permitted next action; never show a blank panel |
| Error | Plain-language localized message, retry where safe, reference/request ID for support |
| Partial/offline | Preserve entered form data; no silent data loss |

---

## 7. Imagery and iconography

**[RECOMMENDATION]**

- **Actual institutional field photography and rights-bearing illustration** must be authentic, consented, and approved for publication; no invented project imagery or fabricated beneficiaries. OQ-34 is a **conditional use gate**: it must be answered with consent, publication-rights/license, and OQ-06 content-owner evidence before such an asset is used in public Phase 3/4 content. It remains DEFERRED because neutral design assets are the documented fallback; no consent or licensing approval is assumed here.
  - **Prototype imagery:** temporary, non-public wireframe/mockup placeholders only; synthetic, with no real people, no institutional claims, and never shipped in a public or production build. It does not answer or consume OQ-34.
  - **Design assets:** tokens, neutral abstract surfaces, icons, a text-only wordmark, and authored CSS/SVG or logo-derived motifs. These do not require OQ-34 unless they depict real people/events/places or reproduce rights-bearing artwork; animated logo/brand usage remains gated by OQ-35.
  - Until verified institutional assets exist, use neutral abstract surfaces, not stock "humanitarian" imagery.
- Icons: single consistent outline style, 1.5–2px stroke, currentColor, decorative icons `aria-hidden`. Never use icon-only controls without accessible names.
- Logo: preserve original colors and proportions; provide clear space; no animation that distorts it. Any logo usage in motion sequences requires an affirmative OQ-35 answer (see `MOTION_DESIGN_SYSTEM.md` §5).

---

## 8. Accessibility baseline

- WCAG 2.2 AA target across public templates and core dashboard flows.
- Keyboard navigation, visible focus (3:1 contrast), semantic landmarks, correct heading order, skip links.
- Screen-reader labels for controls, live regions for async updates, form error summaries.
- RTL/LTR parity via logical properties; mirrored directional icons.
- Reduced-motion overrides in `motion.css` (see `MOTION_DESIGN_SYSTEM.md`).
- Zoom to 200% without loss of content or function; no fixed-height text containers.

---

## 9. Relationship to motion

Motion is a core part of this design system but is specified separately in `MOTION_DESIGN_SYSTEM.md` to keep tokens, library evaluation, accessibility, and performance rules explicit. Every component described above inherits those motion rules; components must not define ad-hoc durations or easings.

---

## 9.1 Phase 3 asset and content gate (F-14)

**[RECOMMENDATION]** No design asset or public claim is approved by this document. Before Phase 3 implementation:

| Gate | Requirement | Source |
|---|---|---|
| Content owner | A named human owner is recorded; every AR/EN institutional claim traces to verified content or remains empty in both locales | OQ-01–OQ-06 |
| Typography | Fonts are licensed, self-hosting method recorded, Vite/fontaine impact checked | OQ-33 |
| Actual institutional imagery | Consent, publication-rights/license, and OQ-06 content-owner evidence documented before any actual institutional photograph or rights-bearing illustration is used; neutral surfaces/icons/text wordmark/authored CSS-SVG motifs are exempt from OQ-34 | OQ-34, OQ-06 |
| Logo/brand motion | Permission for any logo motion recorded; default is a static logo | OQ-35 |
| Search UI | Only built if OQ-39 is resolved and the CMS schema reflects that decision | OQ-39 |

See `IMPLEMENTATION_ROADMAP.md` §5 for the Phase 3 entry gate and `DEPLOYMENT_ARCHITECTURE.md` §8.7 for the go-live checklist. Compliance with this table is a design-review criterion, not evidence that assets are already approved.

---

## 10. Implementation note

**[RECOMMENDATION]** Tokens are to be expressed as CSS custom properties and mirrored into Tailwind v4 `@theme` when implementation begins. The current `resources/css/app.css` and `vite.config.js` font helper are left untouched in Phase 1.
