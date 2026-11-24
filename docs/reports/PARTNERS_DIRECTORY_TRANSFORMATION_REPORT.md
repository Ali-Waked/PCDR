# Partners Directory Transformation Report

Generated: 2026-11-24  
Repository: `/root/PCDR/pcdr`  
Branch: `main`  
Baseline HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
Baseline snapshot: `/tmp/opencode/partners-directory/initial-git-state.txt`  
Final status: **PASS WITH LIMITATIONS**

---

## 1. Executive summary and final status

The former partnership application page has been replaced with a professional, backend-driven, fully CMS-managed institutional **Partners Directory**. The public page at `/{locale}/partners` is informational only: it displays CMS-published partner records, grouped by CMS-managed categories, with logos rendered via the existing Media Library and `object-fit: contain`. The partnership *application workflow* was removed from public routes, controllers, form requests, actions, CMS tabs, CTAs, and UI strings.

The CMS now provides a dedicated **Partners directory** section with partner and category management, bilingual fields, Media Library logo selection, publish/unpublish, ordering, search, filters, pagination, validation, confirmation dialogs, safe deletion, and audit logging. It manages website content only; it does **not** grant technical administrators authority to confirm partnerships or sign agreements.

All work remains uncommitted in the primary working tree. No branch, worktree, merge, commit, push, reset, clean, stash, `git checkout .`, `git restore`, `git add .`, or `git add -A` was performed. Concurrent staged and unstaged work was preserved.

Final status is **PASS WITH LIMITATIONS**:

- **PASS** because the task-specific suite, targeted regressions, build, lint/format, route checks, migration, audits, and data-preservation checks succeeded.
- **WITH LIMITATIONS** because browser/visual verification tooling was unavailable, the full suite still reports two unrelated media-module failures from concurrent work, and the Vite build emits non-blocking optional-package/chunk-size warnings.

---

## 2. Scope, objectives, and governance constraints

### In scope

- Read-only audit of the existing partnership page, inquiry routes, CMS inquiry UI, public navigation, roles/permissions, Media Library, and localization architecture.
- Removal of only the partnership application workflow while preserving contact and volunteer inquiry flows.
- New public Partners Directory with canonical `/{locale}/partners` and backwards-compatible `/{locale}/partnerships` alias.
- New CMS directory module using dedicated `directory_partners.*` permissions and dedicated schema.
- Additive, reversible migration; bilingual AR/EN strings; RTL/LTR support; SEO metadata; accessibility-oriented markup; feature tests; and this report.

### Explicitly preserved / out of scope

- The operational `partners` table and its sensitive fields (`internal_notes`, `strategic_alignment`, `public_contact`, `relationship_status`) were not exposed or modified.
- Historical `partnership_inquiries` records, the `PartnershipInquiry` model, original migration, mail mappings, and the unused historical `PartnershipRequest` class were preserved.
- Contact and volunteer inquiry flows, operational partner management, navigation conventions, footer, and all unrelated concurrent work were preserved.
- No fictional partners, logos, or copied reference organizations were added.
- `README.md`, `app/Http/Controllers/Admin/DashboardController.php`, `.env.mysql.bak`, and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not modified; the two protected untracked files were not read.

### Design authority boundary

The CMS directory is a **website content tool only**. Publish/unpublish and status controls affect public visibility of website content. They are not institutional partnership approval, contract execution, or signature authority.

---

## 3. Baseline, Git safety, and concurrent-work preservation

Baseline was captured on `main` at `07848abbf1a2b3d12102b4fd0ee37ecc260195fd` in `/tmp/opencode/partners-directory/initial-git-state.txt`.

No destructive Git operation was used. Baseline staged files were preserved:

- `D  docs/reports/DASHBOARD_UI_UX_REDESIGN_INTEGRATION_REPORT.md`
- `M  resources/js/Layouts/AppLayout.vue`
- `MM resources/js/Layouts/CmsDashboardLayout.vue`

The working tree already contained extensive concurrent work across CMS, admin, audit, email, Phase 5, public-site, localization, CSS, and test files. Edits were limited to the implementation files listed in Section 4 and to updates required by the removed workflow and new routes/permissions. `vendor/bin/pint --dirty --format agent` changed formatting only in one unrelated concurrent test file (`tests/Feature/PublicationsMediaUsersSettingsRedesignTest.php`: import ordering/cleanup); no concurrent content was discarded.

