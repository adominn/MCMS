# MCMS UI Screen Inventory: SRS Sections 3.1 to 3.17 (FR-A, FR-B, FR-C)

**Legend.** Keys without a tag are R1 (SHALL). R2 = SHOULD, R3 = MAY. Confirm = Y if the action is irreversible, submits, publishes, approves, revokes, deletes, issues, or changes a setting. "system" = automatic. "approver" = whichever role `approval.route.*` maps to. "authorised user" = a role the SRS does not name.

---

## 3.1 Identity, Roles and Access (M1)

**1. Screens**
- SSO sign-in and redirect; stub IdP login (dev/test only). All users.
- Profile (view; edit language, notification prefs, contact phone). All users.
- Language switch (BM/EN). All users.
- Account list and detail (search, deactivate, reactivate, merge). S1.
- Role assignment (role, scope, reason, effective date). S1.
- Approver delegation. Approver.
- Conflict-of-interest declaration. X1.
- Comment-only view of proposal. X2.
- Settings store (grouped editor, change history). S1.
- Templates manager (BM/EN, versioned). S1.
- Reference data (PTj, programmes, parent courses; form or CSV). S1.
- Adapter status dashboard (mode, last run, errors). S1, P5.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-sso | staff, participant | Sign in via UTeM SSO (OIDC assumed) | N | Session; profile exists | SSO failure message (TBD, OI-03) |
| fr-nosignup | system | No public sign-up page | – | Local account only on first SSO or applicant import | – |
| fr-firstlogin | system | Create profile from SSO claims; assign Participant role | N | Profile + Participant role | Staff/matric no. optional |
| fr-applicant | system / S1 | Recognise outside applicants (attribute or import) | N | Applicant flagged | OI-03 |
| fr-devidp | tester | Sign in as any test user/role | N | Test session | Must be impossible in prod |
| fr-session | all | Idle timeout; sign out | N | Session ended after `auth.idle_timeout_minutes` | – |
| fr-profile | all | Edit preferred language, notification prefs, contact phone | N | Saved | Name and IDs read-only |
| fr-lang | all | Choose BM or EN | N | Remembered across sessions | – |
| fr-idmerge (R2) | S1 | Merge two accounts of one person | Y | Linked records moved; audit entry | – |
| fr-rbac | all | Server-side RBAC on every action | – | Denied action | UI hiding is not enough |
| fr-multirole | S1 | Hold several roles, each with scope | Y (via roleassign) | – | – |
| fr-scope | S1 | Scope = whole Univ / PTj / MC | Y (via roleassign) | Users see only in-scope records | Out-of-scope hidden |
| fr-roleassign | S1 | Assign or revoke role | Y | Audit: role, scope, reason, effective date, actor | Reason and date required |
| fr-sod | system | Block self-review; the enterer cannot be the only verifier | – | Action blocked | Warning/block message |
| fr-adminlimits | S1 | S1 cannot approve, verify, issue, prepare ACR, decide transfer | – | Those actions not offered to S1 | Hide actions |
| fr-approverbody | S1 | Map each decision type to a role | Y | Routing changed; history kept | – |
| fr-coi | X1 | Declare conflict before review | Y | Reviewer removed; assigner notified | Must declare before opening |
| fr-sme | X2 | Read proposal; comment on competencies and relevance | N | Comment added | No decision controls shown |
| fr-delegate (R2) | approver | Delegate role for a period | Y | Delegation shown on each decision | Eligible users only |
| fr-settings | S1 | Edit any value in §3.1.3 table | Y | Value changed, no code change | – |
| fr-settinghist | system | Record old/new value, editor, reason, date | auto | Decisions use value in force on decision date | Reason required |
| fr-templates | S1 | Manage BM/EN templates | Y (publish) | New template version | – |
| fr-userlist | S1 | Search, view, deactivate, reactivate | Y (deactivate) | Account off; linked records kept | No deletion |
| fr-refdata | S1 | Maintain PTj, programmes, courses (form or CSV) | Y (import) | Reference data updated | Accreditation status field |
| fr-adapterstatus | S1, P5 | View adapter mode, last run, errors | N | – | – |

**3. Status values**
- No state diagram. Implied: user account Active / Deactivated (S1 toggles); role assignment Assigned / Revoked (S1).

**4. Notifications**
- fr-coi: conflict declared → person who assigned the reviewer.

**5. Settings**
- `auth.idle_timeout_minutes`, `approval.route.*`, and the whole §3.1.3 settings table: `slt.min_hours_samc`, `slt.min_hours_umc_whole`, `slt.min_hours_module_credit_model`, `credit.slt_hours_per_credit`, `qv.max_validity_years`, `qv.min_completed_cohorts`, `credit.abc_validity_years`, `stackable.min_home_credit_percent`, `stackable.residency_semesters`, `alerts.qv_expiry_days`, `alerts.credit_expiry_days`, `alerts.decision_overdue_days`, `umc.completion_window_months`, `retention.*`, `verification.public_fields`.

---

## 3.2 Category Determination and Proposal (M2, M3)

