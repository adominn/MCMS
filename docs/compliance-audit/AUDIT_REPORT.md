# Independent Compliance Audit Report — UTeM MCMS (MC-ABC System)

**Audit Authority (Single Source of Truth):** *Garis Panduan Micro-Credential UTeM Versi 1.0* (18 September 2026) — [MC UTeM Guide.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MC%20UTeM%20Guide.pdf) (119 pages, Chapters 1–18, 19 embedded figures, 2 tables, Appendices A–H).  
**Audit Mode:** Independent, read-only compliance verification against live application code, MySQL 8.4 database (`mcms_demo`), 243 routes, 135 Blade templates, Pest test suite, and live multi-persona browser captures. Zero application files were modified.  
**Audit Date:** 20 November 2026.  
**Deliverables:**
- [REQUIREMENTS.csv](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/REQUIREMENTS.csv) (241 atomic requirements extracted from the Guide)
- [TRACEABILITY_MATRIX.csv](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/TRACEABILITY_MATRIX.csv) (241 requirements mapped to code, DB, UI, print, tests, evidence, and gaps)
- [evidence/](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/) (visually verified PNG screenshots, rendered text dumps, and live HTTP outputs)

---

## a. Executive Summary

### Overall Compliance Score

**Scoring Formulas (never rounded up — floored to 1 decimal place):**
- **Headline Strict Score** = `IMPLEMENTED / (IMPLEMENTED + PARTIAL + WRONG + MISSING)` over **mandatory `SYS-*` requirements only** (`PARTIAL = 0`, `UNVERIFIABLE` excluded if any; `0` here).
- **Weighted Score** = `(IMPLEMENTED + 0.5 * PARTIAL) / (IMPLEMENTED + PARTIAL + WRONG + MISSING)` over **mandatory `SYS-*` requirements only**.

| Metric | Value | Breakdown (Mandatory `SYS-*` = 212) |
|---|---:|---|
| **Headline Strict Score** | **9.9%** (`21 / 212`) | `IMPLEMENTED = 21`, `PARTIAL = 114`, `WRONG = 68`, `MISSING = 9`, `UNVERIFIABLE = 0` |
| **Weighted Score (`PARTIAL = 0.5`)** | **36.7%** (`78.0 / 212`) | `21 + (0.5 * 114) = 78.0 / 212` |
| Optional `SYS-*` (excluded from denominator) | `0 / 2` | `REQ-C12-012 = MISSING`, `REQ-C12-015 = WRONG` |
| Non-System Institutional/Info (`NOT-SYSTEM`) | `27` items | `20 PROCESS` (17 Mandatory, 3 Optional), `7 INFO` |
| **Live Pest Automated Test Suite** | **4 FAILED, 406 PASSED** | `5,907 assertions` (`CompletionTest`, `CreditBankTest`, `DocumentsTest` x2 fail — contradicting repo claims of 100% green) |

### Score Summary per Chapter and per Appendix (Mandatory `SYS-*`)

- **Chapters (Ch 1–18, 135 Mandatory `SYS-*`):** Strict **14.8%** (`20 / 135`), Weighted **39.2%** (`53.0 / 135`). Highest: Ch 4 (`50.0%` strict, 1/2) and Ch 12 (`35.2%` strict, 6/17). **Eight chapters score `0.0%` strict:** Ch 7, Ch 9, Ch 11, Ch 14, Ch 15, Ch 16, Ch 17 (plus Ch 2/5/FM/Rujukan which are `NOT-SYSTEM`).
- **Appendices (Lampiran A–H, 77 Mandatory `SYS-*`):** Strict **1.2%** (`1 / 77`), Weighted **32.4%** (`25.0 / 77`). **Seven of eight appendices score `0.0%` strict** (Lampiran A, B, C, D, F, G, H); only Lampiran E (`11.1%`, 1/9) has a single strictly compliant requirement (`REQ-AE-001`). Earlier internal audit reports claiming *"11/11 FULL COMPLIANCE VERIFIED"* on Appendix F and Appendix H are contradicted by live code, database, Pest test failures, and rendered printouts.

### Top 10 Critical Findings

1. **Fabricated Official Printouts, Signatory Titles, Dates, Arithmetic & Cryptographic Proofs** (Cl. A5–A9, 14.7, 17.10, E4; pp. 57, 67, 76–79, 101–102):
   - `/suspensions/1/print` prints P5 CODL Administrator **Farid Hashim** with the fabricated title **`Deputy Dean (Academic), Fakulti Teknologi Maklumat Dan Komunikasi`** (`monitoring/suspension-notice.blade.php:111`), fabricates a fallback transition plan claiming cohorts finish as planned after `decide()` held/cancelled them (`:16-18`), and collides `suspensions.id` with `micro_credentials.id` (`QualityReviewController.php:233-239`). Evidence: [REQ-C17-008_suspension_notice.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C17-008_suspension_notice.png).
   - `/proposals/1/print` prints assessment weights `30% + 30% + 40%` as **`Total: 1%`**, ignores `assessment_standard` and hardcodes **`Pass mark: 50%`**, hardcodes `now()->format('d/m/Y')` (`curriculum/print.blade.php:198,204,309`), and fabricates `Digital Credential/Certificate: Yes`. Evidence: [REQ-AA-009_proposal_print_appendix_a.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AA-009_proposal_print_appendix_a.png).
   - `/public/pathways/1/print` and `/public/pathways` strip trailing zeros via a systemic `rtrim(rtrim((string) $v, '0'), '.')` bug (39 occurrences across 18 Blade views), printing `10` credits as **`1`** (`Total Potential Transferable Credits: 1`; `1 Max articulable credits`), while printing the wrong credit expiry rule (**"within 10 years of completion"** instead of deposit date). Evidence: [REQ-AG-001_pathway_print.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AG-001_pathway_print.png), [REQ-C14-012_public_pathways.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C14-012_public_pathways.png).
   - `/my-credentials/1/sheet` synthesizes a **fabricated W3C `DataIntegrityProof` (`eddsa-rdfc-2022`)** from `'z' . hash('sha256', $c->jwt)` with fake `did:web:127.0.0.1` and non-existent `/status/1` (`CredentialController.php:220-229`). Evidence: [REQ-AE-004_student_credential_sheet.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-004_student_credential_sheet.png).

2. **Revoked Credential PII Leak & False Public Cryptographic Verification** (Cl. 10.5.1, 18.11, E2, E5, E6; pp. 36, 70, 100–102):
   - `GET /public/verify/SEC03-2P6RW/download/json` and `/download/jwt` on a **REVOKED** credential (`UTEM-MC-2026-0009`) both return **HTTP 200**, leaking recipient name, full claims, and a valid signed Ed25519 JWT (`VerifyController.php:39-113`). Evidence: [REQ-AE-006_revoked_download_bypass.txt](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_revoked_download_bypass.txt).
   - `public/verify-result.blade.php:564-565` unconditionally renders green **`Cryptographic Integrity Verified · Ed25519`** on **revoked** credentials, unsigned **Digital Badges**, and `invalid_signature` credentials. `VerifyController.php:255` falls back to `proposed_credit ?? 3` and mislabels non-UMC offerings (including MCEs) as `"Stand-Alone Micro-Credential (SAMC)"` with **`3 graduating credits`**. Evidence: [REQ-AE-006_public_verify_revoked.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_public_verify_revoked.png), [REQ-AE-002_public_verify_badge.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-002_public_verify_badge.png), [REQ-AE-006_public_verify_valid.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_public_verify_valid.png).

3. **Severe Role & RACI Collapse + Wrong Role Controlling Credentials & Indirect P5 Usurpation** (Cl. 15.2, 16.3, 16.6, H2–H3; pp. 59–64, 115–116):
   - Only **6 of 8** Guide roles work (`Role::Reviewer` is 100% dead code; **`P4` Quality Assurance does not exist**). `deputy_dean` collapses `P2` (PTj) + `P3` (Academic Authority / Senate) + `P4` (QA) + `P6` (Credentials & Admissions) + partial `P7` (ABC redemption/release).
   - `records_depositor` (`P6/P7`) receives **HTTP 403** on `/credentials/*` while `deputy_dean` controls credentials (`REQ-AH-002_depositor_blocked_from_credentials_403.png`). `codl_admin` (`P5`) can unilaterally lower all statutory academic thresholds (`/admin/settings`), overwrite accredited programmes/parent courses (`/admin/reference`), lift retention holds, and self-assign `deputy_dean`/`records_depositor`. Evidence: [REQ-AH-002_depositor_blocked_from_credentials_403.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-002_depositor_blocked_from_credentials_403.png), [REQ-C15-002_admin_settings.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C15-002_admin_settings.png).

4. **Stackable Degree Awarded While All 4 Mandatory Courses Remain Incomplete** (Cl. 14.1, 14.5, 14.10, G8; pp. 53–59, 112–113):
   - `Plans::checks():42-43` marks `remaining.met = true` solely if the `remaining` JSON array is non-empty — ignoring that all 4 mandatory courses (Core, Final Year Project, Industrial Training) are `todo`/`planned`. `PathwayController::planAward():195-205` awards a BITS degree (`SENATE-AWARD-BITS-2026/04`) without verifying remaining courses, CGPA/graduation rules, BoK, or full PLO coverage, and never calls `CreditLedger::redeem()`. Evidence: [REQ-AG-006_stackable_plan_appendix_g.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AG-006_stackable_plan_appendix_g.png), [REQ-C14-001_student_stackable_plan.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C14-001_student_stackable_plan.png).

5. **QV Forgery, Material-Change QV Leakage & Suspended-QV ACR Generation** (Cl. 4, 6.3.2, 7.7, 11.2, 12.1.1, 12.3.2; pp. 14, 20, 24, 37–39, 45–46):
   - `ProposalController::credential():376` lets any lecturer POST `qv_status = 'verified'` on a draft proposal (including MCEs).
   - `VersionController::copy():168` copies `qv_status = 'verified'` and `qv_valid_until` onto materially changed `v2`; `ApprovalController::senate():225` never clears QV.
   - `Acr::prepare():46-47` queries `qv_records` with `status IN ('verified','superseded','expired','suspended','withdrawn')` while `QvAlerts::end():58` never truncates `valid_until` — allowing **suspended/withdrawn QV SAMCs** to generate credit-bearing ACRs for ABC deposit. `QvController::result():186` overflows leap-year 3-year limits and overwrites all historical `mc_versions`.

6. **Credit Transfer Caps, Non-Transferable Courses, F7 Evaluation & Partial Approvals Broken on Server** (Cl. 12.9, 13.3–13.6, 14.5.1, F7–F9; pp. 48–51, 56, 107–108):
   - `EvaluationController::decide():211-221` treats the 30% UTeM home floor, programme transfer cap, and `parent_courses.non_transferable` as **warning-only** (still approves violating transfers).
   - `EvaluationController::decide():203-210` approves transfers even when `accreditation='invalid'`, `mqf='not_matched'`, `currency='needs_review'`, `professional='not_met'`, or `credit_slt='inadequate'`; `evaluate():118` permits `na` with blank findings across all 8 criteria.
   - Partial approval (`Diluluskan sebahagian`) locks and redeems the **entire** `abc_deposits` row (`CreditLedger::move():127-134`), destroying unapproved credits; expired-credit re-evaluation (`reevaluate=true`) is dead at approval (`whyNot():54` hard-blocks). Evidence: [REQ-AF-007_transfer_eval_f7_form.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-007_transfer_eval_f7_form.png), [REQ-AF-008_transfer_decision_letter_print.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-008_transfer_decision_letter_print.png).

