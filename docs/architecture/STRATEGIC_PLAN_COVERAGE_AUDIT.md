# PCDR — Strategic Plan & Architecture Coverage Audit

**Date:** 2026-11-21
**Audit type:** READ-ONLY documentation audit
**Strategic plan:** `docs/strategy/STRATEGIC_OPERATIONAL_PLAN_2027_2029.md`
**Architecture corpus:** 17 Phase 1 architecture documents + 3 supporting reports
**Scope boundary:** No code, migration, route, model, component, package, environment, database, Git staging, commit, ADR status change, Open Question closure, Phase 2 start, or Phase 1 approval is performed by this audit. The only file created is this report.

**Status statement:** Phase 1 remains **not approved**. All 17 ADRs remain `PROPOSED — pending human approval`. No implementation or phase transition is authorized by this report.

> **Addendum (2026-11-22, governance clarification; documentation-only):** After the 2026-11-21 audit, an organizational response clarified OQ-49. OQ-49 is **PARTIALLY RESOLVED — PENDING FORMAL APPROVAL** (not closed). Confirmed by strategic plan: Board of Directors approves institutional policies, regulations, plans, and budgets; Executive Director handles daily execution and implementation of Board decisions. Proposed, not formally approved: Executive Director approves ordinary public content; Media/MEAL/relevant Program Unit review content. Not established/recorded: named officeholders, formal content approval procedure, formal review committee, written delegation decisions, actual user assignments. Approval permissions remain pending until formal authorization. This addendum does **not** approve Phase 1, does **not** accept any ADR, does **not** authorize Phase 2, and does **not** grant approval rights to the developer. Historical findings below remain the 2026-11-21 audit record except where explicitly corrected by later dated documentation updates.

---

## Abbreviations and evidence convention

- **PLAN** = `docs/strategy/STRATEGIC_OPERATIONAL_PLAN_2027_2029.md`
- **ARCH** = `docs/architecture/`
- **SYS** = `ARCH/SYSTEM_ARCHITECTURE.md`
- **BACK** = `ARCH/BACKEND_ARCHITECTURE.md`
- **AUTHZ** = `ARCH/AUTHORIZATION_ARCHITECTURE.md`
- **DB** = `ARCH/DATABASE_ARCHITECTURE.md`
- **FE** = `ARCH/FRONTEND_ARCHITECTURE.md`
- **LOC** = `ARCH/LOCALIZATION_ARCHITECTURE.md`
- **DESIGN** = `ARCH/DESIGN_SYSTEM.md`
- **MOTION** = `ARCH/MOTION_DESIGN_SYSTEM.md`
- **SEC** = `ARCH/SECURITY_ARCHITECTURE.md`
- **COMM** = `ARCH/COMMUNICATION_ARCHITECTURE.md`
- **DEP** = `ARCH/DEPLOYMENT_ARCHITECTURE.md`
- **MAP** = `ARCH/MODULE_MAP.md`
- **TEST** = `ARCH/TESTING_STRATEGY.md`
- **ADR** = `ARCH/ARCHITECTURE_DECISIONS.md`
- **ROAD** = `ARCH/IMPLEMENTATION_ROADMAP.md`
- **OQ** = `ARCH/OPEN_QUESTIONS.md`
- **SUM** = `ARCH/PHASE_1_SUMMARY.md`
- **REVIEW** = `ARCH/PHASE_1_INDEPENDENT_REVIEW.md`
- **FINAL** = `ARCH/PHASE_1_FINALIZATION_REPORT.md`
- **VERIFY** = `ARCH/PHASE_1_FINAL_VERIFICATION_REPORT.md`

Classification codes:

| Code | Classification | Meaning used in this audit |
|---|---|---|
| **F** | FULLY COVERED | The requirement is documented clearly and with enough technical/operational detail to be converted into implementation. This is a documentation-coverage judgment, **not** an implementation claim. |
| **P** | PARTIALLY COVERED | The requirement is mentioned or a generic mechanism exists, but required domain detail, workflow, data, permission, report, or acceptance detail is insufficient. |
| **N** | NOT COVERED | No clear treatment, entity, workflow, permission, route, test, or gate was found. |
| **C** | CONFLICT | The plan and architecture state incompatible or unmapped requirements/status schemes. |
| **D** | DEFERRED | The requirement is intentionally gated by an open owner decision, privacy gate, content gate, or approval gate. Deferral is **not** an answer and **not** approval. |
| **O** | OUT OF SCOPE | The physical/external action itself is not an in-system software function; only its recording/tracking can be in scope. |

Counting unit: one **requirement unit** = one distinct functional, institutional, data, governance, security, interface, readiness, or explicitly out-of-scope requirement identified in the plan. Matrices in later sections reuse the same IDs; they are not recounted.

---

## 1. Executive Summary

The architecture corpus is strong as a **generic platform blueprint**: layered Laravel backend, custom RBAC with data scopes, localized AR/EN routing, CMS/public website structures, media registry, notification/email plumbing, audit logging, design/motion tokens, testing strategy, provider-neutral deployment requirements, and a single canonical production gate are documented with substantial detail.

However, the corpus is **not a complete architectural coverage of the 2027–2029 strategic plan**. It does not contain a strategy-to-architecture crosswalk. The dominant gaps are not cosmetic: the plan’s core humanitarian and institutional operating model is only thinly represented.

### Master classification totals

| Classification | Count |
|---|---:|
| FULLY COVERED | **70** |
| PARTIALLY COVERED | **78** |
| NOT COVERED | **57** |
| CONFLICT | **2** |
| DEFERRED | **26** |
| OUT OF SCOPE | **4** |
| **Total requirement units** | **237** |

### Most important findings

1. **No plan crosswalk exists.** The 17 architecture documents never map the plan’s 11 pillars, 11 objectives, 2027 quarterly phases, 2028–2029 stages, annual KPI targets, or budget distribution into architecture requirements. The only direct plan reference is `OQ-05` about publishing the plan as planning targets (`OQ:37`; `FINAL:121`).
2. **Core operational domains are absent.** No first-class entity, workflow, permission family, route/page set, test category, or roadmap item was found for donors, finance/accounting, allocations, distributions, inventory/warehouses, referrals, families/households, studies/needs assessments, indicators/results/KPI targets, evaluations, risk register, or community committees.
3. **Humanitarian programs are mostly generic.** Projects, activities, locations, aggregate reach, and beneficiary applications can host many plan activities, but the archive does not define the program-specific data, state machines, eligibility rules, distributions, stock movements, referral pathways, or reporting logic required by pillars 1–8 and 11.
4. **MEAL/KPI cannot be computed from the documented model.** The plan requires baselines, targets, actuals, outputs, outcomes, impact, data sources, verification methods, unique-beneficiary deduplication, and periodic review. Architecture offers only `report_exports`, optional non-identifying `metric_snapshots`, generic dashboards, and export permissions (`DB:263-269`, `MAP:204-213`).
5. **Governance lifecycle has a terminology conflict.** The plan uses `Draft → Pending Review → Approved → Published → Archived`; architecture uses `draft, submitted, in_review, changes_requested, approved, published, archived, rejected` with no mapping for “Pending Review” (`BACK:178-187`, `DB:95-101`, `DESIGN:159-163`).
6. **Phase scheme conflict.** PLAN uses four 2027 execution quarters and 2027/2028/2029 maturity stages (`PLAN:905-999`, `PLAN:1183-1189`); architecture uses engineering Phases 1–7 with 2a/2b (`ROAD:19-32`) and provides no crosswalk.
7. **Many requirements are correctly deferred, not rejected.** OQ-49 (approver), OQ-05 (plan as targets), OQ-19 (org structure), OQ-24–OQ-29 (privacy), OQ-31/33/34/35/39 (locale/assets/content/search), OQ-44 (exports), provider OQs, and OQ-41–43 remain open. These must not be answered by the developer or treated as approvals.
8. **Implementation is still skeleton-only.** One route, one placeholder Vue page, one Inertia middleware, one `User` model, empty provider, framework migrations, and skeleton tests are the verified baseline (`SYS:30-31`, `BACK:11`, `FE:13`, `TEST:12`, `SUM:18-23`). Every control discussed below is a design requirement, not an implemented control.
9. **Hosting remains provider-neutral.** No provider is selected; Hostinger is a candidate label only. No capability is asserted. The canonical gate is `DEP:150-212`. PHP 8.3 is a Laravel 13 support-window/portability floor; PHP 8.5.12 is the verified local development runtime. This is not a downgrade or deployment-runtime claim (`ADR:7-16`, `SYS:26,33`, `DEP:13,36`, `VERIFY:160-189`).
10. **Final readiness verdict:** **NOT READY FOR FULL STRATEGIC-PLAN IMPLEMENTATION**. Only a bounded, provider-neutral platform foundation can be designed for later implementation after explicit human approval and after the missing strategy-specific architecture is completed. Phase 2 must not start on the basis of this audit.

---

## 2. Strategic Plan File Integrity Check

### 2.1 Physical and structural checks

| Check | Result | Evidence |
|---|---|---|
| File exists | PASS | `docs/strategy/STRATEGIC_OPERATIONAL_PLAN_2027_2029.md` |
| Size | 57,839 bytes | Filesystem `ls -l` |
| Encoding | Valid UTF-8; no CRLF (`\r\n` count = 0) | Python decode check |
| Line count | 1,290 lines | `wc -l` |
| Word count | 4,595 words | `wc -w` |
| Introduction | Present at lines 8–16 | `PLAN:8-16` |
| Chapters 1–12 | All present and in order | `PLAN:17`, `123`, `808`, `905`, `1006`, `1037`, `1080`, `1122`, `1168`, `1193`, `1218`, `1235` |
| Closing recommendation | Present | `PLAN:1265-1277` |
| Content truncation | No truncation detected; final marker present | `PLAN:1279` |
| 11 strategic objectives | Present | `PLAN:61-94` |
| 11 strategic pillars | Present | `PLAN:125`, `204`, `275`, `348`, `423`, `479`, `535`, `587`, `641`, `719`, `775` |
| Programs/activities and indicators | Present for all 11 pillars | `PLAN:128-799` |
| KPI targets and calculation controls | Present | `PLAN:1006-1033` |
| Finance/budget section | Present | `PLAN:1037-1077` |
| Governance, partnerships, MEAL, priorities, risks | Present | `PLAN:1080-1261` |

### 2.2 Integrity issues documented, not corrected