**1. Screens**
- Category wizard (origin, total SLT, credit/MQF sought). P1.
- Category result with plain reason; "Reclassify" button. P1.
- Proposal list (scope). P1, P2.
- Proposal workspace with tabs: basic info, justification, outcomes, learning design/SLT, assessment, alignment matrix, credential design, QV status, readiness A8, attachments, co-developers, IP declaration, validation panel. P1 (co-developers from same PTj).
- UMC parent and unbundling screen. P1.
- Category verification at endorsement. P2.
- Print preview (App A layout). P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-catwizard | P1 | Answer origin, SLT, credit/MQF sought | N | Category proposed | All answers required |
| fr-catsuggest | system | Suggest category with reason | N | Shown as proposed | – |
| fr-catumc | system | UTeM credit-course origin → UMC only | N | Other categories disabled | – |
| fr-catmce | system | Own offering below `slt.min_hours_samc` → MCE only | N | Marked non-credit | Warning |
| fr-catsamc | system | Own offering at/above threshold → SAMC; credit/MQF sought → QV planned | N | SAMC; QV planned recorded | – |
| fr-catconfirm | P2 | Verify category at endorsement | Y | Verified category drives rules | Stays "proposed" until verified |
| fr-umcnotsamc | system | Block UMC module proposed as SAMC just for separability | – | Blocked | Error |
| fr-mcereclass | P1 | "Reclassify" MCE to SAMC | Y | New version (Draft) | Only from MCE |
| fr-propnew | P1 | Create New / Revision / Re-offer for PTj in scope | N | Draft | Out-of-scope PTj blocked |
| fr-propbasic | P1 | Title BM/EN, PTj, lecturer, category, type, field, target group, mode, language, period | N | Saved | Required fields |
| fr-propjust | P1 | Need, evidence + attachments, added value | N | Saved | – |
| fr-proplo | P1 | Enter learning outcomes as items linked to content, activities, assessment, SLT | N | Saved | Each outcome linked |
| fr-propslt | system | Total SLT from rows; split; vs threshold | N | Shown | Below category threshold warns |
| fr-propassess | P1 | Assessment design, weightings, standard, integrity, feedback | N | Saved | Weightings must total 100% |
| fr-propalign | system | Alignment matrix | N | Shown | Warn outcome without assessment, and assessment without outcome |
| fr-propcred | P1 | Credential types, level, QV status (not applied / will apply / Quality-Verified / N/A), verification method | N | Saved | – |
| fr-propcredit | P1 | Enter credit and MQF as "proposed" only | N | Stored as proposed | Not shown in catalogue or credentials until confirmed |
| fr-propready | P1 | Readiness A8 items with evidence notes | N | Saved | – |
| fr-propattach | P1 | Upload PDF, Office, image | N | Attached | Configurable size limit (no key) |
| fr-propcollab | P1 | Add same-PTj co-developers | N | Co-developer can edit; edits attributed | Same PTj only |
| fr-propautosave | system | Autosave; resume later | N | Draft saved | – |
| fr-propip | P1 | Copyright/IP confirmation and licence note | Y (declaration) | Recorded | Required before submit |
| fr-propvalidate | system; P1 submits | Check vs category rules before submit | Y (submit) | Submitted | Blocking errors stop submit; warnings need written response |
| fr-propprint | P1, P2 | Print proposal document | N | PDF | – |
| fr-umcparent | P1 | Pick parent programme, course, model, module count from reference data | N | Stored | Not typed |
| fr-umcaccred | system | Parent programme not accredited | – | Submit blocked | Blocking |
| fr-umcmodel | P1 | Choose one unbundling model | N | Stored | One model only |
| fr-umcsltsum | system | Sum of module SLT ≠ parent course SLT | – | Submit blocked | Blocking |
| fr-umcwhole | system | Whole UMC < `slt.min_hours_umc_whole` or < 1 credit | – | Submit blocked | Blocking |
| fr-umccreditmodel | system | Credit-based model, any module < `slt.min_hours_module_credit_model` | – | Submit blocked | Blocking |
| fr-umcassessmodule | system | Separate-model: need ≥1 assessment module; assessment covers all parent CLOs | – | Submit blocked | Blocking |
| fr-umcclo | system | Module LO mapped to parent CLOs | – | – | Warn uncovered parent CLO |
| fr-umcmodcredit | academic-authority role (not S1) | Set "separately recognisable credit unit" Yes (default No) | Y | Flag set by recorded case decision | S1 cannot set |
| fr-umcparentchange (R2) | system | Parent course changed | auto | Linked UMCs flagged | – |

**3. Status values**
- Category: proposed → verified (P2) → fixed by approval. Values UMC, MCE, SAMC (SAMC with no credit claim = "NQ").
- MCE → SAMC only through a new version (Reclassify).
- Proposal type: New / Revision / Re-offer.
- Readiness item: Yes / Not yet / Not applicable.
- Module flag: No (default) / Yes.
- Proposal version lifecycle is in §3.3.

**4. Notifications**
- fr-umcparentchange (R2): parent course change → UMC lecturer and PTj.

**5. Settings**
- `slt.min_hours_samc`, `slt.min_hours_umc_whole`, `slt.min_hours_module_credit_model`.
- `approval.route.*` (academic authority for module credit flag; key not named in SRS).
- Attachment size limit (no key).

---

## 3.3 Review, Approval and Versioning (M4)

**1. Screens**
- Reviewer assignment (due date, scope). P2.
- Review form (item ratings, comments, recommendation). X1.
- Comment tracker (open / answered / resolved; blocking flag; response). P1, P2, X1.
- Amend and resubmit. P1.
- Endorsement and category verification. P2.
- Approval queue (age per item). Approver.
- Decision screen (decision, comments, external ref e.g. Senate meeting no.). Approver.
- Batch decision (one meeting ref) (R2). Approver.
- Review summary and decision pack (print). Approver, P2.
- Version history and change classification. P1, P2.
- Acknowledge minor change. P2.
- Side-by-side version compare (R2). P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-revassign | P2 | Assign reviewers with due date and scope | N | Review task; reviewer notified | No self-review; COI check |
| fr-revform | X1 | Rate items Satisfactory / Needs amendment, comment; UMC mapping evidence | Y | Review recorded | – |
| fr-revrec | X1 | Recommendation Support / Amend / Not support | Y | Feeds review summary | – |
| fr-revcomments | P1 responds; P2 tracks | Respond; mark open / answered / resolved | N | – | Blocking unresolved comment stops approval |
| fr-revcategory | P2 | Verify category at endorse | Y | Verified category stored separately; Endorsed | – |
| fr-revsummary | system | Review summary for approver | auto | Summary built | – |
| fr-wfstages | system | Three stages tracked | – | Stage progress | – |
| fr-wfrecord | approver / P2 | Record decision, role, date, comments, external ref | Y | Status change | External ref field |
| fr-wfversion | system | Approval binds to exact version | – | No carry-over | – |
| fr-wflock | system | Lock content on submit | – | Editing blocked | Unlock only on return or new version |
| fr-wfnotcredit | system | Approval shows no QV/ABC/transfer/stackable status | – | – | – |
| fr-wfqueue | approver | View queue with age | N | – | Overdue items reminded after `alerts.decision_overdue_days` |
| fr-wfbatch (R2) | approver | Several decisions in one action with one meeting ref | Y | Decisions recorded per item | One meeting ref |
| fr-wfpack | approver, P2 | Print decision pack per item | N | PDF | – |
| fr-verhistory | system | Version history (number, date, summary, decision, participant impact) | N | – | – |
| fr-chgclassify | P1; system | Classify minor / material | N | Class set | Auto-material for listed triggers |
| fr-chgmaterial | P1 | Material change → new version → review and approval | Y | New version Draft; not effective until approved | – |
| fr-chgminor | P1; P2 acknowledges | Description and impact note | Y | Logged to CQI | Note required |
| fr-chgqv | system | Material change to QV SAMC | auto | QV re-verification task | – |
| fr-verkeep | system | Referenced version never deleted | – | Delete disabled | – |
| fr-verbind | P2 | Record transition rule (finish old / move new / equivalence) | Y | Enrolments bound to version | Rule required when new version takes effect |
| fr-vernotify | system | Notify enrolled participants (change, date, rule) | auto | Notice sent | Mandatory, cannot switch off |
| fr-vercompare (R2) | P1, P2, approver | Side-by-side with changed fields highlighted | N | – | – |