7. **Assessment Integrity Bypass, Failed-Learner Certification Loophole & `separate_assessment` UMC Deadlock** (Cl. 6.1, 6.3.1.1, 7.4, 8.7.3, 10.4; pp. 17–20, 24, 28, 36):
   - `Verifier::verify():23` never checks `integrity_cases` (verifies marks and mints badges while an academic integrity case is open).
   - Lowering a verified mark below `pass_mark` via `ResultsController::decideChange()` never revokes `module_achievements`/badges, and `Completion::check()` never re-checks lowered scores — permitting a failed learner to be certified and issued an ACR.
   - `Completion.php:55-58` permanently deadlocks `separate_assessment` UMCs (learning modules have no directly linked assessment component), and `module_achievements.enrolment_id` prevents cross-cohort modular UMC completion. `student/results.blade.php:136-138` renders green `Passed` for failed learners with verified marks.

8. **Live COI Bypass, Lecturer Self-Approval & Cross-PTj Search/Audit Leaks** (Cl. 9.4, 11.5, 16.4–16.6, 18.11, H6; pp. 33, 42, 63–64, 70, 118):
   - Live-verified: `U#6 GET /proposals/4/print` returns **HTTP 200** bypassing the COI gate (`ProposalController.php:123-138`).
   - `QualityReviewController::reviewOutcome():143-162` has zero Separation of Duties check, letting a lecturer self-approve their own periodic review.
   - `SearchController` has zero PTj scoping (plus fake PDPA correction `DC-2026-0118` and fake session termination stubs); `RecordsController::auditRows():157` applies `limit(600)` before PTj filtering; MySQL audit triggers carry an `IF @mcms_disable_triggers` bypass and no `BEFORE TRUNCATE` guard.

9. **Non-Synchronizing F10 Corrections, Broken F5 Ledger & Missing Formal/Non-Formal Classification** (Cl. 12.1.1, 12.10, 18.10, F5, F10, F11; pp. 45, 48, 70, 106–109):
   - `CreditBankController::correct()` and `Discrepancies::resolve()` write text rows with **zero synchronization** of underlying ACR/ABC/Credential records; `correct()` writes `$a->reference` even for `AbcDeposit`/`Credential`; `discrepancies.key` `UNIQUE` permanently blinds repeat scans.
   - Mandatory F5 non-automatic acceptance disclaimer was deleted from `creditbank/ledger.blade.php` (visually confirmed and failing `CreditBankTest.php:188`), and `ledger()` double-counts expired redeemed credits.
   - Formal vs Non-Formal learning classification (Cl. 12.1.1) is absent across DB, UI, CSV, and print. `CreditBankController::record():191` overwrites F11 Row 3 created at `confirm():113`. Evidence: [REQ-AF-005_student_credit_ledger.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-005_student_credit_ledger.png), [REQ-C18-008_discrepancies.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-008_discrepancies.png).

10. **Systematic Collapse or Omission of Official Appendices (A, B, C, D, E, G, H) & Defective Demo Data** (Lampiran A–H; pp. 75–119):
    - Appendix B collapses 38 items (B2–B7) + 10-row B5 table into **6 rows** (no print view). Appendix D collapses 42 evidence items (D3–D7) into **5 rows** and prints 2 blank lines labelled `Programme Leader`. Appendix C omits Indicator #6, 3/4 feedback sources, 3/8 quality domains, C9, C10, and has defective `Indicators.php` math (unverified marks, `null=0`, revoked credentials counted, `null` rendered green `Met`, no print view). Missing completely: G5 (Body of Knowledge), G11, E10, H4 (12-class ownership matrix), H8, H9, and Nota Penting A/B/C/D/E/F/G/H.
    - Seeded `mcms_demo` violates core Guide rules on live screens: approved MCE `MC#07` with **40h SLT & 1.5 credits**, 12/17 UMC SLT mismatches (live `/public/offering/7` shows 40h for a 3-credit/120h UMC), 5 UMC QV records (`QV#02` = **3.67 years**), `0/2` achieved OOP UMC with deposited ABC credit, and P5 CODL Admin Farid Hashim recorded on ACRs, corrections, disposals, and suspension notices. Evidence: [REQ-AB-001_offering_readiness_appendix_b.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AB-001_offering_readiness_appendix_b.png), [REQ-AC-001_cqi_appendix_c.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AC-001_cqi_appendix_c.png), [REQ-AD-010_qv_package_appendix_d.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AD-010_qv_package_appendix_d.png), [REQ-C06-014_public_offering_mce_demo_violation.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C06-014_public_offering_mce_demo_violation.png).

---

## b. Method and Limits

### Audit Methodology
1. **Phase 1 — Exhaustive Requirement Extraction:** Extracted all 119 pages of [MC UTeM Guide.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MC%20UTeM%20Guide.pdf) (zero unreadable or scanned pages) AND isolated and visually inspected all **19 embedded diagram pages** (Rajah 1–19, Jadual 1–2) to verify rules inside figures, footnotes, and *Nota Penting* boxes. Produced **241 atomic requirements** in [REQUIREMENTS.csv](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/REQUIREMENTS.csv).
2. **Phase 2 — Complete Codebase, Schema, Route, Config, Bilingual, Seeder & Docs Inspection:**
   - Inspected all **26 migrations**, **93 MySQL tables** in `mcms_demo`, **243 routes**, all custom `app/config/*.php` files, **82 PHP module classes** under `app/app/Modules/*`, **135 Blade templates**, **11 seeders**, **36 test files**, `app/lang/ms.json` (3,213 keys), and all existing audit/handover documents.
   - Queried live `mcms_demo` directly via MySQL to validate referential integrity, dates, credits, SLT totals, QV windows, roles, and seeded records.
3. **Phase 2 — Live Multi-Role Runtime Verification & Visual Inspection:**
   - Executed the live Laravel 13.38 application at `http://127.0.0.1:8110` (`MCMS_STUB_IDP=true`, Docker MySQL 8.4 `mcms_demo`).
   - Exercised all **6 active personas** (Student `U#1`, Lecturer `U#6`, Deputy Dean `U#10` w/ MFA, Registry/ABC Depositor `U#13` w/ MFA, CODL Admin `U#12` w/ MFA, Industry Expert `U#15`) plus unauthenticated Public views via Playwright, capturing 50+ full-page PNG screenshots and HTML/HTTP evidence dumps under `docs/compliance-audit/evidence/`. Every cited screenshot was visually inspected to eliminate caption/screenshot mismatches.
   - Executed direct live security/bypass probes (`GET /public/verify/SEC03-2P6RW/download/json`, `/download/jwt`, `U#6 GET /proposals/4/print`, `U#13 GET /credentials`).
4. **Phase 2 — Automated Pest Suite Execution:**
   - Executed the full Pest suite cleanly against `mcms_test` (`php vendor/bin/pest --colors=never`): **`4 failed, 406 passed (5,907 assertions)`** across 27 feature test files:
     1. `Tests\Feature\CompletionTest` (`tests/Feature/CompletionTest.php:146`): `it gives the deputy dean a task and a screen with the pre-issue checks (FL-08)` — failed asserting `'badges only'`.
     2. `Tests\Feature\CreditBankTest` (`tests/Feature/CreditBankTest.php:188`): `it shows the credit ledger with the total and the note that credits are not automatically accepted` — failed asserting `'not automatically accepted by any programme'` (confirming deleted F5 disclaimer).
     3. `Tests\Feature\DocumentsTest` (`tests/Feature/DocumentsTest.php:130`): `it renders proposal print (Appendix A) with 10 sections` — failed asserting `'Appendix A'`.
     4. `Tests\Feature\DocumentsTest` (`tests/Feature/DocumentsTest.php:157`): `it renders suspension notice under standard print layout` — failed asserting BM text `'Kredensial dan kredit yang telah anda peroleh kekal sah'`.

### Audit Limits
- **Stubbed External Integrations:** External services are stubbed or manual in this environment: SSO/Entra ID (`MCMS_STUB_IDP=true`), national MQA ABC API (CSV export + manual deposit recording), FPX payment gateway (manual/stub payment confirmation), and uLearn/UCreds LMS exchange (file/stub adapter). Compliance ratings evaluate MCMS's own functional enforcement, records, and printouts rather than live third-party API transport.
- **Read-Only Non-Destructive Constraint:** Because the audit operated strictly read-only against `mcms_demo`, destructive state-mutating POST workflows (e.g., triggering live disposal, modifying `/admin/settings`, mutating student records) were verified via exact server-side controller/service code inspection combined with Pest tests and non-destructive GET/RBAC probes rather than altering `mcms_demo` state.
- **Single-Version Guide Scope:** Only *Garis Panduan Micro-Credential UTeM Versi 1.0* (18 September 2026) was treated as authoritative. External MQA/MOHE documents cited in *Rujukan* (p. 72) were not audited separately except where explicitly stated inside the Guide.

---

## c. Scoring Table

*Note: Columns `IMPL`, `PARTIAL`, `WRONG`, `MISSING`, and `UNVERIF` count **Mandatory `SYS-*`** requirements (denominator = `212`). The 2 Optional `SYS-*` requirements in Ch 12 (`REQ-C12-012 = MISSING`, `REQ-C12-015 = WRONG`) are excluded from the mandatory denominator per scoring rules and shown explicitly in the footnote. All percentages are floored (never rounded up).*

