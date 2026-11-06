# MCMS UI design basis (v2)

The rules every MCMS screen follows, drawn from the documents we wrote. When a screen and this basis disagree, fix the screen. When this basis and the SRS disagree, the SRS wins and this file is corrected in a new version.

| Source | Version | What it gives the design |
|---|---|---|
| Glossary and Decision Log | v0.5 | D-01..D-29 (user model D-28, automatic issue D-29, one stage one record D-15), A-01..A-09, OI-xx |
| SRS | v1.0 (rev. 2) | FR per module (3.1–3.17), BR-01..42, IR, DR, NFR, SR, state diagrams |
| PRD | v1.0 | Features F-01..F-38, stories US-01..55, journeys 4.1–4.4, demo scenario 7.1, status vocabulary 8.3 |
| DESIGN.md | ulearn-carbon | Look: IBM Plex Sans, flat 0px corners, uLearn Navy #1e2a6e as the only accent |
| taste-skill app-screens | 2.1.4 | Working-screen craft: quiet chrome, colour on data, tables by rules and alignment |

Detail per requirement is in [basis/srs-fr-inventory.md](basis/srs-fr-inventory.md), which lists every FR as actor, action, confirmation, result and blocking checks.

## 1. Principles

1. **One stage, one record, one look (D-15, BR-16).** Approval, offering, result verification, completion, credential, QV, ACR, ABC deposit, transfer and redemption are separate decisions. No button combines two stages. In particular, **verifying results** creates module achievements and badges, and **confirming completion** is a separate later action that makes the system issue the certificate and Digital Credential.
2. **Every person lands on "what needs me today"** (PRD 8.2). Staff land on a task list. Participants land on their learning and credit.
3. **Only the authorised role sees a decision button (BR-42, D-12).** CODL has no approve, verify, issue, ACR, deposit or transfer action anywhere. The Senate never logs in; the deputy dean records its decisions with the meeting reference. The industry expert can only comment.
4. **Recognition text is generated, never typed (BR-22, D-17).** Credit, MQF level and QV claims come only from verified records. A SAMC shows "credit after QV" until QV is recorded. When QV lapses, the claim disappears from the catalogue and from new credentials.
5. **The rule sits beside the field and is checked as you type** (PRD 8.2, ir-forms). Blocking errors stop submission. Warnings let the user continue with the warning on record.
6. **History is one click away (D-20).** Every record has a History tab or panel, drawn from the append-only audit trail. Nothing is ever deleted; a correction makes a new revision.
7. **Honest about credit.** Every place a participant meets credit says that deposit is storage, not a transfer, and that each transfer is decided case by case (BR-26, BR-31, US-19, US-49).

## 2. Interaction rules

### 2.1 Every control has a defined result
Each button, link, tab, row and menu item does exactly one of these things. A control that does nothing is a defect.

| Result | Used for | Prototype attribute |
|---|---|---|
| Go to a screen | Opening a record, a step of a wizard, a list | `data-go` |
| Confirmation dialog | Anything in 2.2 | `data-modal` |
| Side panel | Reading a record without leaving the list (history, evidence, email) | `data-drawer` |
| In-place change and toast | Saving a draft, marking read, adding a row | `data-act` + `data-done` |
| Menu | Profile, notifications, row actions | `data-menu` |

### 2.2 Confirmation dialogs (ir-confirm)
**Ask for confirmation, with a summary of the effect, before:** submit, endorse, return, record a decision, verify, confirm completion (issues credentials), correct, replace, revoke, confirm ACR, record a deposit, decide a transfer, redeem or release credit, change a setting, assign or remove a role, deactivate an account, import a file, withdraw an enrolment, give or withdraw consent, pay, sign out with unsaved work.

**Dialog anatomy:**
- A short kicker naming the record.
- A title phrased as a question ("Confirm completion for 27 learners?").
- A body that says what will happen, what will not happen, and who is told.
- Fields the decision needs, such as a reason or meeting reference, with required ones marked.
- A footer with Cancel on the left and the action, named as a verb, on the right.

**Destructive actions** (revoke, deactivate, reject, release, withdraw) use the danger style. The record name must appear in the body, and a reason is required for the audit trail.

**Warnings that do not block** use the same dialog. They state the risk and ask the user to confirm. For example: "1 non-blocking comment is still open."