**3. Status values** (`propstate` diagram)
- Draft, Submitted, In review, Returned for amendment (Amend), Endorsed by PTj, Not supported (end), Pending approval, Approved, Rejected (end), Superseded, Suspended, Terminated.
- Transitions:
  - Draft → Submitted (P1).
  - Submitted → In review (P2 assigns reviewers).
  - In review → Amend (amend requested; actor not stated).
  - Amend → Submitted (P1 resubmits).
  - In review → Endorsed (PTj endorses and verifies category).
  - In review → Not supported (PTj).
  - Endorsed → Pending approval (sent to approver).
  - Pending approval → Approved / Amend (return) / Rejected (approver).
  - Approved → Superseded (later version approved; system).
  - Approved → Suspended, Suspended → Approved (lifts), Approved → Terminated (authority).
  - Suspended → Terminated (no label in the diagram).
- Comment status: open / answered / resolved.
- Recommendation: Support / Amend / Not support.
- Item rating: Satisfactory / Needs amendment.
- Change class: minor / material.

**4. Notifications**
- proposal submitted → P2 (implied).
- reviewer assigned → reviewer.
- review due → reviewer.
- amendment requested → P1.
- decision made → P1.
- Pending decision overdue → approver (reminder).
- change affecting enrolled participants → participants.
- fr-coi (see §3.1).

**5. Settings**
- `alerts.decision_overdue_days`.
- `approval.route.*` (approver per decision type).

---

## 3.4 Offering, Catalogue and Readiness (M5)

**1. Screens**
- Offering (cohort) create and edit form. P1, P2.
- Readiness checklist (App B2–B7). P1, P2, support staff.
- Readiness confirmation (Ready / Ready with conditions / Not ready). P1, P2, support or system staff.
- Recheck for new cohort (pre-filled; hold banner). P1, P2.
- Public catalogue with search and filter. Public, participant.
- Catalogue entry detail. Public, participant.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-offcreate | P1 or P2 | Create cohort for an Approved version | N | Offering created (not public) | Only Approved versions selectable |
| fr-offapproved | system | Version not Approved | – | Blocked | Error |
| fr-offreadiness | P1 | Yes / Not yet / NA with evidence per B2–B7 | N | Saved | – |
| fr-offconfirm | P1, P2, support | Ready / Ready with conditions / Not ready | Y | Confirmation recorded | Conditions must be recorded |
| fr-offopen | system | Registration opens on PTj confirmation | N | Registration open | Not until Ready (or Ready with conditions recorded) |
| fr-offrecheck | P1, P2 | Repeat checklist per cohort (pre-filled) | Y | If material change: offering held until approved | Hold |
| fr-offqv | system | SAMC QV valid to cohort end date? | – | If not, QV claim, credit and MQF hidden | Warning to P1 |
| fr-catpublic | public | Browse open and upcoming offerings | N | – | Only open or upcoming listed |
| fr-catinfo | public, participant | View entry fields | N | – | QV, credit, MQF shown only if valid |
| fr-catsearch | public, participant | Filter by category, PTj, field, mode, language, start date, fee | N | – | – |
| fr-catwording | system | Recognition statements from templates; scan free text | – | Publish blocked if forbidden claim | Blocking |
| fr-catstages | system | Standard "separate stages" explanation for credit-potential offerings | – | Shown | – |
| fr-catmodular | system | Module structure and staged completion shown | – | Shown | – |
| fr-catlang | public | BM or EN | N | – | – |

**3. Status values**
- No diagram. Implied: offering open / upcoming; readiness item Yes / Not yet / NA; readiness Ready / Ready with conditions / Not ready; offering held (after material change); registration open / closed.

**4. Notifications**
- fr-ntevents "offering open" → recipients not specified.

**5. Settings**
- No keyed setting. QV validity from `qv.*` via the QV record.

---

## 3.5 Registration, Enrolment and Cohorts (M6)

**1. Screens**
- Offering detail and registration form (acknowledgement of info and recognition statements). P8.
- Eligibility result (reasons for ineligibility). P8.
- PDPA notice and acknowledgement (first registration). P8.
- Payment (dummy adapter: simulated success or failure, reference). P8.
- Fee waiver or sponsorship. P2 (R2).
- My enrolments and enrolment state. P8.
- Module status panel (staged completion). P8, P1, P2.
- Withdraw. P8.
- Waiting list (R2). P8.
- Group CSV enrolment (R2). P5.
- Cohort participant list (filter, export). P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-regopen | P8 | Register while window open and not full | Y | Registered | Closed or full blocks |
| fr-regelig | system | Check group and prerequisites; explain | – | Blocks registration | Reason per failed rule |
| fr-regack | P8 | Acknowledge info and recognition statements | Y | Acknowledged text version stored with enrolment | Blocks until acknowledged |
| fr-regpdpa | P8 | PDPA notice acknowledgement (first registration) | Y | Recorded | Blocks until acknowledged |
| fr-regpay | P8; system | Pay via dummy adapter | Y | Pending payment → Enrolled (success) or Cancelled (window end) | Failure message with reference |
| fr-regwaiver (R2) | P2 | Fee waiver or sponsorship with reason | Y | Registered → Enrolled | Reason required |
| fr-regstatus | system | Every state change time-stamped and attributed | auto | Per state diagram | – |
| fr-regmodule | system; P1, P2 | Per-module enrolment and completion with dates | auto | Kept separately | – |
| fr-regwithdraw | P8 | Withdraw before offering deadline | Y | Withdrawn; refunds outside MCMS | Blocked after deadline |
| fr-regwaitlist (R2) | system; P8 | Waiting list; release places in order | Y (accept place) | Place offered | Order not specified in detail |
| fr-regbulk (R2) | P5 | Group CSV enrolment with same checks | Y | Enrolments created | Per-row checks (implied) |
| fr-cohortlist | P1, P2 | View, filter, export cohort list | N | Export file | – |
| fr-regconfirm | system | Confirmation with access instructions | auto | Notification sent | – |

**3. Status values** (`enrolstate` diagram)
- Registered, Pending payment, Enrolled, Withdrawn, Completed, Not completed, Transferred (to new version), Cancelled (from pending payment).
- Transitions:
  - Registered → Pending payment (fee applies).
  - Registered → Enrolled (no fee or waiver).
  - Pending payment → Enrolled (payment confirmed).
  - Pending payment → Cancelled (window ends).
  - Enrolled → Withdrawn (participant).
  - Enrolled → Completed (PTj confirms).
  - Enrolled → Not completed (cohort closes).
  - Enrolled → Transferred (new version).
- Gap: no path from Pending payment to Withdrawn, although fr-regwithdraw does not say whether withdrawal is possible there.
- Module statuses: not defined.

**4. Notifications**
- Registration, payment, enrolment confirmed → participant.
- Transfer to new version → participant (fr-vernotify).
- Places released from waiting list → participant (implied).

**5. Settings**
- None keyed. Deadlines and capacity per offering.

---

