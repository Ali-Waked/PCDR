# Email Templates Redesign Report

Date: 2026-11-24  
Branch: `main`  
Baseline HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
Status: implemented, verified by automated tests/build, browser run skipped because no seeded CMS credentials are configured.

---

## 1. Executive summary

The PCDR email-template management page was redesigned end to end. The page now provides stats, search/filtering, pagination, responsive template cards, a six-step guided creation wizard, an integrated help guide, and a real read-only rendered email preview.

The preview uses the same content resolver and renderer as the real inquiry-mail path, replaces only the seven allowlisted variables with fixed sample data, sanitizes HTML with an allowlist, and is displayed inside a sandboxed iframe. No mail transport is touched by preview requests.

Existing plain-text templates and the existing inquiry reply flow remain compatible. Plain text is still rendered with the original `nl2br(e())` behavior; HTML bodies are sanitized and wrapped in table-based, inline-styled email markup.

---

## 2. Objectives and acceptance criteria

- Redesign the CMS email-template management page for desktop and mobile.
- Provide a guided creation/editing wizard instead of a single long form.
- Provide an in-product help guide with a copyable, renderer-accurate example.
- Show a real rendered email preview using actual renderer/sample data.
- Support Arabic RTL and English LTR throughout.
- Keep preview strictly read-only and never send email.
- Sanitize all editable HTML before save, preview, and mail rendering.
- Preserve existing CRUD behavior, permissions, audit logging, and mail consumers.
- Add backend tests and, where runnable, browser coverage.
- Do not add dependencies, create a worktree, commit, push, or modify protected files.

All acceptance criteria are met except authenticated browser verification, which could not be performed because no `E2E_EMAIL` / `E2E_PASSWORD` or equivalent `.env` credentials are available. A Playwright spec was added and reports four explicit skips rather than claiming visual verification.

---

## 3. Pre-change audit and baseline

A read-only audit was completed before implementation:

- Current branch and HEAD were confirmed as `main` / `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`.
- Existing uncommitted work was inventoried and treated as user-owned.
- Routes, controller, requests, models, services, mailable, frontend page, shared dashboard components, localization files, and existing tests were reviewed.
- The existing template contract was confirmed: plain UTF-8 text/HTML in `subject` / `body`, with `{{ variable }}` tokens; no Blade or PHP evaluation.
- The seven allowed variables were confirmed from `config/email.php`:
  `sender_name`, `sender_email`, `inquiry_subject`, `inquiry_reference`, `reply_message`, `organization_name`, `current_date`.
- The prior page used a two-column form, raw `fetch` preview, and `form.reset(object)`, which is unreliable in the installed Inertia version.

No protected file was read or modified.

---

## 4. Scope boundaries and preservation

### Preserved

- Existing CRUD routes and route names.
- Existing permission keys: `email_templates.view`, `.create`, `.update`, `.delete`.
- Existing audit actions and flash messages.
- Existing sample preview values asserted by the original feature test, including `01JPREVIEW00000000000000000`.
- Existing plain-text mail behavior and `InquiryMailService` public API.
- Existing uncommitted user work, including dashboard, footer, error-page, localization, README, and `DashboardController` changes.
- `CmsDashboardLayout.vue` was not modified; its pre-existing `eyebrow` prop and `actions` slot were used.

### Explicitly untouched

- `.env.mysql.bak` — not read, not modified.
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` — not read, not modified.
- No dependency manifests were changed for this task.
- No Git worktree, branch switch, commit, push, merge, cherry-pick, stash, clean, reset, or broad `git add` was used.

---

## 5. Architecture overview

The redesign centralizes content resolution so preview and real mail cannot drift:

```text
TemplateRenderer (allowlisted interpolation)
        ↓
EmailTemplateContentResolver (DB template → locale fallback → render)
        ↓
EmailMessagePresenter (plain-text legacy rendering OR sanitized HTML wrapper)
        ↓