| Issue | Evidence | Impact | Action |
|---|---|---|---|
| Duplicate title/identity block appears before Chapter 3 | `PLAN:800-806` repeats the title, organization, and slogan already present at `PLAN:1-6` | Appears to be a merge artifact; does not remove content but affects clean heading structure | Documented only; not fixed per audit scope |
| Chapters 1 and 2 are plain text lines, not Markdown headings, while Chapters 3–12 use `#` headings | `PLAN:17`, `123` vs `PLAN:808` onward | Mixed heading style; automated chapter detection may miss Chapters 1–2 | Documented only; not fixed |
| Large blank-line gaps exist between many Arabic list items | e.g. `PLAN:27-54`, `PLAN:134-146` | Formatting noise; no content loss observed | Documented only; not fixed |
| `file` utility unavailable in environment | Shell check returned `file: command not found` | MIME label could not be obtained with that utility; UTF-8 validity and byte/line counts were verified independently | Recorded as tooling limitation, not a file defect |

**Integrity conclusion:** the strategic plan is complete enough for audit. The duplicate title block is recorded as an observed formatting/merge artifact; this report does not repair it.

---

## 3. Overall Coverage Assessment

### 3.1 Coverage profile by architectural strength

| Area | Overall assessment | Evidence |
|---|---|---|
| Platform layering, transactions, error/logging conventions | FULLY COVERED at design level | `BACK:52-91`, `BACK:99-137`, `BACK:195-236` |
| Custom RBAC, scopes, audit of sensitive reads/exports | FULLY COVERED at design level, with owner decisions open | `AUTHZ:7-18`, `AUTHZ:136-173`, `AUTHZ:192-292`, `AUTHZ:296-336` |
| Bilingual AR/EN, RTL/LTR, translations | FULLY COVERED at design level; default locale and several formatting gates open | `LOC:23-36`, `LOC:73-138`, `LOC:142-176`, `DB:273-288` |
| CMS/public website, media, SEO, menus, homepage | FULLY/PARTIALLY COVERED; content and search gates open | `DB:135-157`, `MAP:37-92`, `ROAD:88-117` |
| Security design | FULLY COVERED as requirements; not implemented | `SEC:7-212` |
| Communication/email/notification design | FULLY COVERED as requirements; critical-mail and push gates open | `COMM:7-239` |
| Design/motion system | FULLY COVERED, including OQ-34 asset categories and skeleton-pulse exception | `DESIGN:176-218`, `MOTION:32-72`, `MOTION:144-148`, `MOTION:262-266` |
| Programs/projects/activities generic lifecycle | PARTIALLY COVERED | `DB:159-179`, `MAP:123-129`, `BACK:178-187` |
| Humanitarian domain operations | PARTIALLY/NOT COVERED | `DB:38-63`, `MAP:21-31`, `ROAD:121-168` |
| Finance/donor/funding/inventory/distribution | NOT COVERED | No domain/module/permission/route/test found |
| MEAL/KPI/indicators/evaluations | NOT COVERED | `DB:263-269`, `MAP:204-213`, no definitions |
| Institutional structure/org chart | PARTIALLY/NOT/DEFERRED | `AUTHZ:111`, `OQ:67`, `MAP:46-50` |
| Implementation sequencing vs strategic plan phases | CONFLICT | `PLAN:905-999`, `PLAN:1183-1189`, `ROAD:19-32` |

### 3.2 Strategic/institutional register (A-01…A-12)

| ID | Requirement | Plan ref | Architecture ref | Class | Specific gap | Recommendation |
|---|---|---|---|---|---|---|
| A-01 | Vision, mission, values as publishable verified content | PLAN:17-59 | `MAP:46-50`, `DB:135-147`, `OQ:33-38` | P | CMS can host content, but no content-owner-approved record or mapping from values to system behavior | Add a verified-content catalog with owner, locale, source, and publication status |
| A-02 | 11 strategic objectives | PLAN:61-94 | `OQ:37` (`OQ-05`) | D | Publication as targets is gated; no objective entity | Resolve OQ-05; model objectives as planning records with target/result distinction |
| A-03 | Target groups and priority rules | PLAN:97-121 | `DB:181-195`, `SEC:162-180` | P | Beneficiary categories appear as application intake, not as target-group taxonomy | Add target-group taxonomy and objective eligibility linkage after privacy gate |
| A-04 | Board strategic oversight and approval | PLAN:814-821 | `DEP:158`, `OQ:118` | D | Approval authority is explicitly unknown under OQ-49 | Name approver through OQ-49; do not infer board identity or authority |
| A-05 | Executive director responsibilities | PLAN:823-830 | — | N | No executive role entity or responsibility matrix | Add role/responsibility catalog after OQ-19/OQ-23 |
| A-06 | Program/projects departments and units | PLAN:832-841 | `AUTHZ:31-35,105-111`, `OQ:67` | D | Department/section tables are conditional and excluded from initial migration | Resolve OQ-19 before modeling departments |
| A-07 | Partnerships/resource mobilization department | PLAN:843-850 | `MAP:131-134`, `DB:235-241` | P | Partner content module exists; no department/unit or funding-unit workflow | Add unit ownership to partner/funding records |
| A-08 | Media/communication department | PLAN:852-859 | `MAP:37-92`, `COMM:170-184` | P | Media/CMS modules exist; no departmental responsibility model | Add content ownership and approval delegation rules |
| A-09 | Finance/admin department | PLAN:861-868 | — | N | No finance, procurement, warehouse, or HR admin domain | Define finance/admin domain before implementation |
| A-10 | MEAL unit | PLAN:870-877 | `MAP:204-213` | N | Only generic dashboards/exports; no MEAL unit, cycle, or learning record | Define MEAL domain and workflows |
| A-11 | Research/needs-assessment unit | PLAN:879-886 | `MAP:85-88` | N | Studies appear only as publications; no research workflow | Define studies/surveys/needs-assessment domain |
| A-12 | Institutional capacity-building requirements | PLAN:890-901 | `SYS:58-69`, `TEST:55-80`, `DEP:150-212` | P | Technical controls exist; policies, job descriptions, adopted systems, and manuals are not modeled or approved | Add a governance/capability register with verified policy status only |

### 3.3 Count reconciliation

The detailed requirement registers in Sections 3–18 enumerate all 237 units exactly once: A-01…Q-12 plus R-01…R-05. `R-05` is the distinct strategic-adoption unit registered in §17.3; it is not a second copy of `Q-09` (Phase 1 approval). Roll-up matrices in §§3.1, 4–6, conflict rows in §19, and ID lists in §§18 and 22 reuse the same IDs; they are not recounted, and their summary classes do not override the single primary class recorded in each detailed register. Where a roll-up covers mixed classes, its label reflects the dominant or decision-relevant gating condition.

---

## 4. Chapter-by-Chapter Coverage Matrix

| Chapter | Requirement scope | PLAN ref | Main ARCH ref | Class | Coverage reason / specific gap | Recommendation |
|---|---|---|---|---|---|---|
| Introduction | Transition from relief to resilience, participatory methodology | 8-16 | `SYS:58-69` | P | Principles are reflected; participation workflows and community decision records are absent | Add community participation model |
| 1 — General strategic framework | Vision, mission, values, objectives, target groups | 17-121 | `MAP:46-50`, `OQ:33-38` | D | Objective publication and institutional claims are gated by OQ-01–06/OQ-05 | Resolve content owner and OQ-05 before strategic content entry |
| 2 — Pillars and executive programs | 11 pillars, programs, indicators | 123-799 | `DB:38-63`, `MAP:123-151`, `ROAD:121-145` | P | Generic projects/activities; most pillar-specific operations not modeled | Add strategy crosswalk and missing domains |
| 3 — Institutional/ administrative development | Org structure, units, capacity building | 808-902 | `AUTHZ:111`, `OQ:67` | P | Conditional departments only; roles are permission templates, not real people | Resolve OQ-19/OQ-23; model only verified structure |
| 4 — 2027 operational plan | Four quarterly phases and outputs | 905-999 | `ROAD:19-32` | C | Engineering phase scheme has no mapping to PLAN quarters; see Q-01 | Add phase crosswalk; do not equate P2a/P2b with plan quarters |
| 5 — Annual KPI targets | 13 planning targets and calculation controls | 1006-1033 | `DB:263-269`, `MAP:204-213` | N | No indicator/target/actual/dedup model | Define MEAL/KPI domain |
| 6 — Finance and budget | 100% distribution, funding sources, controls | 1037-1077 | `DB:27,163,169` | N | Only money format and project budget reference | Define finance/donor domain |
| 7 — Governance and transparency | 15 policies and accountability principles | 1080-1109 | `SEC:7-212`, `AUTHZ:277-309` | P | Technical governance exists; policy adoption and publication are not assumed | Add policy register with verified adoption status |
| 8 — Partnerships | Priority entities and management principles | 1122-1165 | `DB:235-241`, `MAP:131-134` | P | Partner content exists; potential/active lifecycle, MOUs, contacts, KPIs absent | Extend partner model |
| 9 — Expected results 2027–2029 | 10 expected results and maturity stages | 1168-1190 | `ROAD:19-32`, `SUM:71-78` | P | Results are not modeled as measurable results; stages not mapped | Map results to outcomes/indicators after MEAL design |
| 10 — MEAL | Monitoring mechanisms and five performance levels | 1193-1215 | `DB:263-269`, `MAP:204-213` | N | No MEAL entities, cycles, feedback loop, or lesson/corrective-action records | Define MEAL domain |
| 11 — Urgent execution priorities | 10 priorities | 1218-1231 | `ROAD:121-190` | P | Some priorities map to roadmap modules; funding/inventory/referral/research parts do not | Add missing priority coverage |
| 12 — Risk and continuity | 9 risks and mitigation actions | 1235-1261 | `SEC:64-86,184-192`, `DEP:192-197` | P | Security/backup controls exist; institutional risk register and continuity workflows absent | Add risk/continuity domain |
| Closing recommendation | Board/authorized bodies consider adoption | 1265-1277 | `DEP:158`, `OQ:118` | D | OQ-49 approver unknown; developer cannot approve or answer | Name owner via OQ-49; record approval explicitly |

---

## 5. Strategic Objectives Coverage Matrix