## 3.6 Learning Platform Connection (M7)

**1. Screens**
- Enrolment export to UCreds CSV. P5.
- Results import upload and validation report (file-level error or downloadable row report). P5.
- Staged results to verify (provisional). P2.
- Badge import and matching (flagged unmatched). P5, P2.
- Import and export log. P5, S1.
- Adapter status (§3.1). S1, P5.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-lpadapter | system | All exchange through adapter interface | – | No UI | Architectural |
| fr-lpexport | P5 | Export enrolment CSV (UCreds template) | N | File logged | – |
| fr-lpimport | P5 | Upload progress, module, results, badge CSV | Y | Results staged provisional | Template version check |
| fr-lpvalidate | system | Validate format, row match, score range, duplicates | – | File-level error rejects all; valid rows staged | Row report downloadable |
| fr-lpstaged | system | Store as provisional | – | Task to P2; no achievement yet | No achievement or badge until verified |
| fr-lpbadge | P5; system | Import badge ID, link, date; match to verified achievement | – | Unmatched flagged | Badge without verified achievement |
| fr-lplog | system | Log file name, hash, counts, result, user, time; keep file | auto | Log entry | Kept for retention |
| fr-lplive (R2) | system; P5 | Live connector when method agreed | – | – | OI-04 |
| fr-lpsync (R3) | system | Near real-time sync | – | – | – |

**3. Status values**
- Staged result: provisional → verified (P2) or returned.
- Import outcome: accepted / rejected (file-level) / partly accepted (row errors). Implied.
- Badge: matched / flagged. Implied.

**4. Notifications**
- Verification task → P2 (diagram).
- Adapter failures → P5 (fr-ihretry, §3.17).

**5. Settings**
- None keyed. Adapter mode (fr-adapterstatus).

---

## 3.7 Assessment and Achievement Verification (M8)

**1. Screens**
- Assessment plan (read-only from approved version). P1, P2.
- Results grid (per participant and component). P1.
- Integrity flag form. P1.
- Verification queue with cohort or module pass-rate summary and outstanding flags. P2.
- Result change form (reason; previous value kept). P1 requests, P2 approves.
- Appeal or query and case tracker. Participant, P2.
- My results and feedback. Participant.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-asplan | P1 (read) | Plan from approved version | – | – | Changes only via §3.3.3 |
| fr-asentry | P1 | Enter results per participant and component | N | Totals and pass/fail computed | Against pass standard |
| fr-asnoattend | system | Attendance-only pass or completion | – | Blocked | Blocking |
| fr-asintegrity | P1 | Record identity and integrity mechanism; flag case | Y (flag) | Results under case held | Held results cannot be verified |
| fr-asverify | P2 | Verify results (verifier, date, identity evidence link) | Y | Achievements created | Open case blocks |
| fr-asbulkverify | P2 | Verify whole cohort or module | Y | Verified in bulk | Outstanding flags shown; open cases block |
| fr-aschange | P2 approves | Change verified result: reason, approval | Y | Audit; if credential issued, credential correction task | Reason required; old value kept |
| fr-asmodule | system | Module achievement when all LOs and assessments met and verified | auto | Module achievement recorded | – |
| fr-asappeal (R2) | participant | Raise query or appeal | Y | Case opened; linked to result change | – |
| fr-asfeedback | participant | View verified results and released feedback | N | – | Verified only |

**3. Status values**
- Result: entered or imported (provisional) → verified, or returned. Held if integrity case open. Change pending approval.
- Module achievement: recorded or not.
- Appeal: open → decided.
- Implied, no diagram.

**4. Notifications**
- Results verified → participant.
- Credential correction → participant (§3.9.4).

**5. Settings**
- None keyed. Pass standard comes from approved version.

---

## 3.8 Achievement Record (M9)

**1. Screens**
- Achievement Record (modules, results, badges, MC status, credentials). Labelled "Internal record, not ACR". Participant, P1, P2.
- UMC completion confirmation (auto summary, P2 confirms). P2.
- Completion window tracker. Participant.
- Staff list of records in offerings. P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-arkeep | system | One AR per participant per MC | auto | Holds modules, results, badges, status, credentials | – |
| fr-arlabel | system | Label on every screen and export | – | – | Mandatory |
| fr-arumc | system calc; P2 confirms | UMC complete when all modules and requirements met | Y | Complete (confirmed by PTj); enables certificate and ACR eligibility | Blocked if any requirement missing |
| fr-arnomix | system | No combining badges or modules from different UMCs | – | Blocked; equivalence only by recorded decision | Reason shown |
| fr-arwindow | system | Track completion window | auto | Reminder before end | `umc.completion_window_months` (TBD) |
| fr-arsamc | P2 confirms | SAMC or MCE complete (LOs, SLT, assessment, conditions) | Y | Completed | Blocked until all met |
| fr-arview | participant | View own AR incl. cross-period progress | N | – | – |
| fr-arstaff | P1, P2 | View ARs of participants in their offerings | N | – | Scope |

**3. Status values**
- Module: verified / not. UMC: in progress → complete (pending or confirmed by P2). MC: complete (confirmed) or not. Credentials awarded. No diagram; implied.

**4. Notifications**
- Completion window reminder → participant (before window ends).

**5. Settings**
- `umc.completion_window_months` (TBD).

---

## 3.9 Credential Issuance and Verification (M10)

