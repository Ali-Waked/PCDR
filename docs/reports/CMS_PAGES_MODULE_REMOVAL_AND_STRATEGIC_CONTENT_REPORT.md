# CMS Pages Module Removal and Strategic Content Replacement Report

**Date:** 2026-11-24
**Branch:** `main`
**HEAD before and after:** `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`
**Working directory:** `/root/PCDR/pcdr`

This report documents the removal of the administrative CMS Pages management module while preserving public website page rendering, and the replacement of the obsolete strategic-planning disclaimer with content grounded in the authoritative PCDR strategic plan.

---

## 1. CMS Pages module architecture audit

The audit separated administrative management from public content rendering.

### Removed administrative surface

- Admin-only Laravel routes under `{locale}/cms/pages`.
- Admin controller and form request:
  - `app/Http/Controllers/Cms/PageController.php`
  - `app/Http/Requests/Cms/PageRequest.php`
- Pages preview resource in `app/Http/Controllers/Cms/PreviewController.php`.
- Pages navigation item and its UI translation label.
- Pages dashboard metrics, cards and quick action.
- Pages-specific segments, extras and relation fields in shared CMS content screens.
- `pages.*` permission identifiers in code configuration and seeded CMS roles.
- Page-only translation keys no longer used by the application.

### Shared/public infrastructure intentionally preserved

- `pages`, `page_translations` and `page_media` database tables.
- `App\Models\Page` and `App\Models\PageTranslation`.
- `Route::get('/pages/{page}', Public\PageController@show)->name('public.page')`.
- `App\Services\ContentQuery::findPublishedPage()` and public page rendering.
- Homepage section resolution for `item_type = 'page'`.
- Sitemap entries for published pages.
- Shared `Cms\Content\Index.vue`, `Cms\Content\Edit.vue`, `PreviewController`, media, SEO, translation and revision infrastructure.

The audit established that public pages consume the same `pages` records as the removed admin module. Deleting the tables or models would destroy institutional content and break public routes, so the removal was deliberately limited to administrative management.

---

## 2. Removed frontend functionality

- Pages listing screen route/segment in `resources/js/Pages/Cms/Content/Index.vue`.
- Pages create/edit route segment in `resources/js/Pages/Cms/Content/Edit.vue`.
- Pages-specific `parent_id` and `is_system` form extras.
- Pages-specific relation template in the shared content editor.
- Pages metric cards and “New page” quick action in `resources/js/Pages/Cms/Dashboard.vue`.
- Pages dashboard module link and icon in `resources/js/Pages/Dashboard.vue`.
- `pages` icon mapping in `resources/js/Components/Dashboard/DashboardChrome.vue`.
- `ui.admin_navigation.pages` in `lang/ar/ui.php` and `lang/en/ui.php`.
- Unused website UI keys: `cms_pages`, `cms_new_page`, `parent_page`.
- E2E route/content mappings for `cms.pages`, the `pages` preview resource, and the `pages` fixture table.

No Pages-only Vue component file existed; the admin experience was a resource variant of shared CMS components, so those shared components were retained.

---

## 3. Removed backend functionality

- `app/Http/Controllers/Cms/PageController.php` deleted.
- `app/Http/Requests/Cms/PageRequest.php` deleted.
- Admin route import and all seven admin `cms.pages.*` routes removed from `routes/web.php`.
- Pages entry removed from `PreviewController::RESOURCES`.
- Default fallback permission in `ContentRequest` changed from `pages.view` to `pillars.view`.
- CMS dashboard gate changed from `pages.view` to `pillars.view`.
- Page metrics removed from `app/Http/Controllers/Cms/DashboardController.php`.
- `pages.*` permissions removed from `config/cms.php`, `config/permissions.php` and `database/seeders/CmsRolesSeeder.php`.
- CMS dashboard navigation permission changed to `pillars.view`; the Pages navigation item was removed.
- Page-management tests were removed or retargeted to remaining CMS modules without weakening unrelated coverage.

