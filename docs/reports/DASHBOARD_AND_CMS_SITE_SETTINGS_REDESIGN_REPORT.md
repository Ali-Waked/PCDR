# Dashboard and CMS Site Settings Redesign Report

Generated: 2026-11-24  
Repository: `/root/PCDR/pcdr`  
Branch: `main`  
Baseline HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
Baseline snapshot: `/tmp/opencode/dashboard-settings-redesign/initial-git-state.txt`  
Final classification: **PASS WITH LIMITATIONS**

---

## 1. Executive summary and final classification

The audit and redesign of `/ar/dashboard` and `/ar/cms/settings` is complete. The admin dashboard now presents a permission-filtered, database-backed operational overview of **website/CMS data only**. It does not render ERP, Phase 5, institutional-approval, or fabricated trend/activity data. The CMS site settings page now exposes a canonical 19-key public-website catalog, localized editing for Arabic and English, typed validation, image upload for the two existing image URL keys, cache flushing, audit logging, and a clear separation from Platform Settings at `/ar/admin/settings`.

All implementation remains uncommitted in the primary working tree. No branch, worktree, merge, commit, push, reset, clean, stash, or destructive Git restore was performed.

The final classification is **PASS WITH LIMITATIONS**:

- **PASS** because all targeted, regression, and full-suite tests pass; the production Vite build succeeds; PHP lint, Pint, `git diff --check`, Composer audit, and npm audit pass; routes and translated strings were verified; and scope boundaries are enforced in server-side data and validation.
- **WITH LIMITATIONS** because browser/visual verification (screenshots, axe, Lighthouse) was unavailable, the CMS and Platform Settings still share the legacy `settings.view` permission key, the Vite build emits non-blocking warnings, and one unrelated untracked file required a one-line import correction to unblock the build.

---

## 2. Scope, constraints, and Git safety

### In scope

- Full read-only audit of the existing `/ar/dashboard` and `/ar/cms/settings` implementations.
- Backend redesign: dashboard data service, controller wiring, settings controller, form requests, canonical catalog, localized routes.
- Frontend redesign: `resources/js/Pages/Dashboard.vue`, `resources/js/Pages/Cms/Settings/Index.vue`, appended design-system CSS.
- Arabic RTL and English LTR strings in `lang/ar/ui.php`, `lang/en/ui.php`, `lang/ar/website.php`, and `lang/en/website.php`.
- Focused feature tests plus regression and full-suite verification.
- Dependency, build, and security audits.
- This 19-section report.

### Out of scope / explicitly preserved

- No dependency manifest changes were made. `composer.json`, `composer.lock`, `package.json`, and `package-lock.json` were not edited by this redesign. (`package.json` and `package-lock.json` were already modified at baseline by concurrent work.)
- No database migration or schema change was introduced.
- No settings record, uploaded asset, or published-website integration was deleted.
- No favicon setting was invented because the schema has no favicon key.
- No prior email-design or admin-hint work was re-proposed or overwritten; the approved `AdminHintBox` text remains `t('admin_dashboard.overview_hint')`.

### Git safety

The following were **not** performed: branch creation, worktree creation, merge, commit, push, `reset --hard`, `clean`, `stash`, `git checkout .`, `git restore`, `git add .`, or `git add -A`.

Baseline staged files were preserved exactly:

- `D  docs/reports/DASHBOARD_UI_UX_REDESIGN_INTEGRATION_REPORT.md`
- `M  resources/js/Layouts/AppLayout.vue`
- `MM resources/js/Layouts/CmsDashboardLayout.vue`

The untracked protected files `.env.mysql.bak` and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not read or modified. `README.md` was not modified.

---

## 3. Baseline state and preservation of concurrent work

The baseline was captured at `2026-11-24T11:42:27Z` on `main` at `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`. The working tree already contained extensive concurrent uncommitted work across CMS, email, navigation, audit, Phase 5, public-site, localization, and test files.

Preservation measures:

- Only the files listed in Section 2 and the specific implementation files in Section 3.1 were edited.
- Concurrent staged state was checked before and after implementation and remained identical.
- Existing tests that encode prior behavior (`PlatformOwnerDashboardFoundationTest`, `WebsiteCmsScopeTest`, `AdminNavigationReorganizationTest`) were kept passing without weakening their assertions.
- The existing `DashboardController` intent was preserved while its implementation was rewired to the new service.
- The unrelated untracked file `resources/js/Pages/Public/Complaints.vue` was touched only to correct a broken CSS import path that blocked `npm run build`; all other content was left intact.

