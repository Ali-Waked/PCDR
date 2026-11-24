# Audit Logs Redesign and Archival Report

## 1. Executive summary and final status

The PCDR admin Audit Logs module has been redesigned as a premium, mobile-first enterprise surface and extended with a secure monthly archival, integrity-verification, retention-reporting, legal-hold, and cleanup lifecycle.

Audit history remains append-only. Historical event fields are never edited; only lifecycle metadata (`archive_id`, `archived_at`) may be written by the archive service, and deletion is possible only through a short-lived, scoped token issued by the retention service after all safeguards pass.

**Final status: PASS WITH LIMITATIONS**

The implementation and automated verification pass. The limitations are operational/environmental: no browser or E2E verification was performed, and production scheduler/queue-worker runtime behaviour must be validated after deployment (see sections 15 and 18).

---

## 2. Scope, constraints, and preservation of existing work

Work was performed directly on the existing `main` checkout in `/root/PCDR/pcdr`. No worktree, branch switch, commit, push, reset, stash, clean, checkout-restore, or broad staging command was used.

The pre-task working tree was already heavily modified. The initial snapshot was preserved at `/tmp/opencode/audit_initial_status.txt`. Pre-existing CMS, homepage, user, RBAC, email-template, translation, and report work was left intact.

Explicitly protected files were not opened, read, modified, staged, or committed:

- `.env.mysql.bak`
- `docs/architecture/PHASE_5_AND_FOUNDATION_INTEGRATION_REPORT.md`

`README.md` and `app/Http/Controllers/Admin/DashboardController.php` were not modified.

Pint was applied with an explicit file list covering only this task’s PHP files. Pre-existing dirty PHP files touched for configuration/navigation/translations (`config/permissions.php`, `config/admin_navigation.php`, `routes/web.php`, `lang/en/ui.php`, `lang/ar/ui.php`) were checked read-only with Pint and already passed formatting, so unrelated user work was not reformatted.

---

## 3. Baseline and legacy gap analysis

The pre-task baseline recorded 240 tests with 239 passing; the single failure was an unrelated `TranslationKeysTest` issue in concurrent homepage work (`website.ui.display_value`, `website.ui.url`).

Legacy gaps addressed by this work:

- Audit Logs had a basic table without a premium responsive redesign, derived severity/event classes, metric summary, mobile cards, filter chips, or rich detail inspection.
- No monthly archival registry, private ZIP export, manifest, checksum verification, or retry-safe generation existed.
- No retention reporting, legal holds, dry-run workflow, explicit confirmation, batching, or cleanup audit trail existed.
- Export safety was not centralized; CSV formula-injection protection and consistent redaction needed to be guaranteed in both live exports and archives.
- Archive permissions were not defined and private download authorization was not separated from view/manage capabilities.
- Scheduling and queue execution for audit lifecycle work were absent.

---

## 4. Target architecture and data flow

The implemented lifecycle has four layers:

1. **UI/controller layer** — Inertia/Vue pages and authorized controllers expose exploration, exports, archive management, verification, download, and cleanup reporting.
2. **Query/export layer** — `AuditLogQueryService` applies filters, derives event class/severity, builds summaries/details, and emits normalized export rows.
3. **Archive/retention layer** — `AuditArchiveService` builds and verifies monthly ZIPs; `AuditRetentionService` produces reports, enforces holds, and performs guarded cleanup.
4. **Async/scheduling layer** — queued jobs and Laravel 13 native schedule entries provide unattended monthly generation and daily reporting without destructive default behaviour.

Monthly generation resolves the previous complete month (or an explicit `YYYY-MM`) into a fixed `[start, end)` period plus a stable cutoff. Records are streamed with `chunkById()`, written to UTF-8 CSV/JSONL, packaged with a manifest and SHA-256 checksums, re-inspected from private storage, then marked with lifecycle metadata only after counts reconcile.

---

## 5. Database schema and lifecycle metadata

Migration `2026_11_24_055404_create_audit_archive_lifecycle_schema.php` adds:

- `audit_archives` — period boundaries, cutoff, record count, private file path, checksums, manifest, status, schema version, attempt count, initiator, verification/pruning/cleanup timestamps, and error text.
- `audit_cleanup_runs` — dry-run or execute mode, status, counts, report payload, requester, and timestamps.
- `audit_log_holds` — global/subject/actor/period scope, label, reason, expiry, creator, release metadata, and target identifiers.
- Nullable `archive_id` and `archived_at` columns on `audit_logs`.

