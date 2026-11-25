---
version: 3.0
name: utem-linear-precision
authority: design-samples/linear-precision/DESIGN.md (v2.4) & design-samples/linear-precision/preview.html
theme: light-only
description: "UTeM MCMS v3 Linear-Precision Design System: light-only pure white canvas (#ffffff), Jet Black typography (#090a0f), Electric Cobalt primary accent (#0052ff), Deep Cobalt Navy left navigation drawer (#0b1a3d) with 28px right-side contours and protruding white pull-tab latch, Plus Jakarta Sans typography, controlled micro-radii (2px–8px), and crisp 1px stark black card outlines (#090a0f)."
---

# UTeM MCMS Design System (`DESIGN.md` — v3 Linear-Precision)

> **Scope**: Light theme only (dark theme removed per Phase 0 Decision D1). Bilingual `EN | BM` toggle retained (Decision D2). Self-hosted `Plus Jakarta Sans` + `IBM Plex Mono` / `JetBrains Mono` stack (Decision D3).

---

## 1. Color Palette (Light Theme Only)

### 1.1 Brand & Accent
| Token | Value | Semantic Role |
| :--- | :--- | :--- |
| `--color-primary` | `#0052ff` | Electric Cobalt — primary actions, active focus, links, key highlights |
| `--color-primary-hover` | `#0045d8` | Primary hover state |
| `--color-primary-pressed` | `#0036a8` | Primary active / pressed state |
| `--color-primary-deep` | `#0a192f` | Deep Navy Obsidian — dark accents, inverse hero blocks |
| `--color-primary-tint` | `#edf4ff` | Airy ice blue wash — hovered table rows, subtle info highlights |
| `--color-primary-line` | `rgba(0, 82, 255, 0.28)` | Focus outlines and soft accent borders |
| `--color-primary-glow` | `rgba(0, 82, 255, 0.12)` | Subtle cobalt focus ring halo |

### 1.2 Surfaces & Navigation Chrome
| Token | Value | Semantic Role |
| :--- | :--- | :--- |
| `--color-canvas` | `#ffffff` | Pure white default page floor |
| `--color-canvas-subtle` | `#f8fafc` | Ghost slate-50 for alternating sections and subtle headers |
| `--color-surface-1` | `#f1f5f9` | Cool slate-100 for secondary panels, inputs, code blocks |
| `--color-surface-2` | `#e2e8f0` | Cool slate-200 for dividers and secondary hairlines |
| `--color-surface-contrast` | `#090a0f` | Jet black for inverse badges, secondary buttons, contrast modules |
| `--color-sidebar-bg` | `#0b1a3d` | Deep Cobalt Navy left navigation drawer |
| `--color-header-bg` | `#ffffff` | Pure white sticky command topbar |

### 1.3 Typography & Borders (WCAG 2.1 AA Enforced)
| Token | Value | Contrast / Rule |
| :--- | :--- | :--- |
| `--color-ink` | `#090a0f` | ~18:1 on white — primary headlines, body anchors, labels |
| `--color-ink-muted` | `#475569` | ~4.6:1 on white — secondary body copy, helper text, subtitles |
| `--color-ink-subtle` | `#94a3b8` | ~2.6:1 — **FORBIDDEN for body text**; decorative borders/rules only |
| `--color-ink-inverse` | `#ffffff` | Crisp white text on navy/cobalt/black surfaces |
| `--color-line-hairline` | `#e2e8f0` | 1px default internal divider / table row rule |
| `--color-line-strong` | `#090a0f` | 1px stark jet black structural border |
| `--color-line-accent` | `#0052ff` | 2px electric cobalt active tab / indicator stroke |
| `--color-card-border` | `#090a0f` | 1px solid crisp black card outline |

### 1.4 Semantic Feedback
| State | Text / Accent | Background Tint | Border |
| :--- | :--- | :--- | :--- |
| **Success** | `--color-success: #24a148` (dark ink `#0e6027`) | `--color-success-tint: #edfcf0` | `--color-success-border: #a7f0ba` |
| **Warning** | `--color-warning: #b28900` (dark ink `#735700`) | `--color-warning-tint: #fefdf0` | `--color-warning-border: #f1c21b` |
| **Error** | `--color-error: #da1e28` (dark ink `#a2191f`) | `--color-error-tint: #fff1f1` | `--color-error-border: #ffd7d9` |
| **Info** | `--color-info: #0052ff` (dark ink `#0036a8`) | `--color-info-tint: #edf4ff` | `--color-info-border: #d0e2ff` |