### 2.3 Feedback
- **Toast** after every completed action, top right, for 5 seconds. Text: what happened plus the next step ("Results verified. 27 module badges issued. Confirm completion next.").
- **Inline error** under the field, in plain language, keeping what the user entered (ir-forms). A toast summarises the count.
- **Errors from the system** appear inside the block that failed, with a reference code and Retry, and no technical detail (nfr-errors).
- **Long jobs** (issuing 500 credentials, importing 5,000 rows, large reports) show queued, running with progress, then done, and notify when finished (nfr-queue).
- **Empty state** keeps the frame and header and adds one sentence plus the first action.

### 2.4 Tables
- 12px grey sentence-case headers, rules between rows, no zebra striping. Numbers are right-aligned in tabular figures.
- **Pagination on every list that can exceed one page:**
  - "Rows per page" control with 5, 10, 25 or 50 rows; the default is 10.
  - A count reading "1–10 of 28".
  - "Page 1 of 3" with Previous and Next.
- Search box above long lists. Filters as selects in the same bar. Counts follow the filter.
- **Rows that open a record** are clickable across the whole row. Row actions sit at the right as text links, one per row, or as a kebab menu for more.
- **Bulk action** is one filled button above the table that names the count ("Verify 28 results").

### 2.5 Shell
- **Top bar (48px):**
  - Product name, which goes home.
  - Global search (staff).
  - Language switch EN | BM.
  - Help.
  - Notifications bell with an unread count.
  - Profile button showing avatar, name and post.
- **Notifications:** the bell opens a panel with the latest 5, "Mark all read" and "View all". The full page has filters by type and read state, with pagination. Preferences live on the profile.
  - **Mandatory notices cannot be switched off** (fr-ntprefs): decisions, credential actions, consent requests and enrolment changes.
  - **Approvers and lecturers can choose a daily digest** (fr-ntdigest).
- **Profile menu items:**
  - My profile.
  - Notification preferences.
  - Sign-in and sessions.
  - Help and guides.
  - Switch persona (prototype only).
  - Sign out, which asks for confirmation.
- **Profile page tabs:**
  - **Details:** read-only fields from SSO, plus editable language, contact phone and name format.
  - **Notifications:** preferences.
  - **Sign-in and sessions:** MFA status, active sessions, idle timeout.
  - **My personal data:** view the data and request a correction (sr-access).
- **Side navigation** sits on the page ground. The current page has a neutral grey fill; counts are shown at the right.
- **Session:**
  - A warning dialog 2 minutes before the idle timeout (default 30 minutes), with Stay signed in and Sign out.
  - A maintenance banner announced 48 hours ahead (nfr-maint).

### 2.6 Formats
- **Dates** DD/MM/YYYY; times as 24-hour Malaysia time (ir-dates, ir-time). **Money** as "RM 450.00" (nfr-currency).
- **Names** print in full, including bin, binti, a/l and a/p (nfr-names).
- **Status is never colour alone:** every tag carries its word (nfr-wcag).
- **Each status has a one-line explanation** where it first appears (nfr-plain).

### 2.7 Status vocabulary (PRD 8.3, SRS state diagrams)
| Record | Statuses |
|---|---|
| Proposal / version | Draft, Submitted, In review, Returned for amendment, Endorsed, Pending approval, Approved, Not approved, Superseded, Suspended, Terminated |
| Offering | Not ready, Ready, Open, Full, Running, Ended, Cancelled |
| Enrolment | Registered, Pending payment, Enrolled, Cancelled, Withdrawn, Completed, Not completed |
| Module / result | Not started, In progress, Provisional, Verified, Held (integrity), Achieved (badge) |
| Completion / credential | Complete, Issued, Corrected, Replaced, Revoked |
| QV | Not applied, Preparing, Ready, Submitted, Quality-Verified, Expired, Suspended, Withdrawn |
| ACR / deposit | ACR prepared, Verified, Consent given, Consent declined, Deposited, Correction needed |
| Credit | Available, Provisionally redeemed, Redeemed, Expired |
| Transfer | Submitted, Under evaluation, Approved, Partly approved, Not approved, More evidence needed |
| Stackable plan | Suitable, Needs review, Not yet suitable |
| CQI action | Open, In progress, Done (effectiveness pending), Closed |