Archive statuses are `pending`, `processing`, `verified`, `failed`, and `pruned`. Cleanup statuses are `processing`, `completed`, and `failed`.

Historical audit fields remain untouched. The only permitted update to an `AuditLog` is archive lifecycle metadata; deletion requires the retention service’s short-lived scoped token.

---

## 6. Append-only immutability and deletion control

`App\Models\AuditLog` enforces immutability in an `updating` hook. Any update containing fields other than `archive_id`/`archived_at` is rejected. `delete()` is likewise rejected unless the cleanup service has issued its scoped token via `withCleanupDeletion()`.

This means:

- Event actions, actors, subjects, before/after payloads, context, request IDs, and created timestamps cannot be edited after insertion.
- A retry can re-link or count already-archived records without mutating historical event content.
- Cleanup deletion is explicit, temporary, service-scoped, and audited.

Verification evidence includes tests asserting that an archived event cannot change `action`, cannot be deleted directly, and is deleted only by an authorized cleanup run.

---

## 7. Redaction and export safety

`App\Support\AuditRedactor` centralizes redaction for both `AuditService` and archive/export paths. Sensitive key fragments such as `password`, `token`, `secret`, `authorization`, `cookie`, `session`, `api_key`, `credential`, `private_key`, and `passphrase` are replaced with `[REDACTED]`.

`App\Support\CsvSafeWriter` provides:

- UTF-8/JSON encoding without escaping Arabic (`JSON_UNESCAPED_UNICODE`).
- CSV formula-injection neutralization by prefixing values beginning with `=`, `+`, `-`, `@`, or tab with an apostrophe, while preserving numeric values such as `42` and `-7`.
- NUL removal and newline normalization.
- Shared field/row/json helpers used by both live exports and monthly archives.

Network metadata (`ip`, `user_agent`) in event details is replaced with `[RESTRICTED]` unless the viewer holds the stronger `audit_logs.export` capability.

---

## 8. Admin Audit Logs UI redesign

`resources/js/Pages/Admin/AuditLogs/Index.vue` was rebuilt with:

- PCDR design tokens, premium page header, metric cards, and an append-only trust cue.
- Desktop table and mobile card layouts, with no unnecessary horizontal scrolling.
- Filter panel with search, actor, action, subject, event class, severity, and date range.
- Active filter chips with individual removal and full reset.
- Empty states, loading/permission-aware actions, and bilingual labels.
- Detail dialog loaded through Inertia v3 `useHttp` (not native `fetch`).
- CSV/JSONL export links and an Archives navigation action.
- RTL/LTR-aware spacing and light/dark theme support through existing dashboard CSS variables.

`resources/js/Pages/Admin/AuditArchives/Index.vue` adds archive generation/retry/queue controls, status filtering, archive metrics, desktop/mobile archive views, cleanup reports, holds, and integrity details.

---

## 9. Filters, date range, details, and pagination

Filters are normalized server-side by `AuditLogQueryService` and returned to the page as canonical filter state. Date boundaries use inclusive/exclusive-safe handling and are reflected in exports and archives.

The details endpoint derives:

- Human-readable event labels.
- Event classes (`security`, `content`, `people`, `system`).
- Severity (`critical`, `warning`, `info`).
- Changed-field diffing from redacted before/after payloads.
- Archive lifecycle metadata when present.

Pagination is capped at 100 rows per page to protect the query path. Filter and pagination state are preserved across Inertia visits.

---

## 10. CSV and JSONL exports

Live exports are available at `GET {locale}/admin/audit-logs/export` and require `audit_logs.export`.

Example:

```text
/en/admin/audit-logs/export?format=csv&search=actor&from=2026-10-01&to=2026-10-31
/en/admin/audit-logs/export?format=jsonl&severity=critical
```

Both formats:

- Apply the same filters as the explorer.
- Use redacted, normalized rows.
- Stream with `Cache-Control: no-store, private` and `X-Content-Type-Options: nosniff`.
- Preserve UTF-8 Arabic text.
- Emit an `audit_logs.export` audit event with `before`/`after` left null for legacy compatibility; operational details are stored in `context`.

CSV starts with a UTF-8 BOM and uses formula-injection-safe fields. JSONL emits one JSON object per line using `JSON_UNESCAPED_UNICODE`.

---

## 11. Private archive packaging and storage

