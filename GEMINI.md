# MC-ABC System Guidelines (Antigravity 6)

## 1. Handover & Core Directives
- **Primary Goal (D-38)**: Build the UTeM Micro-Credential Management System (MCMS) so that it looks and behaves like the approved clickable prototype (style, arrangement, structure, content) while fully working with a real Laravel backend. Extra features that the prototype lacks are acceptable if required by documents, but must strictly adhere to the same styling/conventions and summary-first presentation.
- **UI Guidelines**: Strictly follow [DESIGN.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/DESIGN.md) (v3 Linear-Precision, light-only, Electric Cobalt `#0052ff`, Deep Cobalt Navy `#0b1a3d` sidebar, `Plus Jakarta Sans`, crisp `#090a0f` card outlines). Refer to [HANDOVER.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/HANDOVER.md) for full project history and status.
- **Prohibition of Generic Icons / Emojis (D-53)**: Never use generic emojis (such as ⏱, 🔒, ⚖️, 💡, 👉, etc.) or arbitrary decorative SVGs in UI views. Rely strictly on Linear-Precision typography, layout geometry, and semantic status tags.
- **Prohibition of Guide & Regulatory Structure References (D-64)**: Never display references to the UTeM Micro-Credential Guide or its internal structure (`Appendix A`–`H`, `Lampiran A`–`H`, `B1.`–`B8.`, `C1.`–`C10.`, `D1.`–`D10.`, `E10`, `F1`–`F11`, `G4`–`G11`, `H4`/`H5`/`H8`/`H9`, `Cl.`, `Klausa`, `Garis Panduan Micro-Credential UTeM`, `m/s 119`) in any user-facing UI label, checklist, heading, button, validation/error message, PDF/printout, receipt, email, or notification. Use plain, self-explanatory wording that makes sense without the guide; keep regulatory traceability strictly in internal docs and code comments.
- **Automated Readiness Verification (D-65)**: Offering readiness uses `ReadinessChecker` for automated system checks plus 15 grouped Assisted/Manual confirmations across 4 collapsible sections.
- **Role Governance Alignment (D-66)**: Deputy Dean (`P2`) endorses MC proposals (within own PTj) and retains faculty operational/quality confirmations (offering readiness, results verification, completion, CQI, QV readiness, suspensions, pathway drafting); CODL (`P5`) verifies endorsed MC proposals, evaluates credit transfers (Step 1 validity + 9-criterion outcome equivalence), and shares Senate minute-recording Secretariat access; Academic Authority / Senate (`P3`) grants final approval on MC proposals, credit transfers, stackable pathways, and record disposal; Deputy Dean views credit transfers in read-only monitor mode only and is separated from Records/ABC deposits (`P6`/`P7`); all 403 rejections render contextual role handoffs with safe return links rather than bare generic denials.
- **Bounded Page & Progressive Disclosure (D-67)**: No page may display unbounded growing content at once. Repeating cards must never embed data tables or per-card search bars. All thresholds live in a single file (`app/config/ui.php`): `12` cards/page desktop (`6` mobile), `0` tables inside cards, `10` initial table rows, `1` global search bar, and `≤ 4` open sections. Every new page or change must pass D-67 before acceptance.
- **Prohibition of Redundant Text & Overused Brackets**: Never add redundant explanatory blocks (e.g., "Where is this / What happens next / Who is responsible") or redundant section markers when UI context is obvious. Never wrap badges, tags, or buttons in square brackets `[...]` (use `FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In`). Use standard spelling `"enroll"` / `"enrollment"` (not "enrol"). Card outlines must use crisp black (`#090a0f`).
- **Knowledge Base & Queries**:
  - Read `graphify-out/GRAPH_REPORT.md` first for questions regarding documents (Guide, Glossary, Decision Log, SRS, SDD, PRD, UI/UX Spec, Audit).
  - Query using `graphify query "<question>"`.
  - After documents change, run `/graphify --update`, then re-run `graphify-out/link_modules.py` to preserve SDD module links.

## 2. Document Sources of Truth
- **Primary Regulatory Authority**: `MC UTeM Guide.pdf` (Garis Panduan Micro-Credential UTeM Versi 1.0, 18 September 2026)
- **Decision Log**: `Document/Decision log/v1.3/` (`MCMS_Glossary_and_Decision_Log_v1.3.pdf`, Decisions D-01 to D-65)
- **SRS**: `Document/SRS/v1.5/` (`MCMS_SRS_v1.5.pdf`)
- **PRD**: `Document/PRD/v1.4/` (`MCMS_PRD_v1.4.pdf`)
- **SDD**: `Document/SDD/v1.5/` (`MCMS_SDD_v1.5.pdf`)
- **UI/UX Spec**: `Document/UIUX Spec/v1.6/` (`MCMS_UIUX_Spec_v1.6.pdf`)
- **Audit**: `Document/Audit/v2.2/` (`MCMS_Guide_Compliance_and_Verification_Audit_v2.2.pdf` & `MCMS_Appendix_F_Compliance_Audit_v2.2.pdf`)
- **Diagrams**: `Document/Diagrams/`
- **Prototype**: `UIUX/v2/prototype/`

## 3. Application Architecture & Testing (`app/`)
- **Stack**: Laravel 13.38 / PHP 8.3 / MySQL 8.4 LTS (with PostgreSQL 16 backup) modular monolith (93 tables, 26 migrations, 243 routes, 135 Blade views, 7 active roles). Plain Blade + CSS (no Livewire/Vite/Redis). Single-language `EN | BM` toggle backed by `app/lang/ms.json` (3,211 keys).
- **Modules**: `app/app/Modules/*`
- **Serve**: `cd app; php artisan serve --port=8110`
- **Database**: Docker Compose (`docker compose up -d mysql` on port 3306; user `mcms:mcms`).
- **Tests**:
  - Pest: `php vendor/bin/pest` (410 tests, 5,900+ assertions, 100% green; run only one Pest run at a time to avoid deadlock on `mcms_test`).
  - Playwright: `npx playwright test` (headed Edge).
