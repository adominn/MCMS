---
version: alpha
name: ulearn-carbon
# Based on VoltAgent/awesome-design-md IBM (Carbon); accent swapped from #0f62fe to uLearn-ICTM navy (estimated from screenshots).
description: "An enterprise-marketing canvas faithful to Carbon Design System: white surfaces, charcoal type, uLearn Navy (#1e2a6e) as the single confident accent, and a deliberately flat-square aesthetic where corners stay at 0–4px. Type runs IBM Plex Sans at light weight 300 for display sizes (a brand signature) and 400/600 for body and emphasis. Cards live as thin-bordered tiles with no shadow; sections separate via subtle gray rows. The chrome is square, the typography is light, and the only color in the system is one assertive blue — the result reads as old-world enterprise gravitas reframed for the cloud era."

colors:
  primary: "#1e2a6e"
  on-primary: "#ffffff"
  ink: "#161616"
  ink-muted: "#525252"
  ink-subtle: "#8c8c8c"
  canvas: "#ffffff"
  surface-1: "#f4f4f4"
  surface-2: "#e0e0e0"
  inverse-canvas: "#161616"
  inverse-surface-1: "#262626"
  inverse-ink: "#ffffff"
  inverse-ink-muted: "#c6c6c6"
  hairline: "#e0e0e0"
  hairline-strong: "#161616"
  blue-60: "#2e3d94"
  blue-80: "#121a45"
  blue-hover: "#18225a"
  semantic-success: "#24a148"
  semantic-warning: "#f1c21b"
  semantic-error: "#da1e28"
  semantic-info: "#1e2a6e"

typography:
  display-xl:
    fontFamily: IBM Plex Sans
    fontSize: 76px
    fontWeight: 300
    lineHeight: 1.17
    letterSpacing: -0.5px
  display-lg:
    fontFamily: IBM Plex Sans
    fontSize: 60px
    fontWeight: 300
    lineHeight: 1.17
    letterSpacing: -0.4px
  display-md:
    fontFamily: IBM Plex Sans
    fontSize: 42px
    fontWeight: 300
    lineHeight: 1.20
    letterSpacing: 0
  headline:
    fontFamily: IBM Plex Sans
    fontSize: 32px
    fontWeight: 400
    lineHeight: 1.25
    letterSpacing: 0
  card-title:
    fontFamily: IBM Plex Sans
    fontSize: 24px
    fontWeight: 400
    lineHeight: 1.33
    letterSpacing: 0
  subhead:
    fontFamily: IBM Plex Sans
    fontSize: 20px
    fontWeight: 400
    lineHeight: 1.40
    letterSpacing: 0
  body-lg:
    fontFamily: IBM Plex Sans
    fontSize: 18px
    fontWeight: 400
    lineHeight: 1.50
    letterSpacing: 0
  body:
    fontFamily: IBM Plex Sans
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.50
    letterSpacing: 0.16px
  body-sm:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.29
    letterSpacing: 0.16px
  body-emphasis:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: 600
    lineHeight: 1.29
    letterSpacing: 0.16px
  caption:
    fontFamily: IBM Plex Sans
    fontSize: 12px
    fontWeight: 400
    lineHeight: 1.33
    letterSpacing: 0.32px
  button:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.29
    letterSpacing: 0.16px
  eyebrow:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.29
    letterSpacing: 0.16px

rounded:
  none: 0px
  xs: 2px
  sm: 4px
  md: 6px
  lg: 8px
  pill: 9999px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 96px

components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-primary-pressed:
    backgroundColor: "{colors.blue-80}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
  button-secondary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.inverse-ink}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-tertiary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-ghost:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-danger:
    backgroundColor: "{colors.semantic-error}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  feature-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 24px
  feature-card-elevated:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 24px
  product-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 32px
  hero-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.display-md}"
    rounded: "{rounded.none}"
    padding: 48px
  cta-banner:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.headline}"
    rounded: "{rounded.none}"
    padding: 48px
  text-input:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  text-input-focused:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  text-input-error:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  newsletter-input:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  product-tab:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 16px 20px
  product-tab-selected:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-emphasis}"
    rounded: "{rounded.none}"
    padding: 16px 20px
  tab-bar:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    height: 48px
  tab-item:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 12px 20px
  tab-item-selected:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-emphasis}"
    rounded: "{rounded.none}"
    padding: 12px 20px
  dropdown-switcher:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 8px 12px
  section-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 24px
  data-table:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
  data-table-header:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body-emphasis}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  badge-pill:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.caption}"
    rounded: "{rounded.pill}"
    padding: 4px 8px
  accordion-row:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 16px 20px
  pagination-bar:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 8px 16px
  button-secondary-outline:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-utility:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 8px 12px
  button-compact:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 8px 12px
  resource-tile:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 16px
  customer-logo-tile:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.caption}"
    rounded: "{rounded.none}"
    padding: 24px
  top-nav:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    height: 48px
  utility-bar:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.caption}"
    rounded: "{rounded.none}"
    height: 32px
  footer:
    backgroundColor: "{colors.inverse-canvas}"
    textColor: "{colors.inverse-ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 64px 32px
