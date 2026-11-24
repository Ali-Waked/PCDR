# CMS Programs, Projects, and News Redesign Report

Date: 2026-11-24  
Branch: `main`  
Baseline HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
Status: implemented; focused backend, static, build, security, and direct browser checks passed. Final classification: **PASS WITH LIMITATIONS**.

---

## 1. Executive summary and classification

The CMS Programs, Projects, and News management experiences were redesigned across index, create, edit, and status-transition workflows. The three modules now share one coherent, responsive, bilingual editorial workspace while their existing routes, permissions, validation, status graph, records, relationships, revisions, previews, and public output remain intact.

The design follows the existing Pillar filtered-index and Inquiries filter/list patterns. It reuses the established dashboard chrome, cards, badges, pagination, selection controls, notifications, confirmation dialogs, `RichTextEditor`, and media library rather than introducing a new design system or dependency.

The work is complete for the requested scope. It is classified **PASS WITH LIMITATIONS** because:

- The task-owned feature test passes: 6 tests, 216 assertions.
- Production build, frontend static tests, PHP lint, scoped Pint, `composer audit`, `npm audit`, and `git diff --check` pass.
- Direct authenticated browser checks passed for Arabic and English, desktop and mobile, including create → edit → update for one temporary record in each module.
- The global browser suite was not fully green: 3 passed, 5 failed, 1 skipped. The failures are global/legacy checks outside the three redesigned modules, including a download-triggered navigation abort, global small-font findings, global color-contrast findings, a DataTable overflow check, and a reduced-motion check.
- The final full PHPUnit suite was red because of unrelated/concurrent work: 323 tests, 316 passed, 7 failed, 9,263 assertions. An earlier clean snapshot passed 324 tests / 8,971 assertions before later unrelated changes landed.

---

## 2. Objectives and acceptance criteria

The accepted objective was a comprehensive UI/UX redesign of:

- `/ar/cms/programs`
- `/ar/cms/projects`
- `/ar/cms/news`

with unchanged English-locale behavior at `/en/...`.

Acceptance criteria addressed:

- Preserve backend contracts, routes, permissions, validation, business rules, records, revisions, and public behavior.
- Audit first; do not invent fields, statuses, workflows, relationships, metrics, or institutional content.
- Use PCDR design tokens and existing shared components.
- Reuse `RichTextEditor`, `SafeRichText`/existing sanitization behavior, media library, notifications, confirmation dialogs, status components, selects, and server-side pagination.
- Support Arabic RTL and English LTR through existing localization.
- Avoid hardcoded translated strings in Vue.
- Add or update tests and run focused/full PHPUnit, Pint, build, audits, and `git diff --check`.
- Claim browser verification only when actually performed.
- Preserve all staged, unstaged, and untracked user-owned work.

---

## 3. Baseline, Git safety, and protected files

All work was performed directly in the existing `main` worktree at `/root/PCDR/pcdr`.

- Initial Git state was captured in `/tmp/opencode/cms_redesign_initial_status.txt`.
- Branch remained `main`.
- HEAD remained `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`.
- No branch, worktree, commit, push, merge, cherry-pick, stash, clean, reset, checkout, restore, or broad `git add` was used.
- Pre-existing staged changes were preserved:
  - `D docs/reports/DASHBOARD_UI_UX_REDESIGN_INTEGRATION_REPORT.md`
  - `M resources/js/Layouts/AppLayout.vue`
  - `MM resources/js/Layouts/CmsDashboardLayout.vue`
- Pre-existing unstaged and untracked work elsewhere in the repository was left intact.
- `README.md` and `app/Http/Controllers/Admin/DashboardController.php` were not modified by this task. Their pre-existing modified state was retained.
- Protected files were not read, modified, staged, or committed:
  - `.env.mysql.bak`
  - `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md`
- Both protected files were already untracked in the baseline snapshot and remain untracked with unchanged status.

Final whitespace integrity check: `git diff --check` passed.

---

## 4. Pre-change audit findings

The audit covered routes, controllers, request validation, models, configuration, shared frontend components, localization, existing tests, and prior redesign reports.

Key findings:

- Programs, Projects, and News already shared `ContentController`-driven CRUD and status behavior.
- Projects intentionally authorize with `programs.*` permissions while keeping their own labels, routes, lifecycle stage, relations, and page copy.
- Project lifecycle stage is stored via `project_status` → `status`, while publication workflow uses `workflow_status`.
- Executable transitions come from `config('cms.transitions')`; the UI must not invent its own graph.
- Public pages consume only published records through existing controllers and must remain unchanged.
- The existing `useI18n()` helper reads `page.props.ui`, which is supplied from `lang/{locale}/website.php['ui']`; `lang/{locale}/ui.php` was a false lead.
- The previous shared index/edit pages had inconsistent filter handling, weak mobile layouts, and a form resource key that could not distinguish a shared permission namespace from the actual UI module.
- The repository already contained substantial concurrent user work; all such changes were treated as owned and preserved.

---

## 5. Scope boundaries and preservation

### Changed for this task

- Shared CMS index and edit data contracts.
- Shared CMS index and edit Vue pages.
- New CMS-specific filter, form, media, and card components.
- Responsive RTL/LTR styles under the existing `.pc-*` dashboard token system.
- Localization keys under `ui` in `lang/en/website.php` and `lang/ar/website.php`.
- Focused feature coverage in `tests/Feature/CmsContentRedesignTest.php`.
- Two backward-compatible controller fixes discovered during verification:
  - Projects expose a frontend resource key without changing authorization.
  - Publication validation rules were moved out of a PHP property initializer.

### Explicitly preserved

- All three route families and route names.
- `programs.*` permission usage for Projects.
- `ContentStatus`, `ProjectStatus`, category/tag/pillar/program/media relationships, featured flags, publication dates, and display order semantics.
- Revision creation and actor attribution.
- Editor preview flow and public SEO/content consumption.
- Existing confirmation and notification components.
- Existing server-side pagination behavior.
- All user-owned staged, unstaged, and untracked work.

### Explicitly not done

- No new database migration, dependency, field, status, workflow graph, or institutional content.
- No public-site redesign.
- No creation of a new design system.
- No destructive Git operation.

---

## 6. Shared backend data contract

`app/Http/Controllers/Cms/ContentController.php` now builds a richer, consistent Inertia contract for `Cms/Content/Index` and `Cms/Content/Edit`.

Index props include:

- Localized `pageTitle`, `pageDescription`, and `label`.
- Paginated `items` with localized titles, summaries, status, locales, dates, module-specific metadata, and preview identity.
- `pagination` with current page, last page, per-page value, total, and range.
- `counts` for all lifecycle states.
- `statuses` with localized labels and descriptions.
- `sortOptions` and module-specific `filterOptions`.
- Permission-aware `transitions` derived from `config('cms.transitions')`.
- `can` flags for create, update, delete, publish, and unpublish.
- Active `filters` retained from the request.

Edit props include:

- Record identity, localized title, lifecycle status, published date, display order, and module attributes.
- Localized translations and active locale.
- Revision history with actor, status, note, and timestamp.
- Relation and media `options`.
- Permission-aware transitions and `can` flags.
- Localized page copy and breadcrumb data.

A new `frontendResource()` / `$uiResource` pair separates the frontend template key from the authorization key. This keeps Projects on `programs.*` permissions while allowing Vue to render `projects` fields such as `program_id` and `project_status`.

---

## 7. Module controllers and business-rule preservation

- `ProgramController.php` exposes pillar, date-range, and featured-image metadata and filters while preserving existing behavior.
- `ProjectController.php` keeps `$resource = 'programs'` for authorization, adds `$uiResource = 'projects'`, preserves `workflow_status` for publication workflow, and maps `project_status` to the project lifecycle column.
- `ArticleController.php` exposes news category, tag, featured, cover media, and event metadata without changing stored values.
- `PublicationController.php` was repaired so dynamic `Rule::exists(...)->where(...)` objects are attached in `validateContent()` rather than in a property initializer, restoring PHP 8.5 compatibility. No validation semantics were changed.
- `PreviewController.php` and `PillarController.php` were aligned with the shared contract.
- No transition was added, removed, or reordered; all transition labels, permissions, and confirmation classes come from existing configuration and localization.

