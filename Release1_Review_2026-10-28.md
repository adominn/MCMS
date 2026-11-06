# MCMS Release 1: review of the prototype against the PRD

Date: 28 October 2026. Reviewed: the Laravel prototype in `app/` against PRD v1.1 (Section 5 feature list and Section 7 demonstration scenario), with the SRS v1.1, SDD v1.1 and UI/UX Specification v1.2 as the detail behind each feature.

## 1. Result

**The Release 1 acceptance script of PRD Section 7 runs end to end.** FL-19 plays the four acts through the screens only, on a fresh database, with the demo clock:

| Act | What it proves | Result |
|---|---|---|
| 1 Curriculum | A guided proposal stops an SLT of 110 h and accepts 120 h; colleague review; endorsement with the category check; Senate decision recorded by the deputy dean; readiness confirmed and the cohort opens in the catalogue | Pass |
| 2 Learning and credentials | Registration and dummy payment; CSV import with one rejected row; verification gives module badges only; completion confirmed separately; certificate and signed Digital Credential; employer verification; a name corrected with history kept | Pass |
| 3 Credit | ACR generated and confirmed; consent; ABC reference; 3 credits Available with a 10-year expiry; admission recorded apart from the transfer; equivalence judged on outcomes; credit Provisionally Redeemed; a second application blocked; stackable plan at 25% UTeM credit is Not yet suitable | Pass |
| 4 Quality | Quality Verification result of 2 credits at MQF level 4; the demo clock passes the expiry and the claims go; the MCE completes and no ACR is offered; a CQI action closes only after its effectiveness check; the traceability view for Amir shows every stage | Pass |

Test evidence on the day of the review: 324 Pest tests (4,804 assertions), 23 Playwright flows (FL-00 to FL-24 where they apply) and the 4-act scenario, all passing. The code is about 7,300 lines in `app/`, 19 migrations, 104 views, 197 routes, 34 settings, 17 module folders.

## 2. Release 1 features against the PRD list

Status: **Built** means the core SHALL requirements of the feature work and are tested. **Built, with gaps** means the feature works but something the SRS or SDD names is missing; the gap is stated.

