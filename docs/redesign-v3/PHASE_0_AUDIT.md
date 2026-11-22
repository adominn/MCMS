# UTeM MCMS Phase 0: Audit & Safety Net Specification

**Document Version**: 1.0  
**Date**: 22 November 2026  
**Target Branch**: `redesign/uiux-v3` (branched from commit `620614f` on `main`)  
**Design Reference**: `design-samples/linear-precision/DESIGN.md` (v2.4) & `UIUX/v3/`  
**Application Target**: `app/` (Laravel 13.38 / PHP 8.3 / MySQL 8.4 LTS)  

---

## 1. Executive Summary & Safety Net Directives

### 1.1 Objective
Modernize the user interface of the UTeM Micro-Credential Management System (MCMS) from the legacy IBM Carbon / uLearn Navy (`#1e2a6e`, 0px flat corners) styling to the approved **v3 "Linear-Precision" Design System** (Electric Cobalt `#0052ff`, `Plus Jakarta Sans`, 4–8px controlled micro-radii, stark `#090a0f` borders, pure white brand header, and `#0b1a3d` Deep Cobalt Navy navigation with 28px contours).

### 1.2 Non-Negotiable Safety Rules (Zero Functional Regressions)
1. **Visual Changes Only**:
   - Never modify Laravel route definitions (`app/routes/*.php`).
   - Never modify controller actions or request parameters (`app/app/Modules/*`).
   - Never modify database migrations, Eloquent models, or validation rules.
   - Never change HTML element IDs, form field names, or data attributes (`data-tab`, `data-pane`, `data-auto-filter`, `data-target`).
2. **One Atomic Git Commit Per Phase**:
   - Each phase is executed, verified, and committed individually to allow instant clean rollback if needed.
3. **No Unrequested Heavy Testing**:
   - Do not trigger full headless Playwright or Pest test suites without explicit user confirmation. Perform targeted route checks and view syntax validations (`php -l`) at each stage.
4. **Prohibition of Generic Icons / Emojis (D-53)**:
   - Rely strictly on IBM Carbon / Linear typography, geometry, and semantic status tags. Never introduce generic emojis (⏱, 🔒, 💡, 👉, etc.).
5. **No Square Brackets Around Badges**:
   - Render clean pills (`FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In`) without literal `[...]`.

---

## 2. Extracted v3 Design Tokens (`UIUX/v3/css/tokens.css`)

### 2.1 Color Palette
| Token Variable | Hex / Value | Semantic Role |
| :--- | :--- | :--- |
| `--color-primary` | `#0052ff` | Electric Cobalt: primary brand action, active highlights, key links |
| `--color-primary-hover` | `#0045d8` | Interactive hover state for primary elements |
| `--color-primary-pressed` | `#0036a8` | Active pressed state |
| `--color-primary-deep` | `#0a192f` | Deep Navy Obsidian: dark accents, inverse hero cards |
| `--color-primary-tint` | `#edf4ff` | Ice blue tint for hovered table rows and card accents |
| `--color-primary-line` | `rgba(0, 82, 255, 0.28)` | Focus ring and soft decorative borders |
| `--color-canvas` | `#ffffff` | Pure white page background floor |
| `--color-canvas-subtle` | `#f8fafc` | Ghost slate-50 for alternating sections |
| `--color-surface-1` | `#f1f5f9` | Cool slate-100 for secondary panels, form backgrounds |
| `--color-surface-2` | `#e2e8f0` | Slate-200 for hairline dividers |
| `--color-surface-contrast` | `#090a0f` | Jet black slate for inverse badges and modules |
| `--color-sidebar-bg` | `#0b1a3d` | Deep Cobalt Navy for left navigation drawer |
| `--color-header-bg` | `#ffffff` | Pure white sticky command header |
| `--color-ink` | `#090a0f` | Jet Black typography (~18:1 contrast on white) |
| `--color-ink-muted` | `#475569` | Slate-600 body typography (~4.6:1 WCAG AA pass) |
| `--color-ink-subtle` | `#94a3b8` | Slate-400 technical metadata and hairline outlines |
| `--color-ink-inverse` | `#ffffff` | Crisp white typography on dark navy surfaces |
| `--color-line-hairline` | `#e2e8f0` | 1px hairline border |
| `--color-line-strong` | `#090a0f` | 1px stark jet black outline |
| `--color-card-border` | `#090a0f` | 1px stark jet black card border |
| `--color-success` | `#24a148` | Success state (Approved, Active, Complete) |
| `--color-success-tint` | `#edfcf0` | Soft green background tint |
| `--color-warning` | `#b28900` | Warning state (Pending, Needs Revision) |
| `--color-warning-tint` | `#fefdf0` | Soft amber background tint |
| `--color-error` | `#da1e28` | Error / Danger state (Rejected, Suspended, Discrepancy) |
| `--color-error-tint` | `#fff1f1` | Soft red background tint |

