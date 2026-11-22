# PCDR — Motion Design System

**Status:** Design specification only. No animation, CSS, composable, or library is added in this phase.

---

## 1. Why motion is a core system here

**[RECOMMENDATION]** For an institutional humanitarian platform, motion must communicate **hierarchy, orientation, feedback, and continuity** — not decoration. PCDR's motion identity should feel calm, precise, and humane: the digital equivalent of a well-edited publication. Movement should help a visitor understand where they are and what changed, especially across the public site, dashboard workflows, and Arabic/English transitions.

**[VERIFIED]** The current project has no motion system beyond browser defaults. `resources/css/app.css` contains only the Tailwind import and a font theme.

---

## 2. Motion principles

| Principle | Rule |
|---|---|
| Purposeful movement | Every animation answers "what changed?" or "where did this come from?" |
| Smooth transitions | Short durations, consistent easing, no abrupt starts/stops |
| Consistent timing | Shared tokens; components never hardcode durations |
| Natural easing | Decelerate on entrance, accelerate on exit, symmetric for state swaps |
| Subtle feedback | Micro-interactions confirm action without demanding attention |
| No unnecessary movement | Static is the default; motion is the exception |
| No continuous distraction | No infinite loops, parallax, or ambient motion in content areas |
| Information never depends on motion | All states are understandable with animation disabled |
| Direction-aware | Slide directions follow `dir` (RTL/LTR) using logical edges |
| Performance-aware | Transform and opacity first; no layout-thrilling properties |

---

## 3. Motion tokens

**[RECOMMENDATION] CSS custom properties (mirrored into Tailwind `@theme` at implementation):**

```css
/* Duration */
--motion-duration-instant: 90ms;   /* micro press feedback */
--motion-duration-fast:    140ms;   /* hover, focus, toggles */
--motion-duration-base:    200ms;   /* standard state changes */
--motion-duration-slow:    320ms;   /* panels, dialogs, page transitions */
--motion-duration-slower:  480ms;   /* hero/editorial entrances only */

/* Easing */
--motion-ease-standard:    cubic-bezier(0.2, 0, 0, 1);
--motion-ease-decelerate:  cubic-bezier(0, 0, 0.2, 1);   /* entering */
--motion-ease-accelerate:  cubic-bezier(0.4, 0, 1, 1);   /* leaving */
--motion-ease-emphasized:  cubic-bezier(0.2, 0, 0, 1);   /* dialogs */

/* Delay & stagger */
--motion-delay-none: 0ms;
--motion-delay-short: 60ms;
--motion-delay-medium: 120ms;
--motion-stagger: 60ms;             /* list children, max 6 items */

/* Distance / scale / opacity */
--motion-distance-xs: 4px;
--motion-distance-sm: 8px;
--motion-distance-md: 16px;
--motion-distance-lg: 24px;
--motion-scale-sm: 0.98;
--motion-scale-base: 1;
--motion-scale-lg: 1.02;
--motion-opacity-hidden: 0;
--motion-opacity-muted: 0.6;
--motion-opacity-visible: 1;

/* Labeled exception — the only repeating effect in the system */
--motion-duration-skeleton-pulse: 1400ms; /* loading skeletons only; §4.12 */
```

**Usage defaults:** entrances use `decelerate` + `base/slow`; exits use `accelerate` + `fast/base`; state swaps use `standard`; dialogs use `emphasized`. Maximum stagger total ≈ 360ms. **No one-shot animation may exceed 480ms**, including hero sequences (F-15). The sole repeating exception is the loading-skeleton pulse token above, explicitly bounded to pending-data states in §4.12; it must not be used in content or ambient decoration.

---

## 4. Animation categories

### 4.1 Page transitions

- **Where:** Inertia navigation between public pages and dashboard sections.
- **How:** Fade (opacity 0→1) plus 8px vertical rise on the page container; outgoing view short accelerate fade.
- **Avoid:** Slide-in from arbitrary sides, long transitions, blocking the next page until animation finishes.

### 4.2 Hero section animations

- **Where:** Public homepage hero only.
- **How:** Sequential entrance: eyebrow → headline → supporting text → primary action, using at most 4 elements with a 60ms stagger and `slower` (480ms) duration, so the **entire sequence stays within the 480ms ceiling**. Media fades in with a subtle scale from `0.98` within the same window.
- **Avoid:** Multiple simultaneous motions, text typing effects, looping background movement, any total sequence longer than 480ms.

### 4.3 Scroll-triggered reveals

- **Where:** Editorial sections, impact figures, long-form content.
- **How:** One shared `IntersectionObserver`; reveal once with opacity + 16px rise; `rootMargin` tuned so content is already visible when animation starts.
- **Avoid:** Revealing per word, re-animating on scroll-up, hiding content from assistive tech.

### 4.4 Staggered content entrances

- **Where:** Lists of articles, project cards, team-free partner listings, table rows on first load.
- **How:** Cap at 6 visible items; 60ms stagger; skip stagger on wide grids or reduced-motion.
- **Avoid:** Long cascades that delay comprehension.

