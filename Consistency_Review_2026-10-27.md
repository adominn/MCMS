# MCMS document set: consistency review (27 Oct 2026)

Checked: Glossary v0.5, SRS v1.0, PRD v1.0, SDD v1.0, UI/UX Spec v1.1, flow map (v1), against the Guide (Versi 1.0, 18 Sep 2026). UI/UX v1.2 skipped as requested. No document was edited.

## What aligns

- Requirement IDs: FR-01 to FR-271 are the same in SRS, PRD and SDD. The SDD cites every SHALL functional requirement. The PRD feature table covers all 271, and its Release 1 / 2 / 3 split matches the SRS SHALL / SHOULD / MAY wording.
- Numeric rules: 40 h SLT, 3 years QV, 10 years credit validity, 30% UTeM credit, 1 semester residency. They are identical in every document and match Guide 6.3.1, 11.2.2, 12.8, 14.5.1, 14.5.3.
- Guide references: every Guide clause (G x.y) and appendix table (App E5, F3, F7 ...) cited exists in the Guide. Existence only: I did not check each clause says what its requirement claims.
- Decisions: D-01 to D-29, A-01 to A-09 and OI-01 to OI-15 are used consistently. Retired roles (P4, P6, P7, X4) appear only in the SRS note that says they are unused.
- Design: UI/UX screen IDs (CMN, PUB, ST, LEC, DD, CODL, DOC) match the SDD. The flow map matches the SDD on MD-00 to MD-16 and JB-01 to JB-22. CODL imports UCreds results in the SRS, SDD and UI/UX alike.

## Conflicts and gaps (most important first)

| # | Finding | Where | Suggested fix |
|---|---|---|---|
| 1 | **FL-19 is not one traceable chain.** It stitches three offerings: registers in Cloud Native Development (SAMC), verifies Data Structures (UMC), completes Leadership Essentials (MCE, "no ACR follows"), then consents to an ACR. PRD Section 7 is the coherent script (Data Structures UMC through ACR, deposit, transfer; SAMC QV; MCE no ACR). | UI/UX App. D.21, PRD 7.1 | Use PRD Section 7 as the Release 1 acceptance script; rewrite FL-19 to follow it. |
| 2 | **"Prototype" means two things.** The clickable HTML prototype (UI/UX) and "Release 1 prototype" (PRD 5.5, 7; D-22, D-23), which is the real app on stubs. The SDD designs a production stack (Redis, Horizon, MinIO, ClamAV). | Glossary, PRD, UI/UX | Add a glossary entry for each term. |
| 3 | **Build order differs from the PRD.** PRD has 4 increments (1.1 Foundation incl. notifications and audit; 1.2 Curriculum incl. readiness and catalogue; 1.3 Delivery and credentials; 1.4 Credit and quality) and about 17 weeks from 2 Nov 2026. My 7-slice plan puts notifications (MD-16) last and catalogue in slice 3. | PRD 5.3, 5.4 | Adopt the PRD increments; split them into slices inside each. |
| 4 | **Document set defined two ways.** Glossary D-01, SRS and PRD: SRS, PRD, SDD. SDD and UI/UX: SRS, PRD, UI/UX Spec, SDD. The UI/UX Spec is marked "Approved", the others "Draft". The flow map has no document ID. | Front matter of each | Glossary v0.6: new decision for the document set and for the flow map. |
| 5 | **Completion window (OI-13) disagrees.** SRS: tracked with global setting `umc.completion_window_months`, default TBD. SDD: per-offering setting, no default, no reminder while unset. The key is missing from the SDD settings catalogue, though JB-11 exists. | SRS 3.7, SDD OI table, JB-11 | Choose one; add the key to the SDD. |
| 6 | **SDD says it covers every SHALL, but 16 SHALL IDs are never cited:** BR-09 to BR-15, IR-05, IR-08, IR-09, IR-12, NFR-23, NFR-35, NFR-39, NFR-51, NFR-52. NFR-51 and NFR-52 are training (process, not design). | SDD 2, 11.2 | Add a requirement-to-module row for each, or narrow the claim. |
| 7 | **Laravel 11 pinned.** From my own knowledge, not checked online: its security support ended in March 2026, before the SDD date. Horizon 5, Livewire 3 and Pest 3 also work on Laravel 12. | SDD 4 (AD-02) | Check the release page; start the build on a supported Laravel. |
| 8 | **Corporate identity (IR-08).** UI/UX treats DESIGN.md as "accepted as the UTeM corporate identity". The Glossary has no such assumption and the SDD is silent. | UI/UX 4, SRS IR-08 | Record an assumption in the Glossary. |
| 9 | **Proposed or Assumed decisions built in as fixed:** D-09 (PostgreSQL 16), D-10, D-13, D-14, D-19 (eddsa-rdfc-2022 proof), D-21, D-24, D-25. OI-02 and OI-03 were "Needed by: Before SDD"; the SDD issued with both open. | Glossary 3.2, 3.4 | Update the "needed by" dates; keep fallback adapters. |
| 10 | Cosmetic: UI/UX says "114 screens" but lists 97 screens plus 17 document entries (DOC-17 is an index); the appendix says 98 screens. The SDD cites PUB-02, 03, 04, 07, 08 only, not PUB-01, 05, 06, 09 to 11. The flow map omits FL-00, FL-19 and EV-61 (DisposalApproved); the SDD also omits FL-00. | UI/UX 6.6, SDD, flow map | Fix at the next version. |

## Not checked

- Whether each of the ~270 requirements restates its Guide clause correctly (numbers and clause existence only).
- SDD data model (Section 6) field by field against SRS Section 8.
- UI/UX v1.2 (skipped by request).

## Resolution (27 Oct 2026)

New version copies built; earlier versions untouched. Glossary v0.6, SRS v1.1, PRD v1.1, SDD v1.1, UI/UX Spec v1.2 (text only; Appendices B to D stay in v1.1), System Flow Map v2.

| # | Resolved in |
|---|---|
| 1 FL-19 | UI/UX 7.1, 7.3 and UA-06/UA-07 (follows PRD Section 7); the design prototype is not yet re-seeded |
| 2 Prototype terms | Glossary D-31; terms added to PRD, UI/UX and SDD |
| 3 Build order | Glossary D-32; PRD 5.3; SDD 11.2 build-order table |
| 4 Document set | Glossary D-30; all front pages |
| 5 Completion window | SRS FR-133 and settings table; SDD JB-11 and OI-13; UI/UX OI-13 |
| 6 Uncited requirements | SDD 11.2 cross-cutting table (all 16 now cited) |
| 7 Laravel | SDD AD-02 and Section 4: Laravel 13, DA-19 |
| 8 Corporate identity | Glossary D-33, A-10; SRS IR-08; SDD DA-18 |
| 9 Open-item dates | Glossary OI-02, OI-03, OI-08 |
| 10 Cosmetic | UI/UX counts (97 screens + 17 document entries); SDD public pages; flow map v2 (EV-61, corrected FL labels) |