### 2.8 Accessibility and language
- **Keyboard:** everything works by keyboard and Escape closes overlays.
- **Labels:** every field has a visible label; placeholders never replace labels.
- **Language:** every screen, notification and credential exists in BM and EN. The switch keeps the page and entered data. A bilingual field shows a marker when one language is missing.
- **Web first, responsive to 360px.** The prototype is drawn at desktop width. Participant screens must also reflow to a phone (nfr-mobile).

## 3. Roles and permissions

| Role | Signs in | Can | Cannot |
|---|---|---|---|
| Student P8 (Amir Hakimi) | SSO | Browse, register, pay (stub), withdraw, view own records, share credentials, give or decline consent, apply for transfer, view own plan | See anyone else's data |
| Lecturer P1 (Dr. Aina Zulkifli) | SSO + MFA | Propose, edit, respond to comments, create offerings, confirm readiness (lecturer part), enter results, submit for verification, write CQI, gather QV evidence, review colleagues' proposals (X1, after COI) | Review or decide their own proposal; be the only verifier of results they entered |
| Deputy dean P2 (Hakim Rahman, FTMK) | SSO + MFA | Assign reviewers, verify category, endorse or return, record the Senate decision, confirm readiness, verify results, confirm completion, correct, revoke on a recorded decision, record QV result, confirm CQI, confirm ACR, record deposits, decide transfers into FTMK degrees, redeem, map pathways, plans, periodic review, suspension | Decide outside FTMK scope; issue credentials by hand (the system issues them) |
| Senate P3 | Never | Decides in its meeting | Use MCMS |
| CODL S1/P5 (Farid Hashim) | SSO + MFA | Users and scoped roles, reference data, settings with history, templates, UCreds files, adapters, test mailbox, audit, reports, retention | Any academic decision (D-12): approve, verify, issue, ACR, deposit, transfer, module credit flag |
| Industry expert X2 (Ir. Siti Aminah) | SSO (limited) | Declare COI, comment on invited proposals | Rate, recommend or decide |
| Public X3 (Tan Mei Ling) | Never | Catalogue, pathways, FAQ, verify a credential | See results, IC number, email or matric number |

A person may hold several roles, each with its own scope (fr-multirole). The separation-of-duties checks run per record.

## 4. Screen inventory

The route is the prototype address (`#/route`). R1 = Release 1, R2 = Release 2 (shown as "Release 2" and disabled).

### 4.1 Common
| Route | Screen | Traces |
|---|---|---|
| `/` | Personas and prototype start | — |
| `signin`, `signin/mfa`, `signin/noaccount`, `signin/stub` | UTeM Microsoft sign-in, MFA step, applicant not recognised, test stub (test only) | fr-sso, fr-devidp, sr-mfa, A-06 |
| `profile` | Profile with 4 tabs | fr-profile, fr-ntprefs, sr-access |
| `notifications` | Notification centre | fr-ntevents, fr-ntlog |
| `help` | Help, guides per role, glossary of stages | ir-help |
| `search` | Search results across records in scope | nfr-search |
| `screens`, `flows` | Prototype index of screens and walk-through flows | — |
| `404`, `error` | Not found and system error with reference code | nfr-errors |

### 4.2 Public website (no sign-in)
| Route | Screen |
|---|---|
| `public/home` | Landing: search, the three categories, open offerings, how recognition works, pathways, verify |
| `public/catalogue` | Catalogue with filters (category, faculty, mode, language, start, fee, credit), sort and pagination |
| `public/offering/:id` | Offering detail: outcomes, modules and SLT, assessment, prerequisites, completion conditions, support, credential received, generated recognition statement, separate-stages notice, cohorts, fee, places, Register |
| `public/pathways` | Published stackable pathways, with the "no automatic degree" wording |
| `public/how` | How recognition works: badge, certificate, credential, ACR, ABC, transfer, each explained |
| `public/faq` | FAQ |
| `public/verify`, `public/verify/:code` | Verify by code or link. Results: valid, corrected, replaced, revoked, not found |

