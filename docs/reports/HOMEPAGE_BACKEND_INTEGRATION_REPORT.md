# Homepage Backend Integration Report

Date: 2026-11-24

Branch: `main`  
Baseline commit: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`

## A. Objective and scope

This work aligned the PCDR homepage backend, data model, Inertia props, seeders, and CMS admin integration with the actual rendered homepage frontend.

Scope included:

- Auditing the frontend as the source of truth and mapping every visible homepage block to an existing or additive backend source.
- Reusing the existing homepage-section, navigation, strategic-pillar, program, article, publication, settings, and media structures instead of creating a parallel CMS.
- Adding one additive inline-content extension for homepage items rather than creating a table per section.
- Rewiring `Public/Home.vue` to consume localized backend props while preserving its fixed DOM order, approved styling, RTL/LTR behavior, link map, and shared public shell.
- Making seeders idempotent and non-destructive toward administrator-managed values.
- Keeping strategic-plan figures explicitly labeled as 2027 planning targets, never as achieved results.
- Extending the CMS homepage editor so administrators can manage the new inline content.
- Adding feature coverage and running the project’s PHPUnit, formatter, build, and diff checks.

Out of scope:

- No new CMS Pages module was introduced.
- No institutional facts, contact details, partners, donors, legal text, or achievements were invented.
- No browser, visual, screenshot, Lighthouse, or Playwright verification was performed.
- No commit, push, branch switch, worktree, stash, reset, clean, `checkout .`, or `restore` was performed.

## B. Baseline and worktree preservation

Work was performed only in `/root/PCDR/pcdr` on the existing `main` branch. The original worktree status was saved to `/tmp/opencode/pcdr-homepage-baseline.txt` before changes.

Preservation results:

- All pre-existing and concurrent uncommitted changes remain present in `git status --short`.
- No `git add`, `git add .`, or `git add -A` was used.
- No destructive Git command was used.
- `README.md` was not modified.
- `app/Http/Controllers/Admin/DashboardController.php` was not modified.
- `.env.mysql.bak` was not opened, read, modified, staged, or committed.
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` was not opened, read, modified, staged, or committed.
- `vendor/bin/pint --dirty --format agent` touched only `database/seeders/HomepageContentSeeder.php` from this task’s files.
- No `migrate:fresh`, `db:wipe`, or other destructive database command was used. The additive migration was applied with `php artisan migrate --no-interaction`.

The final `git status --short` contains additional entries belonging to this task alongside the unchanged baseline/concurrent entries. This was reviewed manually against the saved baseline.

## C. Frontend source-of-truth audit

The rendered homepage order was confirmed as:

1. Hero
2. About / vision / mission
3. Strategic areas
4. Humanitarian approach / framework
5. Programs
6. Strategic targets
7. Community participation
8. Partnerships
9. Knowledge / news / publications
10. CTA / contact
11. Shared footer

The audit found that the previous homepage was largely driven by a large hardcoded bilingual object. Backend sections existed, but the frontend consumed only selected eyebrow/title values and the CTA body. CTA labels/links, section bodies, and section items were serialized but unused. Strategic target cards were hardcoded, and mission was not passed as a prop. No statistics/KPI backend module existed.

The rewrite in `resources/js/Pages/Public/Home.vue` keeps the approved fixed template order and shared shell while resolving content by section key. `is_enabled` and `display_order` are deliberately not used to hide or reorder public sections; the public controller supplies all sections so the fixed DOM always has content. CMS preview remains enabled-only.

Preserved frontend contracts include:

- Root class `pcdr-home public-shell`.
- `<SiteHeader :transparent="true" />` and `<SiteFooter />`.
- `class="sec cta"` on the final homepage section.
- A single in-page `href="#main"` skip link.
- The existing anchor-to-localized-path map, including `#about`, `#areas`, `#programs`, `#targets`, `#knowledge`, `#contact`, `#partners`, `#footer`, and `#community`.
- The canonical three-group footer navigation behavior and seeded primary header navigation.
- Byte-identical `resources/css/pcdr-home.css`, `resources/views/app.blade.php`, and `resources/js/Components/LanguageSwitcher.vue`.
- The undefined `navOpen` Escape-handler bug was removed; `SiteHeader.vue` continues to own Escape handling.

## D. Data model and migration

Added one additive migration:

`database/migrations/2026_11_24_051843_expand_homepage_section_items_for_inline_content.php`

Changes:

- Added nullable `item_key` to `homepage_section_items`.
- Added `is_published`, defaulting to true.
- Added nullable `numeric_value` for planning-target figures.
- Added a unique constraint on `(homepage_section_id, item_key)`.
- Created `homepage_section_item_translations` with `label`, `title`, `body`, `display_value`, and `url`, unique per `(homepage_section_item_id, locale)`.

Updated/created models:

- `app/Models/HomepageSectionItem.php`: localized fields, published scope, inline detection, casts, and existing published-module resolution.
- `app/Models/HomepageSectionItemTranslation.php`: fillable localized inline fields.
- Existing `HomepageSection` relationships were reused unchanged.

This single inline extension supports hero buttons, vision/mission cards, framework steps, program tiles, target figures, community roles, partner categories, and CTA buttons without creating a new table per homepage section.

## E. Backend services, controllers, and Inertia props

### `HomepageService`

Updated to:

- Load section and item translations.
- Serialize all section translation fields: eyebrow, title, body, CTA label, and CTA link.
- Expose `enabled` and `order`.
- Filter inline and resolved items by `is_published`.
- Localize inline item fields and resolved module titles/labels/bodies/URLs.
- Provide `sections($locale, $onlyEnabled = true)` and `section($key, $locale)`.

Public home calls `sections($locale, false)` so every fixed section remains populated. CMS preview calls the default enabled-only form.

### `Public/HomeController`

Added:

- `mission` from `config('website.mission.<locale>')`.
- All homepage sections via `HomepageService`.
- Existing `vision`, pillars, framework, articles, programs, publications, and SEO `meta` props remain intact.

### `Public/Home.vue`

Rewritten to consume backend props while retaining the fixed DOM structure. Hero copy, about vision/mission, section eyebrows/titles/bodies, framework steps, program tiles, target cards, community roles, partner categories, knowledge empty-state copy, and CTA buttons now resolve from seeded homepage content. Published pillars, programs, articles, and publications still come through the existing `ContentQuery` boundary.

SEO `<Head>` now consumes `meta.canonical`, locale alternates, `x-default`, robots, OG, and Twitter fields with safe fallbacks. The intro splash logo uses `site.logo` with the approved static asset fallback. Header and footer logos remain hardcoded static paths by design.

## F. Seeders, settings, and strategic provenance

### `SiteSettingsSeeder`

New seeder initializing only the 18 CMS-managed public settings from `Cms\SettingController`:

- Organization name, short name, and logo.
- Default language.
- SEO title, description, and OG image.
- Footer copyright and legal note.
- Contact email, phone, address, and hours.
- Five social URLs.

Safety rules:

- Values are applied only when missing or empty.
- Existing administrator values are never overwritten.
- Arabic organization name uses the confirmed `جمعية العمل التشاركي في الأزمات والكوارث الخيرية – قطاع غزة`.
- English organization name comes from `config('website.name.en')`.
- Contact details, social URLs, OG image, copyright, and legal note remain empty unless already populated by an administrator.
- No passwords, API keys, tokens, environment values, or invented contact details are seeded.

### `HomepageContentSeeder`

New seeder for the ten rendered homepage sections and 32 inline items. It:

- Uses `firstOrCreate` for sections and inline items.
- Fills only empty translation fields.
- Replaces only exact known legacy defaults with current bilingual homepage copy.
- Preserves administrator-customized titles, bodies, labels, and display values on re-seed.
- Sources strategic content from `docs/strategy/STRATEGIC_OPERATIONAL_PLAN_2027_2029.md`.

Strategic figures remain explicitly planning targets. The Arabic section is labeled `مستهدفات الخطة الاستراتيجية 2027`, the English section `Strategic Plan Targets 2027`, and the body copy states that the figures are targets pursued within available resources and capacities. No actual-results or achievement claims were added.

### `WebsiteSeeder`

Reworked to:

- Seed only navigation and strategic pillars.
- Preserve administrator-customized navigation labels and pillar values.
- Populate empty fields only.
- Stop seeding homepage sections, which are now owned by `HomepageContentSeeder`.

### `DatabaseSeeder`

Now calls `SiteSettingsSeeder`, `WebsiteSeeder`, and `HomepageContentSeeder` before the existing role and email-template seeders. The local/testing identity behavior was left unchanged.

## G. CMS admin integration

`app/Http/Controllers/Cms/HomepageController.php` was extended to:

- Load item translations for the admin form.
- Validate inline item type, key, published state, numeric value, and bilingual fields.
- Require an item ID for resolved module item types.
- Reject duplicate item keys within a section.
- Save section translations, enabled flags, display order, and inline item translations in one transaction.
- Delete only items removed from the submitted section payload.

