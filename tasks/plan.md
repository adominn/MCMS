# Implementation Plan: UTeM MCMS Full Guide Compliance Remediation

## Overview

This plan remediates all **191 mandatory non-compliant requirements** (`68 WRONG`, `9 MISSING`, `114 PARTIAL`), **2 optional requirements**, **4 live Pest test failures**, **demo data violations**, and **documentation overclaims** identified in [AUDIT_REPORT.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/AUDIT_REPORT.md) and [TRACEABILITY_MATRIX.csv](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/TRACEABILITY_MATRIX.csv) against *Garis Panduan Micro-Credential UTeM Versi 1.0* ([MC UTeM Guide.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MC%20UTeM%20Guide.pdf)).

Current baseline: **9.9% strict** (`21/212`), **36.7% weighted** (`78.0/212`), **4 failed / 406 passed** Pest tests.  
Target state: **0 `WRONG`**, **0 `MISSING`** mandatory `SYS-*` requirements, **100% passing Pest + Playwright suite**, clean Guide-compliant `mcms_demo`, and verified EN/BM parity across UI and A4 printouts.

Full executable task checklist with per-task acceptance criteria, files touched, and focused Pest commands is maintained in [tasks/todo.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/tasks/todo.md).

---

## Dependency Graph

```text
Phase 1: Security, Verification & Printout Fabrications (Tasks 1–5)   [Immediate critical risk kill; no breaking schema]
   │
   ▼
Phase 2: Role / RACI & Governance Foundation — App. H (Tasks 6–9)     [8-role P1–P8 RBAC, SoD, locked statutory rules]
   │
   ├──▶ Phase 3: Curriculum, Approval, QV & Material Change (Tasks 10–14)
   │       │
   │       ▼
   │    Phase 4: Enrolment, Assessment, Completion & Modular UMC (Tasks 15–17)
   │       │
   │       ▼
   │    Phase 5: Credit Bank, Transfer & Stackable Degree — App. F–G (Tasks 18–22)
   │       │
   │       ▼
   └──▶ Phase 6: Complete Official Appendices & Printouts — App. A–E, G (Tasks 23–27)
           │
           ▼
        Phase 7: Bilingual EN/BM, Demo Seeders, Docs & Re-Audit (Tasks 28–30)
```

---

## Architecture Decisions

1. **Dedicated 8-Role RBAC Aligned to Guide Appendix H2–H3:** Split `deputy_dean` into explicit `P2` (`ptj_authority` / Deputy Dean PTj), `P3` (`academic_authority` / Senate & Transfer Committee), and `P4` (`qa_officer` / Quality Assurance); separate `P6` (`records_custodian` / Registry & Credentials) from `P7` (`abc_depositor` / Authorized ABC Depositor); retire or wire dead `Role::Reviewer`. Route middleware must dynamically honor `approval.route.*` or enforce statutory H3 mappings without deadlocking.
2. **Statutory Threshold Immutability:** Statutory Guide rules (`40h` minimum UMC/SAMC SLT, `< 40h` MCE ceiling, `3-year` maximum QV validity, `10-year` ABC validity from deposit date, `30%` minimum direct UTeM graduating credit floor, `1-semester` conventional residency) must be removed from unilateral `P5` (`codl_admin`) write access and guarded by `P3` academic authority with audit trail.
3. **Single Numeric Formatter Helper:** Replace all 39 `rtrim(rtrim((string) $v, '0'), '.')` calls across 18 Blade views with a single tested helper (`number_format((float) $v, 2, '.', '')` before trimming) so trailing zeros on integers (`10`, `100`) are never stripped.
4. **Strict Stage & State-Machine Invariants on Server:** Every gate (ProposalChecker, COI, QV standing, open integrity cases, verified score checks, F7 equivalence criteria, 30% home floor, programme transfer caps, `non_transferable`, mandatory `remaining` courses) is enforced server-side in service/controller transactions — never UI-only or warning-only.
5. **Non-Destructive Synchronized F10 Corrections:** Corrections preserve original snapshots in `corrections` / `credential_revisions` while atomically applying authorized field updates to target records (`acrs`, `abc_deposits`, `credentials`) and notifying affected learners.
6. **Zero-Fabrication Rule for Official Printouts & Proofs:** Official printouts render only persisted actors, `users.position` / `users.position_ms`, real decision dates, real `assessment_standard`, and authentic Ed25519 VC-JWT proofs — never `now()`, hardcoded titles, fallback credits, or synthetic `sha256` proofs.

---

## Task List (Summary Index — Details in `tasks/todo.md`)

