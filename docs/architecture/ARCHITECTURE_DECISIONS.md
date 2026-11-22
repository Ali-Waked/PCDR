# PCDR — Architecture Decision Records (ADRs)

**Status:** All decisions below are **PROPOSED by the Phase 1 blueprint** and pending human approval. No ADR is accepted, no owner has been named, and no ADR describes implemented code. **OQ-49 is PARTIALLY RESOLVED — PENDING FORMAL APPROVAL** (general authority framework confirmed by strategic plan; formal execution approval/delegation not recorded; no approval rights granted to the developer).

---

## ADR-001 — Preserve the Laravel 13 / Vue 3 / Inertia stack; no downgrade or recreation

- **Context:** **[VERIFIED]** Phase 0 confirmed Laravel 13.43.0, PHP 8.5.12, Vue 3.5.46, Inertia adapter 3.7.0, `@inertiajs/vue3` 3.11.0, Vite 8.3.8, Tailwind 4.3.5, MySQL connection on MariaDB 12.3.2, clean git at `c371e8e`. Laravel 13 is officially supported on PHP 8.3–8.5 until 2028-03-17. No hosting provider is selected; Hostinger is a candidate label only.
- **Decision:** Keep the existing project, framework versions, and database type. No downgrade, no project recreation, no framework replacement. If a provider cannot meet the requirements, select another provider rather than downgrading Laravel.
- **Alternatives:** Downgrade to Laravel 10/11 for perceived hosting ease (rejected: shorter support, needless rewrite); switch to React/Next or Livewire-only (rejected: discards verified working setup and goes against requirements); change to PostgreSQL/SQLite (rejected: no justification).
- **Consequences:** Provider compatibility must satisfy PHP 8.3–8.5 and be validated through the canonical gate before dependent features or deployment. Deployment must prebuild assets if Node is unavailable on the host.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-002 — Single application and single primary database

- **Context:** Public website and institutional back office share content, media, users, and settings. The target is one deployment account or platform with one primary database **[ASSUMPTION]**; provider is not selected.
- **Decision:** One Laravel application, one primary MySQL database, with a clear public/private boundary enforced in code. No separate admin subdomain, separate database, or microservice split in the foreseeable phase.
- **Alternatives:** Separate public/admin apps (rejected: duplicated auth and content sync); multi-database read replicas (rejected: premature); microservices (rejected: unjustified operational cost).
- **Consequences:** Filament-like packages are not required; one deployment pipeline; authorization must be rigorous because both surfaces share a process and data store.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-003 — Locale-prefixed routes with Arabic as initial default

- **Context:** Native AR/EN parity, RTL/LTR, SEO. **[VERIFIED]** current app defaults to `en` and has no `dir` attribute or locale routes.
- **Decision:** Use `/{locale}` prefixes (`/ar`, `/en`) for public and admin routes; Arabic becomes the initial default; root `/` redirects to `/ar`. Locale is URL-authoritative, not cookie-driven.
- **Alternatives:** Subdomains (`ar.example.com`) — rejected due to DNS/cert complexity; session-only locale — rejected due to SEO/cache ambiguity; no prefix — rejected.
- **Consequences:** All route generation must preserve locale prefix; SEO canonical/hreflang required; current env defaults change in a later phase. Admin prefix is governed by OQ-20; no default is approved until answered.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-004 — Custom RBAC instead of Spatie Laravel Permission

- **Context:** Requirements include granular permissions, multi-role users, custom roles, protected system roles, and extensible data scopes.
- **Decision:** Build a custom RBAC model (`users`, `roles`, `permissions`, pivots, `role_scopes`) with a code-owned permission catalog in `config/permissions.php`. No Spatie package.
- **Alternatives:** Spatie package (rejected: extra dependency, its team/scope model would need extension, second vocabulary to maintain); Gates-only hardcoded roles (rejected: no admin manageability).
- **Consequences:** More code to write and test; full control over scopes, cache invalidation, and protection rules; authorization tests mandatory.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-005 — Backend is the sole authorization authority; frontend checks are UI-only

