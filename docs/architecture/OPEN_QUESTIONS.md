# PCDR — Open Questions & Decision Register

**Status:** Consolidated register of unresolved items. Nothing here is assumed resolved. A classification states dependency scope only; it is not an answer. **OQ-49 is PARTIALLY RESOLVED — PENDING FORMAL APPROVAL** (not closed; formal authorization/delegation remains pending).

---

## 1. Classification

| Tag | Meaning |
|---|---|
| **[VERIFIED]** | Directly observed; not an open question. |
| **[ASSUMPTION]** | Used provisionally in the blueprint; must be confirmed before reliance. |
| **[DECISION REQUIRED]** | Needs an explicit human decision. |
| **[RECOMMENDATION]** | Architect's proposed default if no other constraint exists. |

### 1.1 Dependency classification used in this register

| Classification | Meaning |
|---|---|
| **GLOBAL BLOCKER** | Must be answered before production go-live and before the earliest dependent phase or feature. It affects multiple phases or a cross-cutting control. |
| **PHASE-SPECIFIC BLOCKER** | Must be answered before the named phase, schema, or feature. It does not by itself block unrelated phases. |
| **IMPORTANT** | A safe, reversible default exists; resolve during the named phase. |
| **DEFERRED** | May remain open until the stated trigger. No answer is inferred from deferral. |

**[RECOMMENDATION]** Where a default is recorded, it is provisional and reversible until approved. No human answer is supplied by this document. **OQ-48 is an alias of OQ-16** and must not create a second staging or deployment gate.

---

## 2. Organizational and legal

| ID | Question | Current state | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|---|
| OQ-01 | What is the verified legal registration status and registration number? | **[VERIFIED]** unverified in Phase 0 | Omit entirely from UI and settings until provided | PHASE-SPECIFIC BLOCKER | Before Phase 3 About-page content entry |
| OQ-02 | What is the verified founding date? | **[VERIFIED]** unverified | Omit; no invented dates | PHASE-SPECIFIC BLOCKER | Before Phase 3 public content entry |
| OQ-03 | Are there verified staff, teams, headquarters, or field offices? | **[VERIFIED]** none documented | No staff/team pages; roles are permission templates, not real people | PHASE-SPECIFIC BLOCKER | Before Phase 3 institutional-page design |
| OQ-04 | Are there verified partners, donors, or achievements? | **[VERIFIED]** none documented | Empty partner list; no results or targets presented as achievements | PHASE-SPECIFIC BLOCKER | Before Phase 3/4 public claims |
| OQ-05 | Is the strategic plan 2027–2029 content approved for public publication as targets? | **[DECISION REQUIRED]** | Label explicitly as planning targets, not results | PHASE-SPECIFIC BLOCKER | Before Phase 3 strategic-plan content |
| OQ-06 | Who is the authorized content owner approving public institutional claims? | **[DECISION REQUIRED]** | Designate before content entry | PHASE-SPECIFIC BLOCKER | Before first public content entry or editor grant |

---

## 3. Hosting and deployment

**Provider-neutral constraint:** No hosting provider is selected. Hostinger is a candidate label only. These questions are about the eventual target runtime, not about a selected vendor.