---

## 2. Typography Scale

- **Primary Font**: `--font-primary: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;`
- **Monospace Font**: `--font-mono: "JetBrains Mono", "IBM Plex Mono", Menlo, Consolas, monospace;`
- **Self-hosted local assets**: `app/public/fonts/plus-jakarta-sans-*.woff2` (weights 400, 500, 600, 700) + `ibm-plex-mono-latin-400-normal.woff2`. Zero external CDN calls.

| Role | Token | Size | Weight | Line Height | Tracking |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Display XL | `--font-size-display-xl` | `54px` | `300` | `1.15` | `-0.03em` |
| Display LG | `--font-size-display-lg` | `44px` | `300` | `1.15` | `-0.025em` |
| Display MD | `--font-size-display-md` | `32px` | `600` | `1.20` | `-0.02em` |
| Headline (H1) | `--font-size-headline` | `22px` | `700` | `1.30` | `-0.015em` |
| Section Title (H2) | `--font-size-title` | `18px` | `600` | `1.35` | `-0.01em` |
| Body | `--font-size-body` | `14px` | `400` | `1.45` | `0` |
| Small / UI Control | `--font-size-small` | `13px` | `500` | `1.45` | `0` |
| Caption / Table Head | `--font-size-caption` | `12px` | `600` | `1.40` | `0.02em` |
| Mono Code / Ref | `--font-size-mono-code` | `12px` | `500` | `1.40` | `0` |
| Eyebrow / Group Label | `--font-size-eyebrow` | `11px` | `600` | `1.30` | `0.08em` uppercase |

---

## 3. Spacing, Micro-Radius & Elevation

### 3.1 Spacing Scale (4px Base Grid)
`4px` (`xxs`) · `8px` (`xs`) · `12px` (`sm`) · `16px` (`md`) · `20px` · `24px` (`lg`) · `32px` (`xl`) · `48px` (`xxl`)

### 3.2 Controlled Micro-Radius Scale
| Token | Value | Usage |
| :--- | :--- | :--- |
| `--radius-none` | `0px` | Table cells, horizontal dividers, print sheets |
| `--radius-xs` | `2px` | Micro tags, inline metadata badges |
| `--radius-sm` | `4px` | Buttons, text inputs, selects, textareas, segmented controls |
| `--radius-md` | `6px` | Cards, panels, popovers, dropdown menus, bento tiles |
| `--radius-lg` | `8px` | Modal dialogs, hero callouts, standalone containers |
| `--radius-search-pill` | `12px` | Floating search bar container |
| `--radius-active-tab` | `12px` | Active sidebar navigation pill |
| `--radius-pull-tab-latch` | `24px` | Sidebar collapse pull-tab (`0 24px 24px 0`) |
| `--radius-sidebar-corner` | `28px` | Brand header & left sidebar right-edge contours (`0 28px 28px 0`) |
| `--radius-pill` | `9999px` | Status indicator pips ONLY (never full-pill buttons) |

### 3.3 Elevation & Shadows
- `--shadow-subtle`: `0 1px 3px rgba(0, 0, 0, 0.05)` (resting elevated controls)
- `--shadow-dropdown`: `0 8px 24px rgba(9, 10, 15, 0.12)` (menus, popovers)
- `--shadow-modal`: `0 16px 40px rgba(9, 10, 15, 0.20)` (dialogs, persona picker)

---

## 4. Component Specifications & States

- **Buttons (`.btn`)**: `4px` radius (`--radius-sm`), `13px–14px` semibold `Plus Jakarta Sans`.
  - Primary (`.btn` / `.btn.primary`): `#0052ff` fill, `#ffffff` ink; hover `#0045d8`; active `#0036a8`; focus `2px solid #0052ff` + `2px` offset.
  - Secondary (`.btn.sec`): `#090a0f` fill, `#ffffff` ink; hover `#1e293b`.
  - Tertiary / Outline (`.btn.ter`): `#ffffff` fill, `1px solid #090a0f` border, `#090a0f` ink; hover `#edf4ff` with `#0052ff` border & ink.
  - Danger (`.btn.danger`): `#da1e28` fill, `#ffffff` ink; hover `#b81921`.
  - Disabled: `opacity: 0.45`, `cursor: not-allowed`.
