# Select and Dropdown System Redesign Report

Date: 2026-11-24  
Branch: `main`  
Baseline HEAD: `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`  
Final status: **PASS WITH LIMITATIONS**

---

## 1. Executive summary

Every select, dropdown, status selector, filter, and array-backed selection interface in PCDR now uses one shared, accessible selection architecture. The redesign covers admin, CMS, public, operations, Phase 5, dashboard, modal, table, filter, auth/settings, and form surfaces.

The implementation consists of one primitive, `SelectionControl.vue`, plus thin compatibility and semantic wrappers: `StatusSelect.vue`, `MultiSelect.vue`, `Ui/SelectField.vue`, and `Dashboard/PcSelect.vue`. All wrappers delegate to the primitive; there is no second listbox implementation. The legacy `.pc-select-pro` markup and CSS were retired, and all Vue templates were migrated off native `<select>` elements.

Status colors are derived from the real backend status vocabulary through `resources/js/Support/selectionStatuses.js`. Stored status values, field names, `v-model` contracts, validation, submissions, filters, query parameters, permissions, and backend payload formats were preserved. No PHP file was changed by this task.

The selection-specific audit passes 10/10 tests, the production build passes, and the final full Feature suite is 273/274 with one unrelated backend audit-action naming failure. Authenticated browser checks could not run because no E2E credentials are configured; the cross-browser run is further limited by a Chromium GPU crash in this environment. These limits are documented in sections 14 and 15.

---

## 2. Objective, scope, and acceptance criteria

The objective was to replace inconsistent native selects, custom dropdowns, filter menus, and checkbox-array pickers with one cohesive, premium, accessible system without changing application behavior.

Acceptance criteria applied during the work:

- One reusable selection primitive with thin wrappers, not duplicated select implementations.
- ARIA combobox/listbox semantics with keyboard navigation, active-descendant tracking, outside-click dismissal, Escape handling, and focus restoration.
- Disabled, loading, empty, search, clear, multiple, grouped, error, required, and status states.
- RTL/LTR support, responsive/mobile behavior, reduced-motion support, and light/dark token hooks.
- Teleported panels that avoid clipping in tables, modals, drawers, and narrow layouts.
- A centralized semantic status map based on actual backend values; no status values or business rules changed.
- Existing field names, `v-model` contracts, validation, submissions, filters, query parameters, permissions, backend request formats, and defaults preserved.
- No new dependency; the existing `@floating-ui/dom` package was reused.
- No commit, push, branch change, worktree change, reset, clean, stash, broad staging, or destructive checkout.

All criteria were implemented. Verification limitations are recorded rather than hidden.

---

## 3. Baseline, preservation, and Git safety

Checks were performed on `main` at baseline HEAD `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`. Branch and HEAD remain unchanged at final review.

Safety notes:

- No branch, worktree, commit, push, reset, clean, stash, `git checkout .`, `git restore`, `git add .`, or `git add -A` was performed.
- Pre-existing staged, unstaged, and untracked work was left intact. The repository already contained unrelated staged changes; none were created by this task.
- `.env.mysql.bak` and `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md` were never opened, read, modified, staged, or committed.
- `README.md` was not modified.
- `app/Http/Controllers/Admin/DashboardController.php` was not modified.
- No PHP files were changed by this task, so Pint was not run.
- `package.json` was changed only to add `test:selection`; no dependency was added or removed.
- All generated build artifacts are normal Vite output and were not hand-edited.

`git diff --check` was clean at final review.

---

## 4. Pre-change audit findings

The initial audit found:

- 51 native `<select>` occurrences across 23 Vue files.
- Six additional native selects discovered later in `Admin/AuditArchives/Index.vue` and `Admin/AuditLogs/Index.vue`.
- Array-backed checkbox groups used as pseudo-multi-selects in role/user permission forms, CMS tag editing, webhook event selection, and the access-management drawer.
- Phase 5 dynamic fields rendering raw selects from schema metadata.
- Status colors derived independently in several components, including `CmsStatusBadge.vue`, audit archive chips, and audit log chips.
- A legacy `.pc-select-pro` implementation retained in `dashboard.css` and referenced by Playwright selectors.
- No backend-driven async selector endpoints; loading/error/empty behavior was therefore implemented in the primitive rather than exposed through a separate `AsyncSelect`.
- No true native multi-select; grouped permissions were the main grouped-selection use case.
- No application-level dark theme; dark support needed to be token-hook based rather than tied to an existing live toggle.

Final static audit result:

- `grep` for `<select` in `resources/**/*.vue`: **0 matches**.
- `grep` for `pc-select-pro` in application source (`resources/**`) and browser specs: **0 matches**. The term remains only in the negative audit assertion and this report.
- Only `SelectionControl.vue` contains `role="listbox"`.

---

## 5. Architecture and shared primitive

`resources/js/Components/Selection/SelectionControl.vue` is the single interaction and accessibility engine. It handles:

- Option normalization for primitive values and objects with `value`, `id`, `key`, `code`, `slug`, `name`, or `label`.
- Search filtering over label, description, and group text.
- Grouped rendering through option `group`/`module` fields.
- Active index tracking over navigable, non-disabled options.
- Single and multiple selection, chip display, clear behavior, optional limits, and empty value emission.
- Teleported fixed-position panels using `computePosition`, `flip`, `shift`, and `autoUpdate` from the existing `@floating-ui/dom` package.
- Placement that switches between `bottom-start` in LTR and `bottom-end` in RTL.
- Outside pointer dismissal, Escape dismissal, Tab dismissal, and focus restoration.
- Loading, empty, disabled, invalid, required, and error states.

Public entry points are intentionally thin:

- `Selection/StatusSelect.vue`: semantic status wrapper, non-searchable by default.
- `Selection/MultiSelect.vue`: array-valued wrapper with chips and optional `max`.
- `Ui/SelectField.vue`: form field wrapper with automatic search above six options.
- `Dashboard/PcSelect.vue`: compatibility wrapper preserving legacy props/emits and `emptyValue: null`.

No wrapper duplicates combobox, listbox, option, or keyboard markup.

---

## 6. Unified visual language and design tokens

`resources/css/selection.css` is imported from `resources/js/app.js` and defines one token set for the control and its teleported panel.

The approved palette is represented through tokens including Navy `#3E5172`, Navy 900 `#25334B`, Navy 800 `#30415E`, Navy 100 `#E9EDF2`, Teal `#71BCB7`, Teal 700 `#397F7B`, Teal 100 `#EAF5F4`, Sand `#DAC3A3`, Sand 100 `#F7F2EA`, Wheat `#D7C5A1`, and White `#FFFFFF`.

Variants:

- `dashboard`: inherits existing dashboard surface, border, text, and teal tokens.
- `public`: uses public-site navy, teal, sand, and white tokens with a compact radius.
- `auth`: uses auth-surface and sand-soft tokens.

Sizes `sm`, `md`, and `lg`, focus rings, hover states, disabled styling, invalid borders, chips, group headings, badges, highlighted search text, spinner, footer, and status tones are defined centrally. Retired `.pc-select-pro` CSS was removed from `dashboard.css`, including stale reduced-motion selectors.

---

## 7. Component contracts and form behavior

The redesign preserved caller contracts:

- `modelValue` and `update:modelValue` remain the primary Vue contract.
- `change` is emitted by the primitive and compatibility wrappers where callers previously relied on it.
- `name` is serialized through a visually hidden native input for single selects and `name[]` hidden inputs for multiple selects.
- `required`, `error`, `aria-invalid`, and invalid focus handling remain available.
- `disabled` and `loading` states do not emit values.
- `options` accept both primitives and objects; extra object metadata such as description, badge, icon, group, disabled, `testId`, and `dataKey` is passed through safely.
- `StatusSelect` derives tones from option values unless an explicit `tone` is provided.
- `MultiSelect` keeps the bound value as an array and preserves the backend array shape.
- `PcSelect` retains its historical props/emits and emits `null` when cleared.
- `Ui/SelectField` keeps its prior `name`, `label`, `options`, `error`, `required`, `disabled`, and `variant` behavior.