### 4.3 Student
| Route | Screen |
|---|---|
| `st/home` | My learning: in progress with module progress, waiting for verification, achieved, actions due |
| `st/catalogue` | Catalogue inside the app |
| `st/register/:cohort` | Register wizard: eligibility, acknowledgement and PDPA notice, dummy payment (success or failure), confirmation and access instructions |
| `st/enrolments`, `st/enrolment/:id` | Registrations and payments; module enrolments; withdraw; receipt |
| `st/record` | Achievement record (labelled "internal record, not an ACR") |
| `st/credentials`, `st/credential/:id` | Credentials; download PDF and signed JSON; share link and QR; revision history |
| `st/ledger`, `st/acr/:id` | Credit ledger (Available, Provisionally redeemed, Redeemed, Expired); ACR detail; consent: give, decline, postpone |
| `st/transfer`, `st/transfer/new`, `st/transfer/:id` | Applications; new application wizard (MCE and redeemed credit not selectable); status and decision; upload more evidence |
| `st/plan` | Stackable plan and published pathway |

### 4.4 Lecturer (and X1 reviewer)
| Route | Screen |
|---|---|
| `lec/home` | Needs you, and my micro-credentials |
| `lec/mc/:id` | Micro-credential overview: versions, cohorts, QV, CQI, history |
| `lec/proposals`, `lec/new` | Proposals and versions; new proposal category wizard (UMC / SAMC / MCE with the reason) |
| `lec/proposal/:id` | Appendix A workspace: basics, justification, outcomes, modules and SLT, assessment, alignment, credential design, delivery, co-developers, declaration; live checks; autosave; comments; submit |
| `lec/change/:id` | Change to an approved version: minor or material, impact, new version |
| `lec/reviews`, `lec/review/:id` | Review invitations; COI first, then rate items, then recommend (Support, Amend, Do not support) |
| `lec/cohorts`, `lec/cohort/:id`, `lec/offering/new`, `lec/readiness/:id` | Cohorts; results entry (provisional, integrity flag, submit for verification); create offering; lecturer's readiness items |
| `lec/cqi`, `lec/cqi/:id` | Quality review: indicators, issues, actions, effectiveness before close, change request |
| `lec/qv`, `lec/qv/:id` | QV evidence; "Ready to submit" gated by a completed cohort |
| `lec/reports` | Reports in scope |
| `ext/home`, `ext/review/:id` | Industry expert: invitations, COI, comments only |

### 4.5 Deputy dean
| Route | Screen |
|---|---|
| `dd/queue` | Task queue: everything waiting, by type, with age and overdue flag |
| `dd/proposals`, `dd/proposal/:id`, `dd/senate/:id`, `dd/pack` | Proposals; review summary with category verification, blocking comments, reviewer assignment, endorse or return; record Senate decision; decision pack (print) |
| `dd/offerings`, `dd/readiness/:id` | Offerings; confirm readiness (blocked until every item is done) |
| `dd/results`, `dd/verify/:id`, `dd/completion/:id` | Results; verify results (module badges; held results excluded); confirm completion (pre-issue checks, issue job progress, failures with reasons) |
| `dd/creds`, `dd/cred/:id` | Credential search; correct (new revision), replace, revoke (needs a recorded decision of the authorised body) |
| `dd/quality`, `dd/qv/:id`, `dd/cqi/:id`, `dd/review/:id`, `dd/suspend/:id` | QV status and results; CQI confirmation; periodic review; suspension or termination with transition plan |
| `dd/acr`, `dd/acr/:id`, `dd/deposit/:id` | ACR confirmation; deposit queue; export; record ABC reference; discrepancies |
| `dd/transfer`, `dd/transfer/:id` | Transfer cases; equivalence by outcome; decision (Approved, Partly approved, Not approved, More evidence needed); redemption |
| `dd/stack`, `dd/pathway/:id`, `dd/plan/:id` | Pathway mapping for the Senate; individual plans with the 30% and residency checks |
| `dd/reports`, `dd/trace` | Reports; traceability from approval to credit use |

### 4.6 CODL administrator
| Route | Screen |
|---|---|
| `codl/users`, `codl/user/:id` | Users; roles with scope, reason and effective date; deactivate or reactivate |
| `codl/refdata` | PTj, programmes, parent courses (form or CSV) |
| `codl/settings` | Settings with history, change dialog (reason, effective date); demo clock and maintenance banner (test only) |
| `codl/templates`, `codl/template/:id` | Bilingual templates with versions and preview |
| `codl/ucreds` | Export enrolments, import results, validation report, job progress |
| `codl/adapters`, `codl/mailbox` | Adapter mode, status and retry; test mailbox |
| `codl/audit`, `codl/reports`, `codl/retention` | Audit trail search and export; reports; retention, holds and disposal |