---

## 8. Index redesign

`resources/js/Pages/Cms/Content/Index.vue` now provides:

- A localized page header with breadcrumb and permission-gated create action.
- Metric cards driven by real server-side status counts.
- A `ContentFilterBar` with debounced search, status, sort, clear-filters, result count, and module-specific filters.
- Desktop `DataTable` rendering with title, summary, module metadata, status badge, locales, updated date, status select, preview, edit, and delete.
- Mobile `ContentCollectionCard` rendering with the same information and actions, avoiding horizontal table scrolling for these modules.
- `CmsPagination` with real range metadata.
- Permission-aware status transitions with existing archive/publish/reverse confirmation dialogs.
- Busy states for transition and delete actions.
- Distinct empty states for no records versus no filtered results.
- Server-side query updates via Inertia partial visits with `preserveState`, `preserveScroll`, and replace semantics.

---

## 9. Create/edit redesign and workflows

`resources/js/Pages/Cms/Content/Edit.vue` now provides:

- A two-column responsive editorial layout with clear section grouping via `ContentFormSection`.
- Locale tabs for Arabic and English with required/optional labeling from localization.
- `RichTextEditor` for body, description, and objectives fields.
- Existing sanitization/rendering behavior retained; no parallel rich-text implementation was introduced.
- `SelectionControl` for all status, relation, category, tag, and workflow selects.
- `ContentMediaField` backed by the existing media library and media options.
- Module-specific relation blocks for pillars, programs, projects, news categories/tags, and publications.
- Record details, workflow state, published date, display order, and revision history.
- Permission-aware transitions on existing records, with dirty-form confirmation and the same confirmation dialog family used elsewhere.
- Server validation errors surfaced inline and focused through `aria-invalid`.
- Preview links that continue to use the existing editor-only preview route.

Browser verification exposed and fixed a real Projects defect: because `$resource` was `programs`, the form previously chose Programs fields and omitted `program_id` / `project_status`. The UI-resource separation corrected this without changing authorization.

---

## 10. Reuse of existing components and design system

The redesign composes existing PCDR primitives instead of adding a new system:

- `CmsDashboardLayout`, `DataTable`, `MetricCard`, `EmptyState`, `CmsPagination`, `CmsStatusBadge`, and `UiIcon`.
- `SelectionControl`, `StatusSelect`, and the shared selection status vocabulary.
- `useAdminNotifications()` and existing confirmation dialogs.
- `RichTextEditor` and existing safe rich-text rendering/sanitization behavior.
- Existing media library options and `ContentMediaField`.
- Existing `.pc-*` dashboard tokens, cards, buttons, badges, spacing, and motion patterns.

New components are thin composition layers around those primitives:

- `resources/js/Components/Cms/ContentFilterBar.vue`
- `resources/js/Components/Cms/ContentCollectionCard.vue`
- `resources/js/Components/Cms/ContentFormSection.vue`
- `resources/js/Components/Cms/ContentMediaField.vue`

No dependency manifest change is attributable to this task.

---

## 11. Localization, RTL/LTR, and accessibility

- All user-facing strings in the redesigned pages and components come from `useI18n()` / `page.props.ui` / `lang/{locale}/website.php['ui']`.
- An audit of 74 literal translation keys used by the new CMS components confirmed all keys exist in both English and Arabic.
- The previously hardcoded `Code` label was replaced with `ui.cms_content_page.code` (`Code` / `الرمز`).
- Arabic uses RTL document direction; English uses LTR. Direct browser checks confirmed `dir=rtl` / `dir=ltr` on the target pages.
- Directional date strings are wrapped with explicit `dir="ltr"` where appropriate.
- Form controls use labels, `aria-label`, `aria-selected`, `role="tablist"`, `role="alert"`, and focus-on-first-error behavior.
- Responsive CSS in `resources/css/dashboard.css` adds `.pc-content-*` rules for stacked mobile cards, wrapping toolbars, and RTL-aware spacing.
- Direct browser checks at 1440px and 390px found no horizontal overflow on the six target index/create pages.

---

## 12. Data safety, sanitization, and public behavior