├── InquiryReplyMail / InquiryMailService  (real mail)
└── EmailTemplatePreviewService            (read-only preview)
```

The controller orchestrates authorization, validation, sanitization on save, and JSON preview responses. The Vue page consumes paginator props and composes four new email-template components plus the shared dialog shell.

---

## 6. Backend: controller, routes, validation

### Controller

`app/Http/Controllers/Cms/EmailTemplateController.php` now provides:

- `index()` with stats, paginated template data, search, status and locale filters, variable catalog, starters, guide example, permissions, and localized strings.
- `store()` / `update()` / `destroy()` with existing permissions, audit records, and sanitized saves.
- `preview()` with a JSON response that keeps legacy `subject` and `body` keys and adds `html`, `plainBody`, `direction`, `locale`, `sender`, `recipient`, `sampleVariables`, `templateKey`, `templateId`, and `isDraft`.
- `duplicate()` and `setStatus()` with `email_templates.create` / `.update` authorization and audit logging.

A localized `guideExample` prop is built from `email.default_templates.inquiry_reply.<locale>` with a language-aware subject label.

### Routes

Added to the existing CMS email-template group in `routes/web.php`:

- `POST /{locale}/cms/email-templates/{template}/duplicate` → `email-templates.duplicate`
- `PATCH /{locale}/cms/email-templates/{template}/status` → `email-templates.status`

Verified with:

```bash
php artisan route:list --name=email-templates --except-vendor
```

Result: 7 expected routes.

### Requests

New form requests validate:

- Template key regex, max length, and uniqueness on store/duplicate.
- Required localized name, subject, and body for every locale.
- Optional description, active state, and sort order.
- Preview `locale`, `template_id`, `subject`, and `body`; `subject` / `body` are required only when no saved template is selected.

### Search fix

The initial search pattern escaped underscores for SQL `LIKE`, which broke keys such as `filter_template_13` on SQLite. It now escapes only `%`, preserving literal underscores while still using bound parameters.

---

## 7. Shared rendering and existing consumers

`EmailTemplateContentResolver` is the single source of truth for resolving a localized field from a database template, falling back to the shipped default template, then rendering allowlisted variables.

`InquiryMailService` now delegates rendering to that resolver instead of duplicating fallback logic. Its public methods and stored `InquiryReply` content are unchanged.

`InquiryReplyMail` uses `EmailMessagePresenter::bodyHtml()`:

- Plain text: `nl2br(e($renderedBody))`, identical to the legacy behavior.
- HTML: allowlist sanitization plus safe email-oriented markup.
- The plain-text alternative remains the rendered body.

This was verified by direct mailable rendering tests and the existing inquiry workflow tests.

---

## 8. HTML sanitization and preview isolation

`EmailHtmlSanitizer` is allowlist-based and strips:

- `script`, `style`, `iframe`, `object`, `embed`, `form`, active media, and similar tags with their content.
- All event handler attributes.
- Unsafe URL schemes; only `http(s):`, `mailto:`, and in-page fragments for links are accepted, while `img src` accepts only `http(s):`.
- Disallowed inline CSS properties and dangerous CSS constructs such as `url()`, `expression()`, `@import`, and `behavior`.
- Comments and disallowed elements (unknown tags are unwrapped, not blindly rendered).

Safe links receive `rel="noopener noreferrer"`; images without `alt` receive an empty `alt`.

`cleanBody()` sanitizes only when `EmailMessagePresenter::isHtml()` detects markup. Legacy plain-text bodies are therefore saved byte-for-byte.

Preview isolation uses both backend sanitization and frontend containment:

```html
<iframe :srcdoc="preview.html" sandbox="" referrerpolicy="no-referrer">
```

The empty `sandbox` disables scripts and same-origin access in the browser, even if sanitization were later changed.

---

## 9. Preview service and sample data

`EmailTemplatePreviewService`:

- Uses fixed sample values for all seven variables.
- Reads sender identity from mail configuration and uses a fixed `.test` sample recipient.
- Returns `direction` based on the requested locale.
- Builds a complete HTML document using `EmailMessagePresenter::previewDocument()` with inline, table-based layout.
- Supports both saved-template previews and unsaved draft overrides.

Saved previews post only `template_id` and `locale`; draft previews post `subject` and `body`. The frontend deliberately uses separate `useHttp` objects so saved previews never accidentally include empty draft keys.

No mail facade is used by the preview path. A feature test uses `Mail::fake()` and `Mail::assertNothingSent()`.

---

## 10. Frontend management page

`resources/js/Pages/Cms/EmailTemplates/Index.vue` was replaced with a professional dashboard page containing:

- Four metric cards: total, active, inactive, recently updated.
- Header actions for the help guide and template creation, gated by permissions.
- Search, status filter, locale filter, clear-filters, and result count.
- Desktop table with template identity, locales, status, updated date, and actions.
- Mobile card list with the same actions, avoiding horizontal table scrolling.
- Pagination driven by `templates.meta` with localized range labels.
- Empty state with a create call to action.
- Delete confirmation via the shared `ConfirmDialog`.
- Direct duplicate and status-toggle actions with busy states.
- Inertia partial reloads after mutating actions.

---

## 11. Guided creation wizard

`TemplateWizard.vue` provides six steps:

1. Information — key, sort order, status, starter layouts, localized name/description.
2. Message — localized subject and variable shortcuts.
3. Design — email-safe contenteditable editor with RTL/LTR toolbar behavior.
4. Variables — catalog of all supported tokens, examples, copy, and insert-at-cursor.
5. Preview — desktop/mobile rendered view, plain-text tab, sender/recipient summary, no email sent.
6. Review — final localized summary before save.

Additional behavior:

- Unsaved-change detection and a discard confirmation.
- Breadcrumb-style step navigation with progress.
- Client validation per step plus server validation surfacing by field.
- `form.defaults(data); form.reset()` is used instead of the unreliable `form.reset(object)` form.
- Starter layouts copy content into a new key without changing the original.
- `beforeunload` warning while dirty.
- Inline iframes use `sandbox=""` and `referrerpolicy="no-referrer"`.

---

## 12. Help guide and variable catalog

`TemplateGuideDialog.vue` presents eight localized steps explaining:

- What a template is.
- Naming and keys.
- Subject writing.
- Email-safe design.
- Variable semantics.
- Variable insertion.
- Preview usage.
- Save and activation.

It includes a copyable example generated from the real `inquiry_reply` default template.

The variable catalog uses flat localization keys (`email_templates.variables_<key>_label` / `_description`). This was corrected during verification after the repository-wide translation-key scan detected a dynamic-prefix false positive. The full translation test now passes.

---

## 13. Bilingual and RTL/LTR behavior

- The page uses the new `lang/en/email_templates.php` and `lang/ar/email_templates.php` groups.
- All UI strings, wizard labels, guide copy, variable labels, preview labels, and flash messages are localized.
- Content editing tabs follow configured locales (`ar`, `en`).
- Preview direction is `rtl` for Arabic and `ltr` for English, including the generated HTML document.
- The editor toolbar swaps start/end alignment commands based on content direction.
- Dates use the existing `useI18n().formatDate()` localization helper.
- Logical CSS properties (`inline-size`, `margin-inline`, `padding-inline`, etc.) are used throughout.

---

## 14. Responsive design and accessibility

- Desktop: table view; mobile: card list.
- Metric grid collapses from four columns to two to one.
- Dialogs become full-screen on small viewports.
- Wizard step navigation scrolls horizontally on narrow screens.
- The shared `EmailTemplateDialog` provides focus trapping, Escape handling, focus restoration, `role="dialog"`, `aria-modal`, and accessible labels.
- Tabs, segmented controls, selects, buttons, and iframes have explicit labels/titles.
- Visual-only labels use clipped text rather than `display: none`, preserving screen-reader access.
- Preview iframe contains no interactive content because of the empty sandbox.

---

## 15. Tests added and updated

`tests/Feature/WebsiteCmsEmailTemplateTest.php` was extended from 3 to 9 tests without weakening existing assertions:

1. Existing permission enforcement.
2. Existing create/update/delete with audit.
3. Existing allowlist interpolation and no-evaluation check.
4. Index props: pagination, stats, search, status/locale filters, variable catalog, starters, guide example.
5. Preview sanitization, sample data, sender/recipient, draft flag, and `Mail::assertNothingSent()`.
6. Arabic direction and saved-vs-draft preview sources.
7. Duplicate, duplicate-key rejection, status toggle, audit, and permission checks.
8. Wizard-style save: HTML sanitized, plain text preserved byte-for-byte, editor prop conversion verified.
9. Existing plain-text and HTML mailable compatibility.

Added `tests/e2e/email-templates-redesign.spec.mjs` covering Arabic/English at 1440px and 390px, page render, guide, wizard, preview iframe sandbox, and horizontal overflow. It uses existing credentials-based helpers and skips when credentials are absent.

---

## 16. Verification results

All commands were run from `/root/PCDR/pcdr`.

| Check | Command | Result |
|---|---|---|
| PHP syntax | `php -l` over 15 touched PHP files | 15/15 passed |
| Pint | `vendor/bin/pint --dirty --format agent <touched PHP paths>` | `{"tool":"pint","result":"passed"}` |
| Targeted email tests | `vendor/bin/phpunit tests/Feature/WebsiteCmsEmailTemplateTest.php --colors=never --no-progress` | 9 tests, 158 assertions, passed |
| Inquiry workflow | `vendor/bin/phpunit tests/Feature/WebsiteCmsInquiryTest.php --colors=never --no-progress` | 7 tests, 87 assertions, passed |
| Localization | `vendor/bin/phpunit tests/Feature/LocalizationTest.php --colors=never --no-progress` | 4 tests, 42 assertions, passed |
| CMS scope | `vendor/bin/phpunit tests/Feature/WebsiteCmsScopeTest.php --colors=never --no-progress` | 6 tests, 57 assertions, passed |
| Translation keys | `vendor/bin/phpunit tests/Feature/TranslationKeysTest.php --colors=never --no-progress` | 1 test, passed |
| Full feature suite | `vendor/bin/phpunit --testsuite Feature --colors=never --no-progress` | 236 tests, 5925 assertions, passed |
| Unit suite | `vendor/bin/phpunit --testsuite Unit --colors=never --no-progress` | 3 tests, 10 assertions, passed |
| Route audit | `php artisan route:list --name=email-templates --except-vendor` | 7 routes shown |
| Frontend build | `npm run build` | passed |
| Diff whitespace | `git diff --check` | passed |
| Playwright spec | `npx playwright test --config=tests/e2e/playwright.config.mjs tests/e2e/email-templates-redesign.spec.mjs --reporter=list` | 4 skipped (no credentials) |

Notes:

- Earlier attempts using `php artisan test --compact` timed out in this environment. Direct `vendor/bin/phpunit` runs completed normally, so all reported test results use the direct runner.
- Pint was scoped to the PHP files touched by this task rather than the whole dirty tree, because the working tree contains substantial unrelated user changes that must remain byte-for-byte preserved.
- `npm run build` emitted two non-blocking pre-existing warnings: the optional `fontaine` package is not installed, and the main JS chunk exceeds Vite's 500 kB advisory threshold. The build completed successfully.
- No authenticated browser verification is claimed. Playwright and Chromium are installed, but no CMS credentials are configured; the added spec correctly skips instead of inventing data.

---

## 17. Known limitations and follow-up

- Authenticated visual verification remains for an environment with a seeded CMS user and `E2E_EMAIL` / `E2E_PASSWORD` set. The added spec will then exercise both locales and both viewport widths.
- `document.execCommand()` is used for simple editor formatting. It remains broadly supported but is deprecated; replacing it with a full editor framework would require a new dependency and was intentionally out of scope.
- Email client rendering can still differ in external clients; the preview is rendered through the sanitized inline/table markup, not a full inbox simulator.
- The existing unrelated `PillarController::index()` signature mismatch noted in the audit was not touched.
- No dependency changes were made; no package-lock or Composer manifest changes are attributable to this task.

---

## 18. Final status and handoff

Implementation is complete and all automated checks available in this environment pass. The working tree still contains the pre-existing user modifications alongside the email-template redesign; nothing was committed or pushed.

### Files introduced or materially changed by this task

Backend:

- `app/Http/Controllers/Cms/EmailTemplateController.php`
- `app/Http/Requests/Cms/PreviewEmailTemplateRequest.php`
- `app/Http/Requests/Cms/StoreEmailTemplateRequest.php`
- `app/Http/Requests/Cms/UpdateEmailTemplateRequest.php`
- `app/Services/EmailHtmlSanitizer.php`
- `app/Services/EmailMessagePresenter.php`
- `app/Services/EmailTemplateContentResolver.php`
- `app/Services/EmailTemplatePreviewService.php`
- `app/Services/InquiryMailService.php`
- `app/Notifications/InquiryReplyMail.php`
- `config/email.php`
- `routes/web.php`
- `lang/en/email_templates.php`
- `lang/ar/email_templates.php`

Frontend:

- `resources/js/Pages/Cms/EmailTemplates/Index.vue`
- `resources/js/Components/EmailTemplates/EmailTemplateDialog.vue`
- `resources/js/Components/EmailTemplates/EmailPreviewDialog.vue`
- `resources/js/Components/EmailTemplates/TemplateGuideDialog.vue`
- `resources/js/Components/EmailTemplates/TemplateEditor.vue`
- `resources/js/Components/EmailTemplates/TemplateWizard.vue`
- `resources/js/Components/Dashboard/UiIcon.vue`

Tests and documentation:

- `tests/Feature/WebsiteCmsEmailTemplateTest.php`
- `tests/e2e/email-templates-redesign.spec.mjs`
- `docs/reports/EMAIL_TEMPLATES_REDESIGN_REPORT.md`

Final Git state remains on `main` at the baseline HEAD with all work uncommitted.