### 2.2 Controlled Micro-Radius Scale
| Token Variable | Value | Intended Usage |
| :--- | :--- | :--- |
| `--radius-none` | `0px` | Strict flat geometry, table cells, divider rules |
| `--radius-xs` | `2px` | Micro tags, inline metadata tags |
| `--radius-sm` | `4px` | Standard buttons, input fields, selects, segmented controls |
| `--radius-md` | `6px` | Standard cards, bento cells, dropdowns |
| `--radius-lg` | `8px` | Modal dialogs, hero callouts, standalone containers |
| `--radius-sidebar-corner` | `28px` | Brand header & sidebar contour curves |
| `--radius-search-pill` | `12px` | Floating search input container |
| `--radius-active-tab` | `12px` | Active navigation pill |
| `--radius-pull-tab-latch`| `24px` | Protruding navigation drawer latch (`0 24px 24px 0`) |
| `--radius-pill` | `9999px` | Circular status indicator pips ONLY |

### 2.3 Typography Scale
| Role | Font Family | Size | Weight | Line Height |
| :--- | :--- | :--- | :--- | :--- |
| **Display XL** | Plus Jakarta Sans | 54px | 300 | 1.10 (-0.035em tracking) |
| **Display LG** | Plus Jakarta Sans | 44px | 300 | 1.15 |
| **Display MD** | Plus Jakarta Sans | 32px | 300 | 1.20 |
| **Headline** | Plus Jakarta Sans | 22px | 600 | 1.30 |
| **Title** | Plus Jakarta Sans | 18px | 600 | 1.35 |
| **Body** | Plus Jakarta Sans | 14px | 400 | 1.55 |
| **Body Small** | Plus Jakarta Sans | 13px | 400 | 1.45 |
| **Caption** | Plus Jakarta Sans | 12px | 400 | 1.40 |
| **Mono Code** | JetBrains Mono / IBM Plex Mono | 12px | 500 | 1.50 |
| **Eyebrow** | JetBrains Mono / IBM Plex Mono | 11px | 600 | Uppercase +0.08em tracking |

---

## 3. Current System Inventory (137 Blade Views across 10 Roles)