Archives are generated by `AuditArchiveService` and stored only on the configured private disk:

```dotenv
PCDR_AUDIT_ARCHIVE_DISK=private
PCDR_AUDIT_ARCHIVE_PATH=audit-archives
PCDR_AUDIT_EXPORT_BATCH_SIZE=500
PCDR_AUDIT_EXPORT_SCHEMA_VERSION=1.0
```

Each archive is named `audit-logs-YYYY-MM.zip` and contains:

```text
audit-logs.csv
audit-logs.jsonl
manifest.json
```

The manifest records period, boundaries, cutoff, record count, schema version, application/commit metadata, and verification status. SHA-256 checksums are computed for all three files and stored both in `manifest.json` and in the `audit_archives.checksums` registry column.

Downloads are served only through an authorized controller stream using a single `Content-Disposition: attachment` header. No public URL is generated.

---

## 12. Integrity verification and retry semantics

Verification reopens the stored ZIP, validates required members, recomputes all SHA-256 checksums, checks the manifest, and confirms that the counted rows match the registry record count.

Duplicate prevention and retry behaviour:

- A verified archive for a period cannot be regenerated normally.
- `pending`, `processing`, or `failed` attempts can be retried only with `--force`.
- Retries increment `attempts`, clear stale errors, remove stale temporary files, and preserve the original archive registry identity.
- Queued jobs use `ShouldBeUnique` to reduce duplicate work.
- The mark-archived step counts already-linked records before updating remaining records, preventing double counting on retries.

Status is persisted as `verified` only after export count, marked count, checksums, and manifest inspection all reconcile.

---

## 13. Retention policy and dry-run reporting

Retention is disabled by default and no institutional retention duration is assumed:

```dotenv
PCDR_AUDIT_RETENTION_ENABLED=false
PCDR_AUDIT_RETENTION_MONTHS=
PCDR_AUDIT_RETENTION_MIN_ARCHIVE_AGE_DAYS=7
PCDR_AUDIT_RETENTION_REQUIRE_DRY_RUN=true
PCDR_AUDIT_CLEANUP_BATCH_SIZE=500
```

Destructive cleanup requires all of the following:

- Retention enabled with a positive configured retention period.
- A verified archive that has reached the retention boundary and minimum archive age.
- A fresh completed dry-run report.
- Exact confirmation phrase `DELETE AUDIT LOGS`.
- No active global legal hold.
- Reconciled eligible/deleted/remaining counts.
- Small-batch deletion and a persisted execute-mode cleanup run.

Scheduled cleanup is report-only by default. It never deletes records without explicit CLI confirmation.

---

## 14. Legal holds

`pcdr:audit:hold` creates active holds with `global`, `subject`, `actor`, or `period` scope. Optional expiry and release metadata support audited legal review workflows.

Example:

```bash
php artisan pcdr:audit:hold \
  --scope=subject \
  --subject-type=users \
  --subject-id=101 \
  --label='Subject litigation hold' \
  --reason='Active legal review of subject 101.'
```

Hold constraints are NULL-safe: system events with no actor or no subject are not accidentally excluded by SQL NULL comparison semantics. A global hold blocks all destructive cleanup. Hold creation and release are themselves recorded in the audit trail.

Release example:

```bash
php artisan pcdr:audit:release-hold 12 --released-by=7
```

---

## 15. Scheduled tasks, queues, and command reference

Laravel 13 native scheduling is defined in `routes/console.php`:

```text
30 2 2 * *  php artisan pcdr:audit:archive
30 3 * * *  php artisan pcdr:audit:cleanup-due
```

Both tasks use `withoutOverlapping()`, `onOneServer()`, and `runInBackground()`. The archive task is guarded by `when(config('audit_archive.enabled'))`; the cleanup task is report-only by default.

Queue configuration:

```dotenv
PCDR_AUDIT_QUEUE_CONNECTION=
PCDR_AUDIT_QUEUE_NAME=audit
```

Available commands:

```bash
php artisan pcdr:audit:archive {month?} [--force] [--queue]
php artisan pcdr:audit:archives [--status=] [--limit=20]
php artisan pcdr:audit:archive:verify {archive} [--dry-run]
php artisan pcdr:audit:cleanup {archive} [--dry-run] [--execute] [--confirm=] [--requested-by=]
php artisan pcdr:audit:cleanup-due [--execute] [--confirm=]
php artisan pcdr:audit:hold [--scope=global] [--subject-type=] [--subject-id=] [--actor=] [--from=] [--to=] [--expires=] [--label=] [--reason=] [--created-by=]
php artisan pcdr:audit:release-hold {hold} [--released-by=]
```

