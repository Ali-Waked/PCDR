# CMS Scope Consolidation — Integration Report

**Date:** 2026-11-23
**Repository:** `/root/PCDR/pcdr`
**Branch under integration:** `master`
**Task type:** integration-only verification and reporting; no feature work.

---

## 1. Source branch and commit

No separately named CMS consolidation branch exists in this repository. The verified implementation is the commit already present on `master`:

- **Source commit:** `29d950a83a5641e8edec52408761ba4c921295ac`
- **Subject:** `feat(cms): deliver scoped bilingual website CMS`
- **Parent / baseline:** `d7c25e754da3c2337e752433ffe2d2ef78d4d00d`
- **Branches containing the commit:** `master` only.

The five phase/legacy worktrees were inspected and are clean at their existing commits. None of them contains CMS consolidation work, and none was modified. The related commit was reviewed in full against its merge base (`d7c25e7`); it was already committed before this integration task began.

## 2. Master baseline

- **Baseline before consolidation:** `d7c25e7` — `docs(phase-6): document final integration checkpoint`.
- **Current master before this report:** `29d950a`.
- **Staged and unstaged changes at task start:** none.
- **Untracked files at task start:** only the two protected artifacts, which were not opened or touched.

## 3. Merge commit

No merge commit was created. The exact verified consolidation commit is already the tip of `master`; a merge would be a no-op and would artificially rewrite review history. No squash, reset, rebase, or history rewrite was performed. The only new commit in this task will contain this integration report.

## 4. Files changed

The reviewed commit changes 58 files against the baseline: 4,065 insertions and 280 deletions.

Classification:

| Class | Representative files |
|---|---|
| A. In-scope CMS implementation | `app/Http/Controllers/Cms/*`, `app/Services/InquiryMailService.php`, `app/Services/TemplateRenderer.php`, `config/email.php`, `database/migrations/*website_engagement*`, `database/seeders/*Cms*`, `resources/js/Pages/Cms/*` |
| B. Dashboard navigation and visibility | `config/admin_navigation.php`, `config/admin_navigation_hidden.php`, `config/dashboard_features.php`, `app/Services/AdminNavigationService.php`, `app/Http/Middleware/EnsureDashboardFeatureAvailable.php`, `resources/js/Pages/Dashboard.vue` |
| C. Authorization and permissions | `config/permissions.php`, `config/cms.php`, `database/seeders/CmsRolesSeeder.php`, `app/Services/CmsAccess.php` consumers |
| D. Tests | `tests/Feature/WebsiteCmsScopeTest.php`, `WebsiteCmsInquiryTest.php`, `WebsiteCmsEmailTemplateTest.php`, plus two updated regression tests |
| E. Documentation | four `docs/architecture/WEBSITE_CMS_*.md` files |
| F. Unrelated or unsafe changes | none found |

No files were deleted. No governance, strategy, ADR, open-question, or protected artifacts appear in the commit. The two modified pre-existing tests reflect intentional scope changes (navigation reduced to three groups; inquiry status aliases accepted) and were not weakened to hide failures.

## 5. CMS features integrated

Verified present and exercised by tests:

- Website pages, homepage content, news/articles, programs, projects, strategic pillars, publications/reports.
- Media library, navigation, website settings, localized public identity and SEO metadata.
- Contact, volunteer and partnership inquiries with persistence, reference numbers, filtering, assignment, status handling, internal notes and email replies.
- Bilingual email templates with safe variable interpolation and preview.
- Users, roles and permissions through the existing IAM/RBAC contract.
- Localization keys for Arabic and English, RTL/LTR handling, and configurable default public language.
- Server-rendered draft preview for authorized editors.

## 6. Hidden modules preservation verification

- `config/dashboard_features.php` marks `operations` and `phase5` as `hidden`; `overview`, `content`, `engagement`, and `access` are `enabled`.
- Original operational navigation definitions are preserved in a separate non-loaded configuration file.
- `EnsureDashboardFeatureAvailable` blocks direct URLs for hidden prefixes for ordinary CMS users while preserving Platform Owner technical access and legacy operational permissions for regression continuity.
- The committed diff contains no deletions of migrations, tables, models, services, routes, or dependencies.
- Route verification shows 318 operational/Phase 5 routes still registered.
- `WebsiteCmsScopeTest` verifies active navigation contains exactly three groups and that ordinary CMS users receive 403 on hidden module URLs.