---

## 4. Change inventory

### Created

- `database/migrations/2026_11_27_000100_create_partner_directory_schema.php`
- `app/Models/DirectoryPartner.php`
- `app/Models/DirectoryPartnerTranslation.php`
- `app/Models/DirectoryPartnerCategory.php`
- `app/Models/DirectoryPartnerCategoryTranslation.php`
- `app/Services/PartnerDirectory.php`
- `app/Http/Controllers/Cms/DirectoryPartnerController.php`
- `app/Http/Controllers/Cms/DirectoryPartnerCategoryController.php`
- `app/Http/Requests/StoreDirectoryPartnerRequest.php`
- `app/Http/Requests/UpdateDirectoryPartnerRequest.php`
- `app/Http/Requests/StoreDirectoryPartnerCategoryRequest.php`
- `app/Http/Requests/UpdateDirectoryPartnerCategoryRequest.php`
- `database/factories/DirectoryPartnerFactory.php`
- `database/factories/DirectoryPartnerCategoryFactory.php`
- `resources/js/Pages/Public/Partnerships.vue` (rewritten)
- `resources/js/Pages/Cms/PartnerDirectory/Index.vue`
- `tests/Feature/PartnerDirectoryTest.php`
- `docs/reports/PARTNERS_DIRECTORY_TRANSFORMATION_REPORT.md`

### Modified for this task

- `routes/web.php`
- `app/Http/Controllers/Public/StaticPageController.php`
- `app/Http/Controllers/Public/InquiryController.php`
- `app/Actions/StoreInquiry.php`
- `app/Support/PermissionCatalog.php`
- `app/Console/Commands/SyncPermissionsCommand.php`
- `config/permissions.php`
- `config/admin_navigation.php`
- `lang/en/website.php`, `lang/ar/website.php`
- `lang/en/ui.php`, `lang/ar/ui.php`
- `resources/css/public-site.css` (scoped additive directory styles)
- `tests/Feature/PublicWebsiteTest.php`
- `tests/Feature/WebsiteCmsInquiryTest.php`
- `tests/Feature/AdminNavigationReorganizationTest.php`
- `tests/Feature/UnifiedRolesPermissionsTest.php`

No changes were made to `resources/css/pcdr-home.css`.

---

## 5. Audit findings and design decisions

The audit confirmed an existing bilingual public-page convention, Inertia/Vue pages, a shared Media Library, unified RBAC with a code-owned permission catalog, audit logging, and canonical public layouts/footer. Installed versions were confirmed before relying on APIs: Laravel `13.43.0`, Inertia Laravel `3.7.0`, Vue `^3.5.46`, `@inertiajs/vue3` `^3.11.0`, Vite `^8.0.0`, Tailwind `^4.0.0`.

Key decisions:

- Reuse the public `StaticPageController` for the directory page rather than introduce a new public base folder.
- Do **not** reuse operational `partners.*` permissions or the operational `partners` table; use dedicated `directory_partners.*` and directory-prefixed tables.
- Keep `/{locale}/partnerships` as an alias so existing navigation/footer links cannot break, while canonical metadata uses `/{locale}/partners`.
- Exclude draft/unpublished partners and partners assigned to inactive categories from the public payload.
- Include active categories with zero published partners so the filtered-empty state is reachable and counts match displayed groups.
- Generate blank slugs per record/locale and enforce unique `(locale, slug)` validation.
- Use the reference page only for information architecture inspiration; no names, logos, or visual design were copied.

---

## 6. Partnership application workflow removal

Removed:

- `POST {locale}/partnership/inquiry` and `public.inquiry.partnership`.
- `Public\InquiryController::partnership()`.
- The partnership branch in `StoreInquiry`.
- `PartnershipRequest` usage in the public workflow.
- Partnership-only branches/tabs/metric in `Cms\InquiryController` and CMS inquiry screens.
- Partnership application CTAs and fields from public `Involve` and `Contact` pages.
- Strings `partnership_form` and `cms_inquiries_partnership` from AR/EN language files.

Preserved:

- `GET/POST {locale}/contact/inquiry` and `GET/POST {locale}/volunteer/inquiry`.
- CMS inquiry route constraints narrowed to `['contact', 'volunteer']`.
- Historical partnership inquiry rows, model, migration, mail mappings, and email-template keys.