- **Context:** Dynamic permissions and data scopes must not be bypassable via manipulated clients.
- **Decision:** Enforce authorization in middleware, Form Requests, Policies, Gates, and Actions. Frontend `can()` helpers only hide/disable controls.
- **Alternatives:** Trust client-supplied role/permission flags (rejected: trivial bypass); duplicate rules in JS as a "second authority" (rejected: drift risk, still not security).
- **Consequences:** Every sensitive endpoint needs server tests; Inertia props include permission lists for UX only. `auth.scopes` carries `scope_type` only and never `scope_value`.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-006 — Thin controllers, Actions/Services, no Repository Pattern by default

- **Context:** **[VERIFIED]** no business layer exists; risk of logic accumulating in routes/controllers.
- **Decision:** Controllers are HTTP adapters; Form Requests own validation/authorization; Actions own one business operation; Services coordinate domains/integrations; DTOs carry structured data. Repositories are not introduced unless a genuine multi-source or complex-query need appears.
- **Alternatives:** Fat controllers (rejected: untestable duplication); Repository Pattern everywhere (rejected: indirection over Eloquent without benefit); command bus package (rejected: unnecessary dependency).
- **Consequences:** More small classes; disciplined naming; boundary enforcement through review/static analysis.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-007 — Per-entity translation tables plus Laravel lang files

- **Context:** Independent AR/EN content, partial translations, editor-owned content, static UI strings.
- **Decision:** Authored content uses `*_translations` tables with unique (`owner_id`, `locale`); static UI/validation/mail keys use `lang/ar` and `lang/en`; user-submitted text stores one value with a `locale` marker. No single generic translations table.
- **Alternatives:** Single `translations` table with polymorphic keys (rejected: weak integrity, hard to query); JSON columns only (rejected: poor querying and constraints); no DB translations (rejected: editors cannot own translations).
- **Consequences:** More tables; explicit fallback rules; locale-aware admin UI required. Static `lang/` keys are the code-owned fallback for critical mail.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-008 — Enum status + config-driven workflow transitions, no workflow engine

- **Context:** Draft → review → approval → publication → archival across several modules.
- **Decision:** PHP backed enums plus `config/workflows.php` transition maps, enforced by one `TransitionWorkflow` Action and audit events.
- **Alternatives:** Generic workflow engine package (rejected: overengineering for declared scope); ad-hoc per-controller checks (rejected: drift and bypass risk); native MySQL ENUM (rejected: hard to evolve).
- **Consequences:** New modules must declare their transition map; complex future workflows may need reconsideration.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-009 — Explicit media registry with pivots and dedicated restricted attachments

- **Context:** Media is reused across many entities and requires localized alt/caption, ordering, usage role, and strict access control for restricted documents.
- **Decision:** One `media` registry + `media_translations`; explicit pivots (`page_media`, `project_media`, etc.) for public/reused media with sort order and usage metadata; dedicated restricted-attachment tables with an explicit `media_id` for beneficiary, complaint, career, volunteer, tender, and email attachments. No domain table stores a raw file path. All restricted reads use an authorization-checked controller with a signed, expiring URL.
- **Alternatives:** Polymorphic `mediable` table (rejected: weaker referential integrity and awkward per-relation metadata); per-entity file columns (rejected: duplication); mixing pivots and standalone file paths without one access rule (rejected: authorization drift).
- **Consequences:** A few extra tables and migrations; strong integrity; orphan cleanup must check both pivots and dedicated attachment references. **[DECISION REQUIRED]** owner confirmation of the split model; if pivots are used everywhere, the same signed-URL and authorization rule still applies.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-010 — Split settings tiers; never expose secrets or treat visibility as authorization