| Objective | PLAN ref | Architecture evidence | Class | Reason / gap | Recommendation |
|---|---|---|---|---|---|
| 1. Integrated humanitarian response and disaster management | 63 | `DB:159-195`, `MAP:123-151` | P | Projects/activities/beneficiary applications exist; no emergency teams, shelter registry, stock, distributions, or referral workflow | Add emergency operations domain |
| 2. Food security and living conditions | 66 | `DB:159-179`, `MAP:127-129` | P | Generic activity/reach only; no food aid, coupons, kitchens, nutrition cases, or supply chain | Add food security/distribution data model |
| 3. Economic empowerment, employment, anti-poverty | 69 | `MAP:123-129`, `DB:207-221` | P | Projects/activities and careers exist; no grants, small-business support, cooperatives, or income outcome tracking | Add livelihood and finance domains |
| 4. Social protection and psychosocial care | 72 | `DB:181-195`, `SEC:162-180` | D | Sensitive fields and care workflows are gated by OQ-24–OQ-29; no safeguarding case model | Resolve privacy gate; add protection workflows |
| 5. Public health, water, sanitation | 75 | `DB:171-179`, `MAP:127-129` | P | Generic activities/locations only; no WASH facility, water quality, health campaign case, or environmental site data | Add health/WASH domain |
| 6. Education and youth capacity | 78 | `DB:171-179`, `MAP:127-129` | P | Activities can record sessions; no student, school, scholarship, or youth initiative model | Add education/youth domain |
| 7. Early recovery, housing, community facilities | 81 | `DB:159-179`, `MAP:123-129` | P | Projects/activities exist; no asset condition assessment, rehabilitation workflow, or facility registry | Add asset/recovery domain |
| 8. Community participation, volunteering, participatory work | 84 | `DB:223-229`, `MAP:161-163` | P | Volunteers are covered; community committees, consultative decisions, and initiatives are not | Add committees/participation domain |
| 9. Partnerships and resource mobilization | 87 | `DB:235-241`, `MAP:131-134`, `F` register | P | Partner content exists; donors, proposals, grants, funding sources, and agreements are absent | Add donor/funding domain |
| 10. Media, communication, advocacy | 90 | `MAP:37-92`, `COMM:101-118` | P | CMS/media/email covered; advocacy campaigns, media monitoring, and policy briefs lack workflows | Add advocacy/campaign module |
| 11. Research, studies, needs assessment, impact | 93 | `MAP:85-88`, `DB:263-269` | N | Publications/export containers only; no survey, study, assessment, impact, or research database workflow | Define research/MEAL domain |

---

## 6. All 11 Strategic Pillars Coverage Matrix

| Pillar | PLAN ref | Intended programs/KPIs | Architecture evidence | Class | Main gap | Recommendation |
|---|---|---|---|---|---|---|
| 1. Humanitarian response, relief, disaster management | 125-202 | 4 programs; response time, coverage | `DB:159-195`, `MAP:123-151` | P | No emergency response lifecycle, field teams, stock, distributions, risk maps, or coordination referrals | Add emergency operations model |
| 2. Food security and nutrition | 204-273 | 4 programs; meals, households, regularity | `DB:159-179` | P | No household ration, coupon, kitchen, meal, nutrition case, or agriculture input records | Add food/nutrition domain |
| 3. Economic development and livelihoods | 275-346 | 4 programs; supported projects, jobs, six-month survival | `DB:159-179`, `DB:207-221` | P | No grants, livelihoods, cooperatives, market linkages, or income outcome tracking | Add livelihood/finance domain |
| 4. Social protection and psychosocial support | 348-421 | 4 programs; orphans, disability, referrals | `DB:181-195`, `SEC:162-180` | D | Sensitive personal data gated; no case management, guardianship, disability fields, or referral workflow | Resolve OQ-24–OQ-29; add case model |
| 5. Health, water, sanitation | 423-477 | 3 programs; campaigns, sites, beneficiaries | `DB:171-179` | P | No health/WASH facility, site, campaign outcome, or environmental intervention records | Add health/WASH domain |
| 6. Education and youth | 479-533 | 3 programs; students, spaces, youth initiatives | `DB:171-179` | P | Generic activities only; no learner, institution, scholarship, or youth initiative entity | Add education/youth domain |
| 7. Housing, early recovery, community rebuilding | 535-585 | 3 programs; housing units, facilities, jobs | `DB:159-179`, `MAP:123-129` | P | No asset assessment, rehab workflow, community facility lifecycle, or social-capital records | Add assets/recovery domain |
| 8. Community participation and volunteering | 587-639 | 3 programs; committees, volunteers, private sector | `DB:223-229`, `MAP:161-163` | P | Volunteers covered; committees, consultations, community proposals, CSR campaigns absent | Add participation/CSR domain |
| 9. Partnerships and funding | 641-717 | 4 programs; agreements, proposals, secured funding | `DB:235-241`, `MAP:131-134` | P | No donor/proposal/grant/funding-source model; potential vs active partner status incomplete | Add partnership/funding lifecycle |
| 10. Media, communication, advocacy | 719-773 | 3 programs; reports, campaigns, reach | `MAP:37-92`, `COMM:101-118` | P | CMS/media/email covered; campaigns, advocacy, media monitoring, and policy briefs not modeled | Add advocacy domain |
| 11. Research, studies, needs assessment | 775-799 | Activities; studies, reports, databases | `MAP:85-88`, `DB:263-269` | N | No survey/study/assessment/impact workflow or research database | Define research needs-assessment domain |

---

## 7. Programs & Activities Coverage

The following 39 rows are the master program/activity register (**C-01…C-39**). “P” indicates a generic architecture mechanism exists but plan-specific detail is missing; it does not mean a named module exists.

| ID | Program / activity group | PLAN ref | ARCH ref | Class | Specific gap | Recommendation |
|---|---|---|---|---|---|---|
| C-01 | Emergency relief interventions | 130-148 | `DB:159-195` | P | No emergency trigger, field team, dispatch, aid handover, or response-time model | Define emergency response workflow |
| C-02 | Winter and natural-disaster response | 149-164 | `DB:115-121,171-179` | P | Locations/activities exist; no hazard map, winter-item stock, or targeted dispatch | Add hazard and stock tracking |
| C-03 | Shelter/camp management | 166-183 | `DB:115-121,171-195` | P | No camp/center registry, capacity, service mapping, or displaced-household record | Add shelter/camp domain |
| C-04 | Coordination and joint response | 185-199 | `DB:235-241`, `MAP:131-134` | P | Partner content exists; no referral exchange, gaps report, or joint-plan workflow | Add coordination/referral model |
| C-05 | Food assistance | 209-224 | `DB:159-179` | P | No ration plan, coupon, household entitlement, or distribution record | Add food assistance domain |
| C-06 | Community kitchens and nutrition | 226-240 | `DB:171-179` | P | No kitchen, meal batch, menu, safety inspection, or beneficiary meal record | Add kitchen/meal model |
| C-07 | Home gardening/urban agriculture | 243-257 | `DB:171-179` | P | No input distribution, plot, training attendance, or harvest outcome | Add agriculture activity detail |
| C-08 | Nutrition and food protection | 260-271 | `DB:171-195` | P | No malnutrition case, referral, or specialized food basket model | Add nutrition case/referral |
| C-09 | Household economic empowerment | 280-295 | `DB:159-179` | N | No grant, business profile, equipment issuance, or post-funding mentoring model | Define livelihood finance domain |
| C-10 | Vocational training and employment | 297-312 | `DB:207-221` | P | Careers/jobs exist; no trainee, course, competency, job-placement outcome, or employer partnership record | Add training/employment model |
| C-11 | Small projects and cooperatives | 314-329 | `DB:159-179` | P | Generic projects only; no cooperative member, product, marketing, or advisory record | Add cooperative domain |
| C-12 | Economic recovery of affected households | 331-343 | — | N | No replacement-asset, restarted-business, or income-recovery tracking | Define economic recovery records |
| C-13 | Orphan care | 353-369 | `DB:181-195` | P | Application intake exists; no guardian, sponsorship, child well-being, or individual follow-up model after privacy gate | Resolve OQ gate; add child-care case model |
| C-14 | Disability care | 372-386 | `DB:181-195`, `OQ:75-76` | D | Vulnerability fields and assistive-device records are gated by OQ-24–OQ-29 | Resolve privacy gate before schema |
| C-15 | Psychosocial support | 389-401 | `DB:171-195` | P | No session plan, counsellor, case referral, or consent record | Add PSS case workflow |
| C-16 | Protection of women, children, elderly | 404-418 | `SEC:162-180`, `OQ:75-77` | D | Safeguarding/GBV/child-protection workflows absent and sensitive data gated | Resolve OQ-24–OQ-29; add protection case model |
| C-17 | Health awareness | 428-442 | `DB:171-179` | P | Activities can record campaigns; no health topic, reached-group, or behavior-outcome model | Add health campaign detail |
| C-18 | Water and environmental sanitation | 445-459 | `DB:115-121,171-179` | P | No water point, sanitation facility, waste site, or service-status record | Add WASH asset/site domain |
| C-19 | Environmental initiatives | 462-474 | `DB:171-179` | P | Generic activities only; no planting site, recycling stream, or pollution incident | Add environmental intervention records |
| C-20 | Education support | 484-498 | `DB:171-179` | P | No student, school, materials distribution, remedial program, or scholarship record | Add education domain |
| C-21 | Youth capacity building | 501-515 | `DB:171-179`, `MAP:161-163` | P | Activities/volunteers exist; no youth initiative, leadership curriculum, or outcome record | Add youth program detail |
| C-22 | Childhood and community activities | 518-528 | `DB:171-179` | P | Activity records exist; no child-safe space, safeguarding, or developmental outcome model | Add child activity safeguards after privacy gate |
| C-23 | Housing/shelter improvement | 540-552 | `DB:159-179` | P | No technical assessment, damage grade, repair item, or accessibility adaptation record | Add housing asset/rehab workflow |
| C-24 | Community recovery | 555-567 | `DB:159-179` | P | Generic projects only; no facility rehabilitation, service restoration, or community priority vote | Add community recovery model |
| C-25 | Rebuilding social capital | 570-580 | `DB:171-179` | P | Activities exist; no committee, mutual-aid group, or reintegration record | Add social-capital records |
| C-26 | Participatory committees | 592-604 | — | N | No committee entity, membership, meeting, decision, or community proposal workflow | Define participation domain |
| C-27 | Volunteer program | 607-621 | `DB:223-229`, `MAP:161-163` | F | Opportunities, applications, assignments, restricted records, and permissions are documented | Keep privacy retention decisions open |
| C-28 | Social responsibility/private sector | 624-634 | `DB:235-241`, `MAP:131-134` | P | Partner content exists; no CSR campaign, donation pledge, or business engagement workflow | Add CSR/funding engagement model |
| C-29 | Local partnerships | 646-658 | `DB:235-241` | P | Partner content exists; no agreement lifecycle, contact, or responsibility detail | Extend partner model |
| C-30 | International partnerships | 661-675 | `DB:235-241` | P | No donor-platform registration, conference, or funding relationship record | Add international funding/partner records |
| C-31 | Project development and resource mobilization | 678-695 | `MAP:123-125`, `COMM:101-118` | P | Project structure and email keys exist; no concept note, proposal, budget proposal, or grant tracker | Add proposals/funding pipeline |
| C-32 | Financial sustainability | 704-714 | — | N | No donor base, individual giving, crowdfunding, or diversification model | Define fundraising/finance domain |
| C-33 | Institutional media | 724-738 | `MAP:37-92`, `DB:135-157` | F | Pages, news, publications, media, SEO, workflow, and permissions are documented | Apply OQ-01–06/OQ-33/34/35 gates at content entry |
| C-34 | Humanitarian media and advocacy | 741-755 | `MAP:85-88`, `DB:135-147` | P | Publications can host reports; no advocacy campaign, policy brief workflow, or media-monitoring record | Add advocacy domain |
| C-35 | Institutional identity | 758-770 | `DESIGN:19-53,176-185`, `MOTION:171-183` | P | Design tokens and logo rules exist; font, logo motion, and actual imagery remain gated | Resolve OQ-33/34/35 when final content is entered |
| C-36 | Field surveys and needs mapping | 780-784 | — | N | No survey, survey response, needs map, or enumeration workflow | Define research/needs-assessment domain |
| C-37 | Thematic studies | 785-788 | `MAP:85-88` | N | Studies are only publication documents; no study lifecycle or analysis dataset | Add study workflow |
| C-38 | Displacement/shelter analysis and impact studies | 787-790 | — | N | No displacement analytics or impact evaluation entity | Add impact/evaluation domain |
| C-39 | Periodic reports, databases, academic cooperation | 791-794 | `MAP:85-88,131-134`, `DB:263-269` | P | Report/export and partner containers exist; no research database or academic cooperation workflow | Add research/report catalog |