Post-removal targeted verification: `37 passed, 3301 assertions` in `PublicWebsiteTest.php` and `WebsiteCmsInquiryTest.php`.

---

## 7. Public directory information architecture

`GET /{locale}/partners` renders `Public/Partnerships.vue` inside the canonical public layout/footer. `/{locale}/partnerships` renders the same page as a compatibility alias.

The public page contains:

- Localized hero with introductory copy.
- Category navigation derived from active CMS categories, with per-category counts.
- Partner logo grid grouped by category.
- `object-fit: contain` for every logo to avoid cropped institutional marks.
- Optional partnership year, localized description, and external website link.
- Professional empty state when no published partners exist.
- Professional filtered-empty state when an active category has no published partners.
- Informational note clarifying that the directory is maintained by the CMS.
- Canonical footer inherited from `PublicLayout`.

Only published, non-deleted partners in active categories are exposed. Active empty categories remain visible with count `0`.

---

## 8. CMS partners directory implementation

`GET /{locale}/cms/partner-directory` renders `Cms/PartnerDirectory/Index.vue`, added to the **Content & Website** navigation group as `partner_directory` with `directory_partners.view`.

The UI provides:

- Partners and Categories tabs.
- Partner list with search, status filter, category filter, and pagination.
- Partner create/edit forms with AR/EN names, slugs, descriptions, category, year, website URL, ordering, and logo selection from the Media Library.
- Publish and unpublish actions with confirmation.
- Reorder controls and soft delete with confirmation.
- Category list with create/edit, bilingual name/description, active toggle, ordering, and safe delete.
- Inline validation errors and loading/disabled states across all mutations.
- Reuse of the existing design system, status badges, pagination, dialogs, and admin/CMS layout.

Category deletion is blocked while partners remain assigned; partner deletion is a soft delete. Both paths are audited.

---

## 9. Data model and additive migration

`2026_11_27_000100_create_partner_directory_schema.php` creates only new tables:

- `directory_partner_categories` (`slug`, `is_active`, `display_order`, soft deletes).
- `directory_partner_category_translations` (`locale`, `name`, optional `description`, unique category+locale).
- `directory_partners` (nullable category, nullable Media Library logo, website URL, year, status, `published_at`, `display_order`, soft deletes, indexes).
- `directory_partner_translations` (`locale`, generated/explicit `slug`, `name`, optional `description`, unique partner+locale and unique locale+slug).

The `down()` method drops only these four new tables in reverse order. No existing table, column, row, or historical record is altered or removed. The migration was applied locally by path only; the concurrent audit-archive migration remains pending and untouched.

Data-preservation check after migration: `partnership_inquiries=1`, `partners=0`, `directory_partner_categories=0`, `directory_partners=0`. No seed data or fictional partner was introduced.

---

## 10. Media Library integration

Partner logos reference `media.id` via `logo_media_id`. The CMS uses the existing Media Library picker; no separate upload path or new storage disk was added. Public rendering uses the Media Library URL and `object-fit: contain`, with an accessible fallback when no logo is selected. Deleting the referenced media preserves the partner record via `nullOnDelete`.

---

## 11. Roles, permissions, and audit logging

Six new supported permission keys were added to the unified catalog:

- `directory_partners.view`
- `directory_partners.create`
- `directory_partners.update`
- `directory_partners.delete`
- `directory_partners.publish`
- `directory_partners.unpublish`

`partners.view` was explicitly verified as **not** supported for this CMS module, preventing conflation with operational partner data. `SyncPermissionsCommand` and `PermissionCatalog` recognize the new keys. All CMS directory mutations use the existing audit service with `cms.%`-scoped activity, consistent with the prior dashboard redesign.

The supported-catalog count assertion was updated from `69` to `67` to reflect the current code-owned allowlist after concurrent catalog changes plus the six directory keys; the test now also asserts the six new keys and rejects `partners.view`.

---

## 12. Bilingual content, RTL/LTR, and design system

All directory labels, headings, empty states, form labels, buttons, validation messages, and navigation strings exist in `lang/en/website.php`, `lang/ar/website.php`, `lang/en/ui.php`, and `lang/ar/ui.php`. Partner and category names/descriptions are stored as per-locale translations. Public and CMS pages inherit existing locale/direction handling (`ar` → RTL, `en` → LTR) and reuse the established design-system components and PCDR color tokens. No new base directory or competing design system was introduced.