## 7. Authorization verification

- CMS authorization delegates to the authoritative RBAC service through `CmsAccess` and fails closed.
- Duplicate inquiry status permission was removed; `inquiries.update` is the single authoritative key.
- Functional CMS roles are seeded as definitions only; no user or officeholder is assigned.
- `publisher_reviewer` is view-only and reserved.
- Scoped support users are restricted to assigned inquiries for display and all mutation actions; a direct-ID bypass discovered during review was fixed and covered by a regression test.
- No `*.approve`, institutional, governance, or delegation permission keys were introduced. Route verification found zero approval routes.
- Platform Owner technical permissions are not treated as institutional authority.

## 8. Governance status

All governance statuses remain exactly as documented; no status was changed by this integration:

- **OQ-49:** `PARTIALLY RESOLVED — PENDING FORMAL APPROVAL`
- **ADR-001 through ADR-017:** `PROPOSED`
- **OQ-24 through OQ-29:** `DECISION REQUIRED`

No ADR was accepted, no open question was closed, no officeholder was created, and no institutional approval delegation or publication-approval power was granted. This report makes no institutional approval claim.

## 9. Test results

- Full suite: `OK (181 tests, 4017 assertions)`.
- Authorization, scope, CMS regression, inquiry and email-template focused set: `37 tests, 503 assertions`, all passing.
- No existing test was deleted. Two existing tests were updated only to match the intentional scope contract.
- Localization checks are included in the full suite and pass for both locales.

## 10. Build results

- `npm run build`: passed (`✓ built in 9.55s`).
- Non-blocking warnings remain: Vite chunk-size advisory and optional `fontaine` font-fallback notice.
- No build error or unresolved Vite manifest issue was observed.

## 11. Security checks

- Laravel Pint (`--dirty`): passed with no changes.
- Composer audit: no security vulnerability advisories.
- npm audit: 0 vulnerabilities.
- Secret-pattern scan over all committed implementation files: no hits.
- `git diff --check`: clean.
- Internal inquiry notices carry only the reference; sender name, email and message are excluded from notification payloads.
- IP addresses are stored as keyed SHA-256 hashes; delivery logs store hashed recipients.
- Email template interpolation is allowlisted and never evaluates PHP.
- Reply delivery is synchronous; exceptions cause `failed` status and an error flash rather than a false success.

## 12. Known limitations

- No browser/UI verification was performed in this task.
- No production MySQL/MariaDB verification was performed; tests use the configured test database.
- With Laravel's default `log` mailer (used when no mail configuration is supplied), a reply marked `sent` means the configured transport accepted it; it does not prove external delivery to the recipient.
- The development-only local CMS sign-in seam remains present but is disabled in production; Phase 2 authentication remains authoritative.
- Institutional approval capability remains intentionally absent, so the institutional verdict is not cleared.
- The prior final report of the consolidation was delivered in conversation, not as a committed artifact; this document is the committed integration record.

## 13. Git status

- No merge conflict occurred; no conflict resolution was needed.
- No push was performed and no remote is configured.
- The working tree contains no tracked modifications after the implementation commit.
- The two protected untracked artifacts remain untouched and excluded from all scans and commits.
- Destructive commands (`git reset --hard`, `git clean`, `git checkout -- .`, `git restore .`, `git add .`, `git add -A`) were not used.

## 14. Final verdict

**PASS WITH DOCUMENTED LIMITATIONS**

The technical integration is complete and verified: the consolidation commit is on `master`, all mandatory tests and checks pass, commercial scope is unchanged, out-of-scope modules remain preserved and hidden, and governance restrictions are intact. The documented limitations above are non-blocking for this integration-only task and do not constitute institutional approval or production-readiness certification.
