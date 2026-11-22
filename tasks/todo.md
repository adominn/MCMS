# Compliance Remediation Task Checklist — UTeM MCMS

Source Plan: [tasks/plan.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/tasks/plan.md)  
Audit Baseline: [AUDIT_REPORT.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/docs/compliance-audit/AUDIT_REPORT.md) (`9.9%` strict / `36.7%` weighted; 4 Pest failures)

---

## Phase 1: Emergency Security, Verification & Official Printout Fabrications

### Task 1: Fix systemic `rtrim` trailing-zero formatting bug across Blade views
**Description:** Replace all 39 unsafe `rtrim(rtrim((string) $v, '0'), '.')` calls across 18 Blade views with a shared numeric formatter (`number_format((float) $v, 2, '.', '')` before trimming) so integer sums ending in `0` (`10`, `100`) never render as `1`.
**Requirements closed:** `REQ-AA-006`, `REQ-AG-001`, `REQ-AG-003`, `REQ-C14-010`, `REQ-C14-012` (arithmetic portions)
**Acceptance criteria:**
- [x] Shared helper (e.g., `mcms_num($v)`) formats `100` -> `100`, `10` -> `10`, `2.50` -> `2.5`, `0` -> `0`.
- [x] Zero occurrences of `rtrim(rtrim((string)` remain under `app/resources/views/`.
- [x] `/proposals/1/print` prints `Total: 100%`, `/public/pathways/1/print` prints `Total Potential Transferable Credits: 10`, and `/public/pathways` prints `10 Max articulable credits`.
**Verification:**
- [x] Unit/Feature test: `cd app; php vendor/bin/pest --filter="DocumentsTest|PathwayTest|CreditBankTest"` (PASS)
- [x] Grep check: zero matches for `rtrim(rtrim((string)` in `app/resources/views/` (0 remaining)
- [x] Visual check: inspect rendered `/proposals/1/print` and `/public/pathways/1/print`
**Dependencies:** None
**Files touched:**
- `app/app/Support/helpers.php` (created `mcms_num()` helper loaded in `AppServiceProvider.php`)
- `app/app/Providers/AppServiceProvider.php`
- 28 Blade views across `curriculum`, `pathways`, `creditbank`, `monitoring`, `results`, `transfer`, `student`, and `records`
**Status:** COMPLETED

### Task 2: Patch Public Verification security, revoked downloads & credit/category misrepresentation
**Description:** Close the HTTP 200 revoked-credential download leak, fix the unconditional green Ed25519 trust banner, remove fabricated `proposed_credit ?? 3` / SAMC / `"graduating credits"` fallbacks, stop retroactively expiring earned credentials, verify outer JSON against embedded JWT, and widen `verification_log.outcome`.
**Requirements closed:** `REQ-C01-003`, `REQ-C06-018`, `REQ-C08-011`, `REQ-C08-012`, `REQ-C10-008`, `REQ-C10-009`, `REQ-AE-002` (badge banner), `REQ-AE-005`, `REQ-AE-006`
**Acceptance criteria:**
- [x] `GET /public/verify/{code}/download/json` and `/download/jwt` return HTTP 403/410 for revoked credentials and respect `verification.public_fields`.
- [x] `verify-result.blade.php` displays cryptographic verification badge ONLY for active credentials with verified Ed25519 signature (never for revoked, invalid_signature, or unsigned badges).
- [x] `VerifyController` never falls back to `proposed_credit ?? 3`, never labels MCE as SAMC, never uses `"graduating credits"`, and never retroactively marks earned credentials `expired` after `qv_valid_until`.
- [x] `VerifyController::file()` verifies outer VC JSON claims match decoded `proof_jwt` claims; `verification_log.outcome` stores `invalid_signature` (`VARCHAR(32)`).
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter=CredentialTest` (26 passed, 515 assertions)
- [x] Live check: `GET /public/verify/SEC03-2P6RW/download/json` and `/download/jwt` return 403/410; revoked page shows no green cryptographic banner
**Dependencies:** None
**Files touched:**
- `app/app/Modules/Credentials/VerifyController.php`
- `app/resources/views/public/verify-result.blade.php`
- `app/database/migrations/2027_02_10_000001_widen_verification_log_outcome.php`
**Status:** COMPLETED

### Task 3: Remove official printout fabrications on Suspension Notice, Credential Sheet & Pathways Print
**Description:** Eliminate fabricated titles, transition plans, fake `DataIntegrityProof`, and wrong credit-validity/30% rules on official public and learner printouts.
**Requirements closed:** `REQ-C17-008`, `REQ-AE-004`, `REQ-C12-013` (print wording), `REQ-C14-006` (card wording), `REQ-C14-012`
**Acceptance criteria:**
- [x] `QualityReviewController::printSuspension()` looks up strictly by `suspensions.id` (never colliding with `micro_credentials.id`), prints real decider's `users.position`/`position_ms` (never hardcoded `Deputy Dean`), renders only persisted `transition_plan` (no fabricated fallback), and restores BM wording (`DocumentsTest:157` passes).
- [x] `CredentialController::sheet()` removes fabricated `sha256` `DataIntegrityProof`, fake `did:web:127.0.0.1`, and non-existent `/status/1`; renders authentic Ed25519 JWT proof metadata and configured `verification.public_url`.
- [x] `public/pathway-print.blade.php` states credit validity is `"within 10 years of ABC deposit date"` (Cl. 12.8), and `public/pathways.blade.php` fixes the contradictory 30% transfer-threshold card.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="DocumentsTest|CredentialTest|PathwayTest"` (100% PASS)
- [x] Visual check: re-capture `/suspensions/1/print`, `/my-credentials/1/sheet`, `/public/pathways/1/print`
**Dependencies:** Task 1
**Files touched:**
- `app/app/Modules/Monitoring/QualityReviewController.php`
- `app/resources/views/monitoring/suspension-notice.blade.php`
- `app/app/Modules/Credentials/CredentialController.php`
- `app/resources/views/public/pathway-print.blade.php`
- `app/resources/views/public/pathways.blade.php`
- `app/resources/views/curriculum/print.blade.php` (restored official Appendix A title)
**Status:** COMPLETED

### Task 4: Close COI bypass, cross-PTj search leak, audit `limit(600)` ordering & MySQL trigger backdoor
**Description:** Enforce COI check on proposal print, enforce PTj scoping and remove fake stubs in `SearchController`, fix `RecordsController::auditRows()` filtering order, and harden `audit_events` immutability triggers.
**Requirements closed:** `REQ-C09-004` (COI print), `REQ-C15-005`, `REQ-C16-004` (COI print), `REQ-C18-009`, `REQ-AH-005`
**Acceptance criteria:**
- [x] `GET /proposals/{id}/print` enforces identical COI gate as `/reviews/{id}` (returns 403 for undeclared or conflicted reviewers).
- [x] `SearchController` scopes all results strictly to the caller's PTj/role and removes fake PDPA correction `DC-2026-0118` and fake session termination stubs.
- [x] `RecordsController::auditRows()` applies PTj filter BEFORE `limit()` and exposes `before`/`after` payload & IP to authorized audit viewers and CSV export.
- [x] Migration recreates `audit_events` triggers without `@mcms_disable_triggers` bypass.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="RbacSecurityTest|RecordsTest"` (100% PASS)
- [x] Live check: `U#6 GET /proposals/4/print` returns 403
**Dependencies:** None
**Files touched:**
- `app/app/Modules/Curriculum/ProposalController.php`
- `app/app/Modules/Identity/SearchController.php`
- `app/app/Modules/Records/RecordsController.php`
- `app/resources/views/records/audit.blade.php`
- `app/database/migrations/2027_02_10_000002_harden_audit_events_triggers.php`
**Status:** COMPLETED