### 4.5 Navigation transitions

- **Where:** Header state change on scroll, active link changes, mobile menu open/close.
- **How:** Background/border transition using `fast`; active indicator slides along the logical inline axis; menu panel uses `slow` with `emphasized`.
- **Avoid:** Fullscreen menu flips, elastic effects.

### 4.6 Dropdown and mega-menu interactions

- **Where:** Public navigation, dashboard action menus.
- **How:** Opacity + 4px vertical translate, `fast`, `decelerate`; keyboard focus drives the same visual state as hover.
- **Avoid:** Hover-only open states, delayed closing that traps focus.

### 4.7 Button micro-interactions

- **Where:** All primary/secondary controls.
- **How:** background/border **color** change on hover using `fast` (140ms) — no positional shift; `scale(0.98)` on active for 90ms; focus ring appears instantly (no fade for focus). Buttons do not use a 4px translate.
- **Avoid:** Bouncing, glow loops, size changes that shift layout.

### 4.8 Card hover interactions

- **Where:** Article/project teasers.
- **How:** Subtle border color change + 4px lift using transform + shadow-sm→shadow-md, `fast`.
- **Avoid:** Rotations, scale > 1.02, parallax.

### 4.9 Statistics count-up animations

- **Where:** Verified institutional figures only (none exist yet).
- **How:** Count from 0 to approved value over **at most 480ms** (the `slower` ceiling) with `Intl.NumberFormat` per locale; respect reduced motion by rendering the final value immediately.
- **Avoid:** Animating fictional or unverified numbers; formatting that ignores locale.

### 4.10 Chart and infographic animations

- **Where:** Analytics dashboards.
- **How:** Fade data in, then animate bar heights/line paths from baseline over `slow`; animate axes only once; tooltips use `instant`.
- **Avoid:** Re-animating on every hover, 3D effects, animation that obscures exact values.

### 4.11 SVG line drawing

- **Where:** Decorative dividers, illustrative icons, simple data paths.
- **How:** Native SVG `stroke-dasharray`/`stroke-dashoffset`, `slow`, `decelerate`; one pass only.
- **Avoid:** Lottie for simple lines, looping strokes.

### 4.12 Loading skeletons

- **Where:** Data tables, dashboards, image-heavy pages.
- **How:** Static skeleton shape with a subtle opacity pulse (0.6→1→0.6, `skeleton-pulse` = 1400ms, linear). This is the **only repeating animation permitted anywhere in the system**, and it applies solely while data is pending: it stops as soon as content renders, is never used in content or ambient areas, and is **disabled under reduced motion**. Skeleton geometry must match final content to prevent layout shift.
- **Avoid:** Shimmer sweeps across the whole viewport, infinite spinners without labels, any pulse that continues after loading completes.

### 4.13 Modal and dialog transitions

- **Where:** Confirmations, forms in dialogs, media preview.
- **How:** Backdrop opacity + panel scale `0.98→1` and 8px rise, `slow` `emphasized`; exit is faster (`base`, `accelerate`); focus moves on open and returns on close.
- **Avoid:** Animating focus movement, closing animations that delay Escape by more than the token duration.

### 4.14 Notification transitions

- **Where:** Toast/in-app notification center.
- **How:** Enter from the logical end edge by 16px + fade, `base` `decelerate`; auto-dismiss uses fade only; stack changes use FLIP-style transforms if needed.
- **Avoid:** Bouncing badges, sound, motion that covers content.

### 4.15 Interactive map transitions

- **Where:** Project/activity geography views.
- **How:** Smooth pan/zoom via the map library's own transitions; marker clusters fade/scale `sm`; tooltip opens `instant`; respect reduced motion by disabling fly-to.
- **Avoid:** Animated path routes, decorative globe spins, motion that hides precise locations.
- **[DECISION REQUIRED]** Whether maps are in scope at all and which provider is acceptable (OQ-36); this must be answered before Phase 4 location schema is designed. No mapping dependency or provider is assumed.

---

## 5. Brand-inspired motion

**[RECOMMENDATION]**

| Motif | Subtle treatment | Where |
|---|---|---|
| Olive branch | Single leaf unfurl (path draw) on section entry | About / values sections, once |
| Hands | Two shapes easing together to "hold" a card on hover | Hero support CTA, rarely |
| Sun | Slow radial opacity bloom behind a headline | Homepage hero only |
| Wheat | Slight sway (≤2° rotation) on a decorative divider | Editorial dividers, once |
| Water | Single soft ripple on form success | Donation/contact confirmation, if approved |

**Rules:** never distort or animate the logo itself; at most one symbolic motif per view; no continuous loops; all motifs decorative and `aria-hidden`; all disabled under reduced motion. Authored CSS/SVG or logo-derived motifs that do not depict real people/events/places and do not reproduce rights-bearing artwork are design assets and do not require OQ-34. OQ-34 rights/consent/publication-rights evidence is required before any actual institutional photo or rights-bearing illustration motif is used; OQ-35 governs animated logo/brand usage. If no approved rights-bearing illustration assets exist, omit those motifs entirely — do not invent symbolism.

