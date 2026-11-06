# SDD v1.0 → v1.1 fix list (27 Oct 2026)

Source: Consistency_Review_2026-10-27.md. Make these in a copy `SDD/v1.1/src/`, never in v1.0 (rule: version copies). Glossary v0.6 decisions D-30 to D-33 are the basis. Build with `SRS\build\build.ps1 -Src 'SDD\v1.1\src' -Out 'SDD\MCMS_SDD_v1.1.pdf' -Ref MCMS_SDD_V1.1_Oct_2026`.

| # | File | Change |
|---|---|---|
| 1 | 01-front | Version 1.1, reference MCMS_SDD_V1.1_Oct_2026; "Written against" Glossary v0.6 (MCMS_FND_V0.6_Oct_2026); document set adds the System Flow Map (D-30); history row 1.1. |
| 2 | 02-intro, 11-verification | Define "design prototype" and "Release 1 prototype" as in D-31. Say the acceptance script is PRD Section 7, and FL-19 follows it. Add the PRD increments 1.1 to 1.4 (D-32) as the build order, with each module (MD-00 to MD-16) assigned to an increment. |
| 3 | 05-modules (MD-08), 06-data, 11-verification (OI-13) | Completion window: add setting `umc.completion_window_months` (global default, no value until OI-13 is answered) to the settings catalogue; offering may override; JB-11 reads offering value, then the default; no reminder while both are unset. |
| 4 | 11-verification (2 and 11.2) | Cite the 16 uncited SHALL IDs, or narrow the "every requirement" claim in 02-intro. Proposed mapping: BR-09, BR-12, BR-13 → MD-02 (UMC rules) · BR-10, BR-11, BR-15 → MD-02 and MD-08 · BR-14 → MD-10 (QV) · IR-05 → shared Livewire form components (04) · IR-08 → DESIGN.md tokens (D-33) · IR-09 → CMN-08 Help and the help text on each screen · IR-12 → A4 Blade document templates (08/DOC) · NFR-23 → task page and step counts in the Playwright flows · NFR-35 → compliance check in 11 · NFR-39 → AD-09 credential signing · NFR-51, NFR-52 → handover and training in deployment (training is a process item, not code). Check each mapping against the module text before adding it. |
| 5 | 04-technology | Laravel version: confirm the supported release (my recollection, unverified: Laravel 11 security support ended March 2026) and set AD-02 and the "current for Laravel 11" health-check line to the supported release. Re-check Livewire 3, Horizon 5, Pest 3, Socialite 5 against it. |
| 6 | 04-technology, 11 (assumptions) | Add DA entry for D-33 (DESIGN.md as corporate identity, IR-08). |
| 7 | 11 (open items) | "Needed by" for OI-02, OI-03, OI-08 as in Glossary v0.6. |
| 8 | 05/07 (screens) | Cite PUB-01, PUB-05, PUB-06, PUB-09, PUB-10, PUB-11 (static pages) in MD-15 or the public site module. |
| 9 | Flow map | Add EV-61 DisposalApproved; add FL-19 as the end-to-end path once rewritten. Separate build of `Diagrams/`. |

Not part of the SDD: rewrite FL-19 in the UI/UX Design Specification (v1.2 or later) to follow PRD Section 7.
