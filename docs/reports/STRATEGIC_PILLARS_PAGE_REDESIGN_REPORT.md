# Strategic Pillars Page Redesign Report

## Objective

Redesign and harden the localized CMS Strategic Pillars management page in Arabic and English, add backend filtering and a professional workflow experience, and keep the change isolated from the shared content-management screens.

## Scope and constraints

- Work stayed on `main` in `/root/PCDR/pcdr`; no branch, commit or push was created.
- Existing user modifications and staged changes were preserved.
- Protected files `.env.mysql.bak` and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were not opened or modified.
- `README.md` was not modified.
- The shared `Cms/Content/Index` page, permission catalog and initial-status behavior on create were left unchanged.

## Implementation

### Backend

- `PillarController::index()` now renders a dedicated `Cms/Pillars/Index` Inertia page instead of reusing the shared content index.
- Search is executed in the database across `code` and translated `title`, `short_title`, `slug` and `description` in both locales, with escaped `LIKE` wildcards.
- Status filtering accepts only values from `ContentStatus`; invalid values safely fall back to `all`.
- Pagination uses `withQueryString()` so filters survive page links.
- Summary counts and available transition actions are computed server-side from the same graph used by `TransitionContentStatus`.
- `StrategicPillar::scopeSearch()` and `scopeWorkflowStatus()` encapsulate the query behavior.
- `ContentController::transitionPermission()` centralizes the existing permission mapping; no permission keys were added or removed.
- `PillarController::status()` catches invalid transitions only for Inertia visits. Explicit non-Inertia JSON clients still receive the original `409` JSON response, and ordinary HTML requests keep the original conflict behavior.
- Inertia invalid transitions receive a localized `303` redirect with flashed `flash.error` and structured `flash.workflow` feedback. Query filters and the current page are preserved.
- `bootstrap/app.php` no longer returns bare JSON to Inertia requests for domain exceptions; it returns an Inertia-compatible redirect with localized flash data.
- `HandleInertiaRequests` shares `flash.workflow` with the client.
- Arabic and English translations were added under `ui.cms_pillars_page`.

### Workflow graph

The authoritative graph comes from `config('cms.transitions')`:

| From | Allowed transitions |
| --- | --- |
| `draft` | `pending_review`, `archived` |
| `pending_review` | `draft`, `approved`, `archived` |
| `approved` | `draft`, `published`, `archived` |
| `published` | `draft`, `archived` |
| `archived` | `draft` |

`WorkflowFeedback` derives the shortest valid sequence between states from this graph. For an invalid `draft -> published` request, the displayed sequence is `Draft → Pending review → Approved → Published` (localized). The technical exception message is never shown to Inertia users.

### Frontend

- New page: `resources/js/Pages/Cms/Pillars/Index.vue`.
  - Localized title and subtitle above the table via `PageHeader`.
  - Breadcrumb wired through `CmsDashboardLayout` and `DashboardChrome`.
  - Six summary cards, debounced search, searchable status select, clear-filters action and loading state.
  - Desktop table with status badges, locales, dates, workflow actions and permission-gated edit/delete controls.
  - Mobile card layout below 820px with the same actions.
  - Empty states for no records and no filtered matches.
  - Pagination summary and filters preserved across visits.
- New reusable accessible select: `PcSelect.vue`.
  - Combobox/listbox roles, keyboard navigation, type-ahead search, clear support and controlled selection.
- New `WorkflowAlert.vue`.
  - Presents localized invalid-transition reasons, current/requested states, optional next action and the graph-derived sequence.
- New `WorkflowTransitionDialog.vue`.
  - Shows only transitions permitted by both the graph and the user's permissions.
  - Displays the required sequence from the executed graph and a localized confirmation message.
- Responsive styles appended to `resources/css/dashboard.css` for the select, toolbar, cards, table wrappers, alert, dialog and 360/390/430/768px layouts. Table scrolling is contained inside the table wrapper to avoid page-level horizontal overflow.

## Tests added

`tests/Feature/StrategicPillarsPageTest.php` covers:

- Dedicated Inertia component, localized page title, filters and the real transition graph.
- Backend search by code, slug and translated fields in both locales.
- Status filtering and query-preserving pagination.
- Invalid Inertia transition returning a localized `303` with the full required sequence and no raw exception text.
- Non-Inertia JSON clients retaining the `409` contract.
- Valid transitions still using the existing workflow action.

`tests/e2e/strategic-pillars-responsive.spec.mjs` checks the localized title and no horizontal page overflow at 360, 390, 430, 768 and 1440px, plus the searchable select and clear-filters flow. It skips when E2E credentials are unavailable.

## Verification

- `php artisan test --compact tests/Feature/StrategicPillarsPageTest.php` — **6 passed**, 165 assertions.
- `php artisan test --compact tests/Feature/CmsWorkflowTest.php` — **20 passed**, 219 assertions.
- `npm run build` — **passed** (Vite production build).
- `php artisan route:list --name=cms.pillars` — all 7 pillar routes confirmed.
- `git diff --check` — **clean**.
- `node --check tests/e2e/strategic-pillars-responsive.spec.mjs` — **clean**.
- `vendor/bin/pint --dirty --format agent` — run after PHP changes.

`vendor/bin/phpunit` completed 225 of 226 tests successfully. The single failure is `TranslationKeysTest::vue_and_blade_translation_keys_exist_in_lang_files`, caused by an unrelated `email_templates.variables.` dynamic key in concurrently present email-template work (`app/Services/EmailTemplatePreviewService.php`). It is outside this redesign scope and was not changed.

Browser verification is covered by the added Playwright specification, which skips when E2E credentials are unavailable instead of inventing identities. A local browser run was attempted, but Playwright Chromium could not launch in this environment because its GPU process exited unexpectedly; disabling GPU and shared-memory support did not resolve the crash. The specification passes `node --check`, but the responsive assertions remain unverified in this environment.

## Result

The Strategic Pillars page now has a localized, responsive dashboard layout; real backend search and filtering; an accessible searchable select; permission-aware workflow dialogs; and safe, localized handling of invalid transitions without breaking existing API contracts or shared CMS screens.
