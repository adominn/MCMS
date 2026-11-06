# Planner review: student.js, public.js, common.js, core.js, data.js, flows.js, proto.css (26 Oct 2026)
Format: priority | file:line | problem | fix. Line numbers are from review time; search by content if moved.

## core.js (fixed by the lead, not by role fixers)
1. P1 | render/openMenu | Controls not keyboard reachable (span.btn, a[data-go] without href, div.nav, div.tab, tr[data-go], .lang, .mi, .ni). | Give every interactive data-* element that isn't a native control tabindex=0 and role (link for data-go, else button); Enter/Space triggers click.
2. P2 | modals | No focus trap or return; drawer lacks role=dialog; danger dialogs focus destructive button first. | Save/restore focus; trap Tab; drawer role/aria; danger focuses Cancel.
3. P2 | MC.tag | ok regex first → "Not approved", "Partly approved", "Not ready", "Not completed", "Not issued", "Invalid" turn green. | Check err (add /^not |^invalid|^un/i) and warn (partly) before ok.
4. P2 | MC.fmt | Only screen body formatted; toasts, modals, drawers, menus show "26 Oct 2026"; "26 Oct" without year never converted. | Apply MC.fmt in toast, modal, drawer, menu; second pass for day-month without year → DD/MM.
5. P2 | lang act | Re-renders and wipes typed data. | Toggle in place, save, toast, no re-render.
6. P2 | render | s.banner (codl) never drawn. | Draw maintenance banner under top bar.
7. P2 | — | No idle timer. | 28-minute timer → MC.open('timeout'); ?idle=1 fires in 5 s for demos.
8. P3 | validate | Radio group counted per input; choices outside .field get no inline error. | Count per name; place error after group.
9. P3 | finish | Act throwing leaves dialog silent. | try/catch → toast "Something went wrong · Reference ERR-…".
10. P3 | load | Shallow merge misses records pushed into seed arrays. | Append seed records whose id isn't in the saved array.