| ID | Question | Current state | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|---|
| OQ-07 | Selected provider/plan, PHP version selector, SSH/Composer/Node availability, and deployment channel | **[ASSUMPTION]** unconfirmed | Prebuild assets/upload `vendor/` so host tooling is not required; select another provider if requirements cannot be met | GLOBAL BLOCKER | Before Phase 2 dependency-sensitive decisions and production gate §8 |
| OQ-08 | Can document root be `public/` only? | **[ASSUMPTION]** unconfirmed | Require document-root isolation; reject provider if not possible | GLOBAL BLOCKER | Before Phase 2 config; blocks go-live |
| OQ-09 | Symlink support for `storage:link` or a signed-delivery alternative | **[ASSUMPTION]** unconfirmed | Use Laravel filesystem abstraction; choose delivery strategy per provider | GLOBAL BLOCKER | Before Phase 3 media implementation; blocks go-live |
| OQ-10 | Cron availability and artisan command support | **[ASSUMPTION]** unconfirmed | Bounded scheduled worker or dedicated worker where available | GLOBAL BLOCKER | Before Phase 2b; blocks go-live |
| OQ-11 | Long-running worker allowed? | **[ASSUMPTION]** typically unavailable on shared plans | `jobs` table is storage only; execution path must be confirmed | GLOBAL BLOCKER | Before Phase 2b; blocks go-live |
| OQ-12 | Database engine/version and InnoDB/`utf8mb4`/strict mode on target | **[ASSUMPTION]** local is MariaDB 12.3.2 | Avoid version-specific SQL; verify before production migration | GLOBAL BLOCKER | Before first Phase 2 migration; blocks go-live |
| OQ-13 | TLS termination, forwarded headers, trusted proxy configuration | **[ASSUMPTION]** unconfirmed | Configure trusted proxies only from confirmed headers | GLOBAL BLOCKER | Before Phase 2 cookie/security config; blocks go-live |
| OQ-14 | Backup scope, encryption, and restore test ownership | **[DECISION REQUIRED]** | Encrypted DB + private files; tested restore; no retention value assumed | GLOBAL BLOCKER | Before Phase 7 rehearsal; blocks go-live |
| OQ-15 | Outbound SMTP/transport port restrictions | **[DECISION REQUIRED]** | Provider-agnostic Laravel Mail; verify egress before relying on it | GLOBAL BLOCKER | Before Phase 2b; blocks go-live |
| OQ-16 | Staging or rehearsal environment availability | **[DECISION REQUIRED]** | Use staging/anonymized rehearsal for migration, CSP, and deployment | GLOBAL BLOCKER | Before Phase 3 rehearsal planning; blocks go-live. OQ-48 is an alias. |

---

## 4. Identity, authorization, and privacy

| ID | Question | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|
| OQ-17 | Auth UI: Laravel Breeze, Fortify, or hand-built Inertia pages? | **[RECOMMENDATION]** hand-built Inertia pages using Laravel's auth scaffolding components, after review — avoids a starter-kit dependency and matches existing Vue/Inertia | PHASE-SPECIFIC BLOCKER | Before Phase 2a auth implementation |
| OQ-18 | Is MFA required, and for which roles? | **[RECOMMENDATION]** required for privileged roles; method TBD | GLOBAL BLOCKER | MFA policy decision before Phase 2a auth design closes; implementation/enforcement before Phase 2b account-security delivery/go-live and before any production/restricted-data release |
| OQ-19 | Are departments/sections a real organizational structure? | **[VERIFIED]** none exists. Leave scope types unused if not real | PHASE-SPECIFIC BLOCKER | Before Phase 2 IAM migration |
| OQ-20 | Should admin routes be locale-prefixed? | **[RECOMMENDATION]** yes, consistent with public routes | PHASE-SPECIFIC BLOCKER | Before Phase 2a routing |
| OQ-21 | Should explicit deny rules be supported in scopes? | **[RECOMMENDATION]** no in initial design; union of scopes only | IMPORTANT | During Phase 2a `ScopeResolver` build |
| OQ-22 | Is impersonation required for support? | **[RECOMMENDATION]** no; strong audit/accountability concern | DEFERRED | Any phase, only on explicit request |
| OQ-23 | Which roles hold protected capabilities (`settings.update`, `roles.grant_protected`, bulk export)? | **[RECOMMENDATION]** top-level administration only; names to be confirmed by the organization | GLOBAL BLOCKER | Before Phase 2a role templates; blocks go-live |
| OQ-24 | Is a two-person rule required for beneficiary data export? | **[DECISION REQUIRED]** | GLOBAL BLOCKER | Before Phase 4 export design; blocks go-live |
| OQ-25 | What fields are truly required for beneficiary applications? | **[DECISION REQUIRED]** data-minimization workshop needed before schema | GLOBAL BLOCKER | Before Phase 4 beneficiary migration; blocks go-live |
| OQ-26 | Retention periods for beneficiary, complaint, CV, volunteer, and tender records | **[DECISION REQUIRED]** no values assumed | GLOBAL BLOCKER | Before Phase 4/5 retention implementation; blocks go-live |
| OQ-27 | Is there a data protection/DSAR or erasure process? | **[DECISION REQUIRED]** | GLOBAL BLOCKER | Before Phase 4 erasure path; blocks go-live |
| OQ-28 | Are beneficiary/complaint fields encrypted at rest beyond DB/server controls? | **[DECISION REQUIRED]** | GLOBAL BLOCKER | Before Phase 4 schema; blocks go-live |
| OQ-29 | Are anonymous complaints supported, and how is follow-up handled? | **[DECISION REQUIRED]** | GLOBAL BLOCKER | Before Phase 5 complaint schema; blocks go-live |
| OQ-30 | Who is the designated security/incident owner? | **[DECISION REQUIRED]** | GLOBAL BLOCKER | Before Phase 4 restricted data; no later than go-live |