All seeded CMS roles that previously carried `pages.view` also carried `pillars.view`, so repointing the shared dashboard gate did not introduce a new permission or broaden unrelated authority.

---

## 4. Routes removed

| Route name | URI |
|---|---|
| `cms.pages.index` | `{locale}/cms/pages` |
| `cms.pages.create` | `{locale}/cms/pages/create` |
| `cms.pages.store` | `POST {locale}/cms/pages` |
| `cms.pages.edit` | `{locale}/cms/pages/{content}/edit` |
| `cms.pages.update` | `PUT {locale}/cms/pages/{content}` |
| `cms.pages.destroy` | `DELETE {locale}/cms/pages/{content}` |
| `cms.pages.status` | `POST {locale}/cms/pages/{content}/status` |

`php artisan route:list --except-vendor --json` reported **446 routes** and **0** `cms.pages` / `cms/pages` hits after the change.

Public route `public.page` (`{locale}/pages/{page}`) was retained.

---

## 5. Permissions removed or retained

### Removed from code configuration and seed definitions

- `pages.view`
- `pages.create`
- `pages.update`
- `pages.delete`
- `pages.publish`
- `pages.unpublish`

### Repointed

- `config/cms.php`: `permissions.dashboard` now uses `pillars.view`.
- `config/admin_navigation.php`: CMS dashboard item now requires `pillars.view`.
- `app/Http/Controllers/Cms/DashboardController.php`: dashboard gate now uses `pillars.view`.
- `app/Http/Requests/Cms/ContentRequest.php`: fallback permission now uses `pillars.view`.

### Retained

- All non-page CMS permissions and role assignments.
- Institutional approval authority model (unchanged).
- Existing database permission rows were not deleted.

---

## 6. Database decisions and rationale

No migration was created or run against development/production data. No tables were dropped, altered or truncated.

Decision: **preserve `pages`, `page_translations` and `page_media`.**

Rationale:

- Public page rendering depends on published `Page` records via `ContentQuery::findPublishedPage()`.
- The homepage can resolve `item_type = 'page'` sections.
- The sitemap includes published page slugs.
- Existing records contain institutional content and must not be destroyed.

Existing database rows for the now-unknown `pages.*` permissions may remain until the normal, reviewable `pcdr:permissions:sync` command is run by an operator. That command was intentionally not run here because it mutates permission state. The seeder no longer assigns those permissions, and all admin endpoints that consumed them are gone.

---

## 7. Public website dependencies preserved

Verified by tests and route inspection:

- `/{locale}` homepage.
- `/{locale}/about`.
- `/{locale}/strategic-areas`.
- `/{locale}/programs`.
- `/{locale}/news`.
- `/{locale}/publications`.
- `/{locale}/transparency`, `/{locale}/partnerships`, `/{locale}/get-involved`, `/{locale}/contact`.
- `/{locale}/pages/{page}` for published page records.
- Shared site footer, navigation, SEO metadata and sitemap support.

The new feature test creates a published `Page` with Arabic and English translations and confirms the public page route still renders it after admin removal.

---

## 8. Strategic plan source document inspected

**Authoritative source:** `docs/strategy/STRATEGIC_OPERATIONAL_PLAN_2027_2029.md`

No authoritative English strategic-plan document was found in the repository. The English replacement is therefore a faithful translation of the approved Arabic concepts and wording.

The protected file `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` was not opened or modified.

---

## 9. Exact source sections used for replacement content

| Plan section | Lines | Use in replacement |
|---|---:|---|
| Title/timeframe/strategic slogan | 1–6 | Slogan and 2027–2029 framing |
| Introduction / participatory philosophy | 8–15 | Partnership, participation, dignity, resilience, transparency |
| Vision | 19–20 | Resilient Palestinian society, dignity and justice |
| Mission | 22–23 | Coordination, local/international partnerships, vulnerable groups, community participation |
| Core values | 25–54 | Human dignity, justice, transparency, participation, partnership, protection of vulnerable groups |
| Strategic purpose | 58–59 | Early recovery and sustainable development |
| General strategic objectives | 61–93 | Program areas, recovery, participation, partnerships |
| Planning indicators | 1006–1008 | Positive framing of planning targets without presenting them as achieved results |
| Final recommendation / priority values | 1265–1275 | Partnership, dignity, justice and sustainability |