**1. Screens**
- Pre-issuance checks (App E8 pass or fail per check). P2, system.
- Issuance queue (single or cohort) with failures and reasons. P2.
- Credential template preview (BM/EN). S1 manages; P2 views.
- Participant wallet (view, download JSON and PDF, copy share link; add to profile R2; export R3). Participant.
- Public verification page (ID, URL or QR; signature upload R2; neutral not-found). Public (X3).
- Credential detail with correction, replacement and revocation history. P2 (correct), authorised body (revoke).
- Revocation decision form. Authorised body.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-crcheck | system | Run E8 checks before issue | – | Issue blocked if any fails | List failing check |
| fr-crbadge | system | Digital Badge per verified module (when design includes badges and UCreds does not issue) | auto | Badge issued with metadata and link | Never credit |
| fr-crbadgetext | system | Badge not described as credit | – | – | Copy blocked |
| fr-crcert | P2 verify → system | Certificate after whole MC complete | Y (P2 verify triggers auto-issue) | Certificate issued | Only when whole MC complete |
| fr-crdc | system | Digital Credential with E4 metadata | auto | Issued | Credit or MQF only when verified; QV only after QV obtained |
| fr-crformat | system | Signed JSON and PDF; UTeM key; no blockchain | – | Downloadable | Signing-key config not in SRS |
| fr-crgenerated | system | Credit, MQF, QV wording from verified records only | – | – | Fields not editable |
| fr-crbatch | P2 verify → system | Auto-issue for participant or cohort | Y | Issued; failures listed to P2 with reasons | Failed participants listed |
| fr-crlang | system | Certificates and DCs in BM and EN | – | – | – |
| fr-crid | system | Unique non-guessable ID; QR URL | auto | – | – |
| fr-crwallet | participant | View, download, copy share link | N | – | – |
| fr-crshare (R2) | participant | Add to professional profile | Y | Shared | – |
| fr-crwalletint (R3) | participant | Export to external wallet | Y | Exported | – |
| fr-wdtemplate | system | Recognition statement from template for category and status | – | – | Table §3.9.2 |
| fr-wdblock | system | Block text stating unsupported credit, MQF or QV | – | Publish or issue blocked | Blocking |
| fr-wdqvlapse | system | After QV lapse, no QV claim on new credentials | auto | Earlier credentials show status valid at issue date | – |
| fr-vfpage | public | Verify by ID, URL or QR | N | Minimal info shown | – |
| fr-vffields | public | Show only permitted fields | – | – | Never ID numbers, matric, email, results |
| fr-vfsignature (R2) | public | Upload DC file; check signature | N | Result shown | – |
| fr-vflog | system | Log time, ID, outcome; IP only | auto | Log | – |
| fr-vfnotfound | public | Neutral not-found | – | – | Must not reveal similar IDs |
| fr-crcorrect | P2 (owning PTj) | Correct confirmed error | Y | Corrected (new revision); participant notified | Reason required; original kept |
| fr-crreplace | P2 (implied) | New credential with new ID linked to old | Y | Old → Replaced; old shows new ID | – |
| fr-crrevoke | authorised body | Revoke by recorded decision | Y | Revoked with date | Stated reason required |
| fr-crnoerase | system | No deletion | – | Delete disabled | – |
| fr-crnotify | system | Notify participant of correction, replacement, revocation | auto | Notice sent | Mandatory |

**3. Status values** (`credstate` diagram)
- Issued, Corrected, Replaced, Revoked.
- Transitions:
  - (start) → Issued (P2 confirms, checks pass, system issues).
  - Issued → Corrected (confirmed error).
  - Corrected → Corrected (further correction).
  - Issued → Replaced (new ID).
  - Corrected → Replaced.
  - Issued → Revoked (authorised body).
  - Corrected → Revoked.
  - Replaced → end; Revoked → end.
- Verification shows valid / corrected / replaced / revoked. "Valid" is not a diagram state name; it presumably maps to Issued.

**4. Notifications**
- Issued, corrected, replaced, revoked → participant (fr-ntevents, fr-crnotify).
- fr-crbatch failures → P2 (list, not a notification).

**5. Settings**
- `verification.public_fields`.

---

## 3.10 Quality Verification for SAMC (M11)

**1. Screens**
- QV status panel per SAMC (state). P1, P2.
- Eligibility checklist (D2 conditions; gate for "Ready to submit"). P1.
- Evidence checklist (D3–D7; Available / Not yet / NA; file ref; notes). P1.
- Auto-linked evidence (LOs, SLT, assessment, cohort results, feedback, CQI, versions, credential design). P1.
- Material-change review per component (D8). P1.
- Readiness decision (D9) and P2 confirmation (D10). P1, P2.
- Submission package generator. Staff.
- Submission record (date, QVC/MQA ref, submitter). Staff.
- QV decision form (verified or not, ref, credit, MQF, start, expiry, conditions, document). Staff.
- QV expiry alerts and lapse task list. P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-qvsamc | system | QV functions only for confirmed SAMC | – | Tab hidden for other categories | – |
| fr-qveligible | P1 | Set "Ready to submit" | Y | Preparing → Ready | Blocks if not stand-alone, SLT < `slt.min_hours_samc`, provider unregistered, < `qv.min_completed_cohorts` cohorts completed with evidence, or unjustified proposed credit or MQF |
| fr-qvevidence | P1 | Evidence checklist | N | Saved | – |
| fr-qvautolink | system | Link existing MCMS evidence | auto | Items linked | – |
| fr-qvchange | P1 | Material change review per component | Y | Components flagged for internal review or QVC/MQA referral | – |
| fr-qvreadiness | P1 records; P2 confirms | Ready / Ready with minor corrections / Not ready; missing evidence; target date | Y | Confirmed readiness | Missing critical evidence listed |
| fr-qvpackage | staff | Generate evidence index, summary, compressed evidence | N | Package file | – |
| fr-qvsubmit | staff | Record submission date, ref, submitter | Y | Ready → Submitted | Requires Ready |
| fr-qvresult | staff | Record verified or not; ref; credit; MQF; start; expiry; conditions; attach document | Y | Submitted → Verified or Not verified; values become sole source | Expiry > `qv.max_validity_years` after start blocked |
| fr-qvsource | system | QV record is sole source for catalogue, credentials, ACR | – | – | – |
| fr-qvalerts | system | Alert P1 and P2 before expiry | auto | Alert | `alerts.qv_expiry_days` (180, 90, 30) |
| fr-qvlapse | system | Expiry, suspension or withdrawal: stop QV claims, stop new ACR eligibility, create PTj task | auto | Status change; task | – |
| fr-qvnotransfer | system | QV never implies transfer, admission or stackable status | – | – | – |
| fr-qvportal (R3) | staff | Electronic submission when interface exists | Y | – | Not in R1 |

**3. Status values** (`qvstate` diagram)
- Not applied, Preparing evidence, Ready to submit, Submitted to QVC/MQA, Quality-Verified, Not verified, Re-verification required, Expired, Suspended, Withdrawn (Withdrawn appears in transitions; no state label).
- Transitions:
  - Not applied → Preparing (PTj decides).
  - Preparing → Ready (internal readiness confirmed).
  - Ready → Submitted (staff).
  - Submitted → Verified (QV obtained) or Not verified.
  - Not verified → Preparing (address findings).
  - Verified → Re-verification required (material change).
  - Re-verification required → Submitted.
  - Verified → Expired (validity end).
  - Verified → Suspended (QVC/MQA).
  - Verified → Withdrawn (QVC/MQA or UTeM).
  - Suspended → Verified (reinstated).
  - Expired → Preparing (renewal).
- Gap: no transition from Suspended to Withdrawn.
- Evidence: Available / Not yet / NA.

**4. Notifications**
- QV expiry alert → P1, P2.
- QV lapse → PTj task (P2).

**5. Settings**
- `qv.max_validity_years`, `qv.min_completed_cohorts`, `slt.min_hours_samc`, `alerts.qv_expiry_days`.

---

## 3.11 ACR and ABC Deposit (M12)