---

## 8. Domain Model & Database Gap Analysis

### 8.1 Documented conceptual domains

`DB:38-63` defines 18 conceptual domains: IAM, System Configuration, CMS & Content, Navigation & Homepage, Media, Projects & Programs, Humanitarian Activities, Beneficiary Applications, Complaints, Tenders, Careers, Volunteers, News & Publications, Partnerships, Email, Notifications, Audit Logs, Analytics & Reporting. This is a sound generic platform model but not the plan’s operating model.

### 8.2 Entity-level register (B-01…B-24)

| ID | Entity / capability | PLAN ref | ARCH ref | Class | Gap / relation issue | Recommendation |
|---|---|---|---|---|---|---|
| B-01 | Programs | 128+, 641+ | `DB:159-169` | F | None at conceptual level | Define concrete fields and program→pillar linkage |
| B-02 | Projects | 128+, 678+ | `DB:159-169` | F | No explicit strategic-plan objective foreign key | Add objective/pillar linkage |
| B-03 | Activities | 130+ | `DB:171-179` | F | Aggregate reach only for beneficiaries | Keep personal links in restricted domain only |
| B-04 | Locations/geography | 161, 787 | `DB:115-121` | P | Map provider and location sensitivity undecided (OQ-36) | Resolve OQ-36; define publication granularity |
| B-05 | Beneficiaries as applications | 97-121, 100-118 | `DB:181-195` | P | Not a persistent beneficiary registry; gated by OQ-24–OQ-29 | Resolve privacy gate; define registry only if approved |
| B-06 | Families/households | 100-118, 1012 | — | N | No household entity or membership relation | Define family/household model after privacy gate |
| B-07 | Volunteers | 607-621 | `DB:223-229` | F | Retention policy open | Resolve OQ-26 |
| B-08 | Partners | 646-675, 1126-1164 | `DB:235-241` | P | No agreement, contact, lifecycle, or KPI fields | Extend partner model |
| B-09 | Donors | 843-850, 1054-1063 | — | N | No donor entity or funding relation | Define donor domain |
| B-10 | Finance/budgets/expenses | 1037-1077 | `DB:27,163,169` | N | Only money type and project budget reference | Define finance ledger and budget execution |
| B-11 | Distributions/aid dispatch | 134-159, 209-240 | — | N | No dispatch, receipt, or handover entity | Define distribution domain |
| B-12 | Inventory/warehouses | 144, 867, 1096, 1255 | — | N | No stock item, warehouse, movement, or count | Define inventory domain |
| B-13 | Complaints | 418, 1093, 1105 | `DB:197-205` | F | Anonymity/retention decisions open | Resolve OQ-26/OQ-29 |
| B-14 | Referrals | 193, 266, 399, 410 | — | N | No referral entity, status, or route | Define referral workflow |
| B-15 | Studies/needs assessments | 775-799, 879-886 | `MAP:85-88` | N | Publication shell only | Define study/survey domain |
| B-16 | Indicators/results/KPIs | 202, 273, 1006-1033 | `DB:263-269` | N | No indicator, baseline, target, actual, source, or verification fields | Define MEAL/KPI model |
| B-17 | Evaluations | 790, 983-998 | — | N | No evaluation entity or approval workflow | Define evaluation domain |
| B-18 | Reports/publications | 734, 792, 1104 | `DB:135-147,263-269`, `MAP:85-88` | P | Generic publication/export only; no report catalog or approval cycle | Add report/evaluation lifecycle |
| B-19 | Attachments/restricted documents | 181, 410, 691 | `DB:103-113` | F | Dedicated restricted tables and signed URLs are designed; owner confirmation open | Resolve owner confirmation |
| B-20 | Media registry | 736, 762 | `DB:103-113`, `ADR:95-104` | F | Explicit pivots designed; imagery rights not approved | Apply OQ-34/OQ-06 gates |
| B-21 | Translations | 768, 916-917 | `DB:273-288`, `LOC:73-109` | F | Fallback and ownership rules designed; several locale gates open | Resolve OQ-31/32/35/37/38 |
| B-22 | Audit logs | 1080-1099 | `DB:123-129`, `AUTHZ:277-292`, `ADR:183-192` | F | Retention unresolved; not full external immutability | Resolve OQ-26; document compensating-entry semantics |
| B-23 | Email/notification logs | 742-756 | `DB:243-257`, `COMM:148-166,188-220` | F | Provider/push/masking gates open | Resolve OQ-40/42/45 |
| B-24 | Metric snapshots/report exports | 1006-1033, 1203 | `DB:263-269` | P | Aggregates only; no definitions, formulas, or source verification | Define snapshot definitions and calculation rules |

### 8.3 Missing entities and unclear relations

- **Missing entities:** families, donors, finance accounts/transactions, funding opportunities, proposals, grants, expenses, financial reports, distributions, inventory items, warehouses, stock movements, referrals, studies, surveys, needs assessments, indicators, baselines, targets, actual values, outputs/outcomes/impact records, evaluations, lesson-learned/corrective-action records, institutional risk register, community committees, field teams, shelter/camp records, asset condition assessments, advocacy campaigns.
- **Unclear relations:** plan pillar/objective → program → project → activity → indicator; beneficiary/family → eligibility → intervention → distribution → outcome; donor → funding agreement → project budget → expense; partner → MOU → responsibility → KPI; complaint → referral → response → appeal; study → needs assessment → program design → impact evaluation.

---

## 9. Dashboard & Workflow Gap Analysis

### 9.1 Dashboard register (N-01…N-11)

`MAP:204-213` defines only generic **I1 Dashboards** and **I2 Report exports**: role-appropriate, aggregated, non-identifying overviews; queued exports with expiring signed downloads and audit. No dashboard page inventory, widget specification, KPI definition, filter set, or metric query is provided.

| ID | Dashboard required by plan | PLAN ref | Info expected | Filters / permissions / export expected | Class | Documented state / gap | Recommendation |
|---|---|---|---|---|---|---|---|
| N-01 | Senior management | 1203 | Program, finance, MEAL, risk overview | Role-scoped; export | P | Generic I1 only | Define executive widget set |
| N-02 | Executive management | 823-830, 1203 | Operations and performance | Role-scoped | P | Generic I1 only | Define management views |
| N-03 | Programs/projects | 1203 | Progress, activities, locations | Project/status/location filters | P | `MAP:123-129`, `DB:159-179` provide data but no widgets | Specify widgets/queries |
| N-04 | Finance | 1037-1077 | Budget execution, funding, expenses | Finance permission; export | N | No finance domain | Define finance dashboard after finance model |
| N-05 | MEAL | 1006-1033, 1193-1215 | Indicators, targets, actuals, impact | Indicator/period filters; export | N | No indicators | Define MEAL dashboard after MEAL model |
| N-06 | Partnerships/funding | 641-717 | Agreements, proposals, secured funding | Partner/donor filters | N | No donor/funding model | Define funding dashboard |
| N-07 | Media | 719-773 | Campaign reach, publications | Media permission | N | No analytics for campaigns | Add media analytics model |
| N-08 | Beneficiary management | 97-121, 1006 | Aggregate counts only | Sensitive; no PII; audited | P | `MAP:206-209` explicitly forbids personal data in dashboards | Define aggregate-only metrics after privacy gate |
| N-09 | Volunteers | 607-621 | Registered/active volunteers | Volunteer scope | P | Volunteer module exists; no dashboard | Add volunteer widgets |
| N-10 | Complaints | 418, 1093 | Counts, status, SLA | Confidential; no sensitive disclosure | P | `MOTION:249-258` only an illustrative complaints dashboard | Define complaint metrics and reports |
| N-11 | System administration | H3, 187-200 | Queue, scheduler, cache, backup health | Settings/admin permission | P | `MAP:187-200` references health only; H2 permission undecided | Define admin health dashboard and owner |

### 9.2 Workflow register

| Workflow | ARCH evidence | Class | Gap |
|---|---|---|---|
| Content workflow | `BACK:178-187`, `DB:95-101`, `MAP:219` | P | Status vocabulary mismatch; guard classes and per-entity transition table not named |
| Project/program workflow | `DB:159-169`, `MAP:123-125` | P | No plan-specific funding/approval stages |
| Beneficiary application workflow | `DB:181-195`, `MAP:144-147` | D | Gated by OQ-24–OQ-29; no eligibility/verification model |
| Complaint workflow | `DB:197-205`, `MAP:157-159` | F | Core intake/triage/assignment/response/resolution designed; appeals absent |
| Volunteer workflow | `DB:223-229`, `MAP:161-163` | F | Opportunities/applications/assignments designed |
| Tender/career workflows | `DB:207-221` | F | Designed; consent/retention gates open |
| Finance/distribution/referral/study/MEAL workflows | — | N | Absent |

---

## 10. Permissions & Security Gap Analysis