### 3.1 Role Architecture & Scope (Appendix H2/H3 Alignment)
1. **Student / Learner (P8)**: Self-service learning dashboard, course registration, results view, verifiable credential wallet, ACR/ABC credit bank status, degree pathway planner, credit transfer applications.
2. **Lecturer / Proposer (P1)**: Proposal authoring (Tabs 1–7), curriculum workspace, cohort creation, syllabus export, mark entry & submission, CQI reporting, QV evidence submission, reviewer scoring.
3. **Deputy Dean / PTj Authority (P2)**: Proposal review & faculty endorsement, readiness review & sign-off, mark submission verification, credentials administration, read-only Transfer Monitor, CQI faculty oversight.
4. **Academic Authority (P3 - Senate & DVC-A)**: Task queue, proposal approval review, Senate credit transfer approvals, stackable degree pathway approval.
5. **QA Officer (P4 - Faculty / Central Quality)**: Proposal quality assessment, independent Quality Verification (QV) dossier auditing, CQI indicator monitoring & alerts.
6. **CODL Admin (P5 - Centre for Open & Distance Learning)**: Central governance, Senate decision recording from minutes, credit transfer on-screen evaluation & decision recording, user/role management, settings, template management, UCreds exchange, audit trails.
7. **Records & Credential Custodian (P6)**: Credential issuance sign-off, mark verification & lock, discrepancy triaging & resolution, retention rule management, trace audits.
8. **Authorized ABC Depositor (P7)**: Academic Bank of Certificate (ABC) operations, ACR queue processing, batch deposits, XML/JSON export, deposit ledger inspection.
9. **Registry / ABC (Dual P6/P7)**: Combined records custodian & depositor workflow for small registry teams.
10. **Industry Expert (External Reviewer)**: External curriculum evaluation, industry rubrics scoring, conflict of interest declaration.

### 3.2 View & Screen Catalog by Module

#### Module 1: Public Portal (`resources/views/public/`)
- `public/home.blade.php`: Portal landing page, search bar, category cards (UMC/SAMC/MCE), upcoming cohorts carousel, verification CTA.
- `public/catalogue.blade.php`: Micro-credential course catalogue with dynamic filters (discipline, mode, faculty, fee, free-only).
- `public/offering.blade.php`: Detailed offering view, syllabus breakdown, CLOs, assessment integrity standards, fee, enrolment CTA.
- `public/pathways.blade.php`: Interactive degree pathways explorer, articulation tables, maximum credit transfer guidance.
- `public/pathway-print.blade.php`: Clean printable articulation plan for student advisory.
- `public/how-it-works.blade.php`: Explainer of 8-stage micro-credential lifecycle (Guide Cl. 5).
- `public/faq.blade.php`: Accordion FAQ covering credit transfer, fees, and MQF compliance.
- `public/verify.blade.php`: Public credential verification hub (certificate code lookup & JSON verification).
- `public/policies.blade.php`: Institutional policies and regulatory compliance references.

#### Module 2: Authentication & Identity (`resources/views/`)
- `signin.blade.php`: Test sign-in hub with persona selector tabs (P1–P8, IE), instant 1-click test login, production credentials form.
- `layouts/app.blade.php`: Top header with user profile menu, workspace switch dropdown (for multi-role users like Nurul Izzah P6/P7), language switcher (`EN | BM`), notification bell.

#### Module 3: Task Queues & Dashboard (`resources/views/`)
- `tasks.blade.php`: Unified task queue with status filtering, urgency indicators, and direct action routing.
- `tasks-show.blade.php`: Task detail view with action buttons and audit context.

#### Module 4: Curriculum & Proposals (`resources/views/curriculum/`)
- `curriculum/mcs/index.blade.php`: List of micro-credentials with status badges, faculty tags, and create proposal action.
- `curriculum/mcs/show.blade.php`: Summary view of micro-credential metadata and versions.
- `curriculum/workspace.blade.php`: 7-tab proposal editing workspace:
  - Tab 1: Basic details & classification.
  - Tab 2: Market justification & industry need.
  - Tab 3: Modules, topics & Student Learning Time (SLT) calculator.
  - Tab 4: Assessment rubrics, CLO mapping & integrity mechanism.
  - Tab 5: Credential design & badge upload.
  - Tab 6: External review feedback & responses.
  - Tab 7: Proposal audit trail & version diff.
- `curriculum/print.blade.php`: Standardized printable curriculum specification.
- `curriculum/version-diff.blade.php`: Visual side-by-side comparison of proposal iterations.