**1. Screens**
- ACR eligibility checks (F2). P2, system.
- ACR preview and confirmation (read-only fields). P2.
- Consent request (wording version; decline or postpone). Participant.
- Deposit queue. P2.
- Deposit export (format TBD, OI-11). P2.
- Deposit recording form (date, ABC ref, credits, status). P2.
- Credit ledger (per ACR; total; note). Participant.
- External credit record entry (R2). Staff.
- ACR list and detail (labelled "Academic Credit Record"). Participant, P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-acrelig | system | ACR only for accredited UMC or QV-valid SAMC | – | ACR allowed | Blocked for MCE, SAMC without QV, module, badge, certificate |
| fr-acrchecks | system | Run and record F2 checks | auto | Results recorded | Failing check blocks |
| fr-acrprep | system | Generate ACR from verified records (F3 fields) | auto | ACR generated | Fields not typed |
| fr-acrverify | P2 | Confirm generated ACR | Y | Eligible for deposit | – |
| fr-acrseparate | system | ACR is a separate record type, labelled | – | – | Label everywhere |
| fr-acrmulti | system | Several ACRs per participant | – | – | Own identity each |
| fr-acrnocombine | system | No combining credits or modules across ACRs without recorded equivalence | – | Blocked | – |
| fr-acrexternal (R2) | staff | Record external credit (marked external; provider; QV or accreditation; credit; MQF; evidence) | Y | External record created | – |
| fr-dpconsent | participant | Explicit consent (date, wording version, channel) | Y | ACR enters deposit queue | No consent → not in queue |
| fr-dpdecline | participant | Decline or postpone | Y | ACR kept; deposit possible later | – |
| fr-dpqueue | P2 | View consented, confirmed ACRs | N | – | – |
| fr-dpexport | P2 | Export deposit data (agreed format TBD) | N | File | OI-11 |
| fr-dprecord | P2 | Record deposit date, ABC ref, credits, status, recorder | Y | Credit Available; expiry set; participant notified | Required fields |
| fr-dpnottransfer | system | Deposit is storage only | – | Notices say so | Never shown as transfer |
| fr-dponlyacr | system | Only ACR selectable for deposit | – | – | Badges, certificates, DCs, module records not selectable |
| fr-dplive (R3) | system; P2 | Live ABC deposit | – | – | Not in R1 |
| fr-cvexpiry | system | Expiry = deposit + `credit.abc_validity_years`; shown to participant | auto | Expiry shown | – |
| fr-cvalert | system | Alerts before credit expiry | auto | Alert | `alerts.credit_expiry_days` (365, 90) |
| fr-cvexpired | system | Expired for transfer; ACR remains; expired transfer flagged for re-evaluation | auto | Available → Expired | – |
| fr-cvledger | participant | View ledger per ACR with total and note | N | – | Note: credits not automatically accepted |
| fr-cvretain | authorised user | Record retention or disposal direction | Y | Recorded; carried out by authorised user | Retained indefinitely by default |

**3. Status values**
- Deposit status (fr-dprecord): Successful, In process, Correction needed.
- Ledger credit status: Available, Provisionally Redeemed, Redeemed, Expired (see §3.13).
- ACR lifecycle: generated → confirmed → in deposit queue → deposited (implied).
- Consent: given, declined, postponed (implied).

**4. Notifications**
- Consent requested → participant.
- Deposit recorded → participant ("not a credit transfer").
- Credit expiry alert → participant.

**5. Settings**
- `credit.abc_validity_years`, `alerts.credit_expiry_days`.

---

## 3.12 Credit Transfer (M13)

**1. Screens**
- Transfer application (F6 fields; document upload). Participant or staff.
- Record validity check (authenticity, ABC status, QV or accreditation). Evaluator.
- Equivalence evaluation (F7 criteria; finding and result each). P2 of target faculty.
- Limit and non-transferable warnings. Decision-maker.
- Academic decision form (F8). Transfer decision authority.
- Application and decision trace. Participant, P2.
- Equivalence precedent library (R2). P2.
- Programme admission record (separate). Authorised user.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-ctapply | participant or staff | Submit application (F6) | Y | Application under evaluation | MCE refused; conflict block (fr-rdconflict) |
| fr-ctsource | system | Accept only UTeM UMC, other eligible UMC, QV SAMC with verifiable status | – | MCE application refused with explanation | Refusal reason |
| fr-ctavailable | system | Only Available credit selectable; Expired only with re-evaluation flag | – | Expired flagged | Redeemed or Provisionally Redeemed not selectable |
| fr-ctvalidity | P2 (evaluator) | Record validity check | Y | Valid branch or Not approved | – |
| fr-ctequiv | P2 (target faculty) | Complete F7 criteria (finding and result each) | Y | Evaluation saved | Each criterion needs finding and result |
| fr-ctloequiv | system | Block Approved where LO criterion Not equivalent; credit or SLT alone not evidence | – | Decision blocked | Blocking |
| fr-ctlimits | system | Check programme transfer limit and non-transferable components | – | – | Warns decision-maker (no block stated); OI-09 |
| fr-ctpartial | system | Partly completed UMC or badge set not full credit unless separately recognisable flag | – | Blocked | Blocking |
| fr-ctdecision | transfer decision authority | Approved / Partly approved / Not approved / More evidence needed; credits; component; date; authority; conditions | Y | Approved → Provisionally Redeemed; More evidence → back to application; Not approved → no change | Required fields; limit warnings shown |
| fr-ctauthority | system | Only configured authority can decide | – | Others denied | – |
| fr-ctreject | system | Not approved changes nothing in ACR, badges, credentials, credit status | – | No change | – |
| fr-cttrace | system | Trace to source, ACR, evaluation, approval, programme record | – | – | – |
| fr-ctversion | system | Record curriculum and MC version; flag earlier equivalence on material change | auto | Flag for review | – |
| fr-ctadmission | authorised user | Record admission separately; approval does not imply admission | Y | Separate admission fact | Not implied by transfer |
| fr-ctprecedent (R2) | P2 | Consult library of approved mappings | N | – | Precedent does not replace decision |

**3. Status values**
- Decision outcomes: Approved, Partly approved, Not approved, More evidence needed.
- Equivalence results per criterion: finding and result (values not defined).
- No application state diagram. Implied: submitted → validity check → equivalence → academic decision.

**4. Notifications**
- Transfer decision → participant.

**5. Settings**
- Transfer limit and non-transferable components (programme settings, TBD, OI-09; no key).
- `approval.route.*` for individual transfer case (decision type named in fr-approverbody).

---

## 3.13 Single Redemption of Credit (M12)

