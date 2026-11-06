# MCMS handover (written 29 October 2026)

Read this first in a new session. It says what the project is, what is done, how to run and test it, what is still open, and the rules the project owner set.

## 1. Main objective

Build the **UTeM Micro-Credential Management System (MCMS)** so that it **looks and behaves like the approved clickable prototype** (style, arrangement, structure, content) **but is fully working with a real backend**, and add **any extra feature the documents require** that the prototype does not show, in the same style and kept simple.

The project owner's words (28 October 2026): the prototype/UI design must be followed properly; extra features that the prototype lacks are fine "to meet the requirement", but they must follow the same style/arrangement/theme, be easy for the end user, and not show everything on one page (summary and next action first, detail on request). This is recorded as **D-38** (Decision Log v0.8); it replaces D-35.

## 2. The documents (the sources of truth)

All under `C:\Users\Adomin\Documents\Intern\CODL\MC-ABC System\`. Documents are versioned copies; **never edit a version in place**, copy to the next version (source + PDF).

| Document | Latest | Where |
|---|---|---|
| Glossary and Decision Log | v0.8 (D-38 added, D-35 superseded) | `Decision log/v0.8/src/01-glossary.html`, PDF `Decision log/MCMS_Glossary_and_Decision_Log_v0.8.pdf` |
| SRS (requirements, FR-/NFR-/IR- ids) | v1.2 | `SRS/v1.2/src`, `SRS/MCMS_SRS_v1.2.pdf` |
| SDD (architecture, data, modules) | v1.2 | `SDD/v1.2/src`, `SDD/MCMS_SDD_v1.2.pdf` |
| PRD (scope, release plan, demo scenario PRD 7) | v1.1 | `PRD/v1.1`, `PRD/MCMS_PRD_v1.1.pdf` |
| UI/UX Design Specification | v1.3 (records D-38 and the summary-first rule, section 4.5) | `UIUX Spec/v1.3/src`, `UIUX Spec/MCMS_UIUX_Spec_v1.3.pdf` |
| Approved clickable prototype | v2 | `UIUX/v2/prototype` (index.html, `js/core.js`, `js/data.js`, `js/screens/*.js`, `css/mcms.css`, `css/proto.css`) |
| Design tokens | | `DESIGN.md` (Carbon with uLearn Navy). The navy `#1e2a6e` left sidebar is the owner's choice: keep it |
| UCreds content (public catalogue audit) | | `UCreds/` (identical copy in "MC-ABC System - Antigravity 4/UCreds") |
| Review notes | | `Release1_Review_2026-10-28.md`, `Consistency_Review_2026-10-27.md`, `UI_Alignment_Plan_2026-10-28.md` |

**graphify-out** (`graphify-out/GRAPH_REPORT.md`, `graph.json`, `graph.html`) is a knowledge graph of the project documents. `CLAUDE.md` says: for questions about the documents (Guide, Glossary and Decision Log, SRS, design docs) read `graphify-out/GRAPH_REPORT.md` first and answer with `graphify query "<question>"`; run `/graphify --update` after documents change. It has not been updated for SRS v1.2, SDD v1.2, Decision Log v0.7/v0.8 or UI/UX v1.3, so run `/graphify --update` early in the new session.

Build a document PDF (from the project root, PowerShell; the global playwright-cli path is missing so PWC is required):

```powershell
$env:PWC=(Resolve-Path 'app\node_modules\playwright-core').Path; & 'SRS\build\build.ps1' -Src '<version dir>\src' -Out '<pdf path>' -Ref <REF>
```

## 3. The application

`app/` is a Laravel 13.38 / PHP 8.3 / PostgreSQL 16 modular monolith. Plain Blade plus CSS (no Livewire, Vite, Redis), jobs via the scheduler or inline, modules under `app/app/Modules/*`, routes in `app/routes/*.php` (registered from `routes/web.php`), views in `app/resources/views`, front end in `app/public/css/mcms.css` and `app/public/js/mcms.js`. Fonts: IBM Plex Sans/Mono in `public/fonts`.

Release 1 (increments 1.1 to 1.4, modules MD-00 to MD-15) and the four-act PRD Section 7 scenario with a test-only demo clock (FL-19) are built. `app/README.md` lists every module, flow, decision link and known gap: read it.

### Run and test

- Database: Docker (`docker compose up -d mysql` in `app`, port 3306; PostgreSQL container `db` remains on port 5433 as backup). Configured with MySQL 8.4 LTS under Decision D-39.
- Databases: `mcms_demo` (what `.env` uses; clean data), `mcms` (base), `mcms_test` (Pest), `mcms_e2e` (Playwright, reset by globalSetup), `mcms_scenario` (scenario).
- Serve: `php artisan serve --port=8110` (test sign-in: choose a persona; staff roles ask for any 6 digits).
- Pest: `php vendor/bin/pest` (366 tests, 5225 assertions, all passing). **Run one Pest run at a time**: overlapping runs deadlock `mcms_test` and cause false failures. Pest helpers are centralized in `tests/Helpers.php`.
- Playwright (Edge, `channel: 'msedge'`): `npx playwright test` (23 flows, serial and state-dependent), `npm run test:scenario` (4 acts, port 8102).
- Screenshots for comparing with the prototype: `MSYS_NO_PATHCONV=1 node tests/E2E/shots.mjs "<persona name>" <outdir> /path1 /path2` (Git Bash rewrites leading slashes, hence the variable).
- Prototype: `UIUX/v2/prototype/index.html` served by the `mcms-prototype` launch config on port 8731 (`#/st/home`, `#/lec/home`, `#/dd/queue`, `#/codl/users`, `#/doc/certificate/<id>` ...). The Browser pane viewport often resets to narrow: set it to 1440x900 before screenshots.
- Last verified state before this handover: Pest 329/329, flows 23/23, scenario 4/4 (before the last batch of student-page changes). A final run (flows then Pest) was in progress when this was written: **re-run all three first** and fix anything red (a likely cause of red flows is test locators that no longer match the new layouts, see conventions below).

## 4. What was done in this stretch

1. **Release 1 review and doc updates**: SRS v1.2, SDD v1.2, Decision Log v0.7 then v0.8 (D-34 UCreds content basis, D-35 now superseded, D-36 VC-JOSE proof, D-37 demo clock, **D-38 follow the prototype**), UI/UX Spec v1.3.
2. **UCreds content**: 59 courses loaded as reference data (`ucreds_courses`, CODL screen "UCreds content", datalist on the offering form).
3. **Shared front end (`public/js/mcms.js`, `public/css/mcms.css`)**: sortable/searchable/paged tables (search and pager only over 10 rows); whole-row click for rows with one link (and `tr[data-href]`); confirmation dialogs (`data-confirm`, `data-reason`, `data-danger`); toasts from flash messages; notice and user menus; tabs (`data-tabs`, `data-tab`, `data-pane`, `data-auto` opens the first non-empty tab, `data-empty`); case switch select (`select[data-switch]` + `[data-case][data-id]`); folds (`details.fold`); styled 403/404/419/500/503 pages.
4. **Shell**: header like the prototype (per-role search, help, bell, user menu with profile/preferences/sessions/documents/help/sign out), `/search`, `/profile` (tabs: Details, Notifications with the email switches, Sign-in and sessions, My personal data), per-role sidebars (lecturer, deputy dean, CODL, industry expert) with the prototype's names, order and counts. Sign out lives in the user menu.
5. **EN/BM**: header switch (`POST /language`, session `ui_lang` and `lang`, middleware `Core\UiLanguage`), `lang/ms.json` translates sidebars, menus and static page headings (the `<h1>` strings were wrapped in `__()`). **Only navigation and headings are Malay**; table headers, buttons, subtitles and body text are still English. The Malay is a draft for review.
6. **Screens reworked toward the prototype** (summary first, detail in tabs/folds): student home, catalogue, registrations, **registration detail**, My results and Achievement record (registration selector + summary strip + tabs), credentials, credit ledger, credit transfer index, **My degree plan** (track, KPIs, tabs), **Documents** (tables per type), **Notifications** (prototype layout, All/Unread/Read tabs, Mark all read, a click marks read and opens the page where the action is done via `Notifier::linkFor`), **Profile**, **Feedback** (table of cohorts first, click a row for the rating page with 18 items in 6 groups; the 6 overall ratings are required, 12 are optional; `config/monitoring.php` `feedback_groups`), public catalogue and **public offering page** (two columns, one Register button); lecturer micro-credential page (tabs); deputy dean proposal review (folds), deposits and transfer queue (tabs), completion checks (folds); industry expert home is "My review invitations"; printable documents frame (`layouts/print.blade.php`: dark bar, A4 sheet, letterhead, footer) with the receipt and certificate converted.
7. **Bugs found and fixed on the way**: feedback page crash (a `each()` callback returning false stops the loop), micro-credential page used a variable before it was defined, offering page crash when a seeded cohort lacks a readiness row, notifications body truncation broke a flow.

## 5. What is still open (suggested order)

**A. Student role (Completed & Verified)**
- **Completed screens**:
  - `st/ledger` (`resources/views/creditbank/ledger.blade.php`): 5 Carbon KPI cards (`Credits earned`, `Available`, `Provisionally redeemed`, `Redeemed`, `Expired`) matching prototype `st/ledger`, computed in `CreditBankController@ledger`.
  - `st/enrolments` & `st/enrolment/:id`: headers, status badges, module list table, withdrawal dialogues, folds, and payment details wrapped with `__()`.
  - `st/results`: KPIs, entity switcher, assessment breakdown, badge cards, and platform progress wrapped with `__()`.
  - `st/credentials` & `st/credential/:id`: verification sharing card, copy link, QR code, revision history, and employer view.
  - `st/register/:cohort`: 4-step stepper (Eligibility, Acknowledgement, Payment, Confirmation) with dummy card/FPX and confirmation modal.
  - `records/mine`: achievement record and internal caveats wrapped with `__()`.
  - `help`: quick guides across all roles and recognition glossary wrapped with `__()`.
- **Bilingual coverage**: `lang/ms.json` expanded to 490 dictionary keys covering all student, registration, payment, result, and credit workflows.
- **Verification**: Pest (366/366 passed, 5,225 assertions) and Playwright (23/23 flows passed) 100% green on MySQL 8.4 LTS.

**B. Other roles (Lecturer & Deputy Dean Completed & Verified)**
- **Completed Lecturer & Deputy Dean screens**:
  - **Proposal workspace (`curriculum/`)**: index tabs, 3-step wizard (`new.blade.php`), workspace tabs (Basics, Modules, Justification, Outcomes, Assessment, Credential, Readiness, Comments, Submit), live check panels (`Clear`, `Autosaved`, `Error`, `Warning`), and micro-credential management (`mcs/` show, change, re-offer).
  - **Approval & Reviews (`approval/`, `reviews/`)**: index status tabs, proposal evaluation summary (`_summary.blade.php`), reviewer assignment, conflict-of-interest declarations, tracked review comments & resolution, endorsement, decision pack (`pack.blade.php`), minor change acknowledgment (`change.blade.php`), and Senate decision recording (`senate.blade.php`) with two-column governance guidance.
  - **Offerings & Cohort Readiness (`offerings/`)**: offering index, cohort parameter setup, readiness checklist (Lecturer responses, Deputy Dean verification & confirmation), and participant rosters with CSV export.
  - **Results Verification & Completion (`results/`, `completion/`)**: cohort assessment table with component marks, provisional/held/verified badges, change request modal, integrity case management, Deputy Dean verification summary, and pre-issue completion checks with single-action batch confirmation (`completion/show.blade.php`).
  - **Quality, Transfers, Deposits, & Tasks**: CQI dashboard & effectiveness tracking (`monitoring/`), Quality Verification dossiers (`quality/`), credit transfer evaluations with learning-outcome comparison (`transfer/`), ABC deposit queue (`creditbank/queue.blade.php`), credential admin corrections & revocations (`credentials/admin.blade.php`), stackable pathways (`pathways/`), and task queue (`tasks.blade.php`).
- **Bilingual coverage**: `app/lang/ms.json` expanded to 677 keys covering all newly converted strings across Lecturer and Deputy Dean interfaces.
- **Verification**: Pest test suite (366/366 passed, 5,225 assertions) and Playwright E2E test suite (23/23 flows passed) 100% green on MySQL 8.4 LTS.
- **Remaining roles for future pass**: CODL Admin (users, reference data, settings, templates, UCreds, audit, retention), Industry Expert.

**C. Printable documents (Completed & Verified)**
- **All 17 official printable documents aligned with the prototype frame (`UIUX/v2/prototype/js/screens/docs.js`)**:
  - `receipt`: Official payment receipt (`resources/views/student/receipt.blade.php`) with fee breakdown and dummy payment reference.
  - `enrolment`: Enrolment confirmation letter (`resources/views/student/confirmation.blade.php`) with UCreds access steps, recognition wording, and withdrawal deadline.
  - `certificate`: Bilingual landscape certificate (`resources/views/credentials/certificate.blade.php`) with QR verification code and REVOKED badge handling.
  - `credential`: Digital Credential sheet (`resources/views/credentials/sheet.blade.php`) with Open Badges 3.0 / W3C Verifiable Credential proof block, JSON structure, and Download .json action.
  - `record`: Untabbed, comprehensive achievement record (`resources/views/records/mine.blade.php`) with registration summaries, modules/badges, verified assessment results, and Registrar/CODL signatures.
  - `acr`: Academic Credit Record (`resources/views/creditbank/acr.blade.php`) with consent/deposit status, 10-year validity note, and Deputy Dean signature.
  - `proposal`: Proposal print / Appendix A (`resources/views/curriculum/print.blade.php`) with 10 sections, LO-assessment alignment matrix, and proposer/dean sign-off.
  - `pack`: Senate decision pack (`resources/views/approval/pack.blade.php`) with category verification, reviewer summaries, comment resolution, and official Senate decision minute record box.
  - `report`: 10 standard university reports (`resources/views/records/report.blade.php`) with cover metadata, summary figures, totals rows, and Download .csv action.
  - `abc`: ABC deposit batch export (`resources/views/creditbank/batch.blade.php`) with storage-only note, privacy protections (no student ID/email on paper), and Download .csv action.
  - `qvpack`: SAMC Quality Verification submission package (`resources/views/quality/package.blade.php`) with evidence index, CQI indicators, and dual declarations.
  - `transfer`: Credit transfer decision letter (`resources/views/transfer/show.blade.php`) with per-line course equivalence outcomes and conditions.
  - `suspend`: Suspension or termination notice (`resources/views/monitoring/suspension-notice.blade.php`) with affected cohort list, transition plan, and participant safeguards.
  - `export`: UCreds enrolment export manifest (`resources/views/ucreds/manifest.blade.php`) with export columns and Download .csv action.
  - `import`: UCreds import validation report (`resources/views/ucreds/report.blade.php`) with staged/provisional counts, rejected rows breakdown, and Download .csv action.
  - `audit`: Scoped audit trail export (`resources/views/records/audit.blade.php`) with cryptographic SHA-256 hash chain verification statement and Download .csv action.
  - `documents`: Official Documents & Exports hub (`resources/views/records/documents.blade.php`) organizing receipts, certificates, ACRs, university reports, proposal prints, decision packs, and transfer letters.
- **Bilingual dictionary**: `app/lang/ms.json` expanded to 708 keys covering all document titles, table headers, declarations, and notes.
- **Verification**: Pest (366/366 passed, 5,225 assertions) and Playwright E2E (23/23 flows passed including FL-24) 100% green on MySQL 8.4 LTS.

**D. Smaller gaps**: drawers (side panels) from the prototype, sidebar counts for cohorts and CQI, a working "Switch persona" is intentionally absent, README gap list.

**E. Documents**: when decisions or behaviour change, version-copy SRS/SDD/UIUX/Decision Log (+ PDF) and run `/graphify --update`. The SRS/SDD text about UI fidelity should be checked against D-38.

**F. The whole-system review**: the owner said they will ask for it **after** the UI work and only then. Do **not** start it until asked (it was planned as a requirement-by-requirement check of SRS/PRD/SDD/UIUX against the running system).

## 6. Conventions and rules from the owner (follow them)

- Follow `DESIGN.md` for all UI. Keep the navy sidebar.
- Match the prototype in style, arrangement, structure and content; extra features must use the same theme and stay simple (summary and next action first, detail on request).
- Never edit a document version in place; copy to the next version plus PDF.
- Proceed on assumptions; do not wait for supervisor review (it happens after the prototype is shown).
- Each build step ends with: Pest tests, a Playwright flow (add or update), a README row, and a short summary to the owner.
- Locator hygiene in Playwright specs: link names now often carry a count (for example "Credentials 5"), the sidebar helper `nav()` in the specs accepts an optional trailing count; whole-row clicks and tabs mean content can be hidden until a tab is opened (click the tab in the test); sign out is `page.locator('.who-btn').click()` then the Sign out button.
- Windows shell notes: Bash heredocs with apostrophes often fail; write files with the editor tool and run Python from a file; use `MSYS_NO_PATHCONV=1` for paths that start with `/`.
- Agents: use the `scout` agent (Haiku) to dig, `planner` (Opus) to plan or review, `builder` (Sonnet) to implement, for non-trivial work; skip them for small edits.
- Memory notes exist under `C:\Users\Adomin\.claude\projects\C--Users-Adomin-Documents-Intern-CODL-MC-ABC-System\memory\`.

## 7. First steps for the new session

1. Read this file, `CLAUDE.md`, `app/README.md`, `DESIGN.md`, and `graphify-out/GRAPH_REPORT.md`; run `/graphify --update`.
2. Start Docker, `docker compose up -d db` in `app`, run Pest once, then `npx playwright test` and `npm run test:scenario`; fix anything red.
3. Serve the app (`php artisan serve --port=8110`) and the prototype (port 8731); take screenshots of the student pages next to the prototype and continue with section 5A.