### Phase 1: Emergency Security, Verification & Official Printout Fabrications
- [ ] **Task 1 (S):** Fix systemic `rtrim` trailing-zero formatting bug across all 18 Blade views (39 occurrences).
- [ ] **Task 2 (M):** Patch Public Verification (`VerifyController`, `verify-result.blade.php`, `verification_log.outcome` migration) — block revoked downloads, gate Ed25519 badge, remove `proposed_credit ?? 3` & SAMC/graduating-credit fallbacks, stop retroactive credential expiry, validate outer JSON against JWT.
- [ ] **Task 3 (M):** Eliminate official printout fabrications on Suspension Notice (`DocumentsTest:157`), Credential Sheet (`DataIntegrityProof`), and Pathways Print (`10 years of deposit date`, 30% card wording).
- [ ] **Task 4 (S):** Close live COI bypass on `/proposals/{id}/print`, PTj scope leak in `SearchController` (remove fake PDPA/session stubs), `auditRows()` `limit(600)` ordering, and MySQL audit trigger `@mcms_disable_triggers` backdoor.
- [ ] **Task 5 (S):** Restore F5 Credit Ledger disclaimer (`CreditBankTest:188`), fix `ledger()` accounting, and remove MCE/MQF/credit leaks across student result/record views.
- [ ] **Checkpoint 1:** Run focused Pest tests (`CredentialTest`, `CreditBankTest`, `DocumentsTest`, `RbacSecurityTest`, `RecordsTest`) + live revoked-download HTTP 403/410 check.

### Phase 2: Role / RACI Separation & Governance Hardening (Appendix H)
- [ ] **Task 6 (M):** Implement full 8-role `P1`–`P8` enum (`Role.php`), add `P3`/`P4`, separate `P6`/`P7`, resolve dead `Role::Reviewer`, update role assignment UI & navigation.
- [ ] **Task 7 (M):** Re-align routes and controller middleware to Appendix H3 RACI (move `/credentials/*` & admissions to `P6`, ABC redemption/release to `P7`, separate `P2` endorse vs `P3` Senate/Transfer/Stackable, block lecturer self-approval on periodic reviews, fix `approval.route.*` deadlocks).
- [ ] **Task 8 (S):** Prevent `P5` (`codl_admin`) academic usurpation across `SettingsController`, `ReferenceDataController`, `UserAdminController` SoD, and `RetentionController` holds.
- [ ] **Task 9 (M):** Build Appendix H4 (12-class Record Ownership Matrix), expand H5 Cohort Records Checklist to 10 items with Location/Owner, and build H8 Governance Action Register + H9 Confirmation + Nota Penting H.
- [ ] **Checkpoint 2:** Run `RbacSecurityTest`, `FoundationTest`, `RecordsTest`; verify all 8 role personas via Playwright.

### Phase 3: Curriculum, Approval, QV & Material-Change Enforcement
- [ ] **Task 10 (M):** Enforce `ProposalChecker` on save/approval/offering, block `qv_status='verified'` self-assignment, forbid `parent_course_id` on SAMC, enforce UMC `parent_clo_id` mapping, invalidate stale reviews after return-for-amendment.
- [ ] **Task 11 (M):** Add schema & UI for A3 Category Justification, A5 structured `learning_content` & `learning_activities` with SLT-per-activity/LO breakdown (Cl. 10.1), and A10 follow-up actions.
- [ ] **Task 12 (M):** Fix Version Control & Material-Change lifecycle (`VersionController::copy` clears QV, `ApprovalController::senate` clears QV, `OfferingStatus` stops reopening superseded `v1` cohorts, parent course/programme changes auto-trigger UMC periodic reviews per Cl. 11.5).
- [ ] **Task 13 (M):** Fix SAMC QV enforcement (`QvController::result` exact leap-year 3y bound, reject expired `valid_until` & `0` credit, version-scoped update; `Qv::completedCohorts` verified evidence on current version; `QvAlerts::end` truncates `valid_until` & notifies learners).
- [ ] **Task 14 (S):** Upgrade `ForbiddenClaims.php` (add BM regexes; scan proposals, pathways & all offering fields) and fix `/public/offering/{id}` 5-stage notice, `credential_types` display, and `"Identity Verification"` label.
- [ ] **Checkpoint 3:** Run `CurriculumTest`, `ApprovalTest`, `VersionTest`, `QualityTest`, `OfferingTest`.