| Chapter / Appendix | Total | Mand SYS-* | IMPL | PARTIAL | WRONG | MISSING | UNVERIF | NOT-SYS | Strict Score | Weighted Score |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Front Matter | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 1 | N/A | N/A |
| Ch 1: Pengenalan | 5 | 3 | 1 | 1 | 1 | 0 | 0 | 2 | 33.3% | 50.0% |
| Ch 2: Tujuan & Objektif | 2 | 0 | 0 | 0 | 0 | 0 | 0 | 2 | N/A | N/A |
| Ch 3: Skop | 8 | 3 | 1 | 2 | 0 | 0 | 0 | 5 | 33.3% | 66.6% |
| Ch 4: Definisi & Glosari | 3 | 2 | 1 | 0 | 1 | 0 | 0 | 1 | 50.0% | 50.0% |
| Ch 5: Singkatan | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 1 | N/A | N/A |
| Ch 6: Konsep MC | 18 | 17 | 3 | 9 | 5 | 0 | 0 | 1 | 17.6% | 44.1% |
| Ch 7: Prinsip MC | 4 | 2 | 0 | 0 | 2 | 0 | 0 | 2 | 0.0% | 0.0% |
| Ch 8: Kerangka MC | 13 | 12 | 2 | 7 | 3 | 0 | 0 | 1 | 16.6% | 45.8% |
| Ch 9: Pembangunan MC | 8 | 7 | 0 | 5 | 2 | 0 | 0 | 1 | 0.0% | 35.7% |
| Ch 10: Pelaksanaan MC | 11 | 10 | 1 | 4 | 4 | 1 | 0 | 1 | 10.0% | 30.0% |
| Ch 11: Jaminan Kualiti | 12 | 11 | 0 | 5 | 6 | 0 | 0 | 1 | 0.0% | 22.7% |
| Ch 12: Pengumpulan Kredit *(+2 opt)* | 19 | 17 | 6 | 7 | 4 | 0 | 0 | 0 | 35.2% | 55.8% |
| Ch 13: Pemindahan Kredit | 11 | 11 | 3 | 4 | 4 | 0 | 0 | 0 | 27.2% | 45.4% |
| Ch 14: Stackable Degree | 13 | 13 | 0 | 7 | 6 | 0 | 0 | 0 | 0.0% | 26.9% |
| Ch 15: Tadbir Urus | 6 | 3 | 0 | 2 | 1 | 0 | 0 | 3 | 0.0% | 33.3% |
| Ch 16: Peranan & Tanggungjawab | 7 | 3 | 0 | 1 | 2 | 0 | 0 | 4 | 0.0% | 16.6% |
| Ch 17: Pemantauan & CQI | 9 | 9 | 0 | 6 | 3 | 0 | 0 | 0 | 0.0% | 33.3% |
| Ch 18: Pengurusan Rekod | 12 | 12 | 2 | 8 | 2 | 0 | 0 | 0 | 16.6% | 50.0% |
| Rujukan | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 1 | N/A | N/A |
| Lampiran A | 11 | 11 | 0 | 6 | 4 | 1 | 0 | 0 | 0.0% | 27.2% |
| Lampiran B | 8 | 8 | 0 | 8 | 0 | 0 | 0 | 0 | 0.0% | 50.0% |
| Lampiran C | 10 | 10 | 0 | 7 | 1 | 2 | 0 | 0 | 0.0% | 35.0% |
| Lampiran D | 10 | 10 | 0 | 9 | 1 | 0 | 0 | 0 | 0.0% | 45.0% |
| Lampiran E | 9 | 9 | 1 | 2 | 5 | 1 | 0 | 0 | 11.1% | 22.2% |
| Lampiran F | 11 | 11 | 0 | 6 | 5 | 0 | 0 | 0 | 0.0% | 27.2% |
| Lampiran G | 11 | 11 | 0 | 7 | 2 | 2 | 0 | 0 | 0.0% | 31.8% |
| Lampiran H | 7 | 7 | 0 | 1 | 4 | 2 | 0 | 0 | 0.0% | 7.1% |
| **TOTAL (Mandatory `SYS-*`)** | **241** | **212** | **21** | **114** | **68** | **9** | **0** | **27** | **9.9%** | **36.7%** |

*All-items totals including 2 optional `SYS-*` requirements (`REQ-C12-012` = `MISSING`, `REQ-C12-015` = `WRONG`): `IMPLEMENTED = 21`, `PARTIAL = 114`, `WRONG = 69`, `MISSING = 10`, `UNVERIFIABLE = 0`, `NOT-SYSTEM = 27` (sum = `241`).*

---

## d. Critical and High-Severity Findings with Evidence

### Critical Findings

#### C-01. Fabricated Official Printouts, Signatory Titles, Dates, Arithmetic & Cryptographic Proofs
- **Severity:** Critical
- **Guide Authority:** Cl. 12.8, 14.7, 17.10, A5–A9, D10, E4 (pp. 48, 57, 67, 76–79, 98–99, 101–102)
- **Affected IDs:** `REQ-AA-005`, `REQ-AA-006`, `REQ-AA-007`, `REQ-AA-009`, `REQ-AD-010`, `REQ-AE-004`, `REQ-AG-001`, `REQ-C14-010`, `REQ-C14-012`, `REQ-C17-008`
- **Defects & Code Locations:**
  1. **Suspension Notice (`monitoring/suspension-notice.blade.php:12-18,111`; `QualityReviewController.php:229-245`):** Prints P5 CODL Admin **Farid Hashim** under the fabricated title **`Deputy Dean (Academic), Fakulti Teknologi Maklumat Dan Komunikasi`**; fabricates a fallback transition plan claiming open cohorts finish as planned after `decide()` already held/cancelled them; `printSuspension():233-239` collides `suspensions.id` with `micro_credentials.id`; `DocumentsTest.php:157` fails because BM text was removed.
  2. **Proposal Appendix A Print (`curriculum/print.blade.php:14,189,198,204,263,308-314`):** `rtrim(rtrim((string)$weightsSum, '0'), '.')` strips trailing zeros off integer `"100"`, printing `30% + 30% + 40%` as **`Total: 1%`**; line 204 ignores `$v->assessment_standard` and hardcodes **`Pass mark: 50%`**; line 263 hardcodes `MQF level 6/4 (proposed)`; line 309 hardcodes `now()->format('d/m/Y')`; lines 14,308,314 hardcode signatory titles; fabricates `Digital Credential/Certificate: Yes`; omits A3, A4 parent programme/accreditation/unbundling model, A5 content/activities, A8 (replaced by `"8. Delivery"`), A9 Stages 1 & 3, A10, and Nota Penting A; fails `DocumentsTest.php:130`.
  3. **Pathways Arithmetic & Wrong Expiry Rule (`public/pathway-print.blade.php:58,72`; `public/pathways.blade.php:66,104`):** Systemic `rtrim(rtrim((string) $v, '0'), '.')` bug (present **39 times across 18 Blade views**, also breaking `creditbank/batch.blade.php:45,80` and `student/result-detail.blade.php:7` on score `100`) prints `3+2+2+3 = 10` credits as **`Total Potential Transferable Credits: 1`** and **`1 Max articulable credits`**. Bottom notice on `/public/pathways/1/print` states **"within 10 years of completion"** contradicting Guide 12.8 (**"10 tahun daripada tarikh deposit"**).
  4. **Fabricated W3C `DataIntegrityProof` (`CredentialController.php:197-229`):** `/my-credentials/{id}/sheet` constructs a fake `'type' => 'DataIntegrityProof'`, `'cryptosuite' => 'eddsa-rdfc-2022'` using `'z' . hash('sha256', $c->jwt)`, fake `did:web:127.0.0.1#...`, and non-existent `/status/1` BitstringStatusListEntry.
  5. **QV Package Print (`quality/package.blade.php`):** Prints 2 blank signature lines with wrong label `Programme Leader`, omitting real Coordinator/PTj/QA names, positions, dates, remarks, D2, D8, and Nota Penting D.
- **Evidence:** [REQ-C17-008_suspension_notice.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C17-008_suspension_notice.png), [REQ-AA-009_proposal_print_appendix_a.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AA-009_proposal_print_appendix_a.png), [REQ-AG-001_pathway_print.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AG-001_pathway_print.png), [REQ-C14-012_public_pathways.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C14-012_public_pathways.png), [REQ-AE-004_student_credential_sheet.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-004_student_credential_sheet.png), [REQ-AD-010_qv_package_appendix_d.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AD-010_qv_package_appendix_d.png).

#### C-02. Revoked Credential PII & JWT Download Bypass + False Cryptographic Integrity Badges + Credit/Category Misrepresentation
- **Severity:** Critical
- **Guide Authority:** Cl. 1.2, 6.3.2–6.3.3, 6.6, 8.7.4, 10.5, 10.5.1, 11.2.2, 18.11, 18.13, E2, E5, E6 (pp. 8, 20–23, 29, 36, 39, 70–71, 100–102)
- **Affected IDs:** `REQ-C01-003`, `REQ-C06-013`, `REQ-C06-015`, `REQ-C06-018`, `REQ-C08-011`, `REQ-C08-012`, `REQ-C10-008`, `REQ-C10-009`, `REQ-C18-009`, `REQ-AE-002`, `REQ-AE-005`, `REQ-AE-006`, `REQ-AH-005`
- **Defects & Code Locations:**
  1. `VerifyController.php:39-113` does NOT reject revoked credentials on `/public/verify/{code}/download/json` or `/download/jwt`. Live HTTP test on revoked `UTEM-MC-2026-0009` (`SEC03-2P6RW`) returned **HTTP 200** for both endpoints, leaking recipient PII, claims, and a valid signed Ed25519 JWT and bypassing `verification.public_fields`.
  2. `public/verify-result.blade.php:564-565` unconditionally renders green **`Cryptographic Integrity Verified · Ed25519`** even when status is **`Revoked`**, **`invalid_signature`**, or an unsigned **Digital Badge**.
  3. `VerifyController.php:255-256` & `verify-result.blade.php:446-456` compute `'credits' => $x['claims']['credit'] ?? $c->proposed_credit ?? 3` and render non-UMC categories as `"Stand-Alone Micro-Credential (SAMC)"` with **`graduating credits`**, fabricating `3 graduating credits` on MCEs and unverified credit/MQF on pre-QV/expired SAMCs.
  4. `VerifyController.php:218-229` retroactively marks already-earned valid credentials as **`expired`** once `qv_valid_until` passes (violating Guide 11.2.2 & 18.13).
  5. `VerifyController::file():121-129` ignores tampering of outer VC JSON fields if the embedded `proof_jwt` stays intact; `verification_log.outcome` is `VARCHAR(14)`, failing/truncating 17-char `'invalid_signature'`.
  6. `student/result-detail.blade.php:75` hardcodes `proposed_mqf_level ?? '6'` (leaking MQF 6 on MCE/unverified SAMC); `student/register.blade.php:433-434` displays unverified `proposed_credit`/`proposed_mqf_level`; `student/results.blade.php:113-114` and `records/_record.blade.php:353,392-393` tell MCE learners an ACR will be generated.
- **Evidence:** [REQ-AE-006_revoked_download_bypass.txt](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_revoked_download_bypass.txt), [REQ-AE-006_public_verify_revoked.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_public_verify_revoked.png), [REQ-AE-006_public_verify_valid.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-006_public_verify_valid.png), [REQ-AE-002_public_verify_badge.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-002_public_verify_badge.png), [REQ-AE-005_student_result_detail_leak.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-005_student_result_detail_leak.png).

#### C-03. Stackable Degree Awarded Without Completing Mandatory Remaining Courses or Redeeming Credits
- **Severity:** Critical
- **Guide Authority:** Cl. 12.9, 14.1, 14.5, 14.10, G8 (pp. 48, 53–59, 112–113)
- **Affected IDs:** `REQ-C12-017`, `REQ-C14-001`, `REQ-C14-005`, `REQ-C14-013`, `REQ-AG-008`
- **Defects & Code Locations:**
  - `Plans.php:42-43`: `$c[] = ['key' => 'remaining', 'met' => $rem !== [], ...]` treats having *any* items listed in `remaining` as `met = true`, ignoring whether `status` is `todo` or `planned`.
  - `PathwayController.php:195-210`: `planAward()` inserts `qualification_awards` without checking whether remaining mandatory courses are `done`, without checking graduation CGPA/credit totals/BoK/full PLO coverage, and without calling `CreditLedger::redeem()`.
  - `pathways/plan.blade.php` locks `remaining` inside `<input type="hidden">` after plan creation, preventing updates to `done`.
  - Live `PLAN-2026-0001` (`BITS`) displays `Award recorded` (`SENATE-AWARD-BITS-2026/04`) while `BITI 2213` (Planned), `BITP 3113` (To do), `BITU 3983 Final Year Project I` (To do), and `BITU 3926 Industrial Training` (To do) are all incomplete.