### 3.1 Files created or modified by this redesign

Backend and routes:

- `app/Services/AdminDashboardService.php` (new)
- `app/Support/SiteSettingsCatalog.php` (new)
- `app/Http/Requests/Cms/UpdateSiteSettingsRequest.php` (new)
- `app/Http/Requests/Cms/UploadSiteSettingImageRequest.php` (new)
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Cms/SettingController.php`
- `routes/web.php`

Frontend and styles:

- `resources/js/Pages/Dashboard.vue`
- `resources/js/Pages/Cms/Settings/Index.vue`
- `resources/css/dashboard.css` (append-only redesign section)
- `resources/js/Pages/Public/Complaints.vue` (one-line build-unblock import fix only)

Localization:

- `lang/ar/ui.php`
- `lang/en/ui.php`
- `lang/ar/website.php`
- `lang/en/website.php`

Tests:

- `tests/Feature/AdminDashboardRedesignTest.php` (new)
- `tests/Feature/CmsSiteSettingsRedesignTest.php` (new)

Report:

- `docs/reports/DASHBOARD_AND_CMS_SITE_SETTINGS_REDESIGN_REPORT.md` (this file)

---

## 4. Dependency and integration audit

Relevant installed versions were confirmed before relying on framework APIs:

| Package | Installed / declared version |
| --- | --- |
| `laravel/framework` | `v13.43.0` |
| `inertiajs/inertia-laravel` | `v3.7.0` |
| `vue` | `^3.5.46` |
| `@inertiajs/vue3` | `^3.11.0` |
| `vite` | `^8.0.0` |
| `@vitejs/plugin-vue` | `^6.0.11` |
| `@tailwindcss/vite` / `tailwindcss` | `^4.0.0` |
| `@floating-ui/dom` | `^1.8.0` |

Dependency conclusions:

- No new package was added and no existing package version was changed for this redesign.
- Existing components were reused: `MetricCard`, `EmptyState`, `UiIcon`, `AdminHintBox`, `CmsDashboardLayout`, and `SelectionControl`.
- Existing services were integrated rather than replaced: `RbacService`, `CmsAccess`, `AdminNavigationService`, `SiteSettings`, `AuditService`, and `UploadMedia`.
- Public website consumers of `SiteSettings` continue to read `website.settings.public.{locale}`; the cache is flushed after both settings updates and image uploads.
- `composer audit --format=plain` reported: `No security vulnerability advisories found.`
- `npm audit --omit=dev` reported: `found 0 vulnerabilities`.

---

## 5. Pre-redesign dashboard audit findings

The audit found the following problems in the legacy dashboard:

- The page mixed website/CMS data with platform identity counts, operational records, Phase 5 counters, and generic recent events.
- Legacy `statistics` and `latestEvent` props were the primary payload and encouraged UI that implied non-website operational scope.
- No single server-side service owned permission filtering for the overview; count logic was distributed across controllers.
- The dashboard did not clearly distinguish real database-backed CMS state from decorative or institutional narratives.
- Audit activity exposed actor identity in the data path, which was inappropriate for a website/CMS overview.
- The UI had no explicit empty states or permission-aware sections for content status and pending queues.

These findings drove the decision to add a dedicated `AdminDashboardService` and a new `overview` payload, while retaining legacy props only for compatibility.

---

## 6. Pre-redesign CMS site settings audit findings

The audit found the following problems in the legacy CMS settings page:

- The settings catalog was not centralized in one code-owned class; controller-local constants and request validation could drift.
- The UI rendered raw field groups and raw setting keys without localized group descriptions or consistent field types.
- No supported image upload flow existed for the two existing image URL settings (`organization.logo`, `seo.og_image`).
- Validation was insufficiently field-aware for email, URL, and locale values.
- Cache flushing and audit logging were not consistently tied to settings mutation.
- The boundary between public website settings and platform settings was visually weak, even though routes were distinct.
- There was no favicon field in the schema; inventing one would have created a setting with no consumer.

These findings drove the canonical `SiteSettingsCatalog`, dedicated form requests, typed localized UI, upload pipeline, and explicit boundary notes.

---

## 7. Target information architecture and scope boundaries

### Dashboard scope

`/ar/dashboard` and `/en/dashboard` now render only:

- Localized greeting and description.
- Real database counts for published news, active programs, ongoing projects, publications, public media, new inquiries, and draft content.
- Per-content status rows (total, published, draft; enabled/disabled; public/restricted).
- Pending queues (new inquiries, draft content).
- CMS-only audit activity (`cms.%` actions) when `audit_logs.view` is present.
- Permission-filtered quick actions derived from `adminNavigation`.
- Public website settings readiness when `settings.view` is present.

Explicitly excluded from the rendered dashboard:

- `overview.access` users/roles/permissions metrics.
- ERP, inventory, operational record, and Phase 5 counters.
- Institutional approval claims or authority indicators.
- Fabricated trends, fake activity feeds, or unverified percentages.
- Actor names, emails, and inquiry submitter identities.

### Settings scope

CMS Site Settings manages only the public institutional website:

- Organization identity.
- Default language.
- SEO metadata.
- Footer text.
- Contact details.
- Social links.

Platform Settings remains a separate surface at `/{locale}/admin/settings` with its own controller and route group. The redesigned UI includes an explicit protected-settings note.

---

## 8. Canonical public settings catalog and data model

`app/Support/SiteSettingsCatalog.php` is now the single source of truth for the **19** public settings:

| Group | Keys | Count |
| --- | --- | --- |
| `organization` | `organization.name`, `organization.short_name`, `organization.logo` | 3 |
| `website` | `website.default_language` | 1 |
| `seo` | `seo.site_title`, `seo.default_description`, `seo.og_image` | 3 |
| `footer` | `footer.copyright`, `footer.legal_note` | 2 |
| `contact` | `contact.email`, `contact.complaints_email`, `contact.phone`, `contact.address`, `contact.hours` | 5 |
| `social` | `social.facebook`, `social.x`, `social.instagram`, `social.linkedin`, `social.youtube` | 5 |
| **Total** |  | **19** |

Catalog API:

- `all()` — ordered key/metadata map.
- `keys()` — allowed key list used by validation.
- `groupOrder()` — stable group ordering.
- `imageKeys()` — `organization.logo`, `seo.og_image`.
- `find()` — metadata lookup used by controller and requests.

Data preservation:

- Updates use `firstOrNew(['key' => $key])`; existing records are updated, not replaced wholesale.
- Localized values use `translations()->updateOrCreate(...)`; existing translations are preserved unless explicitly submitted.
- Empty strings are normalized to `null`; no record or translation row is deleted by this flow.
- No setting outside the catalog can be read, rendered, updated, or uploaded through the CMS settings surface.

---

## 9. Dashboard service and permission-filtered data contract

`AdminDashboardService::overview(User $user, string $locale)` returns:

- `permissions` — booleans resolved server-side through `CmsAccess` and `RbacService`.
- `greeting`, `description`, `generated_at`, `timezone`.
- `kpis` — list of `{key, value, icon, href}`.
- `content` — list of `{key, href, metrics, updated_at}`.
- `pending` — list of `{key, value, href, icon}`.
- `activity` — CMS-only audit events `{id, action, label, created_at}`.
- `quick_actions` — permission-filtered and localized navigation entries.
- `website` — public settings readiness, or `null` when settings access is absent.

Permission behavior:

- Content and inquiry counters appear only when the corresponding CMS permission is granted.
- Audit activity appears only with `audit_logs.view` and is filtered to `action LIKE 'cms.%'`.
- Activity payloads contain no `actor`, `name`, or `email` fields.
- Website readiness appears only with `settings.view`.
- A user with only unrelated permissions receives empty CMS sections and no website readiness block.

Compatibility:

- `statistics` and `latestEvent` remain in the Inertia payload for existing consumers/tests.
- `statistics.operational_records`, `statistics.phase5_tasks`, and `statistics.phase5_workflows` are fixed at `0`.
- The redesigned Vue page does not read or render those compatibility props.
- `overview.access` was removed from the backend payload and the UI.

---

## 10. CMS settings update, validation, upload, cache, and audit pipeline

### Update pipeline

`PUT /{locale}/cms/settings` uses `UpdateSiteSettingsRequest`:

- `settings` must be an array with at most 19 entries.
- Each `key` is required, distinct, and must be in the canonical catalog.
- Unknown keys such as `app.key` or `mail.*` are rejected.
- Values are capped at 2000 characters.
- Email settings use `email:rfc`.
- URL settings allow only `http://`, `https://`, or a root-relative path beginning with `/` and not `//`; `javascript:` and similar schemes are rejected.
- `website.default_language` must be one of the configured website locales.
- Localized settings validate each submitted AR/EN translation independently.

