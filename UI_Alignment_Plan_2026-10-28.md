# MCMS UI alignment plan (to the approved prototype)

Date: 2026-10-28. Status: PLAN ONLY, nothing implemented. Awaiting approval.

## 1. Goal and what changes

Make the running app (`app/`) look and behave like the approved prototype (`UIUX/v2/prototype`) in style, arrangement, structure and content. Features the prototype lacks but the SRS/PRD/SDD need may be added, in the prototype's style. Backend logic, routes, rules and tests stay; this is a view and front-end behaviour change.

This reverses D-35 ("UI judged against requirements"). It is recorded in Decision Log v0.8 (new copy, PDF, never edited in place).

Source of truth: prototype `css/mcms.css`, `css/proto.css`, `js/core.js`, `js/data.js`, `js/screens/*.js` (about 106 screens); UI/UX Spec v1.2 chapters 04 (visual), 05 (interaction), 05b (navigation), 06 (screens), 06b (inventory: 114 screen entries: DD 29, LEC 18, DOC 17, ST 14, CODL 13, PUB 11, CMN 10, EXT 2), 08 (documents).

## 2. Gap summary (current app vs prototype)

| Area | Prototype | App today |
|---|---|---|
| Tables | search, sortable headers (`aria-sort`, date/RM/number aware), rows per page 5/10/25/50, "from-to of n", "Page x of y", prev/next, empty state | plain tables, simple Previous/Next on a few |
| Dialogs | modal confirm with optional reason (kept in audit trail), form modals | none; direct POST buttons |
| Drawers | side panels for detail/quick view | none |
| Toasts | ok/warn/err/info after actions | flash banner only |
| Menus | notification popover with badge, user menu | links only |
| Top bar | brand, per-role search, EN/BM, help, bell, user menu | brand and links; BM toggle inert |
| Side nav | grouped, icons, counts per role | grouped, no counts |
| Breadcrumbs, flow/dev bar | yes | no |
| Role homes | task queue, KPIs, "at a glance" per role | student done; staff homes partial |
| Detail screens | tabs, `.dl` definition lists, `.check` lists, `.track` progress, `.note` callouts | mixed |
| Validation | required fields highlighted plus toast | browser default |

## 3. Shared components (build once, reuse everywhere)

Server-rendered Blade plus one small vanilla JS file (`public/js/mcms.js`), no build tooling, progressive enhancement (works without JS, as the prototype does not need a server).

1. **Data table** `<x-table>`: header search, sortable columns (click or Enter, `aria-sort`, type-aware: text, date, RM, number), rows-per-page select, range text, pager. Client-side over the rendered rows for lists up to a few hundred rows (all current lists); for larger sets (audit trail, notifications, UCreds content) server-side paging with the same markup and query string. Empty state row. Replaces every `<table>` and the `pagination.simple` view.
2. **Confirm/form modal** `<x-modal>` plus `data-confirm` hook: title, body, optional reason field posted with the form; focus trap, Esc, return focus (UI/UX 4.2).
3. **Drawer** `<x-drawer>` for quick-view panels.
4. **Toast** from the session flash (ok/warn/err/info) with auto-dismiss and `role=status`.
5. **Popover menus**: notifications (count badge, last items, mark read, link to full list) and user menu (profile, language, help, sign out).
6. **Tabs** with counts, **breadcrumbs**, **page title block** (title plus subtitle plus actions), `.note`, `.check`, `.dl`, `.track`, `.kpis` as Blade components.
7. **Top-bar search** per role (route to existing search results page), **nav counts** (open tasks, pending approvals, unread), **EN/BM toggle** (stored per user; translate nav, headings and common labels first, rest falls back to English; recorded as a known partial).
8. **Validation styling** and error summary, form layout classes from the prototype.
9. CSS: replace `public/css/mcms.css` with the prototype's `mcms.css` and `proto.css` tokens and classes, keep fonts, keep the navy #1e2a6e sidebar (your earlier choice).

## 4. Order of work (each step ends: Pest + Playwright flow + README row + wrap-up)

| Step | Scope | Screens (approx.) |
|---|---|---|
| U0 | Decision Log v0.8 (reverse D-35), UI/UX Spec v1.3 finish, this plan approved | docs |
| U1 | Shell and components: CSS port, top bar, nav counts, menus, toasts, modal, drawer, table, breadcrumbs, validation, BM toggle | all layouts |
| U2 | Public site and sign-in: landing, catalogue, programme detail, verify, help, 404/error, maintenance | PUB 11, CMN 10 |
| U3 | Student: My learning, find, registrations/payments, achievement record, credentials, credit ledger, transfer, stackable plan | ST 14 |
| U4 | Lecturer and reviewer: my MCs, proposals/versions, reviews, cohorts/results, CQI, QV evidence, reports | LEC 18 |
| U5 | Deputy dean (largest): task queue, approvals, offerings/readiness, results, credentials, quality, QV, ACR/ABC deposits, transfer, pathways, reports, traceability | DD 29 |
| U6 | CODL admin and industry expert: users/roles, reference data, settings, templates, UCreds exchange and content, adapter status, test mailbox, audit, retention, review invitations | CODL 13, EXT 2 |
| U7 | Printable documents: receipt, enrolment, certificate, credential, record, ACR, proposal, pack, report, ABC, QV pack, transfer, suspend, import, audit | DOC 17 |

Rule per screen: open the prototype screen, match layout order, headings, copy, labels, tags, columns, actions; wire to the real data; add only what the SRS requires beyond it (marked in the README gap list).

## 5. Testing and risk control

- Keep the 23 Playwright flows and the 4-act scenario green after each step; selectors move from raw `table`/link text to roles and `data-*` hooks, updated in the same step.
- Pest: keep 328 tests green; add component tests (table sort/search/paging markup, modal reason posts to the audit trail, toast flash, nav counts, BM toggle persistence).
- Visual check of every screen against the prototype side by side in the browser pane, screenshots per role.
- Risks: a modal on every destructive action changes the Playwright steps (mitigated by `data-confirm` working without JS fallback); client-side tables on big lists (mitigated by server-side mode); BM copy is incomplete (needs your translations or my draft for review).

## 6. Effort

U1 largest shared build, then DD (29 screens) and LEC (18) are the bulk. Roughly 8 build steps; each is a normal session-sized chunk. Order above lets you see a finished role after each step.

## 7. Decisions needed

1. **Start order**: shell first, then public, student, lecturer, deputy dean, CODL, documents (recommended), or deputy dean first?
2. **BM**: build the toggle with BM for navigation and headings only (recommended), or full BM text per screen?
3. **Tables**: client-side sorting/paging for normal lists, server-side only for audit/notifications/UCreds (recommended).
4. **Docs**: do Decision Log v0.8 and UI/UX Spec v1.3 first (short) or at the end?

Not started until you approve. The whole-system review is still held until you ask.