- **Evidence:** [REQ-AG-006_stackable_plan_appendix_g.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AG-006_stackable_plan_appendix_g.png), [REQ-C14-001_student_stackable_plan.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C14-001_student_stackable_plan.png).

#### C-04. Quality Verification (QV) Forgery, Material-Change Carryover & Suspended-QV ACR Generation
- **Severity:** Critical
- **Guide Authority:** Cl. 4, 6.3.2, 7.7, 9.5, 11.2.1–11.2.3, 12.1.1, 12.3.2, 17.7, H7 (pp. 14, 20, 24, 33, 38–40, 45–46, 66, 118)
- **Affected IDs:** `REQ-C04-003`, `REQ-C06-013`, `REQ-C07-004`, `REQ-C09-006`, `REQ-C11-004`, `REQ-C11-005`, `REQ-C11-006`, `REQ-C11-008`, `REQ-C12-003`, `REQ-C12-005`, `REQ-C17-005`, `REQ-AA-007`, `REQ-AH-006`
- **Defects & Code Locations:**
  1. `ProposalController.php:376` accepts `qv_status = 'verified'` directly from a lecturer POST on any draft proposal (including MCE/UMC), bypassing QVC entirely.
  2. `VersionController.php:168` copies `qv_status = 'verified'` and `qv_valid_until` onto materially changed `v2`; `ApprovalController.php:225` never clears QV on Senate approval; `OfferingStatus::releaseForChange()` reopens cohorts bound to superseded `v1`.
  3. `Acr.php:46-47` queries `qv_records` with `whereIn('status', ['verified','superseded','expired','suspended','withdrawn'])` relying only on `valid_from <= completed_on <= valid_until`, while `QvAlerts::end():58` never truncates `valid_until` upon suspension/withdrawal -> **suspended/withdrawn SAMCs still generate credit-bearing ACRs**.
  4. `QvController::result():186` uses `Carbon::addYears(3)` (leap-year overflow `2024-02-29 -> 2027-03-01 > 3 yrs`), accepts already-expired `valid_until`, accepts `0` credit, and overwrites ALL historical `mc_versions` (`:197`). `Qv::completedCohorts()` ignores verified `assessment_results` and aggregates across superseded versions. `QvAlerts::end()` never notifies affected learners.
- **Evidence:** [REQ-AD-001_qv_samc_show.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AD-001_qv_samc_show.png), [REQ-AF-003_student_acr_view.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-003_student_acr_view.png).

#### C-05. Credit Transfer Enforcement Failures (Warning-Only Caps & Non-Transferable Courses, Broken F7, Partial Approval Bug, Dead Re-Evaluation)
- **Severity:** Critical
- **Guide Authority:** Cl. 12.8, 12.9, 13.3–13.6, 14.5.1, F7–F9 (pp. 48–51, 56, 107–108)
- **Affected IDs:** `REQ-C12-015`, `REQ-C12-017`, `REQ-C13-003`, `REQ-C13-005`, `REQ-C13-007`, `REQ-C13-008`, `REQ-C14-006`, `REQ-AF-007`, `REQ-AF-008`, `REQ-AF-009`
- **Defects & Code Locations:**
  1. `EvaluationController.php:211-221`: `Transfers::warnings()` checks programme `transfer_limit_percent`, the 30% UTeM home floor, and `parent_courses.non_transferable`, but `decide()` merely serializes warnings into `transfer_decisions.warnings` and **approves the transfer anyway**. No check prevents approving credits exceeding target `parent_courses.credit` or duplicating a target course (demo `TR-2026-0003` approves `3.0` credits from a `2.0`-credit ACR).
  2. `config/transfer.php:26-36` & `EvaluationController.php:118,203-210`: Omits `na`/`TB` on `mqf`; adds unauthorized `partly` on `credit_slt`, `activities`, `assessment`; `evaluate():118` allows `na` with blank findings on all 8 criteria; `decide():203-210` allows approval even when `accreditation='invalid'`, `mqf='not_matched'`, `currency='needs_review'`, `professional='not_met'`, or `credit_slt='inadequate'`.
  3. `EvaluationController.php:213-218` + `CreditLedger.php:127-134`: Partial transfer approval (`Diluluskan sebahagian`) transitions the **entire** `abc_deposits` row to `provisional` and later `redeemed`, destroying unapproved credits.
  4. `TransferController` & `EvaluationController::evaluate()` permit expired credits with `reevaluate=true`, but `EvaluationController::decide():217` -> `CreditLedger::provisional()` -> `whyNot():54` hard-blocks expired credits.
  5. Live UI/print bug on F9: Stage 02 (`Provisionally Redeemed`) displays `25/10/2026` on ACR web, ACR print, and Transfer letter while `credit_ledger_entries` shows `20/10/2026`.
- **Evidence:** [REQ-AF-007_transfer_eval_f7_form.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-007_transfer_eval_f7_form.png), [REQ-AF-008_transfer_decision_letter_print.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-008_transfer_decision_letter_print.png), [REQ-AF-003_student_acr_view.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-003_student_acr_view.png).

#### C-06. Assessment Integrity Bypass, Failed-Learner Certification Loophole & Modular UMC Breakdown
- **Severity:** Critical
- **Guide Authority:** Cl. 6.1, 6.3.1.1, 7.4, 8.7.3, 10.4, 12.2 (pp. 17–20, 24, 28, 36, 45)
- **Affected IDs:** `REQ-C06-001`, `REQ-C07-002`, `REQ-C08-010`, `REQ-C10-007`, `REQ-C12-004`
- **Defects & Code Locations:**
  1. `Verifier.php:23-65`: `verify()` never checks open `integrity_cases`, allowing verification of marks and minting of Digital Badges while an academic integrity case is open; re-saving marks after flagging overwrites held status to `provisional`.
  2. `ResultsController.php:decideChange()` + `Completion.php:37-75`: Lowering a verified mark below `pass_mark` never revokes `module_achievements` or `digital_badges`, and `Completion::check()` never re-verifies scores — enabling a failed learner to be confirmed complete and issued a credential/ACR. `decideChange()` also fails to notify the learner.
  3. `Completion.php:55-58`: `separate_assessment` UMC model permanently deadlocks because learning modules have no directly linked assessment component.
  4. `module_achievements.enrolment_id` & `Completion::check()` are locked to a single `enrolment_id` (cannot aggregate modules across different cohorts for modular UMC per Cl. 7.4); `ResultsController::save()` refuses to save marks if any module/learner is blank; `CompletionWindow::remind()` never enforces expiry.
  5. `student/results.blade.php:136-138` renders green **`Passed`** for failed learners once marks are verified. `CompletionTest.php:146` fails live.
- **Evidence:** [REQ-C10-007_results_cohort.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C10-007_results_cohort.png), [REQ-AF-011_coordinator_p1_signoff.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-011_coordinator_p1_signoff.png), [REQ-C08-013_student_internal_record.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C08-013_student_internal_record.png).

#### C-07. Live COI Bypass, Lecturer Self-Approval, Cross-PTj Disclosure & Audit-Log Trigger Backdoor
- **Severity:** Critical
- **Guide Authority:** Cl. 9.4, 11.5, 15.7, 16.3, 16.6, 18.11, H6 (pp. 33, 42, 62–64, 70, 118)
- **Affected IDs:** `REQ-C09-004`, `REQ-C11-012`, `REQ-C16-002`, `REQ-C16-004`, `REQ-C18-009`, `REQ-AH-005`
- **Defects & Code Locations:**
  1. `ProposalController.php:123-138`: `/proposals/{id}/print` does not enforce the COI gate; live probe as `U#6` on proposal `#4` returned **HTTP 200**. Returned proposals skip re-review via stale submitted reviews (`ApprovalController.php:115`).
  2. `QualityReviewController.php:143-162`: `reviewOutcome()` has zero Separation of Duties check, allowing a lecturer to self-approve their own periodic review.
  3. `SearchController`: Zero PTj scoping (cross-PTj PII/record leak), plus fake PDPA correction (`DC-2026-0118`) and fake session termination stubs.
  4. `RecordsController.php:157`: `auditRows()` runs `limit(600)` BEFORE PTj filtering, silently hiding PTj events.
  5. Audit immutability migration creates MySQL triggers with an `IF @mcms_disable_triggers` backdoor and no `BEFORE TRUNCATE` protection; `before`/`after` payloads and IP addresses are hidden across all audit UI views and CSV exports.
- **Evidence:** [REQ-C16-004_industry_review_coi.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C16-004_industry_review_coi.png), [REQ-C18-009_records_audit.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-009_records_audit.png), [REQ-C15-005_admin_audit_chain.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C15-005_admin_audit_chain.png).

#### C-08. Non-Synchronizing F10 Corrections & Permanent Discrepancy Blindness + F5 Disclaimer Deletion
- **Severity:** Critical
- **Guide Authority:** Cl. 12.5, 12.10, 18.10, F5, F10 (pp. 47–49, 70, 106–108)
- **Affected IDs:** `REQ-C12-010`, `REQ-C12-019`, `REQ-C18-008`, `REQ-AF-005`, `REQ-AF-010`
- **Defects & Code Locations:**
  - `CreditBankController::correct():206-240` and `Discrepancies::resolve()` insert text log entries only, performing **zero synchronization** of underlying ACR/ABC/Credential records; `correct()` always writes `$a->reference` even when `record_type` is `AbcDeposit` or `Credential`; `discrepancies.key` `UNIQUE` constraint blocks future discrepancy scans from flagging recurring issues once resolved once.
  - `creditbank/ledger.blade.php` deleted the mandatory F5 non-automatic acceptance disclaimer (confirmed visually and failing `CreditBankTest.php:188`); `CreditBankController::ledger()` double-counts expired redeemed credits and sums `a.credits` instead of `d.deposited_credits`.
- **Evidence:** [REQ-AF-005_student_credit_ledger.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-005_student_credit_ledger.png), [REQ-AF-004_abc_deposit_detail.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-004_abc_deposit_detail.png), [REQ-C18-008_discrepancies.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-008_discrepancies.png).

---

### High-Severity Findings

#### H-01. Formal vs Non-Formal Learning Classification Completely Missing (Cl. 4, 12.1.1; pp. 14, 45)
- **Affected IDs:** `REQ-C04-003`, `REQ-C12-003`
- No DB column, UI field, ABC CSV export column, or ACR printout field classifies credit source into **Pembelajaran Formal** (UMC from accredited programme) vs **Pembelajaran Non-Formal** (QV-SAMC only).

#### H-02. Defective CQI Monitoring Indicators, Missing Feedback Sources & Missing C9/C10/Print (Cl. 11.3–11.5, 17.1–17.4, C1–C10; pp. 41–43, 65–66, 87–91)
- **Affected IDs:** `REQ-C11-010`, `REQ-C11-012`, `REQ-C17-001`, `REQ-C17-002`, `REQ-AC-001` through `REQ-AC-010`
- `Indicators.php` includes unverified marks and `null = 0` in mean/median, counts revoked credentials in issuance rate, renders `null` rates as green `Met`, and omits Indicator #6 (access/technical issues). C4 omits 3 of 4 sources (Instructor, Industry, System), excludes `not_completed` students, and pre-checks `4` on the survey form. C5 omits 3 of 8 domains and `TB`. C6 omits Issue title; C7 hardcodes owner; C8 hides before/after evidence from closing dean; C9 and C10 (including 3rd QA role & confirmer display) and print view are completely absent. Parent course/programme changes in `/admin/reference` never trigger UMC periodic reviews (Cl. 11.5).
- **Evidence:** [REQ-AC-001_cqi_appendix_c.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AC-001_cqi_appendix_c.png).