**1. Screens**
- Credit status per credit (linked programme, evidence reference, ABC sync flag). P2, participant (view).
- Programme completion or non-completion recording form. Authorised user.
- Conflict warning on application. Participant, staff.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-rdprov | system | Transfer approved → credits Provisionally Redeemed, linked to programme and record | auto | Available → Provisional | – |
| fr-rdfinal | authorised user (manual R1) | Record programme completion | Y | Provisional → Redeemed | Evidence reference required |
| fr-rdrelease | authorised user (manual) | Record withdrawal or non-completion | Y | Provisional → Available (if valid) or Expired | Subject to ABC rules; evidence reference |
| fr-rdnoreuse | system | Redeemed credit not selectable elsewhere | – | Explanation shown | Blocking |
| fr-rdconflict | system | Block second active application using same credits | – | Blocked | Blocking |
| fr-rdkeep | system | ACR, badges, credentials never deleted or altered | – | – | – |
| fr-rdsource | authorised user | Manual recording with evidence reference | Y | Logged | Evidence required |
| fr-rdabcsync | authorised user | Record whether ABC updated (manual R1): date, reference | Y | ABC sync recorded | – |

**3. Status values** (`redemption` diagram)
- Available, Provisionally Redeemed, Redeemed, Expired.
- Transitions:
  - (start) → Available (ACR deposited).
  - Available → Provisional (transfer approved).
  - Provisional → Redeemed (programme completed, qualification awarded).
  - Provisional → Available (not completed, still valid).
  - Provisional → Expired (not completed, validity ended).
  - Available → Expired (10 years after deposit).
  - Redeemed → end.

**4. Notifications**
- None stated for redemption changes.

**5. Settings**
- `credit.abc_validity_years`.

---

## 3.14 Stackable Degree Pathway (M13)

**1. Screens**
- Pathway mapping editor (G1 programme info; mapping table; PLO matrix G4; BoK matrix G5). P1, P2.
- Mapping approval and publish. Stackable-mapping approver.
- Individual stackable plan (credit share, residency, remaining requirements, summary, decision). P1, P2.
- Plan decision confirmation (G11). P1, P2.
- Participant pathway info page. Participant.
- Gap and coverage warnings. P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-spdefine | P1 or P2 | Define pathway mapping (G1 fields) | N | Draft | Required fields |
| fr-spmap | P1 or P2 | Map each source to target component; equivalence High / Partial / None; preliminary Suitable / Needs review / Not suitable | N | Draft | – |
| fr-spcreditbearing | system | Only credit-bearing credentials mappable | – | MCE blocked | Blocking |
| fr-spapprove | stackable-mapping approver | Approve before publish | Y | Approved; published | Must be approved to publish |
| fr-spnotransfer | system | Approved mapping transfers no credit | – | Notice shown | Each credit still via §3.12 |
| fr-spplo (R2) | P1 or P2 | PLO matrix (G4) and BoK matrix (G5) | N | – | Warn on uncovered PLO or domain |
| fr-spcurrchange | system | Target curriculum changed materially | auto | Mapping flagged for review | – |
| fr-spplan | P1 or P2 | Create individual plan (G2, G6–G10) | N | Plan draft | – |
| fr-sp30 | system | Share of credit earned at UTeM; below `stackable.min_home_credit_percent` → not meeting | – | Plan marked not meeting | State that 30% does not mean 70% transferable |
| fr-spresidency | P1 or P2 | Residency semester and delivery method | N | ODL-scope flag for review | ≥ `stackable.residency_semesters` for conventional |
| fr-spremain | P1 or P2 | Remaining requirements with status and planned completion | N | – | – |
| fr-spdecision | P1 or P2 | Suitable to proceed / Suitable with conditions / Not yet suitable / Not suitable; conditions; follow-up | Y | Decision recorded | See fr-spadmit |
| fr-spadmit | system | "Suitable to proceed" needs admission recorded | – | Blocked without admission | Blocking |
| fr-spaward | authorised user (from programme records) | Record award after admission and requirements | Y | Award recorded (MCMS does not award) | Requires admission and requirements |
| fr-spinfo | participant | See accurate pathway info | N | – | No "X credentials leads to degree" wording |
| fr-spsis (R3) | system | Read admission, enrolment, graduation via SIS adapter | – | Status imported | OI-10 |

**3. Status values**
- Mapping equivalence: High / Partial / None. Preliminary decision: Suitable / Needs review / Not suitable.
- Plan decision: Suitable to proceed / Suitable with conditions / Not yet suitable / Not suitable.
- Pathway mapping: draft → approved → published (implied; no diagram).
- Admission: separate fact.

**4. Notifications**
- None explicitly stated. Curriculum change → mapping flagged for review (internal).

**5. Settings**
- `stackable.min_home_credit_percent` (30%), `stackable.residency_semesters` (1).

---

## 3.15 Monitoring and CQI (M14)

**1. Screens**
- Cohort indicator dashboard vs targets. P1, P2.
- Learning-outcome attainment table. P1, P2.
- Feedback questionnaire (configurable) and summary. Participant (respond); builder owner not specified.
- Modular progress monitor. P1, P2.
- Quality domain checklist (C5). P1.
- CQI record (issue, root cause, impact, priority; action plan; effectiveness; confirmation). P1, P2.
- Periodic review form and reminders. P1, P2.
- Suspension or termination decision with transition plan. Configured authority.
- Dashboard (indicators, open CQI actions, QV expiries, periodic reviews) (R2). P1, P2.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-mnindicators | system | Cohort indicators (App C2) vs targets | auto | Shown | Target per offering |
| fr-mnlo | system | LO attainment per outcome | auto | Shown | – |
| fr-mnfeedback | participant | Post-completion questionnaire (configurable) | Y | Responses stored | Facilitator and industry feedback SHOULD |
| fr-mnmodular | system; P1, P2 | Progress across modules and periods | – | Shown | – |
| fr-mndomains | P1 | Domains Satisfactory / Needs action with evidence | N | Saved | – |
| fr-mndash (R2) | P1, P2 | Dashboard | N | – | – |
| fr-cqiissue | P1 | Issue with evidence, root cause, impact, priority | N | Issue recorded | – |
| fr-cqiaction | P1 | Action plan: owner, due, success indicator, status | N | Action recorded | – |
| fr-cqieffect | P1 evaluates; P2 confirms | Effective / Partly effective / Not effective | Y (close) | Closed, or carried to next cycle | Cannot close without evaluation |
| fr-cqitrace | system | Chain issue → evidence → analysis → action → implementation → effectiveness | auto | – | – |
| fr-cqichange | system; P1 | CQI action affecting LO, SLT, credit, MQF, QV, core content, assessment, mode, credential or partner | Y | Change request (§3.3.3) | – |
| fr-cqisign | P1 prepares; P2 confirms | Confirm CQI record (C10) | Y | Confirmed | – |
| fr-prschedule | system | Periodic review date; remind lecturer and PTj | auto | Reminder | – |
| fr-proutcome | P1 or P2 | Continue / Continue with minor CQI / Content or assessment review / Review and re-approval / Consider suspension or termination | Y | Routes to change flow or suspension | – |
| fr-prsuspend | configured authority | Decide suspension or termination | Y | Suspended or Terminated | List active participants; transition plan required before final |
| fr-prnotify | system | Notify affected participants (status, options, period, credential or recognition impact) | auto | Notice sent | Mandatory |
| fr-prkeep | system | Suspension does not cancel achievements, credentials or ACR | – | – | – |