Existing values, defaults, status strings, filter parameters, and permission identifiers were not renamed or normalized in application data.

---

## 8. Migration inventory by area

Representative migrated areas and files:

- **Admin**: `Pages/Admin/AuditLogs/Index.vue` (five filter selects), `Pages/Admin/AuditArchives/Index.vue` (status filter), `Pages/Admin/Roles/Edit.vue`, `Pages/Admin/Users/Create.vue`, `Pages/Admin/Users/Edit.vue`, `Pages/Admin/Access/Index.vue`.
- **CMS**: `Pages/Cms/Content/Edit.vue`, `Pages/Cms/Content/Index.vue`, `Pages/Cms/EmailTemplates/Index.vue`, `Pages/Cms/Homepage/Index.vue`, `Pages/Cms/Inquiries/Index.vue`, `Pages/Cms/Inquiries/Show.vue`, `Pages/Cms/Media/Index.vue`, `Pages/Cms/Pillars/Index.vue`, `Pages/Cms/Settings/Index.vue`, `Components/Cms/NavigationMenuEditor.vue`, `Components/Cms/InquiryStatusControl.vue`.
- **Email templates**: `Components/EmailTemplates/TemplateWizard.vue` and `Components/EmailTemplates/EmailPreviewDialog.vue`.
- **Public site**: shared `Components/InquiryForm.vue` plus public filters such as `Pages/Public/News/Index.vue` and `Pages/Public/Publications/Index.vue`.
- **Operations**: `Pages/Operations/ResourceIndex.vue`, `ResourceForm.vue`, `Reports.vue`, and `Inventory.vue`.
- **Phase 5**: `Components/Phase5/Phase5Form.vue`, `Components/Phase5/Phase5FilterBar.vue`, `Pages/Phase5/Webhooks/Index.vue`, `Pages/Phase5/Automations/Index.vue`, `Pages/Phase5/Notifications/Preferences.vue`.
- **Shared/dashboard**: `Components/Ui/SelectField.vue`, `Components/Dashboard/PcSelect.vue`, `Components/Dashboard/CmsStatusBadge.vue`.

Phase 5 dynamic fields use `StatusSelect` when the field name matches `/status/i` or `field.status` is set; other dynamic selects use `SelectionControl` directly.

---

## 9. Array-backed, grouped, and multi-selection interfaces

Array-backed checkbox groups were converted to `MultiSelect` while preserving their array contracts:

- Role permissions in `Admin/Roles/Edit.vue`.
- User permissions in `Admin/Users/Create.vue` and `Admin/Users/Edit.vue`.
- News tags in `Cms/Content/Edit.vue`.
- Webhook events in `Phase5/Webhooks/Index.vue`.
- Drawer permissions in `Admin/Access/Index.vue`.

The access drawer retained:

- Grouped permissions via option `group`.
- Localized group categories, names, and descriptions.
- Protected permission badges.
- `assignable` as a disabled option state.
- `data-testid="permission-option"` and `data-key` passthrough for the existing test contract.
- Select-all and clear-all behavior.
- The existing summary panel and permissions payload shape.

Boolean toggles such as inquiry consent, section enablement, notification preferences, audit archive force/queue flags, and Phase 5 boolean fields remain native checkboxes; they are not selection lists and were intentionally not converted.

---

## 10. Status vocabulary and semantic colors

`resources/js/Support/selectionStatuses.js` is the single source of truth for status tones. It exports `STATUS_TONES`, `statusTone()`, and `withStatusTone()`.