### 10.1 Permission register (O-01…O-10)

| ID | Requirement | ARCH evidence | Class | Specific gap | Recommendation |
|---|---|---|---|---|---|
| O-01 | Deny by default | `AUTHZ:12,228-257` | F | None | Keep mandatory authz tests |
| O-02 | Code-owned permission vocabulary | `AUTHZ:136-173` | F | None | Extend vocabulary with missing domains |
| O-03 | Roles/templates | `AUTHZ:181-186`, `OQ:35` | P | Templates are not real people or verified roles | Resolve OQ-19/OQ-23 before assignment |
| O-04 | Data scopes | `AUTHZ:192-222` | P | Department/section scopes conditional on OQ-19 | Resolve OQ-19 |
| O-05 | Backend sole authority | `SYS:59`, `AUTHZ:13,228-257` | F | None | Preserve |
| O-06 | Frontend checks UI-only | `FE:121-136`, `AUTHZ:257,270` | F | None | Preserve F-12 test |
| O-07 | Audit authz changes | `AUTHZ:277-292` | F | Retention open | Resolve OQ-26 |
| O-08 | Separation of duties | `AUTHZ:296-309`, `SEC:17` | F | Generic; not finance/COI-specific | Add domain-specific SoD |
| O-09 | Protected capability holders | `AUTHZ:305`, `OQ:71` | D | OQ-23 open | Name holders via owner decision |
| O-10 | Mapping to plan job descriptions | PLAN:890-901 | N | No job-description/permission matrix | Add after org structure is verified |

### 10.2 Security register (M-01…M-12)

| ID | Requirement | ARCH evidence | Class | Gap / gate | Recommendation |
|---|---|---|---|---|---|
| M-01 | RBAC | `AUTHZ:7-352` | F | Owner decisions open | Preserve design; resolve OQ-17/19/23 |
| M-02 | Data classification | `SEC:162-180` | F | Four classes defined | Apply to new domains |
| M-03 | Audit logs | `DB:123-129`, `ADR:183-192` | F | Retention open | Resolve OQ-26 |
| M-04 | Sensitive read/export audit | `AUTHZ:282,290`, `SEC:114-115` | F | Two-person export rule open | Resolve OQ-24 |
| M-05 | Permission-change audit | `AUTHZ:277-292` | F | — | Keep |
| M-06 | Session management | `AUTHZ:119-130`, `SEC:90-103` | F | MFA/idle timeout open | Resolve OQ-18 and timeout decision |
| M-07 | File protection | `DB:103-113`, `SEC:134-144` | F | Storage method OQ-09 open | Resolve OQ-09 |
| M-08 | Encryption | `SEC:177`, `OQ:76` | D | Field encryption and APP_KEY custody gated | Resolve OQ-28/OQ-30 before schema |
| M-09 | Backups | `DEP:47,192-197` | D | Encrypted backups/restore test owned later | Resolve OQ-14 |
| M-10 | Retention | `DB:292-303`, `OQ:74` | D | Retention periods unanswered | Resolve OQ-26 |
| M-11 | Incident response | `SEC:184-192`, `OQ:78` | D | Runbook/security owner pending | Resolve OQ-30 |
| M-12 | Dependency audits | `TEST:14,30,106`, `DEP:18,170`, `SEC:205` | P | `composer audit`/`npm audit` status unverified from Phase 0 timeouts | Run and record after authorization; no clean claim now |

### 10.3 Permission/resources needing addition or clarification

Add planned permission families for: `families.*`, `beneficiaries.*` beyond applications, `donors.*`, `funding_opportunities.*`, `proposals.*`, `grants.*`, `finance.*`, `budgets.*`, `expenses.*`, `distributions.*`, `inventory.*`, `warehouses.*`, `stock_movements.*`, `referrals.*`, `studies.*`, `surveys.*`, `needs_assessments.*`, `indicators.*`, `targets.*`, `results.*`, `evaluations.*`, `risks.*`, `committees.*`, `campaigns.*`, `appeals.*`. Add finance-specific SoD and complaint-specific conflict-of-interest rules.

---

## 11. MEAL & KPI Coverage

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap | Recommendation |
|---|---|---|---|---|---|---|
| E-01 | Manage strategic plan 2027–2029 | 4-6, 800-806 | `OQ:37` | D | OQ-05 publication gate | Resolve OQ-05 |
| E-02 | Annual operational plans | 905-999 | — | N | No annual plan entity | Add planning-year model |
| E-03 | Goals/objectives as records | 61-94 | — | N | No objective entity | Add objective hierarchy |
| E-04 | Program/activity scheduling | 905-999 | `DB:159-179` | P | Dates exist; no milestone/quarter model | Add milestones |
| E-05 | Responsibilities | 914-915, 832-886 | `AUTHZ:181-186` | P | Role templates only | Map verified owners |
| E-06 | Timelines | 909-999 | `DB:159-179` | P | Project dates only | Add plan timelines |
| E-07 | Targets | 1006-1024 | — | N | No target records | Add targets |
| E-08 | KPI definitions | 202, 273, 1006-1033 | — | N | No indicator registry | Add indicators |
| E-09 | Budgets in plans | 1037-1077 | `DB:163` | N | Single budget reference only | Add finance model |
| E-10 | Risks in plans | 1235-1261 | `SEC:64-86` | N | Security threats are not institutional risk register | Add risk register |
| E-11 | Completion percentages | 905-999 | `DB:167` | P | Progress notes without formula | Define calculation |
| E-12 | Status separation: planned/draft/approved/funded/in-progress/completed/suspended/archived | 905-999 | `BACK:182`, `DB:97-99` | P | Workflow statuses omit funded/suspended/planned distinction | Extend status map |
| E-13 | Baselines, actuals, outputs, outcomes, impact | 1208-1214 | — | N | Five-level model absent | Add MEAL result hierarchy |
| E-14 | Monthly/quarterly/annual MEAL cycles, feedback, lessons, corrective actions | 1197-1206 | `MAP:204-213` | N | No cycle or learning records | Add MEAL cycle workflow |

**KPI computation verdict:** the plan’s indicators cannot be calculated from the documented architecture. There is no target value, baseline, actual value, unique-beneficiary dedup rule, data source, verification method, reporting period, or result hierarchy. `metric_snapshots` is only an optional aggregated container (`DB:265`).

---

## 12. Finance & Donor Management Coverage

### 12.1 Finance/donor register (F-01…F-12)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap | Recommendation |
|---|---|---|---|---|---|---|
| F-01 | General budget and 100% distribution | 1039-1052 | — | N | No budget model | Define budget domain |
| F-02 | Project budgets | 1068 | `DB:163,169` | P | Budget reference and retention decision only | Add budget lines and execution |
| F-03 | Funding sources | 1054-1064 | — | N | No funding-source records | Add funding sources |
| F-04 | Donors | 843-850, 917, 928 | — | N | No donor entity | Add donor domain |
| F-05 | Funding opportunities | 683, 847 | — | N | No opportunity tracker | Add opportunity pipeline |
| F-06 | Proposals/concept notes | 685-691, 922 | — | N | No proposal entity | Add proposal workflow |
| F-07 | Grants/agreements | 691, 944, 1069 | `DB:239` (optional `partnership_records`) | P | No fields or grant lifecycle | Define grant/agreement records |
| F-08 | Expenses/transactions | 864, 1070-1075 | — | N | No ledger | Define finance ledger |
| F-09 | Financial reports to donors | 691, 1074 | — | N | No financial report model | Add financial reporting |
| F-10 | Budget distribution percentages | 1041-1052 | — | N | Not modeled | Add allocation model with proposed/approved distinction |
| F-11 | No commitment without coverage/approval | 1076 | — | N | No commitment control | Add approval/commitment workflow |
| F-12 | Separation of financial and administrative authority | 864, 1071 | `AUTHZ:296-309` | P | Generic SoD only | Add finance-specific SoD |

### 12.2 Partnership register (G-01…G-09)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap | Recommendation |
|---|---|---|---|---|---|---|
| G-01 | Potential partner register | 1126-1154 | `DB:235-241`, `MAP:131-134` | P | Content module exists; no explicit potential state | Add partner status lifecycle |
| G-02 | Active vs potential distinction | 1155 | `DB:239` | P | No lifecycle status; only publication control | Add `potential/active/inactive/ended` with evidence |
| G-03 | Agreements/MOUs | 1160 | `DB:239` | P | Optional record with no fields | Define agreement fields |
| G-04 | Contact persons | 1126-1164 | — | N | No partner contact model | Add partner contacts |
| G-05 | Party responsibilities | 1159 | — | N | No responsibility record | Add responsibility matrix |
| G-06 | Agreement duration | 1159-1164 | — | N | No start/end/renewal model | Add dates and renewal state |
| G-07 | Partnership status | 1161-1164 | `DB:239` | P | Publication status is not partnership status | Add lifecycle status |
| G-08 | Partnership KPIs | 1161 | — | N | No KPI linkage | Add partner KPI records |
| G-09 | Potential partners not shown as actual | 1155 | `MAP:131-134`, `DB:239`, `OQ:36` | F | Explicit empty-state and no-invented-endorsement rule | Preserve; require evidence before publication |

**Institutional safeguard:** the architecture correctly avoids presenting potential partners, donors, funding, or targets as actual. No such claim is made in this report.

---

## 13. CMS & Bilingual Website Coverage

### 13.1 CMS register (I-01…I-15)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap / gate | Recommendation |
|---|---|---|---|---|---|---|
| I-01 | Public website | 730 | `DB:135-157`, `MAP:37-61` | F | Provider-neutral single app | Preserve |
| I-02 | Institutional pages | 762-770 | `DB:135-147`, `OQ:33-38` | P | Content owner/source gated | Resolve OQ-06 |
| I-03 | News | 734, 742 | `DB:135-147`, `MAP:81-83` | F | — | Apply publication workflow |
| I-04 | Reports/publications | 734, 1104 | `MAP:85-88` | P | Generic document publishing only | Add report catalog |
| I-05 | Projects | 730 | `DB:159-169`, `MAP:52-56,123-125` | F | — | Add verified content only |
| I-06 | Programs | 641+ | `DB:159-169` | F | — | Add pillar/objective links |
| I-07 | Partners | 646-675 | `DB:235-241` | P | Status/agreement detail absent | Extend partner model |
| I-08 | Volunteer opportunities | 607-621 | `DB:223-229`, `MAP:161-163` | F | — | Apply retention policy |
| I-09 | Careers | 843-850 (dept), `DB:215-221` | `DB:215-221` | F | — | Apply HR retention decision |
| I-10 | Tenders | 1096 | `DB:207-213`, `MAP:181-183` | F | Sealed applications designed | Preserve |
| I-11 | Campaigns | 751, 751-753 | — | N | No campaign entity | Add advocacy campaign domain |
| I-12 | Success stories | 738 | `DB:135-147`, `OQ:34` | P | Content can be published; consent/rights gated | Resolve OQ-06/OQ-34 |
| I-13 | Media | 736 | `DB:103-113`, `MAP:90-92` | F | Rights evidence gated for actual photography | Apply three-category imagery rule |
| I-14 | SEO, menus, homepage | 730 | `DB:149-157`, `LOC:125-138`, `MAP:37-79` | F | Search design gated by OQ-39 | Resolve OQ-39 before search UI/schema freeze |
| I-15 | Manage content without code changes | 724-738 | `DB:135-157`, `MAP:65-92` | F | CMS/admin model designed | Preserve no-arbitrary-HTML rule |