Key verbatim excerpts:

- Line 6: `الشعار الاستراتيجي: «من الاستجابة الإنسانية إلى التعافي والتنمية المستدامة»`
- Line 20: vision ending `...مجتمع فلسطيني أكثر قدرة على الصمود والتعافي...`
- Line 23: mission includes `تنسيق الجهود الإنسانية وتعزيز الشراكات المحلية والدولية... وتمكين الفئات أكثر هشاشة...`
- Line 1008: `تمثل الأرقام التالية مستهدفات تخطيطية أولية لعام 2027، وتخضع للمراجعة بعد تحديد الموارد البشرية والمالية والقدرات التنفيذية والتمويل المتاح.`
- Line 1275: `الشراكة – الكرامة الإنسانية – العدالة – الاستدامة`

---

## 10. Final Arabic replacement text

```text
نعمل بروح الشراكة والمشاركة المجتمعية لتنسيق الاستجابة الإنسانية، وتمكين الأسر والفئات الأكثر هشاشة، والمساهمة في بناء مجتمع فلسطيني أكثر قدرة على الصمود والتعافي، انطلاقاً من شعارنا: من الاستجابة الإنسانية إلى التعافي والتنمية المستدامة.
```

Supporting keys:

- `strategic_programs_intro`: `مجالات عملنا مستمدة من المحاور الاستراتيجية للخطة 2027–2029، وتوجّه استجابتنا الإنسانية وجهود التعافي والتنمية.`
- `strategic_targets_intro`: `نستهدف هذه المستويات ضمن مسار الخطة الاستراتيجية، ونعمل على تحقيقها وفق الموارد والقدرات المتاحة.`
- `strategic_partnerships_intro`: `نعزز الشراكات المحلية والدولية على أساس الشفافية والمساءلة، تماشياً مع رسالتنا ورؤيتنا الاستراتيجية.`

---

## 11. Final English replacement text

```text
Through partnership and community participation, we coordinate humanitarian response, empower the most vulnerable families and groups, and contribute to a Palestinian society better able to withstand crises and recover, guided by our strategic slogan: from humanitarian response to recovery and sustainable development.
```

Supporting keys:

- `strategic_programs_intro`: `Our areas of work draw on the strategic pillars of the 2027–2029 plan, guiding humanitarian response, recovery and development.`
- `strategic_targets_intro`: `These targets form part of the strategic plan’s path forward, and we work toward them within available resources and capacities.`
- `strategic_partnerships_intro`: `We strengthen local and international partnerships on the basis of transparency and accountability, in line with our mission and strategic vision.`

These statements describe strategic direction and planned objectives. They do not claim completed achievements, beneficiary results, funding, partnerships or registration.

---

## 12. Locations where content was replaced

Canonical translation keys were added to `lang/ar/website.php` and `lang/en/website.php` under `ui`:

- `strategic_statement`
- `strategic_programs_intro`
- `strategic_targets_intro`
- `strategic_partnerships_intro`
- `content_items` (admin dashboard metric label)

The old `planning_targets_notice` key was removed.

All 14 former `t('planning_targets_notice')` usages were replaced:

- `resources/js/Components/Public/SiteFooter.vue` — 1
- `resources/js/Pages/Public/About.vue` — 2
- `resources/js/Pages/Public/Involve.vue` — 1
- `resources/js/Pages/Public/Partnerships.vue` — 2
- `resources/js/Pages/Public/Pillars/Index.vue` — 1
- `resources/js/Pages/Public/Pillars/Show.vue` — 1
- `resources/js/Pages/Public/Programs/Index.vue` — 2
- `resources/js/Pages/Public/Programs/Project.vue` — 1
- `resources/js/Pages/Public/Publications/Index.vue` — 1
- `resources/js/Pages/Public/Transparency.vue` — 2

