# Email Design System Redesign Report

**Date:** 2026-11-24  
**Worktree:** `/root/PCDR/pcdr` (primary worktree, branch `main`, HEAD `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`)  
**Classification:** **PASS WITH LIMITATIONS**

This report documents the audit, redesign, verification, and remaining limitations of the PCDR email template system. All changes remain uncommitted. No branch, worktree, reset, clean, stash, restore, broad staging, commit, or push operation was performed.

---

## 1. Scope and method

The objective was to replace the previously fragmented email presentation with one unified, branded, accessible, RTL/LTR-aware, email-client-compatible institutional design system while preserving all business logic, variables, recipients, subjects, delivery behavior, and concurrent user work.

The audit covered:

- Delivered mailables and notifications.
- CMS email-template editing and preview services.
- Database-managed template content and variable interpolation.
- HTML sanitization and iframe sandboxing.
- Public logo assets and URL configuration.
- Rendered output in HTML, RTL/LTR, desktop, and mobile widths.

The following were explicitly out of scope and were not read or modified:

- `.env.mysql.bak`
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md`

`README.md` and `app/Http/Controllers/Admin/DashboardController.php` were preserved. Staged, unstaged, and untracked concurrent user changes were left intact.

---

## 2. Existing architecture before the redesign

Before implementation, the system had two different presentation paths:

1. **Delivered inquiry mail** rendered as a bare fragment:
   - 89-byte `<div dir="auto">…</div>`
   - No doctype, `<html lang>`, header, logo, footer, metadata panel, typography system, or stable layout width.
   - No shared design token source.

2. **CMS preview** rendered a separate approximately 1.6 KB dashboard-style card:
   - Navy header and sand footer.
   - No logo.
   - Different shell from delivered mail.
   - Preview and delivery therefore could not be compared reliably.

The surrounding CMS email-template system already existed as concurrent work: database-managed templates, `TemplateRenderer`, `EmailTemplateContentResolver`, `EmailTemplatePreviewService`, `EmailHtmlSanitizer`, editor components, and sandboxed preview iframes. That work was preserved; the redesign fixed the rendering architecture rather than replacing the CMS.

**Preserved business behavior:**

- Inquiry recipients and reply flow.
- Internal notification recipient and idempotency keys.
- `EmailDeliveryLog` purpose, status, attempts, hashes, and timestamps.
- Subjects and template keys.
- Signed verification and reset URL logic.
- Queued Phase5 mail behavior (`ShouldQueue`, `afterCommit`).
- Database template variables and no PHP/Blade evaluation.

---

## 3. Visual root causes

The visual inconsistency was caused by:

- Two independent renderers for preview and delivery.
- No shared document shell, spacing scale, or color contract.
- No `lang` or `dir` attributes on delivered mail.
- No RTL start-alignment strategy.
- No reliable public logo URL; root-relative or localhost URLs do not work in email clients.
- No table-based outer wrapper or inline-CSS layout, producing inconsistent width and padding.
- No responsive max-width rule for narrow preview/mobile widths.
- No unified button, notice panel, reference panel, or footer component.

The redesign addresses these at the renderer level so every delivered and previewed message inherits the same structure.

---

## 4. Logo asset, public URL strategy, and configuration

The official static logo is used:

- Path: `public/assets/pcdr-homepage/logo/pcdr-logo.png`
- SHA-256: `5834fb72446a75cd56ed6a51a06ae1dafcaa56559c62e6906603978eb27ecb9e`
- Animated SVG variants were not used.
- The asset was not redrawn, cropped, or distorted.
- Rendered dimensions: 72 × 72 px with explicit `width`, `height`, and inline CSS.

New configuration in `config/email.php`:

- `email.brand.logo_path`
- `email.brand.logo_url` from `EMAIL_LOGO_URL`
- `email.brand.website_url` from `EMAIL_WEBSITE_URL`
- `email.brand.logo_width`
- `email.brand.logo_height`

`.env.example` documents `EMAIL_LOGO_URL` and `EMAIL_WEBSITE_URL` without inventing a production domain.

URL rules in `EmailMessagePresenter`:

- Must be absolute `https`.
- Must have a public hostname.
- Rejects embedded credentials.
- Rejects `localhost`, private/reserved IPs, and `.local`, `.test`, `.invalid`, `.example`, and `.internal` suffixes.
- If `EMAIL_LOGO_URL` is invalid, the renderer may derive the logo URL from a public HTTPS `app.url` plus the relative logo path.
- If neither source is valid, the logo is omitted rather than emitted as a broken localhost or root-relative URL.

The trusted logo is generated **outside** the sanitized body HTML because `EmailHtmlSanitizer` intentionally strips root-relative and non-HTTP(S) URLs.

Current environment limitation: `app.url` is `http://localhost:8000` and `mail.default` is `log`; therefore no production HTTPS logo URL could be verified. A logo appears only when `EMAIL_LOGO_URL` or a public HTTPS `app.url` is configured.