### Task 5: Restore F5 Credit Ledger disclaimer, fix `ledger()` accounting & remove student-screen MCE/MQF leaks
**Description:** Restore the deleted F5 non-automatic acceptance disclaimer (`CreditBankTest:188`), fix `ledger()` expired-redeemed double-counting and deposit credit summation, and stop leaking `MQF Level 6` / `proposed_credit` / pending-ACR text on MCE and unverified SAMC student screens.
**Requirements closed:** `REQ-C12-010`, `REQ-C08-013`, `REQ-AF-005`, `REQ-C06-013` (student views), `REQ-C06-015` (student views)
**Acceptance criteria:**
- [x] `creditbank/ledger.blade.php` displays bilingual F5 non-automatic acceptance & storage-only disclaimer (`CreditBankTest.php:188` passes).
- [x] `CreditBankController::ledger()` sums `d.credits` (`deposited_credits`) and does not double-count expired redeemed deposits.
- [x] `student/result-detail.blade.php`, `student/results.blade.php`, `student/enrolments.blade.php`, and `records/_record.blade.php` never show fallback `MQF Level 6`, never display unverified `proposed_credit`, and never tell MCE learners an ACR will be generated.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="CreditBankTest|CompletionTest"` (100% PASS)
- [x] Visual check: inspect `/my-credit` and `/my-results/{id}`
**Dependencies:** Task 1
**Files touched:**
- `app/app/Modules/CreditBank/CreditBankController.php`
- `app/resources/views/creditbank/ledger.blade.php`
- `app/resources/views/student/result-detail.blade.php`
- `app/resources/views/student/results.blade.php`
- `app/resources/views/student/enrolments.blade.php`
- `app/resources/views/records/_record.blade.php`
- `app/resources/views/completion/show.blade.php` (added `(badges only)`)
**Status:** COMPLETED

### Checkpoint 1: After Tasks 1–5
- [x] `DocumentsTest`, `CredentialTest`, `CreditBankTest`, `CompletionTest`, `PathwayTest`, `RbacSecurityTest`, `RecordsTest` pass 100%
- [x] Zero HTTP 200 downloads on revoked credentials; zero COI bypass on `/proposals/4/print`; zero `Total: 1` arithmetic bugs
- [x] All 4 baseline test failures resolved; 0 regressions.
- [x] User confirmed: ABC is strictly university-level (UTeM institutional Credit Bank) and transfer credit is strictly intra-university for now.
**Status:** COMPLETED

---

## Phase 2: Role / RACI Separation & Governance Hardening (Appendix H)

### Task 6: Implement full 8-role `P1`–`P8` enum (`Role.php`), add `P3`/`P4`, separate `P6`/`P7` & resolve dead `Role::Reviewer`
**Description:** Align `Role.php` and `role_assignments` to Guide Appendix H2 (P1–P8) by introducing distinct `academic_authority` (`P3`), `qa_officer` (`P4`), `records_custodian` (`P6`), and `abc_depositor` (`P7`), removing or properly wiring dead `Role::Reviewer`, and updating role labels, MFA rules, and navigation sidebars.
**Requirements closed:** `REQ-AH-001`
**Acceptance criteria:**
- [x] All 8 Guide roles P1–P8 exist as active, usable roles with MFA enabled on privileged roles (P2, P3, P4, P5, P6, P7).
- [x] No dead role enum remains; stub persona switcher and `tests/Helpers.php` support all 8 roles.
- [x] `Role::label()` supports localized EN/BM labels.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="RbacSecurityTest|FoundationTest"` (PASS)
- [x] Manual check: sign in as each of the 8 personas and confirm dedicated sidebar loads without 403/empty menu
**Dependencies:** Checkpoint 1, Open Question #1
**Files touched:**
- `app/app/Modules/Identity/Role.php`
- `app/database/migrations/2027_02_10_000003_expand_roles_for_appendix_h.php`
- `app/app/Models/User.php`
- `app/resources/views/layouts/app.blade.php`
- `app/resources/views/signin.blade.php`
- `app/tests/Helpers.php`
**Status:** COMPLETED

### Task 7: Re-align route middleware & controller SoD across all 18 Appendix H3 RACI activities
**Description:** Re-map route middleware and controller authorization to match Guide Appendix H3: move `/credentials/*` and `programme_admissions` to `P6`, move ABC redemption/release to `P7`, separate `P2` endorsement from `P3` Senate/Transfer/Stackable decisions, add `P4` QA review/QV/CQI confirmation, block lecturer self-approval on periodic reviews, and prevent `approval.route.*` deadlocks.
**Requirements closed:** `REQ-AH-002`, `REQ-C10-010`, `REQ-AE-007` (role ownership), `REQ-C15-003`, `REQ-C16-002`, `REQ-C11-012` (SoD), `REQ-C14-004` (admission role)
**Acceptance criteria:**
- [x] `P6` (`records_custodian`) accesses `/credentials/*` and records `programme_admissions`; `P2`/`P3` cannot mutate credentials directly.
- [x] `P7` (`abc_depositor`) executes `/deposits/*` and ABC redemption/release updates.
- [x] `P2` endorses proposals; `P3` records Senate approvals, credit transfer decisions, and stackable pathway approvals/awards; same user cannot perform both P2 and P3 on the same record.
- [x] `QualityReviewController::reviewOutcome()` rejects self-approval by the MC owner/lecturer (`403`).
- [x] Route middleware resolves configurable `approval.route.*` cleanly without deadlocking.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="RbacSecurityTest|ApprovalTest|CredentialTest"` (100% PASS)
- [x] Live check: P6 opens `/credentials` (200); P2 gets 403 on `/credentials/{id}/revoke`
**Dependencies:** Task 6
**Files touched:**
- `app/routes/credentials.php`, `app/routes/transfer.php`, `app/routes/creditbank.php`, `app/routes/records.php`
- `app/app/Modules/Credentials/CredentialController.php`
- `app/app/Modules/Monitoring/QualityReviewController.php`
- `app/app/Modules/Transfer/EvaluationController.php`
- `app/tests/Feature/CredentialTest.php`
**Status:** COMPLETED

### Task 8: Lock statutory academic thresholds & prevent `P5` (`codl_admin`) academic usurpation
**Description:** Restrict `SettingsController`, `ReferenceDataController`, `UserAdminController`, and `RetentionController` so `P5` (`codl_admin`) supports operations without altering statutory academic rules, accredited curriculum reference data, retention holds, or self-assigning academic/registry roles.
**Requirements closed:** `REQ-C15-002`, `REQ-C06-007` (threshold lock), `REQ-C06-009` (threshold lock), `REQ-C06-012` (threshold lock), `REQ-C11-003` (threshold lock), `REQ-C12-013` (threshold lock), `REQ-C14-008` (threshold lock), `REQ-C18-010` (hold lock)
**Acceptance criteria:**
- [x] Statutory settings (`slt.min_hours_*`, `credit.slt_hours_per_credit`, `qv.max_validity_years`, `credit.abc_validity_years`, `stackable.min_home_credit_percent`, `stackable.residency_semesters`, `approval.route.*`) are read-only for `codl_admin` and editable only by `P3` (`academic_authority`) with lower-bound validation.
- [x] `ReferenceDataController` (programmes, accreditation status, parent courses, CLOs) requires `P3`/`P2` authorization, not `codl_admin`.
- [x] `UserAdminController` SoD blocks `codl_admin` from assigning `ptj_authority`, `academic_authority`, `qa_officer`, `records_custodian`, or `abc_depositor` to themselves.
- [x] `RetentionController` blocks `codl_admin` from lifting retention holds or approving disposals.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="RbacSecurityTest|FoundationTest|RecordsTest"` (100% PASS)
**Dependencies:** Task 6, Task 7
**Files touched:**
- `app/app/Modules/Core/Settings.php`
- `app/app/Modules/Identity/UserAdminController.php`
- `app/app/Modules/Records/RetentionController.php`
**Status:** COMPLETED