#### Module 5: Governance & Approvals (`resources/views/approval/`)
- `approval/index.blade.php`: Proposals queue awaiting faculty endorsement or Senate approval.
- `approval/show.blade.php`: Detailed proposal review dossier with approval/revision actions.
- `approval/senate.blade.php`: Senate decision recording form (Senate meeting ref, date, decision, conditions, minute excerpt).
- `approval/pack.blade.php` & `pack-print.blade.php`: Complete governance dossier pack for Senate submission.

#### Module 6: Offerings & Cohorts (`resources/views/offerings/`)
- `offerings/index.blade.php`: Active, upcoming, and past cohorts table.
- `offerings/new.blade.php`: New cohort offering creation form (dates, mode, capacity, fee).
- `offerings/show.blade.php`: Cohort operations dashboard, learner enrolment roster, facilitator assignment.
- `offerings/readiness.blade.php` & `readiness-print.blade.php`: Readiness verification checklist (Appendix B) and sign-off.

#### Module 7: Assessment & Results (`resources/views/results/`)
- `results/index.blade.php`: Cohorts ready for mark entry and grade submission.
- `results/cohort.blade.php`: Learner marks entry grid, grade calculation, and submission sign-off.
- `results/verify.blade.php`: Records custodian verification & locking interface.
- `results/show.blade.php`: Final signed-off grade sheet with completion audit.

#### Module 8: Completion & Credential Issuance (`resources/views/credentials/` & `completion/`)
- `completion/index.blade.php`: Candidates eligible for credential issuance.
- `completion/show.blade.php`: Individual completion summary and credit award.
- `completion/signoff.blade.php`: Two-officer sign-off interface (P6 + P7).
- `credentials/admin.blade.php` & `admin-show.blade.php`: Issued credentials management and revocation.
- `credentials/wallet.blade.php`: Learner digital badge and certificate wallet.
- `credentials/certificate.blade.php`: High-resolution digital certificate view.
- `credentials/sheet.blade.php`: Verifiable official academic transcript / diploma supplement.

#### Module 9: Credit Bank (ACR / ABC) & Transfers (`resources/views/creditbank/` & `transfer/`)
- `creditbank/queue.blade.php`: Authorized Academic Record (ACR) queue for ABC deposit.
- `creditbank/batch.blade.php`: ABC batch creation, checksum verification, and deposit execution.
- `creditbank/ledger.blade.php`: Official ABC deposit ledger with transaction hashes.
- `creditbank/acr.blade.php`: Individual ACR record view.
- `transfer/index.blade.php`: Credit transfer application history.
- `transfer/queue.blade.php`: Credit transfer review queue (interactive for CODL Admin, read-only Transfer Monitor for Deputy Dean).
- `transfer/create.blade.php`: Learner credit transfer submission form.
- `transfer/show.blade.php`: Detailed credit transfer case with on-screen LO matching.
- `transfer/decide.blade.php`: CODL Admin / Senate credit transfer decision recording.

#### Module 10: Quality Verification (QV) & CQI (`resources/views/quality/` & `monitoring/`)
- `quality/index.blade.php`: QV dossiers queue (SAMC / MQF credit recognition).
- `quality/show.blade.php` & `quality/dossier.blade.php`: QV dossier review with panel scoring.
- `monitoring/index.blade.php`: Continuous Quality Improvement (CQI) dashboard.
- `monitoring/cohort.blade.php`: Cohort performance analysis and student evaluation scores.
- `monitoring/cqi.blade.php` & `cqi-print.blade.php`: Annual CQI report generation (Appendix C).
- `monitoring/suspension-notice.blade.php`: Low-performing cohort suspension notice.

#### Module 11: Stackable Pathways (`resources/views/pathways/`)
- `pathways/index.blade.php`: Articulation pathways matrix.
- `pathways/show.blade.php`: Degree pathway detail with stackable course map.
- `pathways/mine.blade.php` & `plan.blade.php`: Student personalized pathway degree planner.

