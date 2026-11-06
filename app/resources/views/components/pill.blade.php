@props(['s', 'label' => null])
@php
  $t = mb_strtolower(trim((string) $s));
  $class = match (true) {
    (bool) preg_match('/revoked|expired|failed|rejected|not approved|overdue|suspended|withdrawn|cancelled|terminated|not verified|not met|declined|blocked|correction|broken/', $t) => 'err',
    (bool) preg_match('/pending|submitted|waiting|preparing|in review|draft|needs|expiring|held|more evidence|under evaluation|not yet|returned|ready to submit|postponed|invited|open$|in progress|not started|not ready|provisional/', $t) => 'warn',
    (bool) preg_match('/valid|issued|available|approved|published|quality-verified|confirmed|complete|enrolled|ready|deposited|paid|successful|verified|effective|closed|equivalent|achieved|met|passed|yes|corrected|replaced|consent given/', $t) => 'ok',
    default => '',
  };
@endphp
<span {{ $attributes->merge(['class' => 'tag ' . $class]) }}>{{ __($label ?? $s) }}</span>