The controller wraps persistence in `DB::transaction`, records before/after values, calls `SiteSettings::flush()`, and writes a `cms.settings.update` audit record with the submitted key list.

### Image upload pipeline

`POST /{locale}/cms/settings/image` uses `UploadSiteSettingImageRequest` and the existing `UploadMedia` action:

- Only `organization.logo` and `seo.og_image` are accepted.
- File type is limited to configured image MIME types.
- File size is limited by `website.uploads.max_file_size_kb`.
- Uploaded media is stored as public, non-restricted media.
- The setting value is updated to the media URL.
- `SiteSettings::flush()` runs after upload.
- A `cms.settings.image_uploaded` audit record is written.
- Old uploaded files are never deleted.

### Cache and audit

- Public cache key: `website.settings.public.{locale}`.
- Touched paths: CMS settings update and CMS settings image upload.
- Audit actions introduced: `cms.settings.update`, `cms.settings.image_uploaded`.
- Sensitive data is not included in dashboard activity; audit payloads use the existing audit service/redaction path.

---

## 11. Routes, authorization, and governance boundaries

Verified localized routes:

```text
GET|HEAD {locale}/cms/settings        cms.settings.index        Cms\SettingController@index
PUT      {locale}/cms/settings        cms.settings.update       Cms\SettingController@update
POST     {locale}/cms/settings/image  cms.settings.upload-image Cms\SettingController@uploadImage
GET|HEAD {locale}/admin/settings      admin.settings.index      Admin\SettingController@index
PUT      {locale}/admin/settings      admin.settings.update     Admin\SettingController@update
```

