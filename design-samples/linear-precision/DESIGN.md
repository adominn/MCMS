---
version: 2.4
name: linear-precision
description: "A high-precision, architectural design language evolved from Carbon/IBM principles: pure white canvas (with full dark-mode obsidian counterpart), jet black typography, electric cobalt blue accents (#0052ff in light, #3880ff in dark), full-height left navigation drawer with 28px rounded corners, pure white header and floating search pill, collapsible title-case categories, high-contrast white items, and a pure white protruding pull-tab latch. Features subtle micro-radii (4px to 6px) across workspace elements and 12-28px curves on floating drawer components."

colors:
  # Light Theme Scale (Default)
  light:
    primary: "#0052ff"              # Electric Cobalt - core brand, primary actions, active states
    primary-hover: "#0045d8"        # Interactive hover state
    primary-pressed: "#0036a8"      # Pressed active state
    primary-deep: "#0a192f"         # Deep Navy Obsidian - dark accents and inverse hero tiles
    primary-tint: "#edf4ff"         # Airy ice blue wash for hovered rows and subtle highlights
    primary-line: "rgba(0, 82, 255, 0.28)"
    primary-glow: "rgba(0, 82, 255, 0.12)"
    canvas: "#ffffff"               # Pure white default background
    canvas-subtle: "#f8fafc"        # Ghost white for alternating sections
    surface-1: "#f1f5f9"            # Cool slate-50 for secondary panels & code blocks
    surface-2: "#e2e8f0"            # Cool slate-200 for hairline dividers & card borders
    surface-contrast: "#090a0f"     # Jet black slate for inverse modules
    sidebar-bg: "#0b1a3d"           # Deep Cobalt Navy left navigation background
    ink: "#090a0f"                  # Absolute Jet Black for headlines and high-contrast anchors
    ink-muted: "#475569"            # Slate-600 for body copy and readable paragraphs
    ink-subtle: "#94a3b8"           # Slate-400 for technical metadata, tags, and captions
    line-hairline: "#e2e8f0"        # 1px default hairline border
    line-strong: "#090a0f"          # 1px stark jet black outline
    line-accent: "#0052ff"          # 1px electric cobalt indicator line

  # Dark Theme Scale
  dark:
    primary: "#3880ff"              # Luminous Electric Cobalt - high visibility on dark surfaces
    primary-hover: "#5492ff"
    primary-pressed: "#2468e8"
    primary-deep: "#0f1c36"
    primary-tint: "rgba(56, 128, 255, 0.14)"
    primary-line: "rgba(56, 128, 255, 0.38)"
    primary-glow: "rgba(56, 128, 255, 0.25)"
    canvas: "#07090e"               # Pitch Jet Obsidian floor
    canvas-subtle: "#0b0f17"        # Dark slate-950 elevation
    surface-1: "#101522"            # Dark card & panel surface
    surface-2: "#182030"            # Dark input resting surface
    surface-contrast: "#ffffff"     # Inverted white for stark contrast
    sidebar-bg: "#07122a"           # Deep midnight navy sidebar drawer background
    ink: "#f8fafc"                  # Crisp white typography
    ink-muted: "#94a3b8"            # Slate-400 for secondary copy
    ink-subtle: "#64748b"           # Slate-500 for metadata & codes
    line-hairline: "#1c2436"        # 1px dark slate hairline rule
    line-strong: "#334155"          # Stronger boundary line
    line-accent: "#3880ff"          # Luminous cobalt indicator stroke

typography:
  font-primary: "Plus Jakarta Sans, -apple-system, BlinkMacSystemFont, sans-serif"
  font-mono: "JetBrains Mono, IBM Plex Mono, monospace"

  display-xl:
    fontFamily: "{typography.font-primary}"
    fontSize: "54px"
    fontWeight: "300"
    lineHeight: "1.10"
    letterSpacing: "-0.035em"
  display-lg:
    fontFamily: "{typography.font-primary}"
    fontSize: "44px"
    fontWeight: "300"
    lineHeight: "1.15"
  headline:
    fontFamily: "{typography.font-primary}"
    fontSize: "22px"
    fontWeight: "600"
    lineHeight: "1.30"
  body:
    fontFamily: "{typography.font-primary}"
    fontSize: "14px"
    fontWeight: "400"
    lineHeight: "1.55"
  mono-code:
    fontFamily: "{typography.font-mono}"
    fontSize: "12px"
    fontWeight: "500"
  eyebrow:
    fontFamily: "{typography.font-mono}"
    fontSize: "11px"
    fontWeight: "600"
    letterSpacing: "0.08em"
    textTransform: "uppercase"