### 13.2 Localization register (J-01…J-10)

| ID | Requirement | ARCH evidence | Class | Gap / gate | Recommendation |
|---|---|---|---|---|---|
| J-01 | Arabic and English | `LOC:23-36,73-109`, `ADR:29-38` | F | — | Keep parity tests |
| J-02 | RTL/LTR | `LOC:113-121`, `FE:90-91,162` | F | — | Keep logical-property rule |
| J-03 | Content translation | `DB:273-288`, `LOC:73-97` | F | — | Keep no-synthesis fallback |
| J-04 | Menus and pages translation | `DB:149-157,273-288` | F | — | Preserve |
| J-05 | Email translation | `COMM:60-99`, `LOC:142-150` | F | Critical-mail fallback designed | Resolve OQ-10/11/15/40 |
| J-06 | Notification translation | `COMM:188-220` | F | — | Preserve dispatch-time locale |
| J-07 | Report translation | `DB:273-288`, `MAP:85-88` | P | No report-template translation specification | Add report catalog with locale fields |
| J-08 | Search | `LOC:137-138`, `OQ:94` | D | OQ-39 open; no search UI before decision | Resolve OQ-39 |
| J-09 | URLs, canonical, hreflang | `LOC:23-36,125-138` | F | — | Preserve |
| J-10 | Default language | `LOC:11-17,171`, `OQ:86` | D | Current baseline `en`; Arabic default planned under OQ-31 | Resolve OQ-31 |

**Imagery and content safeguard:** `DESIGN:176-218`, `ROAD:99-105`, `DEP:206-212`, and `VERIFY:41-85` distinguish synthetic non-public prototype imagery, authored neutral design assets, and actual institutional field photography/rights-bearing illustration. OQ-34 remains `DEFERRED` and conditional; no consent, license, or approval is invented here.

---

## 14. Governance & Approval Lifecycle

### 14.1 Lifecycle register (H-01…H-10)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap / reason | Recommendation |
|---|---|---|---|---|---|---|
| H-01 | Draft → Pending Review → Approved → Published → Archived | 812-821, 1071-1077, 1271 | `BACK:178-187`, `DB:95-101`, `DESIGN:159-163` | C | Architecture uses `submitted/in_review/changes_requested/rejected`; no mapping for “Pending Review” | Add explicit status crosswalk or rename with owner approval |
| H-02 | Create rights | 812-886 | `AUTHZ:136-173`, `MAP:65-213` | P | Permission strings exist; no verified role/unit mapping | Map after OQ-19/OQ-23 |
| H-03 | Edit rights | 812-886 | `AUTHZ:144-159` | P | Same as H-02 | Same |
| H-04 | Review rights | 818, 819, 875 | `AUTHZ:144-159`, `BACK:182-184` | P | Reviewer template only; guard classes unnamed | Define per-resource reviewers |
| H-05 | Approval authority | 818, 1271 | `DEP:158`, `OQ:118` | D | OQ-49 approver unknown | Name owner via OQ-49; developer not approver |
| H-06 | Publish rights | 1271 | `AUTHZ:144-159`, `BACK:185` | F | `{resource}.publish` and approved-before-publish rule | Preserve |
| H-07 | Archive rights | 1271 | `AUTHZ:144-159`, `DB:95-101` | F | Non-destructive archive | Preserve |
| H-08 | Change log | 1080-1109 | `AUTHZ:277-292`, `DB:123-129` | F | Unified audit design | Preserve |
| H-09 | Separation of roles | 1071, 1084 | `AUTHZ:296-309`, `SEC:17` | F | Generic SoD designed | Add finance/protection specifics |
| H-10 | Prevent approving beyond authority | 818-821 | `AUTHZ:228-257`, `TEST:59-62` | F | Backend authority + mandatory authz tests | Preserve |

### 14.2 Supporting registers placed here

**Complaints & accountability (K-01…K-10):**

| ID | Requirement | ARCH evidence | Class | Gap |
|---|---|---|---|---|
| K-01 | Complaint intake | `DB:197-205`, `MAP:157-159` | F | — |
| K-02 | Classification | `DB:201-203` | F | Category/severity enums |
| K-03 | Confidentiality | `SEC:162-180`, `DB:203-205` | F | — |
| K-04 | Referral to other bodies | — | N | No referral entity |
| K-05 | Processing workflow | `DB:197-205` | F | — |
| K-06 | Status tracking | `DB:199-203` | F | Status events |
| K-07 | Responses | `DB:199-201` | F | Message thread |
| K-08 | Appeals / تظلمات | — | N | No appeal entity |
| K-09 | Conflict-of-interest prevention | `AUTHZ:296-309` | P | Generic SoD only |
| K-10 | Reports without sensitive disclosure | `DB:346-354`, `MAP:206-209` | F | No raw PII in analytics |

**Media/communications (L-01…L-10):**

| ID | Requirement | ARCH evidence | Class | Gap / gate |
|---|---|---|---|---|
| L-01 | File/image management | `DB:103-113`, `SEC:134-144` | F | — |
| L-02 | Approval and publication rights | `AUTHZ:144-159`, `BACK:178-187` | F | — |
| L-03 | Rights/licensing evidence for actual field imagery | `DESIGN:180-183`, `OQ:89` | D | OQ-34/OQ-06 gated |
| L-04 | Bilingual email templates | `COMM:60-99` | F | — |
| L-05 | System notifications | `COMM:188-220` | F | — |
| L-06 | Send logs | `COMM:148-166` | F | — |
| L-07 | Failure/retry | `COMM:134-144` | F | — |
| L-08 | Targeted user notifications | `COMM:208-218` | F | — |
| L-09 | Push notifications | `OQ:104`, `COMM:212` | D | OQ-42 deferred |
| L-10 | Newsletters/subscriptions | `OQ:103`, `COMM:118` | D | OQ-41 deferred |

---

## 15. Privacy & Beneficiary Protection

### 15.1 Beneficiary/protection register (D-01…D-12)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap / gate | Recommendation |
|---|---|---|---|---|---|---|
| D-01 | Family/individual registration | 97-118, 918, 946 | `DB:181-195` | D | Sensitive schema gated by OQ-24–OQ-29; no family entity | Resolve privacy gate before schema |
| D-02 | Beneficiary files | 100-118 | `DB:181-195` | D | Application intake only | Define approved registry fields |
| D-03 | Eligibility criteria | 121, 223 | — | N | No criteria model | Add eligibility rules with transparent versioning |
| D-04 | Needs assessment | 121, 775-799 | — | N | No assessment entity | Define assessment domain |
| D-05 | Verification/deduplication | 1028-1029 | `DB:353` | N | Dedup keys mentioned for reporting, not beneficiaries | Define beneficiary dedup rules after privacy gate |
| D-06 | Link beneficiary to interventions | 100-118 | `DB:171-179` | P | Activity beneficiaries are count-only aggregates | Add approved linkage model |
| D-07 | Prior aid history | 100-118 | — | N | No assistance history | Add aid history after privacy gate |
| D-08 | Referrals | 193, 266, 399, 410 | — | N | No referral workflow | Define referral domain |
| D-09 | Child/vulnerable-group data protection | 404-421, 1091 | `SEC:162-180`, `OQ:75-77` | D | Field set and safeguarding workflows gated | Resolve OQ-24–OQ-29 |
| D-10 | Consent and privacy | 51, 738, 1092 | `OQ:74-77`, `DESIGN:180-183` | D | No institutional consent approval invented | Obtain owner decisions |
| D-11 | Access control to beneficiary data | 1092, 1248 | `AUTHZ:192-222,277-292` | F | Permission+scope+audit designed | Preserve; resolve OQ-24 for export |
| D-12 | Retention/anonymization/erasure | 1092, 1248 | `DB:292-303`, `OQ:74-76` | D | OQ-26/27/28 open | Resolve before sensitive data |

### 15.2 Privacy conclusion

Architecture correctly treats beneficiary/complaint/CV/volunteer/tender records as restricted and blocks personal-data migration until OQ-24→OQ-29 are answered (`DB:195,303,329,340`, `SEC:180`, `ROAD:133-134`, `DEP:174-178`). However, the plan’s substantive beneficiary-protection workflows—eligibility, verification, referrals, prior aid history, safeguarding, child protection, GBV referral, and appeal—are not modeled. Privacy gating must not be used as a reason to invent institutional approvals, but it also does not remove the need to design those workflows after the gate is resolved.

---

## 16. Routes & Frontend Pages Coverage

### 16.1 Route/page register (P-01…P-10)

| ID | Requirement | ARCH evidence | Class | Documentation status / gap | Recommendation |
|---|---|---|---|---|---|
| P-01 | Backend routes | `BACK:99-115`, `SYS:131-150` | P | Illustrative project routes only | Add route inventory per module |
| P-02 | Frontend pages | `FE:24-75` | P | Proposed page tree, not complete plan coverage | Add plan-specific pages |
| P-03 | Dashboard navigation | `FE:86`, `DESIGN:131-137`, `AUTHZ:313-320` | P | Permission-filtered sidebar concept; no full nav inventory | Define nav matrix |
| P-04 | Forms | `BACK:99-115`, `BACK:121-136`, `DESIGN:121-129` | P | One project FormRequest example and form rules | Add form inventory for missing domains |
| P-05 | Detail views | `FE:65-75` | P | Naming convention; no detail-view specifications | Add detail view contracts |
| P-06 | Workflow actions | `BACK:178-187` | P | `TransitionWorkflow` concept; guard classes unnamed | Name guards and transitions |
| P-07 | Reports | `MAP:211-213`, `DB:263-269` | P | Export container only | Define report pages/forms |
| P-08 | Finance routes/pages | — | N | Absent | Add after finance model |
| P-09 | Inventory/distribution routes/pages | — | N | Absent | Add after domains defined |
| P-10 | Research/MEAL routes/pages | — | N | Absent | Add after MEAL model |