### Phase 4: Enrolment, Assessment, Completion & Modular UMC
- [ ] **Task 15 (S):** Replace hardcoded pre-registration checks in `student/register.blade.php` with live `Enrolments::reasons()` checks including `eligible_groups`; remove hidden `prerequisites_ok=1` and unverified credit/MQF display.
- [ ] **Task 16 (M):** Fix `Verifier::verify()` to enforce `integrity_cases` holds, fix `decideChange()` score-reduction revocation of `module_achievements`/badges + learner notification, support partial-cohort marking, and fix false green `Passed` on `student/results.blade.php`.
- [ ] **Task 17 (M):** Fix `separate_assessment` UMC completion deadlock (`Completion.php:55-58`), enable cross-cohort modular UMC aggregation within completion window (`Cl. 7.4`), enforce `CompletionWindow` expiry, and fix `CompletionTest:146`.
- [ ] **Checkpoint 4:** Run `EnrolmentTest`, `ResultsTest`, `CompletionTest` (100% green).

### Phase 5: Credit Bank, Transfer & Stackable Degree Enforcement (Ch. 12–14, App. F–G)
- [ ] **Task 18 (M):** Add Formal vs Non-Formal learning classification (`Cl. 12.1.1`) across DB/ACR/ABC CSV/print; harden `Acr::prepare()` against suspended/withdrawn QV and incomplete UMC modules; fix F11 Row 3 overwrite (`CreditBankController:191`), multi-ACR verifiers, and F9 Provisional date (`20/10` vs `25/10`).
- [ ] **Task 19 (M):** Convert credit transfer caps (30% UTeM floor, programme transfer limit, `non_transferable`, target course credit cap, duplicate target course) into server-side hard blocks; align `config/transfer.php` F7 allowed values exactly to Guide Appendix F7 and block approval on failing criteria or blank `na`.
- [ ] **Task 20 (M):** Fix partial credit transfer approval (`Diluluskan sebahagian`) to preserve remaining deposit balance, fix expired-credit re-evaluation (`reevaluate=true`), implement transactional F10 record synchronization with accurate `record_ref`, and fix `discrepancies.key` `UNIQUE` blindness.
- [ ] **Task 21 (M):** Fix Stackable Degree award gating (`Plans::checks()` requires ALL mandatory `remaining` items `done`, checks live `Qv::standing()`, BoK, full PLO coverage, and graduation criteria; allow editing `remaining` items; call `CreditLedger::redeem()` on `planAward()`).
- [ ] **Task 22 (M — Decision-Gated):** Implement external-institution MC ACR ingestion (`Cl. 12.7` / `13.2`) and curriculum-change equivalence re-evaluation outcomes (`Cl. 13.8` / `14.8`) if confirmed in Phase 1 scope.
- [ ] **Checkpoint 5:** Run `CreditBankTest`, `TransferTest`, `AppendixFComplianceTest`, `PathwayTest` (100% green).