---

## 5. Localization, design, and content

| ID | Question | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|
| OQ-31 | Confirm Arabic as default locale (currently **[VERIFIED]** `en`) | **[RECOMMENDATION]** yes | PHASE-SPECIFIC BLOCKER | Before Phase 2a localization plumbing |
| OQ-32 | Digit system: Latin or Arabic-Indic | **[RECOMMENDATION]** Latin digits initially; consistent Intl config | IMPORTANT | During Phase 2/3 formatting work |
| OQ-33 | Font choice: IBM Plex Sans Arabic + Inter vs current Instrument Sans | **[RECOMMENDATION]** adopt proposed pairing; verify licensing/self-hosting | PHASE-SPECIFIC BLOCKER | Before Phase 3 asset gate |
| OQ-34 | Are approved photographs/illustrations available, with consent? | **[VERIFIED]** none available; **[RECOMMENDATION]** use neutral surfaces/authored design assets until rights-bearing assets are proposed | DEFERRED | Conditional use gate: before actual institutional photography or rights-bearing illustration (consent, publication rights, and OQ-06 approval) enters Phase 3/4 content; synthetic non-public prototypes and authored design assets do not require an answer |
| OQ-35 | May the logo be used in motion sequences (no distortion)? | **[RECOMMENDATION]** logo itself static; separate motifs optional | IMPORTANT | Before Phase 3 motion sequences |
| OQ-36 | Are maps/interactive geography in scope, and which provider? | **[DECISION REQUIRED]** no mapping dependency assumed | PHASE-SPECIFIC BLOCKER | Before Phase 4 location schema |
| OQ-37 | Is rich-text editing allowed for editors, with sanitization? | **[RECOMMENDATION]** plain text/Markdown initially | IMPORTANT | Before Phase 3 editor implementation |
| OQ-38 | Can partial translations be published? | **[RECOMMENDATION]** block publication until required locales exist, unless explicitly overridden | IMPORTANT | Before Phase 3 publication rules; exception owner must be named |
| OQ-39 | Search requirement and support: basic DB filter, MySQL full-text, or external service? | **[DECISION REQUIRED] [RECOMMENDATION]** basic paginated DB filter only; no full-text or external index assumed | PHASE-SPECIFIC BLOCKER | Before Phase 3 CMS schema and any search UI |

---

## 6. Communications and reporting

| ID | Question | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|
| OQ-40 | Email provider and SMTP credentials owner | **[RECOMMENDATION]** provider-agnostic SMTP; credentials in env | GLOBAL BLOCKER | Before Phase 2b; blocks go-live |
| OQ-41 | Is a newsletter/subscription feature approved (consent + unsubscribe)? | **[DECISION REQUIRED]** not included by default | DEFERRED | Phase 6 only if approved |
| OQ-42 | Is web/mobile push (FCM) in scope? Who owns Firebase project/credentials? | **[DECISION REQUIRED]** not configured | DEFERRED | Phase 6 only if approved and credentials owned |
| OQ-43 | Are bounce/complaint webhooks required? | **[DECISION REQUIRED]** not assumed | DEFERRED | Phase 6+ if delivery health requires them |
| OQ-44 | Which reports/exports are required, and who may run them? | **[DECISION REQUIRED]** | PHASE-SPECIFIC BLOCKER | Before Phase 6 export implementation |
| OQ-45 | Should inbox email addresses be masked by default in logs? | **[RECOMMENDATION]** yes; reveal via audited action | IMPORTANT | Before Phase 2b/6 log schema |

---

## 7. Quality and process

