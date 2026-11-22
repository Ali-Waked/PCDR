# PCDR — Public Website Motion Guide

**Status:** Implemented for inner pages. The approved homepage keeps its own cinematic motion layer and is not modified by this guide.

---

## 1. Principles

- Motion explains hierarchy and state; it never carries essential meaning by itself.
- Inner pages are quieter than the homepage: restrained entrances, short hover shifts, and slow decorative rings only in the hero and closing CTA.
- Every animated element has a reduced-motion fallback that shows final state immediately.
- All inner-page animation is scoped under `.public-shell`; homepage keyframes and timings remain in `.pcdr-home`.

---

## 2. Tokens

| Token | Value | Use |
|---|---|---|
| `--ps-fast` | `0.2s` | Hover, focus, underline, burger, and button transitions |
| `--ps-normal` | `0.7s` | Section reveal |
| `--ps-stagger` | `0.09s` | Delay between revealed siblings |
| `--ps-dist` | `28px` | Reveal travel distance |
| `--ps-ease` | `cubic-bezier(0.4, 0, 0.2, 1)` | Standard material easing |
| `--ps-ease-em` | `cubic-bezier(0.16, 1, 0.3, 1)` | Emphasized entrance easing |

Do not introduce a slow inner-page token. The homepage's `1.2s` cinematic duration is deliberately not reused.

---

## 3. Motion inventory

| Name / hook | Duration and easing | Applied to | Behavior |
|---|---|---|---|
| Hero copy entrance | `1s var(--ps-ease-em)` with `0/.1/.2/.3/.4s` child delays | `.ps-animate .ps-hero__copy > *` | From `opacity:0`, `translateY(24px)` to final state when `.is-ready` is added |
| Hero art entrance | `1.4s` opacity / `1.6s` transform, `0.2s` delay | `.ps-hero__art` | From `scale(0.92)` and transparent to final state |
| `ps-spin` | `46s linear infinite` | `.ps-hero__ring` | Slow clockwise rotation |
| `ps-spin reverse` | `70s linear infinite reverse` | `.ps-hero__orbit` | Slow counter-rotation |
| `ps-ring` | `6s ease-out infinite`, second pulse delayed `3s` | `.ps-cta::before/::after` | Expanding, fading ring |
| Timeline draw | `2.2s var(--ps-ease-em)`, `0.2s` delay | `.ps-timeline::before` when `.is-visible` | Spine scaleY from `0` to `1` |
| Section reveal | `var(--ps-normal) var(--ps-ease-em)` with `calc(var(--i, 0) * var(--ps-stagger))` | `.reveal.is-visible` | Opacity and `translateY(var(--ps-dist))` |
| Button hover | `var(--ps-fast)` | `.ps-btn` | `translateY(-2px)`, active `scale(0.98)` |
| Nav underline | `var(--ps-fast) var(--ps-ease-em)` | `.ps-nav__link::after`, `.ps-subnav__link::after` | `scaleX(0 → 1)` |
| Row hover | `var(--ps-fast)` | `a.ps-row` | Sand surface and inline padding shift |
| Card hover | `var(--ps-fast)` | `a.ps-card` | `translateY(-4px)` with elevated shadow |
| Text-link hover | `var(--ps-fast)` | `.ps-text-link::after` | Arrow slides `6px`, direction switches in RTL |
| Burger state | `var(--ps-fast)` | `.ps-burger[aria-expanded="true"]` | Three bars morph to close icon |

---

## 4. Implementation hooks

`resources/js/app.js` owns the global reveal wiring:

1. Adds `js-motion` to `<html>` when a document exists.
2. Computes `reducedMotion` from `matchMedia('(prefers-reduced-motion: reduce)')`.
3. Registers the global `v-reveal` directive. On mount it adds `.reveal` and either:
   - adds `.is-visible` immediately when reduced motion or `IntersectionObserver` is unavailable, or
   - observes the element with `rootMargin: '0px 0px -6% 0px'` and `threshold: 0.04`, then adds `.is-visible` once and unobserves.
4. Removes the observer on unmount.
5. Calls `syncDocument()` on load and on `router.on('navigate')` so `lang`, `dir`, and `data-locale` stay correct during Inertia visits.

`PublicLayout.vue` adds `.is-ready` to the `.public-shell.ps-animate` root on mount, which triggers the hero copy and art entrances.

Usage:

```vue
<div class="ps-card" v-reveal :style="{ '--i': index }">
    <!-- content -->
</div>
```

Use `v-reveal` for content that enters the viewport. Do not attach observers inside individual pages; the global directive already handles registration and cleanup.

---

## 5. Reduced motion

The final `@media (prefers-reduced-motion: reduce)` block in `public-site.css`:

- sets `scroll-behavior: auto`;
- applies `animation: none !important` and `transition: none !important` to every descendant, `::before`, and `::after` inside `.public-shell`;
- forces `.reveal`, hero copy, and hero art to `opacity: 1 !important` and `transform: none !important`;
- forces the timeline spine to `transform: none`.

`app.js` independently prevents the observer from running in reduced-motion mode and marks reveal elements visible immediately. This double guard means content is never hidden when motion is disabled at either the CSS or JavaScript layer.

The approved homepage has its own reduced-motion rules inside `pcdr-home.css`; they remain unchanged and continue to hide the intro splash, show SVGs, stop rings, and make the marquee scrollable.

---

## 6. Rules

**Do**

- Reuse existing durations, easing tokens, and keyframes.
- Keep decorative rings `aria-hidden="true"` and `pointer-events: none`.
- Register one-time entrances through `v-reveal` or `.is-ready`.
- Verify that content is readable before JavaScript runs.

**Do not**

- Add infinite animation to body copy, cards, forms, or tables.
- Use animation alone to communicate status, restriction, selection, or errors.
- Use homepage keyframes (`draw`, `ray`, `drip`, `marquee`, `sheen`, etc.) on inner pages.
- Remove or weaken a reduced-motion rule to make an animation visible.
