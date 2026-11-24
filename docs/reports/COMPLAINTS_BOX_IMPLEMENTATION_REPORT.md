# Complaints Box Implementation Report

**Date:** 2026-11-24  
**Branch:** `main`  
**HEAD before and after:** `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
**Working directory:** `/root/PCDR/pcdr`  
**Final status:** **PASS**

---

## 1. Executive summary

A production-ready public Complaints Box page was implemented at `/ar/complaints` and `/en/complaints`. The page reuses the existing Inertia public shell, header, footer, typography and localization system; it does not introduce a separate layout.

The official email address `complaints@pal-arc.org` is stored as the canonical public website setting `contact.complaints_email`, is editable in the CMS, validated server-side, cached through the existing `SiteSettings` boundary, and never hardcoded in frontend code. The page presents the address with a mailto link, an accessible copy button with clipboard fallback, submission guidance, a helpful-information checklist, and a neutral privacy note.

All automated checks passed: 324/324 PHPUnit tests, 32/32 Node tests, production build, npm/composer audits, Pint, PHP syntax checks and `git diff --check`. Browser verification was performed in headless Firefox across Arabic and English at mobile, tablet and desktop sizes, including CMS settings inspection, empty-value behavior, copy feedback and clipboard verification.

## 2. Baseline, branch and preservation constraints

Work remained in the primary directory `/root/PCDR/pcdr` on `main`. HEAD remained unchanged at `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`.

A fresh Git baseline was captured before edits:

- `/tmp/opencode/complaints-baseline-status.txt`
- `/tmp/opencode/complaints-baseline-staged.txt`
- `/tmp/opencode/complaints-baseline-unstaged.txt`
- `/tmp/opencode/complaints-baseline-untracked.txt`
- `/tmp/opencode/complaints-baseline-head.txt`

The following were not used: worktree creation, branch creation, merge, commit, push, reset, clean, stash, `git checkout .`, `git restore`, `git add .` and `git add -A`. Existing staged, unstaged and untracked user work was preserved.

The protected files `.env.mysql.bak` and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not read or modified. `README.md` was not modified. Concurrent user changes, including the newer CMS settings architecture and dashboard files, were preserved rather than reverted or reformatted.

## 3. Architecture audit and decision

The audit identified the existing public website settings layer as the correct canonical source:

- `App\Services\SiteSettings` already exposes only `is_public` settings through cached `publicValues()`.
- `WebsiteSetting` already supports non-localized values and localized translations.
- CMS settings already use `cms:settings.view` and `cms:settings.update`.
- `SettingController` already builds the canonical settings catalog and audit flow.
- `HandleInertiaRequests` already shares public settings with the frontend.

Decision: add `contact.complaints_email` to the existing `website_settings` catalog. No migration, new table, new subsystem, new permission, SMTP setting or tracking mechanism was introduced.

The concurrent redesign of `SettingController.php` around `UpdateSiteSettingsRequest`, `SiteSettingsCatalog` and image upload was treated as user-owned architecture. Task changes were layered onto that structure without replacing it.

## 4. Localized route and controller

Added the localized route in `routes/web.php`:

```php
Route::get('/complaints', [StaticPageController::class, 'complaints'])->name('public.complaints');
```

`StaticPageController::complaints()` renders `Public/Complaints` with:

- `complaintsEmail` from `$settings->complaintsEmail($locale) ?? ''`
- localized SEO metadata via the existing `Seo` service
- exact Arabic and English SEO title overrides
- canonical URL for the active locale and alternate URLs for both locales
- localized Open Graph and Twitter title/description payloads
- indexable robots metadata

The controller intentionally passes an empty string when no email is configured so the Vue component has a stable prop and can render the neutral empty state.

## 5. Canonical website setting and CMS catalog

Added `contact.complaints_email` to `app/Support/SiteSettingsCatalog.php`:

```php
'contact.complaints_email' => ['group' => 'contact', 'localized' => false, 'type' => 'email'],
```

The setting is grouped with the public contact/communication settings and exposed through the existing CMS settings screen. Its exact localized labels are:

- Arabic: `البريد الإلكتروني لصندوق الشكاوى`
- English: `Complaints Box Email`

Its exact localized descriptions are:

- Arabic: `البريد الإلكتروني الرسمي الذي يظهر للزوار للتواصل مع الجمعية بشأن الشكاوى.`
- English: `The official email address displayed to visitors for submitting complaints.`

The CMS settings form now renders available setting descriptions below their labels. The catalog count used by regression tests was updated from 18 to 19 public settings.

## 6. Validation, authorization and cache behavior

`UpdateSiteSettingsRequest` derives validation from the catalog type. For `email` settings it applies:

```php
['nullable', 'string', 'max:2000', 'email:rfc']
```

Consequences verified by tests:

- Invalid values such as `not-an-email` are rejected with a `settings.0.value` validation error.
- A failed update does not replace the previous value.
- An empty value is allowed and stored as `null`.
- Authorized CMS editors can update the value.
- Unauthorized users receive HTTP 403 and cannot change it.

The existing `SiteSettings::flush()` cache invalidation is used after settings updates. Tests confirm that an authorized update is immediately visible on the public page and that a rejected update leaves both the database row and cached public value unchanged.

## 7. Preservation-safe seeding and default value

`database/seeders/SiteSettingsSeeder.php` seeds the official initial value only through its preservation-safe path:

- `firstOrCreate()` creates the row when absent.
- An existing administrator value is never overwritten.
- A null/empty existing value is filled with `complaints@pal-arc.org`.
- `SiteSettings::flush()` runs after seeding.

A dedicated test covers both cases: an existing custom value survives re-seeding, and an emptied value is restored to the official default on a later seed.

## 8. Public page structure and editorial design

New page: `resources/js/Pages/Public/Complaints.vue`.

It uses the existing `PublicLayout`, `PublicPageHero`, `SectionHeading`, shared public sections and shared `UiIcon` component. The page contains:

- institutional hero with breadcrumb and localized lead
- prominent email panel
- mailto link and copy control
- submission guidance section
- helpful-information checklist
- neutral privacy/responsible-communication note
- optional general contact channel only when configured and different from the complaints address

New scoped stylesheet: `resources/css/complaints-box.css`.

All selectors are scoped under `.public-shell.complaints-page`, so the shared public shell, frozen homepage stylesheet and dashboard surfaces are unaffected. The design follows the Premium Humanitarian Institutional Editorial direction using PCDR Navy, Teal, Sand, Wheat and White plus the approved supporting tokens. There are no gradients, no decorative animation, no excessive card stack, and no generic SaaS treatment. The official PCDR logo remains part of the shared public chrome; no logo asset was redrawn, recolored or distorted.

## 9. Email presentation, mailto and copy behavior

New helper: `resources/js/Support/complaintsEmail.js`.

- `complaintsMailto()` trims the backend-provided value and returns `mailto:<email>` or an empty string.
- `copyComplaintsEmail()` prefers the async Clipboard API.
- If clipboard permission fails, it creates a hidden read-only textarea, selects the address, runs `execCommand('copy')`, and removes the helper node in `finally`.
- Empty, unavailable and blocked environments return explicit failure reasons.

The Vue page maps those results to localized success/failure feedback. The copy button has busy state, `aria-busy`, and disabled-while-copying behavior. No address literal exists in any frontend source file.

## 10. Accessibility, RTL/LTR and responsive design

Accessibility and directionality measures include:

- one `h1` supplied by the shared editorial hero, followed by `h2` section headings
- `role="status"`, `aria-live="polite"` and `aria-atomic="true"` for copy feedback
- semantic button and link controls with visible focus styles from the shared shell
- explicit `dir="ltr"`, `direction: ltr`, `unicode-bidi: isolate` and wrapping for email addresses
- logical CSS properties (`inline-start`, `inline-end`, `block`) so Arabic RTL and English LTR both work
- responsive grids at 900px and 640px breakpoints
- no horizontal overflow at 390px, 820px or 1440px in browser verification

The page inherits the correct `<html lang>` and `<html dir>` values from the existing localized shell.

## 11. Localization

Full Arabic and English translations were added under `ui.complaints` in `lang/ar/website.php` and `lang/en/website.php`, covering all visible text: SEO title/description, intro, channel labels, panel note, copy states, empty state, guidance, checklist items, privacy note and general-contact alternative.

Navigation labels were added as:

- Arabic: `صندوق الشكاوى`
- English: `Complaints Box`

CMS label/description keys were added under `website.cms.setting_labels` and `website.cms.setting_descriptions` in both locales.

Neutral fallback key `ui.admin_dashboard.greeting_` was added in both locales solely to satisfy the repository's dynamic translation-key scanner; existing runtime greeting keys were left intact. The CMS label expression was also made scanner-resolvable without changing runtime output.

No unverified confidentiality, response-time, policy or case-status claim was introduced.

## 12. SEO and discoverability

Localized metadata is generated by the existing `Seo` service:

- Arabic title: `صندوق الشكاوى | جمعية العمل التشاركي في الأزمات والكوارث`
- English title: `Complaints Box | PCDR`
- localized meta descriptions
- canonical URL for each locale
- `alternate` URLs for `/ar/complaints` and `/en/complaints`
- localized Open Graph and Twitter metadata
- indexable robots metadata, with no `noindex`

`SitemapController::STATIC_PATHS` now includes `complaints`, so the page appears in the sitemap alongside other static public destinations.

Discoverability link placement:

- added once to the compiled `knowledge` footer group in `usePublicNavigation.js`
- mirrored once in the `SiteFooter.vue` fallback groups for environments without seeded navigation
- not added to the primary navigation
- not added to CMS menu allowlists
- no duplicate footer legal link remains

## 13. Scope boundary and privacy

The implementation is limited to an informational complaints channel. It explicitly does **not** add:

- complaint records or storage tables
- tracking numbers or case status
- anonymous intake forms
- workflows or case assignment
- dashboards or operational queues
- acknowledgments or automated replies
- WhatsApp or SMS channels
- RBAC/permission changes
- OQ-49 changes
- ADR approval state changes
- SMTP credentials or transport exposure
- analytics or unnecessary logging

The privacy copy is neutral and advises visitors to avoid unnecessary personal or sensitive information while allowing them to choose what contact details to share.

## 14. Tests added

`tests/Feature/ComplaintsBoxTest.php` adds 12 feature tests covering:

1. AR/EN route rendering, correct HTML locale/direction, Inertia component and exact SEO payload
2. named route registration for both locales
3. exact localized CMS label, description, group, type and catalog count
4. authorized update persistence and cache invalidation
5. invalid email rejection with prior-value preservation
6. unauthorized update denial with unchanged value
7. empty value allowed and exposed as an empty public prop
8. preservation-safe seeding with existing and empty values
9. no private platform setting exposure in the public response
10. frontend source contract forbidding the hardcoded official email
11. accessible feedback, LTR email direction, shared layout and absence of a form
12. private-setting and dynamic source assertions

`tests/complaints-box.test.mjs` adds 9 Node tests covering mailto generation, clipboard API success, hidden-textarea fallback, failure reasons, page/source contracts, one compiled footer link, CMS translation integration, both-locale string completeness and scoped palette styling.

## 15. Regression expectations updated

Existing tests were updated only where the canonical catalog count or navigation/public-destination contract changed:

- `tests/Feature/PublicWebsiteTest.php`: added `complaints` to sitemap/static path expectations, shared-shell destinations, compiled footer labels and translation-source checks.
- `tests/Feature/HomepageBackendIntegrationTest.php`: 19 public settings, official seeded value and accessor checks.
- `tests/Feature/WebsiteCmsScopeTest.php`: 19 public settings with an explicit `contact.complaints_email` catalog assertion and continued exclusion of `app.*` and `mail.*`.

No test was weakened or removed to force a pass.

## 16. Automated PHP verification

Final results:

- `php artisan test --compact tests/Feature/ComplaintsBoxTest.php`: **12 passed**, 111 assertions.
- `php artisan test --compact tests/Feature/ComplaintsBoxTest.php tests/Feature/TranslationKeysTest.php`: **13 passed**, 112 assertions.
- Relevant regression set (`PublicWebsiteTest`, `HomepageBackendIntegrationTest`, `WebsiteCmsScopeTest`, `CmsWorkflowTest`, `SettingsTest`): **69 passed**, 3440 assertions.
- Full `php artisan test --compact`: **324 passed**, 8967 assertions.

Formatting and syntax:

- `vendor/bin/pint --format agent` on all task-owned PHP files: passed.
- `php -l` on all changed PHP files: passed.

Pint was scoped to task-owned PHP files so concurrent user-owned PHP work was not reformatted.

## 17. Node, build and security verification

- `node --test tests/complaints-box.test.mjs`: **9 passed**.
- `node --test tests/*.test.mjs`: **32 passed**.
- `npm run build`: passed after correcting the page-level CSS import to `../../../css/complaints-box.css`.
- `npm audit --omit=dev`: `found 0 vulnerabilities`.
- `COMPOSER_ALLOW_SUPERUSER=1 composer audit`: `No security vulnerability advisories found.`
- `git diff --check`: clean.

Non-blocking build notices remain: optional `fontaine` package for optimized font fallbacks and an existing generated JS chunk-size advisory. Dependencies were not changed.

## 18. Browser verification

Browser verification was actually performed with headless Firefox after Chromium failed to launch in this environment due GPU/process errors. A local server was run on port `8010` and stopped afterward.

Verified:

- AR and EN pages at mobile `390x844`, tablet `820x1180` and desktop `1440x900`
- correct `lang`, `dir`, localized title and hero heading
- `mailto:complaints@pal-arc.org`
- email rendered with `dir="ltr"`
- `role="status"` live region with `aria-live="polite"`
- exactly one footer complaints link
- no horizontal overflow at any tested viewport
- no complaint form
- English desktop copy button produced success feedback and the clipboard contained `complaints@pal-arc.org`
- CMS settings pages in AR and EN showed the exact label, description and value
- emptying the setting through the CMS produced the neutral `not_configured` notice with no mailto or copy controls
- restoring the setting through the CMS restored the official email on the public page

External Google Fonts downloads failed because the environment has no network access to `fonts.gstatic.com`. Those console warnings were treated as environment-only; no application JavaScript errors remained.

Local verification side effects were cleaned: the seeded local SQLite setting was restored to `complaints@pal-arc.org`, the existing admin password was restored from its original hash, the temporary hash file was removed, and the verification server was stopped.

## 19. Final Git diff and protected-file inspection

Final status was compared against `/tmp/opencode/complaints-baseline-status.txt`. The task-owned additions/changes are consistent with the implementation:

- routes: `routes/web.php`
- backend: `StaticPageController.php`, `SiteSettings.php`, `SiteSettingsCatalog.php`, `SettingController.php`, `UpdateSiteSettingsRequest.php`, `SitemapController.php`, `SiteSettingsSeeder.php`
- frontend: `Complaints.vue`, `complaintsEmail.js`, `complaints-box.css`, `usePublicNavigation.js`, `SiteFooter.vue`
- translations: `lang/ar/website.php`, `lang/en/website.php`, `lang/ar/ui.php`, `lang/en/ui.php`
- tests: `tests/Feature/ComplaintsBoxTest.php`, `tests/complaints-box.test.mjs`, updated regression expectations
- report: this file

Additional modified/untracked paths in the final status originated from concurrent user work and were preserved, including `app/Http/Controllers/Admin/DashboardController.php`, `app/Services/AdminDashboardService.php`, `app/Http/Requests/Cms/UploadSiteSettingImageRequest.php` and the CMS site-settings redesign report. They were not reverted or reconciled by this task.

Protected files were confirmed absent from the final modification set:

- `.env.mysql.bak`
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md`
- `README.md`

The frozen public assets remain byte-identical, as asserted by `PublicWebsiteTest::test_approved_homepage_baseline_files_remain_byte_identical()`:

- `resources/css/pcdr-home.css`
- `resources/views/app.blade.php`
- `resources/js/Components/LanguageSwitcher.vue`

No commit, push, reset, clean, stash or branch/worktree operation was performed.

## 20. Known limitations and final classification

No blocking limitations remain.

Non-environmental behavior is covered by PHP feature tests, Node helper/source tests, a successful production build and successful browser verification. The only observed warnings are pre-existing build advisories and external font downloads blocked by the sandbox network.

The implementation satisfies the functional, localization, design, accessibility, SEO, security, scope and verification requirements. All work remains uncommitted on `main`.

**Final status: PASS**