- No stored field, status value, relationship, or record was renamed or invented.
- Validation still comes from the shared controller plus each module's existing `extraRules`.
- Slug uniqueness, permission checks, audit snapshots, and revision records remain in place.
- Rich text continues through the existing editor and sanitization/render path; the redesign does not bypass it.
- Preview remains editor-only and uses the existing preview route.
- Public Programs, Projects, and News controllers were not changed for this task; public pages continue to consume published records through existing queries and SEO output.
- The three temporary verification records were created only in the disposable E2E database at `/tmp/opencode/cms-e2e.sqlite`.

---

## 13. Files changed

### Backend and tests

- `app/Http/Controllers/Cms/ContentController.php`
- `app/Http/Controllers/Cms/ProgramController.php`
- `app/Http/Controllers/Cms/ProjectController.php`
- `app/Http/Controllers/Cms/ArticleController.php`
- `app/Http/Controllers/Cms/PublicationController.php`
- `app/Http/Controllers/Cms/PreviewController.php`
- `app/Http/Controllers/Cms/PillarController.php`
- `tests/Feature/CmsContentRedesignTest.php`

### Frontend, styles, and localization

- `resources/js/Pages/Cms/Content/Index.vue`
- `resources/js/Pages/Cms/Content/Edit.vue`
- `resources/js/Components/Cms/ContentFilterBar.vue`
- `resources/js/Components/Cms/ContentCollectionCard.vue`
- `resources/js/Components/Cms/ContentFormSection.vue`
- `resources/js/Components/Cms/ContentMediaField.vue`
- `resources/css/dashboard.css`
- `lang/en/website.php`
- `lang/ar/website.php`

### Report

- `docs/reports/CMS_PROGRAMS_PROJECTS_NEWS_REDESIGN_REPORT.md`

---

## 14. Tests added and updated

`tests/Feature/CmsContentRedesignTest.php` was created with six feature tests:

1. `test_programs_index_exposes_localized_filters_counts_sorting_pagination_and_transitions`
2. `test_projects_index_and_edit_preserve_lifecycle_relations_and_media`
3. `test_news_index_filters_category_tag_and_featured_and_edit_restores_tags`
4. `test_forms_persist_relationships_media_and_revision_actor`
5. `test_transitions_are_permission_aware_and_preserve_executed_workflow_graph`
6. `test_public_pages_keep_serving_only_published_records`

A regression assertion was added to the Projects edit test to require `resource = projects`, preventing the permission/UI key bug from returning.

No existing test assertion was weakened. Other test files were not modified for this task; their failures are documented as unrelated/concurrent below.

---

## 15. Verification results

All commands were run from `/root/PCDR/pcdr`.

| Check | Command | Result |
|---|---|---|
| Focused redesign feature test | `php artisan test --compact tests/Feature/CmsContentRedesignTest.php` | 6 tests, 216 assertions, passed |
| Frontend static checks | `npm run test:selection` | 12/12 passed |
| Production build | `npm run build` | Passed |
| Translation key audit | Scripted check of 74 literal keys | All present in `en` and `ar` |
| PHP syntax | `php -l` over all changed PHP files | Passed |
| Pint | Scoped to task PHP files | Passed |
| Composer audit | `composer audit` | No security advisories |
| NPM audit | `npm audit --audit-level=high` | 0 vulnerabilities |
| Diff whitespace | `git diff --check` | Passed |
| Route audit | `php artisan route:list --path=cms/programs`, `projects`, `news` | Existing routes intact |
| Earlier full suite | `php artisan test --compact` | 324 tests, 8,971 assertions, passed |
| Final full suite | `php artisan test --compact` | 323 tests, 316 passed, 7 failed, 9,263 assertions |

Final full-suite failures, all outside the three redesigned modules:

- `AdminNavigationReorganizationTest::test_owner_sees_localized_group_and_item_labels_in_both_locales`
- `CmsEditorialToolsTest::test_media_library_filters_records_and_exposes_per_locale_alt_text`
- `CmsEditorialToolsTest::test_content_editor_offers_public_media_only_to_users_with_media_view_permission`
- `CmsWorkflowTest::test_upload_rejects_disallowed_operating_system_extension`
- `GlobalUxRefinementTest::test_media_pagination_exposes_real_range_metadata_and_preserves_filters`
- `PartnerDirectoryTest::test_example` (`no such table: settings`)
- `SettingsTest::test_authorized_settings_update_persists_values_and_is_audited` (`Class "App\Services\AuditRedactor" not found`)

