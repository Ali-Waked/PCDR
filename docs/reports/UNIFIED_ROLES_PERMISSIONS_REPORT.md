# Unified Roles & Permissions Report

Date: 2026-11-24

Branch: `main`  
Baseline commit: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`

## 1. Objective and scope

This work completed the homepage backend integration verification, then audited and consolidated PCDR’s fragmented Roles & Permissions administration into a single canonical admin page.

The agreed scope included:

- Keeping permissions predefined by application code.
- Allowing only a supported, code-owned permission catalog to be assigned from the dashboard.
- Permitting role create/update/delete and role-permission assignment, but no dashboard permission definition CRUD.
- Preserving institutional-governance boundaries, especially the Platform Owner’s technical-only authority.
- Providing one canonical page, one navigation entry, and compatibility redirects for old URLs.
- Adding focused automated coverage and running PHPUnit, Pint, the production build, static checks, and dependency audits.

Out of scope:

- No new section-level or module-level granular permission scheme was introduced.
- No dashboard permission create/update/delete feature was restored.
- No institutional approval, appointment, delegation, signing, ratification, OQ-49, or ADR approval authority was changed or granted.
- No browser, visual, screenshot, Lighthouse, or Playwright verification was performed.
- No commit, push, branch switch, worktree, merge, stash, reset, clean, `checkout .`, or `restore` was performed.

## 2. Worktree and preservation controls

All work was performed only in `/root/PCDR/pcdr` on the existing `main` branch.

Preservation results:

- Pre-existing and concurrent uncommitted work remained in the worktree.
- No `git add`, `git add .`, or `git add -A` was used.
- No destructive Git command was used.
- `README.md` was not modified.
- `app/Http/Controllers/Admin/DashboardController.php` was not modified.
- `.env.mysql.bak` was not read, modified, staged, or committed.
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` was not read, modified, staged, or committed.
- Pint was run with explicit task-file paths instead of blanket `--dirty`, because the worktree contains extensive concurrent user changes.
- Audit/archive work and its untracked files were left untouched.

## 3. Homepage integration verification

The homepage integration remained intact and was not changed during the RBAC redesign.

Earlier homepage-specific verification results:

- Targeted homepage tests: `10 passed, 172 assertions`.
- Combined homepage-related tests: `48 passed, 2,834 assertions`.
- Full suite at that time: `250 passed, 6,186 assertions`.
- `npm run build`: passed.

The later RBAC work added new tests and modified unrelated stale tests, so the final full-suite totals are higher and are recorded in section 12.

## 4. RBAC audit findings

The pre-change administration area had several problems:

- Roles and permissions were exposed as separate administrative concepts.
- Permission CRUD endpoints allowed dashboard-driven permission definitions even though permissions are application-code capabilities.
- The dashboard received broad permission data, including reserved, internal, hidden, and historical keys.
- Permission sync could create, rename, delete, or retire rows, which risked destroying historical assignments.
- Role synchronization could silently detach unsupported or historical permissions.
- Navigation had separate role and permission destinations.
- The current worktree already contained concurrent catalog changes: six `pages.*` keys had been removed and four `audit_archives.*` keys added.

The audit conclusion was that the permission catalog must become a code-owned read-only allowlist, while role management remains the only mutable RBAC surface.

## 5. Catalog model and key preservation

`config/permissions.php` now carries catalog metadata and an explicit `dashboard_supported` allowlist.

Final catalog state:

- `177 total / 63 supported`.
- `HEAD` had `179` keys; the current worktree has `177`.
- Concurrent removal preserved: `pages.view`, `pages.create`, `pages.update`, `pages.delete`, `pages.publish`, `pages.unpublish`.
- Concurrent additions preserved: `audit_archives.view`, `audit_archives.download`, `audit_archives.manage`, `audit_archives.cleanup`.
- The historical `inquiries.update` module was restored to `content`, matching the baseline catalog.

Supported categories exposed to the dashboard:

- `identity_access`
- `platform_administration`
- `website`

Excluded from dashboard assignment:

- `operations`
- `phase5`
- reserved workflow keys
- internal permission-management keys
- `roles.grant_protected`
- orphaned `pages.*` keys
- DB-only custom or legacy permissions
- institutional, governance, and authority-style capabilities

`App\Support\PermissionCatalog` provides the supported/excluded accessors, supported keys, category labels, and grouped read-only catalog payloads.