### Task 9: Build Appendix H4 (Record Ownership Matrix), complete H5 (10 cohort artefacts), and build H8–H9 + Nota Penting H
**Description:** Add the 12-class Appendix H4 Record Ownership Matrix (with Owner, Creator, Retention, Access), expand H5 `/records/cohort/{id}` to all 10 Guide artefacts with Location/Owner columns, and implement H8 Governance Action Register + H9 Governance Matrix Confirmation + Nota Penting H.
**Requirements closed:** `REQ-AH-003`, `REQ-AH-004`, `REQ-AH-007`, `REQ-C18-001`, `REQ-C18-010` (H4 schedules)
**Acceptance criteria:**
- [x] `/records/retention` displays all 12 Appendix H4 record classes with owner, creator, retention period/method, and access level.
- [x] `/records/cohort/{id}` displays all 10 H5 cohort artefacts (including #9 ACR/ABC for eligible learners and #10 changes/corrections/incidents) with Location and Owner/Custodian columns.
- [x] H8 Governance Action Register (Item, Responsible Party, Target Date, Status `Belum`/`Proses`/`Selesai`, Evidence) and H9 Confirmation + Nota Penting H are persisted and accessible with print view.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter=RecordsTest` (14 passed, 345 assertions)
- [x] Visual check: inspect `/records/cohort/1`, `/records/retention`, and Governance Register
**Dependencies:** Task 6, Open Question #5
**Files touched:**
- `app/database/migrations/2027_02_10_000004_create_appendix_h_governance_tables.php`
- `app/app/Modules/Records/Retention.php`
- `app/app/Modules/Records/RetentionController.php`
- `app/app/Modules/Records/Trace.php`
- `app/resources/views/records/cohort.blade.php`
- `app/resources/views/records/retention.blade.php`
**Status:** COMPLETED

### Checkpoint 2: After Tasks 6–9
- [x] `RbacSecurityTest`, `FoundationTest`, `RecordsTest`, `ApprovalTest`, `CredentialTest` pass 100%
- [x] All 8 Guide roles P1–P8 operate cleanly end-to-end; zero `P5` academic usurpation vectors remain
**Status:** COMPLETED

---

## Phase 3: Curriculum, Approval, QV & Material-Change Enforcement

### Task 10: Harden `ProposalChecker`, block `qv_status='verified'` forgery & invalidate stale reviews
**Description:** Prevent lecturers from self-setting `qv_status='verified'`, forbid `parent_course_id` on SAMCs, enforce UMC `parent_clo_id` mapping, re-run `ProposalChecker` at Senate approval and offering creation, and invalidate prior submitted reviews when a proposal is returned for amendment.
**Requirements closed:** `REQ-C03-001`, `REQ-C06-004`, `REQ-C06-006`, `REQ-C06-011`, `REQ-C06-013` (draft forgery), `REQ-C06-014`, `REQ-C06-015` (draft forgery), `REQ-C06-017`, `REQ-C08-002`, `REQ-C09-004` (re-review), `REQ-C09-005`, `REQ-AA-007`
**Acceptance criteria:**
- [x] `ProposalController::credential()` strips `verified` from writable `qv_status` choices (set only by QVC outcome) and clears `proposed_credit`/`proposed_mqf_level` when category is `mce`.
- [x] `ProposalChecker` blocks `parent_course_id` on SAMC, blocks UMC LOs without `parent_clo_id`, and verifies credit-based UMC modules each have linked assessment/activities.
- [x] `ApprovalController::senate()` and `OfferingController::store()` re-run `ProposalChecker` and abort on blocking errors.
- [x] Returning a proposal for amendment marks prior `proposal_reviews` as superseded so re-endorsement requires fresh review.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="CurriculumTest|ApprovalTest|OfferingTest"` (83 passed, 806 assertions, 100% green)
**Dependencies:** Task 7
**Files touched:**
- `app/app/Modules/Curriculum/ProposalController.php`
- `app/app/Modules/Curriculum/ProposalChecker.php`
- `app/app/Modules/Approval/ApprovalController.php`
- `app/app/Modules/Offering/OfferingController.php`
- `app/app/Modules/Curriculum/VersionController.php`
- `app/resources/views/curriculum/tabs/credential.blade.php`
- `app/database/migrations/2027_02_10_000005_add_curriculum_design_fields.php`
- `app/tests/Feature/CurriculumTest.php`
- `app/tests/Helpers.php`
**Status:** COMPLETED

### Task 11: Add missing Curriculum design schema (A3 justification, A5 content & activities/SLT breakdown, A10 follow-up)
**Description:** Add structured fields for Category Justification (A3), existing equivalent offerings & recognition potential (Cl. 9.1), per-module Key Content and Learning Activities with SLT-per-activity/LO mapping (Cl. 8.1, 8.3, 10.1, A5), and A10 follow-up action selections.
**Requirements closed:** `REQ-C01-001`, `REQ-C08-001`, `REQ-C08-003`, `REQ-C09-001`, `REQ-C09-002`, `REQ-C10-001`, `REQ-AA-003`, `REQ-AA-005` (data), `REQ-AA-010` (data)
**Acceptance criteria:**
- [x] Migration adds `category_justification`, `existing_equivalents`, `recognition_potential`, `follow_up_action` on `mc_versions` and `mc_module_activities` (or structured content/activities + SLT breakdown linked to LOs).
- [x] Sum of module activity + assessment SLT aligns with module `slt_hours`.
- [x] Proposal workspace tabs persist and validate all new fields.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter=CurriculumTest` (30 passed, 178 assertions, 100% green)
**Dependencies:** Task 10
**Files touched:**
- `app/database/migrations/2027_02_10_000005_add_curriculum_design_fields.php`
- `app/config/proposals.php`
- `app/app/Modules/Curriculum/ProposalController.php`
- `app/app/Modules/Curriculum/ProposalChecker.php`
- `app/app/Modules/Curriculum/ProposalSummary.php`
- `app/resources/views/curriculum/tabs/justification.blade.php`
- `app/resources/views/curriculum/tabs/modules.blade.php`
- `app/tests/Feature/CurriculumTest.php`
**Status:** COMPLETED

### Task 12: Fix Version Control, Material-Change QV reset, cohort release & parent-course review triggers
**Description:** Stop `VersionController::copy()` from copying `qv_status='verified'`/`qv_valid_until` onto materially changed `v2`, clear QV on Senate approval of material changes, stop `OfferingStatus::releaseForChange()` from reopening superseded `v1` cohorts, and auto-trigger UMC periodic reviews when a parent course or programme is edited (Cl. 11.5).
**Requirements closed:** `REQ-C07-004`, `REQ-C09-006`, `REQ-C11-008`, `REQ-C11-012` (parent trigger), `REQ-C17-005`, `REQ-C17-006`, `REQ-C18-006`, `REQ-AH-006` (material change)
**Acceptance criteria:**
- [x] Creating a material `v2` resets `qv_status` to `not_applied`/`will_apply` and nulls `qv_valid_until` while flagging `qv_records.reverification = true`.
- [x] `OfferingStatus::releaseForChange()` never reopens cohorts attached to a superseded version; displays old-vs-new structural diff on version view.
- [x] Updating `parent_courses` or `programmes` in `ReferenceDataController` automatically opens `periodic_reviews` for all linked active UMCs and notifies P1/P2/P4.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest tests/Feature/VersionTest.php` (28 passed, 329 assertions, 100% green)
- [x] Offering regression pass: `cd app; php vendor/bin/pest tests/Feature/OfferingTest.php` (33 passed, 405 assertions, 100% green)
**Dependencies:** Task 8, Task 10
**Files touched:**
- `app/app/Modules/Curriculum/VersionController.php`
- `app/app/Modules/Approval/ApprovalController.php`
- `app/app/Modules/Offering/OfferingStatus.php`
- `app/app/Modules/Core/ReferenceDataController.php`
- `app/resources/views/curriculum/mcs/show.blade.php`
- `app/tests/Feature/VersionTest.php`
**Status:** COMPLETED

### Task 13: Fix SAMC Quality Verification (QV) bounds, cohort evidence check & suspension notifications
**Description:** Enforce exact 3-year QV validity (no leap-year overflow, reject past `valid_until` and `0` credit, remove `outcome` verified fallback, update only active version), require verified `assessment_results` in `Qv::completedCohorts()` on current version, and truncate `valid_until` + notify affected learners in `QvAlerts::end()`.
**Requirements closed:** `REQ-C11-002`, `REQ-C11-004`, `REQ-C11-005`, `REQ-C11-006`, `REQ-C11-007`, `REQ-C10-006` (QV notice), `REQ-AH-006` (QV trigger)
**Acceptance criteria:**
- [x] `QvController::result()` rejects `valid_until > valid_from + 3 years` using exact calendar boundary (e.g., `2024-02-29` max `2027-02-28`), rejects past `valid_until`, rejects `verified_credit <= 0`, rejects non-SAMC MCs, and updates only the verified `mc_version`.
- [x] `Qv::completedCohorts()` counts only completed cohorts on the current version with verified `assessment_results` and `achievement_records`.
- [x] `QvAlerts::end()` sets `valid_until = min(valid_until, today)` on suspension/withdrawal and dispatches notifications to all enrolled learners on active cohorts.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest tests/Feature/QualityTest.php` (18 passed, 374 assertions, 100% green)
**Dependencies:** Task 12
**Files touched:**
- `app/app/Modules/Quality/Qv.php`
- `app/app/Modules/Quality/QvController.php`
- `app/app/Modules/Quality/QvAlerts.php`
- `app/tests/Feature/QualityTest.php`
**Status:** COMPLETED

### Task 14: Upgrade `ForbiddenClaims` (BM + proposals/pathways) & fix Public Offering transparency
**Description:** Add Bahasa Melayu prohibited-claim patterns to `ForbiddenClaims.php`, invoke it on proposal saves, pathway descriptions, and all offering text fields, and fix `/public/offering/{id}` disclosures.
**Requirements closed:** `REQ-C03-003`, `REQ-C09-008`, `REQ-C10-004`, `REQ-C10-005`, `REQ-AE-005` (claims scanner)
**Acceptance criteria:**
- [x] `ForbiddenClaims` blocks prohibited EN and BM phrases (e.g., `"jaminan pemindahan kredit"`, `"kredit automatik"`, `"ijazah automatik"`) on proposals, offerings (all text fields), and pathways.
- [x] `/public/offering/{id}` clearly separates all **5 stages** (completion, credential award, ACR, ABC deposit, credit transfer approval) and states none guarantees automatic admission, transfer, or qualification.
- [x] `/public/offering/{id}` renders Badges/Certificates strictly per `mc_versions.credential_types` and labels `verification_method` accurately as `"Credential Verification Method"`.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="OfferingTest|CurriculumTest|PathwayTest"` (100% PASS)
**Dependencies:** Task 10
**Files touched:**
- `app/config/offerings.php`
- `app/app/Modules/Curriculum/ProposalController.php`
- `app/app/Modules/Pathways/PathwayController.php`
- `app/app/Modules/Offering/OfferingController.php`
- `app/database/seeders/DatabaseSeeder.php`
- `app/resources/views/public/offering.blade.php`
- `app/tests/Feature/OfferingTest.php`
**Status:** COMPLETED

### Checkpoint 3: After Tasks 10–14
- [x] `CurriculumTest`, `ApprovalTest`, `VersionTest`, `QualityTest`, `OfferingTest` pass 100%
- [x] Material change clears QV; suspended QV truncates `valid_until` and notifies learners
**Status:** COMPLETED

---

## Phase 4: Enrolment, Assessment, Completion & Modular UMC

### Task 15: Replace fake pre-registration checks & enforce `eligible_groups` on enrolment
**Description:** Remove the 4 hardcoded `"Passed"` checks and `<input type="hidden" name="prerequisites_ok" value="1">` from `student/register.blade.php`, evaluate `eligible_groups` server-side in `Enrolments::reasons()`, and stop displaying unverified `proposed_credit`/`proposed_mqf_level` during registration.
**Requirements closed:** `REQ-C10-003` (registration), `REQ-C16-006`, `REQ-C06-013` (register view)
**Acceptance criteria:**
- [x] `Enrolments::reasons()` evaluates `offerings.eligible_groups` against learner profile/type and returns dynamic check statuses for the UI table.
- [x] `student/register.blade.php` renders live server-evaluated checks and never submits hardcoded `prerequisites_ok=1` or unverified credit/MQF values.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter=EnrolmentTest` (28 passed, 500 assertions)
- [x] Visual check: inspect `/register/{id}` on both eligible and ineligible offerings
**Dependencies:** Task 14
**Files touched:**
- `app/app/Modules/Enrolment/Enrolments.php`
- `app/app/Modules/Enrolment/EnrolmentController.php`
- `app/resources/views/student/register.blade.php`
- `app/tests/Feature/EnrolmentTest.php`
- `app/app/Modules/Approval/ApprovalController.php` (fixed version rebind order)
**Status:** COMPLETED

### Task 16: Fix Assessment Integrity bypass, verified score-change revocation & false `Passed` display
**Description:** Enforce `integrity_cases` holds inside `Verifier::verify()` and `ResultsController::save()`, revoke `module_achievements`/`digital_badges` and notify the learner when `decideChange()` lowers a verified mark below `pass_mark`, allow partial-cohort marking, and fix `student/results.blade.php` showing green `Passed` for failed learners.
**Requirements closed:** `REQ-C06-001`, `REQ-C10-006` (result change notice), `REQ-C10-007`, `REQ-AH-006` (integrity trigger)
**Acceptance criteria:**
- [x] `Verifier::verify()` skips/blocks any enrolment with an open `integrity_cases` record (never verifies marks or mints badges while held); saving marks cannot overwrite a `held` status to `provisional`.
- [x] `ResultsController::decideChange()` re-evaluates module pass status; if lowered below `pass_mark`, revokes/invalidates `module_achievements` & badge status and notifies the learner.
- [x] `ResultsController::save()` allows saving marks for individual learners/modules without failing on blank cells.
- [x] `student/results.blade.php` renders `Passed` only when all required components meet `pass_mark`, otherwise `Not passed` / `In progress`.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter=ResultsTest` (29 passed, 546 assertions)
**Dependencies:** Task 7
**Files touched:**
- `app/app/Modules/Assessment/Verifier.php`
- `app/app/Modules/Assessment/ResultsController.php`
- `app/resources/views/student/results.blade.php`
- `app/app/Modules/Notifications/Notifier.php`
- `app/database/seeders/DatabaseSeeder.php`
- `app/tests/Feature/ResultsTest.php`
**Status:** COMPLETED

### Task 17: Fix `separate_assessment` UMC deadlock, cross-cohort modular UMC aggregation & completion window expiry
**Description:** Fix `Completion::check():55-58` for `separate_assessment` UMCs (learning modules complete via verified LMS progress + passing the dedicated integrated assessment module), aggregate `module_achievements` across multiple cohorts by `(user_id, mc_version_id / mc_module_id)` within the approved completion window (Cl. 7.4), enforce completion window expiry in `CompletionWindow`, re-verify current scores in `Completion::check()`, and fix `CompletionTest:146`.
**Requirements closed:** `REQ-C06-008`, `REQ-C07-002`, `REQ-C08-008`, `REQ-C08-010`, `REQ-C10-003` (cross-cohort UMC), `REQ-C12-004` (completion checks)
**Acceptance criteria:**
- [x] `separate_assessment` UMC learners pass `Completion::check()` when all learning modules are completed and the integrated assessment module is verified as passed.
- [x] Modular UMC learners completing Module 1 in Cohort A and Module 2 in Cohort B (within `umc.completion_window_months`) are recognized as complete across cohorts; expired windows block automatic aggregation.
- [x] `Completion::check()` re-verifies current verified `assessment_results` >= `pass_mark` and fixes blank reason string on `/cohorts/{id}/signoff` (`Missing Module 1 not achieved:`).
- [x] `CompletionTest.php:146` passes cleanly.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest tests/Feature/CompletionTest.php` (23 passed, 386 assertions)
**Dependencies:** Task 16, Open Question #5 & #6
**Files touched:**
- `app/app/Modules/Achievement/Completion.php`
- `app/app/Modules/Achievement/CompletionController.php`
- `app/app/Modules/Achievement/CompletionWindow.php`
- `app/resources/views/completion/show.blade.php`
- `app/tests/Feature/CompletionTest.php`
**Status:** COMPLETED

### Checkpoint 4: After Tasks 15–17
- [x] `EnrolmentTest`, `ResultsTest`, `CompletionTest` pass 100%
- [x] Open integrity case blocks verification; lowered score invalidates completion; `separate_assessment` and cross-cohort modular UMC complete cleanly
**Status:** COMPLETED

---

## Phase 5: Credit Bank, Transfer & Stackable Degree Enforcement (Ch. 12–14, App. F–G)

### Task 18: Add Formal/Non-Formal classification, harden `Acr::prepare()` & fix F11 Row 3 / F9 date bugs
**Description:** Add `learning_type` (`formal` for UMC, `non_formal` for QV-SAMC) across DB, ACR UI, ABC CSV export, and A4 print; block suspended/withdrawn QV-SAMCs and incomplete-module UMCs in `Acr::prepare()` and `CreditBankController::record()`; fix `CreditBankController::record():191` overwriting F11 Row 3; preserve all ACR verifiers on multi-ACR transfer letters; fix F9 Stage 02 Provisional date displaying `25/10` instead of `20/10` and `en vv1.0` typo.
**Requirements closed:** `REQ-C04-003`, `REQ-C12-003`, `REQ-C12-004`, `REQ-C12-005`, `REQ-C12-006`, `REQ-C12-009`, `REQ-AF-001`, `REQ-AF-002`, `REQ-AF-003`, `REQ-AF-004`, `REQ-AF-009` (date bug), `REQ-AF-011`
**Acceptance criteria:**
- [x] `acrs.learning_type` stores `formal` (UMC) or `non_formal` (QV-SAMC) and appears on web ACR, A4 print, and ABC CSV export.
- [x] `Acr::prepare()` and `CreditBankController::record()` reject suspended/withdrawn `qv_records` and require `module_achievements` count == `mc_modules` count.
- [x] F11 verification distinguishes P6 ACR confirmation from P7 ABC deposit without overwriting Row 3; multi-ACR transfer letters show verifiers for all referenced ACRs.
- [x] F9 Stage 02 Provisional date pulls strictly from `redemption_events`/`credit_ledger_entries` (`20/10/2026`); `vv1.0` typo fixed to `v1.0`.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="CreditBankTest|AppendixFComplianceTest"` (100% PASS)
**Dependencies:** Task 7, Task 13, Task 17
**Files touched:**
- `app/database/migrations/2027_02_10_000006_add_learning_type_and_f11_slots.php`
- `app/app/Modules/CreditBank/Acr.php`
- `app/app/Modules/CreditBank/CreditBankController.php`
- `app/app/Modules/Transfer/EvaluationController.php`
- `app/app/Modules/Transfer/TransferController.php`
- `app/routes/transfer.php`
- `app/resources/views/creditbank/acr.blade.php`
- `app/resources/views/creditbank/acr-print.blade.php`
- `app/resources/views/transfer/show.blade.php`
- `app/lang/ms.json`
**Status:** COMPLETED

### Task 19: Enforce Credit Transfer caps, `non_transferable` blocks & exact Appendix F7 scales server-side
**Description:** Convert `Transfers::warnings()` (30% UTeM home floor, programme `transfer_limit_percent`, `parent_courses.non_transferable`, target `parent_courses.credit` ceiling, duplicate target course) into server-side hard blocks in `EvaluationController::decide()`, align `config/transfer.php` F7 allowed values exactly to Guide Appendix F7, and reject approval when mandatory F7 criteria fail or are left `na`/blank.
**Requirements closed:** `REQ-C13-003`, `REQ-C13-004`, `REQ-C13-005`, `REQ-C13-007`, `REQ-C14-006` (transfer enforcement), `REQ-AF-006`, `REQ-AF-007`, `REQ-C18-005`
**Acceptance criteria:**
- [x] `EvaluationController::decide()` returns HTTP 422 and refuses approval if any line violates the 30% UTeM floor, programme transfer cap, `non_transferable` flag, target course credit limit, or duplicates a target course.
- [x] `config/transfer.php` matches Guide Appendix F7 exactly (`mqf` includes `na`/`TB`; `credit_slt`, `activities`, `assessment` remove unauthorized `partly`).
- [x] `evaluate()` rejects `na` on criteria where Guide F7 does not allow `TB` and requires non-empty findings; `decide()` blocks approval if `accreditation='invalid'`, `mqf='not_matched'`, `currency='needs_review'`, `professional='not_met'`, or `credit_slt='inadequate'`.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="TransferTest|AppendixFComplianceTest"` (100% PASS, 28 tests, 713 assertions)
**Dependencies:** Task 7, Open Question #3
**Files touched:**
- `app/config/transfer.php`
- `app/app/Modules/Transfer/Transfers.php`
- `app/app/Modules/Transfer/EvaluationController.php`
- `app/routes/transfer.php`
- `app/tests/Feature/TransferTest.php`
**Status:** COMPLETED

### Task 20: Fix partial credit transfer approval, expired-credit re-evaluation & F10 transactional record synchronization
**Description:** Fix partial approval (`Diluluskan sebahagian`) so unapproved credits are preserved (per Open Question #2), unblock approved expired-credit re-evaluation (`reevaluate=true`) in `CreditLedger::provisional()`, implement transactional record synchronization in F10 `CreditBankController::correct()` / `Discrepancies::resolve()` with accurate `record_ref`, and remove `discrepancies.key` `UNIQUE` blindness.
**Requirements closed:** `REQ-C12-015`, `REQ-C12-017`, `REQ-C12-019`, `REQ-C13-008`, `REQ-C18-008`, `REQ-AF-008`, `REQ-AF-009`, `REQ-AF-010`, `REQ-AH-006` (correction trigger)
**Acceptance criteria:**
- [x] Partial transfer approval locks/redeems only approved credits (tracking `credits_provisional`/`credits_redeemed` or split ledger balance) without destroying unapproved credits.
- [x] Expired credit with verified `reevaluate=true` and `currency='current'` transitions cleanly through `CreditLedger::provisional()` on approval.
- [x] F10 `correct()` stores the exact target reference (`abc_reference` for `AbcDeposit`, `credential.number` for `Credential`, `acr.reference` for `ACR`), updates the authorized fields while preserving original snapshot, and notifies the learner.
- [x] `discrepancies` allows re-opening or inserting new occurrences for recurring mismatches (replacing global `UNIQUE(key)` with open-status uniqueness).
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="CreditBankTest|TransferTest|RecordsTest|AppendixFComplianceTest"` (100% PASS, 59 tests, 1,442 assertions)
**Dependencies:** Task 18, Task 19, Open Question #2
**Files touched:**
- `app/database/migrations/2027_02_10_000007_fix_partial_redemption_and_discrepancy_key.php`
- `app/app/Modules/CreditBank/CreditLedger.php`
- `app/app/Modules/CreditBank/CreditBankController.php`
- `app/app/Modules/Records/Discrepancies.php`
- `app/app/Modules/Transfer/EvaluationController.php`
- `app/tests/Feature/TransferTest.php`
- `app/tests/Feature/RecordsTest.php`
**Status:** COMPLETED

### Task 21: Fix Stackable Degree award gating, `remaining` editing, live QV standing & automatic redemption on award
**Description:** Fix `Plans::checks():42-43` so `remaining.met` is `true` ONLY when every mandatory remaining item has `status === 'done'`, check live `Qv::standing()`, full PLO coverage, BoK coverage, and actual completed residency semesters before `planAward()`, allow updating `remaining` statuses after plan creation, and invoke `CreditLedger::redeem()` upon degree conferment.
**Requirements closed:** `REQ-C14-001`, `REQ-C14-002`, `REQ-C14-003`, `REQ-C14-005`, `REQ-C14-008`, `REQ-C14-009`, `REQ-C14-013`, `REQ-AG-006`, `REQ-AG-007`, `REQ-AG-008`, `REQ-AG-010`
**Acceptance criteria:**
- [x] `Plans::checks()` marks `remaining.met = false` if any mandatory course is `todo`/`planned`, verifies `Qv::standing()` on every SAMC item, verifies `utem_percent` from real transferred vs total programme credits, and flags conventional-to-ODL mode shifts for ODL QA referral.
- [x] `PathwayController::planAward()` rejects awarding a degree if any check is unmet or admission is missing, and atomically redeems provisional ABC credits linked to the programme via `P7`/redemption service.
- [x] Staff can update `remaining` item statuses (`todo`/`planned`/`done`/`na`) on `/plans/{id}` without `<input type="hidden">` lock.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest tests/Feature/PathwayTest.php` (14 passed, 264 assertions, 100% green)
**Dependencies:** Task 7, Task 20
**Files touched:**
- `app/config/pathways.php`
- `app/app/Modules/Pathways/Plans.php`
- `app/app/Modules/Pathways/PathwayController.php`
- `app/resources/views/pathways/plan.blade.php`
- `app/tests/Feature/PathwayTest.php`
**Status:** COMPLETED

### Task 22 (Decision-Gated): External-institution MC ACR ingestion & curriculum-change equivalence re-evaluation
**Description:** If approved under Open Question #4, add external-institution ACR ingestion for transfer applications (`Cl. 12.7`, `13.2`) and add the 3-outcome curriculum-change equivalence re-evaluation workflow (`still equivalent / supplementary learning / no longer equivalent`) preserving original credentials/ACRs (`Cl. 13.8`, `14.8`).
**Requirements closed:** `REQ-C12-012` (Optional), `REQ-C13-002` (external UMC), `REQ-C13-011`, `REQ-C14-011`
**Acceptance criteria:**
- [x] User decision confirmed: Intra-university ABC credit bank only (no national ABC / external university transfer). External-institution ACR ingestion omitted per user decision.
- [x] Curriculum/MC version changes flag linked equivalence evaluations for re-evaluation with the 3 Guide 14.8 outcomes while keeping original ACR/credentials intact (`Transfers.php`, `EvaluationController.php`).
- [x] Transfer letter displays both `curriculum_version` and `mc_version` (`transfer/show.blade.php`).
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="TransferTest|PathwayTest"` (100% green)
**Dependencies:** Task 19, Open Question #4
**Files touched:**
- `app/app/Modules/Transfer/Transfers.php`
- `app/app/Modules/Transfer/EvaluationController.php`
- `app/resources/views/transfer/show.blade.php`
**Status:** COMPLETED

### Checkpoint 5: After Tasks 18–22
- [x] `CreditBankTest` (14 passed), `TransferTest` (22 passed), `AppendixFComplianceTest` (7 passed), `PathwayTest` (14 passed) pass 100% (1,315 assertions).
- [x] Live check: transferring above 30% floor or into `non_transferable` course returns 422; `planAward()` rejects plans with `todo` courses.
**Status:** COMPLETED

---

## Phase 6: Complete Official Appendices A, B, C, D, E, G Forms & Printables

### Task 23: Rebuild Appendix A Proposal Printout (Sections A1–A10 + Nota Penting A)
**Description:** Rebuild `curriculum/print.blade.php` and `ProposalController::print()` to render all 10 Guide Appendix A sections in order, using real `assessment_standard`, real 3-stage A9 review/endorsement/Senate sign-offs & dates (no `now()` or hardcoded titles), A3 justification, A4 parent programme/accreditation/unbundling model, A5 content/activities matrix without clipping, A8 7-item readiness table, A10 change control, and Nota Penting A.
**Requirements closed:** `REQ-AA-001`, `REQ-AA-002`, `REQ-AA-003` (print), `REQ-AA-004`, `REQ-AA-005` (print), `REQ-AA-006` (print), `REQ-AA-007` (print), `REQ-AA-008`, `REQ-AA-009`, `REQ-AA-010` (print), `REQ-AA-011`, `REQ-C08-005`
**Acceptance criteria:**
- [x] `DocumentsTest.php:130` passes cleanly.
- [x] All 10 sections (A1–A10) plus Nota Penting A render cleanly in A4 layout with zero clipped tables, zero hardcoded dates/titles/pass-marks, and EN/BM support.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="DocumentsTest|CurriculumTest"` (100% PASS, 37 passed, 245 assertions)
- [x] Visual check: inspected `/proposals/1/print` with full A1–A10 sections and Nota Penting A
**Dependencies:** Task 1, Task 4, Task 11
**Files touched:**
- `app/app/Modules/Curriculum/ProposalController.php`
- `app/resources/views/curriculum/print.blade.php`
- `app/resources/views/curriculum/mcs/show.blade.php`
- `app/tests/Feature/DocumentsTest.php`
**Status:** COMPLETED

### Task 24: Expand Appendix B Offering Readiness (B1–B8, 38 items + 10-row B5 + 3-role B8 + Nota B + Print)
**Description:** Replace the 6 collapsed offering readiness rows with all 38 Guide Appendix B2–B7 checklist items, the 10-row B5 record-readiness table, B1 SAMC QV status header, 3-role B8 sign-off (P1 Coordinator, P2 PTj, P5 Support/System), Nota Penting B, and an A4 print view.
**Requirements closed:** `REQ-C09-007`, `REQ-AB-001` through `REQ-AB-008`
**Acceptance criteria:**
- [x] All 38 B2–B7 items + 10 B5 rows are individually persisted with `Ya`/`Belum`/`TB` + Notes/Evidence in grouped Carbon sections.
- [x] B8 captures 3 distinct roles (`P1`, `P2`, `P5`) with Name/Position, Status (`Sedia`/`Bersyarat`/`Belum Sedia`), Date, Remarks, plus Nota Penting B.
- [x] `GET /offerings/{id}/readiness?print=1` renders full A4 Appendix B printout.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="OfferingTest|DocumentsTest"` (43 passed, 456 assertions; 7 passed, 67 assertions)
- [x] Visual check: inspect `/offerings/{id}/readiness` and `?print=1`
**Dependencies:** Task 7
**Files touched:**
- `app/config/offerings.php`
- `app/database/migrations/2027_02_10_000009_expand_appendix_b_readiness.php`
- `app/app/Modules/Offering/OfferingController.php`
- `app/routes/offerings.php`
- `app/resources/views/offerings/show.blade.php`
- `app/resources/views/offerings/readiness-print.blade.php`
- `app/tests/Feature/OfferingTest.php`
**Status:** COMPLETED

### Task 25: Fix Appendix C Monitoring & CQI (`Indicators.php`, C1–C10 + Nota Penting C + Print)
**Description:** Fix `Indicators.php` calculation bugs (verified marks only, exclude null scores from mean/median, exclude revoked credentials from issuance rate, never render `null` as green `Met`, add Indicator #6), add 3 missing C4 feedback sources + include `not_completed` students + remove pre-checked `4`, expand C5 to 8 domains + `TB`, add C6 title & selectable C7 owner, show C8 before/after evidence to closing authority, add C9 Offering Review Decision + C10 3-role confirmation (P1/P2/P4) + Nota Penting C + A4 print view.
**Requirements closed:** `REQ-C11-010`, `REQ-C11-011`, `REQ-C17-001`, `REQ-C17-002`, `REQ-C17-003`, `REQ-C17-004`, `REQ-C17-007`, `REQ-C17-009`, `REQ-C18-007`, `REQ-AC-001` through `REQ-AC-010`
**Acceptance criteria:**
- [x] `Indicators.php` uses only verified `assessment_results`, ignores nulls in mean/median, excludes revoked credentials, renders `null` as `Not recorded`, and includes Indicator #6 (access/technical issues).
- [x] C4 captures all 4 sources (Learners including `not_completed`, Instructors, Industry/SME, System/Operational); survey form has no pre-checked rating.
- [x] C5 has all 8 domains + `TB`; C6 has title + evidence; C7 allows selecting Action Owner; C8 displays before/after evidence on closure.
- [x] C9 (5 decisions, material change, 8 affected components, learner implications) and C10 (3-role sign-off P1/P2/P4 + Nota Penting C) persist and render on `/monitoring/cqi/{id}` and `?print=1`.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="MonitoringTest|DocumentsTest"` (17 passed, 356 assertions; 7 passed, 67 assertions)
- [x] Visual check: inspect `/cqi/{id}` and `?print=1`
**Dependencies:** Task 7
**Files touched:**
- `app/database/migrations/2027_02_10_000010_expand_appendix_c_cqi.php`
- `app/config/monitoring.php`
- `app/app/Modules/Monitoring/Indicators.php`
- `app/app/Modules/Achievement/Completion.php`
- `app/app/Modules/Monitoring/CqiController.php`
- `app/routes/monitoring.php`
- `app/resources/views/monitoring/cqi.blade.php`
- `app/resources/views/monitoring/cqi-print.blade.php`
- `app/resources/views/monitoring/feedback-form.blade.php`
- `app/tests/Feature/MonitoringTest.php`
**Status:** COMPLETED

### Task 26: Expand Appendix D SAMC QV Evidence Package (D1–D10, 42 items + 9 D8 + D9/D10 + Nota D)
**Description:** Expand `qv_checklist_items` from 5 collapsed rows to all 42 Guide Appendix D3–D7 evidence items (grouped by D3–D7), persist D2 eligibility items with file refs/remarks, add QVC mechanism field (D1), expand D8 material-change review to 9 components, add D9 Required Actions, and replace blank `Programme Leader` lines on `/quality/{mc}/package` with real 3-role D10 sign-offs (P1/P2/P4), D2, D8, D9 details, and Nota Penting D.
**Requirements closed:** `REQ-AD-001` through `REQ-AD-010`
**Acceptance criteria:**
- [x] All 42 D3–D7 items and 6 D2 items persist individually with `Ada`/`Belum`/`TB`, File Ref, and Remarks.
- [x] D8 covers all 9 Guide components; D9 captures Required Actions.
- [x] `/quality/{mc}/package` renders D1–D10 complete with real P1/P2/P4 names, positions, dates, remarks (no `Programme Leader` placeholder), and Nota Penting D.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="QualityTest|DocumentsTest"` (19 passed, 424 assertions; 7 passed, 67 assertions)
- [x] Visual check: inspect `/quality/6/package`
**Dependencies:** Task 7, Task 13
**Files touched:**
- `app/config/qv.php`
- `app/database/migrations/2027_02_10_000011_expand_appendix_d_qv.php`
- `app/app/Modules/Quality/Qv.php`
- `app/app/Modules/Quality/QvController.php`
- `app/routes/quality.php`
- `app/resources/views/quality/package.blade.php`
- `app/tests/Feature/QualityTest.php`
**Status:** COMPLETED

### Task 27: Complete Appendix E (Certificate, Badge, E8, E10), Appendix G (G4, G5 BoK, G8, G9, G11) & F5 Print
**Description:** Complete remaining official Appendix E, G, and F5 fields and printables: add PTj, SLT, LOs, and signatory position to E3 Certificate; add criteria, LOs, QR, non-credit disclaimer, and status/revocation lifecycle to E2 Digital Badges; persist E8 8-item pre-issuance checklist (and run on `replace()`); add E10 3-role sign-off & Nota Penting E; add G4 Evidence/Gaps, G5 Body of Knowledge mapping table, G8 full columns, G9 credit/gap summary, G11 3-role sign-off & Nota Penting G; add F5 printable credit ledger (`?print=1`).
**Requirements closed:** `REQ-C08-007`, `REQ-C11-009`, `REQ-C14-007`, `REQ-C18-003`, `REQ-AE-002`, `REQ-AE-003`, `REQ-AE-007` (replace checks), `REQ-AE-008`, `REQ-AE-009`, `REQ-AG-002`, `REQ-AG-004`, `REQ-AG-005`, `REQ-AG-009`, `REQ-AG-011`, `REQ-AF-005` (print)
**Acceptance criteria:**
- [x] Certificate PDF includes PTj/Faculty name, SLT, Learning Outcomes summary, cohort, and signatory official position (`position`/`position_ms`).
- [x] Digital Badges store and display achievement criteria, LOs, QR code, non-credit disclaimer, and validity/revocation status; E8 8-item checklist persists and executes on `replace()`; E10 3-role sign-off + Nota Penting E present.
- [x] Pathways/Plans include G5 Body of Knowledge mapping table (`Lengkap`/`Sebahagian`/`Tiada` + Gap/Action), G4 Evidence/Gaps, G8 (`Lain-lain`, `TB`, Fulfillment Method, Credits, Remarks), G9 summary/gaps, and G11 3-role sign-off + Nota Penting G; `/my-credit?print=1` prints F5 ledger.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest --filter="CredentialTest|PathwayTest|CreditBankTest|DocumentsTest"` (27 passed, 564 assertions; 15 passed, 291 assertions; 14 passed, 319 assertions; 7 passed, 67 assertions)
- [x] Visual check: inspect Certificate PDF, Badge verify, Pathway/Plan Appendix G, and F5 ledger print
**Dependencies:** Task 7, Task 21
**Files touched:**
- `app/database/migrations/2027_02_10_000012_complete_appendix_e_and_g.php`
- `app/app/Modules/Credentials/IssueCredential.php` & `CredentialController.php`
- `app/resources/views/credentials/certificate.blade.php` & `sheet.blade.php`
- `app/config/pathways.php`
- `app/app/Modules/Pathways/PathwayController.php` & `resources/views/pathways/show.blade.php`
- `app/app/Modules/Offering/CatalogueController.php` & `resources/views/public/pathway-print.blade.php`
- `app/resources/views/creditbank/ledger.blade.php`
- `app/tests/Feature/CredentialTest.php`, `PathwayTest.php`, `CreditBankTest.php`
**Status:** COMPLETED

### Checkpoint 6: After Tasks 23–27
- [x] `DocumentsTest`, `MonitoringTest`, `QualityTest`, `CredentialTest`, `PathwayTest`, `OfferingTest` pass 100%
- [x] All official printables (A, B, C, D, E, F1–F11, G, H) visually inspected with zero missing sections or fabrications

---

## Phase 7: Bilingual EN/BM Parity, Demo Seeders, Docs & Independent Re-Audit

### Task 28: Complete EN/BM localization across UI, PHP services, notifications & printouts
**Description:** Add all 655 missing Blade `__()` keys to `app/lang/ms.json`, wrap hardcoded English strings in `Plans.php`, `Trace.php`, `Reports.php`, `Role.php`, and print views, render `users.position_ms` and `learning_outcomes.text_ms` on BM ACR/letter/certificate prints, fix `"Lulus"` mistranslation on F9 Stage 1 Available, localize notification status interpolations, and persist `users.locale` when toggling `EN | BM`.
**Requirements closed:** `REQ-C12-007`, `REQ-C18-012`, `REQ-AF-001`–`004` (BM parity), `REQ-AF-008` (BM parity), `REQ-AF-009` (BM label), `REQ-AF-011` (BM position)
**Acceptance criteria:**
- [x] Localization audit script reports **0** missing Blade `__()` keys in `app/lang/ms.json` (4,174 keys total).
- [x] BM ACR print (`/my-credit/1?print=1` in BM) renders Malay positions (`position_ms`), Malay LOs (`text_ms`), Malay F2 evidence refs, and `"Tersedia"` (not `"Lulus"`) on F9 Stage 1.
- [x] Switching `EN | BM` updates `users.locale` so subsequent notifications use the user's chosen language without raw English status tokens.
**Verification:**
- [x] Tests pass: `cd app; php vendor/bin/pest tests/Feature/LanguageTest.php` (2 passed, 11 assertions) & `CreditBankTest.php` (14 passed, 319 assertions)
- [x] Visual check: inspected BM ACR print, BM Transfer Letter, BM Proposal print, BM Pathways
**Dependencies:** Tasks 23–27
**Files touched:**
- `app/lang/ms.json`
- `app/app/Modules/Core/UiLanguage.php`
- `app/app/Modules/Core/Templates.php`
- `app/app/Modules/CreditBank/Acr.php`
- `app/resources/views/creditbank/acr.blade.php`
- `app/resources/views/transfer/show.blade.php`
- `app/tests/Feature/LanguageTest.php`
**Status:** COMPLETED

### Task 29: Remediate all demo seeders so `mcms_demo` satisfies every Guide rule
**Description:** Fix every seeded demo violation across `ComprehensiveTestingSeeder`, `DemoScenarioSeeder`, `DemoQualitySeeder`, `DemoTransferSeeder`, and `DemoPathwaySeeder` so freshly seeded `mcms_demo` passes all Guide invariants without violating a single rule.
**Requirements closed:** Demo data integrity across Ch. 6, 8, 11, 12, 13, 14, 15, 17, 18, App. F–H
**Acceptance criteria:**
- [x] `MC#07` MCE has `< 40h` SLT (`24h`), `proposed_credit = null`, `proposed_mqf_level = null`, `credit_sought = 0`; `MC#03` & `MC#22` clear `proposed_mqf_level`.
- [x] All 17 UMC versions have `SUM(mc_modules.slt_hours) == parent_courses.slt_hours`; zero SAMCs have `parent_course_id`.
- [x] Zero `qv_records` attached to UMCs; all SAMC QV cycles are `<= 3 years`.
- [x] Zero P5 (`codl_admin` Farid Hashim) attributions on ACRs, ABC deposits, corrections, disposals, credentials, or suspensions (replaced with proper P6/P7/P2/P3 actors).
- [x] `ComprehensiveTestingSeeder` uses 10-year `credit_expiry` and valid `deposit_status`; `ENR-2026-0017` has 2/2 achieved modules; `PLAN-2026-0001` has accurate `utem_percent` (91.7%) and no premature award; `TR-2026-0003` respects 2.0-credit ACR; valid F6 keys and F7 Guide values with zero fabricated `>= 80%` strings; `ACR-2026-0001` snapshots Cloud Native Development's real LOs.
**Verification:**
- [x] Command: `cd app; php artisan migrate:fresh --seed` succeeds cleanly
- [x] Audit script confirms **0** violations in `mcms_demo`
**Dependencies:** Tasks 6–28
**Files touched:**
- `app/database/seeders/ComprehensiveTestingSeeder.php`
- `app/database/seeders/DemoScenarioSeeder.php`
- `app/database/seeders/DemoQualitySeeder.php`
- `app/database/seeders/DemoTransferSeeder.php`
- `app/database/seeders/DemoOfferingsSeeder.php`
**Status:** COMPLETED

### Task 30: Full regression, retract overclaiming docs & run independent compliance re-audit
**Description:** Run the complete Pest suite and Playwright E2E suite cleanly, archive/retract stale overclaiming audit PDFs (`MCMS_Appendix_F_Compliance_Audit_v2.2.pdf`, `MCMS_Guide_Compliance_and_Verification_Audit_v2.2.pdf`, root `MCMS_Appendix_F_Compliance_Audit.pdf`), update `README.md`/`GEMINI.md`/`HANDOVER.md` with verified metrics, and re-audit all 241 requirements to update `docs/compliance-audit/`.
**Requirements closed:** Documentation accuracy & final verification across all 241 requirements
**Acceptance criteria:**
- [ ] `php vendor/bin/pest` passes **100%** (0 failures).
- [ ] `npx playwright test` passes **100%** (0 failures).
- [ ] Independent re-audit produces `0 WRONG` and `0 MISSING` across all 212 mandatory `SYS-*` requirements.
- [ ] Overclaiming prior audit PDFs are archived/superseded and root docs match verified numbers.
**Verification:**
- [ ] Full Pest run: `cd app; php vendor/bin/pest`
- [ ] Full Playwright run: `cd app; npx playwright test`
- [ ] Re-run compliance validator across updated `docs/compliance-audit/TRACEABILITY_MATRIX.csv`
**Dependencies:** Tasks 1–29
**Files likely touched:**
- `README.md`, `GEMINI.md`, `HANDOVER.md`, `app/README.md`
- `docs/compliance-audit/TRACEABILITY_MATRIX.csv` & `AUDIT_REPORT.md`
**Estimated scope:** Small (docs + verification run)

### Checkpoint 7 (Final): Ready for Sign-Off
- [ ] Full Pest suite 100% green (`0` failures)
- [ ] Full Playwright suite 100% green (`0` failures)
- [ ] Zero `WRONG` and zero `MISSING` mandatory `SYS-*` findings on re-audit
- [ ] Human review & sign-off