### Phase 6: Complete Official Appendices A, B, C, D, E, G Forms & Printables
- [ ] **Task 23 (M):** Rebuild Appendix A print (`curriculum/print.blade.php`) with all 10 sections (A1–A10), real `assessment_standard`, real 3-stage A9 sign-offs/dates, A8 table, A10 change control, and Nota Penting A (`DocumentsTest:130`).
- [ ] **Task 24 (M):** Expand Appendix B Offering Readiness from 6 collapsed rows to full B1–B8 (38 items B2–B7 + 10-row B5 table + 3-role B8 sign-off + Nota Penting B + A4 print).
- [ ] **Task 25 (M):** Fix Appendix C CQI (`Indicators.php` verified-only/non-null/non-revoked math + Indicator #6; 4 C4 feedback sources including `not_completed` & no pre-checked `4`; 8 C5 domains + `TB`; C6 title; selectable C7 owner; visible C8 before/after evidence; C9; C10 3-role sign-off + Nota Penting C + A4 print).
- [ ] **Task 26 (M):** Expand Appendix D QV Evidence Package from 5 rows to all 42 D3–D7 items, 9 D8 material-change components, D9 Required Actions, and D10 3-role sign-off + D2/D8/Nota D on print package.
- [ ] **Task 27 (M):** Complete Appendix E & G forms/printables (E3 Certificate PTj/SLT/LOs/position; E2 Digital Badge criteria/LOs/QR/disclaimer/status lifecycle; persisted E8 checklist run on `replace()`; E10 3-role sign-off + Nota Penting E; G4 Evidence/Gaps; G5 Body of Knowledge table; G8 full columns; G9 summary/gaps; G11 3-role sign-off + Nota Penting G; F5 printable credit ledger).
- [ ] **Checkpoint 6:** Run `DocumentsTest`, `MonitoringTest`, `QualityTest`, `CredentialTest`, `PathwayTest` + visual Playwright verification of all A4 print templates.

### Phase 7: Bilingual EN/BM Parity, Demo Seeders, Docs & Independent Re-Audit
- [ ] **Task 28 (M):** Complete EN/BM localization (add 655 missing Blade keys to `ms.json`; wrap unwrapped English in `Plans.php`, `Trace.php`, `Reports.php`, `Role.php`, prints; render `users.position_ms` & `learning_outcomes.text_ms` on BM prints; fix `"Lulus"` mistranslation; persist `users.locale` from header toggle).
- [ ] **Task 29 (M):** Remediate all demo seeders (`ComprehensiveTestingSeeder`, `DemoScenarioSeeder`, `DemoQualitySeeder`, `DemoTransferSeeder`, `DemoPathwaySeeder`) to eliminate every Guide violation in `mcms_demo`.
- [ ] **Task 30 (S):** Execute full Pest + Playwright regression, archive/retract overclaiming audit PDFs, update `README.md`/`GEMINI.md`/`HANDOVER.md`, and re-run independent compliance re-audit.
- [ ] **Checkpoint 7 (Complete):** Full Pest suite 100% green, zero Playwright defects, re-audit confirms `0 WRONG` / `0 MISSING` mandatory `SYS-*`.

---

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Splitting `deputy_dean` into P2/P3/P4/P6/P7 breaks existing 400+ Pest tests and stub persona switcher | High | Update `tests/Helpers.php`, stub persona switcher, and role seeders inside Tasks 6–7 in the same checkpoint before starting Phase 3; run full `RbacSecurityTest` immediately. |
| Expanding Appendices B (38 items) and D (42 items) bloats forms & hurts UX | Medium | Group items cleanly into collapsible Carbon sections (B2–B7, D3–D7) preserving summary-first presentation per `DESIGN.md` while persisting every individual Guide item. |
| Concurrent Pest runs deadlock Docker MySQL `mcms_test` | Medium | Run strictly one `php vendor/bin/pest` command at a time; run focused `--filter=` suites per task and one full suite at Phase checkpoints. |
| Ambiguous Guide clauses cause rework if assumptions conflict with UTeM Senate policy | High | Resolve the 6 decision items in Open Questions below before starting Phases 2 and 5. |

---

## Open Questions Requiring Decision Before Execution

1. **RBAC Role Split Granularity (Phase 2, Tasks 6–7):** Confirm whether MCMS should add all **8 distinct Guide H2 roles** (`P1` Lecturer/Coordinator, `P2` Deputy Dean/PTj, `P3` Academic Authority/Senate, `P4` QA Officer, `P5` CODL Admin, `P6` Records/Credential Custodian, `P7` ABC Depositor, `P8` Student) — or allow dual-assignment on a single staff account with per-record SoD checks (e.g., same user cannot endorse as P2 and approve as P3 on the same proposal).
2. **Partial Credit Transfer (`Diluluskan sebahagian`) vs ABC Deposit Splitting (Phase 5, Task 20 — Guide Cl. 12.9 & F8):** When a 3-credit ABC deposit is partially approved for 2 credits, should MCMS split/track partial balances (`2.0` Provisional + `1.0` Available) or lock the full deposit per national ABC rules?
3. **Programme Credit Transfer Cap Enforcement (Phase 5, Task 19 — Guide Cl. 13.5 & 14.5.1):** Confirm that both the 30% direct UTeM home floor AND each programme's `transfer_limit_percent` must be **hard server-side blocks** (no override), and confirm whether numeric **80% LO matching** (from earlier docs/seeders) should be removed since the Guide uses qualitative F7 scales only.
4. **External-Institution MC Credit Ingestion (Phase 5, Task 22 — Guide Cl. 12.7 & 13.2):** Should external-institution ACR import/entry be built now, or explicitly scoped out of Phase 1 (documented as handled outside MCMS)?
5. **Default Modular UMC Completion Window & Non-ACR Retention Durations (Phase 4 Task 17 & Phase 2 Task 9 — Guide Cl. 7.4 & 18.12):** What default values should be seeded (e.g., 24 or 36 months for modular UMC completion window; exact retention years for the 12 Appendix H4 record classes)?
6. **Rajah 2 Contradiction Confirmation (Phase 4 Task 17 — Guide pp. 19–20, 45):** Confirm that body text (Cl. 6.3.1 & 12.3.1: individual UMC modules do NOT confer full course credit or ABC deposit unless explicitly approved as a standalone credit unit) overrides Rajah 2 Step 2.