| ID | Question | Proposed default | Classification | Needed before / milestone |
|---|---|---|---|---|
| OQ-46 | PHPUnit only or Pest as well? | **[RECOMMENDATION]** PHPUnit only unless team prefers Pest | IMPORTANT | During Phase 2 tooling setup |
| OQ-47 | CI platform availability (GitHub Actions etc.) | **[ASSUMPTION]** unknown; documented pre-push commands are the fallback | IMPORTANT | During Phase 2 quality-gate setup |
| OQ-48 | Staging availability for migration/CSP rehearsal | **Alias of OQ-16** — not a second gate | GLOBAL BLOCKER | Track under OQ-16 only; blocks go-live until OQ-16 is answered |
| OQ-49 | What formal authorization is required for ADR acceptance, Phase 1 approval, and phase transitions? | **[PARTIALLY RESOLVED — PENDING FORMAL APPROVAL]** Confirmed by strategic plan (authority framework): Board of Directors approves institutional policies, regulations, plans, and budgets; Executive Director handles daily execution and implementation of Board decisions. Proposed, not formally approved: Executive Director approves ordinary public content; Media and Communication, MEAL, and relevant Program Unit review content. Not established/recorded: named officeholders, formal content approval procedure, formal review committee, written delegation decisions, actual user assignments. Organization agrees approval permissions remain pending until formal authorization. No approval rights are granted to the developer. | GLOBAL BLOCKER | Before any ADR acceptance, Phase 1 approval record, or any phase transition; formal written authorization/delegation required |

---

## 8. Gate references

The **single authoritative production deployment gate** is `DEPLOYMENT_ARCHITECTURE.md` §8. This register does not maintain a competing list.

- **Sensitive-data gate:** OQ-24 → OQ-29 must be answered before sensitive schema design, the first restricted-data migration, and release.
- **Public-content gate:** OQ-01 → OQ-06 and OQ-31 → OQ-39 apply as features are enabled; OQ-06 must name the content owner before any institutional claim is entered.
- **Provider-capability checks:** OQ-07 → OQ-16 and OQ-40 are validated through §8 before deployment or before enabling a dependent capability; they do not block unrelated Phase 2a work.
- **Governance gate:** OQ-49 is **PARTIALLY RESOLVED — PENDING FORMAL APPROVAL**. The strategic plan confirms the general authority framework, but detailed execution procedures, named officeholders, written delegation decisions, and formal approval authorization are not recorded. Approval permissions remain pending until formal authorization. No ADR is accepted, no Phase 1 approval is recorded, no phase transition is authorized, and no approval rights are granted to the developer.

### 8.1 Classification summary

| Classification | Count | IDs |
|---|---:|---|
| GLOBAL BLOCKER | 22 | OQ-07–OQ-16, OQ-18, OQ-23–OQ-30, OQ-40, OQ-48, OQ-49 |

> **OQ-49 note:** OQ-49 remains counted as a GLOBAL BLOCKER because formal ADR acceptance, Phase 1 approval, and phase-transition authorization are still blocked until formal written authorization/delegation is recorded. Its status is PARTIALLY RESOLVED — PENDING FORMAL APPROVAL, not closed.
| PHASE-SPECIFIC BLOCKER | 14 | OQ-01–OQ-06, OQ-17, OQ-19, OQ-20, OQ-31, OQ-33, OQ-36, OQ-39, OQ-44 |
| IMPORTANT | 8 | OQ-21, OQ-32, OQ-35, OQ-37, OQ-38, OQ-45, OQ-46, OQ-47 |
| DEFERRED | 5 | OQ-22, OQ-34, OQ-41, OQ-42, OQ-43 |

**[RECOMMENDATION]** No answer is fabricated in this blueprint. Where a default is proposed, it is explicitly labeled and remains reversible until approved.

---

## 9. Phase 5 implementation note

The Phase 5 operating-layer implementation does not answer or close any question in this register. OQ-24–OQ-29 remain **[DECISION REQUIRED]** and continue to block sensitive schemas, public intake, retention, and release. OQ-49 remains **PARTIALLY RESOLVED — PENDING FORMAL APPROVAL**; no approval permission, route, status, delegation, or officeholder was introduced. The authoritative technical scope is `docs/architecture/PHASE_5_CONTRACT.md`; older roadmap references to complaints, volunteers, careers, and tenders are not implemented or approved by this delivery.
