# MC-ABC System Guidelines (Antigravity 6)

## 1. Handover & Core Directives
- **Primary Goal (D-38)**: Build the UTeM Micro-Credential Management System (MCMS) so that it looks and behaves like the approved clickable prototype (style, arrangement, structure, content) while fully working with a real Laravel backend. Extra features that the prototype lacks are acceptable if required by documents, but must strictly adhere to the same styling/conventions and summary-first presentation.
- **UI Guidelines**: Strictly follow [DESIGN.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/DESIGN.md) (Carbon with uLearn Navy `#1e2a6e` sidebar). Refer to [HANDOVER.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/HANDOVER.md) for full project history and status.
- **Prohibition of Generic Icons / Emojis (D-53)**: Never use generic emojis (such as ⏱, 🔒, ⚖️, 💡, 👉, etc.) or arbitrary decorative SVGs in UI views. Rely strictly on IBM Carbon typography, layout geometry, and semantic status tags.
- **Prohibition of Redundant Text & Overused Brackets**: Never add redundant explanatory blocks (e.g., "Where is this / What happens next / Who is responsible") or redundant section markers when UI context is obvious. Never wrap badges, tags, or buttons in square brackets `[...]` (use `FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In`). Use standard spelling `"enroll"` / `"enrollment"` (not "enrol"). Card outlines must use crisp black (`#090a0f`).
- **Knowledge Base & Queries**:
  - Read `graphify-out/GRAPH_REPORT.md` first for questions regarding documents (Guide, Glossary, Decision Log, SRS, SDD, PRD, UI/UX Spec, Audit).
  - Query using `graphify query "<question>"`.
  - After documents change, run `/graphify --update`, then re-run `graphify-out/link_modules.py` to preserve SDD module links.

## 2. Document Sources of Truth
- **Primary Regulatory Authority**: `MC UTeM Guide.pdf` (Garis Panduan Micro-Credential UTeM Versi 1.0, 18 September 2026)
- **Decision Log**: `Document/Decision log/v1.1/` (`MCMS_Glossary_and_Decision_Log_v1.1.pdf`, Decisions D-01 to D-59)
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