| Feature | Status | Evidence | Gap |
|---|---|---|---|
| f-sso Sign-in and accounts | Built, with gaps | FL-01, FL-17; stub sign-in with a second step for deciding roles | Microsoft Entra ID sign-in waits for OI-03; the interface outside the public site is English only (FR-08) |
| f-roles Roles, scopes, separation of duties | Built | FL-17, FoundationTest; scoped roles, submitter cannot decide, conflict of interest, preparer is not confirmer | Approval delegation is Release 2 |
| f-settings Settings, templates, reference data | Built | FL-17; 34 versioned settings, bilingual templates, CSV import of reference data | None known |
| f-notify Notifications and integration hub | Built | Increment 1.1 tests; adapters, retry, log | Email goes to a stub or an untested SMTP adapter |
| f-proposal Category and guided proposal | Built, with gaps | FL-02, FL-19 act 1 | Printable proposal (FR-propprint, DOC) is a screen only |
| f-umc UMC structure checks | Built | CurriculumTest; parent course, SLT sum, credit-based and topic-based models | Found in the scenario and fixed: a component could not be tied to a module in the screens |
| f-approval Review and approval | Built | FL-03, FL-04, ApprovalTest | Senate pack is a screen, not an A4 document |
| f-versions Versions and material change | Built | VersionTest | Participant notice of a version change is a notification only |
| f-readiness Offering readiness | Built, with gaps | FL-05, OfferingTest | The support-staff confirmation of FR-86 (the third party) is not built; evidence is a note, not a file |
| f-catalogue Public catalogue | Built | FL-22 | FAQ, privacy, terms and accessibility pages are not written |
| f-register Registration and payment | Built, with gaps | FL-06, FL-06b | Staged completion per module is not tracked; the enrolment confirmation is a page, not a PDF |
| f-csv CSV exchange with UCreds | Built, with gaps | FL-07, ResultsTest | No virus scan and no object store; the import runs inside the request |
| f-verify Result entry and verification | Built | FL-08 | Appeals are Release 2 |
| f-achrec Achievement Record | Built, with gaps | FL-08 | The "Not completed" status needs a closing job; the record is a screen, not a PDF |
| f-issue Credential issuance | Built, with gaps | FL-09, FL-10, CredentialTest; VC-JWT signed with Ed25519 | The proof is VC-JWT, not eddsa-rdfc-2022 (see 4); the signing key sits in the database; the certificate is a print page, not a PDF |
| f-wording Recognition wording control | Built | OfferingTest, CredentialTest; credit, MQF and QV claims only from verified records | None known |
| f-public Public verification | Built | FL-23; minimum fields, log, rate limit, JWKS | None known |
| f-correct Correction, replacement, revocation | Built | FL-10 | None known |
| f-qv Quality Verification for a SAMC | Built, with gaps | FL-14, QualityTest | Evidence is a file reference; the submission package has no zip; re-verification is started by hand |
| f-acr ACR preparation | Built, with gaps | FL-11, CreditBankTest | The ACR is a screen, not a PDF; the correction event EV-60 for an ACR is not handled |
| f-deposit Consent and deposit | Built, with gaps | FL-11 | The ABC file format is open (OI-11), so the export is a draft CSV |
| f-ledger Credit ledger and validity | Built | FL-11, CreditBankTest; alerts at 365 and 90 days, expiry keeps the ACR | None known |
| f-transfer Credit transfer evaluation | Built, with gaps | FL-12, TransferTest | The decision letter (DOC-12) is not built; the transfer limit is a 50% stand-in until OI-09 |
| f-redeem Single redemption | Built | FL-12; second use blocked, release and redemption recorded by hand with evidence | None known |
| f-stackmap Pathway mapping | Built | FL-13, PathwayTest | PLO and Body of Knowledge matrices are lists of names (the full matrices are Release 2) |
| f-stackplan Individual stackable plan | Built | FL-13, FL-19 act 3 | Remaining requirements have no completion dates |
| f-cqi Monitoring and CQI | Built, with gaps | FL-15, MonitoringTest | An action that changes a component opens a task and does not create the change request itself; no technical-issue count |
| f-review Periodic review, suspension, termination | Built, with gaps | FL-16 | The overdue-decision reminder job (JB-10) is not built; the suspension notice is a notification, not a PDF |
| f-audit Audit trail, corrections, traceability | Built | FL-18, RecordsTest; hash-chained audit, discrepancy log, trace, cohort records | None known |
| f-reports Reports and retention | Built, with gaps | FL-24, RecordsTest; ten reports, CSV, print, retention, holds, disposal | Excel and PDF are the CSV and the browser print; large reports do not run in the background |

All thirty Release 1 features are in the prototype. Sixteen are complete as the SRS words them; fourteen work with the gaps above.

Release 2 items that were built early because the work was close: the signature check of an uploaded credential file (FR-vfsignature), a holds feature (FR-hold), and the demo clock (a prototype need, PRD 7.2).

## 3. What stands between the prototype and a pilot

These are not feature gaps. They are the infrastructure and decisions the prototype deliberately stood in for.

1. **Sign-in.** Microsoft Entra ID (OI-03). The stub sign-in and the demo clock are blocked in production by code and by tests.
2. **Stored files.** Upload, virus scan and an object store, for evidence files, QV decision documents and import files. Several screens hold a file reference as text until this exists.
3. **Queue workers.** Redis and Horizon for the 500-credential batch (NFR-08), the background reports (NFR-report) and the import. The scheduled jobs run today through `php artisan schedule:work`.
4. **Real PDFs.** Certificate, receipt, ACR, ABC export, transfer letter and suspension notice open as A4 print pages. A server-side PDF renderer is needed for the "save as file" cases.
5. **Signing key.** The key is stored encrypted with the application key. OI-08 asks who owns it. A key store or an HSM is the production answer.
6. **The authenticated credential API (IR-21).** Not built. (While this work ran, an `api` route file briefly appeared in the project and was removed again by someone else. It was not part of this work and nothing in the prototype depends on it.)
7. **Open items waiting for CODL and UTeM ICT.** OI-02, OI-03, OI-04, OI-07 (retention of academic records), OI-08, OI-09 (transfer limits per programme), OI-10, OI-11 (ABC file format), OI-12 (withdrawal deadline).