## 6. Safe synchronization behavior

`pcdr:permissions:sync` was changed to be additive and non-destructive:

- It creates or refreshes only entries with `dashboard_supported => true`.
- It does not delete unsupported, historical, reserved, or custom rows.
- It does not retire removed keys.
- It is idempotent across repeated runs.
- It preserves existing role assignments to unsupported or historical permissions.

`RbacManagementService::syncRolePermissions()` uses the same supported-ID allowlist and preserves current assignments whose keys are outside the supported catalog. The dashboard cannot silently detach historical capabilities.

`App\Support\RolePermissionInput` centralizes normalization, supported-ID validation, and protected-permission escalation checks. Supported IDs must exist in the database, be non-retired, and appear in the code-owned catalog.

## 7. Server-side authorization hardening

The following request classes were hardened:

- `StoreRoleRequest`
- `UpdateRoleRequest`
- `SyncRolePermissionsRequest`

They now enforce:

- Supported-only permission IDs.
- Numeric, unique, integer permission input.
- Owner-level ability to assign protected supported permissions.
- Non-owner rejection when protected permissions are touched, including already-attached protected permissions.

`RbacService::dashboardPermissionsFor()` and the Inertia shared props now expose only supported permissions. The wildcard capability remains available for universal technical access, but institutional authority keys and unsupported backend permissions are excluded.

## 8. Unified backend and routes

Created `App\Http\Controllers\Admin\AccessController`, which renders `Admin/Access/Index` with:

- `roles`: role metadata, supported assigned permission IDs, member counts, and member summaries.
- `catalog`: grouped supported permissions with localized labels and descriptions.
- `stats`: backend-derived totals for all roles, system roles, custom roles, and available permissions.

Canonical route:

- `GET {locale}/admin/access`, named `admin.access.index`.

Compatibility behavior:

- `GET {locale}/admin/roles` redirects to `/admin/access`.
- `GET {locale}/admin/roles/{role}/edit` redirects to `/admin/access?role={id}&edit=1`.
- `GET {locale}/admin/permissions` redirects to `/admin/access`.
- Role CRUD and role-permission sync endpoints remain for the role editor.
- Permission create/store/edit/update/destroy routes were removed.
- `PermissionController` is a bookmark-compatible redirect only.

## 9. Unified frontend and navigation

Created `resources/js/Pages/Admin/Access/Index.vue`, a responsive single-page admin experience with:

- Roles and catalog tabs.
- Role search and permission search.
- Role create/edit drawer.
- Read-only permission catalog.
- Permission selection limited to supported, assignable entries.
- Protected, system, custom, and non-assignable indicators.
- Backend-derived statistics.
- Mobile layout at the `820px` breakpoint.
- Existing confirm/notification and icon conventions.

`config/admin_navigation.php` now has one consolidated `roles_permissions` item pointing to `/admin/access`. Old role/permission pages are no longer separate navigation destinations.

Missing UI labels were added to English and Arabic translation files, including `ui.clear`, `ui.select`, `ui.selected`, and `access_page.roles`. Concurrent `SelectionControl.vue` translation requirements were preserved.

## 10. Governance boundary

The Platform Owner retains comprehensive technical control over platform modules and supported technical capabilities.

The following remain explicitly denied:

- Institutional approval authority.
- Appointment, delegation, signing, and ratification authority.
- Board or policy authorization authority.
- Any future authority-like key detected by the governance fail-closed rules.

OQ-49 was not changed. ADR approval behavior was not changed. No code-defined support flag was added to any institutional or governance authority key.

## 11. Stale test corrections

Two `PlatformOwnerDashboardFoundationTest` methods still asserted the removed dynamic permission CRUD flow and were updated narrowly:

- `test_permission_forms_reject_all_institutional_authority_keys` became `test_unified_access_page_rejects_all_institutional_authority_keys`. It now verifies that authority keys are unsupported, rejected by supported-only role assignment, and that permission CRUD routes are absent.
- `test_role_and_permission_mutations_are_audit_logged` became `test_role_mutations_are_audit_logged`. It now verifies role creation, update, permission change, and deletion audit events without asserting removed permission CRUD endpoints.

`RbacTest.php` was updated so supported permissions verify cache invalidation, while historical/custom permissions remain effective but are not dashboard assignable.

## 12. Verification