Three hardcoded partial disclaimers in `resources/js/Pages/Public/Home.vue` were also removed and replaced with the canonical keys above:

- Programs intro → `strategic_programs_intro`
- Targets intro → `strategic_targets_intro`
- Partnerships intro → `strategic_partnerships_intro`

A source grep confirmed the exact Arabic sentence, its English equivalent and related partial disclaimers are absent from `resources/js`.

---

## 13. Tests and results

### Added

`tests/Feature/CmsPagesModuleRemovalAndStrategicContentTest.php` covers:

- All seven admin Pages route names absent.
- `pages.*` keys absent from permission and CMS configuration.
- Pages navigation item absent.
- Direct `/ar/cms/pages`, `/en/cms/pages` and create URLs return 404.
- Other CMS modules remain accessible and Pages navigation remains absent.
- Existing published `Page` records still render on the public page route in AR and EN.
- Public homepage/about/strategic-areas/programs/news/publications/transparency/partnerships/involve/contact remain accessible in both locales.
- Old disclaimer text and old key absent from public responses.
- New AR/EN strategic content present in Inertia `ui` props.

### Updated

- `tests/Feature/CmsWorkflowTest.php`
- `tests/Feature/CmsEditorialToolsTest.php`
- `tests/Feature/WebsiteCmsScopeTest.php`
- `tests/Feature/WebsiteCmsEmailTemplateTest.php`
- `tests/Feature/WebsiteCmsInquiryTest.php`
- `tests/e2e/admin-redesign.spec.mjs`
- `tests/e2e/helpers/routes.mjs`

Obsolete page-route test coverage was retargeted to `cms.news.*` / `cms.pillars.*`; workflow, revision, sanitizer and access-control coverage was preserved.

### Commands and results

| Command | Result |
|---|---|
| `php artisan test --compact <six targeted test files>` | **45 passed**, 668 assertions |
| `php artisan test --compact` | 233 tests, **232 passed**, 5802 assertions, **1 unrelated failure** |
| `vendor/bin/pint --dirty --format agent` | Passed |
| `git diff --check` | Passed (`DIFF_CHECK_OK`) |
| `php artisan route:list --except-vendor --json` | 446 routes, 0 Pages admin hits |

The single full-suite failure is unrelated to this task:

```text
TranslationKeysTest::vue_and_blade_translation_keys_exist_in_lang_files
Missing translation keys:
[ar] app/Services/EmailTemplatePreviewService.php:33 php(email_templates.variables.) -> email_templates.variables.
[en] app/Services/EmailTemplatePreviewService.php:33 php(email_templates.variables.) -> email_templates.variables.
```

`app/Services/EmailTemplatePreviewService.php` and the `email_templates.php` language files were pre-existing untracked user work. They were not modified by this task.

---

## 14. Build results

`npm run build` passed:

- Vite `8.3.8`
- 771 modules transformed
- `public/build/manifest.json` and `public/build/fonts-manifest.json` generated
- Completed in 10.26s

Warnings only:

- Optional `fontaine` package not installed (optimized font fallbacks skipped).
- Main JS chunk exceeds 500 kB (existing chunk-size warning).

No frontend test failure was introduced. Playwright E2E tests were not executed because they require a running application, browser runtime and configured credentials.

---

## 15. Browser verification

**Not performed.** No browser was opened and no page was visually inspected for this task. Visual confirmation of the footer/homepage replacement and responsive layout remains outstanding. The build and automated response-level tests passed, but they are not a substitute for browser inspection.

---

## 16. Remaining limitations

1. Existing database rows for retired `pages.*` permissions may remain until an operator runs the normal permission sync command; no DB mutation was performed here.
2. English strategic content is a translation of the Arabic authoritative plan because no authoritative English plan document was found.
3. No browser/visual verification was performed.
4. Playwright E2E tests were not run in this environment.
5. `docs/reports/ADMIN_NOTIFICATIONS_AND_RICH_TEXT_EDITOR_REPORT.md` is a pre-existing historical report that still mentions the removed routes/permissions as past concurrent changes. It was intentionally left unchanged.
6. Several unrelated email-template, dashboard redesign, Phase 5, localization and rich-text changes were already present and were preserved rather than reverted.