Typical operational sequence:

```bash
php artisan pcdr:audit:archive 2026-10
php artisan pcdr:audit:archive:verify 2026-10
php artisan pcdr:audit:cleanup 2026-10 --dry-run
php artisan pcdr:audit:cleanup 2026-10 --execute --confirm='DELETE AUDIT LOGS'
```

---

## 16. Authorization, navigation, and localization/theming

Four protected, internal permissions were added to the code-owned catalog and are not seeded to ordinary roles:

- `audit_archives.view`
- `audit_archives.download`
- `audit_archives.manage`
- `audit_archives.cleanup`

`AuditArchivePolicy` separates viewing, downloading, managing, and cleanup. Platform owners are allowed; ordinary users require explicit grants. Archive navigation is exposed only to users who can view archives.

Bilingual AR/EN translation keys were added to `lang/en/ui.php` and `lang/ar/ui.php`. The redesign uses existing PCDR tokens, dashboard spacing, and light/dark CSS variables. The retention notice now uses the required `pc-workflow-alert__body`, `pc-workflow-alert__icon`, and `pc-workflow-alert__copy` DOM structure.

---

## 17. Verification results and test evidence

All commands below were run from `/root/PCDR/pcdr`.

```bash
php artisan test --compact \
  tests/Feature/AuditArchiveLifecycleTest.php \
  tests/Feature/AuditLogsInterfaceTest.php
# Result: PASS — 16 tests, 238 assertions
```

```bash
php artisan test
# Result: PASS — 277 tests, 6767 assertions
```

```bash
vendor/bin/pint --format agent <task PHP file list>
# Result: PASS (six files were initially normalized; final verification passed)
```

```bash
npm run build
# Result: PASS — 798 modules transformed
# Warnings only: optional fontaine package not installed; one JS chunk exceeds 500 kB
```

```bash
git diff --check
# Result: PASS — no output
```

```bash
php artisan route:list --name=audit --except-vendor
# Result: PASS — 8 audit/archive routes registered
```

```bash
php artisan schedule:list
# Result: PASS — 2 scheduled tasks registered
```

```bash
php artisan list --raw | grep 'pcdr:audit'
# Result: PASS — 7 audit lifecycle commands registered
```

Issues found and resolved during verification:

- Native `fetch()` in both Vue detail dialogs was replaced with Inertia v3 `useHttp`.
- The retention notice DOM was completed to satisfy the workflow-alert styling contract.
- Duplicate `Content-Disposition` header on archive downloads was removed.
- Retry counting double-counted already-linked records; it now counts before updating.
- NULL-unsafe legal-hold constraints were corrected.
- Legacy compatibility was restored for `audit_logs.export` action naming and null `before`/`after` fields on view/export audit entries.
- Route-model bindings for localized `show()` endpoints were corrected with `string $locale` as the first parameter.

---

## 18. Deployment requirements, limitations, and final disposition

### Required deployment runtime

A web request lifecycle alone is insufficient. Production must run:

```bash
php artisan schedule:work
php artisan queue:work --queue=audit
```

or an equivalent supervisor/systemd/process-manager configuration. The scheduler triggers monthly archive generation and daily cleanup reporting; the queue worker handles queued generation/cleanup jobs when `--queue` or queued dispatch is used.

Also required in production:

- A persistent private disk (default `storage/app/private`) with backups and restore testing.
- `PCDR_AUDIT_RETENTION_ENABLED=true` and a positive `PCDR_AUDIT_RETENTION_MONTHS` before any destructive retention is intended.
- Explicit operational approval and exact confirmation phrase for every destructive cleanup.
- Monitoring of `audit_archives`, `audit_cleanup_runs`, failed notifications, and queue failures.

### Limitations

- No browser, Playwright, axe, Lighthouse, or manual visual verification was performed.
- Production scheduler/queue runtime behaviour was not observable from this workspace.
- Retention remains disabled by default; enabling it is an institutional policy decision.
- The private archive disk must be backed up independently of the database.

### Final disposition

All implemented scope is complete, automated PHP tests pass, Pint passes, the frontend production build succeeds, whitespace checks pass, and registration checks pass.

**Final status: PASS WITH LIMITATIONS**