---

## 5. Final color usage

The canonical palette is registered in `config/email.php` and mirrored as safe defaults in `EmailMessagePresenter`.

| Token | Hex | Role in the redesigned layout |
| --- | --- | --- |
| Navy | `#3E5172` | Registered official primary color in the palette contract. |
| Teal | `#71BCB7` | Top accent stripe and primary action button background. |
| Sand | `#DAC3A3` | Registered official accent in the palette contract. |
| Wheat | `#D7C5A1` | Registered official accent in the palette contract. |
| White | `#FFFFFF` | Card surface and header/content background. |
| Navy 900 | `#25334B` | Primary text, organization name, and preview banner. |
| Navy 800 | `#30415E` | Body copy, footer note, and secondary text. |
| Navy 100 | `#E9EDF2` | Card border and header/footer dividers. |
| Teal 700 | `#397F7B` | Eyebrow, reference label, button border, accent text. |
| Teal 100 | `#EAF5F4` | Notice panel and preview metadata. |
| Sand 100 | `#F7F2EA` | Page background and footer surface. |

The redesigned renderer introduces no new hue. Unrelated pre-existing CMS preview palette keys were left untouched as concurrent user work.

---

## 6. Shared table-based layout and rendering path

`App\Services\EmailMessagePresenter::document()` is now the single source of truth for the institutional shell.

Characteristics:

- Full document: `<!doctype html><html lang="…" dir="…">`.
- `<meta charset="utf-8">`.
- Responsive viewport meta.
- `<meta name="x-apple-disable-message-reformatting">`.
- Table-based structure using `role="presentation"`.
- Predominantly inline CSS; one small media query for screens ≤ 620 px.
- 640 px desktop card, 100% fluid mobile card.
- 32 px desktop padding, 18 px mobile padding.
- Stable marker: `data-pcdr-email="layout-v1"`.
- Header with logo, short name, full organization name, and localized eyebrow.
- Optional title and reference panel.
- Sanitized body region.
- Footer with organization, optional website link, and localized footer note.
- Optional preview banner with `data-email-preview="true"`.

Supporting presentation helpers:

- `previewDocument()` for CMS previews.
- `institutionalMailMessage()` for existing `MailMessage` notifications.
- `actionButton()` for teal call-to-action buttons.
- `noticePanel()` for teal-tinted highlighted notices.
- `bodyHtml()`, `plainText()`, `forEditor()`, and `normalizeLocale()` for shared content handling.

`resources/views/emails/unified.blade.php` and `resources/views/emails/unified-text.blade.php` are minimal pass-through views for `MailMessage`-based notifications. They contain no logic and no executable template code.

---

## 7. Templates and notifications migrated

All eight default templates now render through the shared layout in both Arabic and English:

1. `inquiry_received`
2. `additional_information`
3. `general_response`
4. `thank_you`
5. `unable_to_assist`
6. `follow_up`
7. `inquiry_reply`
8. `inquiry_received_notification`

Delivered and system messages migrated:

- `InquiryReplyMail` — now accepts `recipientLocale` and `reference` and renders through the shared layout.
- `InquiryMailService` — passes locale and reference into the mailable for both reply and internal notification paths.
- `Phase5Mail` — queued notification now uses the shared layout; the double-prefix key `phase5.notification.case_created` was normalized so `phase5.case_created` resolves correctly.
- `AuditLifecycleNotification` — `MailMessage` output wrapped in the shared layout.
- `VerifyEmailNotification` — localized verification email wrapped in the shared layout; signed 60-minute verification URL logic preserved.
- `ResetPasswordNotification` — localized reset email wrapped in the shared layout; signed reset URL and configured expiry behavior preserved.

New localization files:

- `lang/en/email.php`
- `lang/ar/email.php`

These provide layout strings and localized auth email copy.

---

## 8. Arabic RTL and English LTR support

Locale handling is centralized in `EmailMessagePresenter::normalizeLocale()`, using `website.locales` and `website.fallback_locale`.

For Arabic:

- `<html lang="ar" dir="rtl">`
- Body and content containers use `dir="rtl"`.
- Text alignment is `right`.
- Logo appears at the right edge of the header.
- Reference and notice panel borders use a right-side accent.
- Arabic layout/action/auth strings come from `lang/ar/email.php`.

For English:

- `<html lang="en" dir="ltr">`
- Text alignment is `left`.
- Logo appears at the left edge of the header.
- Reference and notice panel borders use a left-side accent.

Reference numbers and template keys are explicitly `dir="ltr"` because they are identifiers, not localized prose.

The design-system test asserts language, direction, alignment, localized alt text, and logo placement behavior for both locales.

---

## 9. Email-client compatibility decisions

Decisions made for broad email-client compatibility:

- Table-based structure instead of CSS grid/flexbox.
- `role="presentation"` on layout tables.
- Inline styles for all critical visual properties.
- Fixed desktop width with `max-width: 640px` and fluid mobile fallback.
- System font stack: `Segoe UI`, `Tahoma`, `Arial`, sans-serif.
- No web fonts, external CSS, JavaScript, SVG layout dependencies, or background images in the shell.
- Explicit text-size adjustment controls.
- Absolute HTTPS URLs only for externally referenced assets.
- No logo emitted when a public HTTPS URL is unavailable.

**Not verified:** Gmail, Outlook, Apple Mail, Yahoo, or any live inbox/client rendering. The work was verified only through generated HTML and Firefox-based rendering with programmatic measurements, as documented in section 13.

---

## 10. CMS editor and preview integration

No frontend Vue files were changed. The existing CMS preview architecture was preserved:

- `srcdoc="preview.html"`
- Sandboxed iframe.
- Arabic/English locale switching.
- Desktop/mobile width controls.
- Safe fixed sample variables.
- No PHP, Blade, script, or user-supplied executable content in the preview.

The material change is that `EmailTemplatePreviewService::build()` now calls `EmailMessagePresenter::previewDocument()`. Preview and delivery therefore share the same header, body typography, buttons, reference panel, footer, palette, and direction logic.

Preview-only metadata is rendered in an outer banner marked `data-email-preview="true"`:

- Preview notice.
- Sender name and address.
- Safe recipient sample.
- Template key.

Delivered mail contains no preview banner. A test asserts this parity boundary explicitly.

Because no Vue/CSS source was changed for this task, `npm run build` was not required. `npm audit` was still run against the current tree.

---

## 11. Security, sanitization, and execution boundaries

`App\Services\EmailHtmlSanitizer` was not changed. It remains an allowlist-based sanitizer for database-managed template bodies:

- Allowed presentation tags only.
- Removes scripts, styles, iframes, objects, embeds, forms, inputs, SVG, MathML, media, and other active content with their content.
- Allows only safe presentation attributes and a restricted inline-style property list.
- Rejects `on*` handlers, control characters, `url()`, `expression()`, `@import`, `behavior:`, `-moz-binding`, `javascript:`, and brace/angle/backslash style payloads.
- Allows only `http(s):`, `mailto:`, and fragments for links; `http(s):` only for images.
- Adds `rel="noopener noreferrer"` to safe links.
- Adds empty `alt` to images without one.

The trusted layout is assembled separately from untrusted body content. All dynamic values in the shell are escaped. Only the sanitized body fragment is inserted unre-escaped into the body region.

Verification confirms the rendered document contains no `<script`, `onclick`, `onerror`, or `javascript:`.

The sandboxed preview iframe was retained. No executable template content is introduced.

---

## 12. Verification: commands and exact results

### Formatting

Command:

```bash
vendor/bin/pint --format agent \
  app/Notifications/InquiryReplyMail.php \
  app/Notifications/Phase5/Phase5Mail.php \
  app/Notifications/AuditLifecycleNotification.php \
  app/Notifications/VerifyEmailNotification.php \
  app/Notifications/ResetPasswordNotification.php \
  app/Services/EmailMessagePresenter.php \
  app/Services/InquiryMailService.php \
  config/email.php \
  lang/en/email.php \
  lang/ar/email.php \
  tests/Feature/EmailDesignSystemTest.php
```

Result:

```json
{"tool":"pint","result":"passed"}
```

Only explicitly changed PHP files were formatted; broad `--dirty` formatting was avoided to protect concurrent user work.

### Targeted tests

```bash
vendor/bin/phpunit tests/Feature/EmailDesignSystemTest.php tests/Feature/WebsiteCmsEmailTemplateTest.php --colors=never --no-progress
```

Result:

```json
{"tool":"phpunit","result":"passed","tests":17,"passed":17,"assertions":338,"duration_ms":10155}
```