## 4. Differences from the documents that the documents should now absorb

The documents should be updated in new versions (never edited in place). The prototype differs from them here, and each difference was a decision, not an accident.

| Document | Statement | What the prototype does | Suggested change |
|---|---|---|---|
| SDD | Pest 3 | Pest 4.7 (Laravel 13 needs PHPUnit 12) | Update the version |
| SDD 4, PRD | Credential proof eddsa-rdfc-2022 | VC-JWT with the same Ed25519 key. The PHP JSON-LD library handles JSON-LD 1.0 only and fails on the Verifiable Credentials v2 context | Record the fallback as the Release 1 decision; revisit when a library exists |
| SDD | A repeated import file is refused | Refused. UI/UX storyboard says a notice only | Align the UI/UX Specification with the SDD |
| SDD | `qv_submissions` table | Submission is columns on `qv_records` | Drop the table from the SDD or add it |
| SDD | `abc_batches` with a file | `abc_batches` keeps the record ids it exported; the batch is not linked to the deposits | State this or add the link when OI-11 is answered |
| SDD | `programmes` without a faculty | `programmes.ptj_id`, `transfer_limit_percent`, `non_transferable`, `curriculum_version`, `intake` added | Add to the data dictionary |
| SDD | A CQI action creates a change request | A task opens and the lecturer makes the request | Say so, or build the request |
| SDD | Decision enums | The stackable plan stores the SRS decision and shows the UI status words, as the SDD note proposed | Confirm the mapping (the SDD asks for confirmation) |
| SRS settings table | The table of configurable settings | 34 settings in all, including these new ones: added `approval.route.transfer`, `.stackable`, `.suspension`, `.disposal`, `qv.provider_registered`, four `quality.target_*`, `review.interval_months`, `review.remind_days`, three `retention.*`, `demo.clock_offset_days` | Add the new keys to the settings table |
| UI/UX Spec | Screens for Quality review, Reports, Retention, Demo clock | Built as listed in the README module table | Add the demo clock (CODL-14) as a test-environment-only screen |

## 5. Defects the scenario found, now fixed

An end-to-end run through the screens finds what separate tests cannot. Four were found and fixed during this work:

1. **A proposal made in the wizard could never be completed.** The assessment tab had no way to say which module a component assesses, so a participant could not achieve a module and the completion check always said "Module 1 has no assessment". The tab now has a Module selector for each component. (The back end already accepted it; only the field was missing.)
2. **A demo-clock change took effect only after the days had passed.** The setting was stamped with the moved date, so a reset did not apply. It is stamped with the real time now.
3. **The moved date leaked between requests in a long-lived process**, which signed users out. The date is now restored after each request, and the idle check always uses the real time.
4. **The quality review page did not render once an action existed** (a Blade directive pair). Playwright caught it; the Pest suite had never rendered that page, so a render check was added.

## 6. Recommended next steps

1. Answer the open items OI-03, OI-07, OI-08, OI-09 and OI-11 with CODL and UTeM ICT. They decide the sign-in, retention, the signing key, the transfer limits and the ABC export, and several screens wait on them.
2. Build the stored-files service (upload, scan, object store), then replace the file references in the QV, readiness and import screens.
3. Add the queue workers, then move the import, the credential batch and the large reports onto them.
4. Choose a server-side PDF renderer and move the receipt, certificate, ACR, ABC export, transfer letter and suspension notice onto it.
5. Close the small feature gaps in section 2 in this order: the support-staff readiness confirmation, the CQI-to-change-request link, the "Not completed" closing job, JB-10, and the proposal and Senate pack print layouts.
6. Produce the next versions of the SRS, SDD and UI/UX Specification with the differences in section 4, and rebuild their PDFs.
7. Run the scenario for the supervisor demonstration (D-23): `npm run test:scenario` replays it and leaves the scenario database `mcms_scenario` at the end of Act 4, so the screens can be shown as they stand.