- **Inputs, Selects & Textareas**: `#ffffff` background, `1px solid #94a3b8` border (`#090a0f` on hover, `2px solid #0052ff` + `--color-primary-glow` on focus), `4px` radius, `38px–40px` height. Invalid state: `#da1e28` border + `#fff1f1` tint.
- **Cards & Panels (`.card`, `.panel`, `.section-card`)**: `#ffffff` surface, `1px solid var(--color-card-border)` (`#090a0f`), `6px` radius (`--radius-md`), header bar on `#f8fafc` with `1px solid #e2e8f0` bottom rule.
- **Tables**: Full-width, `#f8fafc` header (`12px` uppercase `#475569`, `1px solid #090a0f` bottom rule), `1px solid #e2e8f0` row dividers, `#edf4ff` (`--color-primary-tint`) row hover, crisp tabular numerals (`font-variant-numeric: tabular-nums`).
- **Tags & Status Pills (`.tag`, `.pill`)**: `2px` radius (`--radius-xs`), `11.5px–12px` semibold, semantic tint + border + accessible dark ink. Never wrapped in `[...]`.
- **Tabs (`.tabs`)**: Horizontal strip with `1px solid #e2e8f0` bottom hairline; active tab uses `#0052ff` text + `2px solid #0052ff` bottom bar.
- **Alerts & Notes (`.note`)**: `4px` radius, `3px` solid semantic left accent bar, semantic tint background (`--color-info-tint`, `--color-warning-tint`, `--color-success-tint`, `--color-error-tint`).
- **Modals**: `8px` radius (`--radius-lg`), `1px solid #090a0f` border, `--shadow-modal`, `#0b1a3d` backdrop with subtle blur.
- **App Shell (Logged-In)**:
  - Left Sidebar (`.side`): `#0b1a3d`, `260px` expanded / `68px` collapsed, `border-radius: 0 28px 28px 0`, pure white top-left brand header (`LogoJawiUTeM_blue-01.png` / `LogoUTeM_crest.png` collapsed), crisp white text resting items, `#ffffff` 12px-radius pill with `#0b1a3d` bold text for active item, ultra-slim 4px custom scrollbar, protruding white semi-pill pull-tab latch (`0 24px 24px 0`).
  - Top Command Bar (`.top`): Pure `#ffffff` sticky bar, `1px solid #e2e8f0` bottom rule, floating search pill (`12px` radius), `EN | BM` language switcher (no dark-mode toggle), notifications bell, and user account pill with workspace switcher.
- **Public Header**: Single-level horizontal white topbar (`LogoJawiUTeM_blue-01.png` + `UTeM MCMS` + clean links `Home`, `Catalogue`, `Pathways`, `How it works`, `FAQ`, `Verify` + `EN | BM` + `Sign In` button). No numbers in nav links.

---

## 5. Do / Don't Rules