Authorization:

- CMS settings routes use `cms:settings.view` / `cms:settings.update` middleware.
- CMS form requests independently check `CmsAccess::allows(..., 'settings.update')`.
- Unauthorized read, update, and upload requests return `403` in tests.
- Dashboard data is filtered server-side; the Vue shell does not decide which capability is allowed.
- Institutional approval permissions remain denied through the CMS seam (`CmsAccess` delegates authority checks to `RbacService::isAuthorityPermission`).

Known boundary limitation:

- CMS and Platform Settings still share the legacy `settings.view` permission key. Route groups, controllers, payloads, UI, and catalogs are separate, but the permission key itself was intentionally not renamed because doing so would be a breaking authorization migration outside this redesign.

---

## 12. Frontend dashboard redesign

`resources/js/Pages/Dashboard.vue` was rebuilt around `overview`:

- Single root element with `defineOptions({ layout: AppLayout })`.
- Kept required `statistics` and optional `latestEvent` props for compatibility; neither is rendered.
- Added required `overview` prop.
- Kept `AdminHintBox` with the exact approved text source `t('admin_dashboard.overview_hint')`.
- Uses `useLocale()` for `t`, `locale`, `localePath`, and `direction`.
- Uses `Intl.NumberFormat` with `ar-EG` / `en-GB`.
- Uses defensive `Date` parsing with timezone formatting and graceful `—` fallback.
- Semantic `<section>` elements with `aria-labelledby`.
- Generated time marked with `role="status"`.
- KPI links use `MetricCard`; empty states use `EmptyState`; icons use `UiIcon`.
- Content rows use `<dl>/<dt>/<dd>` for metric pairs.
- Quick actions use `Link` with localized paths.
- CMS activity is shown only when the payload contains events.
- Website readiness is shown only when the payload is present.
- No `overview.access`, users, roles, permissions, ERP, Phase 5, or institutional-approval section is rendered.

---

## 13. CMS settings UI redesign

`resources/js/Pages/Cms/Settings/Index.vue` was redesigned with:

- `CmsDashboardLayout`, `useForm`, `useI18n`, `SelectionControl`, and `UiIcon`.
- Localized group labels and descriptions from the backend `groups` prop.
- Explicit public-boundary and protected-settings callouts.
- A read-only state with `cms_settings_no_access` when `canUpdate === false`.
- Typed controls:
  - `text` / `url` → text input; URL inputs are `dir="ltr"`.
  - `email` → email input with `dir="ltr"`.
  - `textarea` → textarea with `maxlength="2000"`.
  - `locale` → non-searchable `SelectionControl`.
- One visible input per locale for localized settings, labeled in Arabic and English.
- Client-side URL validation matching server rules, plus server error display.
- Per-field `aria-invalid` and `role="alert"` error rendering.
- Separate image-upload form for `organization.logo` and `seo.og_image`, including current value, image preview, file picker, upload button, and clear-value action.
- Save and upload buttons disabled while processing.
- No favicon field and no delete-old-file action.

