# Planner review: dd-a.js and dd-b.js (26 Oct 2026)
Format: priority | file:line | problem | fix. Search by content if lines moved.

## Cross-file findings (see the shared contracts in FIX-CONTRACTS.md)
a. P1 | dd-a.js:93 | live = 'In review' only; Submitted falls to read-only note. | Treat Submitted like In review for assignment; assignrev sets status 'In review'; seed one FTMK proposal as Submitted (push a new mc record, e.g. a Software Testing v1 copy id 'ST2' owned by aina, or reuse whatever lecturer submit creates).
b. P1 | dd-a.js:286, 294-295, 308-316, 333 | dd/verify, verifyres, roster, results "Passed" read only c.learners. | resOf = (s,c) => c.learners || ((s.lecRes||{})[c.id]||{}).learners || null; compute fin/res from MC.mc(c.mc).weights when missing; l.flag = held.
c. P1 | dd-a.js:225-276 | Readiness ignores the lecturer's checklist (s.lecRead), its sent flag and real values; dd "Mark resolved" signs off evidence that doesn't exist; evidence text fixed (RM 450, Dr. Aina). | Render from s.lecRead[c.id].items (k,t,st,ev,owner) falling back to READY; block Confirm until sent; "Accept evidence" only when lecturer item st==='Yes' with ev else "Waiting for the lecturer"; fee line MC.rm(c.fee), places, owner name from c.owner.
d. P1 | dd-a | m.pending (material change new version) never reaches dd. | verRows row for m.pending; dd/proposal/:id?v=pending; on Senate Approved promote pending to current and mark old Superseded; ask transition rule (finish old / move to new / equivalence) in the Senate dialog.

## dd-a.js
1. P1 | 99, 138, FL-04 462 | dd can "Mark resolved" an open blocking comment with no lecturer response, no dialog. | Resolve only when status 'answered' (else "Waiting for the lecturer"); seed an answered blocking comment for DB (DBC4 with response); FL-04 shows the lecturer response first; resolve via confirm with reason.
2. P2 | 151-155 | Endorse creates no "Record Senate decision" task. | Push {id:'T-SEN-'+id, area:'proposals', what:'Record Senate decision', ref:id, from:'Senate 8/2026', check:'On the agenda; decision not recorded', waiting:'Today', go:'#/dd/senate/'+id}.
3. P2 | 167-168 | Senate options lack "Returned for amendment". | Add; sets m.status='Returned for amendment', m.returnReason, notify lecturer.
4. P2 | 180, 183 | Defaults wrong: fixed meeting date, minutes pre-filled "Senate approved…", "(deferred)" suffix in default ref. | Empty date/minutes with placeholders; strip "(deferred)"; refuse date earlier than m.endorsed.
5. P2 | 103 | Deferral not shown on proposal page. | note('Deferred at <ref> …','warn').
6. P2 | 209, 212 | Pack header always "Senate 8/2026". | Use m.senate meeting.
7. P2 | 225-230, 254-260 | (see c) fixed evidence lines.
8. P2 | 257, 265 | (see c) resolve evidence not provided.
9. P2 | 261-262, 268-276 | Readiness only "Ready". | Radios Ready / Ready with conditions (required conditions → Open with c.conditions) / Not ready (reason; back to lecturer, lecRead.sent=false).
10. P2 | data 79, 296-307 | No held result reachable; held learners have no later path. | Add a held learner (via lecRes or a new cohort record), "Close case and verify" row action (confirm + reason).
11. P2 | 350-361 | finalize doesn't update enrolments. | Set e.status='Completed' for issued owners in c.id, 'Not completed' for not issued.
12. P2 | 335, 374 | SAMC without QV covering the cohort: credential withheld entirely; dialog claims QV valid. | Issue credential without the QV claim ("Claim omitted"); skip ACR batch when qvOk false; outcome text depends on qvOk.
13. P2 | 38 + dd-b 151 | QV renewal overwrites m.qv, losing earlier period. | dd-b pushes old m.qv into m.qvHistory; qvOk accepts any period in [m.qv, ...m.qvHistory] covering the cohort.
14. P3 | 16 | Overdue via /17/. | parseInt(t.waiting) >= 14.
15. P3 | 19 | "SAMC with QV expiring in 90 days" fixed 1. | Compute.
16. P3 | 209-210 | Two filled buttons on pack. | Item switcher ter.
17. P3 | 253 | "Not needed: this is a UMC" for MCE. | Use the real category.
18. P3 | 24, 376 | "To confirm"/"Confirmed" vs PRD "Complete". | "Waiting for your confirmation" / "Complete".
19. P3 | 380 | ACR batch link when no batch. | Only if batch exists.
20. P3 | 233 | Hard-coded c.id==='LE-2026-10'. | Use roster/resOf.

