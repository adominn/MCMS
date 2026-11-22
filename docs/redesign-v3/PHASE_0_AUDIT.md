# Phase 0: Audit, Decisions & Safety Net (UIUX v3 Redesign)

- **Branch**: `redesign/uiux-v3` (branched from `620614f`)
- **Authority**: `UIUX/v3/css/tokens.css`, `design-samples/linear-precision/DESIGN.md` (v2.4), and confirmed user decisions below.
- **Status**: **COMPLETE** — Zero application files modified in Phase 0.

---

## 1. Confirmed User Decisions (Locked for Phases 1–6)

| # | Topic | Confirmed Decision |
| :- | :--- | :--- |
| D1 | **Dark Mode** | **Remove dark mode** — Light theme only, matching v3 (`UIUX/v3`). |
| D2 | **EN \| BM Toggle** | **Keep `EN \| BM` toggle** (4,174 BM keys in `app/lang/ms.json`). |
| D3 | **Typography** | **Download open-source `Plus Jakarta Sans`** `.woff2` files into `app/public/fonts/` (and retain local monospace stack). |
| D4 | **Project Rules (`GEMINI.md`)** | **Update `GEMINI.md`** alongside `DESIGN.md` in Phase 1 so v3 is the sole UI authority. |
| D5 | **Baseline Capture** | **Full baseline captured** against live Docker MySQL `mcms_demo`. |

---

## 2. Current System Inventory & Measurements

- **GET screens (`php artisan route:list`)**: **132** across 17 modules (Offering 20, Credentials 15, Records 14, Identity 10, Curriculum 8, Monitoring 8, Approval 6, CreditBank 6, Core 6, Learning 6, Transfer 6, Achievement 5, Assessment 5, Enrolment 5, Pathways 5, Notifications 4, Quality 3).
- **Blade views**: **137** `.blade.php` files.
- **Stylesheet**: `app/public/css/mcms.css` — 1,689 lines, 92 CSS custom properties.
- **Inline markup debt**: **3,882** `style="..."` attributes and **838** hard-coded hex colours across Blade views (to be systematically migrated module-by-module in Phase 5).
- **v3 Coverage**: 17 public HTML mockups + 11 auth HTML mockups + `student.css` plan; staff workspaces (~75% of routes) have no static HTML mockup and will inherit v3 tokens, components, and shell consistently.

---

## 3. Baseline Safety Net Artifacts

1. **97-Screen Visual & HTTP Baseline**:
   - Manifest: [`docs/redesign-v3/baseline/BASELINE_MANIFEST.csv`](baseline/BASELINE_MANIFEST.csv)
   - Screenshots: `docs/redesign-v3/baseline/screenshots/*.png` (97 full-page captures at `1440x900` via headless Edge; gitignored to keep repo lean).
   - **Result**: **97 / 97 screens returned HTTP 200 with 0 JS page errors** across Public (14), Student (22), Lecturer (21), Deputy Dean (17), CODL Admin (16), ABC Depositor / Custodian (5), and Industry Expert (2).
2. **DOM & Selector Freeze Contract**:
   - File: [`docs/redesign-v3/SELECTOR_FREEZE.md`](SELECTOR_FREEZE.md)
   - Locks 38 `#id` hooks, `data-*` attributes, 7 test CSS classes, and 219 accessible role/name hooks from `app/tests/E2E/*.spec.ts`.

---

## 4. Non-Negotiable Safety Rules (Phases 1–6)

1. **Visual changes only**: Do not alter controllers, routes, models, migrations, validation rules, or business logic (except removing the dark-mode toggle UI/handler per Decision D1).
2. **Preserve all DOM contracts**: Never remove or rename element `id`, `name`, `data-*`, or `aria-label` attributes listed in `SELECTOR_FREEZE.md`.
3. **Rule D-53**: Zero generic emojis (`⏱`, `🔒`, `💡`, `👉`, etc.) and zero square brackets `[...]` around badges, tags, or buttons.
4. **One atomic commit per phase**: Each phase is verified before committing so any phase can be cleanly rolled back.
5. **Testing boundary**: Verify each phase via `php -l`, route checks, and targeted Playwright visual/HTTP verification; ask before running full long regression suites.