- **Context:** Admins must manage many settings, but infrastructure secrets must never be returned to browsers or stored in DB settings. Hiding a menu must not grant/deny access.
- **Decision:** Secrets (`APP_KEY`, DB password, SMTP credentials, provider keys) remain in environment/secret manager. DB settings are public or protected configuration with `is_public` controlling frontend exposure; credential fields are reference names only. Module flags control availability, not authorization or publication.
- **Alternatives:** All settings in DB encrypted (rejected: key management complexity); all settings in env (rejected: admins cannot manage content settings); frontend-hidden = secure (rejected: false security).
- **Consequences:** Clear setting catalog and allowlist required; audits record redacted before/after values. `APP_KEY` custody and rotation are unresolved under OQ-28/OQ-30.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-011 — Laravel Mail + templates + queue + logs; provider-agnostic SMTP

- **Context:** Transactional and administrative email with AR/EN templates, logs, retries, future provider replacement; no paid provider assumed.
- **Decision:** Use Laravel Mail with queued non-critical Mailables, DB-backed localized templates with variable allowlists, delivery logs, dedup keys, and SMTP or another supported transport configured via environment. **Critical account-security mail** (password reset, verification) must use a code-owned `lang/` fallback when a DB translation is missing and must never be silently skipped; its delivery path is synchronous or uses a confirmed worker (OQ-10/OQ-11/OQ-15/OQ-40).
- **Alternatives:** Paid transactional provider SDK as default (rejected: cost/assumption); direct `mail()` calls (rejected: no queue, logs, or templates); self-hosted mail server (rejected: operational burden).
- **Consequences:** A queue worker/scheduler plan is required for queued mail; critical-mail behavior must be tested explicitly; template validation needed.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-012 — Laravel Notifications with replaceable channels; FCM optional and not configured

- **Context:** In-app notification center required; web/mobile push desired in future; provider uncertainty.
- **Decision:** Use Laravel Notifications as the abstraction; database channel first; preferences, categories, dedup, delivery logs; external push behind a replaceable channel. FCM is a candidate only, not configured.
- **Alternatives:** Custom notification system (rejected: reinventing Laravel); hard-coded FCM SDK dependency now (rejected: unconfirmed requirement and credentials).
- **Consequences:** Push phase requires separate approval and credential ownership (OQ-42); notifications carry summaries, not restricted data.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-013 — CSS-first motion system with one small IntersectionObserver composable

- **Context:** Motion is a core requirement; bundle and accessibility discipline matter on mobile and across providers.
- **Decision:** Central CSS tokens + keyframes, native SVG animation, and one internal shared-observer composable. No GSAP, Lottie, or animation package in Phase 1. No one-shot animation exceeds 480ms; the sole repeating effect is the labeled loading-skeleton pulse token, bounded to pending-data states; reduced motion disables non-essential movement.
- **Alternatives:** GSAP (rejected: larger dependency, unnecessary); Lottie (rejected: asset pipeline/decorative); Motion for Vue (deferred: revisit if complex sequencing needed).
- **Consequences:** Complex choreography may need a later reevaluation; reduced-motion handled in our own tokens/composable.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-014 — Token-driven design system with distinct public/dashboard shells; no UI kit

- **Context:** Premium institutional identity required; generic admin templates rejected; public and back office need different density.
- **Decision:** CSS custom properties + Tailwind v4 theme, semantic tokens, shared `Ui/` primitives, separate `PublicLayout` and `DashboardLayout`; IBM Plex Sans Arabic + Inter is a **candidate** typography pairing pending OQ-33; WCAG 2.2 AA target.
- **Alternatives:** Filament/AdminLTE-style template (rejected: generic appearance and constraints); separate CSS frameworks per surface (rejected: drift).
- **Consequences:** Design tokens must be maintained deliberately; contrast, responsive, and RTL verification required; font change is a later implementation step and assets are not approved.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-015 — Pint + PHPUnit + planned Larastan; mandatory authorization tests

- **Context:** **[VERIFIED]** only skeleton tests and Pint installed; sensitive modules need strong guarantees; CI availability is unresolved (OQ-47).
- **Decision:** Keep Pint and PHPUnit; add Larastan and frontend tooling only after OQ-47 and a PHP 8.3 compatibility-check tool are resolved; require authorization tests for every sensitive endpoint and define CI/pre-push quality gates (see `TESTING_STRATEGY.md`).
- **Alternatives:** Pest as primary (optional, decision required); no static analysis (rejected: maintainability risk); coverage-only gates (rejected: coverage theater).
- **Consequences:** More tests to write per feature; quality-gate setup needed; no packages added in Phase 1.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-016 — Provider-neutral deployment with prebuilt assets; portability preserved