`resources/js/Pages/Cms/Homepage/Index.vue` was extended with bilingual inline-item editors for label, title, body, display value, and URL, plus item type, key, numeric value, publication state, add, and remove controls.

No Pages management was reintroduced.

## H. Verification

Commands and results:

| Command | Result |
| --- | --- |
| `php artisan migrate --no-interaction` | Additive migration applied successfully. |
| `php artisan db:seed --class=SiteSettingsSeeder --no-interaction` | Passed. |
| `php artisan db:seed --class=HomepageContentSeeder --no-interaction` | Passed. |
| `php artisan test --compact tests/Feature/HomepageBackendIntegrationTest.php` | 10 passed, 172 assertions. |
| `php artisan test --compact tests/Feature/HomepageBackendIntegrationTest.php tests/Feature/PublicWebsiteTest.php tests/Feature/TranslationKeysTest.php tests/Feature/WebsiteCmsScopeTest.php` | 48 passed, 2,834 assertions. |
| `php artisan test --compact` | 250 passed, 6,186 assertions. |
| `vendor/bin/pint --dirty --format agent` | Fixed only `database/seeders/HomepageContentSeeder.php`. |
| Post-format targeted PHPUnit rerun | 11 passed, 173 assertions. |
| `npm run build` | Passed; Vite 8.3.8, 786 modules transformed. |
| `git diff --check` | Clean. |
| `git status --short` vs saved baseline | Reviewed; baseline/concurrent changes preserved. |

New feature coverage in `tests/Feature/HomepageBackendIntegrationTest.php` includes:

- Seeded section order, item count, and strategic target values.
- Idempotency and administrator-customization preservation across all relevant seeders.
- Replacement of exact known legacy defaults.
- Localized homepage props, mission, target copy, and SEO.
- Disabled sections still present publicly but hidden from CMS preview.
- Unpublished inline items excluded from public content.
- Explicit target labeling and absence of achievement claims.
- Eighteen CMS settings only, with no secrets or invented contact/social/legal values.
- Fixed DOM order, link map, shared shell, and removal of the stale `navOpen` handler.
- CMS inline-item review and update through authenticated Inertia routes.

The one `TranslationKeysTest` issue found during verification was caused by the static scanner interpreting model field names such as `display_value` and `url` as UI translation keys. `HomepageService` was refactored to read model translation fields through a variable-based helper. No meaningless translation keys were added.

Build warnings were non-blocking: the optional `fontaine` package is not installed, and the main JS chunk exceeds Vite’s 500 kB advisory threshold.

## I. Limitations and residual risks

- No browser, visual, screenshot, Playwright, Lighthouse, or accessibility run was performed. Verification was source-contract, automated test, and production-build based only.
- Contact, social, legal, copyright, and OG-image settings are intentionally empty because no verified institutional values were supplied. Administrators must populate them before public use.
- Header and footer logos remain static asset paths; only the homepage intro splash consumes `site.logo`.
- Strategic figures are planning targets only. There is no KPI actuals/baseline/verification module, and none was invented.
- Public homepage order is intentionally fixed by the template. CMS `is_enabled` and `display_order` do not hide or reorder public sections; they affect CMS preview ordering/state only.
- Saving a CMS homepage section submits its full item list; omitted items are deleted by design. The provided editor always submits the complete list.
- The shared worktree contains extensive unrelated concurrent changes. The full PHPUnit suite currently passes, but these changes remain uncommitted and outside this task’s scope.
- No deployment or migration against a production database was performed.

## J. Change inventory and final status

Files created:

- `database/migrations/2026_11_24_051843_expand_homepage_section_items_for_inline_content.php`
- `app/Models/HomepageSectionItemTranslation.php`
- `database/seeders/SiteSettingsSeeder.php`
- `database/seeders/HomepageContentSeeder.php`
- `tests/Feature/HomepageBackendIntegrationTest.php`
- `docs/reports/HOMEPAGE_BACKEND_INTEGRATION_REPORT.md`

Files modified by this task:

- `app/Models/HomepageSectionItem.php`
- `app/Services/HomepageService.php`
- `app/Http/Controllers/Public/HomeController.php`
- `app/Http/Controllers/Cms/HomepageController.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/WebsiteSeeder.php`
- `resources/js/Pages/Public/Home.vue`
- `resources/js/Pages/Cms/Homepage/Index.vue`

Final status: **PASS WITH LIMITATIONS**