# Controlled Micro-Radius: "Can have round corner but not too much"
rounded:
  none: "0px"
  xs: "2px"                       # Micro tags, inline badges
  sm: "4px"                       # Standard buttons, inputs, segmented controls
  md: "6px"                       # Standard cards, popovers, bento cells
  lg: "8px"                       # Hero containers, modals
  sidebar-corner: "28px"          # Top-Right and Bottom-Right sidebar contour
  search-pill: "12px"             # Floating search input container
  active-tab: "12px"              # Pure white active tab pill
  pull-tab-latch: "24px"          # Semi-pill right curve (0 24px 24px 0)
  pill: "9999px"                  # Status indicator pips only

sidebar-navigation:
  docking: "top: 0; height: 100vh (flush to top and bottom, zero gaps)"
  brand-header:
    background: "#ffffff (pure white box flush to top-left, 28px top-right radius)"
    logo-img: "Asset/LogoJawiUTeM_blue-01.png (Official full UTeM Jawi & Roman logo, centered, 220px max-width, 86px max-height)"
    collapsed-icon: "Asset/LogoUTeM_crest.png (Circular UTeM crest emblem centered in collapsed 64px header)"
    border: "none (seamless transition to deep blue sidebar body, zero blue border)"
  search-pill:
    background: "#ffffff (pure white floating pill)"
    radius: "12px"
    height: "42px"
    label: "Jump to token (13px, #64748b, with magnifying glass SVG)"
    badge: "⌘K (11px, #1e293b on #eef2f6 pill)"
    shadow: "0 2px 8px rgba(0, 0, 0, 0.14)"
  background: "#0b1a3d (rich cobalt navy in light, #07122a in dark)"
  border-right: "none (clean deep navy boundary without white border)"
  right-corner-radius: "28px (rounded top-right and bottom-right corners)"
  left-corner-radius: "0px (docked flush to left viewport)"
  right-space-channel: "14px gutter between drawer and workspace"
  pull-tab-latch:
    background: "#ffffff (pure white protruding semi-pill latch)"
    position: "Centered vertically at top 50%"
    geometry: "30px width x 52px height, protruding right (-18px)"
    radius: "0 24px 24px 0"
    icon: "< chevron (#0b1a3d, stroke 2.5px SVG)"
    shadow: "3px 0 14px rgba(0, 0, 0, 0.28)"
    interaction: "Click to toggle between expanded (260px) and collapsed (68px)"
  expanded-width: "260px"
  collapsed-width: "68px"
  collapsible: true
  categories:
    style: "Title Case (Architecture, Components, System app)"
    background: "transparent (blends seamlessly with navy sidebar)"
    chevron: "12px white downward SVG chevron rotating -90deg when collapsed"
    badges: "Spec, UI, MCMS (rounded pill capsules, rgba(255,255,255,0.08), color: #8fa0bc)"
    interaction: "Click category header to collapse/expand its items"
  active-tab:
    item: "Line anatomy"
    fill: "#ffffff (pure white 12px rounded pill card)"
    text: "#0b1a3d (bold deep navy 700 weight)"
    icon: "#0052ff (electric cobalt slider lines with left circle knob)"
    shadow: "0 4px 14px rgba(0, 0, 0, 0.22)"
  resting-tab:
    fill: "transparent"
    text: "#ffffff (crisp white 500 weight, 13.5px)"
    icons: "Precision geometric SVGs matching exact mockup (grid, sidebar, sliders, T, capsule, arc, input, check, queue)"
    hover: "rgba(255, 255, 255, 0.08) with 10px radius"
  user-account:
    location: "Moved to top-right of the command bar (.topbar-controls); removed from sidebar bottom"
    style: "Compact pill with cobalt avatar, full user name (Dr. Zulkifli), role (CODL Academic Officer), and online pip"
  custom-scroller:
    sidebar-scroll:
      width: "4px ultra-slim width (no bulky 16px default OS bar)"
      track: "transparent (no white or gray background strip)"
      buttons: "none (triangle arrow buttons hidden)"
      resting-thumb: "rgba(255, 255, 255, 0.06) (low contrast, blends into navy canvas)"
      hover-thumb: "rgba(255, 255, 255, 0.18)"
      radius: "9999px fully rounded pill"
    global-scroll:
      width: "6px subtle width"
      track: "transparent"
      thumb: "rgba(100, 116, 139, 0.20) in light, rgba(255, 255, 255, 0.12) in dark"

line-rules:
  grid-border-width: "1px"
  indicator-accent-width: "2px"
  corner-crosshairs: true         # Coordinate '+' markers at grid intersections
  linear-track-height: "1px"
  card-border-style: "solid"
