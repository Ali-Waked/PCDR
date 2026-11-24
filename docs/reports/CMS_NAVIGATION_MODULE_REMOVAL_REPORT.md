# CMS Navigation Management Module Removal Report

Date: 2026-11-24  
Repository: `/root/PCDR/pcdr`  
Branch: `main`  
Baseline: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`

## 1. Objective and outcome

The CMS Navigation Management administration module was removed from both localized administrative surfaces (`/ar/cms/navigation` and `/en/cms/navigation`) while the public website navigation stack remained fully intact.

The removal was limited to administration-only code, configuration, translations, and code-owned RBAC references. No public navigation service, model, migration, seeder record, shared Inertia prop, header/footer component, mobile navigation control, locale helper, or canonical footer-group definition was removed or altered.

**Final status: `PASS WITH LIMITATIONS`.** All navigation-scoped checks passed. The full PHPUnit suite still reports two failures in unrelated, pre-existing media/global-UX areas; these are documented in section 9 and were not modified as part of this task.

## 2. Scope, baseline, and classification audit

The worktree already contained extensive staged, unstaged, and untracked concurrent work before this task. All such changes were preserved functionally; one incidental Pint formatting pass touched two unrelated untracked PHP files and is disclosed in section 9. No branch, worktree, merge, commit, push, stash, reset, clean, broad checkout/restore, or broad `git add` was used. `README.md`, `.env.mysql.bak`, and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not modified.

Source-only greps excluded noisy generated/artifact directories (`storage/`, `tests/e2e/screenshots/`, `node_modules/`, `vendor/`, `public/build/`).

### Classification

| Class | Meaning | Disposition |
| --- | --- | --- |
| **A** | Administration-only CMS navigation management | Removed |
| **B** | Shared with the public website | Preserved unchanged |
| **C** | Shared with the admin shell or other CMS modules | Preserved unchanged |

#### A — administration-only, removed

- `app/Http/Controllers/Cms/NavigationController.php`
- `routes/web.php`: controller import plus `cms.navigation.index` and `cms.navigation.update`
- `resources/js/Pages/Cms/Navigation/Index.vue`
- `resources/js/Components/Cms/NavigationMenuEditor.vue`
- `config/admin_navigation.php`: `navigation` sidebar item
- `config/cms.php`: `navigation.view` and `navigation.update` labels
- `config/permissions.php`: `navigation.view`, `navigation.update`, Arabic resource label, module mapping, and supported entries
- `database/seeders/CmsRolesSeeder.php`: navigation-permission grants to `website_administrator`
- `lang/en/ui.php`, `lang/ar/ui.php`: `admin_nav.navigation`
- `lang/en/website.php`, `lang/ar/website.php`: `ui.cms_navigation`

#### B — public/shared, preserved

- `app/Services/NavigationService.php`
- `app/Models/NavigationMenu.php`, `NavigationItem.php`, `NavigationItemTranslation.php`
- `database/migrations/2026_11_23_000400_create_navigation_and_inquiries_schema.php`
- `database/seeders/WebsiteSeeder.php` navigation records
- `app/Http/Middleware/HandleInertiaRequests.php` shared `navigation`, `ui`, `nav`, locale, and direction props
- `resources/js/composables/usePublicNavigation.js`
- `resources/js/Components/Public/SiteHeader.vue`
- `resources/js/Components/Public/SiteFooter.vue`
- Public navigation translations and locale-switching helpers

#### C — shared infrastructure, preserved

- `app/Services/AdminNavigationService.php` and the rest of `config/admin_navigation.php`
- `CmsDashboardLayout.vue`, `CmsLayout.vue`, `DashboardChrome.vue`, and their mobile-navigation state
- Unified RBAC services, permission catalog architecture, role assignments, audit logging, and Platform Owner boundaries
- All other CMS modules and their routes/permissions

There was no separate create, edit, or delete screen/route for navigation management; the single Inertia page and its editor component supplied add/remove/save behavior. Both the index and update endpoints were removed.

## 3. Administration-only components removed

The following files were deleted explicitly:

- `app/Http/Controllers/Cms/NavigationController.php`
- `resources/js/Pages/Cms/Navigation/Index.vue`
- `resources/js/Components/Cms/NavigationMenuEditor.vue`

Targeted edits removed the controller import, both route registrations, the admin sidebar entry, the legacy CMS permission labels, the code-owned permission-catalog entries, the seeder grants, and the exclusive English/Arabic UI translations. The stale `ui.cms_navigation` lookup disappeared with the deleted page; no replacement translation was added.

A source scan of `app/`, `config/`, `database/`, `lang/`, `resources/`, and `routes/` for the removed admin fragments (`Cms/Navigation/Index`, `NavigationMenuEditor`, `/cms/navigation`, `navigation.view`, `navigation.update`, `cms.navigation.index`, `cms.navigation.update`, `cms_navigation`, `ui.admin_nav.navigation`) found no remaining production references. The only matches are deliberate negative assertions in the new removal test.

## 4. Routes, endpoints, and legacy URL behavior

Removed routes:

- `GET /{locale}/cms/navigation` → `cms.navigation.index`
- `PUT /{locale}/cms/navigation/{menu}` → `cms.navigation.update`

Verification:

- `php artisan route:list --name=cms.navigation --except-vendor` — no routes.
- `php artisan route:list --path=cms/navigation --except-vendor` — no routes.
- `GET /ar/cms/navigation`, `GET /en/cms/navigation`, `PUT /ar/cms/navigation/1`, `PUT /en/cms/navigation/1`, and unlocalized `GET /cms/navigation` all return `404` for a Platform Owner.

This follows the existing project convention: `RedirectToLocalizedUrl` is the application fallback, localized paths that no longer resolve return 404, non-GET unmatched requests return 404, and unlocalized candidates are checked against real localized routes before any redirect. Old admin URLs therefore fail consistently and are never redirected to public navigation pages or public website routes.

## 5. Unified Roles & Permissions changes and preserved history

The canonical Roles & Permissions architecture remains unchanged. Only code-owned catalog/seeder references to the removed module were deleted:

- `navigation.view` and `navigation.update` removed from `config/permissions.php` supported list, definitions, Arabic resource label, and module mapping.
- Both keys removed from `config/cms.php` permission labels.
- Both keys removed from the `website_administrator` seeder definition.
- The sidebar item requiring `navigation.view` was removed.

Preserved:

- Historical permission rows in the database are not deleted, dropped, retired, or rewritten by this change.
- Existing role assignments and audit records are untouched.
- `pcdr:permissions:sync` only iterates supported code-defined permissions; a focused test confirms a retired historical `navigation.view` row and its role assignment survive the sync command unchanged.
- Platform Owner authorization, institutional-approval denial, denied authority patterns, and unrelated permissions remain intact.
- No separate admin permission namespace was introduced or removed.

## 6. Public navigation preservation

The public navigation path remains database-backed and bilingual:

- `NavigationService::all($locale)` still reads navigation menus/items/translations from the database.
- `HandleInertiaRequests` still shares `navigation`, `ui`, `nav`, `locale`, and `direction`.
- `usePublicNavigation.js` still prefers seeded `page.props.navigation.primary`, with compiled fallbacks only when the database is unseeded.
- The canonical three-group footer structure (`about`, `knowledge`, `involve`) remains in `usePublicNavigation.js`.
- `SiteHeader.vue` and `SiteFooter.vue` still consume the composable.
- `CmsLayout.vue` and `DashboardChrome.vue` mobile-navigation state and scrims remain present.
- Arabic/English localized URLs remain correct; seeded primary items resolve to the active locale, e.g. `/ar/strategic-areas` and `/en/strategic-areas`.
- Navigation tables, seeders, records, and models were not modified.

The focused test seeds `WebsiteSeeder` and verifies ten localized primary and footer items plus localized URLs in both locales through real Inertia responses.

## 7. Database and migration decision

No migration was added, changed, or run destructively. No table was dropped, no record was deleted, and no database reset was performed. Navigation menus, items, translations, and all other CMS/public tables remain in place. The removal is entirely code, configuration, translation, route, UI, and test level.

## 8. Tests added or updated

New focused test:

- `tests/Feature/CmsNavigationModuleRemovalTest.php`
  - routes/assets absent; localized and unlocalized legacy URLs return 404
  - production sources contain no removed admin fragments
  - code-owned permission catalogs and exclusive translations no longer contain navigation-management entries
  - seeded DB-backed navigation is shared in Arabic and English with correct localized URLs
  - header, footer, mobile navigation, and canonical footer groups remain wired
  - other CMS modules remain accessible without a navigation sidebar item
  - unified RBAC, Platform Owner boundaries, historical permission rows, role assignments, and audit event remain preserved

Updated tests:

- `tests/Feature/CmsWorkflowTest.php` — removed stale `cms.navigation.index`, `cms.navigation.update`, and `/ar/cms/navigation` expectations.
- `tests/Feature/AdminNavigationReorganizationTest.php` — removed the navigation sidebar item expectation, reduced the item count from 19 to 18, removed the navigation label from both locales, and aligned the remaining content-group label sequence with the current config (including the already-present partner-directory entry).

Re-run unchanged:

- `tests/Feature/PublicWebsiteTest.php` — public navigation, shell, footer, and localized route coverage.

## 9. Verification results

### Navigation-scoped tests

Command: `php artisan test --compact tests/Feature/CmsNavigationModuleRemovalTest.php tests/Feature/AdminNavigationReorganizationTest.php tests/Feature/CmsWorkflowTest.php tests/Feature/PublicWebsiteTest.php`

Result: 63 tests, 62 passed, 1 failed, 10,233 assertions. The single failure was the unrelated pre-existing `CmsWorkflowTest::test_upload_rejects_disallowed_operating_system_extension`.

All tests in `CmsNavigationModuleRemovalTest`, `AdminNavigationReorganizationTest`, and `PublicWebsiteTest` passed.

### Full PHPUnit suite

Command: `vendor/bin/phpunit`

Result: 338 tests, 336 passed, 2 failed, 15,259 assertions, 204.8s.

Failures, both outside the navigation module:

1. `Tests\Feature\CmsWorkflowTest::test_upload_rejects_disallowed_operating_system_extension` — expected `InvalidArgumentException` for `photo.png!!` was not thrown. This depends on the already-modified `app/Actions/UploadMedia.php`, which was not touched by this task.
2. `Tests\Feature\GlobalUxRefinementTest::test_media_pagination_exposes_real_range_metadata_and_preserves_filters` — expected filters `['q', 'type']`, actual filters `['q', 'sort', 'type', 'visibility']`. This belongs to concurrent media/global-UX work and was not touched by this task.

Neither failure involves navigation routes, navigation permissions, public navigation props, header/footer/mobile navigation, or the components changed here. They are not masked or modified.

### Formatting and syntax

- `vendor/bin/pint --test --format agent <all PHP files owned by this task>` — passed.
- `vendor/bin/pint --format agent tests/Feature/CmsNavigationModuleRemovalTest.php` — fixed import ordering and strict-type formatting in the new test; final `--test` passed.
- `vendor/bin/pint --dirty --format agent` was run at finalization per project rules. Pint's `--dirty` flag scans the whole working tree even when path arguments are supplied, so it also reformatted two unrelated untracked concurrent files (`app/Http/Controllers/Cms/DirectoryPartnerController.php` and `app/Http/Controllers/Cms/DirectoryPartnerCategoryController.php`) and reported a third (`tests/Feature/PublicationsMediaUsersSettingsRedesignTest.php`) that was subsequently rewritten by concurrent work. The edits were style-only (import/FQCN normalization, brace placement, empty-body formatting, operator spacing); no functional logic was altered. `php -l` and `PartnerDirectoryTest` still pass.
- `php -l` on every changed PHP file — no syntax errors.
- `git diff --check` — no whitespace errors.

### Frontend and dependency audits

- `npm run build` — passed; Vite 8.3.8 transformed 807 modules. Non-blocking warnings: optional `fontaine` package not installed and a chunk larger than 500 kB.
- `composer audit --no-interaction` — no security vulnerability advisories.
- `npm audit --omit=dev` — 0 vulnerabilities.
- `public/build` produced no tracked working-tree changes.

### Browser verification

No live browser, screenshot, or visual regression verification was performed in this session. Responsive header/footer/mobile behavior was verified through source wiring assertions, feature tests, and a successful production build, not by visual inspection.

## 10. Limitations and final status declaration

Limitations:

1. The full PHPUnit suite is not green because of two unrelated pre-existing/concurrent media and global-UX failures documented in section 9.
2. A final `pint --dirty` run incidentally reformatted two unrelated untracked concurrent PHP files (style only, disclosed in section 9); no task-owned files were changed by that run.
3. No live browser verification was performed.
4. Historical documentation references to navigation management were left as historical records; only production code, configuration, translations, and tests were changed.

Within the requested scope, the administration module is removed, old localized admin URLs consistently return 404, public Arabic/English navigation behavior is preserved, unified RBAC and historical records are preserved, and all navigation-specific tests pass.

**Final status: `PASS WITH LIMITATIONS`**