- **DO** use crisp `1px solid #090a0f` borders on cards and primary containers.
- **DO** enforce WCAG 2.1 AA contrast (`#090a0f` or `#475569` for all readable copy; `#94a3b8` for borders/hairlines only).
- **DO** keep `EN | BM` language toggle working across all screens.
- **DO** use institutional spelling `"enroll"` / `"enrollment"`.
- **DO** keep print views (`?print=1`) clean black-on-white with `0px` shadows and no sidebar/topbar chrome.
- **DO** enforce **Rule D-67 (Bounded Page & Progressive Disclosure)**: no page may render unbounded content at once. All thresholds live in a single config file (`app/config/ui.php`): max **12 cards/page** desktop (**6** mobile), **0 tables inside repeating cards**, **10 initial table rows** (max 25), **1 global search bar** per screen, and **≤ 4 open sections** (use tabs, pagination, or dedicated detail pages).
- **DO** enforce **Information Creation & Dependency Order (D-70)**: steps, tabs, sections, validation checks, and generated document sections must follow the order in which information is created. Anything that uses, maps, or evaluates information must come after the place where it is entered, and dependent tabs must state clearly what they need from earlier tabs with a direct link when empty. Check every new page against this rule; never display rule numbers or guide references in the interface.
- **DO** enforce **Generated Document Layout & Zero-Truncation (D-71)** and **Per-Document Language (D-72)**: all generated documents extend `layouts/print.blade.php`, never truncate text with `"..."` or drop items, repeat `<thead>` across page breaks, automatically promote wide tables (`>= 6` cols, `data-matrix` `>= 4` cols, or `< 28mm` col width) to A4 Landscape via `@page a4-landscape` (chunking matrices `> 5` data columns into `Part X of Y` sub-tables) while keeping surrounding pages Portrait, and provide a per-document `EN | BM` toggle (`?doc_lang=en|ms`) with zero mixed EN/BM system text and no silent machine translation of user-typed content.
- **DO** enforce **Inline Table Sort & Shared Toolbar (D-73)** and **Unified Search, Filter & Sort Toolbar (D-74)**: every table or list that can grow has one single-row toolbar containing **Search**, **Filter**, and **Sort** built from its own column headers (`.tbar` / `.tbar-filter` / `.tbar-sort`), never clickable sort arrows in `<th>` headers and never separate filter dropdowns outside the toolbar. Search uses a contextual header-derived placeholder; Filter adapts per column type (choice lists with live counts, date ranges, number ranges, text matches) with removable active-filter chips and `Clear all`; Sort uses true semantic values (dates, numbers, workflow status order, `Intl.Collator` text) with empty values always last and a stable tie-breaker; role-hidden columns and non-growing `data-plain` summary tables are excluded; mobile stacks cleanly. Check every new table against these rules; never show rule numbers in the UI.
- **DO** enforce **Pop-Out Warning/Error Toasts & Tabbed Review Workspaces (D-75)**: every validation error or warning pops out as a visible floating toast (`.toast.err` / `.toast.warn`) with hover-paused auto-dismiss and `×` close (plus dismissible, auto-fading top inline banner and deduplicated error bullets), and multi-section review pages partition reference summaries and review forms into horizontal Carbon section tabs. Never show the rule number in the UI.
- **DO** enforce **Filter Eligibility & Bounded Filter Panel (D-76)**: filters hold only repeating taxonomy values (`≤ 8` chips, Status first, or compact searchable dropdown for explicit borderline entity filters), dates as ranges/presets, and numbers as Min/Max ranges; identifying and free-text columns (`Reference`, `Programme`, `Proposer`, `Name`, `Title`, `Email`, etc.) are search-only so filter panels never grow with data; hide the Filter button when no column qualifies. Never show the rule number in the UI.
- **DO** use proper singular/plural wording (`1 row` vs `4 rows`, `1 micro-credential mapped` vs `4 micro-credentials mapped`).
- **DON'T** re-introduce dark mode CSS blocks or theme toggle buttons.
- **DON'T** use generic emojis (`⏱`, `🔒`, `⚖️`, `💡`, `👉`) or decorative SVGs (Rule D-53).
- **DON'T** show regulatory guide references (`Appendix A–H`, `Lampiran A–H`, `B1.`–`H9` prefixes, `Cl.`/`Klausa`, `Garis Panduan`) anywhere in UI labels, checklists, headings, buttons, validation/error messages, PDFs/printouts, receipts, emails, or notifications (Rule D-64). Use plain, self-explanatory wording.
- **DON'T** wrap badges, tags, or buttons in square brackets `[...]` (use `FTMK`, `MQF 6`, `UMC`, `SAMC`, `Sign In`).
- **DON'T** add redundant context blocks ("Where is this / What happens next / Who is responsible").
- **DON'T** modify element `id`, `name`, `data-*`, or `aria-label` hooks frozen in `docs/redesign-v3/SELECTOR_FREEZE.md`.

---

## 6. Mechanical Token Mapping Table (Legacy Carbon → v3 Linear-Precision)

Use this 1:1 mapping in **Phase 2** when swapping `:root` tokens in `app/public/css/mcms.css`:

| Legacy Token (`mcms.css`) | Legacy Value | New v3 Value | v3 Canonical Alias |
| :--- | :--- | :--- | :--- |
| `--primary` | `#1e2a6e` | `#0052ff` | `var(--color-primary)` |
| `--primary-hover` | `#18225a` | `#0045d8` | `var(--color-primary-hover)` |
| `--blue-60` | `#2e3d94` | `#0045d8` | `var(--color-primary-hover)` |
| `--on-primary` | `#fff` | `#ffffff` | `var(--color-ink-inverse)` |
| `--ink` / `--text` | `#161616` | `#090a0f` | `var(--color-ink)` |
| `--muted` / `--ink-muted` / `--ink-light` | `#525252` | `#475569` | `var(--color-ink-muted)` |
| `--subtle` | `#6f6f6f` | `#475569` *(text-safe)* | `var(--color-ink-muted)` |
| `--canvas` / `--bg` / `--card-bg` / `--top-bg` / `--panel-bg` / `--pop-bg` / `--table-bg` | `#fff` | `#ffffff` | `var(--color-canvas)` |
| `--surface` / `--s1` / `--surface-1` / `--bg-subtle` | `#f4f4f4` | `#f1f5f9` | `var(--color-surface-1)` |
| `--table-header-bg` | `#f4f4f4` | `#f8fafc` | `var(--color-canvas-subtle)` |
| `--table-hover` | `#fafafa` | `#edf4ff` | `var(--color-primary-tint)` |
| `--notif-unread` | `#f7f8fc` | `#edf4ff` | `var(--color-primary-tint)` |
| `--s2` / `--surface-2` / `--hairline` / `--hair` / `--border` | `#e0e0e0` | `#e2e8f0` | `var(--color-line-hairline)` |
| `--rule` / `--rule-light` | `#ececec` | `#e2e8f0` | `var(--color-line-hairline)` |
| `--hairline-strong` | `#161616` | `#090a0f` | `var(--color-line-strong)` |
| `--control` | `#8d8d8d` | `#94a3b8` | `var(--color-ink-subtle)` |
| `--success` / `--ok` / `--green` | `#24a148` | `#24a148` | `var(--color-success)` |
| `--ok-bg` | `#defbe6` | `#edfcf0` | `var(--color-success-tint)` |
| `--ok-ink` | `#0e6027` | `#0e6027` | *(accessible dark green)* |
| `--warn` / `--warning` / `--gold` | `#f1c21b` | `#b28900` | `var(--color-warning)` |
| `--warn-bg` | `#fcf4d6` | `#fefdf0` | `var(--color-warning-tint)` |
| `--warn-ink` / `--warn-dark` | `#684e00` | `#735700` | *(accessible dark amber)* |
| `--error` / `--err` / `--danger` / `--red` | `#da1e28` | `#da1e28` | `var(--color-error)` |
| `--err-bg` | `#fff1f1` | `#fff1f1` | `var(--color-error-tint)` |
| `--err-ink` | `#a2191f` | `#a2191f` | *(accessible dark red)* |
| `--info-bg` | `#e8eaf6` | `#edf4ff` | `var(--color-info-tint)` |
| `--n-bg` | `#e8e8e8` | `#f1f5f9` | `var(--color-surface-1)` |
| `--n-ink` | `#393939` | `#475569` | `var(--color-ink-muted)` |
| `--lang-active-bg` / `--avatar-bg` | `#161616` | `#0052ff` / `#0b1a3d` | `var(--color-primary)` / `var(--color-sidebar-bg)` |
| `--doc-bar-bg` / `--footer-bg` | `#161616` | `#0b1a3d` | `var(--color-sidebar-bg)` |
| `--sans` | `"IBM Plex Sans", …` | `"Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif` | `var(--font-primary)` |
| `--mono` | `"IBM Plex Mono", …` | `"JetBrains Mono", "IBM Plex Mono", Menlo, Consolas, monospace` | `var(--font-mono)` |
| `--shadow` | `0 1px 3px rgba(0,0,0,0.1)` | `0 1px 3px rgba(0,0,0,0.05)` | `var(--shadow-subtle)` |
| `[data-theme="dark"]` & `@media (prefers-color-scheme: dark)` | Carbon Gray 90/100 | **REMOVED** (light-only) | — |