No routes or components were created. This section documents only what architecture text describes.

---

## 17. Architecture Dependencies

### 17.1 Documented dependencies

| Dependency | Evidence | Status |
|---|---|---|
| OQ-49 approver → every phase transition and ADR acceptance | `ROAD:3,238`, `OQ:118,129`, `ADR:3,196-218` | OPEN; blocks formal authorization |
| Shared RBAC/audit/localization before business modules | `SYS:111-125`, `MAP:217-225`, `ROAD:36-65` | Designed |
| Provider-neutral capability checks at dependent points; canonical gate before go-live | `DEP:150-212`, `OQ:122-129`, `ROAD:194-211` | Gate unchecked |
| Privacy gate OQ-24→OQ-29 before beneficiary/complaint sensitive schema | `DB:195,303,329,340`, `ROAD:133-134`, `SEC:180` | OPEN |
| Content owner OQ-06 + OQ-01–06 before public institutional content | `OQ:29-39`, `ROAD:99-104`, `DEP:206-209` | OPEN |
| Fonts OQ-33, imagery OQ-34, logo motion OQ-35 before specific assets | `DESIGN:206-218`, `ROAD:101-103` | OPEN / OQ-34 DEFERRED |
| Search OQ-39 before CMS schema freeze and search UI | `DB:143,313`, `LOC:138,174`, `MAP:52-56` | OPEN |
| Maps OQ-36 before location/map provider assumptions | `MOTION:162-167`, `OQ:91`, `DEP:211` | OPEN |
| Critical mail path OQ-10/11/15/40 before account-security flows go live | `COMM:86-99,134-144`, `ROAD:69-84` | OPEN |
| PHP 8.3–8.5 compatibility check tooling in Phase 2a | `TEST:29,94,107`, `ROAD:46,61` | Tool/command not yet recorded |
| Dependency audits clean/documented before production | `TEST:106`, `DEP:170`, `ROAD:201` | Unverified from Phase 0 timeouts |

### 17.2 Readiness register (Q-01…Q-12)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap / reason | Recommendation |
|---|---|---|---|---|---|---|
| Q-01 | Execution ordering matches plan phases | 905-999, 1183-1189 | `ROAD:19-32` | C | No crosswalk between plan quarters/years and engineering phases | Add explicit crosswalk |
| Q-02 | Incremental build without major rework | 905-999 | `MAP:217-225`, `ROAD:7-15` | F | Shared infra once; module boundaries defined | Preserve |
| Q-03 | Module dependencies documented | 810-902 | `MAP:37-225` | F | Dependencies listed per module | Extend to new domains |
| Q-04 | Operational requirements addressed | 1235-1261 | `DEP:30-51,150-212` | P | Provider capabilities unconfirmed | Resolve at dependency points/canonical gate |
| Q-05 | Privacy before sensitive data | 1092, 1248 | `DB:195,303`, `SEC:180`, `ROAD:133` | F | Gate designed | Preserve |
| Q-06 | Verified content before public content | 730-770 | `ROAD:99-104`, `DEP:206-212` | F | Gate designed | Preserve |
| Q-07 | Hosting/provider readiness | 1235-1261 | `DEP:5,20-26,182` | D | Provider not selected | Resolve OQ-07 etc.; no capability assertion |
| Q-08 | PHP runtime/portability explanation | — | `ADR:7-16`, `SYS:26,33`, `DEP:13,36`, `VERIFY:160-189` | F | 8.5.12 verified; 8.3 floor explained | Keep distinction explicit |
| Q-09 | Phase 1 approval | 1265-1277 | `DEP:158`, `OQ:118`, `VERIFY:265-274` | D | OQ-49 OPEN | Name owner; record approval; no self-approval |
| Q-10 | ADR acceptance | — | `ADR:3,196-218` | D | All PROPOSED | Human acceptance required |
| Q-11 | No automatic phase progression | — | `ROAD:15,238`, `SUM:82-86` | F | Explicitly documented | Preserve |
| Q-12 | No fake institutional data | 1-16, 1155, 1271 | `SYS:58,176`, `OQ:33-38`, `TEST:127` | F | Explicitly prohibited | Preserve |

### 17.3 Strategic adoption decision register (R-05)

| ID | Requirement | PLAN ref | ARCH evidence | Class | Gap / reason | Recommendation |
|---|---|---|---|---|---|---|
| R-05 | Board/authorized bodies consider adoption of the 2027–2029 strategic framework and 2027 operational plan | 1265-1277 | `DEP:158`, `OQ:118,129` | D | OQ-49 approver/authority unknown; the developer cannot record institutional adoption, assign executive tasks, or invent approval | Name owner via OQ-49; record explicit adoption decision. This unit is distinct from `Q-09`, which concerns Phase 1 approval |

---

## 18. Missing Requirements

### 18.1 NOT COVERED (57 units)

| Group | IDs | Missing requirements |
|---|---|---|
| Institutional | A-05, A-09, A-10, A-11 | Executive director, finance/admin department, MEAL unit, research unit |
| Humanitarian domains | B-06, B-09…B-12, B-14…B-17 | Families, donors, finance, distributions, inventory, referrals, studies, indicators, evaluations |
| Programs | C-09, C-12, C-26, C-32, C-36…C-38 | Economic empowerment grants, economic recovery, committees, financial sustainability, field surveys, thematic studies, displacement/impact studies |
| Beneficiary protection | D-03…D-05, D-07, D-08 | Eligibility, needs assessment, dedup, prior aid history, referrals |
| Planning/MEAL | E-02, E-03, E-07…E-10, E-13, E-14 | Annual plans, objectives, targets, KPIs, budgets, risks, result hierarchy, MEAL cycles |
| Finance/donor | F-01, F-03…F-06, F-08…F-11 | Budget, funding sources, donors, opportunities, proposals, expenses, reports, allocations, commitments |
| Partnerships | G-04…G-06, G-08 | Contacts, responsibilities, duration, KPIs |
| CMS | I-11 | Campaigns |
| Complaints | K-04, K-08 | Referrals, appeals |
| Dashboards | N-04…N-07 | Finance, MEAL, partnerships/funding, media dashboards |
| RBAC | O-10 | Job-description/permission mapping |
| Routes/pages | P-08…P-10 | Finance, inventory/distribution, research/MEAL routes/pages |

### 18.2 DEFERRED (26 units)

| Gate | IDs | Requirement area |
|---|---|---|
| OQ-05 / OQ-49 | A-02, A-04, E-01, H-05, Q-09, Q-10, R-05 | Strategic objectives, approval authority, plan management, ADR/phase acceptance, strategic-plan adoption |
| Org/role decisions | A-06, O-09 | Departments, protected capability holders |
| Privacy OQ-24–OQ-29 | C-14, C-16, D-01, D-02, D-09, D-10, D-12 | Beneficiary/protection data and sensitive fields |
| Localization/content/assets | J-08, J-10, L-03, L-09, L-10 | Search, default language, imagery rights, push, newsletter |
| Security/operations | M-08, M-09, M-10, M-11 | Encryption, backups, retention, incident response |
| Provider | Q-07 | Hosting/provider readiness |

**Primary-class correction:** `B-05` (§8.2), `C-35` (§7), `I-02` and `I-12` (§13.1) are **PARTIALLY COVERED** units whose deferred sub-aspects remain recorded in their detailed rows. They are not DEFERRED units and are not counted in this list.

### 18.3 OUT OF SCOPE (4 units)

| ID | Class | External/physical activity | In-system scope |
|---|---|---|---|
| R-01 | O | Physical shelter rehabilitation/construction works | Record assessment, project, budget, progress, and verification only |
| R-02 | O | External media coverage itself | Record published materials, campaigns, and reach metrics only |
| R-03 | O | Legal registration procedure itself | Store verified registration status/source only; never invent status |
| R-04 | O | Direct cash transfer execution by the application | Record cash-assistance approval, distribution, and reconciliation only; payment execution requires separate approved integration |

---

## 19. Conflicts & Inconsistencies

### 19.1 Classified CONFLICT

These two rows reuse the primary `C` classifications already recorded in §§14.1 and 17.2; they do not create additional requirement units.

| Unit (reused) | Conflict | PLAN evidence | ARCH evidence | Why conflict | Required resolution |
|---|---|---|---|---|---|
| H-01 (from §14.1) | Approval lifecycle naming | `PLAN:812-821`, `1071-1077`, `1271` Draft→Pending Review→Approved→Published→Archived | `BACK:182`, `DB:97-99`, `DESIGN:161` use `submitted/in_review/changes_requested/rejected` | “Pending Review” has no documented mapping; architecture adds/omits states without reconciliation | Add an approved status crosswalk before workflow implementation |
| Q-01 (from §17.2) | Execution phase scheme | `PLAN:909-999` four 2027 quarters; `PLAN:1183-1189` 2027/2028/2029 stages | `ROAD:19-32` Phases 1–7 with 2a/2b; `MAP:13,229-242` P2a–P6 | No mapping exists; “Phase 2” in architecture is not a plan quarter/year | Add strategy-phase crosswalk; keep engineering phases distinct |

### 19.2 Other tensions observed (not upgraded to CONFLICT)

| Observation | Evidence | Assessment |
|---|---|---|
| Independent review OQ vocabulary/counts differ from current register | `REVIEW:412-416,675-683` vs `OQ:131-138`, `FINAL:96-167`, `VERIFY:241` | Historical evidence, reconciled by finalization/verification; do not treat review counts as current normative classifications |
| Source line counts in review no longer match current docs | `REVIEW:68-224` vs current files; `VERIFY:241-242` | Expected after corrections; review is unchanged evidence |
| Current baseline locale is `en`, while Arabic default is planned | `LOC:11-17`, `OQ:86`, `DEP:120` | DEFERRED by OQ-31, not a plan conflict |
| Module map still contains some undifferentiated `P2` labels while roadmap uses P2a/P2b | `MAP:105,109,117,138` vs `ROAD:19-32` | Terminology drift; documentation improvement needed |
| Provider checks appear both as “before Phase 2 config” and “do not block Phase 2a” | `OQ:49-54` vs `DEP:154`, `VERIFY:145-149` | Verification interprets them as dependent milestones; wording could be clearer |
| Gate 6 is called blocking but not executable until tool/version/CI owner recorded | `TEST:29,94,107`, `ROAD:46,61` | Known F-11 condition; not resolved by this audit |
| OQ-34 broad vs conditional trigger wording in historical reports | `FINAL:150` vs `OQ:89`, `ROAD:103`, `VERIFY:45-60` | Conditional interpretation is current; neutrals are exempt |
| “Implementation-ready” wording existed historically | `REVIEW:879-882` vs `SUM:14`, `ROAD:3` | Corrected to “review-ready”; do not revive older wording |