**3. Status values**
- CQI priority: High / Medium / Low.
- Action: Not started / In progress / Done.
- Effectiveness: Effective / Partly effective / Not effective.
- Domain: Satisfactory / Needs action.
- Periodic outcome: five options (fr-proutcome).
- Offering version: Suspended, Terminated (§3.3).

**4. Notifications**
- CQI action due → lecturer (implied).
- Periodic review due → lecturer and PTj.
- Offering status or transition → affected participants (fr-prnotify).

**5. Settings**
- None keyed. Indicator targets are per offering.

---

## 3.16 Records, Audit Trail and Reporting (M15)

**1. Screens**
- Audit trail viewer (search within scope). Authorised users.
- Correction form (original, reason, action, approver or executor, date). Party with authority; P2.
- Discrepancy log. Authorised user.
- Traceability chain (per participant and MC). Authorised users.
- Cohort minimum records (H5). P1, P2.
- Reports (scope filter; CSV, Excel, PDF export). Authorised users.
- Retention and disposal approval; hold. S1 (settings), approver, authorised user.
- Analytics dashboard (R3).

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-auditlog | system | Append-only entries for listed events | auto | Entry written | Immutable |
| fr-auditview | authorised users | Search and view audit in scope | N | – | Scope |
| fr-correct | party with authority | Correct record: original, reason, action, approver, date | Y | Logged; participant notified if credential, ACR, deposit or credit affected; linked records updated | Check against source record |
| fr-mismatch | authorised user (P2 implied) | Log and resolve discrepancy | Y | Resolved via correction | – |
| fr-trace | authorised users | Full chain view | N | – | Scope |
| fr-cohortrec | P1, P2 | Show H5 records and whether present | N | – | – |
| fr-reports | authorised users | Standard reports (listed in SRS) | N | – | Scope filter |
| fr-export | authorised users | CSV, Excel, PDF | N | File | – |
| fr-retention | S1 (setting); approver (disposal) | Set retention; approve and log disposal | Y | Disposal logged | Only after period and not held |
| fr-hold (R2) | authorised user | Hold stops disposal | Y | Hold set | – |
| fr-analytics (R3) | authorised users | Trend dashboards | N | – | – |

**3. Status values**
- Discrepancy: open / resolved (implied). Hold: on / off (implied).

**4. Notifications**
- Correction affecting credential, ACR, deposit or credit → participant.

**5. Settings**
- `retention.*` per record category (TBD; A-08, OI-07).

---

## 3.17 Notifications and Integration Hub (M16)

**1. Screens**
- Notification preferences (optional on or off; mandatory shown locked). All users.
- In-system notification inbox. All users.
- Email digest preference (R2). Approvers, lecturers.
- Notification log (recipient, channel, time, status). Admin (role not named).
- Template manager (§3.1). S1.
- Adapter registry (stub / manual / live). S1, P5.
- Failed-call queue for resolution. P5.

**2. Actions**

| FR key | Actor | Action | Confirm | After | Blocking / warnings |
|---|---|---|---|---|---|
| fr-ntevents | system | Send in-system and email for listed events | auto | Notification created | – |
| fr-ntlang | system | Send in recipient's language | auto | – | Managed templates |
| fr-ntprefs | all users | Switch off optional notifications | N | Preference saved | Mandatory items cannot be switched off |
| fr-ntlog | system | Log recipient, channel, time, status | auto | Log entry | – |
| fr-ntdigest (R2) | approvers, lecturers | Daily digest | N | Digest sent | – |
| fr-ihregistry | S1, P5 | Register each adapter with mode | N | – | Stub / manual / live |
| fr-ihretry | system | Retry with increasing delay; list still-failed for P5 | auto | Failed list for P5 | Must not block the business process |

**3. Status values**
- Adapter mode: stub / manual / live.
- Delivery status (implied from log): delivered / failed.
- Retry state (implied).

**4. Notifications (trigger → recipient, from fr-ntevents unless noted)**

| Trigger | Recipient |
|---|---|
| Proposal submitted | (not specified; implied P2) |
| Reviewer assigned | Reviewer |
| Review due | Reviewer |
| Amendment requested | Proposer (P1) |
| Decision made | Proposer |
| Offering open | (not specified) |
| Registration, payment, enrolment confirmed | Participant |
| Results verified | Participant |
| Credential issued, corrected, replaced, revoked | Participant |
| QV expiry alerts | P1, P2 |
| Consent requested | Participant |
| Deposit recorded | Participant |
| Transfer decision | Participant |
| Credit expiry alert | Participant |
| CQI action due | (implied lecturer) |
| Periodic review due | Lecturer and PTj |
| Change affecting enrolled participants | Participants |
| Adapter call failed (fr-ihretry) | P5 |

**5. Settings**
- `alerts.*` (see §3.1.3). Adapter modes (fr-ihregistry).

---

## Cross-cutting gaps and inconsistencies

1. **Duplicate module labels.** §3.8 Achievement Record, §3.11 ACR and §3.13 Redemption share M12; §3.12 and §3.14 both say M13.
2. **Missing states.** Waiting list (§3.5), transfer application (§3.12), pathway mapping (§3.14) and offering (§3.4) have no state values in the SRS.
3. **Diagram gaps.** Enrolment has no Pending payment → Withdrawn path. QV has no Suspended → Withdrawn path. Proposal diagram has no label for the actor that moves Suspended → Terminated.
4. **Unnamed actors.** Who may request a result change (fr-aschange), who may revoke a credential (fr-crrevoke), who may record admission (fr-ctadmission), who may record a retention direction (fr-cvretain), and the recipients of "proposal submitted" and "offering open" are not named.
5. **S1 and P5 overlap.** The brief says S1 is admin and P5 is technical. The SRS uses P5 for integration and enrolment groups (fr-regbulk) and S1 for settings. Confirm P5 may enrol groups, since that is an operational rather than academic action.
6. **Configurable roles without keys.** Approver, transfer decision authority and module-credit academic authority are configured through `approval.route.*` (fr-approverbody). The key for the module credit flag and transfer authority is not named.
7. **TBD values.** `umc.completion_window_months`, `retention.*`, OI-03, OI-09, OI-10, OI-11 are unresolved.

**Not checked.** I read the three files in full. I did not check other SRS files, the Guide, or DESIGN.md, and I did not open the state diagrams in a renderer. Where I wrote "implied" or "not specified", the SRS does not state it in these files.

**Skipped and risk.** I did not verify the mermaid diagrams render, and I did not cross-check against the Guide. The main risk is that the implied actors and notification recipients above may need supervisor confirmation before UI design treats them as fixed.