---

## 14. Design system, visual language, and component reuse

The redesign uses the existing PCDR design system rather than introducing a new one:

- Existing tokens such as `--pc-teal`, `--pc-sand`, `--pc-text`, `--pc-muted`, `--pc-border`, `--pc-surface`, and `--pc-radius` are used by the appended CSS.
- Existing card, metric, badge, button, field, callout, timeline, and list classes are reused.
- `resources/css/dashboard.css` received an append-only section marked `/* Redesigned admin dashboard and CMS site settings */`.
- Existing `overflow-x: hidden`, focus-visible rules, and earlier responsive rules were not removed.
- No new base folder, UI library, or icon set was introduced.
- The page shells already apply `pcdr-enter` motion with global reduced-motion handling; the appended CSS adds local reduced-motion overrides for the new hover transforms/transitions.

---

## 15. Arabic RTL, English LTR, and localization

Dashboard strings were added under `ui.admin_dashboard.*` in both `lang/en/ui.php` and `lang/ar/ui.php`, including greetings, section titles/subtitles, KPI/content/pending/access labels, website readiness labels, activity labels, timezone formatting, empty states, and CMS action labels.

CMS settings strings were added under `website.ui.*` and `website.cms.*` in both `lang/en/website.php` and `lang/ar/website.php`, including:

- Intro, group/list titles, locale labels, upload/current value/preview controls, URL/image hints, validation messages, flash message, and no-access note.
- Descriptions for all 19 settings.
- Labels and descriptions for all six groups.

Direction behavior:

- Dashboard root uses `:dir="direction"` from `useLocale()`.
- Document direction remains managed by the shared locale middleware and `app.js`.
- URL, email, setting value, and raw audit action text use `dir="ltr"` where appropriate.
- Localized routes remain `/{locale}/dashboard` and `/{locale}/cms/settings`.

A direct key audit confirmed all required dashboard and settings keys exist in both locales.

---

## 16. Responsive behavior, keyboard accessibility, focus, and reduced motion

Responsive rules were appended for:

- `max-width: 1100px` — KPI grid and settings form grid become single/adaptive columns.
- `max-width: 820px` — overview panels and content rows collapse to one column.
- `max-width: 640px` — KPI grid becomes two columns; image actions stack; group links wrap vertically.
- `max-width: 560px` — KPI grid becomes one column; inputs use full width and 16px font size to avoid mobile zoom.

Accessibility measures in code:

- Semantic headings and `aria-labelledby` section relationships.
- `role="status"` for generated timestamp and no-access note.
- `role="note"` for informational callouts.
- `role="alert"` for field errors.
- `aria-invalid` on invalid inputs.
- `aria-busy` on processing buttons.
- Existing focus-visible styling preserved; all interactive elements remain native links, buttons, inputs, or the existing accessible `SelectionControl`.
- Reduced-motion rules disable new transitions and hover transforms.

No browser-driven accessibility audit was performed; see Section 19.

---

## 17. Tests added and regression coverage

New tests:

### `tests/Feature/AdminDashboardRedesignTest.php`

- Verifies real website data appears in KPIs, content rows, pending queues, and website readiness.
- Verifies draft/public/restricted counts are derived from actual records.
- Verifies activity is limited to `cms.%` actions and contains no actor/email/name fields.
- Verifies compatibility operational/Phase 5 counters are zero.
- Verifies unauthorized users see empty CMS sections and no `overview.access`.
- Verifies empty dashboards contain only zero-based values.
- Verifies AR and EN greetings/descriptions are localized.

### `tests/Feature/CmsSiteSettingsRedesignTest.php`

- Verifies exactly 19 canonical settings, ordered groups, localized labels, `canUpdate`, and upload keys.
- Verifies route names for CMS index/update/upload and admin settings index.
- Verifies update persists values/translations, flushes public cache, and writes audit data.
- Verifies invalid emails, unknown keys, unsafe URLs, and invalid locales are rejected.
- Verifies unauthorized read/update/upload returns `403`.
- Verifies image upload updates the setting/media record, preserves old assets, and writes audit data.
- Verifies unsupported keys and non-image files are rejected.

Existing regressions kept passing:

- `PlatformOwnerDashboardFoundationTest` — compatibility statistics/latest-event contract.
- `WebsiteCmsScopeTest` — 19-setting public catalog and CMS scope.
- `AdminNavigationReorganizationTest` — navigation/permission boundaries.
- Full suite — all concurrent user work.