A combined run of five related files produced 67 tests, 62 passed, 5 failed, 4,950 assertions. A repeat of `CmsWorkflowTest.php` plus `AdminNavigationReorganizationTest.php` produced 27 tests, 25 passed, 2 failed, 1,093 assertions. These failures were in concurrent user-owned media/navigation/settings/partner-directory work and were not modified to make the suite green.

The build emitted two non-blocking pre-existing warnings: optional `fontaine` is not installed, and one JS chunk exceeds Vite's 500 kB advisory threshold.

---

## 16. Browser verification

Browser verification was actually performed using Firefox against the disposable E2E database/server.

### Global E2E suite

Result: 9 tests, 3 passed, 5 failed, 1 skipped.

Passed:

- Route discovery: 164 routes, including all nine index/create/edit routes for the three target modules.
- Screenshot capture: 56 captures at 1440px and 390px, including 24 target CMS captures.
- Select keyboard navigation: passed in RTL and LTR.

Failed:

- Overflow test aborted on `GET /ar/admin/audit-logs/export` because a download started.
- Font-size audit found 2,574 nodes below 13px globally.
- Axe found 28 serious/critical color-contrast violations globally.
- DataTable overflow check at 390px measured 696px against a `<= 391px` expectation.
- Reduced-motion check found three elements with `0.00001s` transitions.

Skipped:

- Lighthouse, because Chromium cannot launch in this environment and Firefox was used.

Some EN-labeled screenshots in `tests/e2e/screenshots/reports/screenshots.json` were stale/mislabeled with `/ar/...` URLs. They were not used as evidence for English verification; the direct English check below supersedes them.

### Direct authenticated browser check

- `/en/cms/programs`, `/en/cms/projects`, `/en/cms/news` and their `/create` pages were checked at 1440px and 390px.
- All reported `dir=ltr`, `lang=en`, English headings, and no horizontal overflow.
- Screenshots were written to `/tmp/opencode/en-*-*.png`.

Create → edit → update was exercised for one temporary record in each module in the disposable E2E database:

- Program id 1
- Project id 1
- News id 1

All three saves returned HTTP 303, showed no validation errors, and update screenshots were captured in `/tmp/opencode/en-*-edit-created.png` and `/tmp/opencode/en-*-edit-updated.png`. The Projects edit page correctly showed `Programs & projects` and `Project stage`, and revision history recorded the update.

The temporary PHP server on `127.0.0.1:8127` was stopped after verification.

---

## 17. Known limitations and follow-up

- The final full PHPUnit suite is not green because of unrelated/concurrent failures listed in section 15. No unrelated user-owned code was changed to suppress them.
- The global browser suite is not green because of legacy/global findings listed in section 16. None of the failures were traced to the three redesigned CMS modules.
- Lighthouse could not run because Firefox was required in this environment.
- Some global E2E screenshot metadata was stale for English labels; direct browser checks were used instead.
- The temporary verification records remain only in `/tmp/opencode/cms-e2e.sqlite`, not in the project database.
- Broad `pint --dirty` was not used at final handoff because it can restyle unrelated dirty files; Pint was scoped to task files after the last PHP edits.
- No browser verification is claimed beyond the Firefox checks explicitly described above.

---

## 18. Final status and handoff

Implementation is complete for the requested Programs, Projects, and News redesign. The redesigned pages are uncommitted in the existing `main` worktree alongside preserved user-owned changes. No protected file was touched, and branch/HEAD remained unchanged.

Final classification: **PASS WITH LIMITATIONS**.

The limitations are environmental and concurrent-work related, not unresolved defects in the requested three-module redesign: focused tests, direct browser verification, static checks, build, audits, and diff integrity all passed, while the global suite and global E2E suite retain pre-existing/concurrent failures that were deliberately left for their owners.