A literal `t()` key audit of both new Vue pages against `lang/en/website.php` passed.

---

## 13. SEO, metadata, and navigation

`StaticPageController::partners()` sets locale-aware SEO title and description, canonical URL `{$locale}/partners`, and Open Graph/Twitter overrides. Existing nav/footer links continue to resolve through the `/{locale}/partnerships` alias; canonical metadata always points to `/{locale}/partners`. The sitemap and public navigation tests remain green.

Route verification confirmed:

- `GET|HEAD {locale}/partners` → `public.partners`
- `GET|HEAD {locale}/partnerships` → `public.partnerships`
- 16 `cms.partner-directory.*` routes for index, category CRUD/status/reorder, partner CRUD/status/reorder, and create/edit screens.

---

## 14. Accessibility and professional UI

The public page uses semantic sections, hierarchical headings, descriptive link text, keyboard-focusable category controls, visible focus states from the shared design system, and sufficient color contrast through existing tokens. Logos use `object-fit: contain` and an accessible label/fallback. Empty states explain the current condition and avoid dead ends. RTL/LTR layout is inherited rather than reimplemented.

The target is WCAG 2.2 AA. Automated axe/Lighthouse/browser inspection was not available in this environment, so conformance is **designed for** but not independently certified.

---

## 15. Testing, build, and quality verification

| Check | Result |
| --- | --- |
| `php artisan test --compact tests/Feature/PartnerDirectoryTest.php` | 9 passed, 332 assertions |
| Targeted regression set (directory, public, inquiry, navigation, RBAC) | 65 passed, 5177 assertions |
| Post-workflow-removal public/inquiry regression | 37 passed, 3301 assertions |
| Full suite | 338 tests, 336 passed, 15259 assertions, 2 unrelated failures |
| `npm run build` | Succeeded; 808 modules transformed |
| `vendor/bin/pint --dirty --format agent` | Passed; one unrelated concurrent file style-fixed |
| `git diff --check` | Clean |
| `npm audit --omit=dev` | 0 vulnerabilities |
| `composer audit` | No security vulnerability advisories |
| Route list | Verified public aliases and 16 CMS directory routes |
| Migration status | Directory migration applied by path; historical data preserved |

The two full-suite failures are unrelated to this transformation and belong to concurrent media-module work:

- `CmsWorkflowTest::test_upload_rejects_disallowed_operating_system_extension` — expected `InvalidArgumentException` not thrown.
- `GlobalUxRefinementTest::test_media_pagination_exposes_real_range_metadata_and_preserves_filters` — expected exact `filters` array, actual array includes new `sort` and `visibility` keys.

They were reproduced in isolation and were not modified as part of this task.

---

## 16. Security, limitations, and final disposition

### Security and privacy

- Draft/unpublished partners and inactive categories are server-side filtered from the public payload.
- No operational partner fields are exposed through the directory API or UI.
- CMS mutations require dedicated `directory_partners.*` permissions and are audit logged.
- Website links are validated as URLs and rendered with safe external-link behavior.
- Soft deletes and category safe-delete prevent accidental public-data loss.
- CMS authority is explicitly limited to website content; no partnership confirmation or signing capability exists.

### Limitations

- No browser/visual verification, screenshots, axe run, or Lighthouse run was possible; accessibility and responsive behavior were implemented to spec but not instrument-certified.
- Full suite contains two pre-existing/concurrent media-related failures listed in Section 15.
- Vite reports non-blocking warnings for the optional `fontaine` package and a chunk larger than 500 kB.
- Dependency audits required a forced timeout because the initial registry calls hung; once completed, both audits passed cleanly.
- `/{locale}/partnerships` remains an alias rather than a redirect to avoid breaking existing links; canonical metadata uses `/{locale}/partners`.
- The historical `PartnershipRequest` class remains unused but was intentionally not deleted to preserve historical code/context.

### Final disposition

All requested functional, data, CMS, localization, SEO, permission, and preservation requirements are implemented and verified within the available tooling. Remaining items are environmental or unrelated concurrent-work limitations, not blockers to the Partners Directory itself.

**Final status: PASS WITH LIMITATIONS**