#### H-03. Massive Appendix Collapses & Omissions (Appendices A, B, D, E, G, H)
- **Affected IDs:** `REQ-AA-008`, `REQ-AA-010`, `REQ-AA-011`, `REQ-AB-002`–`008`, `REQ-AD-003`–`009`, `REQ-AE-003`, `REQ-AE-008`, `REQ-AE-009`, `REQ-AG-004`, `REQ-AG-005`, `REQ-AG-009`, `REQ-AG-011`, `REQ-AH-003`, `REQ-AH-004`, `REQ-AH-007`
- **Appendix B:** 38 items (B2–B7) + 10-row B5 record table collapsed into **6 rows**; missing 3rd B8 role (P5 Support), Nota Penting B, and print view ([REQ-AB-001_offering_readiness_appendix_b.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AB-001_offering_readiness_appendix_b.png)).
- **Appendix D:** 42 evidence items (D3–D7) collapsed into **5 rows**; 3 of 9 D8 components missing; D9 missing Required Actions; package omits D2/D8/D9-details/Nota D ([REQ-AD-001_qv_samc_show.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AD-001_qv_samc_show.png), [REQ-AD-010_qv_package_appendix_d.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AD-010_qv_package_appendix_d.png)).
- **Appendix E:** Certificate omits PTj/Faculty name, SLT, LOs, cohort, and signatory position ([REQ-AE-003_student_certificate.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-003_student_certificate.png)); E8 checklist unpersisted, incomplete, and skipped on `replace()`; E10 3-role sign-off & Nota Penting E missing; Digital Badge lacks criteria, LOs, QR code, disclaimer, and revocation status.
- **Appendix G:** G5 Body of Knowledge mapping 100% missing; G4 missing Evidence & Gaps; G9 missing approved-equivalence & remaining credits; G11 3-role sign-off & Nota Penting G 100% missing ([REQ-AG-003_pathway_mapping_appendix_g.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AG-003_pathway_mapping_appendix_g.png)).
- **Appendix H:** H4 (12 record classes ownership matrix), H8 (Governance Action Register), H9 (Governance Matrix Confirmation), and Nota Penting H 100% missing; H5 shows only 9 of 10 cohort artefacts (missing #9 ACR/ABC & #10 changes/corrections/incidents) without Location/Owner columns ([REQ-AH-004_cohort_records_appendix_h5.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-004_cohort_records_appendix_h5.png)).

#### H-04. Fake Registration Checks, Unchecked Proposal Saves & Missing Learning Activities/SLT Breakdown (Cl. 6.3.1, 6.3.3, 8.1–8.3, 10.1, 10.3; pp. 19–21, 25–26, 34–35)
- **Affected IDs:** `REQ-C06-006`, `REQ-C06-014`, `REQ-C08-001`, `REQ-C08-003`, `REQ-C10-001`, `REQ-C10-003`, `REQ-C16-006`
- `student/register.blade.php:68-127,188-190` hardcodes 4 green `Passed` pre-registration checks and auto-submits `<input type="hidden" name="prerequisites_ok" value="1">`, while `Enrolments::reasons()` never evaluates `eligible_groups` ([REQ-C06-013_student_register_mce_and_fake_checks.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C06-013_student_register_mce_and_fake_checks.png)).
- `ProposalChecker` runs ONLY at proposal submit — never on save, Senate approval, or offering creation, allowing live approved 40h MCE (`MC#07`) and 40h/120h UMC (`MC#12`) offerings.
- Structured module `learning_content` and `learning_activities` (with activity-to-SLT/LO breakdown, Cl. 10.1 & A5) do not exist in the schema.

#### H-05. F11 Verification Row 3 Overwrite, Multi-ACR Verifier Drop & Bilingual EN/BM Breakdown (Cl. 12.4.2, F1–F11; pp. 46, 105–109)
- **Affected IDs:** `REQ-C12-007`, `REQ-AF-001`–`004`, `REQ-AF-008`, `REQ-AF-011`
- `CreditBankController::record():191` overwrites `acr_verifications(role_slot='records_abc')` created at `confirm():113` (erasing the Registry confirmer's F11 entry when ABC deposit is recorded). Multi-ACR transfer letters drop all but the last ACR's verifiers.
- **Bilingual EN/BM Audit:** **655 distinct `__()` strings** in Blade templates are missing from `app/lang/ms.json` (>50% of Pathways views, 113 Records/Audit keys, 28 print keys), plus unwrapped English in `Plans.php`, `Trace.php`, `Reports.php`, `approval/pack.blade.php`, `curriculum/print.blade.php`, and `Role::label()`. `users.position_ms` is completely ignored in BM ACR/letter prints ([REQ-AF-011_acr_print_bm.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-011_acr_print_bm.png)); `Acr.php:141` saves `text_en` LOs only; BM ACR print mistranslates Stage 1 Available as `"Lulus"`; `ForbiddenClaims.php` has **zero BM regexes**; header `EN | BM` toggle never updates `users.locale`, leaving notifications in English with raw English status strings injected into BM templates.

### Phase 4 Cross-Cutting Verification Summary

| Cross-Cutting Check | Audit Finding | Key Evidence |
|---|---|---|
| **Lifecycle Integrity** | 8 lifecycle tables exist, but boundaries leak: `planAward()` awards degrees with `todo` courses & no redemption; `VerifyController` calls MC credits `"graduating credits"`; F5 ledger deleted non-automatic disclaimer; `Acr::prepare()` allows suspended QV into ACR. | `REQ-AG-006`, `REQ-AE-006`, `REQ-AF-005` |
| **UMC / SAMC / MCE Eligibility** | MCE blocked in ACR/QV/Pathways, but `ProposalController:376` accepts `qv_status='verified'` on MCE, `VerifyController:255` labels MCE as SAMC with `3 graduating credits`, `result-detail:75` leaks `MQF 6`, and live approved `MC#07` is a 40h/1.5cr MCE. | `REQ-C06-014`, `REQ-C06-013`, `REQ-AE-005` |
| **Time Rules (Real Data)** | ABC `deposit_date + 10y` math in `CreditLedger::deposit():29` verified (`2026-09-15 -> 2036-09-15`), BUT `pathway-print` says `"10 years of completion"`, `ComprehensiveTestingSeeder` hardcodes 5-year expiry (`2031`), `QvController:186` overflows leap years, live `QV#02` spans `1,341 days (3.67 yrs)`, and `VerifyController:218` retroactively expires earned credentials. | `REQ-AG-001`, `REQ-AF-003`, `demo_data_report.txt` |
| **State Machines** | `Available -> Provisional -> Redeemed/Available` exists, BUT partial approval locks full deposit, `planAward()` skips `redeem()`, F9 prints wrong Provisional date (`25/10` vs `20/10`), and expired-credit re-evaluation deadlocks at `whyNot():54`. | `REQ-AF-003`, `REQ-AF-008`, `CreditLedger.php` |
| **Limits & Thresholds** | 30% UTeM floor, programme transfer cap, and `non_transferable` are warning-only in `EvaluationController::decide()`; all statutory thresholds (`40h`, `3y`, `10y`, `30%`, `1 sem`) are editable by P5 CODL Admin via `/admin/settings`. | `REQ-C15-002`, `REQ-AF-007` |
| **Traceability & Audit Trail** | 10-stage trace view works on screen (`REQ-C18-012`), BUT F10 corrections never sync records, MySQL triggers have `@mcms_disable_triggers` bypass & no `TRUNCATE` guard, audit UI/CSV hide payloads & IP, and `auditRows()` limits 600 before PTj filter. | `REQ-C18-012`, `REQ-C18-009`, `REQ-C15-005` |
| **Prohibited Claims & Notices** | `ForbiddenClaims` has 0 BM patterns and scans only 5 offering fields (misses proposals/pathways); Nota Penting A/B/C/D/E/F/G/H missing; F5 disclaimer deleted; `/public/pathways` misstates 30% rule. | `REQ-AF-005`, `REQ-C14-012`, `REQ-C10-004` |
| **Bilingual EN/BM** | 655 missing Blade keys in `ms.json`, English leaks in BM ACR/letter (`position_ms` ignored, `text_en` LOs only, English F2 refs), `DocumentsTest:157` fails on missing BM suspension notice, `EN/BM` toggle does not persist `users.locale`. | `REQ-AF-011_acr_print_bm.png`, Pest output |
| **Printables & Letters** | Major fabrications/omissions on Appendix A (`Total: 1%`, `Pass mark: 50%`, `now()`), Suspension Notice (`Farid Hashim - Deputy Dean`), Pathway Print (`Total: 1`, `"10 years of completion"`), Credential Sheet (fake `DataIntegrityProof`), QV Package (`Programme Leader`), Certificate (no PTj/SLT/LOs/position); no print views for Appendix B, C, F5, G plan, H5. | `REQ-AA-009`, `REQ-C17-008`, `REQ-AG-001`, `REQ-AE-004` |

---

## e. Role / RACI Findings (Appendix H)

### 1. Guide Roles (Appendix H2, p. 115) vs Actual System Roles (`app/app/Modules/Identity/Role.php`)

| Guide Role (H2) | Guide Scope | System Role Enum | Live Status & Segregation Defect |
|---|---|---|---|
| **P1** Penyelaras MC / Pemilik Akademik | Coordinates design, delivery, assessment records, CQI draft | `lecturer` | Active (6 seeded). **Defect:** Can self-approve own periodic review (`QualityReviewController:143`) and set `qv_status='verified'` on draft (`ProposalController:376`). |
| **P2** PTj / Fakulti | Academic/PTj endorsement, operational readiness, cohort oversight, QV prep, CQI oversight | `deputy_dean` | **Collapsed mega-role** merging P2 + P3 + P4 + P6 (credentials & admissions) + partial P7. |
| **P3** Pihak Berkuasa Akademik (Senat / JKKP) | Offering approval, credit transfer decision, Stackable Degree approval & award | `deputy_dean` | **No separate P3 role.** Same `deputy_dean` endorses (P2) AND records Senate approval (P3), approves offerings AND decides credit transfers. |
| **P4** Jaminan Kualiti (QA) | Academic/QA review, QV readiness/submission oversight, CQI & periodic review QA confirmation | **MISSING** | **100% absent** from `Role.php`, DB, routes, and sign-off tables (A9, C10, D10, G11). |
| **P5** Sokongan Platform / Operasi / Teknikal | LMS/platform support, system administration — **must NOT usurp academic decisions** (Cl. 15.2.3) | `codl_admin` | Active (2 seeded). **Defect:** Can unilaterally change statutory academic rules (`/admin/settings`), edit accredited programmes/parent courses (`/admin/reference`), lift retention holds, and self-assign `deputy_dean`/`records_depositor` (`UserAdminController` SoD checks only `lecturer` vs `deputy_dean`). |
| **P6** Unit Rekod / Pengeluar Credential | Custodian of achievement records, Digital Badges, Certificates/Digital Credentials, ACRs, admissions | Split & broken (`deputy_dean` + `records_depositor`) | **Inverted RBAC:** `records_depositor` gets **HTTP 403** on `/credentials/*` while `deputy_dean` manages credentials and records admissions! |
| **P7** Pihak Depositor ABC Diberi Kuasa | Deposits consented ACRs to ABC & updates ABC Single Redemption statuses | `records_depositor` (partial) + `deputy_dean` | `records_depositor` handles `/deposits/*`, **but** `deputy_dean` executes ABC redemption/release on `/transfers/*` and `records_depositor` merges P6 ACR confirmation with P7 deposit (overwriting F11 Row 3). |
| **P8** Peserta / Pelajar | Enrols, learns, gives ABC consent, applies for credit transfer | `student` | Active (12 seeded). |
| *(System-only extra)* | Peer reviewer / Industry SME | `reviewer` (DEAD), `industry_expert` | **`Role::Reviewer` (`reviewer`) is 100% dead code** (0 routes, 0 seeded users, 403 on `/reviews`, rejected 422 by `ApprovalController::assign()`, empty sidebar). `industry_expert` works for comment-only review. |

*Additional Routing Deadlock:* 5 of 6 `approval.route.*` settings in `/admin/settings` (`endorsement`, `senate`, `transfer`, `stackable`, `suspension`) **deadlock** if configured to any role other than `deputy_dean` because Laravel route middleware hardcodes `role:deputy_dean`.

### 2. Appendix H3 Lifecycle RACI Comparison (18 Activities, p. 116)

| # | Lifecycle Activity (Guide H3) | Guide `A` / `R` | Actual System Role Enforced | RACI Verdict |
|---|---|---|---|---|
| 1 | Needs analysis & MC design | `A: P2`, `R: P1` | `lecturer` drafts | Aligned |
| 2 | Category determination (UMC/SAMC/MCE) | `A: P2`, `R: P1, P2` | `lecturer` proposes; `deputy_dean` confirms | Collapsed (`deputy_dean` also P3) |
| 3 | Academic & QA review | `A: P2`, `R: P2, P4` | `lecturer`/`industry_expert` review; `deputy_dean` endorses | **GAP:** P4 missing; `Role::Reviewer` dead; COI bypassed on `/print` |
| 4 | Offering approval | `A: P3`, `R: P2, P3` | `deputy_dean` records both P2 endorse & P3 Senate | **VIOLATION:** P2 & P3 collapsed into one role |
| 5 | Pre-offering operational readiness (App. B) | `A: P2`, `R: P1, P5` | `lecturer` + `deputy_dean` | **GAP:** P5 Support sign-off missing |
| 6 | Delivery & learner support | `A: P2`, `R: P1, P5` | `lecturer` manages cohort; `codl_admin` UCreds | Partial (LMS external) |
| 7 | Assessment & marking | `A: P2`, `R: P1` | `lecturer` marks; `deputy_dean` verifies | **GAP:** `Verifier` ignores integrity cases |
| 8 | Completion verification | `A: P2`, `R: P1, P2` | `lecturer` P1 sign-off -> `deputy_dean` P2 confirm | Aligned on role gate (broken by lowered-score loophole) |
| 9 | Digital Badge / Certificate / Digital Credential issuance & management | `A: P2/P6`, `R: P6` | **`deputy_dean`** (P6 `records_depositor` = **HTTP 403**) | **VIOLATION:** Wrong role owns `/credentials/*` |
| 10 | ACR preparation & confirmation | `A: P6`, `R: P6` | `records_depositor` confirms | Partial (merged with P7; F11 Row 3 overwritten at deposit) |
| 11 | Learner consent for ABC deposit | `A: P6/P7`, `R: P8` | `student` (`P8`) consents via `/my-credit/{id}/consent` | Aligned |
| 12 | ABC deposit & credit status update | `A: P7`, `R: P7` | `records_depositor` deposits; **`deputy_dean`** updates redemption/release | **VIOLATION:** P2/P3 performs P7 ABC redemption updates |
| 13 | Credit transfer evaluation & decision | `A: P3`, `R: P2, P3` | `deputy_dean` does validity (P6), F7 eval & decision (P3) | **VIOLATION:** No P3 separation from P2/P6 |
| 14 | Stackable Degree mapping & eligibility | `A: P3`, `R: P2, P3, P4` | `lecturer` drafts; `deputy_dean` approves & awards | **VIOLATION:** P3/P4 missing; award under-gated |
| 15 | Monitoring & CQI | `A: P2`, `R: P1, P2, P4` | `lecturer` drafts; `deputy_dean` confirms | **GAP:** P4 QA missing; periodic review self-approvable by `lecturer` |
| 16 | SAMC Quality Verification (QV) prep & submission | `A: P2`, `R: P1, P2, P4` | `lecturer` drafts; `deputy_dean` submits & records outcome | **GAP:** P4 QA missing; `lecturer` can forge `qv_status='verified'` |
| 17 | Material change & version control | `A: P2/P3`, `R: P1, P2, P4` | `lecturer` creates v2; `deputy_dean` approves | **VIOLATION:** P3/P4 missing; v2 copies verified QV |
| 18 | Suspension, termination & learner transition | `A: P2/P3`, `R: P2, P5, P6` | `deputy_dean` decides; notice prints **`Farid Hashim` as Deputy Dean** | **VIOLATION:** Wrong person & fabricated title in demo/print |

**Evidence:** [REQ-AH-001_admin_users_roles.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-001_admin_users_roles.png), [REQ-AH-002_depositor_blocked_from_credentials_403.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-002_depositor_blocked_from_credentials_403.png), [REQ-AH-002_deputy_dean_credentials_admin.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-002_deputy_dean_credentials_admin.png), [REQ-AH-002_deputy_dean_senate.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AH-002_deputy_dean_senate.png).

---

## f. Findings About Seeded / Demo Data and Documentation That Overclaim

### 1. Seeded / Demo Data Violations (`mcms_demo` & Seeders)
1. **Approved MCE Violating `< 40h` SLT and Non-Credit Rules:** `MC#07` (*Lean Six Sigma Yellow Belt*, `V#07`, `status='approved'`, live on `/public/offering/4` and `/register/4`) is categorized `MCE` yet has **`40h` SLT** (`>= 40h`), **`proposed_credit = 1.5`**, **`proposed_mqf_level = 3`**, and `credit_sought = 1` (violates Cl. 6.3.3 & Jadual 1). `MC#03` and `MC#22` (MCEs) also carry `proposed_mqf_level` (`3` and `4`). Evidence: [REQ-C06-014_public_offering_mce_demo_violation.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C06-014_public_offering_mce_demo_violation.png), [REQ-C06-013_student_register_mce_and_fake_checks.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C06-013_student_register_mce_and_fake_checks.png).
2. **12 of 17 UMC Versions Mismatch Parent Course SLT:** Approved `MC#01` (*Data Structures*, `80h` vs parent `120h`, shows red blocking error while `Approved` in [REQ-AA-001_proposal_workspace.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AA-001_proposal_workspace.png)) and open public UMC `MC#12` (*Electric Circuit Theory & Power Systems*, `40h` module vs `3-credit / 120h` parent course `BENE 1123` in [REQ-C10-004_public_offering_open.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C10-004_public_offering_open.png)). Three SAMCs (`MC#11`, `MC#18`, `MC#21`) improperly set `parent_course_id`.
3. **5 of 10 `qv_records` Attached to UMCs + 3.67-Year QV Cycle:** `QV#02` (`MC#1`), `QV#06` (`MC#17`), `QV#07` (`MC#20`), `QV#08` (`MC#23`), and `QV#09` (`MC#5`) are all UMCs (Guide Jadual 1 & 11.2 restrict QV to SAMC). `QV#02` spans `2026-03-04` to `2029-11-04` = **1,341 days (3.67 years > 3-year max)**.
4. **P5 CODL Admin (`U#12 Farid Hashim`) Recorded Across Academic, Registry & ABC Roles:** Seeded as ABC depositor & F11 Row 3 on `ACR-2026-0001` ([REQ-AF-003_student_acr_view.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AF-003_student_acr_view.png)), approver on `corrections #1 & #2` ([REQ-C18-008_discrepancies.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-008_discrepancies.png)), approver on `disposal_logs #1 & #2` directly contradicting the same page's *"CODL cannot approve disposal"* banner ([REQ-C18-010_retention.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-010_retention.png)), corrector on credential `UTEM-MC-2026-0002` ([REQ-AE-007_credential_correction_audit.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-AE-007_credential_correction_audit.png)), and decision-maker printed as Deputy Dean on `suspensions #1` ([REQ-C17-008_suspension_notice.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C17-008_suspension_notice.png)).
5. **Completed & ABC-Deposited UMC With `0 / 2` Modules Achieved:** Amir Hakimi's `ENR-2026-0017` (*Object-Oriented Software Construction*) shows `0 / 2 Achieved` alongside `Completion: Complete`, `UTEM-MC-2026-0010`, and deposited `ACR-2026-0007` (`ABC-MYS-2026-098476`) ([REQ-C08-013_student_internal_record.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C08-013_student_internal_record.png)).
6. **Stackable Plan, Transfer & Seeder Anomalies:** `PLAN-2026-0001` stores `utem_percent = 100.0%` despite `transfer_credits = 5.0` and holds degree award `AWARD #1` while all 4 remaining courses are `todo`/`planned`; `TR-2026-0003` grants `3.0` credits from a `2.0`-credit ACR; `transfer_applications.document_checklist` uses invalid keys (`abc_statement`, `content_outline`) causing `TR-2026-0005` to display `2 / 8 ticked` with only 1 box checked; `equivalence_criteria` stores invalid `'equivalent'` across all 8 criteria with fabricated `">= 80% equivalence"` text (Guide never specifies 80%); `ACR-2026-0001` stores generic synthetic LOs instead of Cloud Native Development's actual LOs; `ComprehensiveTestingSeeder.php:119-125` hardcodes **5-year** `credit_expiry` (`2031`) and invalid `deposit_status='confirmed'`; audit event `#23` logs `Hakim Rahman` confirming `ACR-2026-0001` while `acrs.confirmed_by` lists `Nurul Izzah`.

### 2. Documentation & Prior Audit Reports That Overclaim Compliance

| Document | Claim Made | Verified Reality |
|---|---|---|
| [MCMS_Appendix_F_Compliance_Audit_v2.2.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/Document/Audit/MCMS_Appendix_F_Compliance_Audit_v2.2.pdf) | *"Status: FULL COMPLIANCE VERIFIED — 11/11 — 409/409 Pest tests PASS — ZERO-FABRICATION RULE ENFORCED — Transfers::warnings() enforces both programme transfer_limit_percent and UTeM 30% floor — Fig. 1 bilingual headers — Fig. 2 Not yet recorded on P1 & real P7 depositor."* | **False on every claim:** Appendix F scores **0.0%** strict (6 `PARTIAL`, 5 `WRONG`); **4 Pest tests FAIL** (including `CreditBankTest.php:188` on deleted F5 disclaimer); `Transfers::warnings()` is warning-only; Fig. 1 headers are English-only; Fig. 2 prints P5 CODL Admin **Farid Hashim** in F4/F11 Row 3 and populated `Dr. Aina Zulkifli` in Row 1; F9 prints wrong Provisional date `25/10` vs `20/10`; F10 performs zero record sync. |
| [MCMS_Guide_Compliance_and_Verification_Audit_v2.2.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/Document/Audit/MCMS_Guide_Compliance_and_Verification_Audit_v2.2.pdf) | *"FULL APPENDIX F1–F11 & APPENDIX H COMPLIANCE ACHIEVED — 0 anomalies across all 14 ACRs and 5 transfer applications — 7 active system roles cleanly partitioned — /records/audit displays IP address, payload & hash-verify button — 80% LO-matching."* | **False & fabricated references:** Cites non-existent routes (`/curriculum/wizard`, `/curriculum/proposals`, `/creditbank`, `/pathways/{id}/plan`, `/records/mismatches`, `/records/cohorts/{id}/checklist`), non-existent DB columns (`mc_versions.unbundling_model`, `mc_versions.origin`, `is_credit_bearing`), and non-existent view (`tabs/_mapping.blade`); only 6 roles work (`Role::Reviewer` dead; P4 missing); `/records/audit` shows NO IP, NO payload, and NO verify button ([REQ-C18-009_records_audit.png](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/evidence/REQ-C18-009_records_audit.png)); invents "80% LO-matching" not in the Guide; 14 ACRs & 5 transfers contain multiple live anomalies documented above. |
| [GEMINI.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/GEMINI.md) & [HANDOVER.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/HANDOVER.md) | *"7 active roles"*, *"410 tests, 5,900+ assertions, 100% green"*, *"3,211 keys"*, strict Guide compliance. | Only **6** active roles (`reviewer` dead); Pest suite is **4 FAILED / 406 PASSED**; `ms.json` has 3,213 keys yet **655 Blade keys are missing**; strict Guide compliance is **9.9%**. |
| [README.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/README.md), [app/README.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/app/README.md), [MCMS_Appendix_F_Compliance_Audit.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MCMS_Appendix_F_Compliance_Audit.pdf) | Overstate Guide compliance, retain stale/obsolete v1 audit at repository root, and omit known enforcement/security/printout failures. | Directly contradicted by live runtime evidence and Pest failures. |

---

## g. `NOT-SYSTEM` Items (Institutional / Governance Responsibilities Outside Software)

All 27 `PROCESS` (20) and `INFO` (7) items extracted from the Guide are excluded from software scoring and require institutional policy, committee governance, staffing, or operational SOPs outside MCMS:

| ID | Chapter | Clause | Page | Type | Role | Mand/Opt | Requirement Summary |
|---|---|---|---|---|---|---|---|
| `REQ-FM-001` | Front Matter | Cover / TOC | 1–7 | INFO | Universiti / CODL | N/A | Document metadata (*Garis Panduan MC UTeM v1.0*, 18 Sept 2026). |
| `REQ-C01-002` | Ch 1 | 1.2 | 8 | PROCESS | Universiti / P2 / P4 | Mandatory | Align MC development/implementation with MQF, MQA guidelines, and institutional policies. |
| `REQ-C01-005` | Ch 1 | 1.4 | 9 | PROCESS | Universiti / P2 / P1 | Mandatory | Adopt Guide as official institutional reference across faculties, PTjs, committees, and staff. |
| `REQ-C02-001` | Ch 2 | 2.1–2.2 | 10 | INFO | Universiti | N/A | Guide purpose and uniform lifecycle framework objectives. |
| `REQ-C02-002` | Ch 2 | 2.3 | 10 | PROCESS | P2 | Mandatory | Apply Guide across all PTjs and external collaborations; PTj ensures partner compliance. |
| `REQ-C03-002` | Ch 3 | 3.2 | 11 | INFO | Universiti / P2 | N/A | End-to-end lifecycle scope definition. |
| `REQ-C03-004` | Ch 3 | 3.4 | 11 | PROCESS | P2 / P4 | Mandatory | Implement category-appropriate QA (academic review, constructive alignment, QV, monitoring, CQI). |
| `REQ-C03-006` | Ch 3 | 3.6 | 11 | INFO | P1 / P2 | N/A | Illustrative target learner groups (students, graduates, professionals, public). |
| `REQ-C03-007` | Ch 3 | 3.7 | 11–12 | PROCESS | Universiti / P2 | Mandatory | Retain UTeM oversight over academic standard, QA, and compliance for external-partner MCs. |
| `REQ-C03-008` | Ch 3 | 3.8 | 12 | PROCESS | Universiti / P2 | Mandatory | Read Guide alongside UTeM policies and specific MQA/regulatory/professional body rules. |
| `REQ-C04-001` | Ch 4 | 4 | 13–15 | INFO | Universiti | N/A | Authoritative glossary of 20 MC ecosystem terms. |
| `REQ-C05-001` | Ch 5 | 5 | 16 | INFO | Universiti | N/A | Standard list of 13 abbreviations. |
| `REQ-C06-016` | Ch 6 | 6.3.3 | 21 | PROCESS | P1 / P2 / P3 | Optional | Institutional reclassification and re-approval of MCE as SAMC prior to QV. |
| `REQ-C07-001` | Ch 7 | 7.1–7.3 | 23–24 | PROCESS | P1 / P2 / P4 | Mandatory | Institutional principles: policy compliance, LO-oriented design, competency-based evidence. |
| `REQ-C07-003` | Ch 7 | 7.5–7.6 | 24 | PROCESS | P1 / P2 / P4 | Optional | Encourage industry/professional/SME involvement; apply QA broadly and QV specifically to SAMC. |
| `REQ-C08-004` | Ch 8 | 8.4–8.5 | 27 | PROCESS | P1 / P2 | Mandatory | Ensure content currency/adequacy, UMC parent linkage, and constructive alignment of activities. |
| `REQ-C09-003` | Ch 9 | 9.3 | 32 | PROCESS | P1 | Mandatory | Author constructively aligned learning materials complying with copyright/IP law. |
| `REQ-C10-002` | Ch 10 | 10.2 | 35 | PROCESS | P2 / P5 | Mandatory | Operate external learning platform (uLearn/UCreds) per UTeM infosec and PDPA policies. |
| `REQ-C11-001` | Ch 11 | 11.1 | 37 | PROCESS | P2 / P4 | Mandatory | Preserve LO quality, credential credibility, academic integrity, and UMC parent linkage. |
| `REQ-C15-001` | Ch 15 | 15.1, 15.3 | 59, 61 | PROCESS | Universiti / P2 / P3 | Mandatory | Establish accountable, transparent, segregated lifecycle governance with designated committees. |
| `REQ-C15-004` | Ch 15 | 15.5–15.6 | 61–62 | PROCESS | Universiti / P2 / P6 | Mandatory | Institutional policy defining record ownership, access authority, and category-specific oversight. |
| `REQ-C15-006` | Ch 15 | 15.8 | 62 | PROCESS | P2 / P3 / P5 / P6 | Mandatory | Inter-party institutional coordination to reconcile discrepancies against official records. |
| `REQ-C16-001` | Ch 16 | 16.1–16.2 | 62–63 | PROCESS | Universiti / P2 | Mandatory | University policy setting and PTj stewardship within academic expertise. |
| `REQ-C16-003` | Ch 16 | 16.4–16.5 | 63 | PROCESS | Pembangun / Pengajar | Mandatory | Developer design/material preparation and instructor teaching/assessment per approved design. |
| `REQ-C16-005` | Ch 16 | 16.7 | 64 | PROCESS | Industri / SME | Optional | External industry/SME advisory contribution without replacing UTeM academic responsibility. |
| `REQ-C16-007` | Ch 16 | 16.9–16.10 | 64–65 | PROCESS | Universiti / P2 / P1 | Mandatory | Institutional adoption of Jadual 2 General Governance Function Matrix and audited reconciliation. |
| `REQ-REF-001` | Rujukan | 1–10 | 72 | INFO | Universiti | N/A | Ten statutory (Akta 679), MQA, and UTeM policy references; latest rules take precedence. |

---

## h. Open Questions (Guide Silent or Ambiguous)

Where [MC UTeM Guide.pdf](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/MC%20UTeM%20Guide.pdf) is silent, ambiguous, or internally inconsistent, no requirement was invented; UTeM Academic Authority / CODL must clarify:

1. **Internal Contradiction in Rajah 2 vs Ch. 6.3.1 / 12.3.1 (pp. 19–20, 45):** Rajah 2 Step 2 states *"Kredit bagi setiap modul disimpan dalam ABC"*, directly contradicting the body text of Cl. 6.3.1 (p. 19) and Cl. 12.3.1 (p. 45), which state that individual UMC modules/badges do NOT confer full course credit and ALL modules must be completed before UMC completion and ACR preparation (except where a specific module is separately approved as a credit unit). Confirm body text takes precedence unless a module is explicitly approved as a standalone credit unit.
2. **Exact Programme-Level Credit Transfer Cap % (Cl. 13.5, p. 50; Cl. 14.5.1, p. 56):** The Guide mandates a minimum **30%** direct UTeM graduating credit floor (Cl. 14.5.1) and states the remaining 70% is NOT an automatic transfer entitlement, while Cl. 13.5 refers to *"had pemindahan kredit yang berkuat kuasa bagi program dan laluan kemasukan berkenaan"* without giving a fixed percentage cap. Confirm whether per-programme caps (e.g., 30%–50% or APEL/MQA statutory caps) must be hard-blocked by Senate-locked reference data.
3. **Minimum Learning-Outcome Equivalence Percentage Threshold (Cl. 13.3, p. 50; Appendix F7, p. 107):** Earlier project documents and demo seeders cite **`>= 80% equivalence`**, but the Guide NEVER states 80% (it uses qualitative F7 scales: `Setara / Sebahagian / Tidak`). Confirm whether UTeM Senate mandates a numeric LO match threshold (e.g., 80%) or strictly qualitative F7 criteria.
4. **Partial Credit Transfer Approval vs ABC Deposit Splitting (Cl. 12.9, p. 48; Appendix F8, p. 108):** Appendix F8 includes `Diluluskan sebahagian`, and Cl. 12.9 enforces Single Redemption. The Guide is silent on whether partial approval splits an ABC deposit (holding only approved credits as `Provisionally Redeemed` and leaving the remainder `Available`) or locks the entire ACR deposit. Clarify exact national ABC mechanism behaviour.
5. **Modular UMC Completion Window Duration (Cl. 7.4, p. 24; Cl. 12.2, p. 45):** Guide requires modular UMC completion within *"tempoh kesahan pembelajaran"* without specifying a default number of months/years (currently blank in `/admin/settings`).
6. **Non-ACR Record Retention Periods (Cl. 18.12, p. 70; Appendix H4, pp. 116–117):** Guide mandates perpetual retention for ACRs in ABC (Cl. 12.8) and controlled disposal after retention expiry for other records, without stating exact retention durations for the 12 record classes in H4.
7. **Periodic Review Frequency (Cl. 11.5, p. 42; Cl. 17.9, p. 67):** Guide mandates periodic review without stating a fixed cycle interval (system defaults to 24 months, editable by CODL Admin).
8. **Rajah 2 Blended 40:40:20 Split (p. 20) & Rajah 3 Typographical Variants (p. 22):** Confirm whether Rajah 2's 40:40:20 breakdown is illustrative rather than normative, and confirm Rajah 3 labels *"Unit Micro-Credential"* / *"Micro-Credential for Experience"* are typographical errors for *Unbundled Micro-Credential (UMC)* and *Micro-Credential Eksekutif (MCE)*.
9. **Passing Score / Assessment Achievement Standard (Cl. 8.6, pp. 27–28):** Guide leaves the exact pass threshold to each approved offering (confirm `curriculum/print.blade.php` must print the offering's approved `assessment_standard`, never hardcoded `50%`).
10. **External-Institution MC Credit Ingestion (Cl. 12.7, p. 47; Cl. 13.2, p. 49):** Guide states external MCs *"boleh dipertimbangkan"* (optional/permissive). Clarify whether MCMS Phase 1 must support external ACR entry/import or whether external transfer applications are handled outside MCMS.

---

## i. Recommended Fix Order (By Severity, Then Dependency)

### Priority 1 — Critical Security, Data Integrity & Official Fabrication Blockers (Fix Immediately)
1. **Patch Public Verification & Revocation Leaks (`VerifyController.php`, `verify-result.blade.php`, migration):** Block `/download/json` and `/download/jwt` on revoked credentials (410/403); conditionally render cryptographic verification banner ONLY when signature is actually verified and credential is active (never on revoked, `invalid_signature`, or unsigned badges); remove `proposed_credit ?? 3` and SAMC fallback labels (never print `"graduating credits"` or credit/MQF on MCE/unverified SAMC); stop retroactively expiring earned credentials after `qv_valid_until`; verify outer VC JSON matches signed JWT claims; widen `verification_log.outcome` to `VARCHAR(32)`.
2. **Eliminate Official Document Fabrications & Systemic `rtrim` Bug:**
   - Replace all 39 occurrences of `rtrim(rtrim((string) $v, '0'), '.')` across 18 Blade views with a shared `number_format((float) $v, 2, '.', '')` helper so `100` never prints as `1` and `10` never prints as `1`.
   - Fix `monitoring/suspension-notice.blade.php` & `QualityReviewController::printSuspension()`: separate `suspensions.id` from `micro_credentials.id`, print actual `users.position`/`position_ms` of the authorized decider (never hardcoded `Deputy Dean`), remove fabricated fallback transition plan, restore BM wording (fixes `DocumentsTest:157`).
   - Fix `CredentialController::sheet()`: remove fabricated `sha256` `DataIntegrityProof`, fake `did:web:127.0.0.1`, and fake `/status/1`; display authentic Ed25519 JWT proof and configured `verification.public_url`.
   - Fix `curriculum/print.blade.php`: print real `assessment_standard` (remove hardcoded `50%`), real approval dates/signatories (remove `now()` & hardcoded titles), real `credential_types`, restore all 10 Guide Appendix A sections + Nota Penting A (fixes `DocumentsTest:130`).
   - Fix `public/pathway-print.blade.php` & `public/pathways.blade.php`: fix `"within 10 years of deposit date"` and fix contradictory 30% card text.
3. **Close Live Security & Access Control Holes:** Enforce COI check on `ProposalController::print()`; add PTj scoping to `SearchController` and remove fake PDPA/session stubs; move `limit(600)` AFTER PTj filter in `RecordsController::auditRows()`; remove `@mcms_disable_triggers` backdoor from MySQL audit triggers and expose audit payloads/IP to authorized auditors; block lecturer self-approval in `QualityReviewController::reviewOutcome()`.

### Priority 2 — Critical Governance, Role/RACI & Core Rule Enforcement (Architectural Foundation)
4. **Refactor RBAC to Match Guide Appendix H2–H3 (`Role.php`, routes, `UserAdminController`, `SettingsController`):**
   - Split `deputy_dean` into distinct `P2` (Faculty/PTj), `P3` (Academic Authority / Senate / Transfer Committee), and add missing `P4` (Quality Assurance).
   - Wire or remove dead `Role::Reviewer`.
   - Grant `P6` (`records_depositor` / Registry) ownership of `/credentials/*` and programme admission recording; move ABC redemption/release updates exclusively to `P7`; prevent F11 Row 3 overwrite (`CreditBankController:191`) by separating P6 ACR confirmation from P7 ABC deposit.
   - Lock statutory academic thresholds (`40h`, `3y`, `10y`, `30%`, `1 sem`) and `ReferenceData` accredited programmes/parent courses from unilateral `P5` (`codl_admin`) edits; fix `UserAdminController` SoD so `codl_admin` cannot self-assign academic/registry roles; align `approval.route.*` settings with route middleware so changing them never deadlocks.
5. **Enforce QV, Material-Change & Eligibility State Machines:**
   - Remove `qv_status = 'verified'` from `ProposalController::credential()` writable inputs; clear `qv_status` / `qv_valid_until` in `VersionController::copy()` on material changes and in `ApprovalController::senate()`; stop `OfferingStatus::releaseForChange()` from reopening cohorts on superseded versions; re-run `ProposalChecker` at Senate approval and offering publication.
   - Fix `QvAlerts::end()` to truncate `valid_until` to effective end date and notify affected learners; restrict `Acr::prepare():46` to `qv_records` valid on `completed_on` AND not suspended/withdrawn before completion (and add Formal vs Non-Formal learning classification across DB/ACR/CSV/print).
   - Fix `QvController::result()` 3-year date check (exact leap-year bound, reject past `valid_until` & `0` credit, bind only to current version) and fix `Qv::completedCohorts()` to verify assessment evidence on current version.
6. **Enforce Assessment, Completion, Credit Transfer & Stackable Degree Gates:**
   - Check open `integrity_cases` inside `Verifier::verify()` and block overwriting held results; revoke `module_achievements`/badges and re-check scores in `Completion::check()` when `decideChange()` lowers a verified mark (and notify learner); fix `separate_assessment` deadlock in `Completion.php:55-58`; decouple `module_achievements` from single `enrolment_id` for cross-cohort modular UMCs; fix `student/results.blade.php:136-138` false `Passed` tag (and restore `CompletionTest:146`).
   - Convert `Transfers::warnings()` (30% UTeM floor, programme transfer cap, `non_transferable`, target course credit cap, duplicate target course) into **server-side hard blocks** in `EvaluationController::decide()`; align `config/transfer.php` F7 scales exactly with Guide Appendix F7 and block approval on failing criteria or blank `na`; fix partial transfer approval (`Diluluskan sebahagian`) to preserve unapproved deposit balance; fix expired-credit re-evaluation deadlock in `whyNot():54` when approved with `reevaluate=true`; fix F9 Provisional date rendering (`20/10` vs `25/10`).
   - Fix `Plans::checks():42-43` to require ALL mandatory remaining items to be `done` before `planAward()`, check live `Qv::standing()`, BoK, full PLO coverage, and graduation criteria, allow editing `remaining` statuses after creation, and call `CreditLedger::redeem()` upon award.
   - Implement true transactional record synchronization for F10 corrections, fix `record_ref` per `record_type`, remove `discrepancies.key` `UNIQUE` blindness, restore F5 non-automatic disclaimer on `creditbank/ledger.blade.php` (fixes `CreditBankTest:188`), and fix `ledger()` sum/double-counting.

### Priority 3 — High-Severity Schema, Appendix Forms & Printables
7. **Complete Missing Schema & Official Appendix Sections:**
   - Add structured `learning_content`, `learning_activities`, and SLT-per-activity breakdown (Cl. 10.1, A5); add `category_justification` (A3); require UMC `parent_clo_id` mapping.
   - Expand Appendix B (`offering_readiness`) from 6 collapsed rows to full B2–B7 items + B5 10-row record table + B8 3-role sign-off + Nota Penting B + A4 print.
   - Fix Appendix C (`Indicators.php` verified-only marks, non-null mean/median, exclude revoked credentials, non-green `null`, Indicator #6), add 3 missing C4 feedback sources (and include `not_completed` learners, remove pre-checked `4`), expand C5 to 8 domains + `TB`, add C6 title, selectable C7 owner, show C8 before/after evidence to closing dean, add C9 & C10 (3 roles) + Nota Penting C + A4 print; trigger UMC periodic reviews on parent course/programme edits (Cl. 11.5).
   - Expand Appendix D (`qv_checklist_items`) from 5 collapsed rows to all 42 D3–D7 items, expand D8 to 9 components, add D9 Required Actions, and complete D10 3-role sign-off + D2/D8/Nota D on QV package print.
   - Complete Appendix E: add PTj, SLT, LOs, and signatory position to Certificate (E3); add criteria, LOs, QR, non-credit disclaimer, and status/revocation lifecycle to Digital Badges (E2); persist 8-item E8 checklist and run on `replace()`; add E10 3-role sign-off & Nota Penting E.
   - Complete Appendix G: add G5 Body of Knowledge mapping table, G4 Evidence & Gaps, G8 missing columns (`Lain-lain`, `TB`, Fulfillment Method, Credits, Remarks), G9 approved/remaining credits & gaps, and G11 3-role sign-off + Nota Penting G.
   - Complete Appendix H: build H4 12-class Record Ownership Matrix, expand H5 cohort records checklist to all 10 items with Location/Owner, and build H8 Governance Action Register + H9 confirmation + Nota Penting H.
   - Replace fake registration checks in `student/register.blade.php` with real server-side `Enrolments::reasons()` checks including `eligible_groups`.

### Priority 4 — Bilingual Parity, Demo Seeders & Documentation Hygiene
8. **Bilingual EN/BM Remediation:** Add all 655 missing keys to `app/lang/ms.json`; wrap hardcoded English strings in `Plans.php`, `Trace.php`, `Reports.php`, `Role::label()`, and print templates; render `users.position_ms` and `learning_outcomes.text_ms` on BM prints; fix `"Lulus"` mistranslation on F9; add Bahasa Melayu prohibited-claim patterns to `ForbiddenClaims.php` and scan proposals, pathways, and all offering fields; persist `users.locale` from header toggle.
9. **Clean Demo Seeders & Retract Overclaiming Docs:** Fix `ComprehensiveTestingSeeder`, `DemoScenarioSeeder`, `DemoQualitySeeder`, `DemoTransferSeeder`, and `DemoPathwaySeeder` so seeded records pass every Guide rule (no 40h/1.5cr MCE, no UMC SLT mismatches, no UMC QV records or >3y QV, no 5y ABC expiry, no P5 Farid Hashim on ACR/corrections/disposals/suspensions, no 0/2 OOP ACR, valid F6 keys & F7 scales, no fabricated `>= 80%`). Archive/retract `MCMS_Appendix_F_Compliance_Audit_v2.2.pdf`, `MCMS_Guide_Compliance_and_Verification_Audit_v2.2.pdf`, and root `MCMS_Appendix_F_Compliance_Audit.pdf`, and update `README.md`/`GEMINI.md`/`HANDOVER.md` only after all 4 failing Pest tests and above remediation items pass independent re-audit.
