<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <title>{{ $progA->code }} vs {{ $progB->code }} &ndash; {{ __('Programme Comparison') }}</title>
  <style>
    body{font-family:"Plus Jakarta Sans",Arial,sans-serif;color:#090a0f;background:#fff;margin:24px;font-size:12px;line-height:1.4}
    h1{font-size:18px;margin:0 0 4px}
    h2{font-size:13px;margin:16px 0 6px;border-bottom:1px solid #090a0f;padding-bottom:3px}
    .meta{color:#475569;margin-bottom:14px}
    table{width:100%;border-collapse:collapse;margin-bottom:12px}
    th,td{border:1px solid #090a0f;padding:5px 7px;text-align:left;vertical-align:top}
    th{background:#f8fafc;font-size:11px;text-transform:uppercase}
    .num{text-align:right;font-variant-numeric:tabular-nums}
    .mono{font-family:"IBM Plex Mono",Consolas,monospace;font-size:11px}
    .note{border:1px solid #090a0f;padding:8px 10px;margin-top:16px;font-size:11px}
    .no-print{margin-bottom:14px}
    @media print{.no-print{display:none}}
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:6px 14px;background:#0052ff;color:#fff;border:none;border-radius:4px;cursor:pointer">{{ __('Print / Save as PDF') }}</button>
  </div>

  <h1>{{ __('Programme Comparison:') }} {{ $progA->code }} &rarr; {{ $progB->code }}</h1>
  <div class="meta">
    {{ __('From:') }} {{ $progA->code }} ({{ $progA->name }}, {{ $progA->total_credits }} cr) &middot;
    {{ __('To:') }} {{ $progB->code }} ({{ $progB->name }}, {{ $progB->total_credits }} cr) &middot;
    {{ __('Generated:') }} {{ now()->format('Y-m-d') }}
  </div>

  <table>
    <thead>
      <tr>
        <th>{{ __('Shared Credits') }}</th>
        <th>{{ __('Approved Equivalent Credits') }}</th>
        <th>{{ __('Not Carried Over') }}</th>
        <th>{{ __('Remaining in :code', ['code' => $progB->code]) }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['shared_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['approved_equiv_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['not_carried_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['remaining_credits_b']) }} cr</strong></td>
      </tr>
    </tbody>
  </table>

  <h2>{{ __('Shared Courses') }} ({{ $comparison['shared']->count() }})</h2>
  <table>
    <thead>
      <tr>
        <th style="width:95px">{{ __('Code') }}</th>
        <th>{{ __('Course') }}</th>
        <th class="num" style="width:60px">{{ __('Credits') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($comparison['shared'] as $row)
        <tr>
          <td class="mono">{{ $row->course->code }}</td>
          <td>{{ $row->course->name }}</td>
          <td class="num">{{ mcms_num($row->credits) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <h2>{{ __('Course Equivalences') }} ({{ $comparison['equivalences']->count() }})</h2>
  <table>
    <thead>
      <tr>
        <th>{{ $progA->code }}</th>
        <th>{{ $progB->code }}</th>
        <th>{{ __('Type') }}</th>
        <th class="num">{{ __('Credits') }}</th>
        <th>{{ __('Status') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($comparison['equivalences'] as $eq)
        <tr>
          <td class="mono">{{ $eq->from_code }}</td>
          <td class="mono">{{ $eq->to_code }}</td>
          <td>{{ __($eqTypes[$eq->equivalence_type] ?? $eq->equivalence_type) }}</td>
          <td class="num">{{ mcms_num($eq->credits_counted) }}</td>
          <td>{{ __($eqStatuses[$eq->status] ?? $eq->status) }}</td>
        </tr>
      @empty
        <tr><td colspan="5">{{ __('None recorded.') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="note">
    {{ __('This comparison is for academic planning only. Formal credit transfer and programme admission require evaluation and approval by UTeM. At least :min% of graduating credits must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent)]) }}
  </div>
</body>
</html>
