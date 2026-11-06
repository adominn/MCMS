@props(['category' => 'samc', 'size' => 48, 'code' => null])
@php
  $cat = strtolower((string) $category);
  $catLabel = strtoupper($cat ?: 'MC');
  $accentColor = match($cat) {
    'umc' => '#0f62fe',
    'samc' => '#24a148',
    'mce' => '#f1c21b',
    default => '#f1c21b'
  };
@endphp
<div class="digital-badge" style="width:{{ $size }}px;height:{{ $size }}px;flex-shrink:0;display:inline-block;vertical-align:middle;position:relative" title="{{ $catLabel }} Digital Badge &middot; Open Badges 3.0">
  <svg viewBox="0 0 100 100" width="{{ $size }}" height="{{ $size }}" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.15))">
    <defs>
      <linearGradient id="badge-bg-{{ $cat }}-{{ $size }}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#1e2a6e" />
        <stop offset="100%" stop-color="#0e1742" />
      </linearGradient>
      <linearGradient id="gold-rim-{{ $size }}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#fbd65c" />
        <stop offset="50%" stop-color="#c49a2a" />
        <stop offset="100%" stop-color="#fbd65c" />
      </linearGradient>
    </defs>

    <!-- Outer Scalloped / Octagonal Hexagon Base -->
    <polygon points="50,3 88,18 97,55 77,90 23,90 3,55 12,18" fill="url(#badge-bg-{{ $cat }}-{{ $size }})" stroke="url(#gold-rim-{{ $size }})" stroke-width="3.5" stroke-linejoin="round" />

    <!-- Inner Inscription Ring -->
    <circle cx="50" cy="50" r="35" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="1.2" stroke-dasharray="2,2" />
    <circle cx="50" cy="50" r="31" fill="#121a45" stroke="{{ $accentColor }}" stroke-width="1.8" />

    <!-- University & Seal Icons in Center -->
    <!-- Graduation Cap / Book Insignia -->
    <path d="M50 34 L68 42 L50 50 L32 42 Z" fill="#ffffff" />
    <path d="M38 46 L38 56 Q50 63 62 56 L62 46 Q50 52 38 46 Z" fill="url(#gold-rim-{{ $size }})" />
    <path d="M64 43.5 L64 54 L66 55 L66 44.5 Z" fill="#ffffff" />

    <!-- Stars for Verification -->
    <polygon points="50,62 51.5,65.5 55,66 52.5,68.5 53,72 50,70 47,72 47.5,68.5 45,66 48.5,65.5" fill="#fbd65c" />
    <circle cx="41" cy="67" r="1.5" fill="#fbd65c" />
    <circle cx="59" cy="67" r="1.5" fill="#fbd65c" />

    <!-- Category Ribbon at Top/Bottom -->
    <rect x="30" y="20" width="40" height="11" rx="2" fill="{{ $accentColor }}" />
    <text x="50" y="28" font-family="'IBM Plex Sans', -apple-system, sans-serif" font-size="7.5" font-weight="800" fill="{{ $cat === 'mce' ? '#161616' : '#ffffff' }}" text-anchor="middle" letter-spacing="0.5">UTeM {{ $catLabel }}</text>
  </svg>
</div>