- **Context:** No provider is selected; Hostinger is a candidate only. Capabilities such as PHP version, Composer/SSH/Node, document root, symlinks, cron, workers, SMTP egress, backups, and SLA are unconfirmed. No downgrade is allowed; future provider or VPS moves must remain possible.
- **Decision:** Prebuild Vite assets, upload `vendor/` if needed, require document-root isolation to `public/` through the provider's supported mechanism, use portable database cache/session/queue defaults, and select worker/scheduler strategy only after capability validation. Host-specific concerns stay in deployment configuration, trusted-proxy settings, filesystem disks, and queue strategy.
- **Alternatives:** Assume SSH + Node + daemon workers on any shared plan (rejected: unverified); container-only deployment (rejected: premature); downgrade framework (rejected by ADR-001).
- **Consequences:** Some features remain disabled until cron/worker is confirmed; the canonical gate in `DEPLOYMENT_ARCHITECTURE.md` §8 must be fully answered before go-live. Moving between shared, managed, VPS, or container hosting changes runtime tooling, not application modules.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

## ADR-017 — Append-only audit logs with redaction and retention

- **Context:** Accountability requirements plus sensitive data handling.
- **Decision:** Central `audit_logs` with actor, action, subject, before/after for state changes, context, timestamp; read/export entries omit `before`/`after` and carry `context` plus optional `access_reason`; append-only inside the application; sensitive reads and exports audited; redaction rules; expiry only through approved archive/export plus a compensating entry, with retention per approved policy. No separate `settings_audit` table.
- **Alternatives:** Model-level timestamps only (rejected: insufficient); mutable log table (rejected: tampering risk); external SIEM now (rejected: unconfirmed budget/hosting).
- **Consequences:** Storage growth planning needed; audit viewer needs its own permission; correction via compensating entries; retention periods remain **[DECISION REQUIRED]**.
- **Status:** PROPOSED — pending human approval (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL; no named/formally delegated decision owner recorded).
- **Decision owner:** TBD — formal authorization under OQ-49 pending; no approval rights granted to the developer.
- **Date proposed:** 2026-11-21.
- **Date approved:** Not recorded.

---

## Decision register summary

All 17 ADRs are **PROPOSED**, not accepted. No decision owner or approval date has been recorded. OQ-49 is **PARTIALLY RESOLVED — PENDING FORMAL APPROVAL**: the strategic plan confirms the general authority framework, but detailed execution procedures, named officeholders, written delegation decisions, and formal approval authorization are not recorded. No approval rights are granted to the developer.

| ID | Topic | Status | Decision owner | Date proposed | Date approved |
|---|---|---|---|---|---|
| ADR-001 | Preserve stack, no downgrade | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-002 | Single app/DB | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-003 | Locale-prefixed routes, AR default | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-004 | Custom RBAC | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-005 | Backend authz authority | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-006 | Thin controllers / no repositories | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-007 | Per-entity translations | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-008 | Config workflows | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-009 | Explicit media model | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-010 | Settings tiers / no secrets | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-011 | Mail architecture | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-012 | Notification architecture | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-013 | CSS-first motion | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-014 | Design system | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-015 | Testing strategy | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-016 | Provider-neutral deployment | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |
| ADR-017 | Audit logging | PROPOSED — pending human approval | TBD (OQ-49 PARTIALLY RESOLVED — PENDING FORMAL APPROVAL) | 2026-11-21 | Not recorded |

---

## Phase 5 implementation note

The Phase 5 implementation introduced no new ADR and accepted no existing ADR. All 17 ADRs remain **PROPOSED** pending formal approval under OQ-49. The technical controls and contract in `docs/architecture/PHASE_5_CONTRACT.md` are implementation constraints only; they do not grant institutional authority, approve a phase transition, or close an open question.