## 5. Flows to walk in the prototype

Each flow is in the prototype's Flows page with a step bar. Negative paths are flows of their own.

| ID | Flow | Actor |
|---|---|---|
| FL-01 | Sign in with SSO and MFA, land on the task page | Any staff |
| FL-02 | Propose a UMC: category, workspace, SLT 110 h blocked, fix to 120 h, submit | Lecturer |
| FL-03 | Review a colleague's proposal: COI, rate, recommend | Lecturer as X1 |
| FL-04 | Endorse or return a proposal; record the Senate decision | Deputy dean |
| FL-05 | Create an offering and confirm readiness; it appears in the catalogue | Lecturer, deputy dean |
| FL-06 | Find, register and pay (success); payment failure and retry | Student |
| FL-07 | Import UCreds results with a bad row; enter missing marks; submit for verification | CODL, lecturer |
| FL-08 | Verify results (badges), then confirm completion (system issues credentials) | Deputy dean |
| FL-09 | Share a credential; employer verifies; not found; revoked | Student, public |
| FL-10 | Correct a credential name (new revision) | Deputy dean |
| FL-11 | Consent to ABC deposit, confirm ACR, record deposit, see ledger | Student, deputy dean |
| FL-12 | Apply for credit transfer; decide by outcome; provisionally redeemed; second use blocked | Student, deputy dean |
| FL-13 | Stackable plan: 25% "Not yet suitable" and a suitable plan | Deputy dean |
| FL-14 | QV evidence, gate, record QV result; expiry removes claims | Lecturer, deputy dean |
| FL-15 | CQI: action closes only after the effectiveness check | Lecturer, deputy dean |
| FL-16 | Suspend an offering with a transition plan | Deputy dean |
| FL-17 | Assign a scoped role; change a setting with history | CODL |
| FL-18 | Trace a participant from approval to credit use; audit trail | Deputy dean, CODL |
| FL-19 | End-to-end journey (SRS A.5): Amir from catalogue to credit used | All |

## 6. Corrections to the v1 screens

| # | v1 problem | v2 rule |
|---|---|---|
| C1 | Verifying results issued credentials | Verify results gives module badges. Confirm completion is a separate step that triggers issue. |
| C2 | Public pages claimed SAMC credit and QV not valid for the cohort dates | Credit and QV shown only while verified and valid for the cohort; otherwise "credit after QV" or "QV renewal pending" |
| C3 | Deputy dean could revoke alone; "superseded" wording | Revoke needs the authorised body's decision reference; statuses Corrected, Replaced, Revoked |
| C4 | Review recommendation labels | Support, Amend, Do not support; COI first |
| C5 | Endorse allowed with open comments | Endorse blocked by open blocking comments; non-blocking ones warn |
| C6, C7 | Date and money formats | DD/MM/YYYY; RM 0.00 |
| C8 | "Admin cannot hold an academic role" | Removed. Multi-role is allowed; separation of duties is checked per record. |
| C9 | Public verification showed credit | Minimum fields only: name, credential, issuer, date, status |
| C10, C11 | Ledger words and stage mixing on the credential page | Credit statuses from 2.7; credential page links to the ledger instead of mixing stages |
| C12 | Transfer could approve a line with no outcome match | Approve disabled for that line, with the reason |
| C16, C17 | Missing transfer evidence; imported results not shown as provisional | Evidence list on the application; imported results labelled Provisional until verified |
| — | Phone-first student screens | Student screens are web first and reflow to phone |

## 7. Open questions this basis carries
- Who is the "advisor" for a stackable plan (US-48)? Assumed: the deputy dean of the degree's faculty.
- Is P1's scope the whole PTj or one micro-credential? Assumed: their own micro-credentials, plus invited reviews.
- Is DESIGN.md (Carbon with uLearn Navy) accepted as UTeM corporate identity (ir-identity)? Assumed yes for the prototype.
- OI-09 transfer limits, OI-11 ABC file format, OI-12 fees and refunds, OI-13 completion window: shown as settings marked TBD.