## dd-b.js
21. P1 | 186 | Periodic review outcome not required → "undefined" outcome saved. | Require selection (act returns false with toast if !c.outcome; add required).
22. P1 | 211-214 | Suspend says "New registrations stop" but CND-2027-1 stays Open. | ddSuspend: Open/Full not-started cohorts → 'Cancelled'; record plan; Running stay under plan.
23. P2 | 175-176 | Outcomes don't match fr-proutcome. | Continue / Continue with minor CQI / Content or assessment review / Review and re-approval / Consider suspension or termination (last routes to dd/suspend; update FL-16).
24. P2 | 190-191 | "Next review scheduled" but no date. | x.last = today, x.due = +1 year, history entry.
25. P2 | 196, 201, 214 | Participants list = generic users; Amir notified though not enrolled. | Rows from s.enrolments for live cohorts; else count "list held by the registry"; notify st only if Amir affected.
26. P2 | 197-206 | No lift suspension / terminate after suspension. | "Lift suspension" (confirm, reason) and "Terminate" (danger, reason) when Suspended.
27. P2 | 89, 93 | QV eligibility ignores MQF justification. | eligible = done >= 1 && slt >= 40 && !!mqf; reason in disabled button.
28. P2 | 157-172 | dd can't confirm action effectiveness/close actions; status words wrong. | "Close action" when "Done (effectiveness pending)" with Effective / Partly effective / Not effective; statuses Open / In progress / Done (effectiveness pending) / Closed.
29. P2 | 241-242 | ACR batch roster = generic NAMES (includes failed Arjun; ACR numbers repeat). | Rows from the cohort's issued credentials; ACR number derived from credential number.
30. P2 | 447 | "Open the full audit trail" → #/codl/audit (CODL screen). | Remove.
31. P2 | 312 | dd can decide again while "More evidence needed". | Decision open only for 'Under evaluation'; else "Waiting for the applicant".
32. P2 | 360 | Release always Available; no ABC sync record. | Available if not expired else Expired; optional ABC update date/reference in redeem and release dialogs.
33. P2 | 404, 413 | Plan decision re-decidable; stored "Suitable" vs option words. | After decision show "Decision recorded"; act checks residency and admission too.
34. P3 | 233, 249, 272 | Non-vocabulary statuses ("ACR confirmed", "Waiting for ABC reference", "Ready to record"), jargon "F2 checks". | 'Verified'; "ACR eligibility checks" with explanation.
35. P3 | 252, 254 | Checks always Pass/green. | Use computed values.
36. P3 | 283 | "Export for ABC" on sent batch. | Hide or "Export again".
37. P3 | 279, 294 | "Correction needed" doesn't set a.status; single deposit doesn't check Verified. | Set status; add check.
38. P3 | 70 | Days left by colour only. | Add words ("41 · renew soon").
39. P3 | 109, 115, 204, 279 | type="date" inputs show browser locale. | Text inputs prefilled "26 Oct 2026", Date.parse.
40. P3 | 125, 119 | Links to non-existent CQI/review records; two filled "Start preparing". | Link only if record exists; drop duplicate button.