## student.js
11. P1 | 70-72, 87-93 | Catalogue/register ignore m.status; suspended CND still registrable. | Filter Approved; register guard note "Registration closed: this micro-credential is <status>".
12. P1 | 124-133 | Capacity not checked; never becomes Full (fr-regopen). | Before charge: if taken >= places → status Full, toast, return false; after increment set Full when reached.
13. P1 | 94-96, 116-117 | ?step=4 without paid enrolment shows "Enrolment undefined". | if (step===4 && !done) step = rec ? 3 : 1.
14. P1 | 291-292 | New application lines get match ['', '', '', ''] so dean can approve with no comparison (BR-30). | Seed match from lookup {'DS|BITS 1123':[Full×4],'CND|BITU 3973':['Full','Partial','Full','None']}, default 'Not rated'. (dd-b fixer also blocks on empty/'Not rated'.)
15. P2 | 99-102 | Step 1 Continue outside form, no validate; eligibility checks hard-coded pass. | Button inside form with data-validate, store phone; compute checks (places, Approved, not already enrolled, prerequisite line); failures shown with reason and Continue disabled.
16. P2 | 121 | Ack stored only as ack:true in module memory. | Store {ackVer:'v1.0', ackOn, pdpaOn} in s.regDrafts[cohort] and copy onto enrolment at payment.
17. P2 | 149-151, 163 | Withdrawal deadline not enforced; Pending payment can't be cancelled. | Withdraw only before deadline else disabled label; "Cancel registration" danger dialog for Pending payment → Cancelled.
18. P2 | 45, 59; data.js:96 | "Completed, waiting for verification" not a status; LE results already Verified; after dd confirms completion E-2026-0644 never becomes Completed. | Student derives waiting label from cohort (results!=='Verified' → "Waiting for result verification", else "Waiting for completion to be confirmed"); credit sentence from creditOf. (dd-a fixer sets enrolments Completed in finalize.)
19. P2 | 176 | Greedy regex strips the whole name in Completions. | Use MC.mcName(MC.co(e.cohort).mc).
20. P2 | 191-197 | Credential page ignores c.history/c.correctedOn; Replaced shows "credential is revoked" and no link; Revoked/Replaced still offer Share. | Revision table from c.history; disabled label per status; replacedBy link to new credential; Share only when Issued or Corrected.
21. P2 | 191, 226, 299, 148 | Any record loadable by id (Amir can consent on Wong's ACR). | Filter owner/who === amir; else note "This record is not yours or does not exist."
22. P2 | 298-309 | Dean's reason/decidedOn never shown; evidence upload doesn't reopen dean task. | Show t.reason and t.decidedOn; st-tr-evidence reopens task ref===t.id (done=false, check 'Evidence received '+TODAY).
23. P2 | 291-293 | Submit doesn't re-check availability; programme hard-coded. | Re-run exclusion rule on submit; programme from s.admissions.
24. P2 | 324 | Plan next steps hard-coded. | Build from myAcrs and transfers.
25. P2 | 33 (+ public.js 87-89, dd-a.js 80, 374, 392) | Category long names wrong. | Glossary names: Unbundled Micro-Credential (UMC), Stand-alone Micro-Credential (SAMC), Micro-Credential Eksekutif (MCE). Use MC.CATNAME (added to core by the lead).
26. P3 | 34 | SAMC never QV'd: "QV … ends on a date not yet set". | Separate sentences for never-QV vs renewal.
27. P3 | 169 | Withdrawn enrolment modules shown as In progress and counted. | "Stopped (withdrawn)".
28. P3 | 133, data.js:94-97 | paid stored as "RM 600, 26 Oct 2026". | Store amount + paidOn, show MC.rm(amount). (Fix display in student.js; seed strings may stay.)
29. P3 | 244-248 | Declining after consent given is a withdrawal without danger/reason. | danger + reason when declining after Given.
30. P3 | 64, 203 | Home recognition track static; "What an employer sees" shows Issued not Valid. | Build track from myAcrs; use public verify mapping.

## public.js
31. P1 | 87 | UMC card promises credit "can be transferred into the matching programme". | "You earn the course credit (for example 3 credits). You may later apply to have it recognised; each transfer is decided case by case."
32. P1 | 160-161 | Pathways shows Draft PW-BITZ-1. | Only Published; else empty state "No pathway is published yet. Pathways appear here after Senate approval."
33. P1 | 237 | Replaced link uses credential id as code → not found. | Look up the new credential by id, link its code.
34. P2 | 232 | Corrected tagged "Valid". | Corrected → "Corrected, valid".
35. P2 | 35, 138, 140 | m.mqf || 4 invents MQF; "Credit after QV renewal" used for never-QV SAMC. | Omit level when no mqf; 'Credit after QV' when not Quality-Verified; renewal wording only when QV ends before cohort end.
36. P2 | 81-82 | Hero "can count towards a degree". | "…that may be considered towards a degree".
37. P3 | 136 | Suspended MC says "not yet approved". | "Registration is closed: this micro-credential is suspended."
38. P3 | 78-107, 132-155, core pubFoot | Less informative than the UCreds site (ucreds.utem.edu.my): featured courses with image banners, facts block (Institution/Format/Effort/Start/End/Duration/Cost, "Enrol me"), tabs for Pre-requisite/Syllabus/Reading/FAQ, policy footer, verify. | Featured strip with image placeholders on home; "Effort: N h/week" in facts; UCreds-style tabs on offering; policy links (Privacy notice, Terms, Accessibility) — footer handled by the lead in core.

## common.js (fixed by the lead)
39. P2 | 92-96 | Notifications page lacks type/read filters; opening a row doesn't mark read; unread by dot/bold only.
40. P2 | 107-114 | Search links go to dd/lec routes regardless of role → persona switch (BR-42).
41. P2 | 88 | Timeout "Sign out now" is just Cancel.
42. P3 | 71-79 | Save preferences does nothing; digest shown to CODL.
43. P3 | 42-46 | Sign-in always MFA, even participants.

## flows (owners fix their own)
44. P1 | flows.js:9-13 (lead) | FL-19 steps 5-9 on cohorts Amir isn't in; certificate step shows old credentials. | Step 7 caveat; step 8 → #/dd/completion/LE-2026-10 (Amir is in roster) "Hakim confirms completion; the system issues Amir's Leadership Essentials certificate (MCE: no ACR)"; step 9 → the new LE certificate.
45. P2 | flows.js:16, 18-19 (lead) | Step 12 → #/dd/deposit/MCMS-A-2026-000214; step 14/15 wording vs TR-0012.
46. P2 | dd-b.js:458 FL-12 step 1 (dd-b fixer) | On fresh data nothing selectable at st/transfer/new. | Go #/st/transfer/TR-2026-0012 "Amir's application TR-2026-0012 is under evaluation".
47. P3 | public FL-23 (public fixer) | No Replaced result shown. | Step telling the viewer to replace 7Q2M-K4PA in dd/cred/MCMS-C-2026-000418 first, then the Replaced page.

## css / data (lead)
48. P2 | proto.css | No responsive rules; participant screens can't reflow to 360px. | @media (max-width:760px): drop min-width, side nav collapses, stack rows/grids, fixed side columns auto width, public padding 16px.
49. P3 | data.js | Wong Kar Seng has CND-2026-1 credential and also in CND-2026-2 import. | Swap CND-2026-2 problem row to another learner.
50. P3 | common.js:128 (lead) | Flows page filled Start on every row. | btn ter sm.
