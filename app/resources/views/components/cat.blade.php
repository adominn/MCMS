@props(['c'])
<span class="cat" title="{{ ['umc' => 'Unbundled Micro-Credential', 'samc' => 'Standalone Micro-Credential', 'mce' => 'Micro-Credential Eksekutif'][strtolower((string) $c)] ?? '' }}">{{ strtoupper((string) $c) }}</span>