```bash
vendor/bin/phpunit tests/Feature/WebsiteCmsInquiryTest.php tests/Feature/AuthenticationTest.php tests/Feature/Phase5CommunicationAutomationTest.php --colors=never --no-progress
```

Result:

```json
{"tool":"phpunit","result":"passed","tests":29,"passed":29,"assertions":416,"duration_ms":17821}
```

### Full Feature suite

```bash
vendor/bin/phpunit --testsuite Feature --colors=never --no-progress
```

Final result:

```json
{"tool":"phpunit","result":"passed","tests":291,"passed":291,"assertions":8074,"duration_ms":157233}
```

An earlier run during implementation recorded 4 unrelated failures in `AdminNavigationReorganizationTest`, `CmsPagesModuleRemovalAndStrategicContentTest`, `CmsWorkflowTest`, and `TranslationKeysTest`. Those failures were resolved in concurrent work before final verification. They were not caused by and were not fixed as part of the email redesign.

### Dependency and diff audits

```bash
composer audit --no-interaction
```

Result:

- `No security vulnerability advisories found.`
- Composer also reported that plugins were disabled for safety in the non-interactive root session.

```bash
npm audit --audit-level=high
```

Result:

- `found 0 vulnerabilities`

```bash
git diff --check
```

Result:

- Exit 0, no output.

---

## 13. Visual verification

Rendered HTML artifacts were written outside the repository to `/tmp/opencode/email-audit/visual/`:

- `new-inquiry-en.html`
- `new-inquiry-ar.html`
- `new-notification-button.html`
- `new-phase5-en.html`
- `new-preview-ar.html`

Firefox Playwright screenshots were generated:

- `inquiry-en-desktop.png`
- `inquiry-ar-desktop.png`
- `notification-button-desktop.png`
- `phase5-en-desktop.png`
- `inquiry-en-mobile.png`
- `inquiry-ar-mobile.png`
- `notification-button-mobile.png`

Programmatic measurements confirmed:

- Logo loaded with `naturalWidth = 640` and rendered at 72 × 72 px.
- Page background: `rgb(247, 242, 234)`.
- Primary text: `rgb(37, 51, 75)`.
- Card width: 640 px desktop, 366 px mobile at the tested viewport.
- No horizontal overflow: `scrollWidth === clientWidth`.
- Header, content, and footer were sequential with no overlap.
- Arabic document placed the logo at the right; English placed it at the left.
- Button background: `rgb(113, 188, 183)` with navy text.

The visual check used Firefox rendering and programmatic DOM/layout measurements. Screenshots were generated but were not manually inspected pixel-by-pixel in this report. No live email-client or production inbox rendering was performed.

---

## 14. Limitations, preserved work, and final classification

### Limitations

1. **Production logo URL unavailable.** The current application URL is `http://localhost:8000` and mail delivery is configured to `log`. The logo is omitted until a public HTTPS `EMAIL_LOGO_URL` or public HTTPS `app.url` is configured. No domain was invented.

2. **No live client verification.** Gmail, Outlook, Apple Mail, and other real inbox clients were not tested. Compatibility decisions are based on conservative table-based email practices and Firefox rendering only.

3. **Authenticated browser verification unavailable.** Earlier attempts lacked usable `E2E_EMAIL` and `E2E_PASSWORD`; Chromium headless rendering was unstable in this environment, while Firefox worked.

4. **Concurrent work preserved, not owned.** Many tracked and untracked changes in the worktree belong to parallel CMS, navigation, RBAC, audit, and UI work. Those changes were not reset, staged broadly, or modified except where the email integration required a targeted change.

5. **All changes remain uncommitted.** No commit, push, branch, or worktree operation was performed.

### Deliverable status

- Unified renderer: implemented.
- Delivered mail and CMS preview parity: implemented and tested.
- Official logo handling: implemented with strict public HTTPS validation.
- Official palette: configured and applied by the renderer.
- RTL/LTR: implemented and tested.
- All default templates: migrated and tested in both locales.
- Notifications and auth emails: migrated and tested.
- Sanitization and sandboxing: preserved and tested.
- Formatting, targeted tests, full Feature suite, audits, and diff check: passed.
- Visual rendering: measured in Firefox at desktop and mobile widths.

### Final classification

**PASS WITH LIMITATIONS**

The email design system is functionally complete and verified within the available environment. The classification is not an unconditional pass because the production HTTPS logo URL is not verifiable from the current configuration and no live major email-client rendering was performed.