Tones are limited to `green`, `teal`, `amber`, `red`, and `slate`. The map includes real stored values from CMS, inquiry, operations, audit, and Phase 5 vocabularies, including values such as `draft`, `pending_review`, `published`, `accepted`, `rejected`, `failed`, `processing`, `verified`, `pruned`, `approved_for_submission`, `queued`, `sending`, `backlog`, `to_do`, `blocked`, `critical`, `warning`, and `info`. Unknown values fall back to `slate`.

Important guarantees:

- `withStatusTone()` does not mutate the caller's options; it only adds tone metadata.
- `statusTone()` is used for comparison/lookup only; it never rewrites stored status values.
- `StatusSelect`, `CmsStatusBadge.vue`, `AuditArchives/Index.vue`, and `AuditLogs/Index.vue` all consume the central map.
- No local `toneMap` remains in `CmsStatusBadge.vue`.
- No status business rule, transition, label, or stored value was changed.

---

## 11. Accessibility and keyboard behavior

The primitive implements the following contract:

- Trigger: `role="combobox"`, `aria-expanded`, `aria-haspopup="listbox"`, `aria-controls`, `aria-labelledby`/`aria-label`, `aria-describedby`, `aria-invalid`, `aria-required`, `aria-disabled`, and `tabindex="0"`.
- Panel: `role="listbox"`, `aria-multiselectable` for multiple controls, and labelled by the visible field label or ARIA label.
- Options: `role="option"`, `aria-selected`, `aria-disabled`, unique DOM IDs, and active-descendant tracking on whichever element has focus.
- Search input: receives arrow, Home/End, Enter/Space, Escape, and Tab handling when the panel is open and searchable.
- Non-searchable controls keep focus on the trigger and expose `aria-activedescendant` there.
- Keyboard: ArrowDown/ArrowUp open or move, Home/End jump, Enter/Space choose, Escape closes and restores focus, Tab closes without stealing focus, and outside `pointerdown` closes.
- Active options are scrolled into view with `scrollIntoView({ block: 'nearest' })`.
- Loading and empty states use `role="status"`; errors use `role="alert"`.
- Chip removal buttons are real buttons with remove labels and stopped keyboard events; no nested-button markup remains.
- Hidden native inputs and `@invalid="focusTrigger"` keep browser form validation reachable.

The Playwright admin spec was updated to assert `.pc-selection__trigger[role="combobox"]`, `[role="listbox"]`, option counts, and changing `aria-activedescendant` in both AR/RTL and EN/LTR.

---

## 12. RTL/LTR, responsive, theme, and portal behavior

- Direction comes from the existing `useI18n()` helper and is applied to the teleported panel with `:dir="direction"`.
- Floating UI uses `bottom-end` for RTL and `bottom-start` for LTR, with `flip` and `shift` padding to keep panels inside the viewport.
- CSS uses logical properties (`inset-inline`, `padding-inline`, `margin-inline-start`) and includes RTL-specific trigger, chip, and search-input padding.
- Panels are teleported to `<body>` with fixed positioning, preventing clipping in tables, drawers, modals, and overflow-hidden containers.
- Mobile/responsive rules at `max-width: 640px` constrain panel width and adapt option/footer spacing; multi-select chips wrap with a capped scroll height.
- Reduced-motion rules disable selection transitions and spinner animation.
- Light/dark tokens are defined for `[data-theme='dark']`, `.dark`, `html.dark`, and `prefers-color-scheme: dark`, with explicit light opt-out selectors.
- The application has no live dark-theme toggle, so dark support is token-hook based and was verified statically rather than visually.

---

## 13. Data, validation, and integration preservation

The redesign was intentionally presentation- and interaction-only:

- Filter controls still emit the same values into the existing `router` query parameters.
- `AuditArchives` still applies `applyStatus` on change.
- `AuditLogs` filters still bind to `form.event_class`, `form.severity`, `form.actor`, `form.action`, and `form.subject`.
- Permissions and role forms still submit the same arrays and identifiers.
- Webhook events remain an array on `form.events`.
- CMS tags remain an array on `form.tags`.
- Required fields, error messages, and disabled states remain wired to the existing form objects.
- No backend route, controller, request, model, migration, config, or validation rule was changed by this task.

`StatusSelect` defaults to non-searchable; callers that need search pass `searchable` explicitly. This preserves prior dropdown density while keeping the capability available.

---

## 14. Verification results

Commands and final results:

- `npm run test:selection`: **10 pass / 0 fail**. The suite covers status mapping integrity, wrapper delegation, no native `<select>`, multi-select migration, central status consumption, design tokens, RTL/responsive/dark hooks, and updated browser selectors.
- `npm run build`: **passed**. Vite transformed 798 modules. Warnings remain for the optional `fontaine` package and a >500 kB chunk; both are non-blocking and pre-existing in nature.
- `vendor/bin/phpunit --testsuite Feature --colors=never --no-progress`: **274 tests, 273 passed, 1 failed, 6,753 assertions**. The failure is `AuditTrailTest::test_audit_log_viewing_and_export_require_authorization`, which expects `audit_logs.export` while the backend records `audit_logs.exported`. No PHP file was changed by this task, and the failure concerns backend audit-action naming, not selection UI. An earlier execution of the suite showed additional audit/archive/dashboard failures and errors; they were not reproducible in the final run.
- `E2E_BROWSER=firefox npm run test:e2e -- tests/e2e/admin-redesign.spec.mjs tests/e2e/strategic-pillars-responsive.spec.mjs`: **1 passed, 10 skipped**. The passing test discovers admin routes. Every authenticated visual, axe, DataTable, select-keyboard, Lighthouse, reduced-motion, and responsive check skipped because no E2E credentials are configured.
- Chromium attempts with and without `--disable-gpu --disable-software-rasterizer` failed at browser context creation because the headless Chromium GPU process exited fatally before any page interaction. Firefox was used to confirm the specs and skip path cleanly.
- `git diff --check`: clean.
- Final branch/head: `main` / `07848abbf1a2b3d12102b4fd0ee37ecc260195fd`.
- Final static audits: no `<select` in Vue templates, no retired `pc-select-pro` selectors in application source or browser specs, one listbox implementation, central status map consumed by status controls and badges.

---

## 15. Limitations, residual findings, and final verdict

Limitations:

1. Authenticated browser verification was not performed because `E2E_EMAIL` and `E2E_PASSWORD` are unavailable and no seeded local credentials were configured. The Firefox run skipped those checks explicitly rather than inventing data.
2. Chromium could not launch reliably in this environment due a GPU process crash; cross-browser visual verification remains outstanding.
3. No application-level dark theme exists, so dark-mode support is implemented through token hooks and verified statically, not against a live theme toggle.
4. No backend-driven async selector endpoint exists, so loading/error states were implemented and statically audited in the primitive but were not exercised against a live async endpoint.
5. The final Feature suite has one unrelated backend failure: an audit action-name expectation (`audit_logs.export` vs `audit_logs.exported`). It was not introduced by this frontend-only task and was left untouched to preserve independent work.

Final verdict: **PASS WITH LIMITATIONS**.

The selection redesign itself is complete: one accessible primitive, all wrappers and migrations in place, zero native selects in Vue, centralized status semantics, preserved data contracts, passing selection-focused tests, and a passing production build. The limitations are environmental verification gaps and one pre-existing backend audit naming mismatch, none of which are caused by the selection changes. No commit was created; all work remains in the existing working tree on `main`.

To close the remaining verification gaps later, provide E2E credentials and run the Firefox/Chromium specs with a working browser runtime, then address the backend audit-action naming mismatch in a separate PHP change if desired.