| Command | Result |
| --- | --- |
| `php artisan test --compact tests/Feature/RbacTest.php tests/Feature/UnifiedRolesPermissionsTest.php tests/Feature/AdminAuthorizationTest.php tests/Feature/PlatformOwnerDashboardFoundationTest.php tests/Feature/TranslationKeysTest.php` | `43 passed, 642 assertions`. |
| `php artisan test --compact` | `277 tests, 276 passed, 1 failed, 6,763 assertions`. |
| `npm run build` | Passed with Vite 8.3.8; 798 modules transformed. |
| `composer audit --format=summary` | `No security vulnerability advisories found.` |
| `npm audit --omit=dev` | `found 0 vulnerabilities`. |
| `git diff --check` | Clean. |
| Explicit PHP syntax checks on task PHP files | `PHP syntax OK`. |
| Explicit Pint runs on task PHP files | Passed. |
| Catalog check | `177 total / 63 supported`. |
| `php artisan route:list --except-vendor` | Confirmed canonical, compatibility, and role endpoints; permission CRUD endpoints absent. |

The new `UnifiedRolesPermissionsTest.php` covers:

- Supported catalog allowlist and excluded authority/backend keys.
- Idempotent, non-retiring sync.
- Unified page props and supported-only catalog payload.
- Legacy destination redirects.
- Absence of permission CRUD endpoints.
- Supported-only role create/update behavior.
- System-role deletion protection.
- Non-owner escalation prevention.
- Platform Owner technical-only boundary.
- Frontend source contract and one canonical navigation entry.

Build warnings were non-blocking: the optional `fontaine` package is not installed, and the main JS chunk exceeds Vite’s 500 kB advisory threshold.

## 13. Remaining full-suite failure

The final full suite has one failure unrelated to this task:

- `Tests\Feature\AuditTrailTest::test_audit_log_viewing_and_export_require_authorization`
- Expected audit action: `audit_logs.export`
- Actual concurrent implementation action: `audit_logs.exported`

The source change came from concurrent audit-archive/audit-interface work in `app/Http/Controllers/Admin/AuditLogController.php`; that work also added a newer `AuditLogsInterfaceTest` that expects `audit_logs.exported`. The stale older assertion was left untouched to preserve concurrent user work. This is documented as a limitation rather than silently changed.

Earlier in the verification cycle, two untracked audit-archive smoke tests also failed because the shared in-memory SQLite test database lacked the `settings` table. They passed in the final full run after concurrent work settled and were not modified.

## 14. Limitations and residual risks

- No browser, visual, screenshot, Playwright, Lighthouse, or accessibility verification was performed.
- The remaining full-suite failure in `AuditTrailTest.php` must be reconciled by the owner of the concurrent audit work: either expect `audit_logs.exported` or restore the previous action name.
- Granular section-level and module-level permissions remain outside the agreed current scope.
- Unsupported and historical database permissions remain effective at runtime when already assigned; they are intentionally preserved and hidden from dashboard assignment.
- The shared worktree contains extensive unrelated uncommitted work. Only the files listed in section 15 were changed for this task.
- No deployment or migration against a production database was performed.

## 15. Change inventory and final status

Files created by this task:

- `app/Support/PermissionCatalog.php`
- `app/Support/RolePermissionInput.php`
- `app/Http/Controllers/Admin/AccessController.php`
- `resources/js/Pages/Admin/Access/Index.vue`
- `tests/Feature/UnifiedRolesPermissionsTest.php`
- `docs/reports/UNIFIED_ROLES_PERMISSIONS_REPORT.md`

Files modified by this task:

- `config/permissions.php`
- `app/Console/Commands/SyncPermissionsCommand.php`
- `app/Http/Requests/Admin/StoreRoleRequest.php`
- `app/Http/Requests/Admin/UpdateRoleRequest.php`
- `app/Http/Requests/Admin/SyncRolePermissionsRequest.php`
- `app/Services/RbacManagementService.php`
- `app/Services/RbacService.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Admin/RoleController.php`
- `app/Http/Controllers/Admin/PermissionController.php`
- `config/admin_navigation.php`
- `routes/web.php`
- `lang/en/ui.php`
- `lang/ar/ui.php`
- `lang/en/website.php`
- `lang/ar/website.php`
- `tests/Feature/RbacTest.php`
- `tests/Feature/AdminAuthorizationTest.php`
- `tests/Feature/PlatformOwnerDashboardFoundationTest.php`

Final status: **PASS WITH LIMITATIONS**
