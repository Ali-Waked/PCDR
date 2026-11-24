# Admin Navigation Reorganization Report

## Scope and git safety

Work was performed directly in the existing `main` worktree at `/root/PCDR/pcdr`.

- Initial branch: `main`
- Initial HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`
- Initial status snapshot: `/tmp/opencode/navigation_initial_status.txt` (215 entries)
- Final status snapshot: `/tmp/opencode/navigation_final_status.txt`
- No branch, worktree, commit, push, broad staging, or destructive Git command was used.
- Protected files were not read or modified:
  - `.env.mysql.bak`
  - `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md`
- `README.md` and `app/Http/Controllers/Admin/DashboardController.php` were not modified.
- All pre-existing staged, unstaged, and untracked work remains in place. The final status differs from the initial status only by the files intentionally changed for this task and the new test/report files.

## Audit findings

The delivered admin/CMS sidebar is server-resolved:

- `config/admin_navigation.php` is the active navigation contract.
- `AdminNavigationService::visibleFor()` resolves labels and permission-filtered items from `RbacService`.
- `HandleInertiaRequests` shares the result as `adminNavigation`.
- `DashboardChrome.vue` is the primary rendered sidebar for admin/CMS dashboard pages.
- `CmsLayout.vue` is a second sidebar renderer using the same shared prop; it was kept consistent.
- `resources/js/Pages/Dashboard.vue` consumes navigation items for its quick-access module cards.
- `config/dashboard_features.php` gates groups by feature state. Because the new group keys differ from the original feature keys, each group now carries an explicit `feature` mapping so the original gating is preserved.
- `config/admin_navigation_hidden.php` remains unchanged and still preserves the hidden `operations` and `phase5` groups.

Initial structure:

1. `overview` — Dashboard
2. `access` — Roles & Permissions, Users, Audit logs, Audit archives, Platform settings
3. `content` — Content dashboard, Strategic pillars, Programmes, Projects, News, Publications, Media library, Navigation menus, Homepage, Site settings, Inquiries, Email templates

There were 18 configured destinations in three groups. All route names, paths, permissions, and localized URL behavior were preserved.

## Final navigation structure

1. **Overview** / نظرة عامة
   - Dashboard — `/dashboard`
2. **Content & Website** / المحتوى والموقع
   - Content dashboard — `/cms`
   - Homepage — `/cms/homepage`
   - Navigation menus — `/cms/navigation`
   - Strategic pillars — `/cms/pillars`
   - Programmes — `/cms/programs`
   - Projects — `/cms/projects`
   - News — `/cms/news`
   - Publications — `/cms/publications`
   - Media library — `/cms/media`
   - Site settings — `/cms/settings`
3. **Communications** / التواصل
   - Inquiries — `/cms/inquiries`
   - Email templates — `/cms/email-templates`
4. **Identity & Security** / الهوية والأمان
   - Roles & Permissions — `/admin/access`
   - Users — `/admin/users`
   - Audit logs — `/admin/audit-logs`
   - Audit archives — `/admin/audit-archives`
5. **System** / النظام
   - Platform settings — `/admin/settings`

Feature-state mapping retained: `overview → overview`, `content → content`, `communications → engagement`, `identity → access`, `system → access`. No duplicate item keys or paths were introduced; all 18 destinations remain present.

## Files modified

- `config/admin_navigation.php` — five groups, target order, explicit feature mapping.
- `app/Services/AdminNavigationService.php` — honors the optional group `feature` mapping without changing permission filtering.
- `lang/en/ui.php` — added/updated group labels; retained existing item labels.
- `lang/ar/ui.php` — added/updated Arabic group labels; refined `media` to `مكتبة الوسائط`.
- `lang/en/website.php` — removed `ui.dashboard_owner_note`.
- `lang/ar/website.php` — removed the Arabic equivalent of that key.
- `resources/js/Components/Dashboard/DashboardChrome.vue` — removed the owner-specific disclaimer branch, kept the neutral workspace note, added `audit_archives` icon mapping.
- `resources/js/Layouts/CmsLayout.vue` — added matching icon rendering and icon map for the second sidebar renderer.
- `resources/js/Pages/Dashboard.vue` — quick-access cards now read module items across all groups instead of assuming the old `content` group.
- `resources/css/app.css` — clearer group headings, consistent icon/label alignment, explicit focus states, mobile overflow guard.
- `resources/css/dashboard.css` — clearer group headings, explicit focus state, mobile overflow guard.
- `tests/Feature/AdminNavigationReorganizationTest.php` — new focused coverage.
- `tests/Feature/PlatformOwnerDashboardFoundationTest.php` — updated existing navigation expectations.
- `tests/Feature/WebsiteCmsScopeTest.php` — updated existing navigation expectations.
- `docs/reports/ADMIN_NAVIGATION_REORGANIZATION_REPORT.md` — this report.

## Localization changes

English group labels are now `Overview`, `Content & Website`, `Communications`, `Identity & Security`, `System`.

Arabic group labels are now `نظرة عامة`, `المحتوى والموقع`, `التواصل`, `الهوية والأمان`, `النظام`.

Labels continue to flow through the existing `ui.admin_nav.*` translation mechanism and `AdminNavigationService`; no translated navigation label was hardcoded in Vue. Both locales were asserted in feature tests, including full item-label sequences.

## Removed UI sentence and search scope

Removed exact English sentence:

> Technical administrator only; institutional authority is not granted.

Locations changed:

- `lang/en/website.php` — deleted the `dashboard_owner_note` entry containing the exact sentence.
- `lang/ar/website.php` — deleted the equivalent `dashboard_owner_note` entry (`مدير تقني فقط؛ لا تُمنح صلاحية مؤسسية.`).
- `resources/js/Components/Dashboard/DashboardChrome.vue` — replaced `isPlatformOwner ? t('ui.dashboard_owner_note') : t('ui.dashboard_technical_note')` with `t('ui.dashboard_technical_note')`. The now-unused permission import/destructuring was removed.

Search scope:

- `lang/en`, `lang/ar`
- `resources/js`, `resources/css`
- `config`
- `database/seeders`
- `public` after `npm run build`
- Repository-wide scan excluding `vendor`, `node_modules`, `.git`, and the two protected files

No occurrences remain in user-facing translation, component, configuration, seeder, layout, or built assets. Remaining matches are limited to the negative assertions in the new test file and pre-existing `storage/inertia-devtools/*.json` request snapshots from earlier sessions; those are diagnostic artifacts, are not part of the delivered UI, and were left untouched to preserve existing user state.

## Governance preservation

This was UI copy and navigation grouping only.

- No backend authorization rule, gate, policy, role, permission, or privilege boundary was changed.
- `authority.institutional_approval.granted` remains `false`.
- Governance permission prefixes/suffixes remain intact and are still shared to the frontend as denied patterns.
- Institutional approval keys remain blocked for Platform Owner and normal users.
- OQ-49 remains `PARTIALLY RESOLVED — PENDING FORMAL APPROVAL`.
- All 17 ADRs remain `PROPOSED`; no ADR was accepted or marked approved.
- Governance-related tests were not removed or weakened; focused governance/RBAC tests were run.

## Tests and exact results

- Focused navigation suite:
  - `php artisan test --compact tests/Feature/AdminNavigationReorganizationTest.php`
  - **9 tests passed, 869 assertions passed**
- Affected existing suites:
  - `php artisan test --compact tests/Feature/PlatformOwnerDashboardFoundationTest.php tests/Feature/WebsiteCmsScopeTest.php tests/Feature/UnifiedRolesPermissionsTest.php tests/Feature/CmsPagesModuleRemovalAndStrategicContentTest.php tests/Feature/Phase5WorkflowTaskTest.php`
  - **48 tests passed, 1,250 assertions passed**
- Full suite:
  - `php artisan test --compact`
  - **286 tests passed, 7,636 assertions passed**
- Laravel Pint:
  - `vendor/bin/pint --dirty --format agent`
  - **passed**
- Vite build:
  - `npm run build`
  - **passed**; warnings only (optional `fontaine` package not installed; one JS chunk exceeds 500 kB)
- Whitespace check:
  - `git diff --check`
  - **passed with no output**

Coverage includes group existence/order, item order, duplicate detection, route-name/URL mapping in AR and EN, reachability of all 18 destinations, permission-filtered visibility, owner/non-owner authority props, active-state markup contract, RTL/LTR labels, disclaimer removal, unchanged governance configuration/ADR status, and unchanged RBAC boundaries.

## Browser verification status

No browser-based visual verification was performed for this task. Responsive and RTL/LTR behavior was verified by source/config assertions and successful automated build/tests only.

## Known limitations

- Active-route highlighting is verified through its server-provided path metadata and Vue source contract, not by a browser interaction test.
- Mobile usability is verified by static assertions on existing open/close behavior and overflow guards, not by a device or emulator session.
- Pre-existing Inertia devtools request snapshots under `storage/inertia-devtools/` retain the historical sentence. They are non-user-facing diagnostics and were intentionally preserved.

## Classification

**PASS WITH LIMITATIONS**