---

## 18. Verification commands and exact results

| Verification | Command | Exact result |
| --- | --- | --- |
| Focused redesign tests | `php artisan test --compact tests/Feature/AdminDashboardRedesignTest.php tests/Feature/CmsSiteSettingsRedesignTest.php` | `tests:10, passed:10, assertions:148, duration_ms:9709` |
| Regression tests | `php artisan test --compact tests/Feature/PlatformOwnerDashboardFoundationTest.php tests/Feature/WebsiteCmsScopeTest.php tests/Feature/AdminNavigationReorganizationTest.php` | `tests:30, passed:30, assertions:1234, duration_ms:19796` |
| Full suite | `php artisan test --compact` | `tests:324, passed:324, assertions:8967, duration_ms:323520` |
| PHP lint | `php -l` across 13 changed PHP files | `PHP lint OK: 13 files` |
| Pint | `vendor/bin/pint <13 redesign PHP files> --format agent` | `{"tool":"pint","result":"passed"}` |
| Production build | `npm run build` | `✓ 807 modules transformed`, `✓ built in 24.39s`, exit success |
| PHP dependency audit | `composer audit --format=plain` | `No security vulnerability advisories found.` |
| Production npm audit | `npm audit --omit=dev` | `found 0 vulnerabilities` |
| Route verification | `php artisan route:list --name=cms.settings --except-vendor` / `--name=admin.settings` | 3 CMS settings routes and 2 admin settings routes as listed in Section 11 |
| Localization key audit | Inline PHP key check on four lang files | `en: OK`, `ar: OK` for dashboard and settings keys |
| Whitespace check | `git diff --check` | No output, clean |

Build notes:

- The first `npm run build` attempt failed with `Could not resolve '../../css/complaints-box.css' in resources/js/Pages/Public/Complaints.vue`.
- The actual file is `resources/css/complaints-box.css`; the untracked page's import was corrected to `../../../css/complaints-box.css`.
- The final build succeeded. It still emits two non-blocking warnings: optional `fontaine` package not installed for optimized font fallbacks, and a JS chunk larger than 500 kB.

Pint note:

- Pint was run on all PHP files touched by this redesign. A repository-wide `--dirty` run was intentionally avoided so unrelated concurrent uncommitted work would not be reformatted.

---

## 19. Limitations, preserved work, and final disposition

### Limitations

1. **No browser or visual verification was available.** No screenshots, axe scans, Lighthouse runs, or manual responsive checks were performed. AR/EN, RTL/LTR, focus states, and responsive behavior were verified at code/CSS level only.
2. **Legacy shared permission key.** CMS Site Settings and Platform Settings still use the legacy `settings.view` permission key. Route, controller, payload, catalog, and UI separation are enforced, but renaming the key would require a separate authorization migration.
3. **Build warnings.** The production build passes but reports the optional `fontaine` package and a >500 kB chunk. These warnings are pre-existing/non-blocking and were not changed because dependency manifests are out of scope.
4. **No production asset verification.** Uploaded image URLs were verified with a fake public disk in tests, not against production storage or a public domain.
5. **Old uploads are preserved by design.** No cleanup/pruning command was introduced; old files remain after an image setting is replaced.
6. **Compatibility props remain.** `statistics` and `latestEvent` are still present in the Inertia payload for existing consumers, but the redesigned UI does not render them. Operational/Phase 5 counters are fixed at zero in the compatibility payload.
7. **One unrelated build-unblock edit.** `resources/js/Pages/Public/Complaints.vue` was an untracked concurrent file. Only its broken CSS import path was corrected; no other content was changed.

### Preserved work confirmed

- All prior email redesign work and email report status remain untouched; the prior report remains `PASS WITH LIMITATIONS` for its own production HTTPS/Gmail/Outlook reasons.
- Approved admin-hint wording remains unchanged.
- Initial staged files are unchanged.
- Concurrent unstaged and untracked work remains in place.
- Protected files were not read or modified; README was not modified.
- No commit, push, branch, worktree, merge, reset, clean, stash, or destructive Git command was used.

### Final disposition

**PASS WITH LIMITATIONS.**

The redesign is functionally complete, permission-filtered, database-backed, validated, audited, localized, responsive at code level, and fully green across 324 tests. The limitations above are transparency constraints and pre-existing architectural boundaries, not blockers to the requested dashboard and CMS site settings redesign.
