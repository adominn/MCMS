# MC-ABC System Guidelines (Antigravity 6)

## 1. Handover & Core Directives
- **Primary Goal (D-38)**: Build the UTeM Micro-Credential Management System (MCMS) so that it looks and behaves like the approved clickable prototype (style, arrangement, structure, content) while fully working with a real Laravel backend. Extra features that the prototype lacks are acceptable if required by documents, but must strictly adhere to the same styling/conventions and summary-first presentation.
- **UI Guidelines**: Strictly follow [DESIGN.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/DESIGN.md) (Carbon with uLearn Navy `#1e2a6e` sidebar). Refer to [HANDOVER.md](file:///C:/Users/Adomin/Documents/Intern/CODL/MC-ABC%20System%20-%20Antigravity%206/HANDOVER.md) for full project history and status.
- **Knowledge Base & Queries**:
  - Read `graphify-out/GRAPH_REPORT.md` first for questions regarding documents (Guide, Glossary, Decision Log, SRS, SDD, PRD, UI/UX Spec, Audit).
  - Query using `graphify query "<question>"`.
  - After documents change, run `/graphify --update`, then re-run `graphify-out/link_modules.py` to preserve SDD module links.

## 2. Document Sources of Truth
- **Decision Log**: `Document/Decision log/v0.9/` (`MCMS_Glossary_and_Decision_Log_v0.9.pdf`)
- **SRS**: `Document/SRS/v1.3/` (`MCMS_SRS_v1.3.pdf`)
- **SDD**: `Document/SDD/v1.3/` (`MCMS_SDD_v1.3.pdf`)
- **PRD**: `Document/PRD/v1.2/` (`MCMS_PRD_v1.2.pdf`)
- **UI/UX Spec**: `Document/UIUX Spec/v1.4/` (`MCMS_UIUX_Spec_v1.4.pdf`)
- **Audit**: `Document/Audit/v2.0/` (`MCMS_Guide_Compliance_and_Verification_Audit_v2.0.pdf`)
- **Diagrams**: `Document/Diagrams/`
- **Prototype**: `UIUX/v2/prototype/`

## 3. Application Architecture & Testing (`app/`)
- **Stack**: Laravel 13.38 / PHP 8.3 / MySQL 8.4 LTS (with PostgreSQL 16 backup) modular monolith. Plain Blade + CSS (no Livewire/Vite/Redis).
- **Modules**: `app/app/Modules/*`
- **Serve**: `cd app; php artisan serve --port=8110`
- **Database**: Docker Compose (`docker compose up -d mysql` on port 3306; user `mcms:mcms`).
- **Tests**:
  - Pest: `php vendor/bin/pest` (366 tests, 5,225 assertions, 100% green; run only one Pest run at a time to avoid deadlock on `mcms_test`).
  - Playwright: `npx playwright test` (headed Edge).