---

## Final safety check

### Pre-existing user modifications preserved

- Email-template controllers, services, requests, language files and frontend components.
- Dashboard redesign and admin notification components.
- Footer/navigation improvements.
- Error-page and auth-layout work.
- Phase 5 operating-layer components.
- Rich-text editor and sanitizer work.
- Strategic Pillars page redesign and its report.
- All initially modified/untracked files listed in `/tmp/opencode/cms_pages_initial_status.txt`.
- Additional concurrent untracked files appearing after the initial snapshot (`UiIcon.vue`, `VerifyEmail.vue`, `resources/js/Components/EmailTemplates/`, `tests/Feature/AdminNotificationsAndRichTextTest.php`, `ADMIN_NOTIFICATIONS_AND_RICH_TEXT_EDITOR_REPORT.md`) were also left intact.

### Files changed by this task

- `routes/web.php`
- `config/permissions.php`
- `config/cms.php`
- `config/admin_navigation.php`
- `database/seeders/CmsRolesSeeder.php`
- `app/Http/Controllers/Cms/DashboardController.php`
- `app/Http/Controllers/Cms/PreviewController.php`
- `app/Http/Requests/Cms/ContentRequest.php`
- `resources/js/Pages/Cms/Content/Index.vue`
- `resources/js/Pages/Cms/Content/Edit.vue`
- `resources/js/Pages/Cms/Dashboard.vue`
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Components/Dashboard/DashboardChrome.vue`
- `resources/js/Components/Public/SiteFooter.vue`
- `resources/js/Pages/Public/Home.vue`
- `resources/js/Pages/Public/About.vue`
- `resources/js/Pages/Public/Involve.vue`
- `resources/js/Pages/Public/Partnerships.vue`
- `resources/js/Pages/Public/Pillars/Index.vue`
- `resources/js/Pages/Public/Pillars/Show.vue`
- `resources/js/Pages/Public/Programs/Index.vue`
- `resources/js/Pages/Public/Programs/Project.vue`
- `resources/js/Pages/Public/Publications/Index.vue`
- `resources/js/Pages/Public/Transparency.vue`
- `lang/ar/ui.php`, `lang/en/ui.php`
- `lang/ar/website.php`, `lang/en/website.php`
- `tests/Feature/CmsWorkflowTest.php`
- `tests/Feature/CmsEditorialToolsTest.php`
- `tests/Feature/WebsiteCmsScopeTest.php`
- `tests/Feature/WebsiteCmsEmailTemplateTest.php`
- `tests/Feature/WebsiteCmsInquiryTest.php`
- `tests/e2e/admin-redesign.spec.mjs`
- `tests/e2e/helpers/routes.mjs`
- Architecture docs listed in section 16 and the current CMS guide/permission docs.
- `app/Services/EmailMessagePresenter.php` — formatting only, applied by the mandated `vendor/bin/pint --dirty --format agent` run; this was pre-existing untracked user work and no behavioral change was made.

### Files deleted by this task

- `app/Http/Controllers/Cms/PageController.php`
- `app/Http/Requests/Cms/PageRequest.php`

### New files created

- `tests/Feature/CmsPagesModuleRemovalAndStrategicContentTest.php`
- `docs/reports/CMS_PAGES_MODULE_REMOVAL_AND_STRATEGIC_CONTENT_REPORT.md`

### Database changes

None. No migrations, table drops, truncations, deletes or permission sync commands were executed against project data.

### Git state

- Branch remained `main`.
- HEAD remained `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`.
- No worktree was created or used.
- No branch switch, merge, cherry-pick, commit or push occurred.
- `.env.mysql.bak` and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not opened or modified.
- `README.md` remained unchanged.
- `app/Http/Controllers/Admin/DashboardController.php` remained unchanged.