---

## Overview

IBM's marketing system is a faithful application of **Carbon Design System** — IBM's open-source enterprise design system. The dominant surface is `{colors.canvas}` pure white with `{colors.surface-1}` light gray for elevation, charcoal `{colors.ink}` (#161616) for text, and uLearn Navy `{colors.primary}` (#1e2a6e) as the single brand accent.

The defining choice is **flat geometry**: every CTA, every card, every input, every container uses square corners (`{rounded.none}` 0px) with thin 1px borders. There are no rounded pills, no soft shadows, no atmospheric gradients. The system is engineered, not stylized.

**IBM Plex Sans** carries the entire type hierarchy. Display sizes (76 / 60 / 42px) run at weight **300** — IBM's signature light display treatment that makes 76px feel calmer than competing brands' 700-weight display. Body type sits at weight 400 with `letter-spacing: 0.16px` (a Carbon precision detail) and line-height 1.50. The voice reads as careful, technical, and trustworthy.

The system reaches for color rarely — uLearn Navy marks links, primary CTAs, and the rare full-bleed CTA banner. Charcoal carries every other surface that isn't white. The result is enterprise gravitas without the enterprise stiffness: rigorous, light-weighted, and intentionally restrained.

**Key Characteristics:**
- **Carbon Design System** — IBM's marketing chrome IS Carbon. Buttons are square, inputs are square-with-bottom-rule, corners stay at 0px.
- **Light-weight display type**: Plex Sans at weight 300 for 42–76px headlines is the brand's typographic signature.
- **One accent color**: `{colors.primary}` uLearn Navy carries every link, primary CTA, and CTA banner. There is no second brand color.
- White canvas + light gray (`{colors.surface-1}`) + charcoal (`{colors.ink}`) cover 95% of surfaces.
- Footer inverts to charcoal (`{colors.inverse-canvas}` #161616) — the only dark surface above the page break.
- Card hierarchy is carried by 1px hairlines and surface change, never by drop shadow.
- `letter-spacing: 0.16px` on body is a Carbon precision detail — the small positive tracking is part of the brand voice.
- Page rhythm: utility bar → top nav → hero with light-weight headline → feature card grid → customer logo marquee → enterprise feature row → training section → newsletter / sign-in CTA → dark footer.

## Colors

> Source pages: ibm.com (home), /software/ai-productivity, /consulting, /products/cloud-pak-for-aiops, /products/bare-metal-servers, community.ibm.com.

### Brand & Accent
- **uLearn Navy** ({colors.primary}): The single brand accent. Links, primary CTAs, CTA banner backgrounds, focus rings.
- **Blue 60** ({colors.blue-60}): Hovered link state.
- **Blue 80** ({colors.blue-80}): Pressed primary button.
- **Blue Hover** ({colors.blue-hover}): Hover state for primary buttons.

### Surface
- **Canvas** ({colors.canvas}): Default page background.
- **Surface 1** ({colors.surface-1}): Light gray (#f4f4f4) — input fields, alternate-row stripes, subtle section bands.
- **Surface 2** ({colors.surface-2}): Slightly darker gray (#e0e0e0) — disabled fields, hairline-as-fill for separators.
- **Hairline** ({colors.hairline}): 1px borders on cards, inputs, dividers.
- **Hairline Strong** ({colors.hairline-strong}): 1px charcoal underline on focused inputs (Carbon's signature focus treatment).
- **Inverse Canvas** ({colors.inverse-canvas}): Charcoal #161616 — footer surface.
- **Inverse Surface 1** ({colors.inverse-surface-1}): One step lighter than inverse canvas — footer column dividers, hovered footer items.

### Text
- **Ink** ({colors.ink}): All headlines and emphasized body type — charcoal #161616.
- **Ink Muted** ({colors.ink-muted}): Secondary type at #525252 — meta, sub-headlines, footer body.
- **Ink Subtle** ({colors.ink-subtle}): Tertiary type at #8c8c8c — disabled, helper text, captions.
- **Inverse Ink** ({colors.inverse-ink}): White on charcoal — footer headings.
- **Inverse Ink Muted** ({colors.inverse-ink-muted}): Light gray on charcoal — footer body.

### Semantic
- **Success Green** ({colors.semantic-success}): Carbon green-50 — success states.
- **Warning Yellow** ({colors.semantic-warning}): Carbon yellow-30 — warning states.
- **Error Red** ({colors.semantic-error}): Carbon red-60 — error states; danger button background.
- **Info Blue** ({colors.semantic-info}): Identical to primary — informational badges.

## Typography

### Font Family

- **IBM Plex Sans** — IBM's open-source proprietary typeface (free for any use). Geometric, slightly humanist, designed specifically for enterprise UI. Fallback: `Helvetica Neue, Arial, sans-serif`.

The same family carries display, body, and caption — there is no display + body pairing. Hierarchy is carried by **size + weight** rather than by family change. Plex Sans is also free / open-source under the SIL Open Font License — making it the easiest custom face on this list to substitute for in implementation.

### Hierarchy

| Token | Size | Weight | Line Height | Letter Spacing | Use |
|---|---|---|---|---|---|
| `{typography.display-xl}` | 76px | 300 | 1.17 | -0.5px | Largest hero headline |
| `{typography.display-lg}` | 60px | 300 | 1.17 | -0.4px | Section opener headlines |
| `{typography.display-md}` | 42px | 300 | 1.20 | 0 | Sub-section headlines, hero card title |
| `{typography.headline}` | 32px | 400 | 1.25 | 0 | Card collection heading, FAQ category |
| `{typography.card-title}` | 24px | 400 | 1.33 | 0 | Feature card title |
| `{typography.subhead}` | 20px | 400 | 1.40 | 0 | Lead body next to display headlines |
| `{typography.body-lg}` | 18px | 400 | 1.50 | 0 | Hero subhead, lead paragraphs |
| `{typography.body}` | 16px | 400 | 1.50 | 0.16px | Default body |
| `{typography.body-sm}` | 14px | 400 | 1.29 | 0.16px | Card body, footer columns |
| `{typography.body-emphasis}` | 14px | 600 | 1.29 | 0.16px | Selected tab label, emphasized body line |
| `{typography.caption}` | 12px | 400 | 1.33 | 0.32px | Captions, meta, utility bar |
| `{typography.button}` | 14px | 400 | 1.29 | 0.16px | All button labels |
| `{typography.eyebrow}` | 14px | 400 | 1.29 | 0.16px | Section eyebrows (Carbon avoids strong eyebrows; uses sentence case 14px) |

### Principles

- **Light-weight display is the brand voice.** Plex Sans at weight 300 for 76px headlines reads as quietly authoritative — switching to 700 would make it look like every other enterprise site.
- **Carbon's `letter-spacing: 0.16px`** on body sizes is a precision detail. Don't remove it.
- **No mono** on marketing surfaces (Plex Mono exists but lives in product surfaces only).
- **Eyebrow typography uses sentence case 14px** — Carbon resists the all-caps tracked eyebrow common to other enterprise brands.
- **Line-heights tighten on display, relax on body**: 1.17 at display-xl, 1.50 at body — proportional to size.

### Note on Font Substitutes

IBM Plex Sans is **free and open-source** (SIL OFL license) and available on Google Fonts. It is the recommended implementation. The Plex family also includes Plex Mono and Plex Serif if expanded typographic needs arise.

## Layout

### Spacing System

- **Base unit**: 4px (Carbon's signature 4-pixel grid).
- **Tokens (front matter)**: `{spacing.xxs}` 4px · `{spacing.xs}` 8px · `{spacing.sm}` 12px · `{spacing.md}` 16px · `{spacing.lg}` 24px · `{spacing.xl}` 32px · `{spacing.xxl}` 48px · `{spacing.section}` 96px.
- Card interior padding: `{spacing.lg}` 24px on feature cards; `{spacing.xl}` 32px on product cards; `{spacing.xxl}` 48px on hero cards and CTA banners.
- Button padding: 12px vertical · 16px horizontal — Carbon spec.
- Form input padding: 11px vertical · 16px horizontal.

### Grid & Container

- Carbon's 16-column grid at desktop, scaling to 8 / 4 columns at tablet / mobile.
- Max content width sits around 1584px (Carbon's max-grid breakpoint).
- Card grids are 4-up at desktop, 2-up at tablet, 1-up at mobile.
- The customer logo marquee uses fixed-width tiles in a flex row, scrolling horizontally on smaller viewports.

### Whitespace Philosophy

Carbon uses precise alignment to a 4-pixel grid as its whitespace system. Sections separate via thin gray rows (`{colors.surface-1}`) rather than via large vertical gaps. Content is dense by design — IBM's customers expect to see a lot on a page, not a lot of air.

### Layout Decision Matrix: When to Use Square Grids vs. Filtered Lists

Carbon layout density must be intentionally matched to the page context and interaction model. **Do NOT force every list into square cards.** Stacking square cards in a narrow column beside a sidebar filter wastes vertical space and creates awkward layouts. Instead, follow this decision matrix:

| Page Context | Recommended Layout | Geometry & Density | Mandatory Navigation / Controls |
|---|---|---|---|
| **Standalone Summaries & Portals** (No sidebar filter) | **Responsive Multi-Column Card Grid** (2, 3, or 4 columns) | Square tiles, 1px `{colors.hairline}`, `{spacing.lg}` (24px) padding, balanced horizontal distribution | Section anchors or simple categorical view switches |
| **Filtered Search & Directory Views** (With left filter sidebar) | **Clean Vertical List / Row Layout** | Full-width horizontal rows, 1px hairline bottom borders, horizontally aligned metadata | **Mandatory Carbon Pagination Bar** (items-per-page, total count, numbered navigation) |
| **Multi-Dataset Management Views** (Registrations, cohorts, evaluations) | **Dropdown Entity Switcher + Section Card** | Single active section card containing a flat Carbon data table | Dropdown select with inline label; eliminates vertical table stacking |
| **Multi-Faceted Entity Views** (Course details, student dossier) | **Horizontal Carbon Tab Bar** | 48px horizontal tab strip above data view | Flat Carbon tabs with 2px `{colors.primary}` active bottom rule |

#### Standalone Summaries & Portals (Grid Tiles)
- For high-level overviews, rule lists, public landing showcases, or role dashboards without a persistent filter sidebar, organize items into **responsive multi-column card grids (2, 3, or 4 columns)** (`grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))`).
- This eliminates empty horizontal dead space, balances visual weight, and provides clear scannable tiles.

#### Filtered Views with Left Sidebar (Vertical List + Mandatory Pagination)
- When a page features a left filter sidebar (e.g., catalogue search, student directory browsing, course repository), the main results area MUST be rendered as a **clean vertical list / row layout** (not square tiles).
- Rows provide ample horizontal space to cleanly align title, metadata pills (MQF level, credits, delivery mode), fee, and tactile CTA buttons without vertical crowding.
- **Mandatory Pagination:** Stacking dozens of rows down an endlessly scrolling page is strictly prohibited. Filtered lists must feature a Carbon-styled **pagination bar** at the bottom:
  - Left: Items per page selector dropdown (`10 | 20 | 50 items per page`).
  - Center: Range and total count (`1–10 of 48 items`).
  - Right: Numbered page navigation controls (`‹ Prev [1] [2] [3] Next ›`) with active page highlight.

## Elevation & Depth

| Level | Treatment | Use |
|---|---|---|
| 0 (flat) | No shadow, no border | Default for body type, hero text, footer body |
| 1 (hairline) | 1px `{colors.hairline}` border on canvas | Feature cards, inputs, list items |
| 2 (surface lift) | `{colors.surface-1}` background on canvas | Alternate-row banners, hovered cards |
| 3 (focus ring) | 2px `{colors.primary}` outline + 1px `{colors.hairline-strong}` underline | Focused input, focused button |

Carbon resists drop shadows on marketing — depth is carried by surface change and 1px hairlines. The exception is product / app surfaces (Carbon documents shadow tokens for elevated panels), but the marketing site barely uses them.

### Decorative Depth

- **Soft blue gradient backdrops** appear behind some hero illustrations — a faint blue-to-white wash that warms the canvas without competing with the headline.
- **No atmospheric depth.** No spotlight cards, no pastel section blocks, no gradient panels.

## Shapes

### Border Radius Scale

| Token | Value | Use |
|---|---|---|
| `{rounded.none}` | 0px | Default — every button, card, input, container |
| `{rounded.xs}` | 2px | Small badges (rare exception) |
| `{rounded.sm}` | 4px | Avatar circles squared, dropdown menus |
| `{rounded.md}` | 6px | (Used rarely; documented for completeness) |
| `{rounded.lg}` | 8px | (Used rarely; documented for completeness) |
| `{rounded.pill}` | 9999px | Status pills, badges in product UI (rare on marketing) |

The brand commits to flat 0px corners. The other tokens exist for product / mobile surfaces but rarely surface on marketing.

### Photography & Illustration Geometry

- IBM uses photography (people, hardware, sports cars) and abstract illustration (geometric mesh, dotted patterns) interchangeably.
- Image frames are flat — no rounded corners.
- Customer logo tiles sit on `{rounded.none}` 0px tiles with thin 1px borders.

## Components

### Buttons & Interactive Controls (Tactile Button System)

**Prohibition of Naked Hyperlinks:** Raw browser hyperlinks (e.g., standard underlined blue text) are strictly prohibited for interactive actions, primary navigation, or workflow triggers (e.g., never output naked links like "Public site Sign in" or "See the full catalogue"). Every interactive trigger and CTA must be rendered through Carbon's tactile button system with flat geometry (`{rounded.none}` 0px), explicit padding, hover transitions, and `cursor: pointer`.

**`button-primary`** — Blue solid CTA. The default primary across all pages and main workflow transitions.
- Background `{colors.primary}`, text `{colors.on-primary}`, type `{typography.button}`, padding 12px 16px (or compact 8px 12px), rounded `{rounded.none}`.
- Hover state: `{colors.blue-hover}`. Pressed state: `button-primary-pressed` (`{colors.blue-80}`).

**`button-secondary`** & **`button-secondary-outline`** — Charcoal solid button or outlined clean white button.
- Solid variant: Background `{colors.ink}`, text `{colors.inverse-ink}`, type `{typography.button}`, padding 12px 16px, rounded `{rounded.none}`.
- Outlined variant: Background `{colors.canvas}`, border 1px solid `{colors.ink}`, text `{colors.ink}`, type `{typography.button}`, padding 12px 16px (or 8px 12px), rounded `{rounded.none}`. Hover state transitions to `{colors.surface-1}` fill.

**`button-tertiary`** — White button with blue 1px border + blue text. Used for tertiary CTAs and filter controls.
- Background `{colors.canvas}`, border 1px solid `{colors.primary}`, text `{colors.primary}`, type `{typography.button}`, rounded `{rounded.none}`, padding 12px 16px.

**`button-ghost`** & **`button-utility`** — Subtle utility triggers with no default background fill.
- Background `{colors.surface-1}` or `{colors.canvas}`, text `{colors.ink}`, type `{typography.button}`, rounded `{rounded.none}`, padding 8px 12px, border 1px solid `{colors.hairline}`.
- Hover state: background shifts to `{colors.surface-2}` with clear tactile feedback. Used for header switches, pagination controls, table row actions, and dismiss triggers.

**`button-danger`** — Carbon's destructive variant.
- Background `{colors.semantic-error}`, text `{colors.on-primary}`, type `{typography.button}`, rounded `{rounded.none}`, padding 12px 16px.

### Dropdown Entity Switcher & Card Section Pattern

When a page manages multiple datasets, cohorts, or records (e.g., viewing a learner's multiple course registrations, switching between offering cohorts, or switching assessment rubrics), **never stack multiple heavy data tables vertically down the page**. Vertical table stacking produces visual clutter, unmanageable scroll length, and redundant header chrome.

Instead, implement the **Dropdown Entity Switcher & Card Section Pattern**:
1. **Dropdown View Switcher (`dropdown-switcher`):**
   - Position an inline Carbon select control prominently at the top of the workspace:
     `Registration: [ Data Structures 2026/2 (E-D-1) ▾ ]`
   - Flat geometry (`{rounded.none}` 0px), `{colors.surface-1}` ground, 1px `{colors.hairline}` border, `{typography.body-sm}`, and an embedded chevron.
   - Selecting a new entity immediately swaps the active dataset within the same section card without page-level layout shifts.
2. **Structured Section Card (`section-card`):**
   - Encapsulate the active dataset inside a unified white container on `{colors.canvas}` with 1px `{colors.hairline}` border and `{spacing.lg}` (24px) padding:
     - **Header Row:** Bold title on the left (`{typography.subhead}` or `{typography.card-title}`) paired with a metadata counter badge on the right (e.g., `<span class="badge info">3 verified</span>`).
     - **Flat Data Table (`data-table`):** Carbon data table with sortable column headers (`Assessment ↕`, `Mark ↕`, `Pass mark ↕`, `Result ↕`) and semantic status pills (`badge-pill`) in soft green `{colors.semantic-success}` for `Met` or warning `{colors.semantic-warning}` for `Below target`.
     - **Footer Context Note:** Subtle italic caption (`{typography.caption}` in `{colors.ink-muted}`) positioned directly below the table to communicate procedural rules and institutional guardrails (e.g., *"Progress alone never gives a pass; the deputy dean confirms completion."*).

### Horizontal Carbon Tab Bar Pattern

When an entity has multiple related facets, lifecycle stages, or sub-datasets (e.g., a micro-credential offering workspace encompassing `Modules`, `Verified results`, `Credentials awarded`, and `Quality review`), group them horizontally using Carbon tab bars rather than stacking sections vertically.

- **Tab Strip Container (`tab-bar`):**
  - Height: 48px.
  - Border: 1px bottom `{colors.hairline}` extending across the full container width.
  - Background: `{colors.canvas}`.
- **Selected Tab (`tab-item-selected`):**
  - Text: Bold `{typography.body-emphasis}` (weight 600) in charcoal `{colors.ink}` (`#161616`).
  - Active Indicator: Solid 2px bottom border rule in uLearn Navy (`{colors.primary}` / `#1e2a6e`) aligned flush to the bottom border.
  - Background: Flat `{colors.canvas}`, borderless sides and top.
- **Unselected Tabs (`tab-item`):**
  - Text: Regular `{typography.body-sm}` (weight 400) in muted gray `{colors.ink-muted}` (`#525252`).
  - Hover: Text darkens to `{colors.ink}` with subtle `{colors.surface-1}` background wash.
  - Prohibitions: No rounded tabs, no pill-shaped tabs, no drop shadows, and no floating background indicator blocks.

### Progressive Disclosure & Hierarchical Accordions

For deep hierarchical data trees, extensive syllabus outlines, or multi-course programme mappings:
- **Collapsed by Default:** The default state for hierarchical accordion rows must be **collapsed** to keep the initial page height clean, scannable, and compact.
- **Accordion Row Component (`accordion-row`):**
  - Header: Displays entity title (`MTDL Master of Technology`), an inline summary count badge (`[ 7 micro-credentials ▾ ]`), and an expandable chevron.
  - Surface: Flat white `{colors.canvas}` with 1px `{colors.hairline}` borders.
  - Expanded Content: Slides down to reveal nested course rows, learning outcomes, or credit breakdown tables without displacing adjacent page structures.

### Data Tables & Pagination Bar Pattern

All data tables must conform to Carbon's flat grid standards:
- **Header Row (`data-table-header`):** Grounded on `{colors.surface-1}` with `{typography.body-emphasis}`, 1px hairline bottom divider, and sort indicators (`↕`).
- **Data Rows:** Flat `{colors.canvas}` with subtle hover state (`{colors.surface-1}`). Text uses `{typography.body-sm}`.
- **Status Pills (`badge-pill`):** High-contrast, semantic color badges with square or pill geometry (`{rounded.pill}` 9999px or `{rounded.xs}` 2px) indicating lifecycle state.
- **Mandatory Pagination Bar (`pagination-bar`):**
  - Background: `{colors.surface-1}` with 1px top `{colors.hairline}`.
  - Left: Items per page selector dropdown (`10 | 20 | 50 items per page`).
  - Center: Range and total count (`1–10 of 48 items`).
  - Right: Numbered page navigation controls (`‹ Prev [1] [2] [3] Next ›`) with active page highlight.

### Cards & Containers

**`feature-card`** — Default feature highlight tile on the home and product pages.
- Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.body}`, rounded `{rounded.none}`, padding 24px. Stroked with 1px `{colors.hairline}`.

**`feature-card-elevated`** — Same shape on `{colors.surface-1}` ground — used for "Recommended" cards in the latest-content carousel.
- Background `{colors.surface-1}`, otherwise identical structure.

**`product-card`** — Larger product showcase tile.
- Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.body}`, rounded `{rounded.none}`, padding 32px.

**`hero-card`** — Hero composition card with light-weight title, body, and CTA.
- Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.display-md}`, rounded `{rounded.none}`, padding 48px.

**`cta-banner`** — Full-width blue CTA panel near the bottom of the page.
- Background `{colors.primary}`, text `{colors.on-primary}`, type `{typography.headline}`, rounded `{rounded.none}`, padding 48px.

**`resource-tile`** — Smaller article / case-study tile.
- Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.body-sm}`, rounded `{rounded.none}`, padding 16px.

**`customer-logo-tile`** — Single tile in the customer marquee on the home page (Ferrari, Pfizer, etc.).
- Background `{colors.canvas}`, text `{colors.ink-muted}`, type `{typography.caption}`, rounded `{rounded.none}`, padding 24px. 1px hairline border.

### Inputs & Forms

**`text-input`** + **`text-input-focused`** + **`text-input-error`** — Carbon's input chrome.
- Background `{colors.surface-1}`, text `{colors.ink}`, type `{typography.body}`, rounded `{rounded.none}`, padding 11px 16px.
- Focus state replaces the bottom 1px hairline with a 2px `{colors.primary}` underline (Carbon's signature focus treatment).
- Error state adds 2px `{colors.semantic-error}` bottom underline.

**`newsletter-input`** — The "Stay connected" newsletter capture on the home page.
- Background `{colors.surface-1}`, text `{colors.ink}`, type `{typography.body}`, rounded `{rounded.none}`, padding 11px 16px. Adjacent submit is `button-primary`.

### Tabs

**`tab-bar`**, **`tab-item`**, **`tab-item-selected`**, **`product-tab`** & **`product-tab-selected`** — The horizontal tab strip on entity workspaces, product pages, and the home "Recommended" carousel.
- Default: `{colors.canvas}` background, `{colors.ink-muted}` text, `{typography.body-sm}`, rounded `{rounded.none}`, padding 12px 20px (or 16px 20px on marketing). Bottom 1px `{colors.hairline}`.
- Selected: `{colors.canvas}` background, `{colors.ink}` text, `{typography.body-emphasis}` weight (600), bottom 2px `{colors.primary}` active underline rule. Same padding / rounding. Borderless sides and top.

### Navigation

**`top-nav`** — Sticky white bar with the IBM logomark left, nav categories center, and search / sign-in icons right.
- Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.body-sm}`, height 48px. 1px bottom hairline.

**`utility-bar`** — Slim gray ribbon above the top nav with location switch, contact, search shortcuts.
- Background `{colors.surface-1}`, text `{colors.ink-muted}`, type `{typography.caption}`, height 32px.

### Footer

**`footer`** — Charcoal footer (`{colors.inverse-canvas}`) with the IBM wordmark left and 5–6 columns of caption-sized links. The only inverted surface above the page break.
- Background `{colors.inverse-canvas}`, text `{colors.inverse-ink-muted}`, type `{typography.body-sm}`, padding 64px 32px.

## Do's and Don'ts

### Do

- Use `{rounded.none}` 0px on every CTA, card, input, and container. The flat-square aesthetic is the brand.
- Pair Plex Sans weight 300 for display sizes (42px+) with weight 400 for body. Resist the urge to bold the headline.
- Reserve `{colors.primary}` uLearn Navy for primary CTAs, links, focused-input underlines, and CTA banner. Do not use it as a card background or eyebrow color.
- Apply `letter-spacing: 0.16px` to body sizes. It's a Carbon precision detail and part of the typographic voice.
- Use surface change (`canvas` → `surface-1`) and 1px hairlines for card hierarchy. Skip drop shadows.
- Stick to sentence case for eyebrows and section labels — Carbon resists all-caps tracking.
- Invert to `{colors.inverse-canvas}` only at the footer; the rest of the page stays light.
- Organise standalone summaries and role dashboards into responsive multi-column card grids (2, 3, or 4 columns) to eliminate horizontal whitespace.
- Render filtered views with a left sidebar as clean vertical list rows with mandatory Carbon pagination at the bottom.
- Eliminate vertically stacked data tables by placing a Carbon dropdown view switcher (`[ Registration ▾ ]`) inside a unified section card.
- Partition multi-faceted entities (modules, verified results, credentials) horizontally with Carbon tab bars.
- Default deep curriculum trees to collapsed accordion rows (`[ 7 micro-credentials ▾ ]`) to maintain clean initial page height.
- Style every CTA and interactive trigger with the tactile Carbon button system — never leave naked browser hyperlinks.

### Don't

- Don't round corners on buttons, cards, or inputs. Even 4px rounded corners break the Carbon look.
- Don't bold display headlines. Plex Sans at weight 300 is the brand voice; weight 700 makes it look generic.
- Don't add atmospheric depth (gradient backdrops, drop shadows, atmospheric overlays) outside the documented soft-blue hero gradient.
- Don't introduce a second brand color. uLearn Navy is the only chromatic accent; status semantics use the documented green / yellow / red.
- Don't replace IBM Plex Sans with Inter or Helvetica without preserving the `letter-spacing: 0.16px` and weight-300 display treatment.
- Don't use pill-shaped buttons. Carbon uses square corners; pills read as a different brand.
- Don't write all-caps tracked eyebrows. Carbon's eyebrows are sentence case at 14px.
- Don't force every list into square card tiles when a left filter sidebar is present.
- Don't leave naked, underlined blue hyperlinks for interactive actions, primary navigation, or workflow triggers.
- Don't stack multiple heavy data tables vertically down the page.
- Don't display unpaginated lists that stack dozens of rows down the page without a pagination bar.
- Don't use rounded, pill-shaped, or floating tabs with background fills; stick strictly to Carbon's flat 2px underline tabs.

## Responsive Behavior

### Breakpoints

| Name | Width | Key Changes |
|---|---|---|
| Max | 1584px | Carbon max grid; gutters expand |
| Desktop-XL | 1312px | Default desktop layout |
| Desktop | 1056px | Card grid 4-up maintained |
| Tablet | 672px | Card grid 4-up → 2-up; nav becomes hamburger |
| Mobile | 320px | Single-column; display-xl scales 76px → ~32px |

### Touch Targets

- Carbon spec: 48px minimum tap target. Buttons and inputs hold 48px on touch viewports.
- Top-nav links grow from 36px to 48px tap height on touch.
- Tab strip rows hold 48px tap height.

### Collapsing Strategy

- **Top nav**: links collapse to a hamburger overlay below 672px. Logomark and search icon stay on the bar.
- **Utility bar**: hides below 672px to reclaim vertical space.
- **Card grid**: 4-up → 2-up at 1056px → 1-up below 672px.
- **Display type**: `{typography.display-xl}` 76px scales toward 42px on mobile while preserving the weight-300 treatment.
- **Footer**: 6-column link grid → 3-column at tablet → 1-column at mobile.

### Image Behavior

- Customer logos in the marquee maintain aspect ratio and may collapse to 2-row scroll below 672px.
- Hero illustrations scale proportionally; below 672px they may stack above the headline rather than sit beside it.

## Iteration Guide

1. Focus on ONE component at a time and reference it by its `components:` token name.
2. Default body to `{typography.body}` at weight 400 with `letter-spacing: 0.16px`. Don't remove the tracking.
3. When introducing a new section, decide whether it sits on `{colors.canvas}` (default) or on `{colors.surface-1}` (alternate band). The two-surface rhythm is the rhythm.
4. Run `npx @google/design.md lint DESIGN.md` after edits.
5. Add new variants as separate component entries (`button-primary-pressed`, `text-input-error`, `text-input-focused`).
6. Treat uLearn Navy as scarce: links, primary CTA, CTA banner, focus underline. Anything beyond that is drift.
7. Resist rounded corners. If a designer pushes for 4px rounding, the brand is shifting away from Carbon.

## Known Gaps

- IBM's product surfaces (cloud-pak, watson, datacap) have richer Carbon component usage (data tables, graph cells, breadcrumbs, contextual menus) that aren't present on the marketing pages inspected — those components live in Carbon's documentation rather than in the marketing extraction.
- Form-field error and validation styling is documented in Carbon docs; the inspected pages didn't render error states.
- Dark mode is documented in Carbon as Gray-100 theme but isn't exposed on these marketing pages — only the footer inverts. The full dark theme is a separate Carbon palette not extracted here.
- The community.ibm.com sub-domain uses a different chrome (community-platform white-label) that approximates Carbon but isn't strict — the documented system applies to ibm.com proper.