---

## 6. Library evaluation

**[RECOMMENDATION] Estimates are qualitative; measure actual bundle impact before adoption.**

| Option | Bundle cost | Complexity | Accessibility | Performance | Maintenance | Usefulness here | Verdict |
|---|---|---|---|---|---|---|---|
| CSS transitions/keyframes + tokens | **None** (native) | Low | Good if reduced-motion overrides exist | Excellent when limited to transform/opacity | Very low | Covers ~80% of needs | **Adopt as foundation** |
| Native SVG animation (CSS/SMIL where appropriate) | None | Low–medium | Decorative only, `aria-hidden` | Excellent | Low | Line drawing, motifs | **Adopt for SVG** |
| IntersectionObserver + tiny composable (WAAPI or classes) | None/minimal custom code | Medium | Must gate on reduced motion | Excellent (one shared observer) | Low | Scroll reveals, stagger | **Adopt as small internal composable** |
| Motion for Vue | Moderate (tens of KB, verify) | Medium | Depends on usage | Good | Community package | Convenient for complex sequences | **Defer** — revisit only if sequences become hard to maintain |
| GSAP (+ plugins) | Larger core, larger with plugins | Medium–high | Manual reduced-motion handling | Good if disciplined | External dependency, licensing to verify | Powerful but unnecessary for this scope | **Do not adopt** |
| Lottie | Player + JSON assets can be large | Medium | Decorative; must be hidden from AT | Can be costly on mobile | Asset pipeline overhead | Only for approved custom illustrations | **Do not adopt now** |

**Recommendation (ADR-013):** CSS tokens + keyframes, native SVG animation, and one small internal `IntersectionObserver` composable. No animation package is installed in Phase 1. This keeps the bundle minimal, works with Tailwind v4 and Vite 8, and keeps accessibility behavior under our control.

---

## 7. Accessibility

**[RECOMMENDATION]**

- Global `@media (prefers-reduced-motion: reduce)` sets durations to `0.01ms` and removes transforms/translates for non-essential motion.
- `useReducedMotion()` composable mirrors the media query for JS-driven reveals and count-ups; count-ups render final values immediately.
- Focus indicators are never animated away; focus changes are instant.
- Essential feedback (validation error, success, destructive confirmation) is conveyed by text/icon/state, not animation.
- Auto-dismissing notifications pause on hover/focus and are dismissible; reduced-motion users receive static messages.
- No animation may autoplay audio, flash more than three times per second, or obscure text.
- RTL direction applies to slide origins: content enters from the inline-start edge.

---

## 8. Performance

**[RECOMMENDATION]**

- Animate only `transform` and `opacity` in CSS; avoid `top`, `left`, `width`, `height`, `margin`, and filters in continuous animations.
- Use `will-change` sparingly and remove it after animation; never apply to many elements permanently.
- One shared `IntersectionObserver` for all reveals; passive listeners; disconnect observers on unmount.
- Target 60fps on mid-range mobile; no more than ~3 simultaneous visible animations.
- Skeletons match final layout to avoid CLS; fonts use `font-display: swap` to avoid invisible text.
- No render-blocking animation JavaScript; motion CSS ships with the main stylesheet.
- Measure with browser performance tooling on the first real content-heavy page; do not pre-optimize.

---

## 9. Component-level motion standards

| Component family | Motion |
|---|---|
| Buttons | press feedback 90ms, hover 140ms |
| Inputs | focus ring instant; error shake **not used** (use color + message) |
| Dialogs | 320ms emphasized enter, 200ms exit |
| Tables | row insert fade 140ms; loading skeleton |
| Cards | hover lift 140ms; reveal 200ms with stagger |
| Navigation | indicator 140ms; panel 320ms |
| Notifications | 200ms enter, fade exit |
| Charts | one-time 320ms data reveal |
| Maps | provider transitions; reduced-motion disables fly-to |
| Page shell | 200ms fade + 8px rise |

---

## 10. Example usage map (illustrative, not implemented)

| Page | Motion palette |
|---|---|
| Homepage | Hero stagger, one brand motif, scroll reveals on sections, stat count-ups (verified figures only) |
| Projects list | Staggered card entrance, card hover lift, filter panel transition |
| Project detail | Page transition, media fade, SVG timeline line draw if approved |
| Complaints dashboard | Fast table transitions, notification enter, dialog for assignment |
| Login | Single quiet form fade; no decorative motion |
| Reports | Chart reveal, skeleton while export queues, static final values |

---

## 11. Governance

- All motion values come from tokens in `MOTION_DESIGN_SYSTEM.md` and `DESIGN_SYSTEM.md`; ad-hoc values in components fail review. One-shot durations above 480ms fail review; the only repeating effect is the explicitly labeled loading-skeleton pulse token (§3/§4.12).
- Every new animation must state its purpose, reduced-motion fallback, and performance impact in the PR description.
- This document and `DESIGN_SYSTEM.md` are reviewed together; motion is not a separate afterthought.