#### Module 12: System Admin, Records & Settings (`resources/views/admin/`, `records/`, `settings/`)
- `admin/users/index.blade.php` & `show.blade.php`: User management and role assignment.
- `records/index.blade.php` & `discrepancies.blade.php`: Discrepancy triaging and resolution.
- `records/retention.blade.php`: Archival and record retention policy rules.
- `records/trace.blade.php`: End-to-end trace audit from proposal to certificate.
- `settings/index.blade.php`: University-wide governance settings.
- `notifications/index.blade.php`: In-app notification centre.

---

## 4. Baseline Feature Regression Checklist

Prior to any visual changes, the following functional workflows are verified as working and must remain 100% operational after redesign:

- [ ] **Sign-in & Authentication**:
  - [ ] 1-click test sign-in works for all 10 persona roles without MFA prompts.
  - [ ] Role switcher allows Nurul Izzah to switch between P6 and P7 workspaces.
  - [ ] Language toggle (`EN | BM`) flips strings smoothly across all views.
- [ ] **Proposal Submission & Governance**:
  - [ ] Lecturer can draft a proposal across all 7 workspace tabs.
  - [ ] SLT calculator updates dynamic hours and credit recommendations.
  - [ ] Deputy Dean can review proposal and issue Faculty Endorsement.
  - [ ] QA Officer can access proposal review without 403 error.
  - [ ] CODL Admin can record Senate decision from minutes.
- [ ] **Cohort Operations & Readiness**:
  - [ ] New cohort can be created with dates, capacity, and fee.
  - [ ] Readiness verification (Appendix B) can be completed and signed off.
  - [ ] Published offering appears in Public Catalogue with active filters.
- [ ] **Results & Verification**:
  - [ ] Lecturer can input and save student marks in cohort grid.
  - [ ] Deputy Dean and Records Custodian can access results without 403 error.
  - [ ] Results locking prevents subsequent mark edits.
- [ ] **Credentials & Transcripts**:
  - [ ] Digital certificate renders with verifiable cryptographic hash and QR code.
  - [ ] Printable transcript sheet formats cleanly.
  - [ ] Public verify tool validates authentic certificate codes.
- [ ] **Credit Bank & Transfers**:
  - [ ] ACR record generates upon result lock.
  - [ ] ABC Depositor can process deposit queue and export batch.
  - [ ] Learner can submit credit transfer application.
  - [ ] CODL Admin can evaluate LO match and record Senate transfer decision.
  - [ ] Deputy Dean can inspect read-only Transfer Monitor without 403 error.
- [ ] **Quality & CQI**:
  - [ ] QV dossier can be reviewed and scored.
  - [ ] CQI feedback form captures learner evaluation.
  - [ ] Suspension notice generates when cohort thresholds fail.

---

## 5. Migration Execution Strategy (Phases 1 to 6)

```
[Phase 0: Audit & Safety Net] (Current)
        │
        ▼
[Phase 1: Rewrite DESIGN.md] ─── Update design spec with v3 tokens & mapping table
        │
        ▼
[Phase 2: Design Tokens Only] ── CSS variables swap in mcms.css (Zero markup changes)
        │
        ▼
[Phase 3: Shared Components] ─── Restyle buttons, inputs, tables, cards, badges, tabs
        │
        ▼
[Phase 4: Layout Shell] ──────── Restyle sidebar (#0b1a3d, 28px contours) & header
        │
        ▼
[Phase 5: Page Modules] ──────── Stepwise page migration with regression check per module:
                                 1. Auth & Signin
                                 2. Tasks & Dashboard
                                 3. Curriculum & Proposals
                                 4. Offerings & Results
                                 5. Credentials & Credit Bank
                                 6. Quality & Pathways
                                 7. Admin & Records
        │
        ▼
[Phase 6: Polish & QA] ───────── Empty states, print styles, accessibility audit
```

### Safety Commit Checkpoints
- Every phase will conclude with a dedicated git commit.
- Target branch: `redesign/uiux-v3`.
- Any unexpected discrepancy will be isolated and rolled back without affecting business logic.