---

## 20. Recommended Documentation Improvements

These are recommendations only; no architecture file is modified by this audit.

| Priority | Improvement | Addresses |
|---|---|---|
| 1 | Add a `STRATEGY_TRACEABILITY_MATRIX.md` or appendix mapping objectives, 11 pillars, 39 program groups, KPIs, budget lines, 2027 quarters, 2028–2029 stages, risks, and policies to modules, tables, permissions, routes, tests, and owners | Root cause of all missing-domain findings |
| 2 | Resolve H-01 status crosswalk: define how “Pending Review” relates to `submitted`/`in_review`; document `changes_requested`/`rejected` | Governance conflict |
| 3 | Resolve Q-01 phase crosswalk: map engineering Phase 2a–7 to plan quarters/years without equating them | Phase conflict |
| 4 | Define missing domains conceptually before implementation: families, donors, finance, distributions, inventory, referrals, studies/needs assessment, indicators/results, evaluations, risks, committees, campaigns, appeals | 57 NOT COVERED units |
| 5 | Create a dashboard specification: purpose, audience, widgets, indicators, filters, permissions, export, refresh, data source, and no-PII rule for each of N-01…N-11 | Dashboard gap |
| 6 | Expand permission catalog with the missing resource families listed in §10.3 and add finance/protection-specific SoD/COI rules | Permissions gap |
| 7 | Add route/page/form/detail-view inventory for all plan-specific modules after domains are approved | Routes/pages gap |
| 8 | Define MEAL calculation rules: indicator IDs, baselines, targets, actuals, data source, verification method, dedup, period, outputs/outcomes/impact, and corrective-action loop | KPI gap |
| 9 | Add partner/donor/funding lifecycle statuses with evidence fields; preserve potential/active and planned/secured/confirmed distinctions | Partnership/finance safeguard |
| 10 | Extend testing strategy with domain-specific tests for finance, distributions, inventory, referrals, studies, KPI calculations, appeals, and dedup once those domains are designed | Missing test categories |
| 11 | Reclarify module-map phase labels P2 vs P2a/P2b and provider-check timing | Terminology drift |
| 12 | Keep all OQ/ADR/status language unchanged except through explicit owner decisions; do not close OQ-49 or OQ-34 in documentation as part of future design work | Governance safeguard |

---

## 21. Recommended Implementation Sequence

This sequence is a **recommendation for future, human-approved work**, not an authorization to begin.

| Step | Future work | Preconditions |
|---|---|---|
| 0 | Name OQ-49 approver; record explicit Phase 1 decision; record ADR owners/dates/statuses; complete strategy crosswalk and CONFLICT resolutions H-01/Q-01 | Human governance only; developer cannot perform |
| 1 | Phase 2a platform foundation: auth/RBAC/scopes/settings/localization plumbing/audit/quality tooling/provider-neutral config | OQ-17, OQ-19, OQ-20, OQ-23, OQ-31; PHP checker tool/version/CI owner |
| 2 | Phase 2b delivery enablement: queue/scheduler/critical-mail path/logs with bounded retries | OQ-10/11/15/40; no account-security mail go-live before path confirmed |
| 3 | Phase 3 public presence/CMS/media/navigation with empty synthetic technical data only | OQ-01–06, OQ-31–39; OQ-34 conditional imagery; no invented content |
| 4 | Generic programs/projects/activities/partners with explicit pillar/objective links | Strategy crosswalk; OQ-06 for public content; OQ-36 for maps |
| 5 | Privacy-gated beneficiary/protection modules | OQ-24→OQ-29 fully answered before sensitive schema/migration |
| 6 | New domain waves: finance/donor/funding; inventory/distributions; referrals; studies/needs assessment; MEAL/KPI/evaluations; risk/committee/campaign modules | Architecture design instead or amendment, owner decisions, permissions, tests |
| 7 | Accountability/participation: complaints, volunteers, careers, tenders | OQ-26/OQ-29/OQ-41–45 as applicable |
| 8 | Reporting/communications: templates, bulk send, optional push, exports, dashboards | OQ-42/OQ-44 and report definitions |
| 9 | Phase 7 hardening/go-live | Canonical gate `DEP:150-212` fully answered, owned, dated, approved; audits clean/documented; rollback rehearsed |

### Functions that can be designed (and later implemented) without waiting for institutional facts

The following do not require knowing board members, real partners, donors, staff names, achievements, or results, provided they remain empty/synthetic and provider-neutral:

- authentication and account lifecycle plumbing;
- custom RBAC vocabulary, policies, scopes, and mandatory authorization tests;
- audit log infrastructure with redaction and read/export entries;
- localization routing, RTL/LTR propagation, lang files, translation-table mechanics;
- CMS/page/news/publication infrastructure with empty content;
- media registry, explicit pivots, restricted attachment tables, signed URLs;
- workflow engine convention and status/config maps (after crosswalk correction);
- mail/notification abstractions with code-owned critical fallback (delivery path decision still required before go-live);
- design/motion tokens and neutral authored assets only;
- generic programs/projects/activities with synthetic technical records;
- dashboard/report export shell with no PII and no KPI definitions;
- testing tooling boundaries and PHP compatibility gate design.

These items still require explicit Phase 1 approval and each phase transition approval; “safe to design” does not mean “authorized now.”

---

## 22. Deferred Decisions & Open Questions

| OQ / gate | Status | Trigger | Effect on plan coverage |
|---|---|---|---|
| OQ-49 | OPEN; GLOBAL BLOCKER | Immediately after Phase 1 review | No ADR accepted, no approver invented, no Phase 2 start, no Phase 1 approval |
| OQ-05 | PHASE-SPECIFIC BLOCKER | Before Phase 3 strategic-plan content | Strategic plan may be published only as explicitly labeled planning targets, after owner decision |
| OQ-06 | PHASE-SPECIFIC BLOCKER | Before first public content/editor grant | All institutional claims need named content owner and verification |
| OQ-19 | OPEN | Before department/section scopes | Org structure cannot be invented or modeled as fact |
| OQ-23 | OPEN | Before protected capability assignment | Approval/protected permission holders unknown |
| OQ-24–OQ-29 | OPEN privacy gate | Before beneficiary/complaint sensitive schema, migration, release | Beneficiary field set, export, retention, erasure, encryption, anonymity unresolved |
| OQ-30 | OPEN | Before incident response/security ownership | Incident owner and runbook unresolved |
| OQ-31/32/38 | OPEN | Before locale/numbering/publication rules finalized | Default Arabic, digits, partial translation publication unresolved |
| OQ-33/34/35 | OQ-33/35 OPEN; OQ-34 DEFERRED | Phase 3 assets | Fonts, actual institutional photography/rights-bearing illustration, logo motion not approved; neutral design assets and synthetic non-public prototypes exempt |
| OQ-36/39 | OPEN | Before map/search schema/UI | Maps/search assumptions blocked |
| OQ-40/41/42/43/45 | OPEN/DEFERRED as registered | Mail/push/newsletter/webhooks/log masking | Communications delivery features gated |
| OQ-44 | OPEN | Before Phase 6 exports | Report definitions/exports gated |
| OQ-07–16, OQ-48 alias | OPEN | At dependent capability points and canonical gate | Hosting/provider capabilities unconfirmed; architecture remains provider-neutral |
| OQ-46/47 | OPEN | Before enforceable QA/CI tooling | Tool choices/CI owner unresolved |

**Deferral rule:** `OPEN_QUESTIONS.md:23,138` and `FINAL:167` state classifications are dependency scopes, not answers. This audit preserves all deferrals and answers none.

---

## 23. Final Readiness Verdict

### 23.1 Evidence-based verdict

**ARCHITECTURE COVERAGE VERDICT:** **PARTIALLY COVERED — NOT READY FOR FULL STRATEGIC-PLAN IMPLEMENTATION.**

The architecture is coherent and unusually careful about not inventing institutional facts, not selecting a hosting provider, not closing owner decisions, and not claiming implementation. Those strengths do not remove the plan-coverage gaps.

- 70 / 237 requirements are documented sufficiently at design level.
- 78 / 237 are only partially covered.
- 57 / 237 are not covered.
- 2 / 237 are conflicts requiring owner-approved reconciliation.
- 26 / 237 are deferred by open decisions/gates.
- 4 / 237 are external/physical activities with only a recording role in the system.

### 23.2 Blocking gaps for full implementation start

1. No OQ-49 approver and no explicit Phase 1 approval.
2. No strategy-to-architecture crosswalk; plan phases and architecture phases conflict.
3. No governance status crosswalk for “Pending Review.”
4. No finance/donor, distribution/inventory, referral, family, study/needs-assessment, indicator/KPI, evaluation, risk, committee, campaign, or appeal domain design.
5. No MEAL calculation model; plan KPIs cannot be computed.
6. No dashboard/widget/report specifications for the required dashboards.
7. No permission/route/page/test inventory for the missing domains.
8. Privacy-sensitive modules are correctly gated by OQ-24–OQ-29; those decisions remain open.
9. Provider capabilities and critical-mail delivery path are unverified.
10. Dependency audit status remains unverified from Phase 0 timeouts.

### 23.3 What may proceed only after approval

After explicit human approval and the above documentation corrections, a bounded provider-neutral platform foundation (RBAC, audit, localization plumbing, CMS shell, media/workflow infrastructure, quality tooling) can proceed without institutional facts, provided all content remains empty or synthetic and all gates remain intact. Business domains unique to the strategic plan require additional architecture definitions before implementation.

### 23.4 Final statement

This audit does **not** approve Phase 1, does **not** close OQ-49 or OQ-34, does **not** change any ADR from `PROPOSED`, does **not** authorize Phase 2, and does **not** treat proposed targets, potential partners, proposed funding, or deferred institutional decisions as facts.

**Final recommendation:** complete the strategy-specific architecture and resolver-approved conflicts first; keep Phase 1 `READY AFTER MINOR CORRECTIONS` from the independent review and `CONSISTENT AFTER TARGETED DOCUMENTATION CORRECTIONS — NOT APPROVED` from verification as historical status evidence; present the corrected plan-coverage architecture to the OQ-49 owner for an explicit decision. No implementation should start before that decision.

---

**End of report.**
